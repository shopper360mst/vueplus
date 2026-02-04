<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;

#[AsCommand(
    name: 'app:sync-receipt',
    description: 'Syncs receipt files - displays files in public folder not available on S3 bucket'
)]
class SyncReceipt extends Command
{
    public function __construct(private ParameterBagInterface $paramBag)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'auto',
            null,
            InputOption::VALUE_NONE,
            'Automatically upload missing files to S3 without confirmation'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $bucketName = $this->paramBag->get('app.s3_bucket_name');
            $io->info('Connecting to S3 bucket: ' . $bucketName);
            
            $S3Client = new S3Client([
                'endpoint'    => $this->paramBag->get('app.s3_base_url'),
                'region'      => $this->paramBag->get('app.s3_region_name'),
                'credentials' => [
                    'key'    => $this->paramBag->get('app.s3_secret_access'),
                    'secret' => $this->paramBag->get('app.s3_secret_key'),
                ]
            ]);

            $adapter = new AwsS3V3Adapter(
                $S3Client,
                $bucketName
            );
            $filesystem = new Filesystem($adapter);

            $io->info('Fetching files from S3 bucket...');
            $s3Files = [];
            $contents = $filesystem->listContents('/', true);
            
            foreach ($contents as $item) {
                if ($item->isFile()) {
                    $s3Files[] = basename($item->path());
                }
            }

            $io->info('Scanning local receipt directory...');
            $receiptDir = __DIR__ . '/../../public/images/uploaded/receipt';
            $localFiles = [];
            
            if (is_dir($receiptDir)) {
                $files = scandir($receiptDir);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..' && is_file($receiptDir . '/' . $file)) {
                        $localFiles[] = $file;
                    }
                }
            }

            $io->newLine();
            $io->section('Comparing Files');

            $missingInS3 = array_diff($localFiles, $s3Files);

            if (empty($missingInS3)) {
                $io->success('All local receipt files are synced to S3!');
            } else {
                $io->warning('Found ' . count($missingInS3) . ' file(s) in public folder NOT available on S3 bucket:');
                $io->newLine();

                $table = new Table($output);
                $table->setHeaders(['File Name', 'Local Path']);
                
                foreach ($missingInS3 as $file) {
                    $table->addRow([
                        $file,
                        '/public/images/uploaded/receipt/' . $file
                    ]);
                }
                
                $table->render();

                $io->newLine();
                $autoUpload = $input->getOption('auto');
                $shouldUpload = $autoUpload || $io->confirm('Do you want to upload these files to S3 bucket?');
                
                if ($shouldUpload) {
                    $io->newLine();
                    $io->section('Uploading Files');
                    
                    $progressBar = $io->createProgressBar(count($missingInS3));
                    $progressBar->start();

                    foreach ($missingInS3 as $file) {
                        try {
                            $localPath = $receiptDir . '/' . $file;
                            $fileContent = file_get_contents($localPath);
                            $filesystem->write($file, $fileContent);
                            $progressBar->advance();
                        } catch (\Exception $e) {
                            $progressBar->finish();
                            $io->newLine();
                            $io->error('Failed to upload ' . $file . ': ' . $e->getMessage());
                            $progressBar->start();
                        }
                    }

                    $progressBar->finish();
                    $io->newLine();
                    $io->success('All files uploaded successfully to S3 bucket!');
                } else {
                    $io->note('Upload cancelled.');
                }
            }

            $io->newLine();
            $io->info('Summary:');
            $io->text([
                'Total files in S3: ' . count($s3Files),
                'Total files locally: ' . count($localFiles),
                'Files missing in S3: ' . count($missingInS3),
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
