<?php

namespace App\Command;
use Symfony\Component\Console\Attribute\AsCommand;

use App\Entity\User;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


#[AsCommand(name: 'app:create-role-users', description: 'Creates role_tristar and role_cc user accounts')]
class CreateRoleUsers extends Command
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
        $this->addArgument('password', InputArgument::OPTIONAL, 'Password for both accounts (default: shopper1100)');        
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $defaultPassword = ($input->getArgument('password')) ? $input->getArgument('password') : "shopper1100";

        // Create role_tristar user
        $tristarUser = new User();
        $tristarUser->setFullName("Tristar Administrator");
        $tristarUser->setUsername("admin@tristar.com");
        $tristarUser->setEmail("admin@tristar.com");
        $tristarUser->setMobileNo("0207654321");
        $roles = array("ROLE_TRISTAR");
        $tristarUser->setRoles($roles);
        $tristarUser->setActive(1);
        $tristarUser->setGuest(0);
        $tristarUser->setDeleted(0);
        $tristarUser->setVisible(0);
        $tristarUser->setCreatedDate(new \DateTime());
        
        $tristarUser->setPassword( 
            $this->passwordEncoder->hashPassword(
                $tristarUser,
                $defaultPassword
            )
        );
        
        $this->manager->persist($tristarUser);

        // Create role_cc user
        $ccUser = new User();
        $ccUser->setFullName("CC Administrator");
        $ccUser->setUsername("admin@cc.com");
        $ccUser->setEmail("admin@cc.com");
        $ccUser->setMobileNo("0207654321");
        $roles = array("ROLE_CC");
        $ccUser->setRoles($roles);
        $ccUser->setActive(1);
        $ccUser->setGuest(0);
        $ccUser->setDeleted(0);
        $ccUser->setVisible(0);
        $ccUser->setCreatedDate(new \DateTime());
        
        $ccUser->setPassword( 
            $this->passwordEncoder->hashPassword(
                $ccUser,
                $defaultPassword
            )
        );
        
        $this->manager->persist($ccUser);
        $this->manager->flush(); 

        $output->writeln([
            '',
            'Role users created successfully:',
            '- admin@tristar.com with ROLE_TRISTAR',
            '- admin@cc.com with ROLE_CC',
            'Password: ' . $defaultPassword
        ]);
        
        return Command::SUCCESS;
    }
}