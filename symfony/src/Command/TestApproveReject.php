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
    name: 'app:test-approve-reject',
    description: 'Test Submission Approve/Reject',
)]
class TestApproveReject extends Command
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
        $batchSizes = 20;

        try{
            $status = ['APPROVED','REJECTED'];
            $rejectReasons = ['Testing','Outside contest period','Duplicate receipt','Invalid receipt','Illegible product',
            'Receipt not clear','INSUFFICIENT PURCHASE QUANTITY','ILLEGIBLE OUTLET','INCOMPLETE INFORMATION',
            'INSUFFICIENT PURCHASE AMOUNT'];
        
            $submission = $this->manager->getRepository(Submission::class)->findBy(
                ['status' => 'PROCESSING'],
                null);
            for ($i=0; $i < count($submission); $i++) { 
                $selectedStatus = $status[rand(0,count($status)-1)];
                $submission[$i]->setStatus($selectedStatus);
                if($selectedStatus != 'APPROVED'){
                    $submission[$i]->setRejectReason($rejectReasons[rand(0,count($rejectReasons)-1)]);
                }
                else{
                    $relatedSubmission = $this->manager->getRepository(Submission::class)->findBy([
                        'user'=> $submission[$i]->getUser(),
                        'status'=>'APPROVED',
                    ]);
                    
                    // IF FILTER NEEDED.
                    $filteredSubmissions = array_filter($relatedSubmission, function($submission) {
                        return $submission->getSubmitCode() !== 'CVSTOFT';
                    });
                    if($submission[$i]->getSubmitCode() == 'GWP'){
                        if(count($filteredSubmissions) < 3){
                            $foundAnyFreeProduct = $this->manager->getRepository(Product::class)->findAndLock($submission[$i]->getUser(),'GWP');
                            if(isset($foundAnyFreeProduct)){
                                $productEntity = $this->manager->getRepository(Product::class)->find($foundAnyFreeProduct);
                                $productEntity->setDeliveryStatus('PROCESSING');
                                $productEntity->setReceiverFullName($submission[$i]->getReceiverFullName() != null ? $submission[$i]->getReceiverFullName() : NULL);
                                $productEntity->setReceiverMobileNo($submission[$i]->getReceiverMobileNo() != null ? $submission[$i]->getReceiverMobileNo() : NULL);
                                $productEntity->setAddress1($submission[$i]->getAddress1() != null ? $submission[$i]->getAddress1() : NULL);
                                $productEntity->setAddress2($submission[$i]->getAddress2() != null ? $submission[$i]->getAddress2() : NULL);
                                $productEntity->setCity($submission[$i]->getCity() != null ? $submission[$i]->getCity() : NULL);
                                $productEntity->setPostcode($submission[$i]->getPostcode() != null ? $submission[$i]->getPostcode() : NULL);
                                $productEntity->setState($submission[$i]->getState() != null ? $submission[$i]->getState() : NULL);
                                $productEntity->setDetailsUpdatedDate(new \DateTime);
                                $productEntity->setUpdatedDate(new \DateTime);
                                $productEntity->isContacted(false);
                                $productEntity->isDeleted(false);
                
                                $this->manager->persist($productEntity);     
                                $this->manager->flush();
                                $submission[$i]->setProductRef($productEntity->getId());
                            }
                        }
                        else{
                            $submission->setField1('LIMIT REACHED');
                            $this->manager->persist($submission[$i]);     
                            $this->manager->flush();
                        }
                    }
    
                }
                $this->manager->persist($submission[$i]);
                $this->manager->flush();
            }
        }
        catch(\Exception $e){
            $io->error($e->getMessage());
        }

        if($failed){
            $io->note($error);
        }
        $io->success('Submission Approve/Reject Completed.');

        return Command::SUCCESS;
    }
}
