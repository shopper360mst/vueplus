<?php

namespace App\Command;
use App\Entity\Product;
use App\Entity\Submission;
use App\Entity\TrxSubmission;
use App\AppBundle\Util\EnumError;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:process-trx-sub')]
class ProcessTrxSubmissionCommand extends Command
{

    private $passwordEncoder;
    private $manager;
    
    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $encoder)
    {
        $this->passwordEncoder = $encoder;
        $this->manager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Processes transaction submissions and updates their status in the database');
        // $this->addArgument('parameter', InputArgument::OPTIONAL, 'Parameter required');        
    }
 
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        // To get Parameter use 
        // $inputParameterValue = ($input->getArgument('parameter'))?$input->getArgument('parameter'):"defaultValue";

        try {            
            $trxSubmission = $this->manager->getRepository(TrxSubmission::class)->findBy([
                'is_completed' => 0
            ]);
            if(isset($trxSubmission)){
                foreach ($trxSubmission as $key => $value) {
                    // If sub_id is set, update existing submission
                    if($value->getSubId()) {
                        $submission = $this->manager->getRepository(Submission::class)->findOneBy(['id' => $value->getSubId()]);
                        if($submission) {
                            $submission->setSubmitType($value->getSubmitType());
                            $submission->setStatus($value->getSubStatus());
                            if($value->getSubStatus() != 'APPROVED'){
                                $submission->setRejectReason($value->getRejectReason());
                            }
                            $this->manager->persist($submission);     
                            $this->manager->flush();
                        }
                    }
    
                    // $relatedSubmission = $this->manager->getRepository(Submission::class)->findBy([
                    //     'user'=> $submission->getUser(),
                    //     'status'=>'APPROVED',
                    // ]);
                    // IF FILTER NEEDED.
                    // $filteredSubmissions = array_filter($relatedSubmission, function($submission) {
                    //     return $submission->getSubmitCode() !== 'CVSTOFT';
                    // });
                    /* 
                    // PRODUCT ASSIGNMENT FEATURE COMMENTED OUT
                    if (strtoupper($value->getSubStatus()) == "APPROVED" && $submission->getSubmitCode()  == 'GWP') {
                        if(count($filteredSubmissions) < 3){
                            if ($submission->getProductRef() != null) {
                                $ERROR_CODE = EnumError::SESSION_OUT;
                                return $ERROR_CODE; 
                            }
                            if ($submission->getSubmitType() == null) {
                                $ERROR_CODE = EnumError::SESSION_OUT;
                                return $ERROR_CODE; 
                            }
                            $productCategory = 'GWP';
                            $foundAnyFreeProduct = $this->manager->getRepository(Product::class)->findAndLock($submission->getUser(),$productCategory);
                            $productEntity = $this->manager->getRepository(Product::class)->find($foundAnyFreeProduct);
                            $productEntity->setDeliveryStatus('PROCESSING');
                            $productEntity->setReceiverFullname($submission->getReceiverFullname() != null ? $submission->getReceiverFullname() : NULL);
                            $productEntity->setReceiverMobileNo($submission->getReceiverMobileNo() != null ? $submission->getReceiverMobileNo() : NULL);
                            $productEntity->setAddress1($submission->getAddress1() != null ? $submission->getAddress1() : NULL);
                            $productEntity->setAddress2($submission->getAddress2() != null ? $submission->getAddress2() : NULL);
                            $productEntity->setCity($submission->getCity() != null ? $submission->getCity() : NULL);
                            $productEntity->setPostcode($submission->getPostcode() != null ? $submission->getPostcode() : NULL);
                            $productEntity->setState($submission->getState() != null ? $submission->getState() : NULL);
                            $productEntity->setDetailsUpdatedDate(new \DateTime);
                            $productEntity->setUpdatedDate(new \DateTime);
                            $productEntity->isContacted(false);
                            $productEntity->isDeleted(false);
                            $this->manager->persist($productEntity);     
                            $this->manager->flush();
                            $submission->setProductRef($productEntity->getId());
                            $this->manager->persist($submission);     
                            $this->manager->flush();
                        
                        }
                        else{
                            $submission->setField1('LIMIT REACHED');
                            $this->manager->persist($submission);     
                            $this->manager->flush();
                        }
                    }
                    */
    
                    $value->setCompleted(true);
                    $value->setCompletedDate(new \DateTime());
                    $this->manager->persist($value);     
                    $this->manager->flush();
                }
            }
            $io->success('Trx Submission Command Completed.');

        } catch( \Exception $e) {
            $this->manager->getConnection()->rollBack();
            $output->writeln([
                '',
                $e->getMessage()
            ]);
        }
        
        return Command::SUCCESS;
        
    }
}
