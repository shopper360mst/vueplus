<?php

namespace App\Command;

use Mockery;
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
    name: 'app:test-submission-api',
    description: 'Test Submission API',
)]
class TestSubmissionApi extends Command
{
    private $manager;
    
    public function __construct(EntityManagerInterface $entityManager,CurlToUrlService $cus, ParameterBagInterface $paramBag, CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->manager = $entityManager;
        $this->cus = $cus;
        $this->paramBag = $paramBag;
        $this->csrfTokenManager = $csrfTokenManager;
        parent::__construct();
        $this->csrfTokenManager = Mockery::mock(CsrfTokenManagerInterface::class);
        $this->csrfTokenManager->shouldReceive('getToken')
            ->with('Bearer')
            ->andReturn(new CsrfToken('Bearer', $_SERVER['CSRF_TOKEN']));
    }

    protected function configure(): void
    {
        $this
            ->addArgument('excelFile', InputArgument::REQUIRED, 'Excel Path Required: /excel/test-data.xlsx')
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $excelFile = $input->getArgument('excelFile');
        if (!$excelFile) {
            $excelFile = $io->ask('Excel Path Required: (/excel/test-data.xlsx) or (/excel/test-data.csv)');
            $input->setArgument('excelFile', $excelFile);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $failed = false;
        $error = '';
        $io = new SymfonyStyle($input, $output);
        $excelFile = $input->getArgument('excelFile');
        if (!file_exists(__DIR__.$excelFile)) {
            $io->error('Error in finding sample-product excel file. Check your filename and path.');
            return Command::SUCCESS;
        }

        try{
            $reader = ReaderEntityFactory::createReaderFromFile($excelFile);
            $reader->open(__DIR__.$excelFile);
            $rowIndex = 0;
            $batchSizes = 500;
            $token = $this->csrfTokenManager->getToken('Bearer')->getValue();
            $imagePath  = __DIR__.'/dist/default_data/default-receipt.jpeg';
            if(!file_exists($imagePath)){
                $io->error('Default Receipt File not found.');
                return Command::SUCCESS;
            }
            $imageData = file_get_contents($imagePath);
            $defaultReceipt = base64_encode($imageData);
            $ccArray = ['MONT','CVSTOFT','SHM','S99'];

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ( $sheet->getRowIterator() as $row ) {
                    $cellsPerRow = $row->getCells();
                    if ($rowIndex != 0) {
                        $channel = $ccArray[rand(0,count($ccArray)-1)];
                        $postData = [
                            'channel' => $channel,
                            'form_code' => $channel,
                            'full_name' => $cellsPerRow[0]->getValue(),
                            'mobile_no' => $cellsPerRow[1]->getValue(),
                            'email' => $cellsPerRow[2]->getValue(),
                            'national_id' => $cellsPerRow[3]->getValue(),
                            'gender' => $cellsPerRow[4]->getValue(),
                            'upload_receipt' => 'data:image/jpeg;base64,' .$defaultReceipt,
                            'receipt_no' => rand(111111,999999)
                        ];
                        $headers = [
                            "Content-Type: application/json",
                            'Authorization: Bearer '.$token
                        ];
                        $response = $this->cus->curlToUrl($this->paramBag->get('app.base_url').'/endpoint/submit', null, true, $postData, $headers);
                        if($response == ""){
                            $io->error('Test Case #'.$rowIndex.' failed');
                            $failed = true;
                            $error = $response;
                        }
                        else{
                            $decoded = json_decode($response);
                            if($decoded == null){
                                $failed = true;
                                $error.=" Response is NULL.";
                            }
                            else{
                                $io->success('Incoming Response: '.$decoded->message);
                                if($decoded->message == '00000' || $decoded->message ==  '00603'){
                                    $io->success('Test Case #'.$rowIndex.' passed');
                                }
                                else{
                                    $io->error('Test Case #'.$rowIndex.' failed');
                                    $failed = true;
                                    $error = $decoded->message;
                                    if($decoded->message ==  '00400'){
                                        $error.=" Invalid Method.";
                                    }
                                    if($decoded->message ==  '00401'){
                                        $error.=" Session Out/Security Measured.";
                                    }
                                }
                            }
                        }
                    }
                    $rowIndex++;
                    if($failed){
                        break;
                    }
                }
                if($failed){
                    break;
                }
            }
            
        }
        catch(\Exception $e){
            $io->error('Error in processing the xlxs file.');
            $io->error($e->getMessage());
        }

        if($failed){
            $io->note($error);
        }
        $io->success('Submission Test Case Completed.');

        return Command::SUCCESS;
    }
}
