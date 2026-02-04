<?php
namespace App\Service;
class TimeService
{
    public function getYear($pdate) {
        return $pdate->format("Y");
    }

    public function getMonth($pdate) {
        return $pdate->format("m");
    }

    public function getDay($pdate) {
        return $pdate->format("d");
    }
}