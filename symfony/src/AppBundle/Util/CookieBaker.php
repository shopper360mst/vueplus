<?php 
/**
 *
 * A tool for Cookie.
 * 
 */
namespace App\AppBundle\Util;

abstract class CookieBaker {
	public function createCookie($nameCookie, $cookie_value) {
        $cookieDuration = $this->getParameter('app.cookie_duration_day');
        $default_duration = isset($cookieDuration)?$cookieDuration:7;
        setcookie($nameCookie, $cookie_value, time() + (86400 * $default_duration), "/"); 
    }
}