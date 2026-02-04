<?php
 
namespace App\Entity;

use App\Repository\ReportEntryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReportEntryRepository::class)]
class ReportEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $week_number = null;

    #[ORM\Column(nullable: true)]
    private ?int $shm_valid = null;

    #[ORM\Column(nullable: true)]
    private ?int $shm_invalid = null;

    #[ORM\Column(nullable: true)]
    private ?int $shm_pending = null;

    #[ORM\Column(nullable: true)]
    private ?int $shm_total = null;

    #[ORM\Column(nullable: true)]
    private ?int $shm_age_21_25 = null;

    #[ORM\Column(nullable: true)]
    private ?int $shm_age_26_30 = null;

    #[ORM\Column(nullable: true)]
    private ?int $shm_age_31_35 = null;

    #[ORM\Column(nullable: true)]
    private ?int $shm_age_36_40 = null;

    #[ORM\Column(nullable: true)]
    private ?int $shm_age_41_45 = null;

    #[ORM\Column(nullable: true)]
    private ?int $shm_age_46_50 = null;

    #[ORM\Column(nullable: true)]
    private ?int $shm_age_50_above = null;

    #[ORM\Column(nullable: true)]
    private ?int $inv_shm_total = null;

    #[ORM\Column(nullable: true)]
    private ?int $inv_shm_redeem = null;

    #[ORM\Column(nullable: true)]
    private ?int $inv_shm_left = null;

    #[ORM\Column(nullable: true)]
    private ?int $del_shm_process = null;

    #[ORM\Column(nullable: true)]
    private ?int $del_shm_out = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason1_shm = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason2_shm = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason3_shm = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason4_shm = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason5_shm = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason6_shm = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason7_shm = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason8_shm = null;

    #[ORM\Column(nullable: true)]
    private ?int $male_entry_shm = null;

    #[ORM\Column(nullable: true)]
    private ?int $female_entry_shm = null;

    // SHM Gender-Age combinations
    #[ORM\Column(nullable: true)]
    private ?int $shm_male_age_21_25 = null;

    #[ORM\Column(nullable: true)]
    private ?int $shm_male_age_26_30 = null;

    #[ORM\Column(nullable: true)]
    private ?int $shm_male_age_31_35 = null;

    #[ORM\Column(nullable: true)]
    private ?int $shm_male_age_36_40 = null;

    #[ORM\Column(nullable: true)]
    private ?int $shm_male_age_41_45 = null;

    #[ORM\Column(nullable: true)]
    private ?int $shm_male_age_46_50 = null;

    #[ORM\Column(nullable: true)]
    private ?int $shm_male_age_50_above = null;

    #[ORM\Column(nullable: true)]
    private ?int $shm_female_age_21_25 = null;

    #[ORM\Column(nullable: true)]
    private ?int $shm_female_age_26_30 = null;

    #[ORM\Column(nullable: true)]
    private ?int $shm_female_age_31_35 = null;

    #[ORM\Column(nullable: true)]
    private ?int $shm_female_age_36_40 = null;

    #[ORM\Column(nullable: true)]
    private ?int $shm_female_age_41_45 = null;

    #[ORM\Column(nullable: true)]
    private ?int $shm_female_age_46_50 = null;

    #[ORM\Column(nullable: true)]
    private ?int $shm_female_age_50_above = null;

    //S99
    #[ORM\Column(nullable: true)]
    private ?int $s99_valid = null;

    #[ORM\Column(nullable: true)]
    private ?int $s99_invalid = null;

    #[ORM\Column(nullable: true)]
    private ?int $s99_pending = null;

    #[ORM\Column(nullable: true)]
    private ?int $s99_total = null;

    #[ORM\Column(nullable: true)]
    private ?int $s99_age_21_25 = null;

    #[ORM\Column(nullable: true)]
    private ?int $s99_age_26_30 = null;

    #[ORM\Column(nullable: true)]
    private ?int $s99_age_31_35 = null;

    #[ORM\Column(nullable: true)]
    private ?int $s99_age_36_40 = null;

    #[ORM\Column(nullable: true)]
    private ?int $s99_age_41_45 = null;

    #[ORM\Column(nullable: true)]
    private ?int $s99_age_46_50 = null;

    #[ORM\Column(nullable: true)]
    private ?int $s99_age_50_above = null;

    #[ORM\Column(nullable: true)]
    private ?int $inv_s99_total = null;

    #[ORM\Column(nullable: true)]
    private ?int $inv_s99_redeem = null;

    #[ORM\Column(nullable: true)]
    private ?int $inv_s99_left = null;

    #[ORM\Column(nullable: true)]
    private ?int $del_s99_process = null;

    #[ORM\Column(nullable: true)]
    private ?int $del_s99_out = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason1_s99 = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason2_s99 = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason3_s99 = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason4_s99 = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason5_s99 = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason6_s99 = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason7_s99 = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason8_s99 = null;

    #[ORM\Column(nullable: true)]
    private ?int $male_entry_s99 = null;

    #[ORM\Column(nullable: true)]
    private ?int $female_entry_s99 = null;

    // S99 Gender-Age combinations
    #[ORM\Column(nullable: true)]
    private ?int $s99_male_age_21_25 = null;

    #[ORM\Column(nullable: true)]
    private ?int $s99_male_age_26_30 = null;

    #[ORM\Column(nullable: true)]
    private ?int $s99_male_age_31_35 = null;

    #[ORM\Column(nullable: true)]
    private ?int $s99_male_age_36_40 = null;

    #[ORM\Column(nullable: true)]
    private ?int $s99_male_age_41_45 = null;

    #[ORM\Column(nullable: true)]
    private ?int $s99_male_age_46_50 = null;

    #[ORM\Column(nullable: true)]
    private ?int $s99_male_age_50_above = null;

    #[ORM\Column(nullable: true)]
    private ?int $s99_female_age_21_25 = null;

    #[ORM\Column(nullable: true)]
    private ?int $s99_female_age_26_30 = null;

    #[ORM\Column(nullable: true)]
    private ?int $s99_female_age_31_35 = null;

    #[ORM\Column(nullable: true)]
    private ?int $s99_female_age_36_40 = null;

    #[ORM\Column(nullable: true)]
    private ?int $s99_female_age_41_45 = null;

    #[ORM\Column(nullable: true)]
    private ?int $s99_female_age_46_50 = null;

    #[ORM\Column(nullable: true)]
    private ?int $s99_female_age_50_above = null;

    //MONT
    #[ORM\Column(nullable: true)]
    private ?int $mont_valid = null;

    #[ORM\Column(nullable: true)]
    private ?int $mont_invalid = null;

    #[ORM\Column(nullable: true)]
    private ?int $mont_pending = null;

    #[ORM\Column(nullable: true)]
    private ?int $mont_total = null;

    #[ORM\Column(nullable: true)]
    private ?int $mont_age_21_25 = null;

    #[ORM\Column(nullable: true)]
    private ?int $mont_age_26_30 = null;

    #[ORM\Column(nullable: true)]
    private ?int $mont_age_31_35 = null;

    #[ORM\Column(nullable: true)]
    private ?int $mont_age_36_40 = null;

    #[ORM\Column(nullable: true)]
    private ?int $mont_age_41_45 = null;

    #[ORM\Column(nullable: true)]
    private ?int $mont_age_46_50 = null;

    #[ORM\Column(nullable: true)]
    private ?int $mont_age_50_above = null;

    #[ORM\Column(nullable: true)]
    private ?int $inv_mont_total = null;

    #[ORM\Column(nullable: true)]
    private ?int $inv_mont_redeem = null;

    #[ORM\Column(nullable: true)]
    private ?int $inv_mont_left = null;

    #[ORM\Column(nullable: true)]
    private ?int $del_mont_process = null;

    #[ORM\Column(nullable: true)]
    private ?int $del_mont_out = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason1_mont = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason2_mont = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason3_mont = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason4_mont = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason5_mont = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason6_mont = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason7_mont = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason8_mont = null;

    #[ORM\Column(nullable: true)]
    private ?int $male_entry_mont = null;

    #[ORM\Column(nullable: true)]
    private ?int $female_entry_mont = null;

    // MONT Gender-Age combinations
    #[ORM\Column(nullable: true)]
    private ?int $mont_male_age_21_25 = null;

    #[ORM\Column(nullable: true)]
    private ?int $mont_male_age_26_30 = null;

    #[ORM\Column(nullable: true)]
    private ?int $mont_male_age_31_35 = null;

    #[ORM\Column(nullable: true)]
    private ?int $mont_male_age_36_40 = null;

    #[ORM\Column(nullable: true)]
    private ?int $mont_male_age_41_45 = null;

    #[ORM\Column(nullable: true)]
    private ?int $mont_male_age_46_50 = null;

    #[ORM\Column(nullable: true)]
    private ?int $mont_male_age_50_above = null;

    #[ORM\Column(nullable: true)]
    private ?int $mont_female_age_21_25 = null;

    #[ORM\Column(nullable: true)]
    private ?int $mont_female_age_26_30 = null;

    #[ORM\Column(nullable: true)]
    private ?int $mont_female_age_31_35 = null;

    #[ORM\Column(nullable: true)]
    private ?int $mont_female_age_36_40 = null;

    #[ORM\Column(nullable: true)]
    private ?int $mont_female_age_41_45 = null;

    #[ORM\Column(nullable: true)]
    private ?int $mont_female_age_46_50 = null;

    #[ORM\Column(nullable: true)]
    private ?int $mont_female_age_50_above = null;

    //TONT
    #[ORM\Column(nullable: true)]
    private ?int $tont_valid = null;

    #[ORM\Column(nullable: true)]
    private ?int $tont_invalid = null;

    #[ORM\Column(nullable: true)]
    private ?int $tont_pending = null;

    #[ORM\Column(nullable: true)]
    private ?int $tont_total = null;

    #[ORM\Column(nullable: true)]
    private ?int $tont_age_21_25 = null;

    #[ORM\Column(nullable: true)]
    private ?int $tont_age_26_30 = null;

    #[ORM\Column(nullable: true)]
    private ?int $tont_age_31_35 = null;

    #[ORM\Column(nullable: true)]
    private ?int $tont_age_36_40 = null;

    #[ORM\Column(nullable: true)]
    private ?int $tont_age_41_45 = null;

    #[ORM\Column(nullable: true)]
    private ?int $tont_age_46_50 = null;

    #[ORM\Column(nullable: true)]
    private ?int $tont_age_50_above = null;

    #[ORM\Column(nullable: true)]
    private ?int $inv_tont_total = null;

    #[ORM\Column(nullable: true)]
    private ?int $inv_tont_redeem = null;

    #[ORM\Column(nullable: true)]
    private ?int $inv_tont_left = null;

    #[ORM\Column(nullable: true)]
    private ?int $del_tont_process = null;

    #[ORM\Column(nullable: true)]
    private ?int $del_tont_out = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason1_tont = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason2_tont = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason3_tont = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason4_tont = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason5_tont = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason6_tont = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason7_tont = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason8_tont = null;

    #[ORM\Column(nullable: true)]
    private ?int $male_entry_tont = null;

    #[ORM\Column(nullable: true)]
    private ?int $female_entry_tont = null;

    // TONT Gender-Age combinations
    #[ORM\Column(nullable: true)]
    private ?int $tont_male_age_21_25 = null;

    #[ORM\Column(nullable: true)]
    private ?int $tont_male_age_26_30 = null;

    #[ORM\Column(nullable: true)]
    private ?int $tont_male_age_31_35 = null;

    #[ORM\Column(nullable: true)]
    private ?int $tont_male_age_36_40 = null;

    #[ORM\Column(nullable: true)]
    private ?int $tont_male_age_41_45 = null;

    #[ORM\Column(nullable: true)]
    private ?int $tont_male_age_46_50 = null;

    #[ORM\Column(nullable: true)]
    private ?int $tont_male_age_50_above = null;

    #[ORM\Column(nullable: true)]
    private ?int $tont_female_age_21_25 = null;

    #[ORM\Column(nullable: true)]
    private ?int $tont_female_age_26_30 = null;

    #[ORM\Column(nullable: true)]
    private ?int $tont_female_age_31_35 = null;

    #[ORM\Column(nullable: true)]
    private ?int $tont_female_age_36_40 = null;

    #[ORM\Column(nullable: true)]
    private ?int $tont_female_age_41_45 = null;

    #[ORM\Column(nullable: true)]
    private ?int $tont_female_age_46_50 = null;

    #[ORM\Column(nullable: true)]
    private ?int $tont_female_age_50_above = null;

    //CVS
    #[ORM\Column(nullable: true)]
    private ?int $cvs_valid = null;

    #[ORM\Column(nullable: true)]
    private ?int $cvs_invalid = null;

    #[ORM\Column(nullable: true)]
    private ?int $cvs_pending = null;

    #[ORM\Column(nullable: true)]
    private ?int $cvs_total = null;

    #[ORM\Column(nullable: true)]
    private ?int $cvs_age_21_25 = null;

    #[ORM\Column(nullable: true)]
    private ?int $cvs_age_26_30 = null;

    #[ORM\Column(nullable: true)]
    private ?int $cvs_age_31_35 = null;

    #[ORM\Column(nullable: true)]
    private ?int $cvs_age_36_40 = null;

    #[ORM\Column(nullable: true)]
    private ?int $cvs_age_41_45 = null;

    #[ORM\Column(nullable: true)]
    private ?int $cvs_age_46_50 = null;

    #[ORM\Column(nullable: true)]
    private ?int $cvs_age_50_above = null;

    #[ORM\Column(nullable: true)]
    private ?int $inv_cvs_total = null;

    #[ORM\Column(nullable: true)]
    private ?int $inv_cvs_redeem = null;

    #[ORM\Column(nullable: true)]
    private ?int $inv_cvs_left = null;

    #[ORM\Column(nullable: true)]
    private ?int $del_cvs_process = null;

    #[ORM\Column(nullable: true)]
    private ?int $del_cvs_out = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason1_cvs = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason2_cvs = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason3_cvs = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason4_cvs = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason5_cvs = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason6_cvs = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason7_cvs = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason8_cvs = null;

    #[ORM\Column(nullable: true)]
    private ?int $male_entry_cvs = null;

    #[ORM\Column(nullable: true)]
    private ?int $female_entry_cvs = null;

    // CVS Gender-Age combinations
    #[ORM\Column(nullable: true)]
    private ?int $cvs_male_age_21_25 = null;

    #[ORM\Column(nullable: true)]
    private ?int $cvs_male_age_26_30 = null;

    #[ORM\Column(nullable: true)]
    private ?int $cvs_male_age_31_35 = null;

    #[ORM\Column(nullable: true)]
    private ?int $cvs_male_age_36_40 = null;

    #[ORM\Column(nullable: true)]
    private ?int $cvs_male_age_41_45 = null;

    #[ORM\Column(nullable: true)]
    private ?int $cvs_male_age_46_50 = null;

    #[ORM\Column(nullable: true)]
    private ?int $cvs_male_age_50_above = null;

    #[ORM\Column(nullable: true)]
    private ?int $cvs_female_age_21_25 = null;

    #[ORM\Column(nullable: true)]
    private ?int $cvs_female_age_26_30 = null;

    #[ORM\Column(nullable: true)]
    private ?int $cvs_female_age_31_35 = null;

    #[ORM\Column(nullable: true)]
    private ?int $cvs_female_age_36_40 = null;

    #[ORM\Column(nullable: true)]
    private ?int $cvs_female_age_41_45 = null;

    #[ORM\Column(nullable: true)]
    private ?int $cvs_female_age_46_50 = null;

    #[ORM\Column(nullable: true)]
    private ?int $cvs_female_age_50_above = null;

    //TOFT
    #[ORM\Column(nullable: true)]
    private ?int $toft_valid = null;

    #[ORM\Column(nullable: true)]
    private ?int $toft_invalid = null;

    #[ORM\Column(nullable: true)]
    private ?int $toft_pending = null;

    #[ORM\Column(nullable: true)]
    private ?int $toft_total = null;

    #[ORM\Column(nullable: true)]
    private ?int $toft_age_21_25 = null;

    #[ORM\Column(nullable: true)]
    private ?int $toft_age_26_30 = null;

    #[ORM\Column(nullable: true)]
    private ?int $toft_age_31_35 = null;

    #[ORM\Column(nullable: true)]
    private ?int $toft_age_36_40 = null;

    #[ORM\Column(nullable: true)]
    private ?int $toft_age_41_45 = null;

    #[ORM\Column(nullable: true)]
    private ?int $toft_age_46_50 = null;

    #[ORM\Column(nullable: true)]
    private ?int $toft_age_50_above = null;
    
    #[ORM\Column(nullable: true)]
    private ?int $inv_toft_total = null;

    #[ORM\Column(nullable: true)]
    private ?int $inv_toft_redeem = null;

    #[ORM\Column(nullable: true)]
    private ?int $inv_toft_left = null;

    #[ORM\Column(nullable: true)]
    private ?int $del_toft_process = null;

    #[ORM\Column(nullable: true)]
    private ?int $del_toft_out = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason1_toft = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason2_toft = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason3_toft = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason4_toft = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason5_toft = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason6_toft = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason7_toft = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason8_toft = null;

    #[ORM\Column(nullable: true)]
    private ?int $male_entry_toft = null;

    #[ORM\Column(nullable: true)]
    private ?int $female_entry_toft = null;

    // TOFT Gender-Age combinations
    #[ORM\Column(nullable: true)]
    private ?int $toft_male_age_21_25 = null;

    #[ORM\Column(nullable: true)]
    private ?int $toft_male_age_26_30 = null;

    #[ORM\Column(nullable: true)]
    private ?int $toft_male_age_31_35 = null;

    #[ORM\Column(nullable: true)]
    private ?int $toft_male_age_36_40 = null;

    #[ORM\Column(nullable: true)]
    private ?int $toft_male_age_41_45 = null;

    #[ORM\Column(nullable: true)]
    private ?int $toft_male_age_46_50 = null;

    #[ORM\Column(nullable: true)]
    private ?int $toft_male_age_50_above = null;

    #[ORM\Column(nullable: true)]
    private ?int $toft_female_age_21_25 = null;

    #[ORM\Column(nullable: true)]
    private ?int $toft_female_age_26_30 = null;

    #[ORM\Column(nullable: true)]
    private ?int $toft_female_age_31_35 = null;

    #[ORM\Column(nullable: true)]
    private ?int $toft_female_age_36_40 = null;

    #[ORM\Column(nullable: true)]
    private ?int $toft_female_age_41_45 = null;

    #[ORM\Column(nullable: true)]
    private ?int $toft_female_age_46_50 = null;

    #[ORM\Column(nullable: true)]
    private ?int $toft_female_age_50_above = null;

    //ECOMM
    #[ORM\Column(nullable: true)]
    private ?int $ecomm_valid = null;

    #[ORM\Column(nullable: true)]
    private ?int $ecomm_invalid = null;

    #[ORM\Column(nullable: true)]
    private ?int $ecomm_pending = null;

    #[ORM\Column(nullable: true)]
    private ?int $ecomm_total = null;

    #[ORM\Column(nullable: true)]
    private ?int $ecomm_age_21_25 = null;

    #[ORM\Column(nullable: true)]
    private ?int $ecomm_age_26_30 = null;

    #[ORM\Column(nullable: true)]
    private ?int $ecomm_age_31_35 = null;

    #[ORM\Column(nullable: true)]
    private ?int $ecomm_age_36_40 = null;

    #[ORM\Column(nullable: true)]
    private ?int $ecomm_age_41_45 = null;

    #[ORM\Column(nullable: true)]
    private ?int $ecomm_age_46_50 = null;

    #[ORM\Column(nullable: true)]
    private ?int $ecomm_age_50_above = null;
    
    #[ORM\Column(nullable: true)]
    private ?int $inv_ecomm_total = null;

    #[ORM\Column(nullable: true)]
    private ?int $inv_ecomm_redeem = null;

    #[ORM\Column(nullable: true)]
    private ?int $inv_ecomm_left = null;

    #[ORM\Column(nullable: true)]
    private ?int $del_ecomm_process = null;

    #[ORM\Column(nullable: true)]
    private ?int $del_ecomm_out = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason1_ecomm = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason2_ecomm = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason3_ecomm = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason4_ecomm = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason5_ecomm = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason6_ecomm = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason7_ecomm = null;

    #[ORM\Column(nullable: true)]
    private ?int $reject_reason8_ecomm = null;

    #[ORM\Column(nullable: true)]
    private ?int $male_entry_ecomm = null;

    #[ORM\Column(nullable: true)]
    private ?int $female_entry_ecomm = null;

    // ECOMM Gender-Age combinations
    #[ORM\Column(nullable: true)]
    private ?int $ecomm_male_age_21_25 = null;

    #[ORM\Column(nullable: true)]
    private ?int $ecomm_male_age_26_30 = null;

    #[ORM\Column(nullable: true)]
    private ?int $ecomm_male_age_31_35 = null;

    #[ORM\Column(nullable: true)]
    private ?int $ecomm_male_age_36_40 = null;

    #[ORM\Column(nullable: true)]
    private ?int $ecomm_male_age_41_45 = null;

    #[ORM\Column(nullable: true)]
    private ?int $ecomm_male_age_46_50 = null;

    #[ORM\Column(nullable: true)]
    private ?int $ecomm_male_age_50_above = null;

    #[ORM\Column(nullable: true)]
    private ?int $ecomm_female_age_21_25 = null;

    #[ORM\Column(nullable: true)]
    private ?int $ecomm_female_age_26_30 = null;

    #[ORM\Column(nullable: true)]
    private ?int $ecomm_female_age_31_35 = null;

    #[ORM\Column(nullable: true)]
    private ?int $ecomm_female_age_36_40 = null;

    #[ORM\Column(nullable: true)]
    private ?int $ecomm_female_age_41_45 = null;

    #[ORM\Column(nullable: true)]
    private ?int $ecomm_female_age_46_50 = null;

    #[ORM\Column(nullable: true)]
    private ?int $ecomm_female_age_50_above = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWeekNumber(): ?int
    {
        return $this->week_number;
    }

    public function setWeekNumber(?int $week_number): static
    {
        $this->week_number = $week_number;

        return $this;
    }

    public function getShmValid(): ?int
    {
        return $this->shm_valid;
    }

    public function setShmValid(?int $shm_valid): static
    {
        $this->shm_valid = $shm_valid;

        return $this;
    }

    public function getShmInvalid(): ?int
    {
        return $this->shm_invalid;
    }

    public function setShmInvalid(?int $shm_invalid): static
    {
        $this->shm_invalid = $shm_invalid;

        return $this;
    }

    public function getShmPending(): ?int
    {
        return $this->shm_pending;
    }

    public function setShmPending(?int $shm_pending): static
    {
        $this->shm_pending = $shm_pending;

        return $this;
    }

    public function getShmTotal(): ?int
    {
        return $this->shm_total;
    }

    public function setShmTotal(?int $shm_total): static
    {
        $this->shm_total = $shm_total;

        return $this;
    }

    public function getShmAge2125(): ?int
    {
        return $this->shm_age_21_25;
    }

    public function setShmAge2125(?int $shm_age_21_25): static
    {
        $this->shm_age_21_25 = $shm_age_21_25;

        return $this;
    }

    public function getShmAge2630(): ?int
    {
        return $this->shm_age_26_30;
    }

    public function setShmAge2630(?int $shm_age_26_30): static
    {
        $this->shm_age_26_30 = $shm_age_26_30;

        return $this;
    }

    public function getShmAge3135(): ?int
    {
        return $this->shm_age_31_35;
    }

    public function setShmAge3135(?int $shm_age_31_35): static
    {
        $this->shm_age_31_35 = $shm_age_31_35;

        return $this;
    }

    public function getShmAge3640(): ?int
    {
        return $this->shm_age_36_40;
    }

    public function setShmAge3640(?int $shm_age_36_40): static
    {
        $this->shm_age_36_40 = $shm_age_36_40;

        return $this;
    }

    public function getShmAge4145(): ?int
    {
        return $this->shm_age_41_45;
    }

    public function setShmAge4145(?int $shm_age_41_45): static
    {
        $this->shm_age_41_45 = $shm_age_41_45;

        return $this;
    }

    public function getShmAge4650(): ?int
    {
        return $this->shm_age_46_50;
    }

    public function setShmAge4650(?int $shm_age_46_50): static
    {
        $this->shm_age_46_50 = $shm_age_46_50;

        return $this;
    }

    public function getShmAge50Above(): ?int
    {
        return $this->shm_age_50_above;
    }

    public function setShmAge50Above(?int $shm_age_50_above): static
    {
        $this->shm_age_50_above = $shm_age_50_above;

        return $this;
    }

    public function getInvShmTotal(): ?int
    {
        return $this->inv_shm_total;
    }

    public function setInvShmTotal(?int $inv_shm_total): static
    {
        $this->inv_shm_total = $inv_shm_total;

        return $this;
    }

    public function getInvShmRedeem(): ?int
    {
        return $this->inv_shm_redeem;
    }

    public function setInvShmRedeem(?int $inv_shm_redeem): static
    {
        $this->inv_shm_redeem = $inv_shm_redeem;

        return $this;
    }

    public function getInvShmLeft(): ?int
    {
        return $this->inv_shm_left;
    }

    public function setInvShmLeft(?int $inv_shm_left): static
    {
        $this->inv_shm_left = $inv_shm_left;

        return $this;
    }

    public function getDelShmProcess(): ?int
    {
        return $this->del_shm_process;
    }

    public function setDelShmProcess(?int $del_shm_process): static
    {
        $this->del_shm_process = $del_shm_process;

        return $this;
    }

    public function getDelShmOut(): ?int
    {
        return $this->del_shm_out;
    }

    public function setDelShmOut(?int $del_shm_out): static
    {
        $this->del_shm_out = $del_shm_out;

        return $this;
    }

    public function getRejectReason1Shm(): ?int
    {
        return $this->reject_reason1_shm;
    }

    public function setRejectReason1Shm(?int $reject_reason1_shm): static
    {
        $this->reject_reason1_shm = $reject_reason1_shm;

        return $this;
    }

    public function getRejectReason2Shm(): ?int
    {
        return $this->reject_reason2_shm;
    }

    public function setRejectReason2Shm(?int $reject_reason2_shm): static
    {
        $this->reject_reason2_shm = $reject_reason2_shm;

        return $this;
    }

    public function getRejectReason3Shm(): ?int
    {
        return $this->reject_reason3_shm;
    }

    public function setRejectReason3Shm(?int $reject_reason3_shm): static
    {
        $this->reject_reason3_shm = $reject_reason3_shm;

        return $this;
    }

    public function getRejectReason4Shm(): ?int
    {
        return $this->reject_reason4_shm;
    }

    public function setRejectReason4Shm(?int $reject_reason4_shm): static
    {
        $this->reject_reason4_shm = $reject_reason4_shm;

        return $this;
    }

    public function getRejectReason5Shm(): ?int
    {
        return $this->reject_reason5_shm;
    }

    public function setRejectReason5Shm(?int $reject_reason5_shm): static
    {
        $this->reject_reason5_shm = $reject_reason5_shm;

        return $this;
    }

    public function getRejectReason6Shm(): ?int
    {
        return $this->reject_reason6_shm;
    }

    public function setRejectReason6Shm(?int $reject_reason6_shm): static
    {
        $this->reject_reason6_shm = $reject_reason6_shm;

        return $this;
    }

    public function getRejectReason7Shm(): ?int
    {
        return $this->reject_reason7_shm;
    }

    public function setRejectReason7Shm(?int $reject_reason7_shm): static
    {
        $this->reject_reason7_shm = $reject_reason7_shm;

        return $this;
    }

    public function getRejectReason8Shm(): ?int
    {
        return $this->reject_reason8_shm;
    }

    public function setRejectReason8Shm(?int $reject_reason8_shm): static
    {
        $this->reject_reason8_shm = $reject_reason8_shm;

        return $this;
    }

    public function getMaleEntryShm(): ?int
    {
        return $this->male_entry_shm;
    }

    public function setMaleEntryShm(?int $male_entry_shm): static
    {
        $this->male_entry_shm = $male_entry_shm;

        return $this;
    }

    public function getFemaleEntryShm(): ?int
    {
        return $this->female_entry_shm;
    }

    public function setFemaleEntryShm(?int $female_entry_shm): static
    {
        $this->female_entry_shm = $female_entry_shm;

        return $this;
    }

    //S99
    public function getS99Valid(): ?int
    {
        return $this->s99_valid;
    }

    public function setS99Valid(?int $s99_valid): static
    {
        $this->s99_valid = $s99_valid;

        return $this;
    }

    public function getS99Invalid(): ?int
    {
        return $this->s99_invalid;
    }

    public function setS99Invalid(?int $s99_invalid): static
    {
        $this->s99_invalid = $s99_invalid;

        return $this;
    }

    public function getS99Pending(): ?int
    {
        return $this->s99_pending;
    }

    public function setS99Pending(?int $s99_pending): static
    {
        $this->s99_pending = $s99_pending;

        return $this;
    }

    public function getS99Total(): ?int
    {
        return $this->s99_total;
    }

    public function setS99Total(?int $s99_total): static
    {
        $this->s99_total = $s99_total;

        return $this;
    }

    public function getS99Age2125(): ?int
    {
        return $this->s99_age_21_25;
    }

    public function setS99Age2125(?int $s99_age_21_25): static
    {
        $this->s99_age_21_25 = $s99_age_21_25;

        return $this;
    }

    public function getS99Age2630(): ?int
    {
        return $this->s99_age_26_30;
    }

    public function setS99Age2630(?int $s99_age_26_30): static
    {
        $this->s99_age_26_30 = $s99_age_26_30;

        return $this;
    }

    public function getS99Age3135(): ?int
    {
        return $this->s99_age_31_35;
    }

    public function setS99Age3135(?int $s99_age_31_35): static
    {
        $this->s99_age_31_35 = $s99_age_31_35;

        return $this;
    }

    public function getS99Age3640(): ?int
    {
        return $this->s99_age_36_40;
    }

    public function setS99Age3640(?int $s99_age_36_40): static
    {
        $this->s99_age_36_40 = $s99_age_36_40;

        return $this;
    }

    public function getS99Age4145(): ?int
    {
        return $this->s99_age_41_45;
    }

    public function setS99Age4145(?int $s99_age_41_45): static
    {
        $this->s99_age_41_45 = $s99_age_41_45;

        return $this;
    }

    public function getS99Age4650(): ?int
    {
        return $this->s99_age_46_50;
    }

    public function setS99Age4650(?int $s99_age_46_50): static
    {
        $this->s99_age_46_50 = $s99_age_46_50;

        return $this;
    }

    public function getS99Age50Above(): ?int
    {
        return $this->s99_age_50_above;
    }

    public function setS99Age50Above(?int $s99_age_50_above): static
    {
        $this->s99_age_50_above = $s99_age_50_above;

        return $this;
    }

    public function getInvS99Total(): ?int
    {
        return $this->inv_s99_total;
    }

    public function setInvS99Total(?int $inv_s99_total): static
    {
        $this->inv_s99_total = $inv_s99_total;

        return $this;
    }

    public function getInvS99Redeem(): ?int
    {
        return $this->inv_s99_redeem;
    }

    public function setInvS99Redeem(?int $inv_s99_redeem): static
    {
        $this->inv_s99_redeem = $inv_s99_redeem;

        return $this;
    }

    public function getInvS99Left(): ?int
    {
        return $this->inv_s99_left;
    }

    public function setInvS99Left(?int $inv_s99_left): static
    {
        $this->inv_s99_left = $inv_s99_left;

        return $this;
    }

    public function getDelS99Process(): ?int
    {
        return $this->del_s99_process;
    }

    public function setDelS99Process(?int $del_s99_process): static
    {
        $this->del_s99_process = $del_s99_process;

        return $this;
    }

    public function getDelS99Out(): ?int
    {
        return $this->del_s99_out;
    }

    public function setDelS99Out(?int $del_s99_out): static
    {
        $this->del_s99_out = $del_s99_out;

        return $this;
    }

    public function getRejectReason1S99(): ?int
    {
        return $this->reject_reason1_s99;
    }

    public function setRejectReason1S99(?int $reject_reason1_s99): static
    {
        $this->reject_reason1_s99 = $reject_reason1_s99;

        return $this;
    }

    public function getRejectReason2S99(): ?int
    {
        return $this->reject_reason2_s99;
    }

    public function setRejectReason2S99(?int $reject_reason2_s99): static
    {
        $this->reject_reason2_s99 = $reject_reason2_s99;

        return $this;
    }

    public function getRejectReason3S99(): ?int
    {
        return $this->reject_reason3_s99;
    }

    public function setRejectReason3S99(?int $reject_reason3_s99): static
    {
        $this->reject_reason3_s99 = $reject_reason3_s99;

        return $this;
    }

    public function getRejectReason4S99(): ?int
    {
        return $this->reject_reason4_s99;
    }

    public function setRejectReason4S99(?int $reject_reason4_s99): static
    {
        $this->reject_reason4_s99 = $reject_reason4_s99;

        return $this;
    }

    public function getRejectReason5S99(): ?int
    {
        return $this->reject_reason5_s99;
    }

    public function setRejectReason5S99(?int $reject_reason5_s99): static
    {
        $this->reject_reason5_s99 = $reject_reason5_s99;

        return $this;
    }

    public function getRejectReason6S99(): ?int
    {
        return $this->reject_reason6_s99;
    }

    public function setRejectReason6S99(?int $reject_reason6_s99): static
    {
        $this->reject_reason6_s99 = $reject_reason6_s99;

        return $this;
    }

    public function getRejectReason7S99(): ?int
    {
        return $this->reject_reason7_s99;
    }

    public function setRejectReason7S99(?int $reject_reason7_s99): static
    {
        $this->reject_reason7_s99 = $reject_reason7_s99;

        return $this;
    }

    public function getRejectReason8S99(): ?int
    {
        return $this->reject_reason8_s99;
    }

    public function setRejectReason8S99(?int $reject_reason8_s99): static
    {
        $this->reject_reason8_s99 = $reject_reason8_s99;

        return $this;
    }

    public function getMaleEntryS99(): ?int
    {
        return $this->male_entry_s99;
    }

    public function setMaleEntryS99(?int $male_entry_s99): static
    {
        $this->male_entry_s99 = $male_entry_s99;

        return $this;
    }

    public function getFemaleEntryS99(): ?int
    {
        return $this->female_entry_s99;
    }

    public function setFemaleEntryS99(?int $female_entry_s99): static
    {
        $this->female_entry_s99 = $female_entry_s99;

        return $this;
    }

    //MONT
    public function getMontValid(): ?int
    {
        return $this->mont_valid;
    }

    public function setMontValid(?int $mont_valid): static
    {
        $this->mont_valid = $mont_valid;

        return $this;
    }

    public function getMontInvalid(): ?int
    {
        return $this->mont_invalid;
    }

    public function setMontInvalid(?int $mont_invalid): static
    {
        $this->mont_invalid = $mont_invalid;

        return $this;
    }

    public function getMontPending(): ?int
    {
        return $this->mont_pending;
    }

    public function setMontPending(?int $mont_pending): static
    {
        $this->mont_pending = $mont_pending;

        return $this;
    }

    public function getMontTotal(): ?int
    {
        return $this->mont_total;
    }

    public function setMontTotal(?int $mont_total): static
    {
        $this->mont_total = $mont_total;

        return $this;
    }

    public function getMontAge2125(): ?int
    {
        return $this->mont_age_21_25;
    }

    public function setMontAge2125(?int $mont_age_21_25): static
    {
        $this->mont_age_21_25 = $mont_age_21_25;

        return $this;
    }

    public function getMontAge2630(): ?int
    {
        return $this->mont_age_26_30;
    }

    public function setMontAge2630(?int $mont_age_26_30): static
    {
        $this->mont_age_26_30 = $mont_age_26_30;

        return $this;
    }

    public function getMontAge3135(): ?int
    {
        return $this->mont_age_31_35;
    }

    public function setMontAge3135(?int $mont_age_31_35): static
    {
        $this->mont_age_31_35 = $mont_age_31_35;

        return $this;
    }

    public function getMontAge3640(): ?int
    {
        return $this->mont_age_36_40;
    }

    public function setMontAge3640(?int $mont_age_36_40): static
    {
        $this->mont_age_36_40 = $mont_age_36_40;

        return $this;
    }

    public function getMontAge4145(): ?int
    {
        return $this->mont_age_41_45;
    }

    public function setMontAge4145(?int $mont_age_41_45): static
    {
        $this->mont_age_41_45 = $mont_age_41_45;

        return $this;
    }

    public function getMontAge4650(): ?int
    {
        return $this->mont_age_46_50;
    }

    public function setMontAge4650(?int $mont_age_46_50): static
    {
        $this->mont_age_46_50 = $mont_age_46_50;

        return $this;
    }

    public function getMontAge50Above(): ?int
    {
        return $this->mont_age_50_above;
    }

    public function setMontAge50Above(?int $mont_age_50_above): static
    {
        $this->mont_age_50_above = $mont_age_50_above;

        return $this;
    }
    
    public function getInvMontTotal(): ?int
    {
        return $this->inv_mont_total;
    }

    public function setInvMontTotal(?int $inv_mont_total): static
    {
        $this->inv_mont_total = $inv_mont_total;

        return $this;
    }

    public function getInvMontRedeem(): ?int
    {
        return $this->inv_mont_redeem;
    }

    public function setInvMontRedeem(?int $inv_mont_redeem): static
    {
        $this->inv_mont_redeem = $inv_mont_redeem;

        return $this;
    }

    public function getInvMontLeft(): ?int
    {
        return $this->inv_mont_left;
    }

    public function setInvMontLeft(?int $inv_mont_left): static
    {
        $this->inv_mont_left = $inv_mont_left;

        return $this;
    }

    public function getDelMontProcess(): ?int
    {
        return $this->del_mont_process;
    }

    public function setDelMontProcess(?int $del_mont_process): static
    {
        $this->del_mont_process = $del_mont_process;

        return $this;
    }

    public function getDelMontOut(): ?int
    {
        return $this->del_mont_out;
    }

    public function setDelMontOut(?int $del_mont_out): static
    {
        $this->del_mont_out = $del_mont_out;

        return $this;
    }

    public function getRejectReason1Mont(): ?int
    {
        return $this->reject_reason1_mont;
    }

    public function setRejectReason1Mont(?int $reject_reason1_mont): static
    {
        $this->reject_reason1_mont = $reject_reason1_mont;

        return $this;
    }

    public function getRejectReason2Mont(): ?int
    {
        return $this->reject_reason2_mont;
    }

    public function setRejectReason2Mont(?int $reject_reason2_mont): static
    {
        $this->reject_reason2_mont = $reject_reason2_mont;

        return $this;
    }

    public function getRejectReason3Mont(): ?int
    {
        return $this->reject_reason3_mont;
    }

    public function setRejectReason3Mont(?int $reject_reason3_mont): static
    {
        $this->reject_reason3_mont = $reject_reason3_mont;

        return $this;
    }

    public function getRejectReason4Mont(): ?int
    {
        return $this->reject_reason4_mont;
    }

    public function setRejectReason4Mont(?int $reject_reason4_mont): static
    {
        $this->reject_reason4_mont = $reject_reason4_mont;

        return $this;
    }

    public function getRejectReason5Mont(): ?int
    {
        return $this->reject_reason5_mont;
    }

    public function setRejectReason5Mont(?int $reject_reason5_mont): static
    {
        $this->reject_reason5_mont = $reject_reason5_mont;

        return $this;
    }

    public function getRejectReason6Mont(): ?int
    {
        return $this->reject_reason6_mont;
    }

    public function setRejectReason6Mont(?int $reject_reason6_mont): static
    {
        $this->reject_reason6_mont = $reject_reason6_mont;

        return $this;
    }

    public function getRejectReason7Mont(): ?int
    {
        return $this->reject_reason7_mont;
    }

    public function setRejectReason7Mont(?int $reject_reason7_mont): static
    {
        $this->reject_reason7_mont = $reject_reason7_mont;

        return $this;
    }

    public function getRejectReason8Mont(): ?int
    {
        return $this->reject_reason8_mont;
    }

    public function setRejectReason8Mont(?int $reject_reason8_mont): static
    {
        $this->reject_reason8_mont = $reject_reason8_mont;

        return $this;
    }

    public function getMaleEntryMont(): ?int
    {
        return $this->male_entry_mont;
    }

    public function setMaleEntryMont(?int $male_entry_mont): static
    {
        $this->male_entry_mont = $male_entry_mont;

        return $this;
    }

    public function getFemaleEntryMont(): ?int
    {
        return $this->female_entry_mont;
    }

    public function setFemaleEntryMont(?int $female_entry_mont): static
    {
        $this->female_entry_mont = $female_entry_mont;

        return $this;
    }

    //TONT
    public function getTontValid(): ?int
    {
        return $this->tont_valid;
    }

    public function setTontValid(?int $tont_valid): static
    {
        $this->tont_valid = $tont_valid;

        return $this;
    }

    public function getTontInvalid(): ?int
    {
        return $this->tont_invalid;
    }

    public function setTontInvalid(?int $tont_invalid): static
    {
        $this->tont_invalid = $tont_invalid;

        return $this;
    }

    public function getTontPending(): ?int
    {
        return $this->tont_pending;
    }

    public function setTontPending(?int $tont_pending): static
    {
        $this->tont_pending = $tont_pending;

        return $this;
    }

    public function getTontTotal(): ?int
    {
        return $this->tont_total;
    }

    public function setTontTotal(?int $tont_total): static
    {
        $this->tont_total = $tont_total;

        return $this;
    }

    public function getTontAge2125(): ?int
    {
        return $this->tont_age_21_25;
    }

    public function setTontAge2125(?int $tont_age_21_25): static
    {
        $this->tont_age_21_25 = $tont_age_21_25;

        return $this;
    }

    public function getTontAge2630(): ?int
    {
        return $this->tont_age_26_30;
    }

    public function setTontAge2630(?int $tont_age_26_30): static
    {
        $this->tont_age_26_30 = $tont_age_26_30;

        return $this;
    }

    public function getTontAge3135(): ?int
    {
        return $this->tont_age_31_35;
    }

    public function setTontAge3135(?int $tont_age_31_35): static
    {
        $this->tont_age_31_35 = $tont_age_31_35;

        return $this;
    }

    public function getTontAge3640(): ?int
    {
        return $this->tont_age_36_40;
    }

    public function setTontAge3640(?int $tont_age_36_40): static
    {
        $this->tont_age_36_40 = $tont_age_36_40;

        return $this;
    }

    public function getTontAge4145(): ?int
    {
        return $this->tont_age_41_45;
    }

    public function setTontAge4145(?int $tont_age_41_45): static
    {
        $this->tont_age_41_45 = $tont_age_41_45;

        return $this;
    }

    public function getTontAge4650(): ?int
    {
        return $this->tont_age_46_50;
    }

    public function setTontAge4650(?int $tont_age_46_50): static
    {
        $this->tont_age_46_50 = $tont_age_46_50;

        return $this;
    }

    public function getTontAge50Above(): ?int
    {
        return $this->tont_age_50_above;
    }

    public function setTontAge50Above(?int $tont_age_50_above): static
    {
        $this->tont_age_50_above = $tont_age_50_above;

        return $this;
    }
        
    public function getInvTontTotal(): ?int
    {
        return $this->inv_tont_total;
    }

    public function setInvTontTotal(?int $inv_tont_total): static
    {
        $this->inv_tont_total = $inv_tont_total;

        return $this;
    }

    public function getInvTontRedeem(): ?int
    {
        return $this->inv_tont_redeem;
    }

    public function setInvTontRedeem(?int $inv_tont_redeem): static
    {
        $this->inv_tont_redeem = $inv_tont_redeem;

        return $this;
    }

    public function getInvTontLeft(): ?int
    {
        return $this->inv_tont_left;
    }

    public function setInvTontLeft(?int $inv_tont_left): static
    {
        $this->inv_tont_left = $inv_tont_left;

        return $this;
    }

    public function getDelTontProcess(): ?int
    {
        return $this->del_tont_process;
    }

    public function setDelTontProcess(?int $del_tont_process): static
    {
        $this->del_tont_process = $del_tont_process;

        return $this;
    }

    public function getDelTontOut(): ?int
    {
        return $this->del_tont_out;
    }

    public function setDelTontOut(?int $del_tont_out): static
    {
        $this->del_tont_out = $del_tont_out;

        return $this;
    }

    public function getRejectReason1Tont(): ?int
    {
        return $this->reject_reason1_tont;
    }

    public function setRejectReason1Tont(?int $reject_reason1_tont): static
    {
        $this->reject_reason1_tont = $reject_reason1_tont;

        return $this;
    }

    public function getRejectReason2Tont(): ?int
    {
        return $this->reject_reason2_tont;
    }

    public function setRejectReason2Tont(?int $reject_reason2_tont): static
    {
        $this->reject_reason2_tont = $reject_reason2_tont;

        return $this;
    }

    public function getRejectReason3Tont(): ?int
    {
        return $this->reject_reason3_tont;
    }

    public function setRejectReason3Tont(?int $reject_reason3_tont): static
    {
        $this->reject_reason3_tont = $reject_reason3_tont;

        return $this;
    }

    public function getRejectReason4Tont(): ?int
    {
        return $this->reject_reason4_tont;
    }

    public function setRejectReason4Tont(?int $reject_reason4_tont): static
    {
        $this->reject_reason4_tont = $reject_reason4_tont;

        return $this;
    }

    public function getRejectReason5Tont(): ?int
    {
        return $this->reject_reason5_tont;
    }

    public function setRejectReason5Tont(?int $reject_reason5_tont): static
    {
        $this->reject_reason5_tont = $reject_reason5_tont;

        return $this;
    }

    public function getRejectReason6Tont(): ?int
    {
        return $this->reject_reason6_tont;
    }

    public function setRejectReason6Tont(?int $reject_reason6_tont): static
    {
        $this->reject_reason6_tont = $reject_reason6_tont;

        return $this;
    }

    public function getRejectReason7Tont(): ?int
    {
        return $this->reject_reason7_tont;
    }

    public function setRejectReason7Tont(?int $reject_reason7_tont): static
    {
        $this->reject_reason7_tont = $reject_reason7_tont;

        return $this;
    }

    public function getRejectReason8Tont(): ?int
    {
        return $this->reject_reason8_tont;
    }

    public function setRejectReason8Tont(?int $reject_reason8_tont): static
    {
        $this->reject_reason8_tont = $reject_reason8_tont;

        return $this;
    }

    public function getMaleEntryTont(): ?int
    {
        return $this->male_entry_tont;
    }

    public function setMaleEntryTont(?int $male_entry_tont): static
    {
        $this->male_entry_tont = $male_entry_tont;

        return $this;
    }

    public function getFemaleEntryTont(): ?int
    {
        return $this->female_entry_tont;
    }

    public function setFemaleEntryTont(?int $female_entry_tont): static
    {
        $this->female_entry_tont = $female_entry_tont;

        return $this;
    }

    //CVS
    public function getCvsValid(): ?int
    {
        return $this->cvs_valid;
    }

    public function setCvsValid(?int $cvs_valid): static
    {
        $this->cvs_valid = $cvs_valid;

        return $this;
    }

    public function getCvsInvalid(): ?int
    {
        return $this->cvs_invalid;
    }

    public function setCvsInvalid(?int $cvs_invalid): static
    {
        $this->cvs_invalid = $cvs_invalid;

        return $this;
    }

    public function getCvsPending(): ?int
    {
        return $this->cvs_pending;
    }

    public function setCvsPending(?int $cvs_pending): static
    {
        $this->cvs_pending = $cvs_pending;

        return $this;
    }

    public function getCvsTotal(): ?int
    {
        return $this->cvs_total;
    }

    public function setCvsTotal(?int $cvs_total): static
    {
        $this->cvs_total = $cvs_total;

        return $this;
    }

    public function getCvsAge2125(): ?int
    {
        return $this->cvs_age_21_25;
    }

    public function setCvsAge2125(?int $cvs_age_21_25): static
    {
        $this->cvs_age_21_25 = $cvs_age_21_25;

        return $this;
    }

    public function getCvsAge2630(): ?int
    {
        return $this->cvs_age_26_30;
    }

    public function setCvsAge2630(?int $cvs_age_26_30): static
    {
        $this->cvs_age_26_30 = $cvs_age_26_30;

        return $this;
    }

    public function getCvsAge3135(): ?int
    {
        return $this->cvs_age_31_35;
    }

    public function setCvsAge3135(?int $cvs_age_31_35): static
    {
        $this->cvs_age_31_35 = $cvs_age_31_35;

        return $this;
    }

    public function getCvsAge3640(): ?int
    {
        return $this->cvs_age_36_40;
    }

    public function setCvsAge3640(?int $cvs_age_36_40): static
    {
        $this->cvs_age_36_40 = $cvs_age_36_40;

        return $this;
    }

    public function getCvsAge4145(): ?int
    {
        return $this->cvs_age_41_45;
    }

    public function setCvsAge4145(?int $cvs_age_41_45): static
    {
        $this->cvs_age_41_45 = $cvs_age_41_45;

        return $this;
    }

    public function getCvsAge4650(): ?int
    {
        return $this->cvs_age_46_50;
    }

    public function setCvsAge4650(?int $cvs_age_46_50): static
    {
        $this->cvs_age_46_50 = $cvs_age_46_50;

        return $this;
    }

    public function getCvsAge50Above(): ?int
    {
        return $this->cvs_age_50_above;
    }

    public function setCvsAge50Above(?int $cvs_age_50_above): static
    {
        $this->cvs_age_50_above = $cvs_age_50_above;

        return $this;
    }

    public function getInvCvsTotal(): ?int
    {
        return $this->inv_cvs_total;
    }

    public function setInvCvsTotal(?int $inv_cvs_total): static
    {
        $this->inv_cvs_total = $inv_cvs_total;

        return $this;
    }

    public function getInvCvsRedeem(): ?int
    {
        return $this->inv_cvs_redeem;
    }

    public function setInvCvsRedeem(?int $inv_cvs_redeem): static
    {
        $this->inv_cvs_redeem = $inv_cvs_redeem;

        return $this;
    }

    public function getInvCvsLeft(): ?int
    {
        return $this->inv_cvs_left;
    }

    public function setInvCvsLeft(?int $inv_cvs_left): static
    {
        $this->inv_cvs_left = $inv_cvs_left;

        return $this;
    }

    public function getDelCvsProcess(): ?int
    {
        return $this->del_cvs_process;
    }

    public function setDelCvsProcess(?int $del_cvs_process): static
    {
        $this->del_cvs_process = $del_cvs_process;

        return $this;
    }

    public function getDelCvsOut(): ?int
    {
        return $this->del_cvs_out;
    }

    public function setDelCvsOut(?int $del_cvs_out): static
    {
        $this->del_cvs_out = $del_cvs_out;

        return $this;
    }

    public function getRejectReason1Cvs(): ?int
    {
        return $this->reject_reason1_cvs;
    }

    public function setRejectReason1Cvs(?int $reject_reason1_cvs): static
    {
        $this->reject_reason1_cvs = $reject_reason1_cvs;

        return $this;
    }

    public function getRejectReason2Cvs(): ?int
    {
        return $this->reject_reason2_cvs;
    }

    public function setRejectReason2Cvs(?int $reject_reason2_cvs): static
    {
        $this->reject_reason2_cvs = $reject_reason2_cvs;

        return $this;
    }

    public function getRejectReason3Cvs(): ?int
    {
        return $this->reject_reason3_cvs;
    }

    public function setRejectReason3Cvs(?int $reject_reason3_cvs): static
    {
        $this->reject_reason3_cvs = $reject_reason3_cvs;

        return $this;
    }

    public function getRejectReason4Cvs(): ?int
    {
        return $this->reject_reason4_cvs;
    }

    public function setRejectReason4Cvs(?int $reject_reason4_cvs): static
    {
        $this->reject_reason4_cvs = $reject_reason4_cvs;

        return $this;
    }

    public function getRejectReason5Cvs(): ?int
    {
        return $this->reject_reason5_cvs;
    }

    public function setRejectReason5Cvs(?int $reject_reason5_cvs): static
    {
        $this->reject_reason5_cvs = $reject_reason5_cvs;

        return $this;
    }

    public function getRejectReason6Cvs(): ?int
    {
        return $this->reject_reason6_cvs;
    }

    public function setRejectReason6Cvs(?int $reject_reason6_cvs): static
    {
        $this->reject_reason6_cvs = $reject_reason6_cvs;

        return $this;
    }

    public function getRejectReason7Cvs(): ?int
    {
        return $this->reject_reason7_cvs;
    }

    public function setRejectReason7Cvs(?int $reject_reason7_cvs): static
    {
        $this->reject_reason7_cvs = $reject_reason7_cvs;

        return $this;
    }

    public function getRejectReason8Cvs(): ?int
    {
        return $this->reject_reason8_cvs;
    }

    public function setRejectReason8Cvs(?int $reject_reason8_cvs): static
    {
        $this->reject_reason8_cvs = $reject_reason8_cvs;

        return $this;
    }

    public function getMaleEntryCvs(): ?int
    {
        return $this->male_entry_cvs;
    }

    public function setMaleEntryCvs(?int $male_entry_cvs): static
    {
        $this->male_entry_cvs = $male_entry_cvs;

        return $this;
    }

    public function getFemaleEntryCvs(): ?int
    {
        return $this->female_entry_cvs;
    }

    public function setFemaleEntryCvs(?int $female_entry_cvs): static
    {
        $this->female_entry_cvs = $female_entry_cvs;

        return $this;
    }

    //TOFT
    public function getToftValid(): ?int
    {
        return $this->toft_valid;
    }

    public function setToftValid(?int $toft_valid): static
    {
        $this->toft_valid = $toft_valid;

        return $this;
    }

    public function getToftInvalid(): ?int
    {
        return $this->toft_invalid;
    }

    public function setToftInvalid(?int $toft_invalid): static
    {
        $this->toft_invalid = $toft_invalid;

        return $this;
    }

    public function getToftPending(): ?int
    {
        return $this->toft_pending;
    }

    public function setToftPending(?int $toft_pending): static
    {
        $this->toft_pending = $toft_pending;

        return $this;
    }

    public function getToftTotal(): ?int
    {
        return $this->toft_total;
    }

    public function setToftTotal(?int $toft_total): static
    {
        $this->toft_total = $toft_total;

        return $this;
    }

    public function getToftAge2125(): ?int
    {
        return $this->toft_age_21_25;
    }

    public function setToftAge2125(?int $toft_age_21_25): static
    {
        $this->toft_age_21_25 = $toft_age_21_25;

        return $this;
    }

    public function getToftAge2630(): ?int
    {
        return $this->toft_age_26_30;
    }

    public function setToftAge2630(?int $toft_age_26_30): static
    {
        $this->toft_age_26_30 = $toft_age_26_30;

        return $this;
    }

    public function getToftAge3135(): ?int
    {
        return $this->toft_age_31_35;
    }

    public function setToftAge3135(?int $toft_age_31_35): static
    {
        $this->toft_age_31_35 = $toft_age_31_35;

        return $this;
    }

    public function getToftAge3640(): ?int
    {
        return $this->toft_age_36_40;
    }

    public function setToftAge3640(?int $toft_age_36_40): static
    {
        $this->toft_age_36_40 = $toft_age_36_40;

        return $this;
    }

    public function getToftAge4145(): ?int
    {
        return $this->toft_age_41_45;
    }

    public function setToftAge4145(?int $toft_age_41_45): static
    {
        $this->toft_age_41_45 = $toft_age_41_45;

        return $this;
    }

    public function getToftAge4650(): ?int
    {
        return $this->toft_age_46_50;
    }

    public function setToftAge4650(?int $toft_age_46_50): static
    {
        $this->toft_age_46_50 = $toft_age_46_50;

        return $this;
    }

    public function getToftAge50Above(): ?int
    {
        return $this->toft_age_50_above;
    }

    public function setToftAge50Above(?int $toft_age_50_above): static
    {
        $this->toft_age_50_above = $toft_age_50_above;

        return $this;
    }

    public function getInvToftTotal(): ?int
    {
        return $this->inv_toft_total;
    }

    public function setInvToftTotal(?int $inv_toft_total): static
    {
        $this->inv_toft_total = $inv_toft_total;

        return $this;
    }

    public function getInvToftRedeem(): ?int
    {
        return $this->inv_toft_redeem;
    }

    public function setInvToftRedeem(?int $inv_toft_redeem): static
    {
        $this->inv_toft_redeem = $inv_toft_redeem;

        return $this;
    }

    public function getInvToftLeft(): ?int
    {
        return $this->inv_toft_left;
    }

    public function setInvToftLeft(?int $inv_toft_left): static
    {
        $this->inv_toft_left = $inv_toft_left;

        return $this;
    }

    public function getDelToftProcess(): ?int
    {
        return $this->del_toft_process;
    }

    public function setDelToftProcess(?int $del_toft_process): static
    {
        $this->del_toft_process = $del_toft_process;

        return $this;
    }

    public function getDelToftOut(): ?int
    {
        return $this->del_toft_out;
    }

    public function setDelToftOut(?int $del_toft_out): static
    {
        $this->del_toft_out = $del_toft_out;

        return $this;
    }

    public function getRejectReason1Toft(): ?int
    {
        return $this->reject_reason1_toft;
    }

    public function setRejectReason1Toft(?int $reject_reason1_toft): static
    {
        $this->reject_reason1_toft = $reject_reason1_toft;

        return $this;
    }

    public function getRejectReason2Toft(): ?int
    {
        return $this->reject_reason2_toft;
    }

    public function setRejectReason2Toft(?int $reject_reason2_toft): static
    {
        $this->reject_reason2_toft = $reject_reason2_toft;

        return $this;
    }

    public function getRejectReason3Toft(): ?int
    {
        return $this->reject_reason3_toft;
    }

    public function setRejectReason3Toft(?int $reject_reason3_toft): static
    {
        $this->reject_reason3_toft = $reject_reason3_toft;

        return $this;
    }

    public function getRejectReason4Toft(): ?int
    {
        return $this->reject_reason4_toft;
    }

    public function setRejectReason4Toft(?int $reject_reason4_toft): static
    {
        $this->reject_reason4_toft = $reject_reason4_toft;

        return $this;
    }

    public function getRejectReason5Toft(): ?int
    {
        return $this->reject_reason5_toft;
    }

    public function setRejectReason5Toft(?int $reject_reason5_toft): static
    {
        $this->reject_reason5_toft = $reject_reason5_toft;

        return $this;
    }

    public function getRejectReason6Toft(): ?int
    {
        return $this->reject_reason6_toft;
    }

    public function setRejectReason6Toft(?int $reject_reason6_toft): static
    {
        $this->reject_reason6_toft = $reject_reason6_toft;

        return $this;
    }

    public function getRejectReason7Toft(): ?int
    {
        return $this->reject_reason7_toft;
    }

    public function setRejectReason7Toft(?int $reject_reason7_toft): static
    {
        $this->reject_reason7_toft = $reject_reason7_toft;

        return $this;
    }

    public function getRejectReason8Toft(): ?int
    {
        return $this->reject_reason8_toft;
    }

    public function setRejectReason8Toft(?int $reject_reason8_toft): static
    {
        $this->reject_reason8_toft = $reject_reason8_toft;

        return $this;
    }

    public function getMaleEntryToft(): ?int
    {
        return $this->male_entry_toft;
    }

    public function setMaleEntryToft(?int $male_entry_toft): static
    {
        $this->male_entry_toft = $male_entry_toft;

        return $this;
    }

    public function getFemaleEntryToft(): ?int
    {
        return $this->female_entry_toft;
    }

    public function setFemaleEntryToft(?int $female_entry_toft): static
    {
        $this->female_entry_toft = $female_entry_toft;

        return $this;
    }

    //ECOMM
    public function getEcommValid(): ?int
    {
        return $this->ecomm_valid;
    }

    public function setEcommValid(?int $ecomm_valid): static
    {
        $this->ecomm_valid = $ecomm_valid;

        return $this;
    }

    public function getEcommInvalid(): ?int
    {
        return $this->ecomm_invalid;
    }

    public function setEcommInvalid(?int $ecomm_invalid): static
    {
        $this->ecomm_invalid = $ecomm_invalid;

        return $this;
    }

    public function getEcommPending(): ?int
    {
        return $this->ecomm_pending;
    }

    public function setEcommPending(?int $ecomm_pending): static
    {
        $this->ecomm_pending = $ecomm_pending;

        return $this;
    }

    public function getEcommTotal(): ?int
    {
        return $this->ecomm_total;
    }

    public function setEcommTotal(?int $ecomm_total): static
    {
        $this->ecomm_total = $ecomm_total;

        return $this;
    }

    public function getEcommAge2125(): ?int
    {
        return $this->ecomm_age_21_25;
    }

    public function setEcommAge2125(?int $ecomm_age_21_25): static
    {
        $this->ecomm_age_21_25 = $ecomm_age_21_25;

        return $this;
    }

    public function getEcommAge2630(): ?int
    {
        return $this->ecomm_age_26_30;
    }

    public function setEcommAge2630(?int $ecomm_age_26_30): static
    {
        $this->ecomm_age_26_30 = $ecomm_age_26_30;

        return $this;
    }

    public function getEcommAge3135(): ?int
    {
        return $this->ecomm_age_31_35;
    }

    public function setEcommAge3135(?int $ecomm_age_31_35): static
    {
        $this->ecomm_age_31_35 = $ecomm_age_31_35;

        return $this;
    }

    public function getEcommAge3640(): ?int
    {
        return $this->ecomm_age_36_40;
    }

    public function setEcommAge3640(?int $ecomm_age_36_40): static
    {
        $this->ecomm_age_36_40 = $ecomm_age_36_40;

        return $this;
    }

    public function getEcommAge4145(): ?int
    {
        return $this->ecomm_age_41_45;
    }

    public function setEcommAge4145(?int $ecomm_age_41_45): static
    {
        $this->ecomm_age_41_45 = $ecomm_age_41_45;

        return $this;
    }

    public function getEcommAge4650(): ?int
    {
        return $this->ecomm_age_46_50;
    }

    public function setEcommAge4650(?int $ecomm_age_46_50): static
    {
        $this->ecomm_age_46_50 = $ecomm_age_46_50;

        return $this;
    }

    public function getEcommAge50Above(): ?int
    {
        return $this->ecomm_age_50_above;
    }

    public function setEcommAge50Above(?int $ecomm_age_50_above): static
    {
        $this->ecomm_age_50_above = $ecomm_age_50_above;

        return $this;
    }

    public function getInvEcommTotal(): ?int
    {
        return $this->inv_ecomm_total;
    }

    public function setInvEcommTotal(?int $inv_ecomm_total): static
    {
        $this->inv_ecomm_total = $inv_ecomm_total;

        return $this;
    }

    public function getInvEcommRedeem(): ?int
    {
        return $this->inv_ecomm_redeem;
    }

    public function setInvEcommRedeem(?int $inv_ecomm_redeem): static
    {
        $this->inv_ecomm_redeem = $inv_ecomm_redeem;

        return $this;
    }

    public function getInvEcommLeft(): ?int
    {
        return $this->inv_ecomm_left;
    }

    public function setInvEcommLeft(?int $inv_ecomm_left): static
    {
        $this->inv_ecomm_left = $inv_ecomm_left;

        return $this;
    }

    public function getDelEcommProcess(): ?int
    {
        return $this->del_ecomm_process;
    }

    public function setDelEcommProcess(?int $del_ecomm_process): static
    {
        $this->del_ecomm_process = $del_ecomm_process;

        return $this;
    }

    public function getDelEcommOut(): ?int
    {
        return $this->del_ecomm_out;
    }

    public function setDelEcommOut(?int $del_ecomm_out): static
    {
        $this->del_ecomm_out = $del_ecomm_out;

        return $this;
    }

    public function getRejectReason1Ecomm(): ?int
    {
        return $this->reject_reason1_ecomm;
    }

    public function setRejectReason1Ecomm(?int $reject_reason1_ecomm): static
    {
        $this->reject_reason1_ecomm = $reject_reason1_ecomm;

        return $this;
    }

    public function getRejectReason2Ecomm(): ?int
    {
        return $this->reject_reason2_ecomm;
    }

    public function setRejectReason2Ecomm(?int $reject_reason2_ecomm): static
    {
        $this->reject_reason2_ecomm = $reject_reason2_ecomm;

        return $this;
    }

    public function getRejectReason3Ecomm(): ?int
    {
        return $this->reject_reason3_ecomm;
    }

    public function setRejectReason3Ecomm(?int $reject_reason3_ecomm): static
    {
        $this->reject_reason3_ecomm = $reject_reason3_ecomm;

        return $this;
    }

    public function getRejectReason4Ecomm(): ?int
    {
        return $this->reject_reason4_ecomm;
    }

    public function setRejectReason4Ecomm(?int $reject_reason4_ecomm): static
    {
        $this->reject_reason4_ecomm = $reject_reason4_ecomm;

        return $this;
    }

    public function getRejectReason5Ecomm(): ?int
    {
        return $this->reject_reason5_ecomm;
    }

    public function setRejectReason5Ecomm(?int $reject_reason5_ecomm): static
    {
        $this->reject_reason5_ecomm = $reject_reason5_ecomm;

        return $this;
    }

    public function getRejectReason6Ecomm(): ?int
    {
        return $this->reject_reason6_ecomm;
    }

    public function setRejectReason6Ecomm(?int $reject_reason6_ecomm): static
    {
        $this->reject_reason6_ecomm = $reject_reason6_ecomm;

        return $this;
    }

    public function getRejectReason7Ecomm(): ?int
    {
        return $this->reject_reason7_ecomm;
    }

    public function setRejectReason7Ecomm(?int $reject_reason7_ecomm): static
    {
        $this->reject_reason7_ecomm = $reject_reason7_ecomm;

        return $this;
    }

    public function getRejectReason8Ecomm(): ?int
    {
        return $this->reject_reason8_ecomm;
    }

    public function setRejectReason8Ecomm(?int $reject_reason8_ecomm): static
    {
        $this->reject_reason8_ecomm = $reject_reason8_ecomm;

        return $this;
    }

    public function getMaleEntryEcomm(): ?int
    {
        return $this->male_entry_ecomm;
    }

    public function setMaleEntryEcomm(?int $male_entry_ecomm): static
    {
        $this->male_entry_ecomm = $male_entry_ecomm;

        return $this;
    }

    public function getFemaleEntryEcomm(): ?int
    {
        return $this->female_entry_ecomm;
    }

    public function setFemaleEntryEcomm(?int $female_entry_ecomm): static
    {
        $this->female_entry_ecomm = $female_entry_ecomm;

        return $this;
    }

    // SHM Gender-Age getters and setters
    public function getShmMaleAge2125(): ?int
    {
        return $this->shm_male_age_21_25;
    }

    public function setShmMaleAge2125(?int $shm_male_age_21_25): static
    {
        $this->shm_male_age_21_25 = $shm_male_age_21_25;
        return $this;
    }

    public function getShmMaleAge2630(): ?int
    {
        return $this->shm_male_age_26_30;
    }

    public function setShmMaleAge2630(?int $shm_male_age_26_30): static
    {
        $this->shm_male_age_26_30 = $shm_male_age_26_30;
        return $this;
    }

    public function getShmMaleAge3135(): ?int
    {
        return $this->shm_male_age_31_35;
    }

    public function setShmMaleAge3135(?int $shm_male_age_31_35): static
    {
        $this->shm_male_age_31_35 = $shm_male_age_31_35;
        return $this;
    }

    public function getShmMaleAge3640(): ?int
    {
        return $this->shm_male_age_36_40;
    }

    public function setShmMaleAge3640(?int $shm_male_age_36_40): static
    {
        $this->shm_male_age_36_40 = $shm_male_age_36_40;
        return $this;
    }

    public function getShmMaleAge4145(): ?int
    {
        return $this->shm_male_age_41_45;
    }

    public function setShmMaleAge4145(?int $shm_male_age_41_45): static
    {
        $this->shm_male_age_41_45 = $shm_male_age_41_45;
        return $this;
    }

    public function getShmMaleAge4650(): ?int
    {
        return $this->shm_male_age_46_50;
    }

    public function setShmMaleAge4650(?int $shm_male_age_46_50): static
    {
        $this->shm_male_age_46_50 = $shm_male_age_46_50;
        return $this;
    }

    public function getShmMaleAge50Above(): ?int
    {
        return $this->shm_male_age_50_above;
    }

    public function setShmMaleAge50Above(?int $shm_male_age_50_above): static
    {
        $this->shm_male_age_50_above = $shm_male_age_50_above;
        return $this;
    }

    public function getShmFemaleAge2125(): ?int
    {
        return $this->shm_female_age_21_25;
    }

    public function setShmFemaleAge2125(?int $shm_female_age_21_25): static
    {
        $this->shm_female_age_21_25 = $shm_female_age_21_25;
        return $this;
    }

    public function getShmFemaleAge2630(): ?int
    {
        return $this->shm_female_age_26_30;
    }

    public function setShmFemaleAge2630(?int $shm_female_age_26_30): static
    {
        $this->shm_female_age_26_30 = $shm_female_age_26_30;
        return $this;
    }

    public function getShmFemaleAge3135(): ?int
    {
        return $this->shm_female_age_31_35;
    }

    public function setShmFemaleAge3135(?int $shm_female_age_31_35): static
    {
        $this->shm_female_age_31_35 = $shm_female_age_31_35;
        return $this;
    }

    public function getShmFemaleAge3640(): ?int
    {
        return $this->shm_female_age_36_40;
    }

    public function setShmFemaleAge3640(?int $shm_female_age_36_40): static
    {
        $this->shm_female_age_36_40 = $shm_female_age_36_40;
        return $this;
    }

    public function getShmFemaleAge4145(): ?int
    {
        return $this->shm_female_age_41_45;
    }

    public function setShmFemaleAge4145(?int $shm_female_age_41_45): static
    {
        $this->shm_female_age_41_45 = $shm_female_age_41_45;
        return $this;
    }

    public function getShmFemaleAge4650(): ?int
    {
        return $this->shm_female_age_46_50;
    }

    public function setShmFemaleAge4650(?int $shm_female_age_46_50): static
    {
        $this->shm_female_age_46_50 = $shm_female_age_46_50;
        return $this;
    }

    public function getShmFemaleAge50Above(): ?int
    {
        return $this->shm_female_age_50_above;
    }

    public function setShmFemaleAge50Above(?int $shm_female_age_50_above): static
    {
        $this->shm_female_age_50_above = $shm_female_age_50_above;
        return $this;
    }

    // S99 Gender-Age getters and setters
    public function getS99MaleAge2125(): ?int
    {
        return $this->s99_male_age_21_25;
    }

    public function setS99MaleAge2125(?int $s99_male_age_21_25): static
    {
        $this->s99_male_age_21_25 = $s99_male_age_21_25;
        return $this;
    }

    public function getS99MaleAge2630(): ?int
    {
        return $this->s99_male_age_26_30;
    }

    public function setS99MaleAge2630(?int $s99_male_age_26_30): static
    {
        $this->s99_male_age_26_30 = $s99_male_age_26_30;
        return $this;
    }

    public function getS99MaleAge3135(): ?int
    {
        return $this->s99_male_age_31_35;
    }

    public function setS99MaleAge3135(?int $s99_male_age_31_35): static
    {
        $this->s99_male_age_31_35 = $s99_male_age_31_35;
        return $this;
    }

    public function getS99MaleAge3640(): ?int
    {
        return $this->s99_male_age_36_40;
    }

    public function setS99MaleAge3640(?int $s99_male_age_36_40): static
    {
        $this->s99_male_age_36_40 = $s99_male_age_36_40;
        return $this;
    }

    public function getS99MaleAge4145(): ?int
    {
        return $this->s99_male_age_41_45;
    }

    public function setS99MaleAge4145(?int $s99_male_age_41_45): static
    {
        $this->s99_male_age_41_45 = $s99_male_age_41_45;
        return $this;
    }

    public function getS99MaleAge4650(): ?int
    {
        return $this->s99_male_age_46_50;
    }

    public function setS99MaleAge4650(?int $s99_male_age_46_50): static
    {
        $this->s99_male_age_46_50 = $s99_male_age_46_50;
        return $this;
    }

    public function getS99MaleAge50Above(): ?int
    {
        return $this->s99_male_age_50_above;
    }

    public function setS99MaleAge50Above(?int $s99_male_age_50_above): static
    {
        $this->s99_male_age_50_above = $s99_male_age_50_above;
        return $this;
    }

    public function getS99FemaleAge2125(): ?int
    {
        return $this->s99_female_age_21_25;
    }

    public function setS99FemaleAge2125(?int $s99_female_age_21_25): static
    {
        $this->s99_female_age_21_25 = $s99_female_age_21_25;
        return $this;
    }

    public function getS99FemaleAge2630(): ?int
    {
        return $this->s99_female_age_26_30;
    }

    public function setS99FemaleAge2630(?int $s99_female_age_26_30): static
    {
        $this->s99_female_age_26_30 = $s99_female_age_26_30;
        return $this;
    }

    public function getS99FemaleAge3135(): ?int
    {
        return $this->s99_female_age_31_35;
    }

    public function setS99FemaleAge3135(?int $s99_female_age_31_35): static
    {
        $this->s99_female_age_31_35 = $s99_female_age_31_35;
        return $this;
    }

    public function getS99FemaleAge3640(): ?int
    {
        return $this->s99_female_age_36_40;
    }

    public function setS99FemaleAge3640(?int $s99_female_age_36_40): static
    {
        $this->s99_female_age_36_40 = $s99_female_age_36_40;
        return $this;
    }

    public function getS99FemaleAge4145(): ?int
    {
        return $this->s99_female_age_41_45;
    }

    public function setS99FemaleAge4145(?int $s99_female_age_41_45): static
    {
        $this->s99_female_age_41_45 = $s99_female_age_41_45;
        return $this;
    }

    public function getS99FemaleAge4650(): ?int
    {
        return $this->s99_female_age_46_50;
    }

    public function setS99FemaleAge4650(?int $s99_female_age_46_50): static
    {
        $this->s99_female_age_46_50 = $s99_female_age_46_50;
        return $this;
    }

    public function getS99FemaleAge50Above(): ?int
    {
        return $this->s99_female_age_50_above;
    }

    public function setS99FemaleAge50Above(?int $s99_female_age_50_above): static
    {
        $this->s99_female_age_50_above = $s99_female_age_50_above;
        return $this;
    }

    // MONT Gender-Age getters and setters
    public function getMontMaleAge2125(): ?int
    {
        return $this->mont_male_age_21_25;
    }

    public function setMontMaleAge2125(?int $mont_male_age_21_25): static
    {
        $this->mont_male_age_21_25 = $mont_male_age_21_25;
        return $this;
    }

    public function getMontMaleAge2630(): ?int
    {
        return $this->mont_male_age_26_30;
    }

    public function setMontMaleAge2630(?int $mont_male_age_26_30): static
    {
        $this->mont_male_age_26_30 = $mont_male_age_26_30;
        return $this;
    }

    public function getMontMaleAge3135(): ?int
    {
        return $this->mont_male_age_31_35;
    }

    public function setMontMaleAge3135(?int $mont_male_age_31_35): static
    {
        $this->mont_male_age_31_35 = $mont_male_age_31_35;
        return $this;
    }

    public function getMontMaleAge3640(): ?int
    {
        return $this->mont_male_age_36_40;
    }

    public function setMontMaleAge3640(?int $mont_male_age_36_40): static
    {
        $this->mont_male_age_36_40 = $mont_male_age_36_40;
        return $this;
    }

    public function getMontMaleAge4145(): ?int
    {
        return $this->mont_male_age_41_45;
    }

    public function setMontMaleAge4145(?int $mont_male_age_41_45): static
    {
        $this->mont_male_age_41_45 = $mont_male_age_41_45;
        return $this;
    }

    public function getMontMaleAge4650(): ?int
    {
        return $this->mont_male_age_46_50;
    }

    public function setMontMaleAge4650(?int $mont_male_age_46_50): static
    {
        $this->mont_male_age_46_50 = $mont_male_age_46_50;
        return $this;
    }

    public function getMontMaleAge50Above(): ?int
    {
        return $this->mont_male_age_50_above;
    }

    public function setMontMaleAge50Above(?int $mont_male_age_50_above): static
    {
        $this->mont_male_age_50_above = $mont_male_age_50_above;
        return $this;
    }

    public function getMontFemaleAge2125(): ?int
    {
        return $this->mont_female_age_21_25;
    }

    public function setMontFemaleAge2125(?int $mont_female_age_21_25): static
    {
        $this->mont_female_age_21_25 = $mont_female_age_21_25;
        return $this;
    }

    public function getMontFemaleAge2630(): ?int
    {
        return $this->mont_female_age_26_30;
    }

    public function setMontFemaleAge2630(?int $mont_female_age_26_30): static
    {
        $this->mont_female_age_26_30 = $mont_female_age_26_30;
        return $this;
    }

    public function getMontFemaleAge3135(): ?int
    {
        return $this->mont_female_age_31_35;
    }

    public function setMontFemaleAge3135(?int $mont_female_age_31_35): static
    {
        $this->mont_female_age_31_35 = $mont_female_age_31_35;
        return $this;
    }

    public function getMontFemaleAge3640(): ?int
    {
        return $this->mont_female_age_36_40;
    }

    public function setMontFemaleAge3640(?int $mont_female_age_36_40): static
    {
        $this->mont_female_age_36_40 = $mont_female_age_36_40;
        return $this;
    }

    public function getMontFemaleAge4145(): ?int
    {
        return $this->mont_female_age_41_45;
    }

    public function setMontFemaleAge4145(?int $mont_female_age_41_45): static
    {
        $this->mont_female_age_41_45 = $mont_female_age_41_45;
        return $this;
    }

    public function getMontFemaleAge4650(): ?int
    {
        return $this->mont_female_age_46_50;
    }

    public function setMontFemaleAge4650(?int $mont_female_age_46_50): static
    {
        $this->mont_female_age_46_50 = $mont_female_age_46_50;
        return $this;
    }

    public function getMontFemaleAge50Above(): ?int
    {
        return $this->mont_female_age_50_above;
    }

    public function setMontFemaleAge50Above(?int $mont_female_age_50_above): static
    {
        $this->mont_female_age_50_above = $mont_female_age_50_above;
        return $this;
    }

    // TONT Gender-Age getters and setters
    public function getTontMaleAge2125(): ?int
    {
        return $this->tont_male_age_21_25;
    }

    public function setTontMaleAge2125(?int $tont_male_age_21_25): static
    {
        $this->tont_male_age_21_25 = $tont_male_age_21_25;
        return $this;
    }

    public function getTontMaleAge2630(): ?int
    {
        return $this->tont_male_age_26_30;
    }

    public function setTontMaleAge2630(?int $tont_male_age_26_30): static
    {
        $this->tont_male_age_26_30 = $tont_male_age_26_30;
        return $this;
    }

    public function getTontMaleAge3135(): ?int
    {
        return $this->tont_male_age_31_35;
    }

    public function setTontMaleAge3135(?int $tont_male_age_31_35): static
    {
        $this->tont_male_age_31_35 = $tont_male_age_31_35;
        return $this;
    }

    public function getTontMaleAge3640(): ?int
    {
        return $this->tont_male_age_36_40;
    }

    public function setTontMaleAge3640(?int $tont_male_age_36_40): static
    {
        $this->tont_male_age_36_40 = $tont_male_age_36_40;
        return $this;
    }

    public function getTontMaleAge4145(): ?int
    {
        return $this->tont_male_age_41_45;
    }

    public function setTontMaleAge4145(?int $tont_male_age_41_45): static
    {
        $this->tont_male_age_41_45 = $tont_male_age_41_45;
        return $this;
    }

    public function getTontMaleAge4650(): ?int
    {
        return $this->tont_male_age_46_50;
    }

    public function setTontMaleAge4650(?int $tont_male_age_46_50): static
    {
        $this->tont_male_age_46_50 = $tont_male_age_46_50;
        return $this;
    }

    public function getTontMaleAge50Above(): ?int
    {
        return $this->tont_male_age_50_above;
    }

    public function setTontMaleAge50Above(?int $tont_male_age_50_above): static
    {
        $this->tont_male_age_50_above = $tont_male_age_50_above;
        return $this;
    }

    public function getTontFemaleAge2125(): ?int
    {
        return $this->tont_female_age_21_25;
    }

    public function setTontFemaleAge2125(?int $tont_female_age_21_25): static
    {
        $this->tont_female_age_21_25 = $tont_female_age_21_25;
        return $this;
    }

    public function getTontFemaleAge2630(): ?int
    {
        return $this->tont_female_age_26_30;
    }

    public function setTontFemaleAge2630(?int $tont_female_age_26_30): static
    {
        $this->tont_female_age_26_30 = $tont_female_age_26_30;
        return $this;
    }

    public function getTontFemaleAge3135(): ?int
    {
        return $this->tont_female_age_31_35;
    }

    public function setTontFemaleAge3135(?int $tont_female_age_31_35): static
    {
        $this->tont_female_age_31_35 = $tont_female_age_31_35;
        return $this;
    }

    public function getTontFemaleAge3640(): ?int
    {
        return $this->tont_female_age_36_40;
    }

    public function setTontFemaleAge3640(?int $tont_female_age_36_40): static
    {
        $this->tont_female_age_36_40 = $tont_female_age_36_40;
        return $this;
    }

    public function getTontFemaleAge4145(): ?int
    {
        return $this->tont_female_age_41_45;
    }

    public function setTontFemaleAge4145(?int $tont_female_age_41_45): static
    {
        $this->tont_female_age_41_45 = $tont_female_age_41_45;
        return $this;
    }

    public function getTontFemaleAge4650(): ?int
    {
        return $this->tont_female_age_46_50;
    }

    public function setTontFemaleAge4650(?int $tont_female_age_46_50): static
    {
        $this->tont_female_age_46_50 = $tont_female_age_46_50;
        return $this;
    }

    public function getTontFemaleAge50Above(): ?int
    {
        return $this->tont_female_age_50_above;
    }

    public function setTontFemaleAge50Above(?int $tont_female_age_50_above): static
    {
        $this->tont_female_age_50_above = $tont_female_age_50_above;
        return $this;
    }

    // CVS Gender-Age getters and setters
    public function getCvsMaleAge2125(): ?int
    {
        return $this->cvs_male_age_21_25;
    }

    public function setCvsMaleAge2125(?int $cvs_male_age_21_25): static
    {
        $this->cvs_male_age_21_25 = $cvs_male_age_21_25;
        return $this;
    }

    public function getCvsMaleAge2630(): ?int
    {
        return $this->cvs_male_age_26_30;
    }

    public function setCvsMaleAge2630(?int $cvs_male_age_26_30): static
    {
        $this->cvs_male_age_26_30 = $cvs_male_age_26_30;
        return $this;
    }

    public function getCvsMaleAge3135(): ?int
    {
        return $this->cvs_male_age_31_35;
    }

    public function setCvsMaleAge3135(?int $cvs_male_age_31_35): static
    {
        $this->cvs_male_age_31_35 = $cvs_male_age_31_35;
        return $this;
    }

    public function getCvsMaleAge3640(): ?int
    {
        return $this->cvs_male_age_36_40;
    }

    public function setCvsMaleAge3640(?int $cvs_male_age_36_40): static
    {
        $this->cvs_male_age_36_40 = $cvs_male_age_36_40;
        return $this;
    }

    public function getCvsMaleAge4145(): ?int
    {
        return $this->cvs_male_age_41_45;
    }

    public function setCvsMaleAge4145(?int $cvs_male_age_41_45): static
    {
        $this->cvs_male_age_41_45 = $cvs_male_age_41_45;
        return $this;
    }

    public function getCvsMaleAge4650(): ?int
    {
        return $this->cvs_male_age_46_50;
    }

    public function setCvsMaleAge4650(?int $cvs_male_age_46_50): static
    {
        $this->cvs_male_age_46_50 = $cvs_male_age_46_50;
        return $this;
    }

    public function getCvsMaleAge50Above(): ?int
    {
        return $this->cvs_male_age_50_above;
    }

    public function setCvsMaleAge50Above(?int $cvs_male_age_50_above): static
    {
        $this->cvs_male_age_50_above = $cvs_male_age_50_above;
        return $this;
    }

    public function getCvsFemaleAge2125(): ?int
    {
        return $this->cvs_female_age_21_25;
    }

    public function setCvsFemaleAge2125(?int $cvs_female_age_21_25): static
    {
        $this->cvs_female_age_21_25 = $cvs_female_age_21_25;
        return $this;
    }

    public function getCvsFemaleAge2630(): ?int
    {
        return $this->cvs_female_age_26_30;
    }

    public function setCvsFemaleAge2630(?int $cvs_female_age_26_30): static
    {
        $this->cvs_female_age_26_30 = $cvs_female_age_26_30;
        return $this;
    }

    public function getCvsFemaleAge3135(): ?int
    {
        return $this->cvs_female_age_31_35;
    }

    public function setCvsFemaleAge3135(?int $cvs_female_age_31_35): static
    {
        $this->cvs_female_age_31_35 = $cvs_female_age_31_35;
        return $this;
    }

    public function getCvsFemaleAge3640(): ?int
    {
        return $this->cvs_female_age_36_40;
    }

    public function setCvsFemaleAge3640(?int $cvs_female_age_36_40): static
    {
        $this->cvs_female_age_36_40 = $cvs_female_age_36_40;
        return $this;
    }

    public function getCvsFemaleAge4145(): ?int
    {
        return $this->cvs_female_age_41_45;
    }

    public function setCvsFemaleAge4145(?int $cvs_female_age_41_45): static
    {
        $this->cvs_female_age_41_45 = $cvs_female_age_41_45;
        return $this;
    }

    public function getCvsFemaleAge4650(): ?int
    {
        return $this->cvs_female_age_46_50;
    }

    public function setCvsFemaleAge4650(?int $cvs_female_age_46_50): static
    {
        $this->cvs_female_age_46_50 = $cvs_female_age_46_50;
        return $this;
    }

    public function getCvsFemaleAge50Above(): ?int
    {
        return $this->cvs_female_age_50_above;
    }

    public function setCvsFemaleAge50Above(?int $cvs_female_age_50_above): static
    {
        $this->cvs_female_age_50_above = $cvs_female_age_50_above;
        return $this;
    }

    // TOFT Gender-Age getters and setters
    public function getToftMaleAge2125(): ?int
    {
        return $this->toft_male_age_21_25;
    }

    public function setToftMaleAge2125(?int $toft_male_age_21_25): static
    {
        $this->toft_male_age_21_25 = $toft_male_age_21_25;
        return $this;
    }

    public function getToftMaleAge2630(): ?int
    {
        return $this->toft_male_age_26_30;
    }

    public function setToftMaleAge2630(?int $toft_male_age_26_30): static
    {
        $this->toft_male_age_26_30 = $toft_male_age_26_30;
        return $this;
    }

    public function getToftMaleAge3135(): ?int
    {
        return $this->toft_male_age_31_35;
    }

    public function setToftMaleAge3135(?int $toft_male_age_31_35): static
    {
        $this->toft_male_age_31_35 = $toft_male_age_31_35;
        return $this;
    }

    public function getToftMaleAge3640(): ?int
    {
        return $this->toft_male_age_36_40;
    }

    public function setToftMaleAge3640(?int $toft_male_age_36_40): static
    {
        $this->toft_male_age_36_40 = $toft_male_age_36_40;
        return $this;
    }

    public function getToftMaleAge4145(): ?int
    {
        return $this->toft_male_age_41_45;
    }

    public function setToftMaleAge4145(?int $toft_male_age_41_45): static
    {
        $this->toft_male_age_41_45 = $toft_male_age_41_45;
        return $this;
    }

    public function getToftMaleAge4650(): ?int
    {
        return $this->toft_male_age_46_50;
    }

    public function setToftMaleAge4650(?int $toft_male_age_46_50): static
    {
        $this->toft_male_age_46_50 = $toft_male_age_46_50;
        return $this;
    }

    public function getToftMaleAge50Above(): ?int
    {
        return $this->toft_male_age_50_above;
    }

    public function setToftMaleAge50Above(?int $toft_male_age_50_above): static
    {
        $this->toft_male_age_50_above = $toft_male_age_50_above;
        return $this;
    }

    public function getToftFemaleAge2125(): ?int
    {
        return $this->toft_female_age_21_25;
    }

    public function setToftFemaleAge2125(?int $toft_female_age_21_25): static
    {
        $this->toft_female_age_21_25 = $toft_female_age_21_25;
        return $this;
    }

    public function getToftFemaleAge2630(): ?int
    {
        return $this->toft_female_age_26_30;
    }

    public function setToftFemaleAge2630(?int $toft_female_age_26_30): static
    {
        $this->toft_female_age_26_30 = $toft_female_age_26_30;
        return $this;
    }

    public function getToftFemaleAge3135(): ?int
    {
        return $this->toft_female_age_31_35;
    }

    public function setToftFemaleAge3135(?int $toft_female_age_31_35): static
    {
        $this->toft_female_age_31_35 = $toft_female_age_31_35;
        return $this;
    }

    public function getToftFemaleAge3640(): ?int
    {
        return $this->toft_female_age_36_40;
    }

    public function setToftFemaleAge3640(?int $toft_female_age_36_40): static
    {
        $this->toft_female_age_36_40 = $toft_female_age_36_40;
        return $this;
    }

    public function getToftFemaleAge4145(): ?int
    {
        return $this->toft_female_age_41_45;
    }

    public function setToftFemaleAge4145(?int $toft_female_age_41_45): static
    {
        $this->toft_female_age_41_45 = $toft_female_age_41_45;
        return $this;
    }

    public function getToftFemaleAge4650(): ?int
    {
        return $this->toft_female_age_46_50;
    }

    public function setToftFemaleAge4650(?int $toft_female_age_46_50): static
    {
        $this->toft_female_age_46_50 = $toft_female_age_46_50;
        return $this;
    }

    public function getToftFemaleAge50Above(): ?int
    {
        return $this->toft_female_age_50_above;
    }

    public function setToftFemaleAge50Above(?int $toft_female_age_50_above): static
    {
        $this->toft_female_age_50_above = $toft_female_age_50_above;
        return $this;
    }

    // ECOMM Gender-Age getters and setters
    public function getEcommMaleAge2125(): ?int
    {
        return $this->ecomm_male_age_21_25;
    }

    public function setEcommMaleAge2125(?int $ecomm_male_age_21_25): static
    {
        $this->ecomm_male_age_21_25 = $ecomm_male_age_21_25;
        return $this;
    }

    public function getEcommMaleAge2630(): ?int
    {
        return $this->ecomm_male_age_26_30;
    }

    public function setEcommMaleAge2630(?int $ecomm_male_age_26_30): static
    {
        $this->ecomm_male_age_26_30 = $ecomm_male_age_26_30;
        return $this;
    }

    public function getEcommMaleAge3135(): ?int
    {
        return $this->ecomm_male_age_31_35;
    }

    public function setEcommMaleAge3135(?int $ecomm_male_age_31_35): static
    {
        $this->ecomm_male_age_31_35 = $ecomm_male_age_31_35;
        return $this;
    }

    public function getEcommMaleAge3640(): ?int
    {
        return $this->ecomm_male_age_36_40;
    }

    public function setEcommMaleAge3640(?int $ecomm_male_age_36_40): static
    {
        $this->ecomm_male_age_36_40 = $ecomm_male_age_36_40;
        return $this;
    }

    public function getEcommMaleAge4145(): ?int
    {
        return $this->ecomm_male_age_41_45;
    }

    public function setEcommMaleAge4145(?int $ecomm_male_age_41_45): static
    {
        $this->ecomm_male_age_41_45 = $ecomm_male_age_41_45;
        return $this;
    }

    public function getEcommMaleAge4650(): ?int
    {
        return $this->ecomm_male_age_46_50;
    }

    public function setEcommMaleAge4650(?int $ecomm_male_age_46_50): static
    {
        $this->ecomm_male_age_46_50 = $ecomm_male_age_46_50;
        return $this;
    }

    public function getEcommMaleAge50Above(): ?int
    {
        return $this->ecomm_male_age_50_above;
    }

    public function setEcommMaleAge50Above(?int $ecomm_male_age_50_above): static
    {
        $this->ecomm_male_age_50_above = $ecomm_male_age_50_above;
        return $this;
    }

    public function getEcommFemaleAge2125(): ?int
    {
        return $this->ecomm_female_age_21_25;
    }

    public function setEcommFemaleAge2125(?int $ecomm_female_age_21_25): static
    {
        $this->ecomm_female_age_21_25 = $ecomm_female_age_21_25;
        return $this;
    }

    public function getEcommFemaleAge2630(): ?int
    {
        return $this->ecomm_female_age_26_30;
    }

    public function setEcommFemaleAge2630(?int $ecomm_female_age_26_30): static
    {
        $this->ecomm_female_age_26_30 = $ecomm_female_age_26_30;
        return $this;
    }

    public function getEcommFemaleAge3135(): ?int
    {
        return $this->ecomm_female_age_31_35;
    }

    public function setEcommFemaleAge3135(?int $ecomm_female_age_31_35): static
    {
        $this->ecomm_female_age_31_35 = $ecomm_female_age_31_35;
        return $this;
    }

    public function getEcommFemaleAge3640(): ?int
    {
        return $this->ecomm_female_age_36_40;
    }

    public function setEcommFemaleAge3640(?int $ecomm_female_age_36_40): static
    {
        $this->ecomm_female_age_36_40 = $ecomm_female_age_36_40;
        return $this;
    }

    public function getEcommFemaleAge4145(): ?int
    {
        return $this->ecomm_female_age_41_45;
    }

    public function setEcommFemaleAge4145(?int $ecomm_female_age_41_45): static
    {
        $this->ecomm_female_age_41_45 = $ecomm_female_age_41_45;
        return $this;
    }

    public function getEcommFemaleAge4650(): ?int
    {
        return $this->ecomm_female_age_46_50;
    }

    public function setEcommFemaleAge4650(?int $ecomm_female_age_46_50): static
    {
        $this->ecomm_female_age_46_50 = $ecomm_female_age_46_50;
        return $this;
    }

    public function getEcommFemaleAge50Above(): ?int
    {
        return $this->ecomm_female_age_50_above;
    }

    public function setEcommFemaleAge50Above(?int $ecomm_female_age_50_above): static
    {
        $this->ecomm_female_age_50_above = $ecomm_female_age_50_above;
        return $this;
    }
}