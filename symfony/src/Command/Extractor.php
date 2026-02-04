<?php

namespace App\Command;

use App\Entity\Submission;
use App\Entity\User;
use App\Entity\ContentPage;
use App\Entity\StoreItem;
use App\Entity\RewardMessage;
use App\Entity\ReportEntry;
use App\Entity\ReportByState;
use App\Entity\ReportBySku;
use App\Entity\Product;
use App\Entity\Quest;
use App\Entity\CampaignConfig;

use Shuchkin\SimpleXLSXGen;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Service\ExcelUtilService;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Service\CipherService;
use App\Service\ActivityService;
use Psr\Log\LoggerInterface;
use App\Service\RewardMessageService;

#[AsCommand(name: 'app:extractor', description: 'Extracts and processes submission data for reporting purposes. Manual week_num takes priority over active CampaignConfig.')]
class Extractor extends Command
{
    private $manager;    
    public function __construct(
        ManagerRegistry $registry, 
        EntityManagerInterface $entityManager, 
        ParameterBagInterface $paramBag,
        ActivityService $avc,
        LoggerInterface $logger,
        CipherService $cs
    )
    {
        $this->doctrine = $registry;
        $this->manager = $entityManager;
        $this->paramBag = $paramBag;
        $this->cs = $cs;
        $this->logger = $logger;
        $this->activity = $avc;
        parent::__construct();
    }

    protected function configure(): void{
        $this->addArgument('week_num', InputArgument::OPTIONAL, 'Week number to extract (takes priority; will find matching CampaignConfig if exists)');
        $this->addOption('export-excel', null, InputOption::VALUE_NONE, 'Export data to Excel file');
    }

    /**
     * Cleanup any needed table abroad TRUNCATE SQL function
     *
     * @param string $className (example: App\Entity\User)
     * @param EntityManagerInterface $em
     * @return bool
     */
    private function truncateTable (string $className, EntityManagerInterface $em): bool {
        $cmd = $em->getClassMetadata($className);
        $connection = $em->getConnection();
        $connection->beginTransaction();

        try {
            $connection->query('SET FOREIGN_KEY_CHECKS=0');
            $connection->query('TRUNCATE TABLE '.$cmd->getTableName());
            $connection->query('SET FOREIGN_KEY_CHECKS=1');
            $connection->commit();
            $em->flush();
        } catch (\Exception $e) {
            try {
                fwrite(STDERR, print_r('Can\'t truncate table ' . $cmd->getTableName() . '. Reason: ' . $e->getMessage(), TRUE));
                $connection->rollback();
                return false;
            } catch (ConnectionException $connectionException) {
                fwrite(STDERR, print_r('Can\'t rollback truncating table ' . $cmd->getTableName() . '. Reason: ' . $connectionException->getMessage(), TRUE));
                return false;
            }
        }
        return true;
    }

    /**
     * Find the active CampaignConfig where current date falls between start_date and end_date
     *
     * @return CampaignConfig|null
     */
    private function findActiveCampaignConfig(): ?CampaignConfig
    {
        $now = new \DateTime();
        
        return $this->manager->getRepository(CampaignConfig::class)
            ->createQueryBuilder('c')
            ->where('c.start_date <= :now')
            ->andWhere('c.end_date >= :now')
            ->setParameter('now', $now)
            ->orderBy('c.start_date', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find CampaignConfig by week number
     *
     * @param int $weekNumber
     * @return CampaignConfig|null
     */
    private function findCampaignConfigByWeek(int $weekNumber): ?CampaignConfig
    {
        return $this->manager->getRepository(CampaignConfig::class)
            ->createQueryBuilder('c')
            ->where('c.week_number = :weekNumber')
            ->setParameter('weekNumber', $weekNumber)
            ->orderBy('c.start_date', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {  
          
        $io = new SymfonyStyle($input, $output);
        $this->manager->getConnection()->beginTransaction();
        $weekInput = $input->getArgument('week_num');
        try {
            $campaignConfig = null;
            
            // Priority 1: If week_num is provided, find CampaignConfig for that specific week
            if ($weekInput) {
                $campaignConfig = $this->findCampaignConfigByWeek($weekInput);
                $reqWeek = $weekInput;
                
                if ($campaignConfig) {
                    $output->writeln('<fg=bright-cyan>Using manually specified week number: ' . $reqWeek);
                    $output->writeln('<fg=bright-cyan>Found CampaignConfig for week ' . $reqWeek);
                    
                    $startDate = $campaignConfig->getStartDate();
                    $endDate = $campaignConfig->getEndDate();
                    if ($startDate && $endDate) {
                        $output->writeln('<fg=bright-cyan>Campaign period: ' . $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d'));
                    }
                } else {
                    $output->writeln('<fg=bright-yellow>No CampaignConfig found for week ' . $reqWeek . ', using week number only');
                }
            } 
            // Priority 2: If no week_num provided, try to find active CampaignConfig
            else {
                $campaignConfig = $this->findActiveCampaignConfig();
                
                if ($campaignConfig && $campaignConfig->getWeekNumber() !== null) {
                    // Use week number from active CampaignConfig
                    $reqWeek = $campaignConfig->getWeekNumber();
                    $output->writeln('<fg=bright-cyan>Using week number from active CampaignConfig: ' . $reqWeek);
                    
                    $startDate = $campaignConfig->getStartDate();
                    $endDate = $campaignConfig->getEndDate();
                    if ($startDate && $endDate) {
                        $output->writeln('<fg=bright-cyan>Campaign period: ' . $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d'));
                    }
                } else {
                    // Priority 3: Fall back to date calculation
                    $liveDate = $this->paramBag->get('app.live_date');
                    $castDate = new \DateTime($liveDate);
                    $weekToLive = $castDate->format("W");
                     
                    $todayDate = new \DateTime();
                    $weekToDate = $todayDate->format("W");
                    $reqWeek = $weekToDate - $weekToLive + 1;
                    
                    $output->writeln('<fg=bright-cyan>No active CampaignConfig found, using calculated week: ' . $reqWeek);
                }
            }
          
            $output->writeln('');
            $output->writeln('<fg=bright-green>#################################################################################');
            $output->writeln('<fg=bright-yellow> Extracting Submission Data Week : <fg=bright-red>'.$reqWeek);
            $output->writeln('<fg=bright-green>#################################################################################');
            $result = true;
            if ($result) {
                // this routine will always extract and update report_entry2024, based on current week no.
                $output->writeln('');            
                $output->writeln('<fg=bright-red>Prepping for Report Entry 2024');
                $output->writeln('');
                $MONT_RESULT = $this->manager->getRepository(ReportEntry::class)->getMainChannelEntries("MONT", $reqWeek);
                $MONT_RESULT_TOTAL = $MONT_RESULT;

                $CVS_RESULT = $this->manager->getRepository(ReportEntry::class)->getMainChannelEntries("CVS", $reqWeek);
                $CVS_RESULT_TOTAL = $CVS_RESULT;

                $TOFT_RESULT = $this->manager->getRepository(ReportEntry::class)->getMainChannelEntries("TOFT", $reqWeek);
                $TOFT_RESULT_TOTAL = $TOFT_RESULT;

                $S99_RESULT = $this->manager->getRepository(ReportEntry::class)->getMainChannelEntries("S99", $reqWeek);
                $S99_RESULT_TOTAL = $S99_RESULT;

                $SHM_RESULT = $this->manager->getRepository(ReportEntry::class)->getMainChannelEntries("SHM" , $reqWeek);
                $SHM_RESULT_TOTAL = $SHM_RESULT;

                $TONT_RESULT = $this->manager->getRepository(ReportEntry::class)->getMainChannelEntries("TONT", $reqWeek);
                $TONT_RESULT_TOTAL = $TONT_RESULT;

                $ECOMM_RESULT = $this->manager->getRepository(ReportEntry::class)->getMainChannelEntries("ECOMM", $reqWeek);
                $ECOMM_RESULT_TOTAL = $ECOMM_RESULT;

                $MONT_VALID_RES = $this->manager->getRepository(ReportEntry::class)->getMainChannelEntries("MONT", $reqWeek ,"APPROVED");
                $MONT_VALID = $MONT_VALID_RES;

                $CVS_VALID_RES = $this->manager->getRepository(ReportEntry::class)->getMainChannelEntries("CVS", $reqWeek, "APPROVED");
                $CVS_VALID = $CVS_VALID_RES;

                $TOFT_VALID_RES = $this->manager->getRepository(ReportEntry::class)->getMainChannelEntries("TOFT", $reqWeek, "APPROVED");
                $TOFT_VALID = $TOFT_VALID_RES;

                $S99_VALID_RES = $this->manager->getRepository(ReportEntry::class)->getMainChannelEntries("S99", $reqWeek, "APPROVED");
                $S99_VALID = $S99_VALID_RES;
            
                $SHM_VALID_RES = $this->manager->getRepository(ReportEntry::class)->getMainChannelEntries("SHM", $reqWeek ,"APPROVED");
                $SHM_VALID = $SHM_VALID_RES;

                $TONT_VALID_RES = $this->manager->getRepository(ReportEntry::class)->getMainChannelEntries("TONT", $reqWeek ,"APPROVED");
                $TONT_VALID = $TONT_VALID_RES;

                $ECOMM_VALID_RES = $this->manager->getRepository(ReportEntry::class)->getMainChannelEntries("ECOMM", $reqWeek ,"APPROVED");
                $ECOMM_VALID = $ECOMM_VALID_RES;

                $MONT_INVALID_RES = $this->manager->getRepository(ReportEntry::class)->getMainChannelEntries("MONT", $reqWeek ,"REJECTED");
                $MONT_INVALID = $MONT_INVALID_RES;

                $CVS_INVALID_RES = $this->manager->getRepository(ReportEntry::class)->getMainChannelEntries("CVS", $reqWeek ,"REJECTED");
                $CVS_INVALID = $CVS_INVALID_RES;

                $TOFT_INVALID_RES = $this->manager->getRepository(ReportEntry::class)->getMainChannelEntries("TOFT", $reqWeek ,"REJECTED");
                $TOFT_INVALID = $TOFT_INVALID_RES;

                $S99_INVALID_RES = $this->manager->getRepository(ReportEntry::class)->getMainChannelEntries("S99", $reqWeek ,"REJECTED");
                $S99_INVALID = $S99_INVALID_RES;
            
                $SHM_INVALID_RES = $this->manager->getRepository(ReportEntry::class)->getMainChannelEntries("SHM", $reqWeek ,"REJECTED");
                $SHM_INVALID = $SHM_INVALID_RES;

                $TONT_INVALID_RES = $this->manager->getRepository(ReportEntry::class)->getMainChannelEntries("TONT", $reqWeek ,"REJECTED");
                $TONT_INVALID = $TONT_INVALID_RES;

                $ECOMM_INVALID_RES = $this->manager->getRepository(ReportEntry::class)->getMainChannelEntries("ECOMM", $reqWeek ,"REJECTED");
                $ECOMM_INVALID = $ECOMM_INVALID_RES;


                $GENDER_MALE = $this->manager->getRepository(ReportEntry::class)->getGenderEntries("M", $reqWeek);
                $GENDER_MALE_MONT = $this->manager->getRepository(ReportEntry::class)->getGenderEntriesChannel("M", $reqWeek,'MONT');
                $GENDER_MALE_CVS = $this->manager->getRepository(ReportEntry::class)->getGenderEntriesChannel("M", $reqWeek,"CVS");
                $GENDER_MALE_TOFT = $this->manager->getRepository(ReportEntry::class)->getGenderEntriesChannel("M", $reqWeek,'TOFT');
                $GENDER_MALE_S99 = $this->manager->getRepository(ReportEntry::class)->getGenderEntriesChannel("M", $reqWeek,'S99');
                $GENDER_MALE_TONT = $this->manager->getRepository(ReportEntry::class)->getGenderEntriesChannel("M", $reqWeek,'TONT');
                $GENDER_MALE_SHM = $this->manager->getRepository(ReportEntry::class)->getGenderEntriesChannel("M", $reqWeek,'SHM');
                $GENDER_MALE_ECOMM = $this->manager->getRepository(ReportEntry::class)->getGenderEntriesChannel("M", $reqWeek,'ECOMM');
                $GENDER_FEMALE = $this->manager->getRepository(ReportEntry::class)->getGenderEntries("F", $reqWeek);
                $GENDER_FEMALE_MONT = $this->manager->getRepository(ReportEntry::class)->getGenderEntriesChannel("F", $reqWeek,'MONT');
                $GENDER_FEMALE_CVS = $this->manager->getRepository(ReportEntry::class)->getGenderEntriesChannel("F", $reqWeek,"CVS");
                $GENDER_FEMALE_TOFT = $this->manager->getRepository(ReportEntry::class)->getGenderEntriesChannel("F", $reqWeek,'TOFT');
                $GENDER_FEMALE_S99 = $this->manager->getRepository(ReportEntry::class)->getGenderEntriesChannel("F", $reqWeek,'S99');
                $GENDER_FEMALE_TONT = $this->manager->getRepository(ReportEntry::class)->getGenderEntriesChannel("F", $reqWeek,'TONT');
                $GENDER_FEMALE_SHM = $this->manager->getRepository(ReportEntry::class)->getGenderEntriesChannel("F", $reqWeek,'SHM');
                $GENDER_FEMALE_ECOMM = $this->manager->getRepository(ReportEntry::class)->getGenderEntriesChannel("F", $reqWeek,'ECOMM');

                $AGE_21_25_MONT = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('MONT', '21-25', $reqWeek);
                $AGE_26_30_MONT = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('MONT', '26-30', $reqWeek);
                $AGE_31_35_MONT = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('MONT', '31-35', $reqWeek);
                $AGE_36_40_MONT = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('MONT', '36-40', $reqWeek);
                $AGE_41_45_MONT = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('MONT', '41-45', $reqWeek);
                $AGE_46_50_MONT = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('MONT', '46-50', $reqWeek);
                $AGE_ABV_50_MONT = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('MONT', '>50', $reqWeek);

                $AGE_21_25_CVS = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries("CVS", '21-25', $reqWeek);
                $AGE_26_30_CVS = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries("CVS", '26-30', $reqWeek);
                $AGE_31_35_CVS = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries("CVS", '31-35', $reqWeek);
                $AGE_36_40_CVS = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries("CVS", '36-40', $reqWeek);
                $AGE_41_45_CVS = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries("CVS", '41-45', $reqWeek);
                $AGE_46_50_CVS = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries("CVS", '46-50', $reqWeek);
                $AGE_ABV_50_CVS = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries("CVS", '>50', $reqWeek);

                $AGE_21_25_TOFT = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('TOFT', '21-25', $reqWeek);
                $AGE_26_30_TOFT = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('TOFT', '26-30', $reqWeek);
                $AGE_31_35_TOFT = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('TOFT', '31-35', $reqWeek);
                $AGE_36_40_TOFT = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('TOFT', '36-40', $reqWeek);
                $AGE_41_45_TOFT = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('TOFT', '41-45', $reqWeek);
                $AGE_46_50_TOFT = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('TOFT', '46-50', $reqWeek);
                $AGE_ABV_50_TOFT = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('TOFT', '>50', $reqWeek);

                $AGE_21_25_SHM = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('SHM', '21-25', $reqWeek);
                $AGE_26_30_SHM = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('SHM', '26-30', $reqWeek);
                $AGE_31_35_SHM = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('SHM', '31-35', $reqWeek);
                $AGE_36_40_SHM = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('SHM', '36-40', $reqWeek);
                $AGE_41_45_SHM = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('SHM', '41-45', $reqWeek);
                $AGE_46_50_SHM = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('SHM', '46-50', $reqWeek);
                $AGE_ABV_50_SHM = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('SHM',  '>50', $reqWeek);

                $AGE_21_25_S99 = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('S99', '21-25', $reqWeek);
                $AGE_26_30_S99 = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('S99', '26-30', $reqWeek);
                $AGE_31_35_S99 = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('S99', '31-35', $reqWeek);
                $AGE_36_40_S99 = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('S99', '36-40', $reqWeek);
                $AGE_41_45_S99 = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('S99', '41-45', $reqWeek);
                $AGE_46_50_S99 = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('S99', '46-50', $reqWeek);
                $AGE_ABV_50_S99 = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('S99',  '>50', $reqWeek);

                $AGE_21_25_TONT = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('TONT', '21-25', $reqWeek);
                $AGE_26_30_TONT = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('TONT', '26-30', $reqWeek);
                $AGE_31_35_TONT = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('TONT', '31-35', $reqWeek);
                $AGE_36_40_TONT = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('TONT', '36-40', $reqWeek);
                $AGE_41_45_TONT = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('TONT', '41-45', $reqWeek);
                $AGE_46_50_TONT = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('TONT', '46-50', $reqWeek);
                $AGE_ABV_50_TONT = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('TONT',  '>50', $reqWeek);
           
                $AGE_21_25_ECOMM = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('ECOMM', '21-25', $reqWeek);
                $AGE_26_30_ECOMM = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('ECOMM', '26-30', $reqWeek);
                $AGE_31_35_ECOMM = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('ECOMM', '31-35', $reqWeek);
                $AGE_36_40_ECOMM = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('ECOMM', '36-40', $reqWeek);
                $AGE_41_45_ECOMM = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('ECOMM', '41-45', $reqWeek);
                $AGE_46_50_ECOMM = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('ECOMM', '46-50', $reqWeek);
                $AGE_ABV_50_ECOMM = $this->manager->getRepository(ReportEntry::class)->getMainChannelByAgeGroupEntries('ECOMM',  '>50', $reqWeek);

                // Channel-Gender-Age combinations
                $output->writeln('');            
                $output->writeln('<fg=bright-red>Extracting Channel-Gender-Age combinations');
                $output->writeln('');

                // SHM Channel-Gender-Age combinations
                $SHM_MALE_AGE_21_25 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('SHM', 'M', '21-25', $reqWeek);
                $SHM_MALE_AGE_26_30 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('SHM', 'M', '26-30', $reqWeek);
                $SHM_MALE_AGE_31_35 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('SHM', 'M', '31-35', $reqWeek);
                $SHM_MALE_AGE_36_40 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('SHM', 'M', '36-40', $reqWeek);
                $SHM_MALE_AGE_41_45 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('SHM', 'M', '41-45', $reqWeek);
                $SHM_MALE_AGE_46_50 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('SHM', 'M', '46-50', $reqWeek);
                $SHM_MALE_AGE_50_ABOVE = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('SHM', 'M', '>50', $reqWeek);

                $SHM_FEMALE_AGE_21_25 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('SHM', 'F', '21-25', $reqWeek);
                $SHM_FEMALE_AGE_26_30 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('SHM', 'F', '26-30', $reqWeek);
                $SHM_FEMALE_AGE_31_35 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('SHM', 'F', '31-35', $reqWeek);
                $SHM_FEMALE_AGE_36_40 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('SHM', 'F', '36-40', $reqWeek);
                $SHM_FEMALE_AGE_41_45 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('SHM', 'F', '41-45', $reqWeek);
                $SHM_FEMALE_AGE_46_50 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('SHM', 'F', '46-50', $reqWeek);
                $SHM_FEMALE_AGE_50_ABOVE = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('SHM', 'F', '>50', $reqWeek);

                // S99 Channel-Gender-Age combinations
                $S99_MALE_AGE_21_25 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('S99', 'M', '21-25', $reqWeek);
                $S99_MALE_AGE_26_30 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('S99', 'M', '26-30', $reqWeek);
                $S99_MALE_AGE_31_35 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('S99', 'M', '31-35', $reqWeek);
                $S99_MALE_AGE_36_40 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('S99', 'M', '36-40', $reqWeek);
                $S99_MALE_AGE_41_45 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('S99', 'M', '41-45', $reqWeek);
                $S99_MALE_AGE_46_50 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('S99', 'M', '46-50', $reqWeek);
                $S99_MALE_AGE_50_ABOVE = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('S99', 'M', '>50', $reqWeek);

                $S99_FEMALE_AGE_21_25 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('S99', 'F', '21-25', $reqWeek);
                $S99_FEMALE_AGE_26_30 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('S99', 'F', '26-30', $reqWeek);
                $S99_FEMALE_AGE_31_35 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('S99', 'F', '31-35', $reqWeek);
                $S99_FEMALE_AGE_36_40 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('S99', 'F', '36-40', $reqWeek);
                $S99_FEMALE_AGE_41_45 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('S99', 'F', '41-45', $reqWeek);
                $S99_FEMALE_AGE_46_50 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('S99', 'F', '46-50', $reqWeek);
                $S99_FEMALE_AGE_50_ABOVE = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('S99', 'F', '>50', $reqWeek);

                // MONT Channel-Gender-Age combinations
                $MONT_MALE_AGE_21_25 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('MONT', 'M', '21-25', $reqWeek);
                $MONT_MALE_AGE_26_30 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('MONT', 'M', '26-30', $reqWeek);
                $MONT_MALE_AGE_31_35 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('MONT', 'M', '31-35', $reqWeek);
                $MONT_MALE_AGE_36_40 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('MONT', 'M', '36-40', $reqWeek);
                $MONT_MALE_AGE_41_45 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('MONT', 'M', '41-45', $reqWeek);
                $MONT_MALE_AGE_46_50 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('MONT', 'M', '46-50', $reqWeek);
                $MONT_MALE_AGE_50_ABOVE = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('MONT', 'M', '>50', $reqWeek);

                $MONT_FEMALE_AGE_21_25 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('MONT', 'F', '21-25', $reqWeek);
                $MONT_FEMALE_AGE_26_30 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('MONT', 'F', '26-30', $reqWeek);
                $MONT_FEMALE_AGE_31_35 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('MONT', 'F', '31-35', $reqWeek);
                $MONT_FEMALE_AGE_36_40 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('MONT', 'F', '36-40', $reqWeek);
                $MONT_FEMALE_AGE_41_45 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('MONT', 'F', '41-45', $reqWeek);
                $MONT_FEMALE_AGE_46_50 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('MONT', 'F', '46-50', $reqWeek);
                $MONT_FEMALE_AGE_50_ABOVE = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('MONT', 'F', '>50', $reqWeek);

                // TONT Channel-Gender-Age combinations
                $TONT_MALE_AGE_21_25 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('TONT', 'M', '21-25', $reqWeek);
                $TONT_MALE_AGE_26_30 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('TONT', 'M', '26-30', $reqWeek);
                $TONT_MALE_AGE_31_35 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('TONT', 'M', '31-35', $reqWeek);
                $TONT_MALE_AGE_36_40 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('TONT', 'M', '36-40', $reqWeek);
                $TONT_MALE_AGE_41_45 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('TONT', 'M', '41-45', $reqWeek);
                $TONT_MALE_AGE_46_50 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('TONT', 'M', '46-50', $reqWeek);
                $TONT_MALE_AGE_50_ABOVE = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('TONT', 'M', '>50', $reqWeek);

                $TONT_FEMALE_AGE_21_25 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('TONT', 'F', '21-25', $reqWeek);
                $TONT_FEMALE_AGE_26_30 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('TONT', 'F', '26-30', $reqWeek);
                $TONT_FEMALE_AGE_31_35 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('TONT', 'F', '31-35', $reqWeek);
                $TONT_FEMALE_AGE_36_40 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('TONT', 'F', '36-40', $reqWeek);
                $TONT_FEMALE_AGE_41_45 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('TONT', 'F', '41-45', $reqWeek);
                $TONT_FEMALE_AGE_46_50 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('TONT', 'F', '46-50', $reqWeek);
                $TONT_FEMALE_AGE_50_ABOVE = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('TONT', 'F', '>50', $reqWeek);

                // CVS Channel-Gender-Age combinations
                $CVS_MALE_AGE_21_25 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('CVS', 'M', '21-25', $reqWeek);
                $CVS_MALE_AGE_26_30 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('CVS', 'M', '26-30', $reqWeek);
                $CVS_MALE_AGE_31_35 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('CVS', 'M', '31-35', $reqWeek);
                $CVS_MALE_AGE_36_40 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('CVS', 'M', '36-40', $reqWeek);
                $CVS_MALE_AGE_41_45 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('CVS', 'M', '41-45', $reqWeek);
                $CVS_MALE_AGE_46_50 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('CVS', 'M', '46-50', $reqWeek);
                $CVS_MALE_AGE_50_ABOVE = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('CVS', 'M', '>50', $reqWeek);

                $CVS_FEMALE_AGE_21_25 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('CVS', 'F', '21-25', $reqWeek);
                $CVS_FEMALE_AGE_26_30 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('CVS', 'F', '26-30', $reqWeek);
                $CVS_FEMALE_AGE_31_35 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('CVS', 'F', '31-35', $reqWeek);
                $CVS_FEMALE_AGE_36_40 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('CVS', 'F', '36-40', $reqWeek);
                $CVS_FEMALE_AGE_41_45 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('CVS', 'F', '41-45', $reqWeek);
                $CVS_FEMALE_AGE_46_50 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('CVS', 'F', '46-50', $reqWeek);
                $CVS_FEMALE_AGE_50_ABOVE = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('CVS', 'F', '>50', $reqWeek);

                // TOFT Channel-Gender-Age combinations
                $TOFT_MALE_AGE_21_25 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('TOFT', 'M', '21-25', $reqWeek);
                $TOFT_MALE_AGE_26_30 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('TOFT', 'M', '26-30', $reqWeek);
                $TOFT_MALE_AGE_31_35 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('TOFT', 'M', '31-35', $reqWeek);
                $TOFT_MALE_AGE_36_40 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('TOFT', 'M', '36-40', $reqWeek);
                $TOFT_MALE_AGE_41_45 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('TOFT', 'M', '41-45', $reqWeek);
                $TOFT_MALE_AGE_46_50 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('TOFT', 'M', '46-50', $reqWeek);
                $TOFT_MALE_AGE_50_ABOVE = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('TOFT', 'M', '>50', $reqWeek);

                $TOFT_FEMALE_AGE_21_25 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('TOFT', 'F', '21-25', $reqWeek);
                $TOFT_FEMALE_AGE_26_30 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('TOFT', 'F', '26-30', $reqWeek);
                $TOFT_FEMALE_AGE_31_35 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('TOFT', 'F', '31-35', $reqWeek);
                $TOFT_FEMALE_AGE_36_40 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('TOFT', 'F', '36-40', $reqWeek);
                $TOFT_FEMALE_AGE_41_45 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('TOFT', 'F', '41-45', $reqWeek);
                $TOFT_FEMALE_AGE_46_50 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('TOFT', 'F', '46-50', $reqWeek);
                $TOFT_FEMALE_AGE_50_ABOVE = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('TOFT', 'F', '>50', $reqWeek);

                // ECOMM Channel-Gender-Age combinations
                $ECOMM_MALE_AGE_21_25 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('ECOMM', 'M', '21-25', $reqWeek);
                $ECOMM_MALE_AGE_26_30 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('ECOMM', 'M', '26-30', $reqWeek);
                $ECOMM_MALE_AGE_31_35 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('ECOMM', 'M', '31-35', $reqWeek);
                $ECOMM_MALE_AGE_36_40 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('ECOMM', 'M', '36-40', $reqWeek);
                $ECOMM_MALE_AGE_41_45 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('ECOMM', 'M', '41-45', $reqWeek);
                $ECOMM_MALE_AGE_46_50 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('ECOMM', 'M', '46-50', $reqWeek);
                $ECOMM_MALE_AGE_50_ABOVE = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('ECOMM', 'M', '>50', $reqWeek);

                $ECOMM_FEMALE_AGE_21_25 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('ECOMM', 'F', '21-25', $reqWeek);
                $ECOMM_FEMALE_AGE_26_30 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('ECOMM', 'F', '26-30', $reqWeek);
                $ECOMM_FEMALE_AGE_31_35 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('ECOMM', 'F', '31-35', $reqWeek);
                $ECOMM_FEMALE_AGE_36_40 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('ECOMM', 'F', '36-40', $reqWeek);
                $ECOMM_FEMALE_AGE_41_45 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('ECOMM', 'F', '41-45', $reqWeek);
                $ECOMM_FEMALE_AGE_46_50 = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('ECOMM', 'F', '46-50', $reqWeek);
                $ECOMM_FEMALE_AGE_50_ABOVE = $this->manager->getRepository(ReportEntry::class)->getChannelGenderAgeEntries('ECOMM', 'F', '>50', $reqWeek);

                /* INV */ 
                $INV_MONT_TOTAL = $this->manager->getRepository(ReportEntry::class)->getProducts(0, "MONT", $reqWeek);
                $INV_CVS_TOTAL = $this->manager->getRepository(ReportEntry::class)->getProducts(0, "CVS", $reqWeek);
                $INV_TOFT_TOTAL = $this->manager->getRepository(ReportEntry::class)->getProducts(0, "TOFT", $reqWeek);
                $INV_S99_TOTAL = $this->manager->getRepository(ReportEntry::class)->getProducts(0, "S99", $reqWeek);
                $INV_SHM_TOTAL = $this->manager->getRepository(ReportEntry::class)->getProducts(0, "SHM", $reqWeek);
                $INV_TONT_TOTAL = $this->manager->getRepository(ReportEntry::class)->getProducts(0, "TONT", $reqWeek);              
                $INV_ECOMM_TOTAL = $this->manager->getRepository(ReportEntry::class)->getProducts(0, "ECOMM", $reqWeek);              
                
                $INV_MONT_REDEEM = $this->manager->getRepository(ReportEntry::class)->getProducts(1, "MONT", $reqWeek);
                $INV_CVS_REDEEM = $this->manager->getRepository(ReportEntry::class)->getProducts(1, "CVS", $reqWeek);
                $INV_TOFT_REDEEM = $this->manager->getRepository(ReportEntry::class)->getProducts(1, "TOFT", $reqWeek);
                $INV_S99_REDEEM = $this->manager->getRepository(ReportEntry::class)->getProducts(1, "S99", $reqWeek);
                $INV_SHM_REDEEM = $this->manager->getRepository(ReportEntry::class)->getProducts(1, "SHM", $reqWeek);
                $INV_TONT_REDEEM = $this->manager->getRepository(ReportEntry::class)->getProducts(1, "TONT", $reqWeek);              
                $INV_ECOMM_REDEEM = $this->manager->getRepository(ReportEntry::class)->getProducts(1, "ECOMM", $reqWeek);              
                
                $DEL_MONT_PROCESS = $this->manager->getRepository(ReportEntry::class)->getDelivery("PROCESSING", "MONT", $reqWeek);
                $DEL_MONT_OUT = $this->manager->getRepository(ReportEntry::class)->getDelivery("OUT FOR DELIVERY", "MONT", $reqWeek);
                $DEL_MONT_ADDRESS = $this->manager->getRepository(ReportEntry::class)->getDelivery("ADDRESS", "MONT", $reqWeek);

                $DEL_CVS_PROCESS = $this->manager->getRepository(ReportEntry::class)->getDelivery("PROCESSING", "CVS", $reqWeek);
                $DEL_CVS_OUT = $this->manager->getRepository(ReportEntry::class)->getDelivery("OUT FOR DELIVERY", "CVS", $reqWeek);
                $DEL_CVS_ADDRESS = $this->manager->getRepository(ReportEntry::class)->getDelivery("ADDRESS", "CVS", $reqWeek);

                $DEL_TOFT_PROCESS = $this->manager->getRepository(ReportEntry::class)->getDelivery("PROCESSING", "TOFT", $reqWeek);
                $DEL_TOFT_OUT = $this->manager->getRepository(ReportEntry::class)->getDelivery("OUT FOR DELIVERY", "TOFT", $reqWeek);
                $DEL_TOFT_ADDRESS = $this->manager->getRepository(ReportEntry::class)->getDelivery("ADDRESS", "TOFT", $reqWeek);

                $DEL_S99_PROCESS = $this->manager->getRepository(ReportEntry::class)->getDelivery("PROCESSING", "S99", $reqWeek);
                $DEL_S99_OUT = $this->manager->getRepository(ReportEntry::class)->getDelivery("OUT FOR DELIVERY", "S99", $reqWeek);
                $DEL_S99_ADDRESS = $this->manager->getRepository(ReportEntry::class)->getDelivery("ADDRESS", "S99", $reqWeek);

                $DEL_SHM_PROCESS = $this->manager->getRepository(ReportEntry::class)->getDelivery("PROCESSING", "SHM", $reqWeek);
                $DEL_SHM_OUT = $this->manager->getRepository(ReportEntry::class)->getDelivery("OUT FOR DELIVERY", "SHM", $reqWeek);
                $DEL_SHM_ADDRESS = $this->manager->getRepository(ReportEntry::class)->getDelivery("ADDRESS", "SHM", $reqWeek);

                $DEL_TONT_PROCESS = $this->manager->getRepository(ReportEntry::class)->getDelivery("PROCESSING", "TONT", $reqWeek);
                $DEL_TONT_OUT = $this->manager->getRepository(ReportEntry::class)->getDelivery("OUT FOR DELIVERY", "TONT", $reqWeek);
                $DEL_TONT_ADDRESS = $this->manager->getRepository(ReportEntry::class)->getDelivery("ADDRESS", "TONT", $reqWeek);

                $DEL_ECOMM_PROCESS = $this->manager->getRepository(ReportEntry::class)->getDelivery("PROCESSING", "ECOMM", $reqWeek);
                $DEL_ECOMM_OUT = $this->manager->getRepository(ReportEntry::class)->getDelivery("OUT FOR DELIVERY", "ECOMM", $reqWeek);
                $DEL_ECOMM_ADDRESS = $this->manager->getRepository(ReportEntry::class)->getDelivery("ADDRESS", "ECOMM", $reqWeek);

                $REJECT_REASON1_MONT = $this->manager->getRepository(ReportEntry::class)->getReasonReject('DUPLICATE RECEIPT', "MONT", $reqWeek);
                $REJECT_REASON2_MONT = $this->manager->getRepository(ReportEntry::class)->getReasonReject('BELOW QUALIFYING QUANTITY', "MONT", $reqWeek);
                $REJECT_REASON3_MONT = $this->manager->getRepository(ReportEntry::class)->getReasonReject('NON PARTICIPATING PRODUCT', "MONT", $reqWeek);
                $REJECT_REASON4_MONT = $this->manager->getRepository(ReportEntry::class)->getReasonReject('OUTSIDE CONTEST PERIOD', "MONT", $reqWeek);
                $REJECT_REASON5_MONT = $this->manager->getRepository(ReportEntry::class)->getReasonReject('UNCLEAR IMAGE/NOT A RECEIPT', "MONT", $reqWeek);
                $REJECT_REASON6_MONT = $this->manager->getRepository(ReportEntry::class)->getReasonReject('BELOW QUALIFYING AMOUNT', "MONT", $reqWeek);
                $REJECT_REASON7_MONT = $this->manager->getRepository(ReportEntry::class)->getReasonReject('NON PARTICIPATING OUTLET', "MONT", $reqWeek);
                $REJECT_REASON8_MONT = $this->manager->getRepository(ReportEntry::class)->getReasonReject('OUTSIDE COVERAGE', "MONT", $reqWeek);

                $REJECT_REASON1_CVS = $this->manager->getRepository(ReportEntry::class)->getReasonReject('DUPLICATE RECEIPT', "CVS", $reqWeek);
                $REJECT_REASON2_CVS = $this->manager->getRepository(ReportEntry::class)->getReasonReject('BELOW QUALIFYING QUANTITY', "CVS", $reqWeek);
                $REJECT_REASON3_CVS = $this->manager->getRepository(ReportEntry::class)->getReasonReject('NON PARTICIPATING PRODUCT', "CVS", $reqWeek);
                $REJECT_REASON4_CVS = $this->manager->getRepository(ReportEntry::class)->getReasonReject('OUTSIDE CONTEST PERIOD', "CVS", $reqWeek);
                $REJECT_REASON5_CVS = $this->manager->getRepository(ReportEntry::class)->getReasonReject('UNCLEAR IMAGE/NOT A RECEIPT', "CVS", $reqWeek);
                $REJECT_REASON6_CVS = $this->manager->getRepository(ReportEntry::class)->getReasonReject('BELOW QUALIFYING AMOUNT', "CVS", $reqWeek);
                $REJECT_REASON7_CVS = $this->manager->getRepository(ReportEntry::class)->getReasonReject('NON PARTICIPATING OUTLET', "CVS", $reqWeek);
                $REJECT_REASON8_CVS = $this->manager->getRepository(ReportEntry::class)->getReasonReject('OUTSIDE COVERAGE', "CVS", $reqWeek);

                $REJECT_REASON1_TOFT = $this->manager->getRepository(ReportEntry::class)->getReasonReject('DUPLICATE RECEIPT', "TOFT", $reqWeek);
                $REJECT_REASON2_TOFT = $this->manager->getRepository(ReportEntry::class)->getReasonReject('BELOW QUALIFYING QUANTITY', "TOFT", $reqWeek);
                $REJECT_REASON3_TOFT = $this->manager->getRepository(ReportEntry::class)->getReasonReject('NON PARTICIPATING PRODUCT', "TOFT", $reqWeek);
                $REJECT_REASON4_TOFT = $this->manager->getRepository(ReportEntry::class)->getReasonReject('OUTSIDE CONTEST PERIOD', "TOFT", $reqWeek);
                $REJECT_REASON5_TOFT = $this->manager->getRepository(ReportEntry::class)->getReasonReject('UNCLEAR IMAGE/NOT A RECEIPT', "TOFT", $reqWeek);
                $REJECT_REASON6_TOFT = $this->manager->getRepository(ReportEntry::class)->getReasonReject('BELOW QUALIFYING AMOUNT', "TOFT", $reqWeek);
                $REJECT_REASON7_TOFT = $this->manager->getRepository(ReportEntry::class)->getReasonReject('NON PARTICIPATING OUTLET', "TOFT", $reqWeek);
                $REJECT_REASON8_TOFT = $this->manager->getRepository(ReportEntry::class)->getReasonReject('OUTSIDE COVERAGE', "TOFT", $reqWeek);

                $REJECT_REASON1_S99 = $this->manager->getRepository(ReportEntry::class)->getReasonReject('DUPLICATE RECEIPT', "S99", $reqWeek);
                $REJECT_REASON2_S99 = $this->manager->getRepository(ReportEntry::class)->getReasonReject('BELOW QUALIFYING QUANTITY', "S99", $reqWeek);
                $REJECT_REASON3_S99 = $this->manager->getRepository(ReportEntry::class)->getReasonReject('NON PARTICIPATING PRODUCT', "S99", $reqWeek);
                $REJECT_REASON4_S99 = $this->manager->getRepository(ReportEntry::class)->getReasonReject('OUTSIDE CONTEST PERIOD', "S99", $reqWeek);
                $REJECT_REASON5_S99 = $this->manager->getRepository(ReportEntry::class)->getReasonReject('UNCLEAR IMAGE/NOT A RECEIPT', "S99", $reqWeek);
                $REJECT_REASON6_S99 = $this->manager->getRepository(ReportEntry::class)->getReasonReject('BELOW QUALIFYING AMOUNT', "S99", $reqWeek);
                $REJECT_REASON7_S99 = $this->manager->getRepository(ReportEntry::class)->getReasonReject('NON PARTICIPATING OUTLET', "S99", $reqWeek);
                $REJECT_REASON8_S99 = $this->manager->getRepository(ReportEntry::class)->getReasonReject('OUTSIDE COVERAGE', "S99", $reqWeek);

                $REJECT_REASON1_SHM = $this->manager->getRepository(ReportEntry::class)->getReasonReject('DUPLICATE RECEIPT', "SHM", $reqWeek);
                $REJECT_REASON2_SHM = $this->manager->getRepository(ReportEntry::class)->getReasonReject('BELOW QUALIFYING QUANTITY', "SHM", $reqWeek);
                $REJECT_REASON3_SHM = $this->manager->getRepository(ReportEntry::class)->getReasonReject('NON PARTICIPATING PRODUCT', "SHM", $reqWeek);
                $REJECT_REASON4_SHM = $this->manager->getRepository(ReportEntry::class)->getReasonReject('OUTSIDE CONTEST PERIOD', "SHM", $reqWeek);
                $REJECT_REASON5_SHM = $this->manager->getRepository(ReportEntry::class)->getReasonReject('UNCLEAR IMAGE/NOT A RECEIPT', "SHM", $reqWeek);
                $REJECT_REASON6_SHM = $this->manager->getRepository(ReportEntry::class)->getReasonReject('BELOW QUALIFYING AMOUNT', "SHM", $reqWeek);
                $REJECT_REASON7_SHM = $this->manager->getRepository(ReportEntry::class)->getReasonReject('NON PARTICIPATING OUTLET', "SHM", $reqWeek);
                $REJECT_REASON8_SHM = $this->manager->getRepository(ReportEntry::class)->getReasonReject('OUTSIDE COVERAGE', "SHM", $reqWeek);

                $REJECT_REASON1_TONT = $this->manager->getRepository(ReportEntry::class)->getReasonReject('DUPLICATE RECEIPT', "TONT", $reqWeek);
                $REJECT_REASON2_TONT = $this->manager->getRepository(ReportEntry::class)->getReasonReject('BELOW QUALIFYING QUANTITY', "TONT", $reqWeek);
                $REJECT_REASON3_TONT = $this->manager->getRepository(ReportEntry::class)->getReasonReject('NON PARTICIPATING PRODUCT', "TONT", $reqWeek);
                $REJECT_REASON4_TONT = $this->manager->getRepository(ReportEntry::class)->getReasonReject('OUTSIDE CONTEST PERIOD', "TONT", $reqWeek);
                $REJECT_REASON5_TONT = $this->manager->getRepository(ReportEntry::class)->getReasonReject('UNCLEAR IMAGE/NOT A RECEIPT', "TONT", $reqWeek);
                $REJECT_REASON6_TONT = $this->manager->getRepository(ReportEntry::class)->getReasonReject('BELOW QUALIFYING AMOUNT', "TONT", $reqWeek);
                $REJECT_REASON7_TONT = $this->manager->getRepository(ReportEntry::class)->getReasonReject('NON PARTICIPATING OUTLET', "TONT", $reqWeek);
                $REJECT_REASON8_TONT = $this->manager->getRepository(ReportEntry::class)->getReasonReject('OUTSIDE COVERAGE', "TONT", $reqWeek);

                $REJECT_REASON1_ECOMM = $this->manager->getRepository(ReportEntry::class)->getReasonReject('DUPLICATE RECEIPT', "ECOMM", $reqWeek);
                $REJECT_REASON2_ECOMM = $this->manager->getRepository(ReportEntry::class)->getReasonReject('BELOW QUALIFYING QUANTITY', "ECOMM", $reqWeek);
                $REJECT_REASON3_ECOMM = $this->manager->getRepository(ReportEntry::class)->getReasonReject('NON PARTICIPATING PRODUCT', "ECOMM", $reqWeek);
                $REJECT_REASON4_ECOMM = $this->manager->getRepository(ReportEntry::class)->getReasonReject('OUTSIDE CONTEST PERIOD', "ECOMM", $reqWeek);
                $REJECT_REASON5_ECOMM = $this->manager->getRepository(ReportEntry::class)->getReasonReject('UNCLEAR IMAGE/NOT A RECEIPT', "ECOMM", $reqWeek);
                $REJECT_REASON6_ECOMM = $this->manager->getRepository(ReportEntry::class)->getReasonReject('BELOW QUALIFYING AMOUNT', "ECOMM", $reqWeek);
                $REJECT_REASON7_ECOMM = $this->manager->getRepository(ReportEntry::class)->getReasonReject('NON PARTICIPATING OUTLET', "ECOMM", $reqWeek);
                $REJECT_REASON8_ECOMM = $this->manager->getRepository(ReportEntry::class)->getReasonReject('OUTSIDE COVERAGE', "ECOMM", $reqWeek);

                /* MONT */
                $ENTRY_KLP_MONT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Kuala Lumpur', "MONT", $reqWeek);
                $ENTRY_SLG_MONT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Selangor', "MONT", $reqWeek);
                $ENTRY_NG9_MONT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Negeri Sembilan', "MONT", $reqWeek);
                $ENTRY_PRK_MONT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Perak', "MONT", $reqWeek);
                $ENTRY_MLK_MONT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Melaka', "MONT", $reqWeek);
                
                $ENTRY_KDH_MONT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Kedah', "MONT", $reqWeek);
                $ENTRY_PPG_MONT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Pulau Pinang', "MONT", $reqWeek);
                $ENTRY_PRL_MONT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Perlis', "MONT", $reqWeek);
                $ENTRY_PJY_MONT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Putrajaya', "MONT", $reqWeek);
                $ENTRY_PHG_MONT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Pahang', "MONT", $reqWeek);
                
                $ENTRY_KLT_MONT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Kelatan', "MONT", $reqWeek);
                $ENTRY_TRG_MONT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Terengganu', "MONT", $reqWeek);
                $ENTRY_JHR_MONT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Johor', "MONT", $reqWeek);
                $ENTRY_SRW_MONT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Sarawak', "MONT", $reqWeek);
                $ENTRY_SBH_MONT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Sabah', "MONT", $reqWeek);
                
                $ENTRY_LBN_MONT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Labuan', "MONT", $reqWeek);

                /* CVS */
                $ENTRY_KLP_CVS =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Kuala Lumpur', "CVS", $reqWeek);
                $ENTRY_SLG_CVS =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Selangor', "CVS", $reqWeek);
                $ENTRY_NG9_CVS =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Negeri Sembilan', "CVS", $reqWeek);
                $ENTRY_PRK_CVS =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Perak', "CVS", $reqWeek);
                $ENTRY_MLK_CVS =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Melaka', "CVS", $reqWeek);

                $ENTRY_KDH_CVS =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Kedah', "CVS", $reqWeek);
                $ENTRY_PPG_CVS =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Pulau Pinang', "CVS", $reqWeek);
                $ENTRY_PRL_CVS =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Perlis', "CVS", $reqWeek);
                $ENTRY_PJY_CVS =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Putrajaya', "CVS", $reqWeek);
                $ENTRY_PHG_CVS =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Pahang', "CVS", $reqWeek);

                $ENTRY_KLT_CVS =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Kelatan', "CVS", $reqWeek);
                $ENTRY_TRG_CVS =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Terengganu', "CVS", $reqWeek);
                $ENTRY_JHR_CVS =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Johor', "CVS", $reqWeek);
                $ENTRY_SRW_CVS =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Sarawak', "CVS", $reqWeek);
                $ENTRY_SBH_CVS =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Sabah', "CVS", $reqWeek);

                $ENTRY_LBN_CVS =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Labuan', "CVS", $reqWeek);

                /* TOFT */
                $ENTRY_KLP_TOFT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Kuala Lumpur', "TOFT", $reqWeek);
                $ENTRY_SLG_TOFT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Selangor', "TOFT", $reqWeek);
                $ENTRY_NG9_TOFT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Negeri Sembilan', "TOFT", $reqWeek);
                $ENTRY_PRK_TOFT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Perak', "TOFT", $reqWeek);
                $ENTRY_MLK_TOFT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Melaka', "TOFT", $reqWeek);

                $ENTRY_KDH_TOFT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Kedah', "TOFT", $reqWeek);
                $ENTRY_PPG_TOFT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Pulau Pinang', "TOFT", $reqWeek);
                $ENTRY_PRL_TOFT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Perlis', "TOFT", $reqWeek);
                $ENTRY_PJY_TOFT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Putrajaya', "TOFT", $reqWeek);
                $ENTRY_PHG_TOFT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Pahang', "TOFT", $reqWeek);

                $ENTRY_KLT_TOFT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Kelatan', "TOFT", $reqWeek);
                $ENTRY_TRG_TOFT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Terengganu', "TOFT", $reqWeek);
                $ENTRY_JHR_TOFT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Johor', "TOFT", $reqWeek);
                $ENTRY_SRW_TOFT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Sarawak', "TOFT", $reqWeek);
                $ENTRY_SBH_TOFT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Sabah', "TOFT", $reqWeek);

                $ENTRY_LBN_TOFT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Labuan', "TOFT", $reqWeek);

                /* S99 */
                $ENTRY_KLP_S99 =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Kuala Lumpur', "S99", $reqWeek);
                $ENTRY_SLG_S99 =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Selangor', "S99", $reqWeek);
                $ENTRY_NG9_S99 =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Negeri Sembilan', "S99", $reqWeek);
                $ENTRY_PRK_S99 =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Perak', "S99", $reqWeek);
                $ENTRY_MLK_S99 =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Melaka', "S99", $reqWeek);

                $ENTRY_KDH_S99 =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Kedah', "S99", $reqWeek);
                $ENTRY_PPG_S99 =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Pulau Pinang', "S99", $reqWeek);
                $ENTRY_PRL_S99 =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Perlis', "S99", $reqWeek);
                $ENTRY_PJY_S99 =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Putrajaya', "S99", $reqWeek);
                $ENTRY_PHG_S99 =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Pahang', "S99", $reqWeek);

                $ENTRY_KLT_S99 =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Kelatan', "S99", $reqWeek);
                $ENTRY_TRG_S99 =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Terengganu', "S99", $reqWeek);
                $ENTRY_JHR_S99 =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Johor', "S99", $reqWeek);
                $ENTRY_SRW_S99 =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Sarawak', "S99", $reqWeek);
                $ENTRY_SBH_S99 =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Sabah', "S99", $reqWeek);

                $ENTRY_LBN_S99 =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Labuan', "S99", $reqWeek);

                /* SHM */
                $ENTRY_KLP_SHM =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Kuala Lumpur', "SHM", $reqWeek);
                $ENTRY_SLG_SHM =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Selangor', "SHM", $reqWeek);
                $ENTRY_NG9_SHM =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Negeri Sembilan', "SHM", $reqWeek);
                $ENTRY_PRK_SHM =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Perak', "SHM", $reqWeek);
                $ENTRY_MLK_SHM =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Melaka', "SHM", $reqWeek);

                $ENTRY_KDH_SHM =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Kedah', "SHM", $reqWeek);
                $ENTRY_PPG_SHM =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Pulau Pinang', "SHM", $reqWeek);
                $ENTRY_PRL_SHM =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Perlis', "SHM", $reqWeek);
                $ENTRY_PJY_SHM =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Putrajaya', "SHM", $reqWeek);
                $ENTRY_PHG_SHM =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Pahang', "SHM", $reqWeek);

                $ENTRY_KLT_SHM =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Kelatan', "SHM", $reqWeek);
                $ENTRY_TRG_SHM =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Terengganu', "SHM", $reqWeek);
                $ENTRY_JHR_SHM =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Johor', "SHM", $reqWeek);
                $ENTRY_SRW_SHM =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Sarawak', "SHM", $reqWeek);
                $ENTRY_SBH_SHM =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Sabah', "SHM", $reqWeek);

                $ENTRY_LBN_SHM =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Labuan', "SHM", $reqWeek);

                /* TONT */
                $ENTRY_KLP_TONT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Kuala Lumpur', "TONT", $reqWeek);
                $ENTRY_SLG_TONT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Selangor', "TONT", $reqWeek);
                $ENTRY_NG9_TONT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Negeri Sembilan', "TONT", $reqWeek);
                $ENTRY_PRK_TONT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Perak', "TONT", $reqWeek);
                $ENTRY_MLK_TONT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Melaka', "TONT", $reqWeek);

                $ENTRY_KDH_TONT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Kedah', "TONT", $reqWeek);
                $ENTRY_PPG_TONT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Pulau Pinang', "TONT", $reqWeek);
                $ENTRY_PRL_TONT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Perlis', "TONT", $reqWeek);
                $ENTRY_PJY_TONT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Putrajaya', "TONT", $reqWeek);
                $ENTRY_PHG_TONT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Pahang', "TONT", $reqWeek);

                $ENTRY_KLT_TONT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Kelatan', "TONT", $reqWeek);
                $ENTRY_TRG_TONT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Terengganu', "TONT", $reqWeek);
                $ENTRY_JHR_TONT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Johor', "TONT", $reqWeek);
                $ENTRY_SRW_TONT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Sarawak', "TONT", $reqWeek);
                $ENTRY_SBH_TONT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Sabah', "TONT", $reqWeek);

                $ENTRY_LBN_TONT =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Labuan', "TONT", $reqWeek);

                /* ECOMM */
                $ENTRY_KLP_ECOMM =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Kuala Lumpur', "ECOMM", $reqWeek);
                $ENTRY_SLG_ECOMM =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Selangor', "ECOMM", $reqWeek);
                $ENTRY_NG9_ECOMM =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Negeri Sembilan', "ECOMM", $reqWeek);
                $ENTRY_PRK_ECOMM =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Perak', "ECOMM", $reqWeek);
                $ENTRY_MLK_ECOMM =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Melaka', "ECOMM", $reqWeek);

                $ENTRY_KDH_ECOMM =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Kedah', "ECOMM", $reqWeek);
                $ENTRY_PPG_ECOMM =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Pulau Pinang', "ECOMM", $reqWeek);
                $ENTRY_PRL_ECOMM =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Perlis', "ECOMM", $reqWeek);
                $ENTRY_PJY_ECOMM =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Putrajaya', "ECOMM", $reqWeek);
                $ENTRY_PHG_ECOMM =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Pahang', "ECOMM", $reqWeek);

                $ENTRY_KLT_ECOMM =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Kelatan', "ECOMM", $reqWeek);
                $ENTRY_TRG_ECOMM =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Terengganu', "ECOMM", $reqWeek);
                $ENTRY_JHR_ECOMM =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Johor', "ECOMM", $reqWeek);
                $ENTRY_SRW_ECOMM =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Sarawak', "ECOMM", $reqWeek);
                $ENTRY_SBH_ECOMM =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Sabah', "ECOMM", $reqWeek);

                $ENTRY_LBN_ECOMM =  $this->manager->getRepository(ReportByState::class)->getStateSubmissions('Labuan', "ECOMM", $reqWeek);
                $ALLDATA = [
                    array(
                        "mont_total"=> $MONT_RESULT_TOTAL,
                        "cvs_total"=> $CVS_RESULT_TOTAL,
                        "toft_total"=> $TOFT_RESULT_TOTAL,
                        "s99_total"=>  $S99_RESULT_TOTAL,
                        "tont_total" => $TONT_RESULT_TOTAL,
                        "ecomm_total" => $ECOMM_RESULT_TOTAL,
                        "shm_total" => $SHM_RESULT_TOTAL
                    ),
                    array(
                        "mont_valid"=> $MONT_VALID,
                        "cvs_valid"=> $CVS_VALID,
                        "toft_valid"=> $TOFT_VALID,
                        "s99_valid"=>  $S99_VALID,
                        "tont_valid" => $TONT_VALID,
                        "ecomm_valid" => $ECOMM_VALID,
                        "shm_valid" => $SHM_VALID
                    ),
                    array(
                        "mont_invalid"=> $MONT_INVALID,
                        "cvs_invalid"=> $CVS_INVALID,
                        "toft_invalid"=> $TOFT_INVALID,
                        "s99_invalid"=>  $S99_INVALID,
                        "tont_invalid" => $TONT_INVALID,
                        "ecomm_invalid" => $ECOMM_INVALID,
                        "shm_invalid" => $SHM_INVALID
                    ),
                    array(
                        "mont_pending"=> $MONT_RESULT_TOTAL - ($MONT_VALID + $MONT_INVALID),
                        "cvs_pending"=> $CVS_RESULT_TOTAL - ($CVS_VALID + $CVS_INVALID),
                        "toft_pending"=> $TOFT_RESULT_TOTAL - ($TOFT_VALID + $TOFT_INVALID),
                        "s99_pending"=>  $S99_RESULT_TOTAL - ($S99_VALID + $S99_INVALID),
                        "tont_pending" => $TONT_RESULT_TOTAL - ($TONT_VALID + $TONT_INVALID),
                        "ecomm_pending" => $ECOMM_RESULT_TOTAL - ($ECOMM_VALID + $ECOMM_INVALID),
                        "shm_pending" => $SHM_RESULT_TOTAL - ($SHM_VALID + $SHM_INVALID)
                    ),
                    array(
                        "m"=> $GENDER_MALE,
                        "f" => $GENDER_FEMALE
                    ),
                    array(
                        "age_21_25_mont" => $AGE_21_25_MONT,
                        "age_26_30_mont" => $AGE_26_30_MONT,
                        "age_31_35_mont" => $AGE_31_35_MONT,
                        "age_36_40_mont" => $AGE_36_40_MONT,
                        "age_41_45_mont" => $AGE_41_45_MONT,
                        "age_46_50_mont" => $AGE_46_50_MONT,
                        "age_abv_50_mont" => $AGE_ABV_50_MONT,
                    ),
                    array(
                        "age_21_25_cvs" => $AGE_21_25_CVS,
                        "age_26_30_cvs" => $AGE_26_30_CVS,
                        "age_31_35_cvs" => $AGE_31_35_CVS,
                        "age_36_40_cvs" => $AGE_36_40_CVS,
                        "age_41_45_cvs" => $AGE_41_45_CVS,
                        "age_46_50_cvs" => $AGE_46_50_CVS,
                        "age_abv_50_cvs" => $AGE_ABV_50_CVS,
                    ),
                    array(
                        "age_21_25_toft" => $AGE_21_25_TOFT,
                        "age_26_30_toft" => $AGE_26_30_TOFT,
                        "age_31_35_toft" => $AGE_31_35_TOFT,
                        "age_36_40_toft" => $AGE_36_40_TOFT,
                        "age_41_45_toft" => $AGE_41_45_TOFT,
                        "age_46_50_toft" => $AGE_46_50_TOFT,
                        "age_abv_50_toft" => $AGE_ABV_50_TOFT,
                    ),
                    array(
                        "age_21_25_shm" => $AGE_21_25_SHM,
                        "age_26_30_shm" => $AGE_26_30_SHM,
                        "age_31_35_shm" => $AGE_31_35_SHM,
                        "age_36_40_shm" => $AGE_36_40_SHM,
                        "age_41_45_shm" => $AGE_41_45_SHM,
                        "age_46_50_shm" => $AGE_46_50_SHM,
                        "age_abv_50_shm" => $AGE_ABV_50_SHM,
                    ),
                    array(
                        "age_21_25_s99" => $AGE_21_25_S99,
                        "age_26_30_s99" => $AGE_26_30_S99,
                        "age_31_35_s99" => $AGE_31_35_S99,
                        "age_36_40_s99" => $AGE_36_40_S99,
                        "age_41_45_s99" => $AGE_41_45_S99,
                        "age_46_50_s99" => $AGE_46_50_S99,
                        "age_abv_50_s99" => $AGE_ABV_50_S99,
                    ),
                    array(
                        "age_21_25_tont" => $AGE_21_25_TONT,
                        "age_26_30_tont" => $AGE_26_30_TONT,
                        "age_31_35_tont" => $AGE_31_35_TONT,
                        "age_36_40_tont" => $AGE_36_40_TONT,
                        "age_41_45_tont" => $AGE_41_45_TONT,
                        "age_46_50_tont" => $AGE_46_50_TONT,
                        "age_abv_50_tont" => $AGE_ABV_50_TONT,
                    ),
                    array(
                        "age_21_25_ecomm" => $AGE_21_25_ECOMM,
                        "age_26_30_ecomm" => $AGE_26_30_ECOMM,
                        "age_31_35_ecomm" => $AGE_31_35_ECOMM,
                        "age_36_40_ecomm" => $AGE_36_40_ECOMM,
                        "age_41_45_ecomm" => $AGE_41_45_ECOMM,
                        "age_46_50_ecomm" => $AGE_46_50_ECOMM,
                        "age_abv_50_ecomm" => $AGE_ABV_50_ECOMM,
                    ),
                    array(
                        "inv_mont_total" => 0,
                        "inv_cvs_total" => 0,
                        "inv_toft_total" => 0,
                        "inv_s99_total" => 0,
                        "inv_shm_total" => 0,
                        "inv_tont_total" => 0,
                        "inv_ecomm_total" => 0,
                    ),
                    array(
                        "inv_mont_redeem" => $INV_MONT_REDEEM,
                        "inv_cvs_redeem" => $INV_CVS_REDEEM,
                        "inv_toft_redeem" => $INV_TOFT_REDEEM,
                        "inv_s99_redeem" => $INV_S99_REDEEM,
                        "inv_shm_redeem" => $INV_SHM_REDEEM,
                        "inv_tont_redeem" => $INV_TONT_REDEEM,
                        "inv_ecomm_redeem" => $INV_ECOMM_REDEEM,
                    ),
                    array(
                        "inv_mont_balance" => 0 - $INV_MONT_REDEEM,
                        "inv_cvs_balance" => 0 - $INV_CVS_REDEEM,
                        "inv_toft_balance" => 0 - $INV_TOFT_REDEEM,
                        "inv_s99_balance" => 0,
                        "inv_shm_balance" => 0 - $INV_SHM_REDEEM,
                        "inv_tont_balance" => 0 - $INV_TONT_REDEEM,
                        "inv_ecomm_balance" => 0 - $INV_ECOMM_REDEEM,
                    ),
                    array(
                        "del_mont_assigned" => $DEL_MONT_ADDRESS,
                        "del_mont_process" => $DEL_MONT_PROCESS,
                        "del_mont_out" => $DEL_MONT_OUT,
                        "del_cvs_assigned" => $DEL_CVS_ADDRESS,
                        "del_cvs_process" => $DEL_CVS_PROCESS,
                        "del_cvs_out" => $DEL_CVS_OUT,
                        "del_toft_assigned" => $DEL_TOFT_ADDRESS,
                        "del_toft_process" => $DEL_TOFT_PROCESS,
                        "del_toft_out" => $DEL_TOFT_OUT,
                        "del_s99_assigned" => $DEL_S99_ADDRESS,                        
                        "del_s99_process" => $DEL_S99_PROCESS,
                        "del_s99_out" => $DEL_S99_OUT,
                        "del_shm_assigned" => $DEL_SHM_ADDRESS,                                                
                        "del_shm_process" => $DEL_SHM_PROCESS,
                        "del_shm_out" => $DEL_SHM_OUT,
                        "del_tont_assigned" => $DEL_TONT_ADDRESS,                                                                        
                        "del_tont_process" => $DEL_TONT_PROCESS,
                        "del_tont_out" => $DEL_TONT_OUT,
                        "del_ecomm_assigned" => $DEL_ECOMM_ADDRESS,                                                                        
                        "del_ecomm_process" => $DEL_ECOMM_PROCESS,
                        "del_ecomm_out" => $DEL_ECOMM_OUT,
                    ),
                    array(
                        "reject_reason1_mont" => $REJECT_REASON1_MONT,
                        "reject_reason2_mont" => $REJECT_REASON2_MONT,
                        "reject_reason3_mont" => $REJECT_REASON3_MONT,
                        "reject_reason4_mont" => $REJECT_REASON4_MONT,
                        "reject_reason5_mont" => $REJECT_REASON5_MONT,
                        "reject_reason6_mont" => $REJECT_REASON6_MONT,
                        "reject_reason7_mont" => $REJECT_REASON7_MONT,
                        "reject_reason8_mont" => $REJECT_REASON8_MONT,
                    ),
                    array(
                        "reject_reason1_cvs" => $REJECT_REASON1_CVS,
                        "reject_reason2_cvs" => $REJECT_REASON2_CVS,
                        "reject_reason3_cvs" => $REJECT_REASON3_CVS,
                        "reject_reason4_cvs" => $REJECT_REASON4_CVS,
                        "reject_reason5_cvs" => $REJECT_REASON5_CVS,
                        "reject_reason6_cvs" => $REJECT_REASON6_CVS,
                        "reject_reason7_cvs" => $REJECT_REASON7_CVS,
                        "reject_reason8_cvs" => $REJECT_REASON8_CVS,
                    ),
                    array(
                        "reject_reason1_toft" => $REJECT_REASON1_TOFT,
                        "reject_reason2_toft" => $REJECT_REASON2_TOFT,
                        "reject_reason3_toft" => $REJECT_REASON3_TOFT,
                        "reject_reason4_toft" => $REJECT_REASON4_TOFT,
                        "reject_reason5_toft" => $REJECT_REASON5_TOFT,
                        "reject_reason6_toft" => $REJECT_REASON6_TOFT,
                        "reject_reason7_toft" => $REJECT_REASON7_TOFT,
                        "reject_reason8_toft" => $REJECT_REASON8_TOFT,
                    ),
                    array(
                        "reject_reason1_s99" => $REJECT_REASON1_S99,
                        "reject_reason2_s99" => $REJECT_REASON2_S99,
                        "reject_reason3_s99" => $REJECT_REASON3_S99,
                        "reject_reason4_s99" => $REJECT_REASON4_S99,
                        "reject_reason5_s99" => $REJECT_REASON5_S99,
                        "reject_reason6_s99" => $REJECT_REASON6_S99,
                        "reject_reason7_s99" => $REJECT_REASON7_S99,
                        "reject_reason8_s99" => $REJECT_REASON8_S99,
                    ),
                    array(
                        "reject_reason1_shm" => $REJECT_REASON1_SHM,
                        "reject_reason2_shm" => $REJECT_REASON2_SHM,
                        "reject_reason3_shm" => $REJECT_REASON3_SHM,
                        "reject_reason4_shm" => $REJECT_REASON4_SHM,
                        "reject_reason5_shm" => $REJECT_REASON5_SHM,
                        "reject_reason6_shm" => $REJECT_REASON6_SHM,
                        "reject_reason7_shm" => $REJECT_REASON7_SHM,
                        "reject_reason8_shm" => $REJECT_REASON8_SHM,
                    ),
                    array(
                        "reject_reason1_tont" => $REJECT_REASON1_TONT,
                        "reject_reason2_tont" => $REJECT_REASON2_TONT,
                        "reject_reason3_tont" => $REJECT_REASON3_TONT,
                        "reject_reason4_tont" => $REJECT_REASON4_TONT,
                        "reject_reason5_tont" => $REJECT_REASON5_TONT,
                        "reject_reason6_tont" => $REJECT_REASON6_TONT,
                        "reject_reason7_tont" => $REJECT_REASON7_TONT,
                        "reject_reason8_tont" => $REJECT_REASON8_TONT,
                    ),
                    array(
                        "reject_reason1_ecomm" => $REJECT_REASON1_ECOMM,
                        "reject_reason2_ecomm" => $REJECT_REASON2_ECOMM,
                        "reject_reason3_ecomm" => $REJECT_REASON3_ECOMM,
                        "reject_reason4_ecomm" => $REJECT_REASON4_ECOMM,
                        "reject_reason5_ecomm" => $REJECT_REASON5_ECOMM,
                        "reject_reason6_ecomm" => $REJECT_REASON6_ECOMM,
                        "reject_reason7_ecomm" => $REJECT_REASON7_ECOMM,
                        "reject_reason8_ecomm" => $REJECT_REASON8_ECOMM,
                    )
                ];
                $RPT_ENTRY_2024 = $this->manager->getRepository(ReportEntry::class)->findOneBy(
                    array (
                        "week_number" => $reqWeek
                    )
                );
                $RPT_BY_STATE_2024 = $this->manager->getRepository(ReportByState::class)->findBy(
                    array (
                        "week_number" => $reqWeek
                    )
                ); 
                
                if ($RPT_BY_STATE_2024) {
                    // Existing THEN DELETE
                    $DEL_RPT_BY_STATE_2024 = $this->manager->getRepository(ReportByState::class)->deleteAllRelated($reqWeek); 
                }

                $this->logger->info(">>>>>>>>>>>>>>>>>>>>>>>>>>>> EXTRACTOR WEEK : ". $reqWeek);
                $this->logger->info(">>>>>>>>>>>>>>>>>>>>>>>>>>>> EXTRACTOR MONT Inventory Total : ". $INV_MONT_TOTAL);
                $this->logger->info(">>>>>>>>>>>>>>>>>>>>>>>>>>>> EXTRACTOR CVS Inventory Total : ". $INV_CVS_TOTAL);
                $this->logger->info(">>>>>>>>>>>>>>>>>>>>>>>>>>>> EXTRACTOR TOFT Inventory Total : ". $INV_TOFT_TOTAL);
                $this->logger->info(">>>>>>>>>>>>>>>>>>>>>>>>>>>> EXTRACTOR SHM Inventory Total : ". $INV_SHM_TOTAL);
                $this->logger->info(">>>>>>>>>>>>>>>>>>>>>>>>>>>> EXTRACTOR S99 Inventory Total : ". $INV_S99_TOTAL);
                $this->logger->info(">>>>>>>>>>>>>>>>>>>>>>>>>>>> EXTRACTOR TONT Inventory Total : ". $INV_TONT_TOTAL);
                $this->logger->info(">>>>>>>>>>>>>>>>>>>>>>>>>>>> EXTRACTOR ECOMM Inventory Total : ". $INV_ECOMM_TOTAL);

                $this->logger->info(">>>>>>>>>>>>>>>>>>>>>>>>>>>> EXTRACTOR MONT Redeem Total : ". $INV_MONT_REDEEM);
                $this->logger->info(">>>>>>>>>>>>>>>>>>>>>>>>>>>> EXTRACTOR CVS Redeem Total : ". $INV_CVS_REDEEM);
                $this->logger->info(">>>>>>>>>>>>>>>>>>>>>>>>>>>> EXTRACTOR TOFT Redeem Total : ". $INV_TOFT_REDEEM);
                $this->logger->info(">>>>>>>>>>>>>>>>>>>>>>>>>>>> EXTRACTOR SHM Redeem Total : ". $INV_SHM_REDEEM);
                $this->logger->info(">>>>>>>>>>>>>>>>>>>>>>>>>>>> EXTRACTOR S99 Redeem Total : ". $INV_S99_REDEEM);
                $this->logger->info(">>>>>>>>>>>>>>>>>>>>>>>>>>>> EXTRACTOR TONT Redeem Total : ". $INV_TONT_REDEEM);
                $this->logger->info(">>>>>>>>>>>>>>>>>>>>>>>>>>>> EXTRACTOR ECOMM Redeem Total : ". $INV_ECOMM_REDEEM);

                $this->pumpByState2024($ENTRY_KLP_MONT, "MONT", $reqWeek);
                $this->pumpByState2024($ENTRY_SLG_MONT, "MONT", $reqWeek);
                $this->pumpByState2024($ENTRY_NG9_MONT, "MONT", $reqWeek);
                $this->pumpByState2024($ENTRY_PRK_MONT, "MONT", $reqWeek);
                $this->pumpByState2024($ENTRY_MLK_MONT, "MONT", $reqWeek);
                $this->pumpByState2024($ENTRY_KDH_MONT, "MONT", $reqWeek);
                $this->pumpByState2024($ENTRY_PPG_MONT, "MONT", $reqWeek);
                $this->pumpByState2024($ENTRY_PRL_MONT, "MONT", $reqWeek);
                $this->pumpByState2024($ENTRY_PJY_MONT, "MONT", $reqWeek);
                $this->pumpByState2024($ENTRY_PHG_MONT, "MONT", $reqWeek);
                $this->pumpByState2024($ENTRY_KLT_MONT, "MONT", $reqWeek);
                $this->pumpByState2024($ENTRY_TRG_MONT, "MONT", $reqWeek);
                $this->pumpByState2024($ENTRY_JHR_MONT, "MONT", $reqWeek);
                $this->pumpByState2024($ENTRY_SRW_MONT, "MONT", $reqWeek);
                $this->pumpByState2024($ENTRY_SBH_MONT, "MONT", $reqWeek);
                $this->pumpByState2024($ENTRY_LBN_MONT, "MONT", $reqWeek);


                $this->pumpByState2024($ENTRY_KLP_CVS, "CVS", $reqWeek);
                $this->pumpByState2024($ENTRY_SLG_CVS, "CVS", $reqWeek);
                $this->pumpByState2024($ENTRY_NG9_CVS, "CVS", $reqWeek);
                $this->pumpByState2024($ENTRY_PRK_CVS, "CVS", $reqWeek);
                $this->pumpByState2024($ENTRY_MLK_CVS, "CVS", $reqWeek);
                $this->pumpByState2024($ENTRY_KDH_CVS, "CVS", $reqWeek);
                $this->pumpByState2024($ENTRY_PPG_CVS, "CVS", $reqWeek);
                $this->pumpByState2024($ENTRY_PRL_CVS, "CVS", $reqWeek);
                $this->pumpByState2024($ENTRY_PJY_CVS, "CVS", $reqWeek);
                $this->pumpByState2024($ENTRY_PHG_CVS, "CVS", $reqWeek);
                $this->pumpByState2024($ENTRY_KLT_CVS, "CVS", $reqWeek);
                $this->pumpByState2024($ENTRY_TRG_CVS, "CVS", $reqWeek);
                $this->pumpByState2024($ENTRY_JHR_CVS, "CVS", $reqWeek);
                $this->pumpByState2024($ENTRY_SRW_CVS, "CVS", $reqWeek);
                $this->pumpByState2024($ENTRY_SBH_CVS, "CVS", $reqWeek);
                $this->pumpByState2024($ENTRY_LBN_CVS, "CVS", $reqWeek);

                $this->pumpByState2024($ENTRY_KLP_TOFT, "TOFT", $reqWeek);
                $this->pumpByState2024($ENTRY_SLG_TOFT, "TOFT", $reqWeek);
                $this->pumpByState2024($ENTRY_NG9_TOFT, "TOFT", $reqWeek);
                $this->pumpByState2024($ENTRY_PRK_TOFT, "TOFT", $reqWeek);
                $this->pumpByState2024($ENTRY_MLK_TOFT, "TOFT", $reqWeek);
                $this->pumpByState2024($ENTRY_KDH_TOFT, "TOFT", $reqWeek);
                $this->pumpByState2024($ENTRY_PPG_TOFT, "TOFT", $reqWeek);
                $this->pumpByState2024($ENTRY_PRL_TOFT, "TOFT", $reqWeek);
                $this->pumpByState2024($ENTRY_PJY_TOFT, "TOFT", $reqWeek);
                $this->pumpByState2024($ENTRY_PHG_TOFT, "TOFT", $reqWeek);
                $this->pumpByState2024($ENTRY_KLT_TOFT, "TOFT", $reqWeek);
                $this->pumpByState2024($ENTRY_TRG_TOFT, "TOFT", $reqWeek);
                $this->pumpByState2024($ENTRY_JHR_TOFT, "TOFT", $reqWeek);
                $this->pumpByState2024($ENTRY_SRW_TOFT, "TOFT", $reqWeek);
                $this->pumpByState2024($ENTRY_SBH_TOFT, "TOFT", $reqWeek);
                $this->pumpByState2024($ENTRY_LBN_TOFT, "TOFT", $reqWeek);


                $this->pumpByState2024($ENTRY_KLP_S99, "S99", $reqWeek);
                $this->pumpByState2024($ENTRY_SLG_S99, "S99", $reqWeek);
                $this->pumpByState2024($ENTRY_NG9_S99, "S99", $reqWeek);
                $this->pumpByState2024($ENTRY_PRK_S99, "S99", $reqWeek);
                $this->pumpByState2024($ENTRY_MLK_S99, "S99", $reqWeek);
                $this->pumpByState2024($ENTRY_KDH_S99, "S99", $reqWeek);
                $this->pumpByState2024($ENTRY_PPG_S99, "S99", $reqWeek);
                $this->pumpByState2024($ENTRY_PRL_S99, "S99", $reqWeek);
                $this->pumpByState2024($ENTRY_PJY_S99, "S99", $reqWeek);
                $this->pumpByState2024($ENTRY_PHG_S99, "S99", $reqWeek);
                $this->pumpByState2024($ENTRY_KLT_S99, "S99", $reqWeek);
                $this->pumpByState2024($ENTRY_TRG_S99, "S99", $reqWeek);
                $this->pumpByState2024($ENTRY_JHR_S99, "S99", $reqWeek);
                $this->pumpByState2024($ENTRY_SRW_S99, "S99", $reqWeek);
                $this->pumpByState2024($ENTRY_SBH_S99, "S99", $reqWeek);
                $this->pumpByState2024($ENTRY_LBN_S99, "S99", $reqWeek);


                $this->pumpByState2024($ENTRY_KLP_SHM, "SHM", $reqWeek);
                $this->pumpByState2024($ENTRY_SLG_SHM, "SHM", $reqWeek);
                $this->pumpByState2024($ENTRY_NG9_SHM, "SHM", $reqWeek);
                $this->pumpByState2024($ENTRY_PRK_SHM, "SHM", $reqWeek);
                $this->pumpByState2024($ENTRY_MLK_SHM, "SHM", $reqWeek);
                $this->pumpByState2024($ENTRY_KDH_SHM, "SHM", $reqWeek);
                $this->pumpByState2024($ENTRY_PPG_SHM, "SHM", $reqWeek);
                $this->pumpByState2024($ENTRY_PRL_SHM, "SHM", $reqWeek);
                $this->pumpByState2024($ENTRY_PJY_SHM, "SHM", $reqWeek);
                $this->pumpByState2024($ENTRY_PHG_SHM, "SHM", $reqWeek);
                $this->pumpByState2024($ENTRY_KLT_SHM, "SHM", $reqWeek);
                $this->pumpByState2024($ENTRY_TRG_SHM, "SHM", $reqWeek);
                $this->pumpByState2024($ENTRY_JHR_SHM, "SHM", $reqWeek);
                $this->pumpByState2024($ENTRY_SRW_SHM, "SHM", $reqWeek);
                $this->pumpByState2024($ENTRY_SBH_SHM, "SHM", $reqWeek);
                $this->pumpByState2024($ENTRY_LBN_SHM, "SHM", $reqWeek);

                
                $this->pumpByState2024($ENTRY_KLP_TONT, "TONT", $reqWeek);
                $this->pumpByState2024($ENTRY_SLG_TONT, "TONT", $reqWeek);
                $this->pumpByState2024($ENTRY_NG9_TONT, "TONT", $reqWeek);
                $this->pumpByState2024($ENTRY_PRK_TONT, "TONT", $reqWeek);
                $this->pumpByState2024($ENTRY_MLK_TONT, "TONT", $reqWeek);
                $this->pumpByState2024($ENTRY_KDH_TONT, "TONT", $reqWeek);
                $this->pumpByState2024($ENTRY_PPG_TONT, "TONT", $reqWeek);
                $this->pumpByState2024($ENTRY_PRL_TONT, "TONT", $reqWeek);
                $this->pumpByState2024($ENTRY_PJY_TONT, "TONT", $reqWeek);
                $this->pumpByState2024($ENTRY_PHG_TONT, "TONT", $reqWeek);
                $this->pumpByState2024($ENTRY_KLT_TONT, "TONT", $reqWeek);
                $this->pumpByState2024($ENTRY_TRG_TONT, "TONT", $reqWeek);
                $this->pumpByState2024($ENTRY_JHR_TONT, "TONT", $reqWeek);
                $this->pumpByState2024($ENTRY_SRW_TONT, "TONT", $reqWeek);
                $this->pumpByState2024($ENTRY_SBH_TONT, "TONT", $reqWeek);
                $this->pumpByState2024($ENTRY_LBN_TONT, "TONT", $reqWeek);
                
                $this->pumpByState2024($ENTRY_KLP_ECOMM, "ECOMM", $reqWeek);
                $this->pumpByState2024($ENTRY_SLG_ECOMM, "ECOMM", $reqWeek);
                $this->pumpByState2024($ENTRY_NG9_ECOMM, "ECOMM", $reqWeek);
                $this->pumpByState2024($ENTRY_PRK_ECOMM, "ECOMM", $reqWeek);
                $this->pumpByState2024($ENTRY_MLK_ECOMM, "ECOMM", $reqWeek);
                $this->pumpByState2024($ENTRY_KDH_ECOMM, "ECOMM", $reqWeek);
                $this->pumpByState2024($ENTRY_PPG_ECOMM, "ECOMM", $reqWeek);
                $this->pumpByState2024($ENTRY_PRL_ECOMM, "ECOMM", $reqWeek);
                $this->pumpByState2024($ENTRY_PJY_ECOMM, "ECOMM", $reqWeek);
                $this->pumpByState2024($ENTRY_PHG_ECOMM, "ECOMM", $reqWeek);
                $this->pumpByState2024($ENTRY_KLT_ECOMM, "ECOMM", $reqWeek);
                $this->pumpByState2024($ENTRY_TRG_ECOMM, "ECOMM", $reqWeek);
                $this->pumpByState2024($ENTRY_JHR_ECOMM, "ECOMM", $reqWeek);
                $this->pumpByState2024($ENTRY_SRW_ECOMM, "ECOMM", $reqWeek);
                $this->pumpByState2024($ENTRY_SBH_ECOMM, "ECOMM", $reqWeek);
                $this->pumpByState2024($ENTRY_LBN_ECOMM, "ECOMM", $reqWeek);

                // SKU Report Processing
                $output->writeln('');            
                $output->writeln('<fg=bright-red>Processing SKU Report Data');
                $output->writeln('');
                
                $RPT_BY_SKU_2024 = $this->manager->getRepository(ReportBySku::class)->findBy(
                    array (
                        "week_number" => $reqWeek
                    )
                ); 
                
                if ($RPT_BY_SKU_2024) {
                    // Existing THEN DELETE
                    $DEL_RPT_BY_SKU_2024 = $this->manager->getRepository(ReportBySku::class)->deleteAllRelated($reqWeek); 
                }

                // Extract SKU data for each channel
                $SKU_MONT = $this->manager->getRepository(ReportBySku::class)->getSkuSubmissions("MONT", $reqWeek);
                $SKU_CVS = $this->manager->getRepository(ReportBySku::class)->getSkuSubmissions("CVS", $reqWeek);
                $SKU_TOFT = $this->manager->getRepository(ReportBySku::class)->getSkuSubmissions("TOFT", $reqWeek);
                $SKU_S99 = $this->manager->getRepository(ReportBySku::class)->getSkuSubmissions("S99", $reqWeek);
                $SKU_SHM = $this->manager->getRepository(ReportBySku::class)->getSkuSubmissions("SHM", $reqWeek);
                $SKU_TONT = $this->manager->getRepository(ReportBySku::class)->getSkuSubmissions("TONT", $reqWeek);
                $SKU_ECOMM = $this->manager->getRepository(ReportBySku::class)->getSkuSubmissions("ECOMM", $reqWeek);

                // Pump SKU data into ReportBySku table
                $this->pumpBySku2024($SKU_MONT, "MONT", $reqWeek);
                $this->pumpBySku2024($SKU_CVS, "CVS", $reqWeek);
                $this->pumpBySku2024($SKU_TOFT, "TOFT", $reqWeek);
                $this->pumpBySku2024($SKU_S99, "S99", $reqWeek);
                $this->pumpBySku2024($SKU_SHM, "SHM", $reqWeek);
                $this->pumpBySku2024($SKU_TONT, "TONT", $reqWeek);
                $this->pumpBySku2024($SKU_ECOMM, "ECOMM", $reqWeek);

                if($RPT_ENTRY_2024) {
                    // MEANS WEEK N exist.
                    // $RPT_ENTRY_2024->setLastUpdated(new \DateTime);
                    $RPT_ENTRY_2024->setMontTotal($MONT_RESULT_TOTAL);
                    $RPT_ENTRY_2024->setCvsTotal($CVS_RESULT_TOTAL);
                    $RPT_ENTRY_2024->setToftTotal($TOFT_RESULT_TOTAL);
                    $RPT_ENTRY_2024->setS99Total($S99_RESULT_TOTAL);
                    $RPT_ENTRY_2024->setTontTotal($TONT_RESULT_TOTAL);
                    $RPT_ENTRY_2024->setEcommTotal($ECOMM_RESULT_TOTAL);
                    $RPT_ENTRY_2024->setShmTotal($SHM_RESULT_TOTAL);
    
                    $RPT_ENTRY_2024->setMontValid($MONT_VALID);
                    $RPT_ENTRY_2024->setCvsValid($CVS_VALID);
                    $RPT_ENTRY_2024->setToftValid($TOFT_VALID);
                    $RPT_ENTRY_2024->setS99Valid($S99_VALID);
                    $RPT_ENTRY_2024->setTontValid($TONT_VALID);
                    $RPT_ENTRY_2024->setEcommValid($ECOMM_VALID);
                    $RPT_ENTRY_2024->setShmValid($SHM_VALID);
    
                    $RPT_ENTRY_2024->setMontInvalid($MONT_INVALID);
                    $RPT_ENTRY_2024->setCvsInvalid($CVS_INVALID);
                    $RPT_ENTRY_2024->setToftInvalid($TOFT_INVALID);
                    $RPT_ENTRY_2024->setS99Invalid($S99_INVALID);
                    $RPT_ENTRY_2024->setTontInvalid($TONT_INVALID);
                    $RPT_ENTRY_2024->setEcommInvalid($ECOMM_INVALID);
                    $RPT_ENTRY_2024->setShmInvalid($SHM_INVALID);
    
                    $RPT_ENTRY_2024->setMontPending($MONT_RESULT_TOTAL - ($MONT_VALID + $MONT_INVALID));
                    $RPT_ENTRY_2024->setCvsPending($CVS_RESULT_TOTAL - ($CVS_VALID + $CVS_INVALID));
                    $RPT_ENTRY_2024->setToftPending($TOFT_RESULT_TOTAL - ($TOFT_VALID + $TOFT_INVALID));
                    $RPT_ENTRY_2024->setS99Pending($S99_RESULT_TOTAL - ($S99_VALID + $S99_INVALID));
                    $RPT_ENTRY_2024->setTontPending($TONT_RESULT_TOTAL - ($TONT_VALID + $TONT_INVALID));
                    $RPT_ENTRY_2024->setEcommPending($ECOMM_RESULT_TOTAL - ($ECOMM_VALID + $ECOMM_INVALID));
                    $RPT_ENTRY_2024->setShmPending($SHM_RESULT_TOTAL - ($SHM_VALID + $SHM_INVALID));
    
                    // $RPT_ENTRY_2024->setMaleEntry($GENDER_MALE);
                    $RPT_ENTRY_2024->setMaleEntryMont($GENDER_MALE_MONT);
                    $RPT_ENTRY_2024->setMaleEntryCvs($GENDER_MALE_CVS);
                    $RPT_ENTRY_2024->setMaleEntryToft($GENDER_MALE_TOFT);
                    $RPT_ENTRY_2024->setMaleEntryS99($GENDER_MALE_S99);
                    $RPT_ENTRY_2024->setMaleEntryTont($GENDER_MALE_TONT);
                    $RPT_ENTRY_2024->setMaleEntryEcomm($GENDER_MALE_ECOMM);
                    $RPT_ENTRY_2024->setMaleEntryShm($GENDER_MALE_SHM);
                    // $RPT_ENTRY_2024->setFemaleEntry($GENDER_FEMALE);
                    $RPT_ENTRY_2024->setFemaleEntryMont($GENDER_FEMALE_MONT);
                    $RPT_ENTRY_2024->setFemaleEntryCvs($GENDER_FEMALE_CVS);
                    $RPT_ENTRY_2024->setFemaleEntryToft($GENDER_FEMALE_TOFT);
                    $RPT_ENTRY_2024->setFemaleEntryS99($GENDER_FEMALE_S99);
                    $RPT_ENTRY_2024->setFemaleEntryTont($GENDER_FEMALE_TONT);
                    $RPT_ENTRY_2024->setFemaleEntryEcomm($GENDER_FEMALE_ECOMM);
                    $RPT_ENTRY_2024->setFemaleEntryShm($GENDER_FEMALE_SHM);

                    $RPT_ENTRY_2024->setMontAge2125( $AGE_21_25_MONT );
                    $RPT_ENTRY_2024->setMontAge2630( $AGE_26_30_MONT );
                    $RPT_ENTRY_2024->setMontAge3135( $AGE_31_35_MONT );
                    $RPT_ENTRY_2024->setMontAge3640( $AGE_36_40_MONT );
                    $RPT_ENTRY_2024->setMontAge4145( $AGE_41_45_MONT );
                    $RPT_ENTRY_2024->setMontAge4650( $AGE_46_50_MONT );
                    $RPT_ENTRY_2024->setMontAge50Above( $AGE_ABV_50_MONT );

                    $RPT_ENTRY_2024->setCvsAge2125( $AGE_21_25_CVS );
                    $RPT_ENTRY_2024->setCvsAge2630( $AGE_26_30_CVS );
                    $RPT_ENTRY_2024->setCvsAge3135( $AGE_31_35_CVS );
                    $RPT_ENTRY_2024->setCvsAge3640( $AGE_36_40_CVS );
                    $RPT_ENTRY_2024->setCvsAge4145( $AGE_41_45_CVS );
                    $RPT_ENTRY_2024->setCvsAge4650( $AGE_46_50_CVS );
                    $RPT_ENTRY_2024->setCvsAge50Above( $AGE_ABV_50_CVS );

                    $RPT_ENTRY_2024->setToftAge2125( $AGE_21_25_TOFT );
                    $RPT_ENTRY_2024->setToftAge2630( $AGE_26_30_TOFT );
                    $RPT_ENTRY_2024->setToftAge3135( $AGE_31_35_TOFT );
                    $RPT_ENTRY_2024->setToftAge3640( $AGE_36_40_TOFT );
                    $RPT_ENTRY_2024->setToftAge4145( $AGE_41_45_TOFT );
                    $RPT_ENTRY_2024->setToftAge4650( $AGE_46_50_TOFT );
                    $RPT_ENTRY_2024->setToftAge50Above( $AGE_ABV_50_TOFT );

                    $RPT_ENTRY_2024->setShmAge2125( $AGE_21_25_SHM );
                    $RPT_ENTRY_2024->setShmAge2630( $AGE_26_30_SHM );
                    $RPT_ENTRY_2024->setShmAge3135( $AGE_31_35_SHM );
                    $RPT_ENTRY_2024->setShmAge3640( $AGE_36_40_SHM );
                    $RPT_ENTRY_2024->setShmAge4145( $AGE_41_45_SHM );
                    $RPT_ENTRY_2024->setShmAge4650( $AGE_46_50_SHM );
                    $RPT_ENTRY_2024->setShmAge50Above( $AGE_ABV_50_SHM );

                    $RPT_ENTRY_2024->setS99Age2125( $AGE_21_25_S99 );
                    $RPT_ENTRY_2024->setS99Age2630( $AGE_26_30_S99 );
                    $RPT_ENTRY_2024->setS99Age3135( $AGE_31_35_S99 );
                    $RPT_ENTRY_2024->setS99Age3640( $AGE_36_40_S99 );
                    $RPT_ENTRY_2024->setS99Age4145( $AGE_41_45_S99 );
                    $RPT_ENTRY_2024->setS99Age4650( $AGE_46_50_S99 );
                    $RPT_ENTRY_2024->setS99Age50Above( $AGE_ABV_50_S99 );
                    
                    $RPT_ENTRY_2024->setTontAge2125( $AGE_21_25_TONT );
                    $RPT_ENTRY_2024->setTontAge2630( $AGE_26_30_TONT );
                    $RPT_ENTRY_2024->setTontAge3135( $AGE_31_35_TONT );
                    $RPT_ENTRY_2024->setTontAge3640( $AGE_36_40_TONT );
                    $RPT_ENTRY_2024->setTontAge4145( $AGE_41_45_TONT );
                    $RPT_ENTRY_2024->setTontAge4650( $AGE_46_50_TONT );
                    $RPT_ENTRY_2024->setTontAge50Above( $AGE_ABV_50_TONT );
                    
                    $RPT_ENTRY_2024->setEcommAge2125( $AGE_21_25_ECOMM );
                    $RPT_ENTRY_2024->setEcommAge2630( $AGE_26_30_ECOMM );
                    $RPT_ENTRY_2024->setEcommAge3135( $AGE_31_35_ECOMM );
                    $RPT_ENTRY_2024->setEcommAge3640( $AGE_36_40_ECOMM );
                    $RPT_ENTRY_2024->setEcommAge4145( $AGE_41_45_ECOMM );
                    $RPT_ENTRY_2024->setEcommAge4650( $AGE_46_50_ECOMM );
                    $RPT_ENTRY_2024->setEcommAge50Above( $AGE_ABV_50_ECOMM );

                    // Channel-Gender-Age combinations
                    // SHM Channel-Gender-Age combinations
                    $RPT_ENTRY_2024->setShmMaleAge2125( $SHM_MALE_AGE_21_25 );
                    $RPT_ENTRY_2024->setShmMaleAge2630( $SHM_MALE_AGE_26_30 );
                    $RPT_ENTRY_2024->setShmMaleAge3135( $SHM_MALE_AGE_31_35 );
                    $RPT_ENTRY_2024->setShmMaleAge3640( $SHM_MALE_AGE_36_40 );
                    $RPT_ENTRY_2024->setShmMaleAge4145( $SHM_MALE_AGE_41_45 );
                    $RPT_ENTRY_2024->setShmMaleAge4650( $SHM_MALE_AGE_46_50 );
                    $RPT_ENTRY_2024->setShmMaleAge50Above( $SHM_MALE_AGE_50_ABOVE );

                    $RPT_ENTRY_2024->setShmFemaleAge2125( $SHM_FEMALE_AGE_21_25 );
                    $RPT_ENTRY_2024->setShmFemaleAge2630( $SHM_FEMALE_AGE_26_30 );
                    $RPT_ENTRY_2024->setShmFemaleAge3135( $SHM_FEMALE_AGE_31_35 );
                    $RPT_ENTRY_2024->setShmFemaleAge3640( $SHM_FEMALE_AGE_36_40 );
                    $RPT_ENTRY_2024->setShmFemaleAge4145( $SHM_FEMALE_AGE_41_45 );
                    $RPT_ENTRY_2024->setShmFemaleAge4650( $SHM_FEMALE_AGE_46_50 );
                    $RPT_ENTRY_2024->setShmFemaleAge50Above( $SHM_FEMALE_AGE_50_ABOVE );

                    // S99 Channel-Gender-Age combinations
                    $RPT_ENTRY_2024->setS99MaleAge2125( $S99_MALE_AGE_21_25 );
                    $RPT_ENTRY_2024->setS99MaleAge2630( $S99_MALE_AGE_26_30 );
                    $RPT_ENTRY_2024->setS99MaleAge3135( $S99_MALE_AGE_31_35 );
                    $RPT_ENTRY_2024->setS99MaleAge3640( $S99_MALE_AGE_36_40 );
                    $RPT_ENTRY_2024->setS99MaleAge4145( $S99_MALE_AGE_41_45 );
                    $RPT_ENTRY_2024->setS99MaleAge4650( $S99_MALE_AGE_46_50 );
                    $RPT_ENTRY_2024->setS99MaleAge50Above( $S99_MALE_AGE_50_ABOVE );

                    $RPT_ENTRY_2024->setS99FemaleAge2125( $S99_FEMALE_AGE_21_25 );
                    $RPT_ENTRY_2024->setS99FemaleAge2630( $S99_FEMALE_AGE_26_30 );
                    $RPT_ENTRY_2024->setS99FemaleAge3135( $S99_FEMALE_AGE_31_35 );
                    $RPT_ENTRY_2024->setS99FemaleAge3640( $S99_FEMALE_AGE_36_40 );
                    $RPT_ENTRY_2024->setS99FemaleAge4145( $S99_FEMALE_AGE_41_45 );
                    $RPT_ENTRY_2024->setS99FemaleAge4650( $S99_FEMALE_AGE_46_50 );
                    $RPT_ENTRY_2024->setS99FemaleAge50Above( $S99_FEMALE_AGE_50_ABOVE );

                    // MONT Channel-Gender-Age combinations
                    $RPT_ENTRY_2024->setMontMaleAge2125( $MONT_MALE_AGE_21_25 );
                    $RPT_ENTRY_2024->setMontMaleAge2630( $MONT_MALE_AGE_26_30 );
                    $RPT_ENTRY_2024->setMontMaleAge3135( $MONT_MALE_AGE_31_35 );
                    $RPT_ENTRY_2024->setMontMaleAge3640( $MONT_MALE_AGE_36_40 );
                    $RPT_ENTRY_2024->setMontMaleAge4145( $MONT_MALE_AGE_41_45 );
                    $RPT_ENTRY_2024->setMontMaleAge4650( $MONT_MALE_AGE_46_50 );
                    $RPT_ENTRY_2024->setMontMaleAge50Above( $MONT_MALE_AGE_50_ABOVE );

                    $RPT_ENTRY_2024->setMontFemaleAge2125( $MONT_FEMALE_AGE_21_25 );
                    $RPT_ENTRY_2024->setMontFemaleAge2630( $MONT_FEMALE_AGE_26_30 );
                    $RPT_ENTRY_2024->setMontFemaleAge3135( $MONT_FEMALE_AGE_31_35 );
                    $RPT_ENTRY_2024->setMontFemaleAge3640( $MONT_FEMALE_AGE_36_40 );
                    $RPT_ENTRY_2024->setMontFemaleAge4145( $MONT_FEMALE_AGE_41_45 );
                    $RPT_ENTRY_2024->setMontFemaleAge4650( $MONT_FEMALE_AGE_46_50 );
                    $RPT_ENTRY_2024->setMontFemaleAge50Above( $MONT_FEMALE_AGE_50_ABOVE );

                    // TONT Channel-Gender-Age combinations
                    $RPT_ENTRY_2024->setTontMaleAge2125( $TONT_MALE_AGE_21_25 );
                    $RPT_ENTRY_2024->setTontMaleAge2630( $TONT_MALE_AGE_26_30 );
                    $RPT_ENTRY_2024->setTontMaleAge3135( $TONT_MALE_AGE_31_35 );
                    $RPT_ENTRY_2024->setTontMaleAge3640( $TONT_MALE_AGE_36_40 );
                    $RPT_ENTRY_2024->setTontMaleAge4145( $TONT_MALE_AGE_41_45 );
                    $RPT_ENTRY_2024->setTontMaleAge4650( $TONT_MALE_AGE_46_50 );
                    $RPT_ENTRY_2024->setTontMaleAge50Above( $TONT_MALE_AGE_50_ABOVE );

                    $RPT_ENTRY_2024->setTontFemaleAge2125( $TONT_FEMALE_AGE_21_25 );
                    $RPT_ENTRY_2024->setTontFemaleAge2630( $TONT_FEMALE_AGE_26_30 );
                    $RPT_ENTRY_2024->setTontFemaleAge3135( $TONT_FEMALE_AGE_31_35 );
                    $RPT_ENTRY_2024->setTontFemaleAge3640( $TONT_FEMALE_AGE_36_40 );
                    $RPT_ENTRY_2024->setTontFemaleAge4145( $TONT_FEMALE_AGE_41_45 );
                    $RPT_ENTRY_2024->setTontFemaleAge4650( $TONT_FEMALE_AGE_46_50 );
                    $RPT_ENTRY_2024->setTontFemaleAge50Above( $TONT_FEMALE_AGE_50_ABOVE );

                    // CVS Channel-Gender-Age combinations
                    $RPT_ENTRY_2024->setCvsMaleAge2125( $CVS_MALE_AGE_21_25 );
                    $RPT_ENTRY_2024->setCvsMaleAge2630( $CVS_MALE_AGE_26_30 );
                    $RPT_ENTRY_2024->setCvsMaleAge3135( $CVS_MALE_AGE_31_35 );
                    $RPT_ENTRY_2024->setCvsMaleAge3640( $CVS_MALE_AGE_36_40 );
                    $RPT_ENTRY_2024->setCvsMaleAge4145( $CVS_MALE_AGE_41_45 );
                    $RPT_ENTRY_2024->setCvsMaleAge4650( $CVS_MALE_AGE_46_50 );
                    $RPT_ENTRY_2024->setCvsMaleAge50Above( $CVS_MALE_AGE_50_ABOVE );

                    $RPT_ENTRY_2024->setCvsFemaleAge2125( $CVS_FEMALE_AGE_21_25 );
                    $RPT_ENTRY_2024->setCvsFemaleAge2630( $CVS_FEMALE_AGE_26_30 );
                    $RPT_ENTRY_2024->setCvsFemaleAge3135( $CVS_FEMALE_AGE_31_35 );
                    $RPT_ENTRY_2024->setCvsFemaleAge3640( $CVS_FEMALE_AGE_36_40 );
                    $RPT_ENTRY_2024->setCvsFemaleAge4145( $CVS_FEMALE_AGE_41_45 );
                    $RPT_ENTRY_2024->setCvsFemaleAge4650( $CVS_FEMALE_AGE_46_50 );
                    $RPT_ENTRY_2024->setCvsFemaleAge50Above( $CVS_FEMALE_AGE_50_ABOVE );

                    // TOFT Channel-Gender-Age combinations
                    $RPT_ENTRY_2024->setToftMaleAge2125( $TOFT_MALE_AGE_21_25 );
                    $RPT_ENTRY_2024->setToftMaleAge2630( $TOFT_MALE_AGE_26_30 );
                    $RPT_ENTRY_2024->setToftMaleAge3135( $TOFT_MALE_AGE_31_35 );
                    $RPT_ENTRY_2024->setToftMaleAge3640( $TOFT_MALE_AGE_36_40 );
                    $RPT_ENTRY_2024->setToftMaleAge4145( $TOFT_MALE_AGE_41_45 );
                    $RPT_ENTRY_2024->setToftMaleAge4650( $TOFT_MALE_AGE_46_50 );
                    $RPT_ENTRY_2024->setToftMaleAge50Above( $TOFT_MALE_AGE_50_ABOVE );

                    $RPT_ENTRY_2024->setToftFemaleAge2125( $TOFT_FEMALE_AGE_21_25 );
                    $RPT_ENTRY_2024->setToftFemaleAge2630( $TOFT_FEMALE_AGE_26_30 );
                    $RPT_ENTRY_2024->setToftFemaleAge3135( $TOFT_FEMALE_AGE_31_35 );
                    $RPT_ENTRY_2024->setToftFemaleAge3640( $TOFT_FEMALE_AGE_36_40 );
                    $RPT_ENTRY_2024->setToftFemaleAge4145( $TOFT_FEMALE_AGE_41_45 );
                    $RPT_ENTRY_2024->setToftFemaleAge4650( $TOFT_FEMALE_AGE_46_50 );
                    $RPT_ENTRY_2024->setToftFemaleAge50Above( $TOFT_FEMALE_AGE_50_ABOVE );

                    // ECOMM Channel-Gender-Age combinations
                    $RPT_ENTRY_2024->setEcommMaleAge2125( $ECOMM_MALE_AGE_21_25 );
                    $RPT_ENTRY_2024->setEcommMaleAge2630( $ECOMM_MALE_AGE_26_30 );
                    $RPT_ENTRY_2024->setEcommMaleAge3135( $ECOMM_MALE_AGE_31_35 );
                    $RPT_ENTRY_2024->setEcommMaleAge3640( $ECOMM_MALE_AGE_36_40 );
                    $RPT_ENTRY_2024->setEcommMaleAge4145( $ECOMM_MALE_AGE_41_45 );
                    $RPT_ENTRY_2024->setEcommMaleAge4650( $ECOMM_MALE_AGE_46_50 );
                    $RPT_ENTRY_2024->setEcommMaleAge50Above( $ECOMM_MALE_AGE_50_ABOVE );

                    $RPT_ENTRY_2024->setEcommFemaleAge2125( $ECOMM_FEMALE_AGE_21_25 );
                    $RPT_ENTRY_2024->setEcommFemaleAge2630( $ECOMM_FEMALE_AGE_26_30 );
                    $RPT_ENTRY_2024->setEcommFemaleAge3135( $ECOMM_FEMALE_AGE_31_35 );
                    $RPT_ENTRY_2024->setEcommFemaleAge3640( $ECOMM_FEMALE_AGE_36_40 );
                    $RPT_ENTRY_2024->setEcommFemaleAge4145( $ECOMM_FEMALE_AGE_41_45 );
                    $RPT_ENTRY_2024->setEcommFemaleAge4650( $ECOMM_FEMALE_AGE_46_50 );
                    $RPT_ENTRY_2024->setEcommFemaleAge50Above( $ECOMM_FEMALE_AGE_50_ABOVE );

                    $RPT_ENTRY_2024->setInvMontTotal( $INV_MONT_TOTAL );
                    $RPT_ENTRY_2024->setInvCvsTotal( $INV_CVS_TOTAL );
                    $RPT_ENTRY_2024->setInvToftTotal( $INV_TOFT_TOTAL );
                    $RPT_ENTRY_2024->setInvS99Total( $INV_S99_TOTAL );
                    $RPT_ENTRY_2024->setInvShmTotal( $INV_SHM_TOTAL );
                    $RPT_ENTRY_2024->setInvTontTotal( $INV_TONT_TOTAL );
                    $RPT_ENTRY_2024->setInvEcommTotal( $INV_ECOMM_TOTAL );
                    
                    $RPT_ENTRY_2024->setInvMontRedeem( $INV_MONT_REDEEM );
                    $RPT_ENTRY_2024->setInvCvsRedeem( $INV_CVS_REDEEM );
                    $RPT_ENTRY_2024->setInvToftRedeem( $INV_TOFT_REDEEM );
                    $RPT_ENTRY_2024->setInvS99Redeem( $INV_S99_REDEEM );
                    $RPT_ENTRY_2024->setInvShmRedeem( $INV_SHM_REDEEM );
                    $RPT_ENTRY_2024->setInvTontRedeem( $INV_TONT_REDEEM );
                    $RPT_ENTRY_2024->setInvEcommRedeem( $INV_ECOMM_REDEEM );

                    $RPT_ENTRY_2024->setInvMontLeft( $INV_MONT_TOTAL - $INV_MONT_REDEEM );
                    $RPT_ENTRY_2024->setInvCvsLeft( $INV_CVS_TOTAL - $INV_CVS_REDEEM );
                    $RPT_ENTRY_2024->setInvToftLeft( $INV_TOFT_TOTAL - $INV_TOFT_REDEEM );
                    $RPT_ENTRY_2024->setInvS99Left( $INV_S99_TOTAL - $INV_S99_REDEEM );
                    $RPT_ENTRY_2024->setInvShmLeft( $INV_SHM_TOTAL - $INV_SHM_REDEEM );
                    $RPT_ENTRY_2024->setInvTontLeft( $INV_TONT_TOTAL - $INV_TONT_REDEEM );
                    $RPT_ENTRY_2024->setInvEcommLeft( $INV_ECOMM_TOTAL - $INV_ECOMM_REDEEM );
                    
                    
                    $RPT_ENTRY_2024->setDelMontProcess( $DEL_MONT_PROCESS );
                    $RPT_ENTRY_2024->setDelMontOut( $DEL_MONT_OUT );
                    // $RPT_ENTRY_2024->setDelMontAssigned( $DEL_MONT_ADDRESS );

                    $RPT_ENTRY_2024->setDelCvsProcess( $DEL_CVS_PROCESS );
                    $RPT_ENTRY_2024->setDelCvsOut( $DEL_CVS_OUT );
                    // $RPT_ENTRY_2024->setDelCvsAssigned( $DEL_CVS_ADDRESS );

                    $RPT_ENTRY_2024->setDelToftProcess( $DEL_TOFT_PROCESS );
                    $RPT_ENTRY_2024->setDelToftOut( $DEL_TOFT_OUT );
                    // $RPT_ENTRY_2024->setDelToftAssigned( $DEL_TOFT_ADDRESS );

                    $RPT_ENTRY_2024->setDelS99Process( $DEL_S99_PROCESS );
                    $RPT_ENTRY_2024->setDelS99Out( $DEL_S99_OUT );
                    // $RPT_ENTRY_2024->setDelS99Assigned( $DEL_S99_ADDRESS );

                    $RPT_ENTRY_2024->setDelShmProcess( $DEL_SHM_PROCESS );
                    $RPT_ENTRY_2024->setDelShmOut( $DEL_SHM_OUT );
                    // $RPT_ENTRY_2024->setDelShmAssigned( $DEL_SHM_ADDRESS );

                    $RPT_ENTRY_2024->setDelTontProcess( $DEL_TONT_PROCESS );
                    $RPT_ENTRY_2024->setDelTontOut( $DEL_TONT_OUT );
                    // $RPT_ENTRY_2024->setDelTontAssigned( $DEL_TONT_ADDRESS );

                    $RPT_ENTRY_2024->setDelEcommProcess( $DEL_ECOMM_PROCESS );
                    $RPT_ENTRY_2024->setDelEcommOut( $DEL_ECOMM_OUT );
                    // $RPT_ENTRY_2024->setDelEcommAssigned( $DEL_ECOMM_ADDRESS );

                    /* BATCH 3 */
                    $RPT_ENTRY_2024->setRejectReason1Mont( $REJECT_REASON1_MONT );
                    $RPT_ENTRY_2024->setRejectReason2Mont( $REJECT_REASON2_MONT );
                    $RPT_ENTRY_2024->setRejectReason3Mont( $REJECT_REASON3_MONT );
                    $RPT_ENTRY_2024->setRejectReason4Mont( $REJECT_REASON4_MONT );
                    $RPT_ENTRY_2024->setRejectReason5Mont( $REJECT_REASON5_MONT );
                    $RPT_ENTRY_2024->setRejectReason6Mont( $REJECT_REASON6_MONT );
                    $RPT_ENTRY_2024->setRejectReason7Mont( $REJECT_REASON7_MONT );
                    $RPT_ENTRY_2024->setRejectReason8Mont( $REJECT_REASON8_MONT );

                    $RPT_ENTRY_2024->setRejectReason1Cvs( $REJECT_REASON1_CVS );
                    $RPT_ENTRY_2024->setRejectReason2Cvs( $REJECT_REASON2_CVS );
                    $RPT_ENTRY_2024->setRejectReason3Cvs( $REJECT_REASON3_CVS );
                    $RPT_ENTRY_2024->setRejectReason4Cvs( $REJECT_REASON4_CVS );
                    $RPT_ENTRY_2024->setRejectReason5Cvs( $REJECT_REASON5_CVS );
                    $RPT_ENTRY_2024->setRejectReason6Cvs( $REJECT_REASON6_CVS );
                    $RPT_ENTRY_2024->setRejectReason7Cvs( $REJECT_REASON7_CVS );
                    $RPT_ENTRY_2024->setRejectReason8Cvs( $REJECT_REASON8_CVS );

                    $RPT_ENTRY_2024->setRejectReason1Toft( $REJECT_REASON1_TOFT );
                    $RPT_ENTRY_2024->setRejectReason2Toft( $REJECT_REASON2_TOFT );
                    $RPT_ENTRY_2024->setRejectReason3Toft( $REJECT_REASON3_TOFT );
                    $RPT_ENTRY_2024->setRejectReason4Toft( $REJECT_REASON4_TOFT );
                    $RPT_ENTRY_2024->setRejectReason5Toft( $REJECT_REASON5_TOFT );
                    $RPT_ENTRY_2024->setRejectReason6Toft( $REJECT_REASON6_TOFT );
                    $RPT_ENTRY_2024->setRejectReason7Toft( $REJECT_REASON7_TOFT );
                    $RPT_ENTRY_2024->setRejectReason8Toft( $REJECT_REASON8_TOFT );

                    $RPT_ENTRY_2024->setRejectReason1S99( $REJECT_REASON1_S99 );
                    $RPT_ENTRY_2024->setRejectReason2S99( $REJECT_REASON2_S99 );
                    $RPT_ENTRY_2024->setRejectReason3S99( $REJECT_REASON3_S99 );
                    $RPT_ENTRY_2024->setRejectReason4S99( $REJECT_REASON4_S99 );
                    $RPT_ENTRY_2024->setRejectReason5S99( $REJECT_REASON5_S99 );
                    $RPT_ENTRY_2024->setRejectReason6S99( $REJECT_REASON6_S99 );
                    $RPT_ENTRY_2024->setRejectReason7S99( $REJECT_REASON7_S99 );
                    $RPT_ENTRY_2024->setRejectReason8S99( $REJECT_REASON8_S99 );

                    $RPT_ENTRY_2024->setRejectReason1Shm( $REJECT_REASON1_SHM );
                    $RPT_ENTRY_2024->setRejectReason2Shm( $REJECT_REASON2_SHM );
                    $RPT_ENTRY_2024->setRejectReason3Shm( $REJECT_REASON3_SHM );
                    $RPT_ENTRY_2024->setRejectReason4Shm( $REJECT_REASON4_SHM );
                    $RPT_ENTRY_2024->setRejectReason5Shm( $REJECT_REASON5_SHM );
                    $RPT_ENTRY_2024->setRejectReason6Shm( $REJECT_REASON6_SHM );
                    $RPT_ENTRY_2024->setRejectReason7Shm( $REJECT_REASON7_SHM );
                    $RPT_ENTRY_2024->setRejectReason8Shm( $REJECT_REASON8_SHM );

                    $RPT_ENTRY_2024->setRejectReason1Tont( $REJECT_REASON1_TONT );
                    $RPT_ENTRY_2024->setRejectReason2Tont( $REJECT_REASON2_TONT );
                    $RPT_ENTRY_2024->setRejectReason3Tont( $REJECT_REASON3_TONT );
                    $RPT_ENTRY_2024->setRejectReason4Tont( $REJECT_REASON4_TONT );
                    $RPT_ENTRY_2024->setRejectReason5Tont( $REJECT_REASON5_TONT );
                    $RPT_ENTRY_2024->setRejectReason6Tont( $REJECT_REASON6_TONT );
                    $RPT_ENTRY_2024->setRejectReason7Tont( $REJECT_REASON7_TONT );
                    $RPT_ENTRY_2024->setRejectReason8Tont( $REJECT_REASON8_TONT );

                    $RPT_ENTRY_2024->setRejectReason1Ecomm( $REJECT_REASON1_ECOMM );
                    $RPT_ENTRY_2024->setRejectReason2Ecomm( $REJECT_REASON2_ECOMM );
                    $RPT_ENTRY_2024->setRejectReason3Ecomm( $REJECT_REASON3_ECOMM );
                    $RPT_ENTRY_2024->setRejectReason4Ecomm( $REJECT_REASON4_ECOMM );
                    $RPT_ENTRY_2024->setRejectReason5Ecomm( $REJECT_REASON5_ECOMM );
                    $RPT_ENTRY_2024->setRejectReason6Ecomm( $REJECT_REASON6_ECOMM );
                    $RPT_ENTRY_2024->setRejectReason7Ecomm( $REJECT_REASON7_ECOMM );
                    $RPT_ENTRY_2024->setRejectReason8Ecomm( $REJECT_REASON8_ECOMM );
                    /* Batch 3 */
                    
                    $this->manager->persist($RPT_ENTRY_2024);     
                    $this->manager->flush();
    
                    $output->writeln('');            
                    $output->writeln('<fg=bright-blue>Updating Week Number => '.$reqWeek);
                    $output->writeln('');
    
                } else {
                    $NEW_RPT_ENTRY = new ReportEntry();
                    $NEW_RPT_ENTRY->setWeekNumber($reqWeek);
                    // $NEW_RPT_ENTRY->setLastUpdated(new \DateTime);
                    $NEW_RPT_ENTRY->setMontTotal($MONT_RESULT_TOTAL);
                    $NEW_RPT_ENTRY->setCvsTotal($CVS_RESULT_TOTAL);
                    $NEW_RPT_ENTRY->setToftTotal($TOFT_RESULT_TOTAL);
                    $NEW_RPT_ENTRY->setS99Total($S99_RESULT_TOTAL);
                    $NEW_RPT_ENTRY->setTontTotal($TONT_RESULT_TOTAL);
                    $NEW_RPT_ENTRY->setEcommTotal($ECOMM_RESULT_TOTAL);
                    $NEW_RPT_ENTRY->setShmTotal($SHM_RESULT_TOTAL);
    
                    $NEW_RPT_ENTRY->setMontValid($MONT_VALID);
                    $NEW_RPT_ENTRY->setCvsValid($CVS_VALID);
                    $NEW_RPT_ENTRY->setToftValid($TOFT_VALID);
                    $NEW_RPT_ENTRY->setS99Valid($S99_VALID);
                    $NEW_RPT_ENTRY->setTontValid($TONT_VALID);
                    $NEW_RPT_ENTRY->setEcommValid($ECOMM_VALID);
                    $NEW_RPT_ENTRY->setShmValid($SHM_VALID);
    
                    $NEW_RPT_ENTRY->setMontInvalid($MONT_INVALID);
                    $NEW_RPT_ENTRY->setCvsInvalid($CVS_INVALID);
                    $NEW_RPT_ENTRY->setToftInvalid($TOFT_INVALID);
                    $NEW_RPT_ENTRY->setS99Invalid($S99_INVALID);
                    $NEW_RPT_ENTRY->setTontInvalid($TONT_INVALID);
                    $NEW_RPT_ENTRY->setEcommInvalid($ECOMM_INVALID);
                    $NEW_RPT_ENTRY->setShmInvalid($SHM_INVALID);
    
                    $NEW_RPT_ENTRY->setMontPending($MONT_RESULT_TOTAL - ($MONT_VALID + $MONT_INVALID));
                    $NEW_RPT_ENTRY->setCvsPending($CVS_RESULT_TOTAL - ($CVS_VALID + $CVS_INVALID));
                    $NEW_RPT_ENTRY->setToftPending($TOFT_RESULT_TOTAL - ($TOFT_VALID + $TOFT_INVALID));
                    $NEW_RPT_ENTRY->setS99Pending($S99_RESULT_TOTAL - ($S99_VALID + $S99_INVALID));
                    $NEW_RPT_ENTRY->setTontPending($TONT_RESULT_TOTAL - ($TONT_VALID + $TONT_INVALID));
                    $NEW_RPT_ENTRY->setEcommPending($ECOMM_RESULT_TOTAL - ($ECOMM_VALID + $ECOMM_INVALID));
                    $NEW_RPT_ENTRY->setShmPending($SHM_RESULT_TOTAL - ($SHM_VALID + $SHM_INVALID));
    
                    // $NEW_RPT_ENTRY->setMaleEntry($GENDER_MALE);
                    $NEW_RPT_ENTRY->setMaleEntryMont($GENDER_MALE_MONT);
                    $NEW_RPT_ENTRY->setMaleEntryCvs($GENDER_MALE_CVS);
                    $NEW_RPT_ENTRY->setMaleEntryToft($GENDER_MALE_TOFT);
                    $NEW_RPT_ENTRY->setMaleEntryS99($GENDER_MALE_S99);
                    $NEW_RPT_ENTRY->setMaleEntryTont($GENDER_MALE_TONT);
                    $NEW_RPT_ENTRY->setMaleEntryEcomm($GENDER_MALE_ECOMM);
                    $NEW_RPT_ENTRY->setMaleEntryShm($GENDER_MALE_SHM);
                    // $NEW_RPT_ENTRY->setFemaleEntry($GENDER_FEMALE);
                    $NEW_RPT_ENTRY->setFemaleEntryMont($GENDER_FEMALE_MONT);
                    $NEW_RPT_ENTRY->setFemaleEntryCvs($GENDER_FEMALE_CVS);
                    $NEW_RPT_ENTRY->setFemaleEntryToft($GENDER_FEMALE_TOFT);
                    $NEW_RPT_ENTRY->setFemaleEntryS99($GENDER_FEMALE_S99);
                    $NEW_RPT_ENTRY->setFemaleEntryTont($GENDER_FEMALE_TONT);
                    $NEW_RPT_ENTRY->setFemaleEntryEcomm($GENDER_FEMALE_ECOMM);
                    $NEW_RPT_ENTRY->setFemaleEntryShm($GENDER_FEMALE_SHM);

                    $NEW_RPT_ENTRY->setMontAge2125( $AGE_21_25_MONT );
                    $NEW_RPT_ENTRY->setMontAge2630( $AGE_26_30_MONT );
                    $NEW_RPT_ENTRY->setMontAge3135( $AGE_31_35_MONT );
                    $NEW_RPT_ENTRY->setMontAge3640( $AGE_36_40_MONT );
                    $NEW_RPT_ENTRY->setMontAge4145( $AGE_41_45_MONT );
                    $NEW_RPT_ENTRY->setMontAge4650( $AGE_46_50_MONT );
                    $NEW_RPT_ENTRY->setMontAge50Above( $AGE_ABV_50_MONT );

                    $NEW_RPT_ENTRY->setCvsAge2125( $AGE_21_25_CVS );
                    $NEW_RPT_ENTRY->setCvsAge2630( $AGE_26_30_CVS );
                    $NEW_RPT_ENTRY->setCvsAge3135( $AGE_31_35_CVS );
                    $NEW_RPT_ENTRY->setCvsAge3640( $AGE_36_40_CVS );
                    $NEW_RPT_ENTRY->setCvsAge4145( $AGE_41_45_CVS );
                    $NEW_RPT_ENTRY->setCvsAge4650( $AGE_46_50_CVS );
                    $NEW_RPT_ENTRY->setCvsAge50Above( $AGE_ABV_50_CVS );

                    $NEW_RPT_ENTRY->setToftAge2125( $AGE_21_25_TOFT );
                    $NEW_RPT_ENTRY->setToftAge2630( $AGE_26_30_TOFT );
                    $NEW_RPT_ENTRY->setToftAge3135( $AGE_31_35_TOFT );
                    $NEW_RPT_ENTRY->setToftAge3640( $AGE_36_40_TOFT );
                    $NEW_RPT_ENTRY->setToftAge4145( $AGE_41_45_TOFT );
                    $NEW_RPT_ENTRY->setToftAge4650( $AGE_46_50_TOFT );
                    $NEW_RPT_ENTRY->setToftAge50Above( $AGE_ABV_50_TOFT );

                    $NEW_RPT_ENTRY->setShmAge2125( $AGE_21_25_SHM );
                    $NEW_RPT_ENTRY->setShmAge2630( $AGE_26_30_SHM );
                    $NEW_RPT_ENTRY->setShmAge3135( $AGE_31_35_SHM );
                    $NEW_RPT_ENTRY->setShmAge3640( $AGE_36_40_SHM );
                    $NEW_RPT_ENTRY->setShmAge4145( $AGE_41_45_SHM );
                    $NEW_RPT_ENTRY->setShmAge4650( $AGE_46_50_SHM );
                    $NEW_RPT_ENTRY->setShmAge50Above( $AGE_ABV_50_SHM );

                    $NEW_RPT_ENTRY->setS99Age2125( $AGE_21_25_S99 );
                    $NEW_RPT_ENTRY->setS99Age2630( $AGE_26_30_S99 );
                    $NEW_RPT_ENTRY->setS99Age3135( $AGE_31_35_S99 );
                    $NEW_RPT_ENTRY->setS99Age3640( $AGE_36_40_S99 );
                    $NEW_RPT_ENTRY->setS99Age4145( $AGE_41_45_S99 );
                    $NEW_RPT_ENTRY->setS99Age4650( $AGE_46_50_S99 );
                    $NEW_RPT_ENTRY->setS99Age50Above( $AGE_ABV_50_S99 );

                    $NEW_RPT_ENTRY->setTontAge2125( $AGE_21_25_TONT );
                    $NEW_RPT_ENTRY->setTontAge2630( $AGE_26_30_TONT );
                    $NEW_RPT_ENTRY->setTontAge3135( $AGE_31_35_TONT );
                    $NEW_RPT_ENTRY->setTontAge3640( $AGE_36_40_TONT );
                    $NEW_RPT_ENTRY->setTontAge4145( $AGE_41_45_TONT );
                    $NEW_RPT_ENTRY->setTontAge4650( $AGE_46_50_TONT );
                    $NEW_RPT_ENTRY->setTontAge50Above( $AGE_ABV_50_TONT );

                    $NEW_RPT_ENTRY->setEcommAge2125( $AGE_21_25_ECOMM );
                    $NEW_RPT_ENTRY->setEcommAge2630( $AGE_26_30_ECOMM );
                    $NEW_RPT_ENTRY->setEcommAge3135( $AGE_31_35_ECOMM );
                    $NEW_RPT_ENTRY->setEcommAge3640( $AGE_36_40_ECOMM );
                    $NEW_RPT_ENTRY->setEcommAge4145( $AGE_41_45_ECOMM );
                    $NEW_RPT_ENTRY->setEcommAge4650( $AGE_46_50_ECOMM );
                    $NEW_RPT_ENTRY->setEcommAge50Above( $AGE_ABV_50_ECOMM );

                    // Channel-Gender-Age combinations
                    // SHM Channel-Gender-Age combinations
                    $NEW_RPT_ENTRY->setShmMaleAge2125( $SHM_MALE_AGE_21_25 );
                    $NEW_RPT_ENTRY->setShmMaleAge2630( $SHM_MALE_AGE_26_30 );
                    $NEW_RPT_ENTRY->setShmMaleAge3135( $SHM_MALE_AGE_31_35 );
                    $NEW_RPT_ENTRY->setShmMaleAge3640( $SHM_MALE_AGE_36_40 );
                    $NEW_RPT_ENTRY->setShmMaleAge4145( $SHM_MALE_AGE_41_45 );
                    $NEW_RPT_ENTRY->setShmMaleAge4650( $SHM_MALE_AGE_46_50 );
                    $NEW_RPT_ENTRY->setShmMaleAge50Above( $SHM_MALE_AGE_50_ABOVE );

                    $NEW_RPT_ENTRY->setShmFemaleAge2125( $SHM_FEMALE_AGE_21_25 );
                    $NEW_RPT_ENTRY->setShmFemaleAge2630( $SHM_FEMALE_AGE_26_30 );
                    $NEW_RPT_ENTRY->setShmFemaleAge3135( $SHM_FEMALE_AGE_31_35 );
                    $NEW_RPT_ENTRY->setShmFemaleAge3640( $SHM_FEMALE_AGE_36_40 );
                    $NEW_RPT_ENTRY->setShmFemaleAge4145( $SHM_FEMALE_AGE_41_45 );
                    $NEW_RPT_ENTRY->setShmFemaleAge4650( $SHM_FEMALE_AGE_46_50 );
                    $NEW_RPT_ENTRY->setShmFemaleAge50Above( $SHM_FEMALE_AGE_50_ABOVE );

                    // S99 Channel-Gender-Age combinations
                    $NEW_RPT_ENTRY->setS99MaleAge2125( $S99_MALE_AGE_21_25 );
                    $NEW_RPT_ENTRY->setS99MaleAge2630( $S99_MALE_AGE_26_30 );
                    $NEW_RPT_ENTRY->setS99MaleAge3135( $S99_MALE_AGE_31_35 );
                    $NEW_RPT_ENTRY->setS99MaleAge3640( $S99_MALE_AGE_36_40 );
                    $NEW_RPT_ENTRY->setS99MaleAge4145( $S99_MALE_AGE_41_45 );
                    $NEW_RPT_ENTRY->setS99MaleAge4650( $S99_MALE_AGE_46_50 );
                    $NEW_RPT_ENTRY->setS99MaleAge50Above( $S99_MALE_AGE_50_ABOVE );

                    $NEW_RPT_ENTRY->setS99FemaleAge2125( $S99_FEMALE_AGE_21_25 );
                    $NEW_RPT_ENTRY->setS99FemaleAge2630( $S99_FEMALE_AGE_26_30 );
                    $NEW_RPT_ENTRY->setS99FemaleAge3135( $S99_FEMALE_AGE_31_35 );
                    $NEW_RPT_ENTRY->setS99FemaleAge3640( $S99_FEMALE_AGE_36_40 );
                    $NEW_RPT_ENTRY->setS99FemaleAge4145( $S99_FEMALE_AGE_41_45 );
                    $NEW_RPT_ENTRY->setS99FemaleAge4650( $S99_FEMALE_AGE_46_50 );
                    $NEW_RPT_ENTRY->setS99FemaleAge50Above( $S99_FEMALE_AGE_50_ABOVE );

                    // MONT Channel-Gender-Age combinations
                    $NEW_RPT_ENTRY->setMontMaleAge2125( $MONT_MALE_AGE_21_25 );
                    $NEW_RPT_ENTRY->setMontMaleAge2630( $MONT_MALE_AGE_26_30 );
                    $NEW_RPT_ENTRY->setMontMaleAge3135( $MONT_MALE_AGE_31_35 );
                    $NEW_RPT_ENTRY->setMontMaleAge3640( $MONT_MALE_AGE_36_40 );
                    $NEW_RPT_ENTRY->setMontMaleAge4145( $MONT_MALE_AGE_41_45 );
                    $NEW_RPT_ENTRY->setMontMaleAge4650( $MONT_MALE_AGE_46_50 );
                    $NEW_RPT_ENTRY->setMontMaleAge50Above( $MONT_MALE_AGE_50_ABOVE );

                    $NEW_RPT_ENTRY->setMontFemaleAge2125( $MONT_FEMALE_AGE_21_25 );
                    $NEW_RPT_ENTRY->setMontFemaleAge2630( $MONT_FEMALE_AGE_26_30 );
                    $NEW_RPT_ENTRY->setMontFemaleAge3135( $MONT_FEMALE_AGE_31_35 );
                    $NEW_RPT_ENTRY->setMontFemaleAge3640( $MONT_FEMALE_AGE_36_40 );
                    $NEW_RPT_ENTRY->setMontFemaleAge4145( $MONT_FEMALE_AGE_41_45 );
                    $NEW_RPT_ENTRY->setMontFemaleAge4650( $MONT_FEMALE_AGE_46_50 );
                    $NEW_RPT_ENTRY->setMontFemaleAge50Above( $MONT_FEMALE_AGE_50_ABOVE );

                    // TONT Channel-Gender-Age combinations
                    $NEW_RPT_ENTRY->setTontMaleAge2125( $TONT_MALE_AGE_21_25 );
                    $NEW_RPT_ENTRY->setTontMaleAge2630( $TONT_MALE_AGE_26_30 );
                    $NEW_RPT_ENTRY->setTontMaleAge3135( $TONT_MALE_AGE_31_35 );
                    $NEW_RPT_ENTRY->setTontMaleAge3640( $TONT_MALE_AGE_36_40 );
                    $NEW_RPT_ENTRY->setTontMaleAge4145( $TONT_MALE_AGE_41_45 );
                    $NEW_RPT_ENTRY->setTontMaleAge4650( $TONT_MALE_AGE_46_50 );
                    $NEW_RPT_ENTRY->setTontMaleAge50Above( $TONT_MALE_AGE_50_ABOVE );

                    $NEW_RPT_ENTRY->setTontFemaleAge2125( $TONT_FEMALE_AGE_21_25 );
                    $NEW_RPT_ENTRY->setTontFemaleAge2630( $TONT_FEMALE_AGE_26_30 );
                    $NEW_RPT_ENTRY->setTontFemaleAge3135( $TONT_FEMALE_AGE_31_35 );
                    $NEW_RPT_ENTRY->setTontFemaleAge3640( $TONT_FEMALE_AGE_36_40 );
                    $NEW_RPT_ENTRY->setTontFemaleAge4145( $TONT_FEMALE_AGE_41_45 );
                    $NEW_RPT_ENTRY->setTontFemaleAge4650( $TONT_FEMALE_AGE_46_50 );
                    $NEW_RPT_ENTRY->setTontFemaleAge50Above( $TONT_FEMALE_AGE_50_ABOVE );

                    // CVS Channel-Gender-Age combinations
                    $NEW_RPT_ENTRY->setCvsMaleAge2125( $CVS_MALE_AGE_21_25 );
                    $NEW_RPT_ENTRY->setCvsMaleAge2630( $CVS_MALE_AGE_26_30 );
                    $NEW_RPT_ENTRY->setCvsMaleAge3135( $CVS_MALE_AGE_31_35 );
                    $NEW_RPT_ENTRY->setCvsMaleAge3640( $CVS_MALE_AGE_36_40 );
                    $NEW_RPT_ENTRY->setCvsMaleAge4145( $CVS_MALE_AGE_41_45 );
                    $NEW_RPT_ENTRY->setCvsMaleAge4650( $CVS_MALE_AGE_46_50 );
                    $NEW_RPT_ENTRY->setCvsMaleAge50Above( $CVS_MALE_AGE_50_ABOVE );

                    $NEW_RPT_ENTRY->setCvsFemaleAge2125( $CVS_FEMALE_AGE_21_25 );
                    $NEW_RPT_ENTRY->setCvsFemaleAge2630( $CVS_FEMALE_AGE_26_30 );
                    $NEW_RPT_ENTRY->setCvsFemaleAge3135( $CVS_FEMALE_AGE_31_35 );
                    $NEW_RPT_ENTRY->setCvsFemaleAge3640( $CVS_FEMALE_AGE_36_40 );
                    $NEW_RPT_ENTRY->setCvsFemaleAge4145( $CVS_FEMALE_AGE_41_45 );
                    $NEW_RPT_ENTRY->setCvsFemaleAge4650( $CVS_FEMALE_AGE_46_50 );
                    $NEW_RPT_ENTRY->setCvsFemaleAge50Above( $CVS_FEMALE_AGE_50_ABOVE );

                    // TOFT Channel-Gender-Age combinations
                    $NEW_RPT_ENTRY->setToftMaleAge2125( $TOFT_MALE_AGE_21_25 );
                    $NEW_RPT_ENTRY->setToftMaleAge2630( $TOFT_MALE_AGE_26_30 );
                    $NEW_RPT_ENTRY->setToftMaleAge3135( $TOFT_MALE_AGE_31_35 );
                    $NEW_RPT_ENTRY->setToftMaleAge3640( $TOFT_MALE_AGE_36_40 );
                    $NEW_RPT_ENTRY->setToftMaleAge4145( $TOFT_MALE_AGE_41_45 );
                    $NEW_RPT_ENTRY->setToftMaleAge4650( $TOFT_MALE_AGE_46_50 );
                    $NEW_RPT_ENTRY->setToftMaleAge50Above( $TOFT_MALE_AGE_50_ABOVE );

                    $NEW_RPT_ENTRY->setToftFemaleAge2125( $TOFT_FEMALE_AGE_21_25 );
                    $NEW_RPT_ENTRY->setToftFemaleAge2630( $TOFT_FEMALE_AGE_26_30 );
                    $NEW_RPT_ENTRY->setToftFemaleAge3135( $TOFT_FEMALE_AGE_31_35 );
                    $NEW_RPT_ENTRY->setToftFemaleAge3640( $TOFT_FEMALE_AGE_36_40 );
                    $NEW_RPT_ENTRY->setToftFemaleAge4145( $TOFT_FEMALE_AGE_41_45 );
                    $NEW_RPT_ENTRY->setToftFemaleAge4650( $TOFT_FEMALE_AGE_46_50 );
                    $NEW_RPT_ENTRY->setToftFemaleAge50Above( $TOFT_FEMALE_AGE_50_ABOVE );

                    // ECOMM Channel-Gender-Age combinations
                    $NEW_RPT_ENTRY->setEcommMaleAge2125( $ECOMM_MALE_AGE_21_25 );
                    $NEW_RPT_ENTRY->setEcommMaleAge2630( $ECOMM_MALE_AGE_26_30 );
                    $NEW_RPT_ENTRY->setEcommMaleAge3135( $ECOMM_MALE_AGE_31_35 );
                    $NEW_RPT_ENTRY->setEcommMaleAge3640( $ECOMM_MALE_AGE_36_40 );
                    $NEW_RPT_ENTRY->setEcommMaleAge4145( $ECOMM_MALE_AGE_41_45 );
                    $NEW_RPT_ENTRY->setEcommMaleAge4650( $ECOMM_MALE_AGE_46_50 );
                    $NEW_RPT_ENTRY->setEcommMaleAge50Above( $ECOMM_MALE_AGE_50_ABOVE );

                    $NEW_RPT_ENTRY->setEcommFemaleAge2125( $ECOMM_FEMALE_AGE_21_25 );
                    $NEW_RPT_ENTRY->setEcommFemaleAge2630( $ECOMM_FEMALE_AGE_26_30 );
                    $NEW_RPT_ENTRY->setEcommFemaleAge3135( $ECOMM_FEMALE_AGE_31_35 );
                    $NEW_RPT_ENTRY->setEcommFemaleAge3640( $ECOMM_FEMALE_AGE_36_40 );
                    $NEW_RPT_ENTRY->setEcommFemaleAge4145( $ECOMM_FEMALE_AGE_41_45 );
                    $NEW_RPT_ENTRY->setEcommFemaleAge4650( $ECOMM_FEMALE_AGE_46_50 );
                    $NEW_RPT_ENTRY->setEcommFemaleAge50Above( $ECOMM_FEMALE_AGE_50_ABOVE );

                    $NEW_RPT_ENTRY->setInvMontTotal( $INV_MONT_TOTAL );
                    $NEW_RPT_ENTRY->setInvCvsTotal( $INV_CVS_TOTAL );
                    $NEW_RPT_ENTRY->setInvToftTotal( $INV_TOFT_TOTAL );
                    $NEW_RPT_ENTRY->setInvS99Total( $INV_S99_TOTAL );
                    $NEW_RPT_ENTRY->setInvShmTotal( $INV_SHM_TOTAL );
                    $NEW_RPT_ENTRY->setInvTontTotal( $INV_TONT_TOTAL );
                    $NEW_RPT_ENTRY->setInvEcommTotal( $INV_ECOMM_TOTAL );

                    $NEW_RPT_ENTRY->setInvMontRedeem( $INV_MONT_REDEEM );
                    $NEW_RPT_ENTRY->setInvCvsRedeem( $INV_CVS_REDEEM );
                    $NEW_RPT_ENTRY->setInvToftRedeem( $INV_TOFT_REDEEM );
                    $NEW_RPT_ENTRY->setInvS99Redeem( $INV_S99_REDEEM );
                    $NEW_RPT_ENTRY->setInvShmRedeem( $INV_SHM_REDEEM );
                    $NEW_RPT_ENTRY->setInvTontRedeem( $INV_TONT_REDEEM );
                    $NEW_RPT_ENTRY->setInvEcommRedeem( $INV_ECOMM_REDEEM );

                    $NEW_RPT_ENTRY->setInvMontLeft( $INV_MONT_REDEEM );
                    $NEW_RPT_ENTRY->setInvCvsLeft( $INV_CVS_REDEEM );
                    $NEW_RPT_ENTRY->setInvToftLeft( $INV_TOFT_REDEEM );
                    $NEW_RPT_ENTRY->setInvS99Left( $INV_S99_REDEEM );
                    $NEW_RPT_ENTRY->setInvShmLeft( $INV_SHM_REDEEM );
                    $NEW_RPT_ENTRY->setInvTontLeft( $INV_TONT_REDEEM );
                    $NEW_RPT_ENTRY->setInvEcommLeft( $INV_ECOMM_REDEEM );

                    $NEW_RPT_ENTRY->setInvMontLeft( $INV_MONT_TOTAL - $INV_MONT_REDEEM );
                    $NEW_RPT_ENTRY->setInvCvsLeft( $INV_CVS_TOTAL - $INV_CVS_REDEEM );
                    $NEW_RPT_ENTRY->setInvToftLeft( $INV_TOFT_TOTAL - $INV_TOFT_REDEEM );
                    $NEW_RPT_ENTRY->setInvS99Left( $INV_S99_TOTAL - $INV_S99_REDEEM );
                    $NEW_RPT_ENTRY->setInvShmLeft( $INV_SHM_TOTAL - $INV_SHM_REDEEM );
                    $NEW_RPT_ENTRY->setInvTontLeft( $INV_TONT_TOTAL - $INV_TONT_REDEEM );
                    $NEW_RPT_ENTRY->setInvEcommLeft( $INV_ECOMM_TOTAL - $INV_ECOMM_REDEEM );
                    

                    $NEW_RPT_ENTRY->setDelMontProcess( $DEL_MONT_PROCESS );
                    $NEW_RPT_ENTRY->setDelMontOut( $DEL_MONT_OUT );
                    // $NEW_RPT_ENTRY->setDelMontAssigned( $DEL_MONT_ADDRESS );

                    $NEW_RPT_ENTRY->setDelCvsProcess( $DEL_CVS_PROCESS );
                    $NEW_RPT_ENTRY->setDelCvsOut( $DEL_CVS_OUT );
                    // $NEW_RPT_ENTRY->setDelCvsAssigned( $DEL_CVS_ADDRESS );

                    $NEW_RPT_ENTRY->setDelToftProcess( $DEL_TOFT_PROCESS );
                    $NEW_RPT_ENTRY->setDelToftOut( $DEL_TOFT_OUT );
                    // $NEW_RPT_ENTRY->setDelToftAssigned( $DEL_TOFT_ADDRESS );

                    $NEW_RPT_ENTRY->setDelS99Process( $DEL_S99_PROCESS );
                    $NEW_RPT_ENTRY->setDelS99Out( $DEL_S99_OUT );
                    // $NEW_RPT_ENTRY->setDelS99Assigned( $DEL_S99_ADDRESS );

                    $NEW_RPT_ENTRY->setDelShmProcess( $DEL_SHM_PROCESS );
                    $NEW_RPT_ENTRY->setDelShmOut( $DEL_SHM_OUT );
                    // $NEW_RPT_ENTRY->setDelShmAssigned( $DEL_SHM_ADDRESS );

                    $NEW_RPT_ENTRY->setDelTontProcess( $DEL_TONT_PROCESS );
                    $NEW_RPT_ENTRY->setDelTontOut( $DEL_TONT_OUT );
                    // $NEW_RPT_ENTRY->setDelTontAssigned( $DEL_TONT_ADDRESS );

                    $NEW_RPT_ENTRY->setDelEcommProcess( $DEL_ECOMM_PROCESS );
                    $NEW_RPT_ENTRY->setDelEcommOut( $DEL_ECOMM_OUT );
                    // $NEW_RPT_ENTRY->setDelEcommAssigned( $DEL_ECOMM_ADDRESS );

                    /* BATCH 3 */
                    $NEW_RPT_ENTRY->setRejectReason1Mont( $REJECT_REASON1_MONT );
                    $NEW_RPT_ENTRY->setRejectReason2Mont( $REJECT_REASON2_MONT );
                    $NEW_RPT_ENTRY->setRejectReason3Mont( $REJECT_REASON3_MONT );
                    $NEW_RPT_ENTRY->setRejectReason4Mont( $REJECT_REASON4_MONT );
                    $NEW_RPT_ENTRY->setRejectReason5Mont( $REJECT_REASON5_MONT );
                    $NEW_RPT_ENTRY->setRejectReason6Mont( $REJECT_REASON6_MONT );
                    $NEW_RPT_ENTRY->setRejectReason7Mont( $REJECT_REASON7_MONT );
                    $NEW_RPT_ENTRY->setRejectReason8Mont( $REJECT_REASON8_MONT );

                    $NEW_RPT_ENTRY->setRejectReason1Cvs( $REJECT_REASON1_CVS );
                    $NEW_RPT_ENTRY->setRejectReason2Cvs( $REJECT_REASON2_CVS );
                    $NEW_RPT_ENTRY->setRejectReason3Cvs( $REJECT_REASON3_CVS );
                    $NEW_RPT_ENTRY->setRejectReason4Cvs( $REJECT_REASON4_CVS );
                    $NEW_RPT_ENTRY->setRejectReason5Cvs( $REJECT_REASON5_CVS );
                    $NEW_RPT_ENTRY->setRejectReason6Cvs( $REJECT_REASON6_CVS );
                    $NEW_RPT_ENTRY->setRejectReason7Cvs( $REJECT_REASON7_CVS );
                    $NEW_RPT_ENTRY->setRejectReason8Cvs( $REJECT_REASON8_CVS );

                    $NEW_RPT_ENTRY->setRejectReason1Toft( $REJECT_REASON1_TOFT );
                    $NEW_RPT_ENTRY->setRejectReason2Toft( $REJECT_REASON2_TOFT );
                    $NEW_RPT_ENTRY->setRejectReason3Toft( $REJECT_REASON3_TOFT );
                    $NEW_RPT_ENTRY->setRejectReason4Toft( $REJECT_REASON4_TOFT );
                    $NEW_RPT_ENTRY->setRejectReason5Toft( $REJECT_REASON5_TOFT );
                    $NEW_RPT_ENTRY->setRejectReason6Toft( $REJECT_REASON6_TOFT );
                    $NEW_RPT_ENTRY->setRejectReason7Toft( $REJECT_REASON7_TOFT );
                    $NEW_RPT_ENTRY->setRejectReason8Toft( $REJECT_REASON8_TOFT );

                    $NEW_RPT_ENTRY->setRejectReason1S99( $REJECT_REASON1_S99 );
                    $NEW_RPT_ENTRY->setRejectReason2S99( $REJECT_REASON2_S99 );
                    $NEW_RPT_ENTRY->setRejectReason3S99( $REJECT_REASON3_S99 );
                    $NEW_RPT_ENTRY->setRejectReason4S99( $REJECT_REASON4_S99 );
                    $NEW_RPT_ENTRY->setRejectReason5S99( $REJECT_REASON5_S99 );
                    $NEW_RPT_ENTRY->setRejectReason6S99( $REJECT_REASON6_S99 );
                    $NEW_RPT_ENTRY->setRejectReason7S99( $REJECT_REASON7_S99 );
                    $NEW_RPT_ENTRY->setRejectReason8S99( $REJECT_REASON8_S99 );

                    $NEW_RPT_ENTRY->setRejectReason1Shm( $REJECT_REASON1_SHM );
                    $NEW_RPT_ENTRY->setRejectReason2Shm( $REJECT_REASON2_SHM );
                    $NEW_RPT_ENTRY->setRejectReason3Shm( $REJECT_REASON3_SHM );
                    $NEW_RPT_ENTRY->setRejectReason4Shm( $REJECT_REASON4_SHM );
                    $NEW_RPT_ENTRY->setRejectReason5Shm( $REJECT_REASON5_SHM );
                    $NEW_RPT_ENTRY->setRejectReason6Shm( $REJECT_REASON6_SHM );
                    $NEW_RPT_ENTRY->setRejectReason7Shm( $REJECT_REASON7_SHM );
                    $NEW_RPT_ENTRY->setRejectReason8Shm( $REJECT_REASON8_SHM );

                    $NEW_RPT_ENTRY->setRejectReason1Tont( $REJECT_REASON1_TONT );
                    $NEW_RPT_ENTRY->setRejectReason2Tont( $REJECT_REASON2_TONT );
                    $NEW_RPT_ENTRY->setRejectReason3Tont( $REJECT_REASON3_TONT );
                    $NEW_RPT_ENTRY->setRejectReason4Tont( $REJECT_REASON4_TONT );
                    $NEW_RPT_ENTRY->setRejectReason5Tont( $REJECT_REASON5_TONT );
                    $NEW_RPT_ENTRY->setRejectReason6Tont( $REJECT_REASON6_TONT );
                    $NEW_RPT_ENTRY->setRejectReason7Tont( $REJECT_REASON7_TONT );
                    $NEW_RPT_ENTRY->setRejectReason8Tont( $REJECT_REASON8_TONT );

                    $NEW_RPT_ENTRY->setRejectReason1Ecomm( $REJECT_REASON1_ECOMM );
                    $NEW_RPT_ENTRY->setRejectReason2Ecomm( $REJECT_REASON2_ECOMM );
                    $NEW_RPT_ENTRY->setRejectReason3Ecomm( $REJECT_REASON3_ECOMM );
                    $NEW_RPT_ENTRY->setRejectReason4Ecomm( $REJECT_REASON4_ECOMM );
                    $NEW_RPT_ENTRY->setRejectReason5Ecomm( $REJECT_REASON5_ECOMM );
                    $NEW_RPT_ENTRY->setRejectReason6Ecomm( $REJECT_REASON6_ECOMM );
                    $NEW_RPT_ENTRY->setRejectReason7Ecomm( $REJECT_REASON7_ECOMM );
                    $NEW_RPT_ENTRY->setRejectReason8Ecomm( $REJECT_REASON8_ECOMM );
    
                    $this->manager->persist($NEW_RPT_ENTRY);     
                    $this->manager->flush();

                    $output->writeln('');            
                    $output->writeln('<fg=bright-blue>Inserting Week Number => '.$reqWeek);
                    $output->writeln('');
                    $this->activity->setActivity("Extractor Success.",null,"...");
                }
            }
            
            // Check if Excel export is requested
            if ($input->getOption('export-excel')) {
                $output->writeln('');
                $output->writeln('<fg=bright-green>Exporting data to Excel...</fg=bright-green>');
                $this->exportToExcel($reqWeek, $output);
            }
            
            $this->manager->getConnection()->commit();
        } catch (\Exception $e) {
            $this->manager->getConnection()->rollBack();

            $output->writeln('Error in processing rolling back...');
            $output->writeln($e->getMessage());            
        }
        return Command::SUCCESS;

    }

    private function pumpByState2024($ENTRY_DATA, $channel, $reqWeek) {
        for($i = 0; $i < count($ENTRY_DATA);$i++) {
            $NEW_MONT = new ReportByState();
            $NEW_MONT->setWeekNumber($reqWeek);
            // $NEW_MONT->setLastUpdated(new \DateTime);
            $NEW_MONT->setState($ENTRY_DATA[$i]['state']);
            $NEW_MONT->setCity($ENTRY_DATA[$i]['city']);
            $NEW_MONT->setChannel($channel);
            $NEW_MONT->setEntries($ENTRY_DATA[$i]['entries']); 
            
            $this->manager->persist($NEW_MONT);     
            $this->manager->flush();
        }
    }

    private function pumpBySku2024($SKU_DATA, $channel, $reqWeek) {
        for($i = 0; $i < count($SKU_DATA);$i++) {
            $NEW_SKU = new ReportBySku();
            $NEW_SKU->setWeekNumber($reqWeek);
            $NEW_SKU->setSkuName($SKU_DATA[$i]['sku_name']);
            $NEW_SKU->setQuantity($SKU_DATA[$i]['quantity']);
            $NEW_SKU->setChannel($channel);
            $NEW_SKU->setCreatedDate(new \DateTime());
            $NEW_SKU->setUpdatedDate(new \DateTime());
            
            $this->manager->persist($NEW_SKU);     
            $this->manager->flush();
        }
    }

    /**
     * Export extracted data to Excel file
     *
     * @param int $weekNumber
     * @param OutputInterface $output
     * @return void
     */
    private function exportToExcel(int $weekNumber, OutputInterface $output): void
    {
        try {
            // Get the report entry data for the week
            $reportEntry = $this->manager->getRepository(ReportEntry::class)->findOneBy(['week_number' => $weekNumber]);
            
            if (!$reportEntry) {
                $output->writeln('<fg=red>No data found for week ' . $weekNumber . '</fg=red>');
                return;
            }

            // Get additional data
            $reportByState = $this->manager->getRepository(ReportByState::class)->findBy(['week_number' => $weekNumber]);
            $reportBySku = $this->manager->getRepository(ReportBySku::class)->findBy(['week_number' => $weekNumber]);

            // Load template
            $templatePath = __DIR__ . '/excel/export_template.xlsx';
            
            if (!file_exists($templatePath)) {
                $output->writeln('<fg=red>Template file not found: ' . $templatePath . '</fg=red>');
                return;
            }

            // Generate Excel file directly from template preserving formatting
            $this->generateExcelFileFromTemplate($templatePath, $reportEntry, $reportByState, $reportBySku, $weekNumber, $output);
            
        } catch (\Exception $e) {
            $output->writeln('<fg=red>Error exporting to Excel: ' . $e->getMessage() . '</fg=red>');
        }
    }







    /**
     * Populate Section 1: Basic Status Data (Valid/Invalid/Pending)
     */
    private function populateBasicStatusData(array &$sheetData, ReportEntry $reportEntry, int $weekNumber, OutputInterface $output): void
    {
        $output->writeln('<fg=cyan>Section 1: Populating basic status data for week ' . $weekNumber . '</fg=cyan>');
        
        // Week number should be in column index (weekNumber + 1) because columns 0,1 are Channel,Status
        // Week 1 = column index 2, Week 2 = column index 3, etc.
        $weekColumnIndex = $weekNumber + 1;
        
        // Define the data mapping for each channel and status
        $channelData = [
            'SHM' => [
                'Valid' => $reportEntry->getShmValid(),
                'Invalid' => $reportEntry->getShmInvalid(),
                'Pending' => $reportEntry->getShmPending()
            ],
            'MONT' => [
                'Valid' => $reportEntry->getMontValid(),
                'Invalid' => $reportEntry->getMontInvalid(),
                'Pending' => $reportEntry->getMontPending()
            ],
            'TONT' => [
                'Valid' => $reportEntry->getTontValid(),
                'Invalid' => $reportEntry->getTontInvalid(),
                'Pending' => $reportEntry->getTontPending()
            ],
            'CVS' => [
                'Valid' => $reportEntry->getCvsValid(),
                'Invalid' => $reportEntry->getCvsInvalid(),
                'Pending' => $reportEntry->getCvsPending()
            ],
            'TOFT' => [
                'Valid' => $reportEntry->getToftValid() ?? 0,
                'Invalid' => $reportEntry->getToftInvalid() ?? 0,
                'Pending' => $reportEntry->getToftPending() ?? 0
            ],
            'S99' => [
                'Valid' => $reportEntry->getS99Valid(),
                'Invalid' => $reportEntry->getS99Invalid(),
                'Pending' => $reportEntry->getS99Pending()
            ],
            'ECOMM' => [
                'Valid' => $reportEntry->getEcommValid(),
                'Invalid' => $reportEntry->getEcommInvalid(),
                'Pending' => $reportEntry->getEcommPending()
            ]
        ];
        
        // Update rows 1-21 (basic status data)
        for ($rowIndex = 1; $rowIndex <= 21; $rowIndex++) {
            if (!isset($sheetData[$rowIndex]) || count($sheetData[$rowIndex]) < 2) {
                continue;
            }
            
            $channel = $sheetData[$rowIndex][0] ?? '';
            $status = $sheetData[$rowIndex][1] ?? '';
            
            if (isset($channelData[$channel]) && isset($channelData[$channel][$status])) {
                if (isset($sheetData[$rowIndex][$weekColumnIndex])) {
                    $value = $channelData[$channel][$status] ?? 0;
                    $sheetData[$rowIndex][$weekColumnIndex] = $value;
                    $output->writeln('<fg=green>  Updated ' . $channel . ' ' . $status . ' for week ' . $weekNumber . ': ' . $value . '</fg=green>');
                }
            }
        }
    }

    /**
     * Populate Section 2: Rejection Reasons Data
     */
    private function populateRejectionReasonsData(array &$sheetData, ReportEntry $reportEntry, OutputInterface $output): void
    {
        $output->writeln('<fg=cyan>Section 2: Populating rejection reasons data</fg=cyan>');
        
        // Define rejection reasons mapping (columns 2-9 based on row 22 headers)
        $rejectionReasonMethods = [
            2 => 'getRejectReason1', // BELOW QUALIFYING AMOUNT
            3 => 'getRejectReason2', // BELOW QUALIFYING QUANTITY
            4 => 'getRejectReason3', // DUPLICATE RECEIPT
            5 => 'getRejectReason4', // NON PARTICIPATING OUTLET
            6 => 'getRejectReason5', // NON PARTICIPATING PRODUCT
            7 => 'getRejectReason6', // OUTSIDE CONTEST PERIOD
            8 => 'getRejectReason7', // OUTSIDE COVERAGEGE
            9 => 'getRejectReason8'  // UNCLEAR IMAGE/NOT A RECEIPT
        ];
        
        // Channel suffixes for method names
        $channelSuffixes = [
            'CVS' => 'Cvs',
            'ECOMM' => 'Ecomm',
            'MONT' => 'Mont',
            'S99' => 'S99',
            'SHM' => 'Shm',
            'TOFT' => 'Toft',
            'TONT' => 'Tont'
        ];
        
        // Update rows 23-29 (rejection reasons data)
        for ($rowIndex = 23; $rowIndex <= 29; $rowIndex++) {
            if (!isset($sheetData[$rowIndex]) || count($sheetData[$rowIndex]) < 2) {
                continue;
            }
            
            $channel = $sheetData[$rowIndex][0] ?? '';
            
            if (isset($channelSuffixes[$channel])) {
                $suffix = $channelSuffixes[$channel];
                
                foreach ($rejectionReasonMethods as $columnIndex => $methodPrefix) {
                    $methodName = $methodPrefix . $suffix;
                    
                    if (method_exists($reportEntry, $methodName)) {
                        $value = $reportEntry->$methodName() ?? 0;
                        if (isset($sheetData[$rowIndex][$columnIndex])) {
                            $sheetData[$rowIndex][$columnIndex] = $value;
                            $output->writeln('<fg=green>  Updated ' . $channel . ' rejection reason ' . ($columnIndex - 1) . ': ' . $value . '</fg=green>');
                        }
                    }
                }
            }
        }
    }

    /**
     * Populate Section 3: Demographics Data (Age/Gender)
     */
    private function populateDemographicsData(array &$sheetData, ReportEntry $reportEntry, OutputInterface $output): void
    {
        $output->writeln('<fg=cyan>Section 3: Populating demographics data</fg=cyan>');
        
        // Age group mapping (columns 2-8 based on row 30 headers)
        $ageGroupMethods = [
            2 => 'Age2125',   // 21-25
            3 => 'Age2630',   // 26-30
            4 => 'Age3135',   // 31-35
            5 => 'Age3640',   // 36-40
            6 => 'Age4145',   // 41-45
            7 => 'Age4650',   // 46-50
            8 => 'Age50Above' // >50
        ];
        
        // Channel prefixes and gender data
        $channelData = [
            'CVS' => ['prefix' => 'Cvs', 'male' => $reportEntry->getMaleEntryCvs() ?? 0, 'female' => $reportEntry->getFemaleEntryCvs() ?? 0],
            'ECOMM' => ['prefix' => 'Ecomm', 'male' => $reportEntry->getMaleEntryEcomm() ?? 0, 'female' => $reportEntry->getFemaleEntryEcomm() ?? 0],
            'MONT' => ['prefix' => 'Mont', 'male' => $reportEntry->getMaleEntryMont() ?? 0, 'female' => $reportEntry->getFemaleEntryMont() ?? 0],
            'S99' => ['prefix' => 'S99', 'male' => $reportEntry->getMaleEntryS99() ?? 0, 'female' => $reportEntry->getFemaleEntryS99() ?? 0],
            'SHM' => ['prefix' => 'Shm', 'male' => $reportEntry->getMaleEntryShm() ?? 0, 'female' => $reportEntry->getFemaleEntryShm() ?? 0],
            'TOFT' => ['prefix' => 'Toft', 'male' => $reportEntry->getMaleEntryToft() ?? 0, 'female' => $reportEntry->getFemaleEntryToft() ?? 0],
            'TONT' => ['prefix' => 'Tont', 'male' => $reportEntry->getMaleEntryTont() ?? 0, 'female' => $reportEntry->getFemaleEntryTont() ?? 0]
        ];
        
        // Update rows 31-44 (demographics data)
        for ($rowIndex = 31; $rowIndex <= 44; $rowIndex++) {
            if (!isset($sheetData[$rowIndex]) || count($sheetData[$rowIndex]) < 2) {
                continue;
            }
            
            $channel = $sheetData[$rowIndex][0] ?? '';
            $gender = $sheetData[$rowIndex][1] ?? '';
            
            if (isset($channelData[$channel])) {
                $prefix = $channelData[$channel]['prefix'];
                
                // Set gender totals in column 1 (if needed)
                if ($gender === 'M') {
                    // For age group data, we'll use the age-specific methods
                    foreach ($ageGroupMethods as $columnIndex => $ageSuffix) {
                        $methodName = 'get' . $prefix . $ageSuffix;
                        if (method_exists($reportEntry, $methodName)) {
                            $totalAgeValue = $reportEntry->$methodName() ?? 0;
                            // For now, split evenly between M/F (you may want to adjust this logic)
                            $value = intval($totalAgeValue / 2);
                            if (isset($sheetData[$rowIndex][$columnIndex])) {
                                $sheetData[$rowIndex][$columnIndex] = $value;
                                $output->writeln('<fg=green>  Updated ' . $channel . ' ' . $gender . ' age group ' . ($columnIndex - 1) . ': ' . $value . '</fg=green>');
                            }
                        }
                    }
                } elseif ($gender === 'F') {
                    // For female, use the remaining half
                    foreach ($ageGroupMethods as $columnIndex => $ageSuffix) {
                        $methodName = 'get' . $prefix . $ageSuffix;
                        if (method_exists($reportEntry, $methodName)) {
                            $totalAgeValue = $reportEntry->$methodName() ?? 0;
                            // For now, split evenly between M/F (you may want to adjust this logic)
                            $value = intval($totalAgeValue / 2);
                            if (isset($sheetData[$rowIndex][$columnIndex])) {
                                $sheetData[$rowIndex][$columnIndex] = $value;
                                $output->writeln('<fg=green>  Updated ' . $channel . ' ' . $gender . ' age group ' . ($columnIndex - 1) . ': ' . $value . '</fg=green>');
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Populate the "data for pivot" sheet
     *
     * @param array $sheetData
     * @param ReportEntry $reportEntry
     * @param int $weekNumber
     * @param OutputInterface $output
     * @return array
     */
    private function populatePivotSheet(array $sheetData, ReportEntry $reportEntry, int $weekNumber, OutputInterface $output): array
    {
        $output->writeln('<fg=yellow>Populating pivot data sheet for week ' . $weekNumber . '</fg=yellow>');
        
        // Determine current year
        $currentYear = date('Y');
        $yearCode = 'Y' . substr($currentYear, 2); // Y24 or Y25
        $weekCode = 'W' . $weekNumber; // W1, W2, etc.
        
        $output->writeln('<fg=cyan>Looking for year: ' . $yearCode . ', week: ' . $weekCode . '</fg=cyan>');
        
        // Define channel data mapping
        $channelData = [
            'CVS' => $reportEntry->getCvsTotal() ?? 0,
            'ECOMM' => $reportEntry->getEcommTotal() ?? 0,
            'MONT' => $reportEntry->getMontTotal() ?? 0,
            'S99' => $reportEntry->getS99Total() ?? 0,
            'SHM' => $reportEntry->getShmTotal() ?? 0,
            'TOFT' => $reportEntry->getToftTotal() ?? 0,
            'TONT' => $reportEntry->getTontTotal() ?? 0
        ];
        
        // Calculate total for the week
        $weekTotal = array_sum($channelData);
        
        // Update the sheet data
        foreach ($sheetData as $rowIndex => &$rowData) {
            if (count($rowData) < 4) {
                continue; // Skip invalid rows
            }
            
            $rowYear = $rowData[0] ?? '';
            $rowWeek = $rowData[1] ?? '';
            $rowChannel = $rowData[2] ?? '';
            
            // Match year and week
            if ($rowYear === $yearCode && $rowWeek === $weekCode) {
                // Handle individual channel data
                if (isset($channelData[$rowChannel])) {
                    $rowData[3] = $channelData[$rowChannel];
                    $output->writeln('<fg=green>  Updated ' . $rowYear . ' ' . $rowWeek . ' ' . $rowChannel . ': ' . $channelData[$rowChannel] . '</fg=green>');
                }
                // Handle total row (channel will be like "Y24 total" or "Y25 Total")
                elseif (strpos($rowChannel, 'total') !== false || strpos($rowChannel, 'Total') !== false) {
                    $rowData[3] = $weekTotal;
                    $output->writeln('<fg=green>  Updated ' . $rowYear . ' ' . $rowWeek . ' total: ' . $weekTotal . '</fg=green>');
                }
            }
        }
        
        return $sheetData;
    }

    /**
     * Generate Excel file with populated data using PhpSpreadsheet to preserve formatting
     *
     * @param string $templatePath
     * @param ReportEntry $reportEntry
     * @param array $reportByState
     * @param array $reportBySku
     * @param int $weekNumber
     * @param OutputInterface $output
     */
    private function generateExcelFileFromTemplate(string $templatePath, ReportEntry $reportEntry, array $reportByState, array $reportBySku, int $weekNumber, OutputInterface $output): void
    {
        try {
            $output->writeln('<fg=cyan>Generating Excel file from template...</fg=cyan>');
            
            // Increase memory limit temporarily
            $originalMemoryLimit = ini_get('memory_limit');
            $currentUsage = memory_get_usage(true);
            $currentUsageMB = round($currentUsage / 1024 / 1024, 2);
            
            // Set memory limit to at least 2GB to handle large Excel files
            $newLimit = '2048M';
            $result = ini_set('memory_limit', $newLimit);
            
            if ($result === false) {
                $output->writeln('<fg=yellow>Could not set memory limit to ' . $newLimit . ', continuing with current limit: ' . $originalMemoryLimit . '</fg=yellow>');
            } else {
                $output->writeln('<fg=yellow>Memory limit set to ' . $newLimit . ' (was: ' . $originalMemoryLimit . ', current usage: ' . $currentUsageMB . 'MB)</fg=yellow>');
            }
            
            // Configure PhpSpreadsheet for memory efficiency
            try {
                \PhpOffice\PhpSpreadsheet\Settings::setCache(new \PhpOffice\PhpSpreadsheet\Collection\Memory\SimpleCache1());
            } catch (\Exception $cacheException) {
                $output->writeln('<fg=yellow>Could not set memory cache, continuing with default settings</fg=yellow>');
            }
            
            // Load the template file
            $spreadsheet = IOFactory::load($templatePath);
            
            // Process each worksheet
            foreach ($spreadsheet->getAllSheets() as $worksheet) {
                $sheetName = $worksheet->getTitle();
                $output->writeln('<fg=cyan>Processing sheet: ' . $sheetName . '</fg=cyan>');
                
                if (strtolower($sheetName) === 'data') {
                    $this->populateDataSheetInPlace($worksheet, $reportEntry, $weekNumber, $output);
                } elseif (strtolower($sheetName) === 'data for pivot') {
                    $this->populatePivotSheetInPlace($worksheet, $reportEntry, $weekNumber, $output);
                }
            }
            
            // Create filename with timestamp
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "report_week_{$weekNumber}_{$timestamp}.xlsx";
            
            // Ensure exports directory exists
            $exportsDir = __DIR__ . '/excel/exports';
            if (!is_dir($exportsDir)) {
                mkdir($exportsDir, 0755, true);
            }
            
            $filepath = $exportsDir . '/' . $filename;
            
            // Save the file
            $writer = new Xlsx($spreadsheet);
            $writer->save($filepath);
            
            // Clean up memory
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet, $writer);
            gc_collect_cycles();
            
            // Restore original memory limit
            if ($result !== false) {
                ini_set('memory_limit', $originalMemoryLimit);
                $output->writeln('<fg=yellow>Restored memory limit to: ' . $originalMemoryLimit . '</fg=yellow>');
            }
            
            $output->writeln('<fg=green>Excel file generated successfully!</fg=green>');
            $output->writeln('<fg=yellow>File saved as: ' . $filepath . '</fg=yellow>');
            $output->writeln('<fg=yellow>File size: ' . number_format(filesize($filepath) / 1024, 2) . ' KB</fg=yellow>');
            
        } catch (\Exception $e) {
            // Restore memory limit even on error
            if (isset($originalMemoryLimit) && isset($result) && $result !== false) {
                ini_set('memory_limit', $originalMemoryLimit);
            }
            $output->writeln('<fg=red>Error generating Excel file: ' . $e->getMessage() . '</fg=red>');
        }
    }

    /**
     * Populate the "data" sheet directly in the worksheet
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet
     * @param ReportEntry $reportEntry
     * @param int $weekNumber
     * @param OutputInterface $output
     */
    private function populateDataSheetInPlace($worksheet, ReportEntry $reportEntry, int $currentWeekNumber, OutputInterface $output): void
    {
        $output->writeln('<fg=yellow>Populating data sheet for weeks 1 to ' . $currentWeekNumber . '</fg=yellow>');
        
        // First, let's examine the header row to understand the template structure
        $highestColumn = $worksheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
        
        $output->writeln('<fg=cyan>Template has columns A to ' . $highestColumn . ' (index: ' . $highestColumnIndex . ')</fg=cyan>');
        
        // Find all week columns (Week 1, Week 2, etc.)
        $weekColumns = [];
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $cellValue = $worksheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '1')->getValue();
            $output->writeln('<fg=magenta>Column ' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . ': "' . $cellValue . '"</fg=magenta>');
            
            // Look for patterns like "Week 1", "W1", "1", etc.
            if (preg_match('/week\s*(\d+)/i', $cellValue, $matches)) {
                $weekNum = (int)$matches[1];
                if ($weekNum <= $currentWeekNumber) {
                    $weekColumns[$weekNum] = $col;
                }
            } elseif (preg_match('/w(\d+)/i', $cellValue, $matches)) {
                $weekNum = (int)$matches[1];
                if ($weekNum <= $currentWeekNumber) {
                    $weekColumns[$weekNum] = $col;
                }
            } elseif (is_numeric(trim($cellValue))) {
                $weekNum = (int)trim($cellValue);
                if ($weekNum >= 1 && $weekNum <= $currentWeekNumber) {
                    $weekColumns[$weekNum] = $col;
                }
            }
        }
        
        // If no week columns found in row 1, try row 2
        if (empty($weekColumns)) {
            $output->writeln('<fg=yellow>No week columns found in row 1, checking row 2...</fg=yellow>');
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $cellValue = $worksheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '2')->getValue();
                $output->writeln('<fg=magenta>Row 2, Column ' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . ': "' . $cellValue . '"</fg=magenta>');
                
                if (preg_match('/week\s*(\d+)/i', $cellValue, $matches)) {
                    $weekNum = (int)$matches[1];
                    if ($weekNum <= $currentWeekNumber) {
                        $weekColumns[$weekNum] = $col;
                    }
                } elseif (preg_match('/w(\d+)/i', $cellValue, $matches)) {
                    $weekNum = (int)$matches[1];
                    if ($weekNum <= $currentWeekNumber) {
                        $weekColumns[$weekNum] = $col;
                    }
                } elseif (is_numeric(trim($cellValue))) {
                    $weekNum = (int)trim($cellValue);
                    if ($weekNum >= 1 && $weekNum <= $currentWeekNumber) {
                        $weekColumns[$weekNum] = $col;
                    }
                }
            }
        }
        
        // Fallback: generate week columns if not found
        if (empty($weekColumns)) {
            $output->writeln('<fg=yellow>No week columns found in headers, using fallback calculation</fg=yellow>');
            for ($week = 1; $week <= $currentWeekNumber; $week++) {
                $weekColumns[$week] = 6 + $week; // G=7, H=8, I=9, etc.
            }
        }
        
        $output->writeln('<fg=green>Found week columns: ' . json_encode($weekColumns) . '</fg=green>');
        
        // Now populate data for each week from 1 to current week
        foreach ($weekColumns as $weekNum => $columnIndex) {
            $this->populateWeekData($worksheet, $weekNum, $columnIndex, $output);
        }

        // Section 2: Rejection reasons (rows 26-32) - CUMULATIVE TOTALS
        // NOTE: Each row is a channel, columns B-I represent reject reasons in specific order
        // Column order: B=reason6, C=reason2, D=reason1, E=reason7, F=reason3, G=reason4, H=reason8, I=reason5
        // This section shows CUMULATIVE reject reason totals from week 1 to current week
        
        $output->writeln('<fg=yellow>Calculating cumulative reject reasons for weeks 1 to ' . $currentWeekNumber . '</fg=yellow>');
        
        // Calculate cumulative reject reasons for CVS (week 1 to current week)
        $cumulativeRejectReason1Cvs = 0;
        $cumulativeRejectReason2Cvs = 0;
        $cumulativeRejectReason3Cvs = 0;
        $cumulativeRejectReason4Cvs = 0;
        $cumulativeRejectReason5Cvs = 0;
        $cumulativeRejectReason6Cvs = 0;
        $cumulativeRejectReason7Cvs = 0;
        $cumulativeRejectReason8Cvs = 0;
        
        for ($w = 1; $w <= $currentWeekNumber; $w++) {
            $weeklyEntry = $this->manager->getRepository(ReportEntry::class)->findOneBy(['week_number' => $w]);
            if ($weeklyEntry) {
                $cumulativeRejectReason1Cvs += $weeklyEntry->getRejectReason1Cvs() ?? 0;
                $cumulativeRejectReason2Cvs += $weeklyEntry->getRejectReason2Cvs() ?? 0;
                $cumulativeRejectReason3Cvs += $weeklyEntry->getRejectReason3Cvs() ?? 0;
                $cumulativeRejectReason4Cvs += $weeklyEntry->getRejectReason4Cvs() ?? 0;
                $cumulativeRejectReason5Cvs += $weeklyEntry->getRejectReason5Cvs() ?? 0;
                $cumulativeRejectReason6Cvs += $weeklyEntry->getRejectReason6Cvs() ?? 0;
                $cumulativeRejectReason7Cvs += $weeklyEntry->getRejectReason7Cvs() ?? 0;
                $cumulativeRejectReason8Cvs += $weeklyEntry->getRejectReason8Cvs() ?? 0;
            }
        }
        
        // Row 26: CVS reject reasons (columns B-I in custom order) - CUMULATIVE TOTALS
        $worksheet->setCellValue('B26', $cumulativeRejectReason6Cvs);  // Column B: reject_reason6
        $worksheet->setCellValue('C26', $cumulativeRejectReason2Cvs);  // Column C: reject_reason2
        $worksheet->setCellValue('D26', $cumulativeRejectReason1Cvs);  // Column D: reject_reason1
        $worksheet->setCellValue('E26', $cumulativeRejectReason7Cvs);  // Column E: reject_reason7
        $worksheet->setCellValue('F26', $cumulativeRejectReason3Cvs);  // Column F: reject_reason3
        $worksheet->setCellValue('G26', $cumulativeRejectReason4Cvs);  // Column G: reject_reason4
        $worksheet->setCellValue('H26', $cumulativeRejectReason8Cvs);  // Column H: reject_reason8
        $worksheet->setCellValue('I26', $cumulativeRejectReason5Cvs);  // Column I: reject_reason5
        
        // Calculate cumulative reject reasons for all other channels
        $channels = ['Ecomm', 'Mont', 'S99', 'Shm', 'Toft', 'Tont'];
        $cumulativeRejectReasons = [];
        
        foreach ($channels as $channel) {
            $cumulativeRejectReasons[$channel] = [
                'reason1' => 0, 'reason2' => 0, 'reason3' => 0, 'reason4' => 0,
                'reason5' => 0, 'reason6' => 0, 'reason7' => 0, 'reason8' => 0
            ];
            
            for ($w = 1; $w <= $currentWeekNumber; $w++) {
                $weeklyEntry = $this->manager->getRepository(ReportEntry::class)->findOneBy(['week_number' => $w]);
                if ($weeklyEntry) {
                    $cumulativeRejectReasons[$channel]['reason1'] += $weeklyEntry->{'getRejectReason1' . $channel}() ?? 0;
                    $cumulativeRejectReasons[$channel]['reason2'] += $weeklyEntry->{'getRejectReason2' . $channel}() ?? 0;
                    $cumulativeRejectReasons[$channel]['reason3'] += $weeklyEntry->{'getRejectReason3' . $channel}() ?? 0;
                    $cumulativeRejectReasons[$channel]['reason4'] += $weeklyEntry->{'getRejectReason4' . $channel}() ?? 0;
                    $cumulativeRejectReasons[$channel]['reason5'] += $weeklyEntry->{'getRejectReason5' . $channel}() ?? 0;
                    $cumulativeRejectReasons[$channel]['reason6'] += $weeklyEntry->{'getRejectReason6' . $channel}() ?? 0;
                    $cumulativeRejectReasons[$channel]['reason7'] += $weeklyEntry->{'getRejectReason7' . $channel}() ?? 0;
                    $cumulativeRejectReasons[$channel]['reason8'] += $weeklyEntry->{'getRejectReason8' . $channel}() ?? 0;
                }
            }
        }
        
        // Row 27: ECOMM reject reasons (columns B-I in custom order) - CUMULATIVE TOTALS
        $worksheet->setCellValue('B27', $cumulativeRejectReasons['Ecomm']['reason6']);  // Column B: reject_reason6
        $worksheet->setCellValue('C27', $cumulativeRejectReasons['Ecomm']['reason2']);  // Column C: reject_reason2
        $worksheet->setCellValue('D27', $cumulativeRejectReasons['Ecomm']['reason1']);  // Column D: reject_reason1
        $worksheet->setCellValue('E27', $cumulativeRejectReasons['Ecomm']['reason7']);  // Column E: reject_reason7
        $worksheet->setCellValue('F27', $cumulativeRejectReasons['Ecomm']['reason3']);  // Column F: reject_reason3
        $worksheet->setCellValue('G27', $cumulativeRejectReasons['Ecomm']['reason4']);  // Column G: reject_reason4
        $worksheet->setCellValue('H27', $cumulativeRejectReasons['Ecomm']['reason8']);  // Column H: reject_reason8
        $worksheet->setCellValue('I27', $cumulativeRejectReasons['Ecomm']['reason5']);  // Column I: reject_reason5
        
        // Row 28: MONT reject reasons (columns B-I in custom order) - CUMULATIVE TOTALS
        $worksheet->setCellValue('B28', $cumulativeRejectReasons['Mont']['reason6']);  // Column B: reject_reason6
        $worksheet->setCellValue('C28', $cumulativeRejectReasons['Mont']['reason2']);  // Column C: reject_reason2
        $worksheet->setCellValue('D28', $cumulativeRejectReasons['Mont']['reason1']);  // Column D: reject_reason1
        $worksheet->setCellValue('E28', $cumulativeRejectReasons['Mont']['reason7']);  // Column E: reject_reason7
        $worksheet->setCellValue('F28', $cumulativeRejectReasons['Mont']['reason3']);  // Column F: reject_reason3
        $worksheet->setCellValue('G28', $cumulativeRejectReasons['Mont']['reason4']);  // Column G: reject_reason4
        $worksheet->setCellValue('H28', $cumulativeRejectReasons['Mont']['reason8']);  // Column H: reject_reason8
        $worksheet->setCellValue('I28', $cumulativeRejectReasons['Mont']['reason5']);  // Column I: reject_reason5
        
        // Row 29: S99 reject reasons (columns B-I in custom order) - CUMULATIVE TOTALS
        $worksheet->setCellValue('B29', $cumulativeRejectReasons['S99']['reason6']);  // Column B: reject_reason6
        $worksheet->setCellValue('C29', $cumulativeRejectReasons['S99']['reason2']);  // Column C: reject_reason2
        $worksheet->setCellValue('D29', $cumulativeRejectReasons['S99']['reason1']);  // Column D: reject_reason1
        $worksheet->setCellValue('E29', $cumulativeRejectReasons['S99']['reason7']);  // Column E: reject_reason7
        $worksheet->setCellValue('F29', $cumulativeRejectReasons['S99']['reason3']);  // Column F: reject_reason3
        $worksheet->setCellValue('G29', $cumulativeRejectReasons['S99']['reason4']);  // Column G: reject_reason4
        $worksheet->setCellValue('H29', $cumulativeRejectReasons['S99']['reason8']);  // Column H: reject_reason8
        $worksheet->setCellValue('I29', $cumulativeRejectReasons['S99']['reason5']);  // Column I: reject_reason5
        
        // Row 30: SHM reject reasons (columns B-I in custom order) - CUMULATIVE TOTALS
        $worksheet->setCellValue('B30', $cumulativeRejectReasons['Shm']['reason6']);  // Column B: reject_reason6
        $worksheet->setCellValue('C30', $cumulativeRejectReasons['Shm']['reason2']);  // Column C: reject_reason2
        $worksheet->setCellValue('D30', $cumulativeRejectReasons['Shm']['reason1']);  // Column D: reject_reason1
        $worksheet->setCellValue('E30', $cumulativeRejectReasons['Shm']['reason7']);  // Column E: reject_reason7
        $worksheet->setCellValue('F30', $cumulativeRejectReasons['Shm']['reason3']);  // Column F: reject_reason3
        $worksheet->setCellValue('G30', $cumulativeRejectReasons['Shm']['reason4']);  // Column G: reject_reason4
        $worksheet->setCellValue('H30', $cumulativeRejectReasons['Shm']['reason8']);  // Column H: reject_reason8
        $worksheet->setCellValue('I30', $cumulativeRejectReasons['Shm']['reason5']);  // Column I: reject_reason5
        
        // Row 31: TOFT reject reasons (columns B-I in custom order) - CUMULATIVE TOTALS
        $worksheet->setCellValue('B31', $cumulativeRejectReasons['Toft']['reason6']);  // Column B: reject_reason6
        $worksheet->setCellValue('C31', $cumulativeRejectReasons['Toft']['reason2']);  // Column C: reject_reason2
        $worksheet->setCellValue('D31', $cumulativeRejectReasons['Toft']['reason1']);  // Column D: reject_reason1
        $worksheet->setCellValue('E31', $cumulativeRejectReasons['Toft']['reason7']);  // Column E: reject_reason7
        $worksheet->setCellValue('F31', $cumulativeRejectReasons['Toft']['reason3']);  // Column F: reject_reason3
        $worksheet->setCellValue('G31', $cumulativeRejectReasons['Toft']['reason4']);  // Column G: reject_reason4
        $worksheet->setCellValue('H31', $cumulativeRejectReasons['Toft']['reason8']);  // Column H: reject_reason8
        $worksheet->setCellValue('I31', $cumulativeRejectReasons['Toft']['reason5']);  // Column I: reject_reason5
        
        // Row 32: TONT reject reasons (columns B-I in custom order) - CUMULATIVE TOTALS
        $worksheet->setCellValue('B32', $cumulativeRejectReasons['Tont']['reason6']);  // Column B: reject_reason6
        $worksheet->setCellValue('C32', $cumulativeRejectReasons['Tont']['reason2']);  // Column C: reject_reason2
        $worksheet->setCellValue('D32', $cumulativeRejectReasons['Tont']['reason1']);  // Column D: reject_reason1
        $worksheet->setCellValue('E32', $cumulativeRejectReasons['Tont']['reason7']);  // Column E: reject_reason7
        $worksheet->setCellValue('F32', $cumulativeRejectReasons['Tont']['reason3']);  // Column F: reject_reason3
        $worksheet->setCellValue('G32', $cumulativeRejectReasons['Tont']['reason4']);  // Column G: reject_reason4
        $worksheet->setCellValue('H32', $cumulativeRejectReasons['Tont']['reason8']);  // Column H: reject_reason8
        $worksheet->setCellValue('I32', $cumulativeRejectReasons['Tont']['reason5']);  // Column I: reject_reason5
        
        $output->writeln('<fg=green>Multi-week data population completed successfully!</fg=green>');
    }

    /**
     * Populate data for a specific week in the specified column
     *
     * @param $worksheet
     * @param int $weekNumber
     * @param int $columnIndex
     * @param OutputInterface $output
     */
    private function populateWeekData($worksheet, int $weekNumber, int $columnIndex, OutputInterface $output): void
    {
        $output->writeln('<fg=yellow>Populating data for week ' . $weekNumber . ' in column ' . $columnIndex . '</fg=yellow>');
        
        // Get ReportEntry data for this specific week
        $reportEntry = $this->manager->getRepository(ReportEntry::class)->findOneBy(['week_number' => $weekNumber]);
        
        if (!$reportEntry) {
            $output->writeln('<fg=red>No ReportEntry found for week ' . $weekNumber . ', skipping...</fg=red>');
            return;
        }
        
        $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
        $output->writeln('<fg=cyan>Using column: ' . $columnLetter . ' for week ' . $weekNumber . '</fg=cyan>');
        
        // Section 1: Status data (rows 2-22) - Valid/Invalid/Pending by channel
        // SHM
        $worksheet->setCellValue($columnLetter . '2', $reportEntry->getShmValid() ?? 0);
        $worksheet->setCellValue($columnLetter . '3', $reportEntry->getShmInvalid() ?? 0);
        $worksheet->setCellValue($columnLetter . '4', $reportEntry->getShmPending() ?? 0);
        
        // MONT
        $worksheet->setCellValue($columnLetter . '5', $reportEntry->getMontValid() ?? 0);
        $worksheet->setCellValue($columnLetter . '6', $reportEntry->getMontInvalid() ?? 0);
        $worksheet->setCellValue($columnLetter . '7', $reportEntry->getMontPending() ?? 0);
        
        // TONT
        $worksheet->setCellValue($columnLetter . '8', $reportEntry->getTontValid() ?? 0);
        $worksheet->setCellValue($columnLetter . '9', $reportEntry->getTontInvalid() ?? 0);
        $worksheet->setCellValue($columnLetter . '10', $reportEntry->getTontPending() ?? 0);
        
        // CVS
        $worksheet->setCellValue($columnLetter . '11', $reportEntry->getCvsValid() ?? 0);
        $worksheet->setCellValue($columnLetter . '12', $reportEntry->getCvsInvalid() ?? 0);
        $worksheet->setCellValue($columnLetter . '13', $reportEntry->getCvsPending() ?? 0);
        
        // TOFT
        $worksheet->setCellValue($columnLetter . '14', $reportEntry->getToftValid() ?? 0);
        $worksheet->setCellValue($columnLetter . '15', $reportEntry->getToftInvalid() ?? 0);
        $worksheet->setCellValue($columnLetter . '16', $reportEntry->getToftPending() ?? 0);
        
        // S99
        $worksheet->setCellValue($columnLetter . '17', $reportEntry->getS99Valid() ?? 0);
        $worksheet->setCellValue($columnLetter . '18', $reportEntry->getS99Invalid() ?? 0);
        $worksheet->setCellValue($columnLetter . '19', $reportEntry->getS99Pending() ?? 0);
        
        // ECOMM
        $worksheet->setCellValue($columnLetter . '20', $reportEntry->getEcommValid() ?? 0);
        $worksheet->setCellValue($columnLetter . '21', $reportEntry->getEcommInvalid() ?? 0);
        $worksheet->setCellValue($columnLetter . '22', $reportEntry->getEcommPending() ?? 0);
        
        // NOTE: Section 2 (Rejection reasons) is now handled in the main populateDataSheetInPlace method
        // to calculate cumulative totals across all weeks, not per individual week
        
        // Section 3: Demographics Data (Channel-Gender-Age combinations) - rows 36-49
        // Structure: Each row is a channel-gender combination, columns C-I are age groups (21-25, 26-30, 31-35, 36-40, 41-45, 46-50, 50+)
        // NOTE: Demographics data shows CUMULATIVE totals from week 1 to current week, not individual week data
        
        // Initialize cumulative totals for all channel-gender-age combinations
        $cumulativeTotals = [
            'cvs_male' => ['2125' => 0, '2630' => 0, '3135' => 0, '3640' => 0, '4145' => 0, '4650' => 0, '50above' => 0],
            'cvs_female' => ['2125' => 0, '2630' => 0, '3135' => 0, '3640' => 0, '4145' => 0, '4650' => 0, '50above' => 0],
            'ecomm_male' => ['2125' => 0, '2630' => 0, '3135' => 0, '3640' => 0, '4145' => 0, '4650' => 0, '50above' => 0],
            'ecomm_female' => ['2125' => 0, '2630' => 0, '3135' => 0, '3640' => 0, '4145' => 0, '4650' => 0, '50above' => 0],
            'mont_male' => ['2125' => 0, '2630' => 0, '3135' => 0, '3640' => 0, '4145' => 0, '4650' => 0, '50above' => 0],
            'mont_female' => ['2125' => 0, '2630' => 0, '3135' => 0, '3640' => 0, '4145' => 0, '4650' => 0, '50above' => 0],
            's99_male' => ['2125' => 0, '2630' => 0, '3135' => 0, '3640' => 0, '4145' => 0, '4650' => 0, '50above' => 0],
            's99_female' => ['2125' => 0, '2630' => 0, '3135' => 0, '3640' => 0, '4145' => 0, '4650' => 0, '50above' => 0],
            'shm_male' => ['2125' => 0, '2630' => 0, '3135' => 0, '3640' => 0, '4145' => 0, '4650' => 0, '50above' => 0],
            'shm_female' => ['2125' => 0, '2630' => 0, '3135' => 0, '3640' => 0, '4145' => 0, '4650' => 0, '50above' => 0],
            'toft_male' => ['2125' => 0, '2630' => 0, '3135' => 0, '3640' => 0, '4145' => 0, '4650' => 0, '50above' => 0],
            'toft_female' => ['2125' => 0, '2630' => 0, '3135' => 0, '3640' => 0, '4145' => 0, '4650' => 0, '50above' => 0],
            'tont_male' => ['2125' => 0, '2630' => 0, '3135' => 0, '3640' => 0, '4145' => 0, '4650' => 0, '50above' => 0],
            'tont_female' => ['2125' => 0, '2630' => 0, '3135' => 0, '3640' => 0, '4145' => 0, '4650' => 0, '50above' => 0],
        ];
        
        // Sum up all weeks from 1 to current week
        for ($w = 1; $w <= $weekNumber; $w++) {
            $weekReportEntry = $this->manager->getRepository(ReportEntry::class)->findOneBy(['week_number' => $w]);
            if ($weekReportEntry) {
                // CVS
                $cumulativeTotals['cvs_male']['2125'] += $weekReportEntry->getCvsMaleAge2125() ?? 0;
                $cumulativeTotals['cvs_male']['2630'] += $weekReportEntry->getCvsMaleAge2630() ?? 0;
                $cumulativeTotals['cvs_male']['3135'] += $weekReportEntry->getCvsMaleAge3135() ?? 0;
                $cumulativeTotals['cvs_male']['3640'] += $weekReportEntry->getCvsMaleAge3640() ?? 0;
                $cumulativeTotals['cvs_male']['4145'] += $weekReportEntry->getCvsMaleAge4145() ?? 0;
                $cumulativeTotals['cvs_male']['4650'] += $weekReportEntry->getCvsMaleAge4650() ?? 0;
                $cumulativeTotals['cvs_male']['50above'] += $weekReportEntry->getCvsMaleAge50Above() ?? 0;
                
                $cumulativeTotals['cvs_female']['2125'] += $weekReportEntry->getCvsFemaleAge2125() ?? 0;
                $cumulativeTotals['cvs_female']['2630'] += $weekReportEntry->getCvsFemaleAge2630() ?? 0;
                $cumulativeTotals['cvs_female']['3135'] += $weekReportEntry->getCvsFemaleAge3135() ?? 0;
                $cumulativeTotals['cvs_female']['3640'] += $weekReportEntry->getCvsFemaleAge3640() ?? 0;
                $cumulativeTotals['cvs_female']['4145'] += $weekReportEntry->getCvsFemaleAge4145() ?? 0;
                $cumulativeTotals['cvs_female']['4650'] += $weekReportEntry->getCvsFemaleAge4650() ?? 0;
                $cumulativeTotals['cvs_female']['50above'] += $weekReportEntry->getCvsFemaleAge50Above() ?? 0;
                
                // ECOMM
                $cumulativeTotals['ecomm_male']['2125'] += $weekReportEntry->getEcommMaleAge2125() ?? 0;
                $cumulativeTotals['ecomm_male']['2630'] += $weekReportEntry->getEcommMaleAge2630() ?? 0;
                $cumulativeTotals['ecomm_male']['3135'] += $weekReportEntry->getEcommMaleAge3135() ?? 0;
                $cumulativeTotals['ecomm_male']['3640'] += $weekReportEntry->getEcommMaleAge3640() ?? 0;
                $cumulativeTotals['ecomm_male']['4145'] += $weekReportEntry->getEcommMaleAge4145() ?? 0;
                $cumulativeTotals['ecomm_male']['4650'] += $weekReportEntry->getEcommMaleAge4650() ?? 0;
                $cumulativeTotals['ecomm_male']['50above'] += $weekReportEntry->getEcommMaleAge50Above() ?? 0;
                
                $cumulativeTotals['ecomm_female']['2125'] += $weekReportEntry->getEcommFemaleAge2125() ?? 0;
                $cumulativeTotals['ecomm_female']['2630'] += $weekReportEntry->getEcommFemaleAge2630() ?? 0;
                $cumulativeTotals['ecomm_female']['3135'] += $weekReportEntry->getEcommFemaleAge3135() ?? 0;
                $cumulativeTotals['ecomm_female']['3640'] += $weekReportEntry->getEcommFemaleAge3640() ?? 0;
                $cumulativeTotals['ecomm_female']['4145'] += $weekReportEntry->getEcommFemaleAge4145() ?? 0;
                $cumulativeTotals['ecomm_female']['4650'] += $weekReportEntry->getEcommFemaleAge4650() ?? 0;
                $cumulativeTotals['ecomm_female']['50above'] += $weekReportEntry->getEcommFemaleAge50Above() ?? 0;
                
                // MONT
                $cumulativeTotals['mont_male']['2125'] += $weekReportEntry->getMontMaleAge2125() ?? 0;
                $cumulativeTotals['mont_male']['2630'] += $weekReportEntry->getMontMaleAge2630() ?? 0;
                $cumulativeTotals['mont_male']['3135'] += $weekReportEntry->getMontMaleAge3135() ?? 0;
                $cumulativeTotals['mont_male']['3640'] += $weekReportEntry->getMontMaleAge3640() ?? 0;
                $cumulativeTotals['mont_male']['4145'] += $weekReportEntry->getMontMaleAge4145() ?? 0;
                $cumulativeTotals['mont_male']['4650'] += $weekReportEntry->getMontMaleAge4650() ?? 0;
                $cumulativeTotals['mont_male']['50above'] += $weekReportEntry->getMontMaleAge50Above() ?? 0;
                
                $cumulativeTotals['mont_female']['2125'] += $weekReportEntry->getMontFemaleAge2125() ?? 0;
                $cumulativeTotals['mont_female']['2630'] += $weekReportEntry->getMontFemaleAge2630() ?? 0;
                $cumulativeTotals['mont_female']['3135'] += $weekReportEntry->getMontFemaleAge3135() ?? 0;
                $cumulativeTotals['mont_female']['3640'] += $weekReportEntry->getMontFemaleAge3640() ?? 0;
                $cumulativeTotals['mont_female']['4145'] += $weekReportEntry->getMontFemaleAge4145() ?? 0;
                $cumulativeTotals['mont_female']['4650'] += $weekReportEntry->getMontFemaleAge4650() ?? 0;
                $cumulativeTotals['mont_female']['50above'] += $weekReportEntry->getMontFemaleAge50Above() ?? 0;
                
                // S99
                $cumulativeTotals['s99_male']['2125'] += $weekReportEntry->getS99MaleAge2125() ?? 0;
                $cumulativeTotals['s99_male']['2630'] += $weekReportEntry->getS99MaleAge2630() ?? 0;
                $cumulativeTotals['s99_male']['3135'] += $weekReportEntry->getS99MaleAge3135() ?? 0;
                $cumulativeTotals['s99_male']['3640'] += $weekReportEntry->getS99MaleAge3640() ?? 0;
                $cumulativeTotals['s99_male']['4145'] += $weekReportEntry->getS99MaleAge4145() ?? 0;
                $cumulativeTotals['s99_male']['4650'] += $weekReportEntry->getS99MaleAge4650() ?? 0;
                $cumulativeTotals['s99_male']['50above'] += $weekReportEntry->getS99MaleAge50Above() ?? 0;
                
                $cumulativeTotals['s99_female']['2125'] += $weekReportEntry->getS99FemaleAge2125() ?? 0;
                $cumulativeTotals['s99_female']['2630'] += $weekReportEntry->getS99FemaleAge2630() ?? 0;
                $cumulativeTotals['s99_female']['3135'] += $weekReportEntry->getS99FemaleAge3135() ?? 0;
                $cumulativeTotals['s99_female']['3640'] += $weekReportEntry->getS99FemaleAge3640() ?? 0;
                $cumulativeTotals['s99_female']['4145'] += $weekReportEntry->getS99FemaleAge4145() ?? 0;
                $cumulativeTotals['s99_female']['4650'] += $weekReportEntry->getS99FemaleAge4650() ?? 0;
                $cumulativeTotals['s99_female']['50above'] += $weekReportEntry->getS99FemaleAge50Above() ?? 0;
                
                // SHM
                $cumulativeTotals['shm_male']['2125'] += $weekReportEntry->getShmMaleAge2125() ?? 0;
                $cumulativeTotals['shm_male']['2630'] += $weekReportEntry->getShmMaleAge2630() ?? 0;
                $cumulativeTotals['shm_male']['3135'] += $weekReportEntry->getShmMaleAge3135() ?? 0;
                $cumulativeTotals['shm_male']['3640'] += $weekReportEntry->getShmMaleAge3640() ?? 0;
                $cumulativeTotals['shm_male']['4145'] += $weekReportEntry->getShmMaleAge4145() ?? 0;
                $cumulativeTotals['shm_male']['4650'] += $weekReportEntry->getShmMaleAge4650() ?? 0;
                $cumulativeTotals['shm_male']['50above'] += $weekReportEntry->getShmMaleAge50Above() ?? 0;
                
                $cumulativeTotals['shm_female']['2125'] += $weekReportEntry->getShmFemaleAge2125() ?? 0;
                $cumulativeTotals['shm_female']['2630'] += $weekReportEntry->getShmFemaleAge2630() ?? 0;
                $cumulativeTotals['shm_female']['3135'] += $weekReportEntry->getShmFemaleAge3135() ?? 0;
                $cumulativeTotals['shm_female']['3640'] += $weekReportEntry->getShmFemaleAge3640() ?? 0;
                $cumulativeTotals['shm_female']['4145'] += $weekReportEntry->getShmFemaleAge4145() ?? 0;
                $cumulativeTotals['shm_female']['4650'] += $weekReportEntry->getShmFemaleAge4650() ?? 0;
                $cumulativeTotals['shm_female']['50above'] += $weekReportEntry->getShmFemaleAge50Above() ?? 0;
                
                // TOFT
                $cumulativeTotals['toft_male']['2125'] += $weekReportEntry->getToftMaleAge2125() ?? 0;
                $cumulativeTotals['toft_male']['2630'] += $weekReportEntry->getToftMaleAge2630() ?? 0;
                $cumulativeTotals['toft_male']['3135'] += $weekReportEntry->getToftMaleAge3135() ?? 0;
                $cumulativeTotals['toft_male']['3640'] += $weekReportEntry->getToftMaleAge3640() ?? 0;
                $cumulativeTotals['toft_male']['4145'] += $weekReportEntry->getToftMaleAge4145() ?? 0;
                $cumulativeTotals['toft_male']['4650'] += $weekReportEntry->getToftMaleAge4650() ?? 0;
                $cumulativeTotals['toft_male']['50above'] += $weekReportEntry->getToftMaleAge50Above() ?? 0;
                
                $cumulativeTotals['toft_female']['2125'] += $weekReportEntry->getToftFemaleAge2125() ?? 0;
                $cumulativeTotals['toft_female']['2630'] += $weekReportEntry->getToftFemaleAge2630() ?? 0;
                $cumulativeTotals['toft_female']['3135'] += $weekReportEntry->getToftFemaleAge3135() ?? 0;
                $cumulativeTotals['toft_female']['3640'] += $weekReportEntry->getToftFemaleAge3640() ?? 0;
                $cumulativeTotals['toft_female']['4145'] += $weekReportEntry->getToftFemaleAge4145() ?? 0;
                $cumulativeTotals['toft_female']['4650'] += $weekReportEntry->getToftFemaleAge4650() ?? 0;
                $cumulativeTotals['toft_female']['50above'] += $weekReportEntry->getToftFemaleAge50Above() ?? 0;
                
                // TONT
                $cumulativeTotals['tont_male']['2125'] += $weekReportEntry->getTontMaleAge2125() ?? 0;
                $cumulativeTotals['tont_male']['2630'] += $weekReportEntry->getTontMaleAge2630() ?? 0;
                $cumulativeTotals['tont_male']['3135'] += $weekReportEntry->getTontMaleAge3135() ?? 0;
                $cumulativeTotals['tont_male']['3640'] += $weekReportEntry->getTontMaleAge3640() ?? 0;
                $cumulativeTotals['tont_male']['4145'] += $weekReportEntry->getTontMaleAge4145() ?? 0;
                $cumulativeTotals['tont_male']['4650'] += $weekReportEntry->getTontMaleAge4650() ?? 0;
                $cumulativeTotals['tont_male']['50above'] += $weekReportEntry->getTontMaleAge50Above() ?? 0;
                
                $cumulativeTotals['tont_female']['2125'] += $weekReportEntry->getTontFemaleAge2125() ?? 0;
                $cumulativeTotals['tont_female']['2630'] += $weekReportEntry->getTontFemaleAge2630() ?? 0;
                $cumulativeTotals['tont_female']['3135'] += $weekReportEntry->getTontFemaleAge3135() ?? 0;
                $cumulativeTotals['tont_female']['3640'] += $weekReportEntry->getTontFemaleAge3640() ?? 0;
                $cumulativeTotals['tont_female']['4145'] += $weekReportEntry->getTontFemaleAge4145() ?? 0;
                $cumulativeTotals['tont_female']['4650'] += $weekReportEntry->getTontFemaleAge4650() ?? 0;
                $cumulativeTotals['tont_female']['50above'] += $weekReportEntry->getTontFemaleAge50Above() ?? 0;
            }
        }
        
        // Row 36: CVS Male across all age groups (cumulative totals)
        $worksheet->setCellValue('C36', $cumulativeTotals['cvs_male']['2125']);    // Column C: 21-25
        $worksheet->setCellValue('D36', $cumulativeTotals['cvs_male']['2630']);    // Column D: 26-30
        $worksheet->setCellValue('E36', $cumulativeTotals['cvs_male']['3135']);    // Column E: 31-35
        $worksheet->setCellValue('F36', $cumulativeTotals['cvs_male']['3640']);    // Column F: 36-40
        $worksheet->setCellValue('G36', $cumulativeTotals['cvs_male']['4145']);    // Column G: 41-45
        $worksheet->setCellValue('H36', $cumulativeTotals['cvs_male']['4650']);    // Column H: 46-50
        $worksheet->setCellValue('I36', $cumulativeTotals['cvs_male']['50above']); // Column I: 50+
        
        // Row 37: CVS Female across all age groups (cumulative totals)
        $worksheet->setCellValue('C37', $cumulativeTotals['cvs_female']['2125']);
        $worksheet->setCellValue('D37', $cumulativeTotals['cvs_female']['2630']);
        $worksheet->setCellValue('E37', $cumulativeTotals['cvs_female']['3135']);
        $worksheet->setCellValue('F37', $cumulativeTotals['cvs_female']['3640']);
        $worksheet->setCellValue('G37', $cumulativeTotals['cvs_female']['4145']);
        $worksheet->setCellValue('H37', $cumulativeTotals['cvs_female']['4650']);
        $worksheet->setCellValue('I37', $cumulativeTotals['cvs_female']['50above']);
        
        // Row 38: ECOMM Male across all age groups (cumulative totals)
        $worksheet->setCellValue('C38', $cumulativeTotals['ecomm_male']['2125']);
        $worksheet->setCellValue('D38', $cumulativeTotals['ecomm_male']['2630']);
        $worksheet->setCellValue('E38', $cumulativeTotals['ecomm_male']['3135']);
        $worksheet->setCellValue('F38', $cumulativeTotals['ecomm_male']['3640']);
        $worksheet->setCellValue('G38', $cumulativeTotals['ecomm_male']['4145']);
        $worksheet->setCellValue('H38', $cumulativeTotals['ecomm_male']['4650']);
        $worksheet->setCellValue('I38', $cumulativeTotals['ecomm_male']['50above']);
        
        // Row 39: ECOMM Female across all age groups (cumulative totals)
        $worksheet->setCellValue('C39', $cumulativeTotals['ecomm_female']['2125']);
        $worksheet->setCellValue('D39', $cumulativeTotals['ecomm_female']['2630']);
        $worksheet->setCellValue('E39', $cumulativeTotals['ecomm_female']['3135']);
        $worksheet->setCellValue('F39', $cumulativeTotals['ecomm_female']['3640']);
        $worksheet->setCellValue('G39', $cumulativeTotals['ecomm_female']['4145']);
        $worksheet->setCellValue('H39', $cumulativeTotals['ecomm_female']['4650']);
        $worksheet->setCellValue('I39', $cumulativeTotals['ecomm_female']['50above']);
        
        // Row 40: MONT Male across all age groups (cumulative totals)
        $worksheet->setCellValue('C40', $cumulativeTotals['mont_male']['2125']);
        $worksheet->setCellValue('D40', $cumulativeTotals['mont_male']['2630']);
        $worksheet->setCellValue('E40', $cumulativeTotals['mont_male']['3135']);
        $worksheet->setCellValue('F40', $cumulativeTotals['mont_male']['3640']);
        $worksheet->setCellValue('G40', $cumulativeTotals['mont_male']['4145']);
        $worksheet->setCellValue('H40', $cumulativeTotals['mont_male']['4650']);
        $worksheet->setCellValue('I40', $cumulativeTotals['mont_male']['50above']);
        
        // Row 41: MONT Female across all age groups (cumulative totals)
        $worksheet->setCellValue('C41', $cumulativeTotals['mont_female']['2125']);
        $worksheet->setCellValue('D41', $cumulativeTotals['mont_female']['2630']);
        $worksheet->setCellValue('E41', $cumulativeTotals['mont_female']['3135']);
        $worksheet->setCellValue('F41', $cumulativeTotals['mont_female']['3640']);
        $worksheet->setCellValue('G41', $cumulativeTotals['mont_female']['4145']);
        $worksheet->setCellValue('H41', $cumulativeTotals['mont_female']['4650']);
        $worksheet->setCellValue('I41', $cumulativeTotals['mont_female']['50above']);
        
        // Row 42: S99 Male across all age groups (cumulative totals)
        $worksheet->setCellValue('C42', $cumulativeTotals['s99_male']['2125']);
        $worksheet->setCellValue('D42', $cumulativeTotals['s99_male']['2630']);
        $worksheet->setCellValue('E42', $cumulativeTotals['s99_male']['3135']);
        $worksheet->setCellValue('F42', $cumulativeTotals['s99_male']['3640']);
        $worksheet->setCellValue('G42', $cumulativeTotals['s99_male']['4145']);
        $worksheet->setCellValue('H42', $cumulativeTotals['s99_male']['4650']);
        $worksheet->setCellValue('I42', $cumulativeTotals['s99_male']['50above']);
        
        // Row 43: S99 Female across all age groups (cumulative totals)
        $worksheet->setCellValue('C43', $cumulativeTotals['s99_female']['2125']);
        $worksheet->setCellValue('D43', $cumulativeTotals['s99_female']['2630']);
        $worksheet->setCellValue('E43', $cumulativeTotals['s99_female']['3135']);
        $worksheet->setCellValue('F43', $cumulativeTotals['s99_female']['3640']);
        $worksheet->setCellValue('G43', $cumulativeTotals['s99_female']['4145']);
        $worksheet->setCellValue('H43', $cumulativeTotals['s99_female']['4650']);
        $worksheet->setCellValue('I43', $cumulativeTotals['s99_female']['50above']);
        
        // Row 44: SHM Male across all age groups (cumulative totals)
        $worksheet->setCellValue('C44', $cumulativeTotals['shm_male']['2125']);
        $worksheet->setCellValue('D44', $cumulativeTotals['shm_male']['2630']);
        $worksheet->setCellValue('E44', $cumulativeTotals['shm_male']['3135']);
        $worksheet->setCellValue('F44', $cumulativeTotals['shm_male']['3640']);
        $worksheet->setCellValue('G44', $cumulativeTotals['shm_male']['4145']);
        $worksheet->setCellValue('H44', $cumulativeTotals['shm_male']['4650']);
        $worksheet->setCellValue('I44', $cumulativeTotals['shm_male']['50above']);
        
        // Row 45: SHM Female across all age groups (cumulative totals)
        $worksheet->setCellValue('C45', $cumulativeTotals['shm_female']['2125']);
        $worksheet->setCellValue('D45', $cumulativeTotals['shm_female']['2630']);
        $worksheet->setCellValue('E45', $cumulativeTotals['shm_female']['3135']);
        $worksheet->setCellValue('F45', $cumulativeTotals['shm_female']['3640']);
        $worksheet->setCellValue('G45', $cumulativeTotals['shm_female']['4145']);
        $worksheet->setCellValue('H45', $cumulativeTotals['shm_female']['4650']);
        $worksheet->setCellValue('I45', $cumulativeTotals['shm_female']['50above']);
        
        // Row 46: TOFT Male across all age groups (cumulative totals)
        $worksheet->setCellValue('C46', $cumulativeTotals['toft_male']['2125']);
        $worksheet->setCellValue('D46', $cumulativeTotals['toft_male']['2630']);
        $worksheet->setCellValue('E46', $cumulativeTotals['toft_male']['3135']);
        $worksheet->setCellValue('F46', $cumulativeTotals['toft_male']['3640']);
        $worksheet->setCellValue('G46', $cumulativeTotals['toft_male']['4145']);
        $worksheet->setCellValue('H46', $cumulativeTotals['toft_male']['4650']);
        $worksheet->setCellValue('I46', $cumulativeTotals['toft_male']['50above']);
        
        // Row 47: TOFT Female across all age groups (cumulative totals)
        $worksheet->setCellValue('C47', $cumulativeTotals['toft_female']['2125']);
        $worksheet->setCellValue('D47', $cumulativeTotals['toft_female']['2630']);
        $worksheet->setCellValue('E47', $cumulativeTotals['toft_female']['3135']);
        $worksheet->setCellValue('F47', $cumulativeTotals['toft_female']['3640']);
        $worksheet->setCellValue('G47', $cumulativeTotals['toft_female']['4145']);
        $worksheet->setCellValue('H47', $cumulativeTotals['toft_female']['4650']);
        $worksheet->setCellValue('I47', $cumulativeTotals['toft_female']['50above']);
        
        // Row 48: TONT Male across all age groups (cumulative totals)
        $worksheet->setCellValue('C48', $cumulativeTotals['tont_male']['2125']);
        $worksheet->setCellValue('D48', $cumulativeTotals['tont_male']['2630']);
        $worksheet->setCellValue('E48', $cumulativeTotals['tont_male']['3135']);
        $worksheet->setCellValue('F48', $cumulativeTotals['tont_male']['3640']);
        $worksheet->setCellValue('G48', $cumulativeTotals['tont_male']['4145']);
        $worksheet->setCellValue('H48', $cumulativeTotals['tont_male']['4650']);
        $worksheet->setCellValue('I48', $cumulativeTotals['tont_male']['50above']);
        
        // Row 49: TONT Female across all age groups (cumulative totals)
        $worksheet->setCellValue('C49', $cumulativeTotals['tont_female']['2125']);
        $worksheet->setCellValue('D49', $cumulativeTotals['tont_female']['2630']);
        $worksheet->setCellValue('E49', $cumulativeTotals['tont_female']['3135']);
        $worksheet->setCellValue('F49', $cumulativeTotals['tont_female']['3640']);
        $worksheet->setCellValue('G49', $cumulativeTotals['tont_female']['4145']);
        $worksheet->setCellValue('H49', $cumulativeTotals['tont_female']['4650']);
        $worksheet->setCellValue('I49', $cumulativeTotals['tont_female']['50above']);

        
        $output->writeln('<fg=green>Week ' . $weekNumber . ' data populated successfully in column ' . $columnLetter . '</fg=green>');
    }

    /**
     * Populate the "data for pivot" sheet directly in the worksheet
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet
     * @param ReportEntry $reportEntry
     * @param int $weekNumber
     * @param OutputInterface $output
     */
    private function populatePivotSheetInPlace($worksheet, ReportEntry $reportEntry, int $currentWeekNumber, OutputInterface $output): void
    {
        $output->writeln('<fg=yellow>Populating pivot data sheet for weeks 1 to ' . $currentWeekNumber . '</fg=yellow>');
        
        // Determine current year
        $currentYear = date('Y');
        $yearCode = 'Y' . substr($currentYear, 2); // Y24 or Y25
        
        // Get the highest row number
        $highestRow = $worksheet->getHighestRow();
        
        // Populate data for each week from 1 to current week
        for ($weekNum = 1; $weekNum <= $currentWeekNumber; $weekNum++) {
            $this->populatePivotWeekData($worksheet, $weekNum, $yearCode, $highestRow, $output);
        }
        
        $output->writeln('<fg=green>Multi-week pivot data population completed successfully!</fg=green>');
    }
    
    /**
     * Populate pivot data for a specific week
     *
     * @param $worksheet
     * @param int $weekNumber
     * @param string $yearCode
     * @param int $highestRow
     * @param OutputInterface $output
     */
    private function populatePivotWeekData($worksheet, int $weekNumber, string $yearCode, int $highestRow, OutputInterface $output): void
    {
        $output->writeln('<fg=yellow>Populating pivot data for week ' . $weekNumber . '</fg=yellow>');
        
        // Get ReportEntry data for this specific week
        $reportEntry = $this->manager->getRepository(ReportEntry::class)->findOneBy(['week_number' => $weekNumber]);
        
        if (!$reportEntry) {
            $output->writeln('<fg=red>No ReportEntry found for week ' . $weekNumber . ', skipping pivot data...</fg=red>');
            return;
        }
        
        $weekCode = 'W' . $weekNumber; // W1, W2, etc.
        
        $output->writeln('<fg=cyan>Looking for year: ' . $yearCode . ', week: ' . $weekCode . '</fg=cyan>');
        
        // Define channel data mapping
        $channelData = [
            'CVS' => $reportEntry->getCvsTotal() ?? 0,
            'ECOMM' => $reportEntry->getEcommTotal() ?? 0,
            'MONT' => $reportEntry->getMontTotal() ?? 0,
            'S99' => $reportEntry->getS99Total() ?? 0,
            'SHM' => $reportEntry->getShmTotal() ?? 0,
            'TOFT' => $reportEntry->getToftTotal() ?? 0,
            'TONT' => $reportEntry->getTontTotal() ?? 0
        ];
        
        // Calculate total for the week
        $weekTotal = array_sum($channelData);
        
        // Iterate through all rows to find matching year/week combinations
        for ($row = 1; $row <= $highestRow; $row++) {
            $rowYear = $worksheet->getCell('A' . $row)->getValue();
            $rowWeek = $worksheet->getCell('B' . $row)->getValue();
            $rowChannel = $worksheet->getCell('C' . $row)->getValue();
            
            // Match year and week
            if ($rowYear === $yearCode && $rowWeek === $weekCode) {
                // Handle individual channel data
                if (isset($channelData[$rowChannel])) {
                    $worksheet->setCellValue('D' . $row, $channelData[$rowChannel]);
                    $output->writeln('<fg=green>  Updated row ' . $row . ': ' . $rowYear . ' ' . $rowWeek . ' ' . $rowChannel . ': ' . $channelData[$rowChannel] . '</fg=green>');
                }
                // Handle total row (channel will be like "Y24 total" or "Y25 Total")
                elseif (strpos($rowChannel, 'total') !== false || strpos($rowChannel, 'Total') !== false) {
                    $worksheet->setCellValue('D' . $row, $weekTotal);
                    $output->writeln('<fg=green>  Updated row ' . $row . ': ' . $rowYear . ' ' . $rowWeek . ' total: ' . $weekTotal . '</fg=green>');
                }
            }
        }
    }

    /**
     * Prepare data for Excel export
     *
     * @param ReportEntry $reportEntry
     * @param array $reportByState
     * @param array $reportBySku
     * @param int $weekNumber
     * @return array
     */
    private function prepareExcelData(ReportEntry $reportEntry, array $reportByState, array $reportBySku, int $weekNumber): array
    {
        $data = [];
        
        // Summary Data Sheet
        $data['Summary'] = [
            ['Week Number', $weekNumber],
            ['Generated On', date('Y-m-d H:i:s')],
            [''],
            ['Channel', 'Total', 'Valid', 'Invalid', 'Pending'],
            ['MONT', $reportEntry->getMontTotal(), $reportEntry->getMontValid(), $reportEntry->getMontInvalid(), $reportEntry->getMontPending()],
            ['CVS', $reportEntry->getCvsTotal(), $reportEntry->getCvsValid(), $reportEntry->getCvsInvalid(), $reportEntry->getCvsPending()],
            ['S99', $reportEntry->getS99Total(), $reportEntry->getS99Valid(), $reportEntry->getS99Invalid(), $reportEntry->getS99Pending()],
            ['SHM', $reportEntry->getShmTotal(), $reportEntry->getShmValid(), $reportEntry->getShmInvalid(), $reportEntry->getShmPending()],
            ['TONT', $reportEntry->getTontTotal(), $reportEntry->getTontValid(), $reportEntry->getTontInvalid(), $reportEntry->getTontPending()],
            ['ECOMM', $reportEntry->getEcommTotal(), $reportEntry->getEcommValid(), $reportEntry->getEcommInvalid(), $reportEntry->getEcommPending()],
        ];

        // Gender Data
        $data['Gender Analysis'] = [
            ['Channel', 'Male', 'Female'],
            ['MONT', $reportEntry->getMaleEntryMont(), $reportEntry->getFemaleEntryMont()],
            ['CVS', $reportEntry->getMaleEntryCvs(), $reportEntry->getFemaleEntryCvs()],
            ['S99', $reportEntry->getMaleEntryS99(), $reportEntry->getFemaleEntryS99()],
            ['SHM', $reportEntry->getMaleEntryShm(), $reportEntry->getFemaleEntryShm()],
            ['TONT', $reportEntry->getMaleEntryTont(), $reportEntry->getFemaleEntryTont()],
            ['ECOMM', $reportEntry->getMaleEntryEcomm(), $reportEntry->getFemaleEntryEcomm()],
        ];

        // Age Group Data for MONT
        $data['Age Groups - MONT'] = [
            ['Age Group', 'Count'],
            ['21-25', $reportEntry->getMontAge2125()],
            ['26-30', $reportEntry->getMontAge2630()],
            ['31-35', $reportEntry->getMontAge3135()],
            ['36-40', $reportEntry->getMontAge3640()],
            ['41-45', $reportEntry->getMontAge4145()],
            ['46-50', $reportEntry->getMontAge4650()],
            ['50+', $reportEntry->getMontAge50Above()],
        ];

        // State Data
        $stateData = [['State', 'City', 'Channel', 'Entries']];
        foreach ($reportByState as $stateReport) {
            $stateData[] = [
                $stateReport->getState(),
                $stateReport->getCity(),
                $stateReport->getChannel(),
                $stateReport->getEntries()
            ];
        }
        $data['Data by State'] = $stateData;

        // SKU Data
        $skuData = [['SKU Name', 'Channel', 'Quantity']];
        foreach ($reportBySku as $skuReport) {
            $skuData[] = [
                $skuReport->getSkuName(),
                $skuReport->getChannel(),
                $skuReport->getQuantity()
            ];
        }
        $data['Data by SKU'] = $skuData;

        // Pivot Data (for pivot table analysis)
        $pivotData = [['Channel', 'State', 'City', 'SKU', 'Quantity', 'Status', 'Gender', 'Age_Group']];
        
        // This is a simplified pivot data structure - you can enhance this based on your needs
        foreach ($reportByState as $stateReport) {
            $pivotData[] = [
                $stateReport->getChannel(),
                $stateReport->getState(),
                $stateReport->getCity(),
                'N/A', // SKU would need to be joined from submissions
                $stateReport->getEntries(),
                'Total',
                'N/A', // Gender would need to be joined
                'N/A'  // Age would need to be joined
            ];
        }
        $data['Data for Pivot'] = $pivotData;

        return $data;
    }
}