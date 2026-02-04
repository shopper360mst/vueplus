<?php
namespace App\Service;

use App\Service\MailerService;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;


class GuzzleService
{
    private $param;
    private $client;
    public function __construct()
    {
        $this->client = new Client();
    }

    public function curlToUrl($url, $param) {
        $response = $this->client->post($url, $param );
        return $response;
    }

    
    
}