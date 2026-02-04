<?php 
/**
 * Convert associated array fields pairing 
 * to update setter e.g. "display_name = ?, email = ?" 
 */
namespace App\AppBundle\Util;

class SQLExtraHelper {
    public static function convertArrayToSet($arrayData) {
        $setPart = implode(', ', array_map(function ($column) {
            return "$column = ?";
        }, array_keys($arrayData)));

        return $setPart;
    }

    public static function processMSSQLException($err) {
        preg_match('#\\((.*?)\\)#', $err, $match);
        return $match[1].' is being used.';
    }
}