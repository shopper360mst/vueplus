<?php

namespace App\Command;

use Mockery;
use App\Entity\Product;
use App\Entity\Submission;
use App\Service\CurlToUrlService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:approve-reject',
    description: 'Test Submission Approve/Reject',
)]
class ApproveRejectCommand extends Command
{
    private $manager;
    
    public function __construct(EntityManagerInterface $entityManager,CurlToUrlService $cus, ParameterBagInterface $paramBag, CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->manager = $entityManager;
        $this->cus = $cus;
        $this->paramBag = $paramBag;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('approveReject', InputArgument::REQUIRED, 'approve/reject');
        $this->addArgument('id', InputArgument::REQUIRED, 'sub id required');
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $failed = false;
        $error = '';
        $io = new SymfonyStyle($input, $output);
        $approveReject = ($input->getArgument('approveReject'));
        $id = ($input->getArgument('id'));
        try{
            $submission = $this->manager->getRepository(Submission::class)->findOneBy(
            [
                'id' => $id,
                'status' => 'PROCESSING'
            ]);

            if($approveReject == 'approve'){
                $status = 'APPROVED';
                $relatedSubmission = $this->manager->getRepository(Submission::class)->findBy([
                    'user'=> $submission->getUser(),
                    'status'=>'APPROVED',
                ]);
                if(count($relatedSubmission) < 3){
                    $foundAnyFreeProduct = $this->manager->getRepository(Product::class)->findAndLock($submission->getUser(),'GWP');
                    $productEntity = $this->manager->getRepository(Product::class)->find($foundAnyFreeProduct);
                    $productEntity->setDeliveryStatus('PROCESSING');
                    $productEntity->setReceiverFullName($submission->getReceiverFullName() != null ? $submission->getReceiverFullName() : NULL);
                    $productEntity->setReceiverMobileNo($submission->getReceiverMobileNo() != null ? $submission->getReceiverMobileNo() : NULL);
                    $productEntity->setAddress1($submission->getAddress1() != null ? $submission->getAddress1() : NULL);
                    $productEntity->setAddress2($submission->getAddress2() != null ? $submission->getAddress2() : NULL);
                    $productEntity->setCity($submission->getCity() != null ? $submission->getCity() : NULL);
                    $productEntity->setPostcode($submission->getPostcode() != null ? $submission->getPostcode() : NULL);
                    $productEntity->setState($submission->getState() != null ? $submission->getState() : NULL);
                    // $productEntity->setDetailsUpdatedDate(new \DateTime);
                    $productEntity->setRApprovedDate(new \DateTime);
                    $productEntity->setUpdatedDate(new \DateTime);
                    $productEntity->isContacted(false);
                    $productEntity->isDeleted(false);
    
                    $this->manager->persist($productEntity);     
                    $this->manager->flush();

                    $submission->setRStatus('APPROVED');
                    $submission->setRCheckedDate(new \DateTime());
                    $submission->setProductRef($productEntity->getId());
                    $this->manager->persist($submission);
                    $this->manager->flush();
                }
                else{
                    $submission->setRStatus('REJECTED');
                    $submission->setRCheckedDate(new \DateTime());
                    $submission->setField1('LIMIT REACHED');
                    $this->manager->persist($submission);     
                    $this->manager->flush();
                }
            }
            else{
                $status = 'REJECTED';
                $rejectReasons = ['TESTING','UNCLEAR IMAGE/NOT A RECEIPT','DUPLICATE RECEIPT','BELOW QUALIFYING AMOUNT','BELOW QUALIFYING QUANTITY',
                'NON PARTICIPATING PRODUCT','NON PARTICIPATING OUTLET','OUTSIDE CONTEST PERIOD','OUTSIDE COVERAGE'];
                $submission->setRejectReason($rejectReasons[rand(0,count($rejectReasons)-1)]);
                $submission->setRCheckedDate(new \DateTime());
                $submission->setRStatus($status);
            }
            $submission->setStatus($status);
            $submission->setUpdatedDate(new \DateTime());
            $submission->setSValidateDate(new \DateTime());
            $this->manager->persist($submission);
            $this->manager->flush();
    
            $io->success('Submission Approve/Reject Completed.');
        }
        catch(\Exception $e){
            $this->manager->getConnection()->rollBack();
            $failed =true;
            $output->writeln([
                '',
                'Error in Approving Or Reject..'.$e->getMessage()
            ]);
        }
        if($failed){
            $io->note($error);
        }

        return Command::SUCCESS;
    }
}
