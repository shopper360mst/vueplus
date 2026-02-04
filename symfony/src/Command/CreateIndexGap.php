<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\DBAL\Connection;

#[AsCommand(name: 'app:create-index-gap', description: 'Creates an ID gap by setting auto_increment to 500000 for user and submission tables')]
class CreateIndexGap extends Command
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $io->info('Starting to create index gap...');

            $this->setAutoIncrement('user', 500000, $io);
            $this->setAutoIncrement('submission', 500000, $io);

            $io->success('Index gap created successfully for user and submission tables');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function setAutoIncrement(string $tableName, int $startId, SymfonyStyle $io): void
    {
        $sql = "ALTER TABLE `{$tableName}` AUTO_INCREMENT = {$startId}";
        $this->connection->executeStatement($sql);
        $io->writeln("Set <info>{$tableName}</info> table AUTO_INCREMENT to <comment>{$startId}</comment>");
    }
}
