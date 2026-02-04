<?php
namespace App\Service;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Aws\S3\S3Client;
use League\Flysystem\FilesystemException;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;
class ObjectStorageService
{
    public function __construct( private LoggerInterface $logger, private ParameterBagInterface $paramBag)
    {
    }

    public function upload($filename, $target_file_source ) {
        try {
            $S3Client = new S3Client([
                'endpoint'    => $this->paramBag->get('app.s3_base_url'),
                'region'  => $this->paramBag->get('app.s3_region_name'),
                'credentials' => [
                    'key'    => $this->paramBag->get('app.s3_secret_access'),
                    'secret' => $this->paramBag->get('app.s3_secret_key'),
                ]
            ]);
            $OBJ_STORAGE_MODE = $this->paramBag->get('app.s3_object_storage');

            if ($OBJ_STORAGE_MODE == 'ipsvr1') {
                //IPSERVER1 MODE DOENST USE PUTOBJECTS THEY USE FLYSYSTEM
                try {
                    $adapter = new AwsS3V3Adapter(
                        $S3Client, 
                        $this->paramBag->get('app.s3_bucket_name')
                    );
                    $filesystem = new Filesystem($adapter);
                    $filesystem->write($filename, $target_file_source);
                    return true;
                } catch (FilesystemException $e) {
                    $this->logger->info("----------------ERROR ------------------------:::".$e->getMessage());
                    return false;
                } 
            } else {
                $this->logger->info("AWS MODE ...");
                // AWS S3 mode;
                $result = $S3Client->putObject([
                    'Bucket' => $this->paramBag->get('app.s3_bucket_name'),
                    'Key'    => $filename.".jpg",
                    'Body'   => '',
                    'SourceFile' => $target_file_source
                ]);
                if ($result) {
                    return true;
                } else {
                    return false;
                }
            }

        } catch( \Exception $e) {
            $this->logger->info("----------------ERROR ------------------------:::".$e->getMessage());
            
            return false;
        }

    }

}