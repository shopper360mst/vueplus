<?php 
namespace App\Service;
use App\Entity\Postal;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
/**
 *
 * A service for GenerateAddressService.
 */
class GenerateAddressService {
    private $manager;
    private $logger;
    public function __construct( EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->manager = $em;
        $this->logger = $logger;
    }

    public function generate($_SIZE, $_STATECODE) 
    {
        $_ADDRESS1_PREFIX = ['BLOCK ', 'NO. ', 'UNIT '];
        $_ADDRESS1_SUFFIX = ['BANGUNAN','PANGSAPURI','FLAT'];
        $_ADDRESS1_NAME = ['TALAM', 'PERINDUSTRIAN', 'MAMELADE', 'TIARA', 'KASTURI', 'MERBUK', 'EMBUN', 'PANGLIMA', 'PAHLAWAN', 'CILI', 'EMERALD', 'SAPHIRE', 'MUTIARA', 'MAHKOTA', 'TASIK', 'SUNGAI', 'BESI', 'ALLOY', 'THOMAS', 'BERINGIN', 'SCIENCE', 'LAPIS', 'MERDEKA', 'BATEK', 'TERIMAS', 'EMAS', 'PUNCAK', 'INDAH', 'NANAS', 'HARIMAU','LANDAK','RUSA', 'SATIN'];        
        $_ADDRESS1_ARR = ['JALAN','LORONG', 'SEKSYEN'];
        $_ADDRESS1_SUBURB = ['TAMAN','BUKIT','MONT', 'ALAM'];

        $POSTAL_DATA = $this->manager->getRepository(Postal::class)->findBy([
            'state_code' => $_STATECODE
        ]);

        if(!$POSTAL_DATA) {
            return [];            
        } else {
            $_OUTPUT_CSV = [];
            $_ROW = [];
            for($i = 0; $i < $_SIZE;$i++) {
                $_NO1 = rand(15,100);
                if (rand(0,1)) {
                    $_NO2 = "/" . rand(10,100);
                } else {
                    $_NO2 = "";
                }
                $_SELECTEDINDEX = rand(0,count($POSTAL_DATA)-1);
                $_CITY = $POSTAL_DATA[$_SELECTEDINDEX]->getCity();
                $_POSTCODE = $POSTAL_DATA[$_SELECTEDINDEX]->getPostCode();
    
                $_PREFIX_PICK = rand(0,2);
                $_ADDRESS_1 = $_ADDRESS1_PREFIX[$_PREFIX_PICK].$_NO1.$_NO2.". "; 
                $_ADDRESS_1 .= $_ADDRESS1_SUFFIX[rand(0,2)]." ".$_ADDRESS1_NAME[rand(0,count($_ADDRESS1_NAME)-1)];
                $_ADDRESS_2 = $_ADDRESS1_SUBURB[rand(0,count($_ADDRESS1_SUBURB)-1)]." ".$_ADDRESS1_NAME[rand(0,count($_ADDRESS1_NAME)-1)];
                $_STATE = $POSTAL_DATA[$_SELECTEDINDEX]->getState();
                
                if ($i == 0) {
                    // $_ROW = array (
                    //     "address_1" => "address_1",
                    //     "address_2" => "address_2",
                    //     "postcode" => "postcode",
                    //     "city" => "city",
                    //     "state" => "state"
                    // );
                    // array_push($_OUTPUT_CSV, $_ROW);
                }
                $_ROW = array (
                        "address_1" => $_ADDRESS_1,
                        "address_2" => $_ADDRESS_2,
                        "postcode" => $_POSTCODE,
                        "city" => strtoupper($_CITY),
                        "state" => strtoupper($_STATE)
                );            
                array_push($_OUTPUT_CSV, $_ROW);
            }

            return $_OUTPUT_CSV;

        } 

    }
}