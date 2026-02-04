<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:access-mode-status',
    description: 'Show the current access mode status'
)]
class AccessModeStatusCommand extends Command
{
    private ParameterBagInterface $params;
    
    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        try {
            $currentMode = $this->params->get('app.access_mode');
        } catch (\Exception $e) {
            $currentMode = 'normal'; // Default if parameter doesn't exist
        }

        $io->title('Access Mode Status');
        
        if ($currentMode === 'admin-only') {
            $io->warning('System is currently in ADMIN-ONLY mode');
            $io->writeln('• All non-admin routes are redirected to /access');
            $io->writeln('• Only /admin, /access, /logout, and /report routes are accessible');
            $io->writeln('• Assets and API routes are still accessible');
        } else {
            $io->success('System is currently in NORMAL mode');
            $io->writeln('• All routes are accessible normally');
        }

        $io->note("Current mode: $currentMode");
        $io->writeln('To change mode, use: php bin/console app:toggle-access-mode [admin-only|normal]');

        return Command::SUCCESS;
    }
}