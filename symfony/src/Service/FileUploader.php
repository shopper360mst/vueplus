<?php
namespace App\Service;

use App\Service\MailerService;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploader
{
    public function __construct()
    {
    }

    public function validateImg($data)
    {
        try {
            $binary = base64_decode(explode(',', $data)[1]);
            $data = getimagesizefromstring($binary);
        } catch (\Exception $e) {
          return false;
        }

        $allowed = ['image/jpeg', 'image/png', 'image/gif'];

        if (!$data) {
            return false;
        }

        if (!empty($data[0]) && !empty($data[0]) && !empty($data['mime'])) {
            if (in_array($data['mime'], $allowed)) {
                return true;
            }
        }

        return false;
    }

    public function uploadFileObject($filename, $fileObject, $folderPath) {
        try {
            if (!is_dir($folderPath)) {
                mkdir($folderPath,0755,true);
            }
            $fileObject->move($folderPath, $filename);
            return true;
        } catch (FileException $e) {
            return null;
        }
    }

    public function uploadImage($filename, $base64Data, $folderPath)
    {
        ob_start();
        // check and get extension.
        $splitted = explode(';', $base64Data);
        $dataType = $splitted[0];
        $pos = strpos($dataType, "/");
        $getExtension = substr($dataType, ($pos+1));

        if ($this->validateImg($base64Data)){
            $fullFolderPath = $folderPath;
            if (!is_dir($fullFolderPath)) {
                mkdir($fullFolderPath,0755,true);
            }

            try {
                $fullPath = $fullFolderPath."//".$filename.".".$getExtension;
                file_put_contents( $fullPath, file_get_contents($base64Data) );
            } catch (FileException $e) {
                // Do Something.
                return null;
            }

            return $filename.".".$getExtension;
        } else {
            return null;
        }
    }

    public function getTargetDirectory()
    {
        return $this->targetDirectory;
    }
}