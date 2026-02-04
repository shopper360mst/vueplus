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


#[AsCommand(name: 'app:create-admin', description: 'Creates an admin user account with specified credentials')]
class CreateAdmin extends Command
{
    //protected static $defaultName = 'app:create-admin';
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
        $this->addArgument('email', InputArgument::OPTIONAL, 'Email required');        
        $this->addArgument('password', InputArgument::OPTIONAL, 'Password required');
        $this->addArgument('username', InputArgument::OPTIONAL, 'Username required');        
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $defaultEmail = ($input->getArgument('email'))?$input->getArgument('email'):"admin@shopper360.com.my";
        $defaultPassword = ($input->getArgument('password'))?$input->getArgument('password'):"shopper1100";
        $defaultDisplayName = ($input->getArgument('username'))?$input->getArgument('username'):"SuperAdmin";
        $defaultFullName = "Super Administrator";

        $backendAdmin = new User();
        $backendAdmin->setFullName($defaultFullName);
        $backendAdmin->setUsername($defaultEmail);
        $backendAdmin->setEmail($defaultEmail);
        $backendAdmin->setMobileNo("0207654321");
        $roles = array("ROLE_ADMIN");
        $backendAdmin->setRoles($roles);
        $backendAdmin->setActive(1);
        $backendAdmin->setGuest(0);
        $backendAdmin->setDeleted(0);
        $backendAdmin->setVisible(0);
        $backendAdmin->setCreatedDate(new \DateTime());
        
        $backendAdmin->setPassword( 
            $this->passwordEncoder->hashPassword(
                $backendAdmin,
                $defaultPassword
            )
        );
        
        $this->manager->persist($backendAdmin);     
        $this->manager->flush(); 

        $output->writeln([
            '',
            'Admin Account generated'
        ]
        );
        return 0;
    }
}
