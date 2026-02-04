<?php

namespace App\Command;

use App\Entity\Product;
use App\Entity\Submission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:approve-reject-with-product',
    description: 'Approve/Reject submission and assign products based on field10',
)]
class ApproveRejectWithProductCommand extends Command
{
    private $manager;
    private $wmDeliveryCounter = 0;
    private $luggageWmDeliveryCounter = 0;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->manager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('id', InputArgument::REQUIRED, 'Submission ID');
        $this->addArgument('action', InputArgument::REQUIRED, 'approve or reject');
        $this->addOption('region', 'r', InputArgument::OPTIONAL, 'Region: wm or em (if not provided, determined from state)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $submissionId = $input->getArgument('id');
        $action = strtolower($input->getArgument('action'));
        $regionOption = $input->getOption('region');

        if (!in_array($action, ['approve', 'reject'])) {
            $io->error('Action must be "approve" or "reject"');
            return Command::FAILURE;
        }

        try {
            $this->manager->getConnection()->beginTransaction();

            $submission = $this->manager->getRepository(Submission::class)->find($submissionId);

            if (!$submission) {
                $io->error("Submission ID {$submissionId} not found");
                return Command::FAILURE;
            }

            $io->info("Processing Submission ID: {$submissionId}");
            $io->writeln("Current Status: {$submission->getStatus()}");
            $io->writeln("Field10 (Product Families): {$submission->getField10()}");

            if ($action === 'approve') {
                $this->approveSubmission($submission, $io, $regionOption);
            } else {
                $this->rejectSubmission($submission, $io);
            }

            $this->manager->flush();
            $this->manager->getConnection()->commit();

            $io->success('Operation completed successfully');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            if ($this->manager->getConnection()->isTransactionActive()) {
                $this->manager->getConnection()->rollBack();
            }
            $io->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function approveSubmission(Submission $submission, SymfonyStyle $io, ?string $regionOption): void
    {
        if (!$submission->getReceiverFullname() || !$submission->getReceiverMobileNo()) {
            $io->error('Submission missing receiver_fullname or receiver_mobile_no');
            throw new \Exception('Missing required receiver information');
        }

        $field10 = $submission->getField10();
        if (!$field10) {
            $io->error('No product families specified in field10');
            throw new \Exception('field10 is required for approval');
        }

        $familyIds = array_map('trim', explode(',', $field10));
        $familyMap = [
            '1' => 'LUGGAGE_SHM',
            '2' => 'RUMMY_SHM',
            '3' => 'GRILL_SHM'
        ];

        $region = $this->determineRegion($submission, $regionOption, $io);
        $io->writeln("Using region: <info>{$region}</info>");

        $productsAssigned = 0;

        foreach ($familyIds as $familyId) {
            if (!isset($familyMap[$familyId])) {
                $io->warning("Unknown product family ID: {$familyId} (valid: 1=LUGGAGE, 2=RUMMY, 3=GRILL)");
                continue;
            }

            $category = $familyMap[$familyId];

            if ($this->hasFamilyBeenClaimed($submission, $category, $io)) {
                $io->warning("Product family {$category} already claimed by this user");
                continue;
            }

            $product = $this->assignProductToSubmission($submission, $category, $region, $io);
            if ($product) {
                $productsAssigned++;
                $io->writeln("<info>✓</info> Assigned {$category} (Product ID: {$product->getId()})");
            } else {
                $io->warning("No available products for {$category} in region {$region}");
            }
        }

        if ($productsAssigned === 0) {
            $io->error('No products could be assigned');
            throw new \Exception('Product assignment failed');
        }

        $submission->setStatus('APPROVED');
        $submission->setUpdatedDate(new \DateTime());
        $this->manager->persist($submission);

        $io->success("Submission approved with {$productsAssigned} product(s) assigned");
    }

    private function rejectSubmission(Submission $submission, SymfonyStyle $io): void
    {
        $submission->setStatus('REJECTED');
        $submission->setUpdatedDate(new \DateTime());
        $this->manager->persist($submission);
        $io->success('Submission rejected');
    }

    private function determineRegion(Submission $submission, ?string $regionOption, SymfonyStyle $io): string
    {
        if ($regionOption) {
            $region = strtolower($regionOption);
            if (!in_array($region, ['wm', 'em'])) {
                throw new \Exception('Region must be "wm" or "em"');
            }
            return $region;
        }

        $state = strtoupper($submission->getState() ?? '');
        $emStates = ['SABAH', 'SARAWAK', 'LABUAN'];

        if (in_array($state, $emStates)) {
            return 'em';
        }

        return 'wm';
    }

    private function hasFamilyBeenClaimed(Submission $submission, string $category, SymfonyStyle $io): bool
    {
        $parts = explode('_', $category);
        $family = $parts[0];

        $nationalId = $submission->getNationalId();
        $mobileNo = $submission->getMobileNo();

        $qb = $this->manager->getRepository(Submission::class)->createQueryBuilder('s');
        $matchingSubmissions = $qb
            ->where('s.status = :status')
            ->andWhere('(s.national_id = :nationalId OR s.mobile_no = :mobileNo)')
            ->andWhere('s.product_ref IS NOT NULL')
            ->setParameter('status', 'APPROVED')
            ->setParameter('nationalId', $nationalId)
            ->setParameter('mobileNo', $mobileNo)
            ->getQuery()
            ->getResult();

        foreach ($matchingSubmissions as $approvedSub) {
            if ($approvedSub->getId() === $submission->getId()) {
                continue;
            }

            $productRef = $approvedSub->getProductRef();
            if (!$productRef) {
                continue;
            }

            $productIds = explode(',', $productRef);
            $productIds = array_map('trim', $productIds);

            foreach ($productIds as $productId) {
                if (!$productId) {
                    continue;
                }
                $product = $this->manager->getRepository(Product::class)->find($productId);
                if ($product) {
                    $productCategory = $product->getProductCategory();
                    if ($productCategory) {
                        $productFamily = explode('_', $productCategory)[0];
                        if ($productFamily === $family) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    private function assignProductToSubmission(Submission $submission, string $category, string $region, SymfonyStyle $io): ?Product
    {
        $foundProductId = $this->manager->getRepository(Product::class)->findAndLock($submission->getUser(), $category, $region);

        if (!$foundProductId) {
            return null;
        }

        $product = $this->manager->getRepository(Product::class)->find($foundProductId);
        if (!$product) {
            return null;
        }

        $lockedDate = $product->getLockedDate();
        if ($lockedDate) {
            $dueDate = $this->addCalendarDays($lockedDate, 5);
            $product->setDueDate($dueDate);
        }

        if ($region === 'wm') {
            $categoryParts = explode('_', $category);
            $family = $categoryParts[0];

            if ($family === 'LUGGAGE') {
                $mod = $this->luggageWmDeliveryCounter % 3;
                $deliveryAssign = ($mod === 0) ? 'TS' : 'SMX';
                $this->luggageWmDeliveryCounter++;
            } else {
                $deliveryAssign = ($this->wmDeliveryCounter % 2 === 0) ? 'TS' : 'SMX';
                $this->wmDeliveryCounter++;
            }
            $product->setDeliveryAssign($deliveryAssign);
        } elseif ($region === 'em') {
            $product->setDeliveryAssign('GDEX');
        }

        $product->setDeliveryStatus('PROCESSING');
        $product->setCourierStatus('PROCESSING');
        $product->setReceiverFullname($submission->getReceiverFullname());
        $product->setReceiverMobileNo($submission->getReceiverMobileNo());
        $product->setAddress1($submission->getAddress1());
        $product->setAddress2($submission->getAddress2());
        $product->setCity($submission->getCity());
        $product->setPostcode($submission->getPostcode());
        $product->setState($submission->getState());
        $product->setDetailsUpdatedDate(new \DateTime());
        $product->setUpdatedDate(new \DateTime());
        $product->setContacted(false);
        $product->setDeleted(false);
        $product->setSubId($submission);

        $this->manager->persist($product);
        $submission->addProductRef($product->getId());
        $this->manager->persist($submission);

        return $product;
    }

    private function addCalendarDays(\DateTime $startDate, int $days): \DateTime
    {
        $date = clone $startDate;
        $date->add(new \DateInterval("P{$days}D"));
        return $date;
    }
}
