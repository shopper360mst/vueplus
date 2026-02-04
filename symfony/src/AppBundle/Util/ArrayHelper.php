<?php 
/**
 *
 * A tool for Array.
 */
namespace App\AppBundle\Util;

abstract class ArrayHelper {
	public static function convert($rawArray, $labelAtrr = 'label', $valueAttr = 'value' ) {
        $newArray = array();

        foreach($rawArray as $key => $items) {
            $newArray[$items->$labelAtrr] = $items->$valueAttr;
        }

        return $newArray;
    }
}