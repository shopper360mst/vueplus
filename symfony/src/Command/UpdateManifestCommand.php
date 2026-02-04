<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:update-manifest',
    description: 'Update manifest.yaml with files changed in the last N commits'
)]
class UpdateManifestCommand extends Command
{
    private string $projectDir;

    public function __construct(string $projectDir = null)
    {
        $this->projectDir = $projectDir ?? dirname(__DIR__, 2);
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('commits', InputArgument::OPTIONAL, 'Number of commits to look back', 3);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $commitCount = (int) $input->getArgument('commits');

        $manifestPath = $this->projectDir . '/src/Command/deploy/manifest.yaml';

        if (!file_exists($manifestPath)) {
            $io->error("Manifest file not found: {$manifestPath}");
            return Command::FAILURE;
        }

        $io->note("Fetching changed files from the last {$commitCount} commits...");

        // Get changed files from git
        $process = new Process(['git', 'log', "-n", (string)$commitCount, '--pretty=format:', '--name-only']);
        $process->run();

        if (!$process->isSuccessful()) {
            $io->error("Git command failed: " . $process->getErrorOutput());
            return Command::FAILURE;
        }

        $outputFiles = explode("\n", $process->getOutput());
        $files = array_filter(array_unique(array_map('trim', $outputFiles)), function($file) {
            return !empty($file) && !str_contains($file, '.DS_Store');
        });

        sort($files);

        if (empty($files)) {
            $io->warning("No changed files found in the last {$commitCount} commits.");
            return Command::SUCCESS;
        }

        $io->section("Files found:");
        foreach ($files as $file) {
            $io->writeln("  - {$file}");
        }

        // Prepare YAML content
        $yamlFiles = "files:\n";
        foreach ($files as $file) {
            $yamlFiles .= "  - \"/{$file}\"\n";
        }

        // Read manifest.yaml and replace the files section
        $content = file_get_contents($manifestPath);
        
        // Find the 'files:' section and replace everything after it (or just the section)
        if (preg_match('/files:\s*(\n\s*-.*)*/s', $content)) {
            $newContent = preg_replace('/files:\s*(\n\s*-.*)*/s', trim($yamlFiles), $content);
        } else {
            $newContent = $content . "\n" . $yamlFiles;
        }

        file_put_contents($manifestPath, $newContent);

        $io->success("Successfully updated manifest.yaml with " . count($files) . " files.");

        return Command::SUCCESS;
    }
}
