<?php
namespace App\AppBundle\Util;

class GenUniqueFiller {
    public static function generate($prefix = null) {
        $maTime = (new \DateTime())->getTimestamp();
        $fourDigits = GenUniqueFiller::randomDigits(4);
        $crc = strval( crc32($maTime) + $fourDigits);
        if (isset($prefix)) {
            $final = $prefix . $crc; 
        } else {
            $final = "A11" . $crc;
        }
        return $final;        
    }

    public static function randomDigits($length){
        $numbers = range(0,9);
        shuffle($numbers);
        for($i = 0; $i < $length; $i++){
            global $digits;
               $digits .= $numbers[$i];
        }
        return $digits;
    }

}
