<?php

namespace App\Command;
use Symfony\Component\Console\Attribute\AsCommand;

use App\Service\CipherService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(name: 'app:cipher-command')]
class CipherCommand extends Command
{
    //protected static $defaultName = 'app:cipher-command';
    private $manager;    
    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager, ParameterBagInterface $paramBag, CipherService $cipher)
    {
        $this->doctrine = $registry;
        $this->manager = $entityManager;
        $this->paramBag = $paramBag;
        $this->cipher = $cipher;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Encrypts or decrypts messages using the application\'s cipher service');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {        
        $io = new SymfonyStyle($input, $output);
        try {
            $output->writeln('');

            $helper = $this->getHelper('question');
            
            $question1 = new Question('<fg=bright-yellow>Encrypt/Decrypt(E/D)?  ', 'encrypt');
            $ENCRYPT_DECRYPT = strtolower($helper->ask($input, $output, $question1));
            if(substr($ENCRYPT_DECRYPT, 0, 1) == 'e'){
                $ENCRYPT_DECRYPT = 'encrypt';
            }
            else{
                $ENCRYPT_DECRYPT = 'decrypt';
            }
            $output->writeln('<fg=bright-green>Cipher Selected = '.$ENCRYPT_DECRYPT);
            $output->writeln('');

            $question2 = new Question('<fg=bright-yellow>Message to '.$ENCRYPT_DECRYPT.' (default="message")  ', 'message');
            $message = $helper->ask($input, $output, $question2);
            $output->writeln('<fg=bright-green>Your Message = '.$message);
            $output->writeln('');
            if($ENCRYPT_DECRYPT == 'encrypt'){
                $result = $this->cipher->encrypt($message);
            }
            else{
                $result = $this->cipher->decrypt($message);
            }
            $output->writeln('<fg=bright-green>Your '.$ENCRYPT_DECRYPT.'ed message is '.$result);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $output->writeln('Error message');
            $output->writeln($e->getMessage());
            
            return Command::SUCCESS;
        }            
        return COMMAND::SUCCESS;
    }
}
