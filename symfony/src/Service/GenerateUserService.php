<?php 
namespace App\Service;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
/**
 *
 * A service for GenerateUserService.
 */
class GenerateUserService {
    private $manager;
    private $logger;
    public function __construct( EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->manager = $em;
        $this->logger = $logger;
    }

    public function numberBetween(int $min = 0, int $max = 2147483647): int
    {
        $int1 = min($min, $max);
        $int2 = max($min, $max);

        return rand($int1, $int2);
    }

    private function myKadNumber($gender = null, $hyphen = false)
    {
        // year of birth
        $yy = $this->numberBetween(0, 99);

        // month of birth
        $fDate = new \DateTime;
        $mm = $fDate->format('m');

        // day of birth
        $dd = $fDate->format('d');;

        // place of birth (1-59 except 17-20)
        while (in_array($pb = $this->numberBetween(1, 59), [17, 18, 19, 20], false)) {
        }

        // random number
        $nnn = $this->numberBetween(0, 999);

        // gender digit. Odd = MALE, Even = FEMALE
        $g = $this->numberBetween(0, 9);

        //Credit: https://gist.github.com/mauris/3629548
        if ($gender === "male") {
            $g |= 1;
        } elseif ($gender === "female") {
            $g &= ~1;
        }

        // formatting with hyphen
        if ($hyphen === true) {
            $hyphen = '-';
        } elseif ($hyphen === false) {
            $hyphen = '';
        }

        return sprintf('%02d%02d%02d%s%02d%s%03d%01d', $yy, $mm, $dd, $hyphen, $pb, $hyphen, $nnn, $g);
    }

    private function checkICDupes(array $incoming, $comparator, $gender) {
        $_ORI_CURRENT_SAMPLE = $incoming;
        $_NEW_CURRENT_SAMPLE = $incoming;
        array_push($_NEW_CURRENT_SAMPLE, $comparator);
        // $_DESIRED_SIZE must be EQUAL.
        $_DESIRED_SIZE = count($_NEW_CURRENT_SAMPLE);
        if (count(array_unique($_NEW_CURRENT_SAMPLE)) == $_DESIRED_SIZE) {
            return $comparator;
        } else {
            $io->writeln('Detected dupe, auto correcting...');

            if ($gender) {
                $ICNO = $this->myKadNumber('female',false);
            } else {
                $ICNO = $this->myKadNumber('male',false);
            }
            $this->checkICDupes($_ORI_CURRENT_SAMPLE, $ICNO, $gender);
        } 
    }

    private function checkMobileDupes(array $incoming, $comparator,$safe) {
        if (!$safe) {
            $_MOBILE_SUFFIX = [
                '6010',
                '6011',
                '6012',
                '6013',
                '6014',
                '6016',
                '6017',
                '6018',
                '6019',
            ];
        } else {
            $_MOBILE_SUFFIX = [
                '6020',
                '6021',
                '6022',
                '6023',
                '6024',
                '6026',
                '6027',
                '6028',
                '6029',
            ];
        }

        $_ORI_CURRENT_SAMPLE = $incoming;
        $_NEW_CURRENT_SAMPLE = $incoming;
        array_push($_NEW_CURRENT_SAMPLE, $comparator);
        // $_DESIRED_SIZE must be EQUAL.
        $_DESIRED_SIZE = count($_NEW_CURRENT_SAMPLE);
        if (count(array_unique($_NEW_CURRENT_SAMPLE)) == $_DESIRED_SIZE) {
            return $comparator;
        } else {
            $io->writeln('Detected dupe, auto correcting...');
            $_MOBILE_NO = $_MOBILE_SUFFIX[rand(0, count($_MOBILE_SUFFIX) - 1)].rand(1000000,9999999);
            $this->checkMobileDupes($_ORI_CURRENT_SAMPLE, $_MOBILE_NO);
        }
    }

    public function generate($_SIZE, $_GENDER, $_MOBILE_SAFE) 
    {
        $_RACE = [0,1,2];
        // $_GENDER = [0,1];
        if(!$_MOBILE_SAFE) {
            $_MOBILE_SUFFIX = [
                '6010',
                '6011',
                '6012',
                '6013',
                '6014',
                '6016',
                '6017',
                '6018',
                '6019',
            ];
        } else {
            $_MOBILE_SUFFIX = [
                '6020',
                '6021',
                '6022',
                '6023',
                '6024',
                '6026',
                '6027',
                '6028',
                '6029',
            ];
        }

        $_FIRSTNAME_MALE[0] = ['ALI','ROSLI','AZLAN','HASAN', 'AZIZUL', 'ARIF', 'AHMAD', 'FIRDAUS', 'OTHMAN', 'AMIRUDDIN', "FUAD", 'HANIF', 'HAKIM', 'KHAIRUL','KARIM','HALIM','AKMAL', 'AKBAL', 'IQBAL', 'AIMAN','HAFIZ']; 
        $_FIRSTNAME_MALE[1] = ['CHONG','TAM','TAN','LEE','WONG','CHIA','NG','OOI','LING','LIM','CHIN','POON','POH','NGIAM'];
        $_FIRSTNAME_MALE[2] = ['SIVA','GOVINDA', 'PUVIN','MOHAN', 'GOVIN', 'BALA', 'SUBRA', 'SUBRAMANIAM', 'GANESH', 'MURALI', 'CHANDRA', 'ARUL', 'ARUN','DINESH','PRAKASH','GUNA', 'PRASATH','REDDY','RENGASAMMY', 'KUMARSAMY','SANGHA', 'KHAN', 'RAM', 'RAMESH'];
        $_LASTNAME_MALE = ['WEI SHENG','KIM HONG','MING YEN','MING KEE','MENG LEE', 'WING LI', 'MONG SHENG','HENG YOU','FOOK SHENG', 'ENG HONG', 'WEI MING','TUCK HEI', 'TUCK FAI','SENG HENG', 'YANG MING', 'KAH LEE', 'TZE GUN'];

        $_FIRSTNAME_FEMALE[0] = ['NURUL','NADIRAH','MAISARAH','SYAHRIN','ATIQAH','NORA','NURLIZA','LIANA', 'MASTURA', 'KHATIJAH' , 'UMI', 'DAYANG', 'FAUZIAH', 'SITI', 'SARIMAH','SALMAH','AISYAH','INTAN','MARIAM','ZALEHA'];
        $_FIRSTNAME_FEMALE[1] = ['CHONG','TAM','TAN','LEE','WONG','CHIA','NG','OOI','LING','LIM','CHIN','CHAN','POON','POH','NGIAM'];
        $_FIRSTNAME_FEMALE[2] = ['DIYA','LEELA','ASHA', 'GHEETA', 'RANI', 'JOTI', 'VIMALA', 'USHA', 'UMA', 'RATI', 'KAREENA', 'SITA', 'LETCHUMI', 'SHANTI', 'NEESA','BASANTI','VIJAYA','PADMA', 'RITA','PRIYA'];
        $_LASTNAME_FEMALE = ['WEI LING','KIM LEE','MEI LEE','MEI MEI','KIM HUA','YEE MONG', 'TZE MEI', 'PEI TZE', 'PEI LING', 'YIN CHI','YEE LING','SOOK KENG', 'SOK YEE','PHOI LING','LIN LING', 'SWEE HUI'];
        $_PARENT_PREFIX = ["MOHD","HAJI","SYED"];
        $_OUTPUT_CSV = [];
        $_ICNO_DUPES = [];
        $_MOBILE_DUPES = [];
        $_ROW = [];

        for($i = 0; $i < $_SIZE;$i++) {             
            $_RACE_PICKED = $_RACE[rand(0,2)];
            if ($_GENDER == 2){
                $_GENDER_PICKED = rand(0,1);
            } else {
                $_GENDER_PICKED = $_GENDER;            
            }
            
            // 0 MEANS MALAY, 1 MEANS CHINESE, 2 MEANS INDIAN.
            $_MOBILE_NO = $_MOBILE_SUFFIX[rand(0, count($_MOBILE_SUFFIX) - 1)].rand(1000000,9999999);

            if ($_GENDER_PICKED) {
                $ICNO = $this->myKadNumber('female',false);
            
            } else {
                $ICNO = $this->myKadNumber('male',false);
            }
            $_ICNO = $this->checkICDupes($_ICNO_DUPES, $ICNO, $_GENDER_PICKED);
            $_MOBILE_NO = $this->checkMobileDupes($_MOBILE_DUPES, $_MOBILE_NO, $_MOBILE_SAFE);

            if ( $_RACE_PICKED == 0) {
                // Malay
                if ($_GENDER_PICKED) {                    
                    // FEMALE MALAY
                    $_PICKED_INDEX = rand(0, count($_FIRSTNAME_FEMALE[0]) - 1 );
                    $_PICKED_INDEX_PARENT = rand(0, count($_FIRSTNAME_MALE[0]) - 1 );
                    $_PICKED_PREFIX = rand(0, count($_PARENT_PREFIX) - 1);
                    $_FULLNAME = $_FIRSTNAME_FEMALE[0][ $_PICKED_INDEX ]." BINTI ".$_PARENT_PREFIX[$_PICKED_PREFIX]." ".$_FIRSTNAME_MALE[0][ $_PICKED_INDEX_PARENT ];
                } else {
                    // MALE MALAY                    
                    $_PICKED_INDEX = rand(0, count($_FIRSTNAME_MALE[0]) - 1 );
                    $_PICKED_INDEX_PARENT = rand(0, count($_FIRSTNAME_MALE[0]) - 1 );
                    if ($_PICKED_INDEX == $_PICKED_INDEX_PARENT) {
                        if ($_PICKED_INDEX_PARENT == count($_FIRSTNAME_MALE[0]) - 1) {
                            $_PICKED_INDEX_PARENT = $_PICKED_INDEX_PARENT - 1;
                        } else if ($_PICKED_INDEX_PARENT == 0) {
                            $_PICKED_INDEX_PARENT = $_PICKED_INDEX_PARENT + 1;
                        } else {
                            $_PICKED_INDEX_PARENT = $_PICKED_INDEX_PARENT;
                        }
                    }
                    $_PICKED_PREFIX = rand(0, count($_PARENT_PREFIX) - 1); 
                    $_FULLNAME = $_FIRSTNAME_MALE[0][ $_PICKED_INDEX ]. " BIN ".$_PARENT_PREFIX[$_PICKED_PREFIX]." ".$_FIRSTNAME_MALE[0][ $_PICKED_INDEX_PARENT ];
                }
            } else if ( $_RACE_PICKED == 1) {
                // Chinese
                if ($_GENDER_PICKED) {
                    // FEMALE CHINESE
                    $_PICKED_INDEX = rand(0, count($_FIRSTNAME_FEMALE[1]) - 1 );
                    $_PICKED_LASTNAME = rand(0, count( $_LASTNAME_FEMALE) - 1 );
                    $_FULLNAME = $_FIRSTNAME_FEMALE[1][ $_PICKED_INDEX ]." ".$_LASTNAME_FEMALE[$_PICKED_LASTNAME];

                } else {
                            
                    // MALE CHINESE
                    $_PICKED_INDEX = rand(0, count($_FIRSTNAME_MALE[1]) - 1 ); 
                    $_PICKED_LASTNAME = rand(0, count( $_LASTNAME_MALE) - 1 );                
                    $_FULLNAME = $_FIRSTNAME_MALE[1][$_PICKED_INDEX]." ".$_LASTNAME_MALE[$_PICKED_LASTNAME];   

                }
            } else {
                // Indian
                if ($_GENDER_PICKED) {
                    
                    // FEMALE INDIAN
                    $_PICKED_INDEX = rand(0, count($_FIRSTNAME_FEMALE[2]) - 1 );
                    $_PICKED_INDEX_PARENT = rand(0, count($_FIRSTNAME_MALE[2]) - 1 );
                    $_FULLNAME = $_FIRSTNAME_FEMALE[2][$_PICKED_INDEX]." A/P ".$_FIRSTNAME_MALE[2][$_PICKED_INDEX_PARENT];   
                } else {
                    
                    // MALE INDIAN
                    $_PICKED_INDEX = rand(0, count($_FIRSTNAME_MALE[2]) - 1 );
                    $_PICKED_INDEX_PARENT = rand(0, count($_FIRSTNAME_MALE[2]) - 1 );

                    if ($_PICKED_INDEX == $_PICKED_INDEX_PARENT) {
                        if ($_PICKED_INDEX_PARENT == count($_FIRSTNAME_MALE[2]) - 1) {
                            $_PICKED_INDEX_PARENT = $_PICKED_INDEX_PARENT - 1;
                        } else if ($_PICKED_INDEX_PARENT == 0) {
                            $_PICKED_INDEX_PARENT = $_PICKED_INDEX_PARENT + 1;
                        } else {
                            $_PICKED_INDEX_PARENT = $_PICKED_INDEX_PARENT;
                        }
                    }

                    $_FULLNAME = $_FIRSTNAME_MALE[2][$_PICKED_INDEX]." A/L ".$_FIRSTNAME_MALE[2][$_PICKED_INDEX_PARENT];

                }
            }              
            
            if ($i == 0) {
                // $_ROW = array (
                //     "full_name" => "full_name",
                //     "mobile_no" => "mobile_no",
                //     "username" => "username",                
                //     "email" => "email",
                //     "national_id" => "national_id",
                //     "gender" => "gender"
                // );
                // array_push($_OUTPUT_CSV, $_ROW);
            }
            $_ROW = array (
                "full_name" => $_FULLNAME,
                "mobile_no" => $_MOBILE_NO,
                "username" => $_MOBILE_NO,
                "email" => $_MOBILE_NO."@wonderlah.com",
                "national_id" => $ICNO,
                "gender" => ($_GENDER_PICKED)?"FEMALE":"MALE"
            );
            array_push($_OUTPUT_CSV, $_ROW);
        }
        return $_OUTPUT_CSV;                    
    }   
}