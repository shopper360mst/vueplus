<?php

namespace App\Command;
use PDO;

use App\Entity\Menu;
use App\Entity\CampaignConfig;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;


#[AsCommand(name: 'app:create-default')]
class CreateDefault extends Command
{
    //protected static $defaultName = 'app:create-default';
    public function __construct(private EntityManagerInterface $manager, private UserPasswordHasherInterface $passwordEncoder, private ParameterBagInterface $paramBag)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $BURL = $this->paramBag->get('app.base_url');
        $CODE = $this->paramBag->get('app.campaign_code');
        $PROXY_LINK = $this->paramBag->get('app.proxy_url');
        
        $io->warning('This command will TRUNCATE your tables');
        $confirmed = $io->ask('Type CONFIRM to execute');
        if($confirmed != 'CONFIRM'){
            $output->writeln([
                '',
                'BYE'
            ]
            );
            return 0;
        }
        
        $_DEFAULTMENU = [
            
            array(
                'label' => "Receipt Submission",
                "url" => "",
                "menu_code" => "promotions",
                "submenu_code" => "",
                "weight" => 4,
                "menu_index" => 0,
                "is_published" => 1,
                "menu_id" => null,
                "locale" => "en"
            ),
            array(
                'label' => "[West Malaysia] Supermarket, Hypermarket & E-Commerce",
                "url" => $PROXY_LINK."en/#SHM_WM",
                "menu_code" => "promotions",
                "submenu_code" => "submit_receipt",
                "weight" => 411,
                "menu_index" => 1,
                "is_published" => 1,
                "menu_id" => null,
                "locale" => "en"
            ),
            array(
                'label' => "[East Malaysia] Supermarket, Hypermarket & E-Commerce",
                "url" => $PROXY_LINK."en/#SHM_EM",
                "menu_code" => "promotions",
                "submenu_code" => "submit_receipt",
                "weight" => 412,
                "menu_index" => 1,
                "is_published" => 1,
                "menu_id" => null,
                "locale" => "en"
            ),
            //Turn on for PMT-long / FULL VERSION
            // array(
            //     'label' => "99 Speedmart",
            //     "url" => $PROXY_LINK."en/#99SM",
            //     "menu_code" => "promotions",
            //     "submenu_code" => "submit_receipt",
            //     "weight" => 413,
            //     "menu_index" => 1,
            //     "is_published" => 1,
            //     "menu_id" => null,
            //     "locale" => "en"
            // ),
            // array(
            //     'label' => "Convenience Store & Mini Market",
            //     "url" => $PROXY_LINK."en/#CVSTOFT",
            //     "menu_code" => "promotions",
            //     "submenu_code" => "submit_receipt",
            //     "weight" => 414,
            //     "menu_index" => 1,
            //     "is_published" => 1,
            //     "menu_id" => null,
            //     "locale" => "en"
            // ),
            // array(
            //     'label' => "Pub, Bar & Café",
            //     "url" => $PROXY_LINK."en/#MONT",
            //     "menu_code" => "promotions",
            //     "submenu_code" => "submit_receipt",
            //     "weight" => 415,
            //     "menu_index" => 1,
            //     "is_published" => 1,
            //     "menu_id" => null,
            //     "locale" => "en"
            // ),
            // array(
            //     'label' => "Coffee Shop & Food Court",
            //     "url" => $PROXY_LINK."en/#TONT",
            //     "menu_code" => "promotions",
            //     "submenu_code" => "submit_receipt",
            //     "weight" => 416,
            //     "menu_index" => 1,
            //     "is_published" => 1,
            //     "menu_id" => null,
            //     "locale" => "en"
            // ),
            //Turn on for PMT-long / FULL VERSION
            // array(
            //     'label' => "Promotion",
            //     "url" => $PROXY_LINK."en/#promotion",
            //     "menu_code" => "promotions",
            //     "submenu_code" => "submit_receipt",
            //     "weight" => 417,
            //     "menu_index" => 1,
            //     "is_published" => 1,
            //     "menu_id" => null,
            //     "locale" => "en"
            // ),
            // array(
            //     'label' => "Redemption Status",
            //     "url" => $PROXY_LINK."en/chk_status",
            //     "menu_code" => "promotions",
            //     "submenu_code" => "",
            //     "weight" => 42,
            //     "menu_index" => 1,
            //     "is_published" => 1,
            //     "menu_id" => null,
            //     "locale" => "en"
            // ),
            array(
                'label' => "Redemption Status",
                "url" => $PROXY_LINK."en/chk_status",
                "menu_code" => "",
                "submenu_code" => "",
                "weight" => 5,
                "menu_index" => 0,
                "is_published" => 1,
                "menu_id" => null,
                "locale" => "en"
            ),

            array(
                'label' => "Events",
                "url" => "",
                "menu_code" => "events",
                "submenu_code" => "",
                "weight" => 6,
                "menu_index" => 0,
                "is_published" => 1,
                "menu_id" => null,
                "locale" => "en"
            ),
            array(
                'label' => "Shaking Prosperity Together",
                "url" => $PROXY_LINK."en/event",
                "menu_code" => "events",
                "submenu_code" => "",
                "weight" => 601,
                "menu_index" => 1,
                "is_published" => 1,
                "menu_id" => null,
                "locale" => "en"
            ),
            array(
                'label' => "CarlsCrib",
                "url" => $PROXY_LINK."en/carlscrib",
                "menu_code" => "events",
                "submenu_code" => "",
                "weight" => 602,
                "menu_index" => 1,
                "is_published" => 1,
                "menu_id" => null,
                "locale" => "en"
            ),
            array(
                'label' => "Campaigns",
                "url" => "",
                "menu_code" => "campaign",
                "submenu_code" => "",
                "weight" => 7,
                "menu_index" => 0,
                "is_published" => 1,
                "menu_id" => null,
                "locale" => "en"
            ),
            array(
                'label' => "World of Smooth",
                "url" => "https://bestwithcarlsberg.my/wos/",
                "menu_code" => "campaign",
                "submenu_code" => "",
                "weight" => 701,
                "menu_index" => 1,
                "is_published" => 1,
                "menu_id" => null,
                "locale" => "en"
            ),
            array(
                'label' => "Football 2025",
                "url" => "https://bestwithcarlsberg.my/football/",
                "menu_code" => "campaign",
                "submenu_code" => "",
                "weight" => 02,
                "menu_index" => 1,
                "is_published" => 1,
                "menu_id" => null,
                "locale" => "en"
            ),
            
            array(
                'label' => "提交收据",
                "url" => "",
                "menu_code" => "promotions",
                "submenu_code" => "",
                "weight" => 4,
                "menu_index" => 0,
                "is_published" => 1,
                "menu_id" => null,
                "locale" => "ch"
            ),
            array(
                'label' => "[西马]超级市场, 霸级市场及电商",
                "url" => $PROXY_LINK."ch/#SHM_WM",
                "menu_code" => "promotions",
                "submenu_code" => "submit_receipt",
                "weight" => 410,
                "menu_index" => 1,
                "is_published" => 1,
                "menu_id" => null,
                "locale" => "ch"
            ),
            array(
                'label' => "[东马]超级市场, 霸级市场及电商",
                "url" => $PROXY_LINK."ch/#SHM_EM",
                "menu_code" => "promotions",
                "submenu_code" => "submit_receipt",
                "weight" => 411,
                "menu_index" => 1,
                "is_published" => 1,
                "menu_id" => null,
                "locale" => "ch"
            ),
            //Turn on for PMT-long / FULL VERSION
            // array(
            //     'label' => "99 Speedmart",
            //     "url" => $PROXY_LINK."ch/#99SM",
            //     "menu_code" => "promotions",
            //     "submenu_code" => "submit_receipt",
            //     "weight" => 412,
            //     "menu_index" => 1,
            //     "is_published" => 1,
            //     "menu_id" => null,
            //     "locale" => "ch"
            // ),
            // array(
            //     'label' => "便利店和小超市",
            //     "url" => $PROXY_LINK."ch/#CVSTOFT",
            //     "menu_code" => "promotions",
            //     "submenu_code" => "submit_receipt",
            //     "weight" => 413,
            //     "menu_index" => 1,
            //     "is_published" => 1,
            //     "menu_id" => null,
            //     "locale" => "ch"
            // ),
            // array(
            //     'label' => "酒吧、咖啡厅和餐厅",
            //     "url" => $PROXY_LINK."ch/#MONT",
            //     "menu_code" => "promotions",
            //     "submenu_code" => "submit_receipt",
            //     "weight" => 414,
            //     "menu_index" => 1,
            //     "is_published" => 1,
            //     "menu_id" => null,
            //     "locale" => "ch"
            // ),
            // array(
            //     'label' => "咖啡店和美食广场",
            //     "url" => $PROXY_LINK."ch/#TONT",
            //     "menu_code" => "promotions",
            //     "submenu_code" => "submit_receipt",
            //     "weight" => 415,
            //     "menu_index" => 1,
            //     "is_published" => 1,
            //     "menu_id" => null,
            //     "locale" => "ch"
            // ),
            
            // array(
            //     'label' => "促销活动",
            //     "url" => $PROXY_LINK."ch/#promotion",
            //     "menu_code" => "promotions",
            //     "submenu_code" => "submit_receipt",
            //     "weight" => 417,
            //     "menu_index" => 1,
            //     "is_published" => 1,
            //     "menu_id" => null,
            //     "locale" => "ch"
            // ),
            //Turn on for PMT-long / FULL VERSION
            array(
                'label' => "兑换状态",
                "url" => $PROXY_LINK."ch/chk_status",
                "menu_code" => "",
                "submenu_code" => "",
                "weight" => 5,
                "menu_index" => 0,
                "is_published" => 1,
                "menu_id" => null,
                "locale" => "ch"
            ),
            array(
                'label' => "活动",
                "url" => "",
                "menu_code" => "events",
                "submenu_code" => "",
                "weight" => 6,
                "menu_index" => 0,
                "is_published" => 1,
                "menu_id" => null,
                "locale" => "ch"
            ),
            array(
                'label' => "摇出嘉运",
                "url" => $PROXY_LINK."ch/event",
                "menu_code" => "events",
                "submenu_code" => "",
                "weight" => 601,
                "menu_index" => 1,
                "is_published" => 1,
                "menu_id" => null,
                "locale" => "ch"
            ),
            array(
                'label' => "CarlsCrib",
                "url" => $PROXY_LINK."ch/carlscrib",
                "menu_code" => "events",
                "submenu_code" => "",
                "weight" => 602,
                "menu_index" => 1,
                "is_published" => 1,
                "menu_id" => null,
                "locale" => "ch"
            ),

            array(
                'label' => "其他活动​",
                "url" => "",
                "menu_code" => "campaign",
                "submenu_code" => "",
                "weight" => 7,
                "menu_index" => 0,
                "is_published" => 1,
                "menu_id" => null,
                "locale" => "ch"
            ),          
            array(
                'label' => "World of Smooth",
                "url" => "https://bestwithcarlsberg.my/wos/",
                "menu_code" => "campaign",
                "submenu_code" => "",
                "weight" => 701,
                "menu_index" => 1,
                "is_published" => 1,
                "menu_id" => null,
                "locale" => "ch"
            ),
            array(
                'label' => "Football 2025",
                "url" => "https://bestwithcarlsberg.my/football/",
                "menu_code" => "campaign",
                "submenu_code" => "",
                "weight" => 702,
                "menu_index" => 1,
                "is_published" => 1,
                "menu_id" => null,
                "locale" => "ch"
            ),
            
        ];
        
        $this->truncateTable("menu");
        for ($i=0; $i < count($_DEFAULTMENU); $i++) { 
            $feMENU = new Menu();
        
            $feMENU->setLabel($_DEFAULTMENU[$i]['label']);
            $feMENU->setMenuCode($_DEFAULTMENU[$i]['menu_code']);
            $feMENU->setSubmenuCode($_DEFAULTMENU[$i]['submenu_code']);

            $feMENU->setUrl($_DEFAULTMENU[$i]['url']);
            $feMENU->setWeight($_DEFAULTMENU[$i]['weight']);
            $feMENU->setMenuIndex($_DEFAULTMENU[$i]['menu_index']);
            $feMENU->setLocale($_DEFAULTMENU[$i]['locale']);
            
            $feMENU->setPublished($_DEFAULTMENU[$i]['is_published']);
            $this->manager->persist($feMENU);     
            $this->manager->flush(); 
        }

        // Create 9 weeks of CampaignConfig starting from August 4, 2025
        $this->truncateTable("campaign_config");
        $startDate = new \DateTime('2025-11-22');
        
        for ($week = 1; $week <= 9; $week++) {
            $campaignConfig = new CampaignConfig();
            $campaignConfig->setWeekNumber($week);
            
            // Calculate start date for this week (each week starts on Monday)
            $weekStartDate = clone $startDate;
            $weekStartDate->modify('+' . ($week - 1) . ' weeks');
            
            // Calculate end date (Sunday of the same week)
            $weekEndDate = clone $weekStartDate;
            $weekEndDate->modify('+6 days');
            $weekEndDate->setTime(23, 59, 59);
            
            $campaignConfig->setStartDate($weekStartDate);
            $campaignConfig->setEndDate($weekEndDate);
            
            // Set all SKU limits to 0 as requested
            $campaignConfig->setSku1Limit(0);
            $campaignConfig->setSku2Limit(0);
            $campaignConfig->setSku3Limit(0);
            $campaignConfig->setSku4Limit(0);
            $campaignConfig->setSku5Limit(0);
            
            $this->manager->persist($campaignConfig);
            $this->manager->flush();
        }

        $output->writeln([
            '',
            'Menu and Campaign Config generated'
        ]
        );
        return 0;
    }

    private function truncateTable (string $tableName): bool {
        $connection = $this->manager->getConnection();
        try {
            $sql = "TRUNCATE TABLE ".$tableName;       
            $stmt = $connection->prepare($sql);
            $result = $stmt->executeQuery();
        } catch (\Exception $e) {
            try {
                fwrite(STDERR, print_r('Can\'t truncate table ' . $tableName . '. Reason: ' . $e->getMessage(), TRUE));
                $connection->rollback();
                return false;
            } catch (ConnectionException $connectionException) {
                fwrite(STDERR, print_r('Can\'t rollback truncating table ' . $tableName . '. Reason: ' . $connectionException->getMessage(), TRUE));
                return false;
            }
        }
        return true;
    }
}
