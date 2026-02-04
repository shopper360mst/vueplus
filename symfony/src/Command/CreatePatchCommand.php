<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

#[AsCommand(
    name: 'app:create-patch',
    description: 'Copy files specified in manifest.yaml to deploy/output folder'
)]
class CreatePatchCommand extends Command
{
    private Filesystem $filesystem;
    private string $projectDir;

    public function __construct(string $projectDir = null)
    {
        $this->filesystem = new Filesystem();
        $this->projectDir = $projectDir ?? dirname(__DIR__, 2);
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $manifestPath = $this->projectDir . '/src/Command/deploy/manifest.yaml';
        $outputDir = $this->projectDir . '/src/Command/deploy/output';

        if (!file_exists($manifestPath)) {
            $io->error("Manifest file not found: {$manifestPath}");
            return Command::FAILURE;
        }

        try {
            $manifest = Yaml::parseFile($manifestPath);
        } catch (\Exception $e) {
            $io->error("Failed to parse manifest: " . $e->getMessage());
            return Command::FAILURE;
        }

        if (empty($manifest['files']) || !is_array($manifest['files'])) {
            $io->warning("No files specified in manifest");
            return Command::SUCCESS;
        }
        
        if ($this->filesystem->exists($outputDir)) {
            $this->filesystem->remove($outputDir);
        }
        
        $this->filesystem->mkdir($outputDir);
        $io->section("Selected Deploy Files");
        $io->text("Manifest: {$manifestPath}");
        $io->text("Output: {$outputDir}");
        $io->newLine();

        $successCount = 0;
        $failureCount = 0;

        foreach ($manifest['files'] as $filePath) {
            $filePath = trim($filePath);
            
            if (empty($filePath)) {
                continue;
            }

            $sourcePath = $this->projectDir . $filePath;
            $destinationPath = $outputDir . $filePath;

            if (!file_exists($sourcePath)) {
                $io->warning("Source not found: {$filePath}");
                $failureCount++;
                continue;
            }

            try {
                if (is_dir($sourcePath)) {
                    $this->filesystem->mirror($sourcePath, $destinationPath, null, [
                        'override' => true,
                        'delete' => false
                    ]);
                } else {
                    $this->filesystem->copy($sourcePath, $destinationPath, true);
                }

                $io->writeln("  <info>✓</info> {$filePath}");
                $successCount++;
            } catch (IOExceptionInterface $e) {
                $io->warning("Failed to copy {$filePath}: " . $e->getMessage());
                $failureCount++;
            }
        }

        $io->newLine();
        $io->success("File copy complete: {$successCount} succeeded, {$failureCount} failed");

        return $failureCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
