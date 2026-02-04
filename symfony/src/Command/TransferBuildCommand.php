<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(name: 'app:transfer-build')]
class TransferBuildCommand extends Command
{
    public function __construct(private ParameterBagInterface $paramBag)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Transfer build folder to remote FTP server');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $ftpHost = $_ENV['FTP_HOST'];
        $ftpUser = $_ENV['FTP_USER'];
        $ftpPass = $_ENV['FTP_PASS'];
        $ftpPort = $_ENV['FTP_PORT'] ?? 21;

        $projectDir = $this->getApplication()->getKernel()->getProjectDir();
        $buildDir = $projectDir . '/public/build';
        $zipFile = $projectDir . '/build.zip';

        if (!is_dir($buildDir)) {
            $io->error("Build directory not found: $buildDir");
            return Command::FAILURE;
        }

        $io->info("Creating build.zip...");
        if (!$this->zipBuildDir($buildDir, $zipFile, $io)) {
            $io->error("Failed to create build.zip");
            return Command::FAILURE;
        }

        $io->info("Connecting to FTP server (timeout: 15s)...");
        $ftpConn = @ftp_connect($ftpHost, (int)$ftpPort, 15);

        if (!$ftpConn) {
            $io->error("Failed to connect to FTP server: $ftpHost:$ftpPort");
            if (file_exists($zipFile)) {
                unlink($zipFile);
            }
            return Command::FAILURE;
        }

        if (!@ftp_login($ftpConn, $ftpUser, $ftpPass)) {
            $io->error("Failed to login to FTP server");
            ftp_close($ftpConn);
            if (file_exists($zipFile)) {
                unlink($zipFile);
            }
            return Command::FAILURE;
        }

        ftp_pasv($ftpConn, true);

        $io->info("Uploading build.zip...");
        if (!ftp_put($ftpConn, 'build.zip', $zipFile, FTP_BINARY)) {
            $io->error("Failed to upload build.zip");
            ftp_close($ftpConn);
            if (file_exists($zipFile)) {
                unlink($zipFile);
            }
            return Command::FAILURE;
        }

        ftp_close($ftpConn);

        if (file_exists($zipFile)) {
            unlink($zipFile);
            $io->info("Cleaned up local build.zip");
        }

        $io->newLine();
        $io->success("Transfer complete! Uploaded: build.zip");

        return Command::SUCCESS;
    }

    private function zipBuildDir(string $buildDir, string $zipFile, SymfonyStyle $io): bool
    {
        $zip = new \ZipArchive();
        
        if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($buildDir),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = str_replace($buildDir . DIRECTORY_SEPARATOR, '', $filePath);
                $zip->addFile($filePath, 'build/' . str_replace(DIRECTORY_SEPARATOR, '/', $relativePath));
            }
        }

        $zip->close();
        
        $size = filesize($zipFile);
        $io->info("Created build.zip (" . $this->formatBytes($size) . ")");
        
        return true;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

}
