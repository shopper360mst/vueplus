<?php

namespace App\Controller;

use Throwable;
use App\Entity\Otp;
use App\Entity\User;
use App\Entity\Postal;
use App\Entity\Payload;
use App\Entity\Product;
use App\Entity\Submission;
use Psr\Log\LoggerInterface;
use App\Service\FileUploader;
use App\Service\CipherService;
use App\Service\MailerService;
use App\Service\ActivityService;
use App\Service\SmsBlastService;

use App\AppBundle\Util\EnumError;
use App\Entity\SubmissionCaptcha;
use App\Service\CurlToUrlService;
use App\Service\ObjectStorageService;
use App\AppBundle\Util\GenUniqueFiller;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class EndPointController extends AbstractController
{
   public function __construct(
        private MailerService $mailer,
        private EntityManagerInterface $em,
        private ActivityService $asvc,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private UserPasswordHasherInterface $passwordEncoder,
        private LoggerInterface $logger,
        private FileUploader $fileSvc,
        private GenUniqueFiller $uniqueGen,
        private CipherService $cs,
        private SmsBlastService $sms,
        private CurlToUrlService $cts,
        private ObjectStorageService $oss
        ){
    }

    private function sendActivity($name, $request, $field1, $field2, $field3, $uname) {
        $this->asvc->setActivity($name, $request, $field1, $field2, $field3, $uname);
    }

    private function checkLegitToken($request) {
        $bearerToken = $request->headers->get('Authorization');
        if (!isset($bearerToken)) {
            return false;
        }
        $returnToken = explode(" ", $bearerToken);
        if (!$this->isCsrfTokenValid('Bearer', $returnToken[1])) {
            return false;
        } else {
            return true;
        }
    }

    /**
     ****************** BEGIN Implementation of basic business logic from typical contest. ******************
     */
    private function getGuestUser($username) {
        $existingUser = $this->em->getRepository(User::class)->findOneBy(
            array( 'username' => $username )
        );
        if($existingUser) {
            return $existingUser;
        } else {
            return false;
        }
    }

    private function findAndLockProduct($user, $product_category) {
        $product = $this->em->getRepository(Product::class)->findAndLock($user,$product_category);
        return $product;
    }

    private function getExtensionFromMimeType($mimeType) {
        $mimeTypeMap = [
            'image/png' => '.png',
            'image/jpeg' => '.jpg',
            'image/jpg' => '.jpg',
            'application/pdf' => '.pdf',            
        ];

        $mimeType = strtolower(trim($mimeType));        
        return $mimeTypeMap[$mimeType] ?? '.bin';
    }

    private function mimeTypeToExtension(string $mimeType): string
    {
        // Check if the MIME type contains a slash
        if (strpos($mimeType, '/') === false) {
            return ''; // Not a valid MIME type format
        }
        $parts = explode('/', $mimeType);
        $extension = strtolower(end($parts));
        if ($extension === 'jpeg' || $extension === 'pjpeg') {
            return '.jpg';
        } elseif ($extension === 'svg+xml') {
            return '.svg';
        } elseif ($extension === 'vnd.openxmlformats-officedocument.wordprocessingml.document') {
            return '.docx';
        }
        return '.' . $extension;
    }

    private function uploadFileObjectLocal($newGen, $fileObject, $folderPath) { 
        // TODO: Implement uploadFileObjectLocal() method.
        $result = $this->fileSvc->uploadFileObject($newGen, $fileObject, $folderPath);
         if(!$result){
            return new JsonResponse([
                'result' => false,
                'message' => EnumError::INVALID_METHOD
            ], Response::HTTP_OK);
        }

    }

    private function uploadToBucketFileObject($filename, $fileObject) {
        // TODO: Implement uploadToBucketFileObject() method.
        $result = $this->oss->upload($filename, $fileObject );
        if(!$result){
            return new JsonResponse([
                'result' => false,
                'message' => EnumError::INVALID_METHOD
            ], Response::HTTP_OK);
        }
    }

    private function uploadToBucketBase64($filename, $target_img) {
        $result = $this->oss->upload($filename, $target_img);
        if(!$result){
            return new JsonResponse([
                'result' => false,
                'message' => EnumError::INVALID_METHOD
            ], Response::HTTP_OK);
        }
    }

    private function uploadBase64StringLocal($newGen, $base64, $folderPath) {
        $fullPathGen = $this->fileSvc->uploadImage( $newGen, $base64, $folderPath );
        if($fullPathGen == null){
            return new JsonResponse([
                'result' => true,
                'message' => EnumError::INVALID_FILE
            ], Response::HTTP_OK);
        }
    }

    private function validateEmptyField($param) {
        if (empty($param->full_name) || $param->full_name == '' || 
            empty($param->mobile_no) || $param->mobile_no == '' || 
            empty($param->national_id) || $param->national_id == '') {
                return true;
        } else {
                return false;
        }

    }

    private function checkDuplicateInPrismTable($receiptNo, $nationalId, $mobileNo) {
        // Check 1: receipt_no first
        if (!empty($receiptNo)) {
            $existingRecord = $this->em->getRepository(\App\Entity\PrismTable::class)->findOneBy(
                array('receipt_no' => $receiptNo)
            );
            if ($existingRecord) {
                return true;
            }
        }
        
        // Check 2: national_id
        if (!empty($nationalId)) {
            $existingRecord = $this->em->getRepository(\App\Entity\PrismTable::class)->findOneBy(
                array('national_id' => $nationalId)
            );
            if ($existingRecord) {
                return true;
            }
        }
        
        // Check 3: mobile_no
        if (!empty($mobileNo)) {
            $existingRecord = $this->em->getRepository(\App\Entity\PrismTable::class)->findOneBy(
                array('mobile_no' => $mobileNo)
            );
            if ($existingRecord) {
                return true;
            }
        }
        
        return false;
    }

    private function getDetailedErrorMessage(\Exception $e): string
    {
        $messages = [];
        
        $messages[] = get_class($e) . ': ' . $e->getMessage();
        
        $previous = $e->getPrevious();
        while ($previous !== null) {
            $messages[] = 'Caused by ' . get_class($previous) . ': ' . $previous->getMessage();
            $previous = $previous->getPrevious();
        }
        
        return implode(' | ', $messages);
    }

    /************************************* END Implementation. ************************************************
    /**
     * This is the main function of the system.
     * It will execute the code based on the incoming method. The code have their own individual function.
     * It will only accept a Request and a string. then returns a JsonResponse.
     *
     * @access public
     * @param Request
     * @param string
     * @return JsonResponse
     */
    #[Route('/%app.campaign_code%/endpoint/{method}',
        name: 'app_endpoint_post',
        methods: ['POST'],
        requirements: ["method" => "^[a-zA-Z0-9]+(?:[\w _]*[a-zA-Z0-9]+)*$"]
    )]
    public function postFn(Request $request, $method = null): JsonResponse
    {
        $RESULT = null;
        $PAYLOAD_CONTENT = null;
        $contentType = $request->headers->get('Content-Type');

        if (str_starts_with($contentType, 'multipart/form-data')) {
            $PAYLOAD_CONTENT = json_encode( $request->request->all());
            $param = json_decode( json_encode( $request->request->all()) );
        } else {
            $PAYLOAD_CONTENT = $request->getContent();
            $param = json_decode( $request->getContent() );
        }
    
        $bearerToken = $request->headers->get('Authorization');
        $userAgent = $request->headers->get('user-agent');
        if (!isset($bearerToken)) {
            $this->saveToBlackBox($PAYLOAD_CONTENT, 'NOTOKEN', 0, $userAgent);
            
            return new JsonResponse([
                'result' => false,
                'message' => EnumError::SESSION_OUT
            ], Response::HTTP_UNAUTHORIZED );
        }

        // check_user_roles doesn't have CSRF for now, so uncomment this when we have csrf
        if ($this->getParameter('app.campaign_code') != "") {
            // CRSF Token style.
            $returnToken = explode(" ", $bearerToken);
            if($_SERVER['CSRF_TOKEN'] != $returnToken[1]){
                $this->saveToBlackBox($PAYLOAD_CONTENT, 'FAILTOKEN', 0, $userAgent);

                return new JsonResponse([
                    'result' => false,
                    'message' => EnumError::SESSION_OUT
                ], Response::HTTP_UNAUTHORIZED);
            }
        } else {
            // form-cmsId
            if (!$this->checkLegitToken($request)) {
                $this->saveToBlackBox($PAYLOAD_CONTENT, 'FAILTOKEN', 0, $userAgent);

                return new JsonResponse([
                    'result' => false,
                    'message' => EnumError::SESSION_OUT
                ], Response::HTTP_UNAUTHORIZED);
            }
        }

        switch($method) {
            case "submit":
                /* TURN THIS ON WHEN CAMPAIGN END 
                return new JsonResponse([
                    'result' => false,
                    'message' => $e->getMessage()
                ], Response::HTTP_OK);
                
                */
                $folderPath     = $this->getParameter('kernel.project_dir')."//public//images//uploaded//receipt";
                $username_alias = $this->getParameter('app.username_alias');
                if ($username_alias == "mobile_no") {
                    $username = $param->mobile_no;
                } else {
                    $username = $param->email;
                }
                $_EXISTING = $this->getGuestUser($username);
                if(isset($param->captcha)){
                    $captchaResult = $this->checkCaptcha($param->captcha);
                    
                    if($captchaResult->hostname === null || $captchaResult->score === null || $captchaResult->action === null){
                        $this->saveToBlackBox($PAYLOAD_CONTENT, 'CAPTCHA_EXPIRED', 0, $userAgent);
                        return new JsonResponse([
                            'result' => false,
                            'message' => '00910'
                        ], Response::HTTP_OK);
                    }

                    $submissionCaptcha = new SubmissionCaptcha();
                    $submissionCaptcha->setSuccessStatus($captchaResult->success);
                    $submissionCaptcha->setChallengeTs(new \DateTime);
                    $submissionCaptcha->setHostname($captchaResult->hostname);
                    $submissionCaptcha->setScore($captchaResult->score);
                    $submissionCaptcha->setAction($captchaResult->action);

                    $this->em->persist($submissionCaptcha);
                    $this->em->flush();
                    $this->saveToBlackBox($PAYLOAD_CONTENT, 'TOKENLEGIT', $captchaResult->score, $userAgent);

                    if($captchaResult->success == false && $captchaResult->score <= 0.3 && $captchaResult->action != "submit"){
                        return new JsonResponse([
                            'result' => false,
                            'message' => $e->getMessage()
                        ], Response::HTTP_OK);
                    }
                } else {
                    $this->saveToBlackBox($PAYLOAD_CONTENT, 'NOCAPTCHA', 0, $userAgent);
                }
                // Validate required fields
                // Test why not working
                // Protect data stream from being corrupted by missing data.
                
                if ($this->validateEmptyField($param) ) {
                    return new JsonResponse([
                            'result' => false,
                            'message' => ''
                        ], Response::HTTP_BAD_REQUEST);
                } else {
                    if (!str_starts_with($contentType, 'multipart/form-data')) {
                        if ($param->upload_receipt == "") {                            
                            return new JsonResponse([
                                'result' => false,
                                'message' => EnumError::INVALID_FILE
                            ], Response::HTTP_OK);
                        }
                    }
                }
                
                // Check for duplicates in prism_table only if form_code is GWP or promoter-specific codes
                $isDuplicate = false;
                $gwpCodes = ['GWP', 'SHM_AEON', 'SHM_LOTUS', 'SHM_APPEAL', 'SHM_FULL_REDEEM'];
                if (isset($param->form_code) && in_array($param->form_code, $gwpCodes)) {
                    $isDuplicate = $this->checkDuplicateInPrismTable(
                        isset($param->receipt_no) ? $param->receipt_no : null,
                        $param->national_id,
                        $param->mobile_no
                    );
                }
                if ($_EXISTING) {
                    // Basic assign Product. Disable if applicable.
                    try {
                        $submissionEntity = new Submission();
                        $submissionEntity->setSubmitCode($param->form_code);
                        $submissionEntity->setFullName($param->full_name);
                        $submissionEntity->setMobileNo($param->mobile_no);
                        $submissionEntity->setEmail($param->email);
                        $submissionEntity->setGender($this->maleFemale($param->national_id));
                        $submissionEntity->setNationalId($param->national_id);
                        $submissionEntity->setUser($_EXISTING);

                        $submissionEntity->setSubmitType($param->channel);
                        
                        /***********************************/
                        /***   Product Selection Data    ***/
                        /***********************************/
                        if(isset($param->product)){
                            $submissionEntity->setField10($param->product);
                        }
                        
                        /***********************************/
                        /***     Upload File Fields      ***/
                        /***********************************/
                        if (str_starts_with($contentType, 'multipart/form-data')) {
                            // To commonize the function below whether is multipart or not.
                            // eventually we are moving away from base64 due to complication.
                            $param->upload_receipt = 1;
                        }

                        if(isset($param->upload_receipt) && !empty($param->upload_receipt)){
                            $newGen = $this->uniqueGen->generate("RC-".uniqid());
                            if($this->getParameter('app.s3_secret_key') != ""){
                                if (str_starts_with($contentType, 'multipart/form-data')) {
                                    // This part is now active. multipart/form-data mode.
                                    $fileObject = $request->files->get('upload_receipt');
                                    $extension = $this->mimeTypeToExtension( $fileObject->getMimeType() );
                                    $this->uploadFileObjectLocal($newGen.$extension , $fileObject, $folderPath) ;

                                    $target_path = $folderPath."//".$newGen.$extension;
                                    $target_file = file_get_contents($target_path);

                                    $this->uploadToBucketFileObject($newGen.$extension, $target_file) ; 
                                    $submissionEntity->setAttachment( $newGen.$extension );

                                } else {
                                    // Added a localize upload.
                                    $this->uploadBase64StringLocal($newGen, $param->upload_receipt, $folderPath); 

                                    $target_img = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '',$param->upload_receipt));
                                    $this->uploadToBucketBase64($newGen.".jpg",$target_img);

                                    $submissionEntity->setAttachment($newGen.'.jpg');
                                }
                            }
                            else{
                                if (str_starts_with($contentType, 'multipart/form-data')) {
                                    // 1. get extension for upload_receipt field and upload_receipt2.
                                    // 2. collect fileobject from field upload_receipt and upload_receipt2.
                                    // $this->uploadFileObjectLocal($newGen, $fileObject, $folderPath) 

                                    // $submissionEntity->setAttachment( $newGen. 'extension' );
                                } else {
                                    $this->uploadBase64StringLocal($newGen, $param->upload_receipt, $folderPath);
                                
                                    $submissionEntity->setAttachment($fullPathGen);
                                }
                            }
                        } 
                        /***********************************/
                        /*** Add Extra Submission Fields ***/
                        /***********************************/

                        //DIY Fields
                        $submissionEntity->setField2($this->convertFieldtoQuest($param->channel));
                        //Age Field - Only calculate for Malaysian NRIC
                        if ($this->isMalaysianNRIC($param->national_id)) {
                            $yearDigits = (int)substr($param->national_id, 0, 2);
                            $currentYear = (int)date('Y');
                            $currentYearLastTwoDigits = $currentYear % 100;

                            // Determine if the year should be 19XX or 20XX
                            // If year digits are greater than current year's last two digits, assume 19XX
                            // Otherwise, assume 20XX
                            if ($yearDigits > $currentYearLastTwoDigits) {
                                $NricAge = 1900 + $yearDigits;
                            } else {
                                $NricAge = 2000 + $yearDigits;
                            }

                            $age = $currentYear - $NricAge;
                            $ageRange = $this->getAgeRange($age);
                            $submissionEntity->setField3($age);
                            $submissionEntity->setField4($ageRange);
                        } else {
                            // For passport or other ID formats, don't set age fields
                            $submissionEntity->setField3(null);
                            $submissionEntity->setField4(null);
                        }
                        $submissionEntity->setAttachmentNo($param->receipt_no);
                        $submissionEntity->setReceiverFullName(isset($param->receiver_full_name) ? $param->receiver_full_name : "");
                        $submissionEntity->setReceiverMobileNo(isset($param->receiver_mobile_no) ? $param->receiver_mobile_no : "");
                        $submissionEntity->setAddress1(isset($param->address1) ? $param->address1 : "");
                        $submissionEntity->setAddress2(isset($param->address2) ? $param->address2 : "");
                        $submissionEntity->setCity(isset($param->city) ? $param->city : "");
                        $submissionEntity->setState(isset($param->state) ? $param->state : "");
                        $submissionEntity->setPostcode(isset($param->postcode) ? $param->postcode : "");
                        
                        if(isset($param->field8)){
                            $submissionEntity->setField8($param->field8);
                        }

                        // Set status and reject reason based on duplicate check
                        if ($isDuplicate) {
                            $submissionEntity->setStatus('REJECTED');
                            $submissionEntity->setRejectReason('DUPLICATE RECEIPT');
                            $submissionEntity->setStatus1('REJECTED');
                            $submissionEntity->setReason1('DUPLICATE RECEIPT');
                        } else {
                            $submissionEntity->setStatus('PROCESSING');
                        }
                        
                        $submissionEntity->setCreatedDate(new \DateTime);
                        $this->em->persist($submissionEntity);
                        $this->em->flush();
                        
                        if (isset($submissionCaptcha)) {
                            $submissionCaptcha->setRefUserId($_EXISTING->getId());
                            $this->em->persist($submissionCaptcha);
                            $this->em->flush();
                        }
                        // contest ID
                        $diyRes = $this->toDIY(
                            $this->convertFieldtoQuest($param->channel),
                            $submissionEntity,
                            $param->full_name,
                            $param->mobile_no,
                            $param->receipt_no,
                            $param->email,
                            $param->national_id,
                            isset($param->product) ? $param->product : null,
                            isset($param->state) ? $param->state : null,
                            isset($param->field8) ? $param->field8 : null
                        );
                        if($diyRes){
                            $this->sendActivity('TO DIY SUCCESS', $request, "submission ID:".$submissionEntity->getId(), "", "", "");
                        }
                        else{
                            $this->sendActivity('TO DIY FAILED', $request, "submission ID:".$submissionEntity->getId(), "", "", "");
                        }

                        // EMAIL EDM
                        // $start_msg = "Dear ".$submissionEntity->getFullName().",<BR><BR>";
                        
                        // if($param->form_code != 'CVSTOFT'){
                        //     $mid_msg = '
                        //     <a href="'.$this->getParameter('app.proxy_url').$request->getLocale().'/chk_status">
                        //     <img style="display: block; width:100%;border:0;" src="'.$this->getParameter('app.base_url').'/build/images/EDM_REDEMPTION_'.$param->locale.'.png" alt="" />
                        //     </a>
                        //     ';
                        // }
                        // else{
                        //     $mid_msg = '
                        //     <a href="'.$this->getParameter('app.proxy_url').'">
                        //     <img style="display: block; width:100%;border:0;" src="'.$this->getParameter('app.base_url').'/build/images/EDM_CONTEST_'.$param->locale.'.png" alt="" />
                        //     </a>
                        //     ';
                        // }
                        // $end_msg = "";
                        // $message = $start_msg.$mid_msg.$end_msg;
                        // $this->mailer->sendTwigEmail(
                        //     $message,
                        //     [$submissionEntity->getEmail()],
                        //     $this->getParameter('app.site_name'),
                        //     $this->getParameter('app.email_title'),
                        //     "",
                        //     false
                        // );

                        // SET OTP WITH USER
                        // $existingOtp = $this->em->getRepository(Otp::class)->findOneBy(
                        //     array( 'phone_no' => $param->mobile_no, 'code' => $param->otp )
                        // );
                        // if(isset($existingOtp)){
                        //     $existingOtp->setUser($_EXISTING);
                        //     $this->em->persist($existingOtp);
                        //     $this->em->flush();
                        // }

                        return new JsonResponse([
                            'result' => true,
                            'message' => EnumError::ALLGOOD
                        ], Response::HTTP_OK);

                    } catch(\Exception $e){
                        $this->sendActivity('Submission Failed', $request, $param->form_code, "", "", $username);
                        
                        $errorDetails = $this->getDetailedErrorMessage($e);
                        $this->logger->error('Submit Endpoint Error (Existing User)', [
                            'exception_class' => get_class($e),
                            'error_message' => $e->getMessage(),
                            'error_details' => $errorDetails,
                            'trace' => $e->getTraceAsString()
                        ]);
                        
                        return new JsonResponse([
                            'result' => false,
                            'message' => $errorDetails
                        ], Response::HTTP_OK);
                    }
                } else {
                    try {
                        $_NEWUSER = new User();
                        $_NEWUSER->setUsername($username);
                        $_NEWUSER->setPassword(
                            $this->passwordEncoder->hashPassword(
                                $_NEWUSER,
                                uniqid()
                            )
                        );
                        $_NEWUSER->setFullName($param->full_name);
                        $_NEWUSER->setMobileNo($param->mobile_no);
                        $_NEWUSER->setEmail($param->email);
                        $_NEWUSER->setGuest(1);
                        $_NEWUSER->setVisible(1);
                        $_NEWUSER->setActive(1);
                        $_NEWUSER->setRoles(["ROLE_USER"]);
                        $_NEWUSER->setCreatedDate(new \DateTime);
                        $_NEWUSER->setDeleted(0);

                        $this->em->persist($_NEWUSER);
                        $this->em->flush();
                        // Basic assign Product. Disable if applicable.

                        /***********************************/
                        /*** Add Extra Submission Fields ***/
                        /***********************************/
                        $submissionEntity = new Submission();
                        $submissionEntity->setSubmitCode($param->form_code);
                        $submissionEntity->setFullName($param->full_name);
                        $submissionEntity->setMobileNo($param->mobile_no);
                        $submissionEntity->setEmail($param->email);
                        $submissionEntity->setGender($this->maleFemale($param->national_id));
                        $submissionEntity->setNationalId($param->national_id);
                        $submissionEntity->setUser($_NEWUSER);
                        $submissionEntity->setSubmitType($param->channel);
                        //DIY Fields
                        $submissionEntity->setField2($this->convertFieldtoQuest($param->channel));
                        //Age Field - Only calculate for Malaysian NRIC
                        if ($this->isMalaysianNRIC($param->national_id)) {
                            $yearDigits = (int)substr($param->national_id, 0, 2);
                            $currentYear = (int)date('Y');
                            $currentYearLastTwoDigits = $currentYear % 100;

                            // Determine if the year should be 19XX or 20XX
                            // If year digits are greater than current year's last two digits, assume 19XX
                            // Otherwise, assume 20XX
                            if ($yearDigits > $currentYearLastTwoDigits) {
                                $NricAge = 1900 + $yearDigits;
                            } else {
                                $NricAge = 2000 + $yearDigits;
                            }

                            $age = $currentYear - $NricAge;
                            $ageRange = $this->getAgeRange($age);
                            $submissionEntity->setField3($age);
                            $submissionEntity->setField4($ageRange);
                        } else {
                            // For passport or other ID formats, don't set age fields
                            $submissionEntity->setField3(null);
                            $submissionEntity->setField4(null);
                        }
                        $submissionEntity->setAttachmentNo($param->receipt_no);
                        $submissionEntity->setReceiverFullName(isset($param->receiver_full_name) ? $param->receiver_full_name : "");
                        $submissionEntity->setReceiverMobileNo(isset($param->receiver_mobile_no) ? $param->receiver_mobile_no : "");
                        $submissionEntity->setAddress1(isset($param->address1) ? $param->address1 : "");
                        $submissionEntity->setAddress2(isset($param->address2) ? $param->address2 : "");
                        $submissionEntity->setCity(isset($param->city) ? $param->city : "");
                        $submissionEntity->setState(isset($param->state) ? $param->state : "");
                        $submissionEntity->setPostcode(isset($param->postcode) ? $param->postcode : "");

                        if(isset($param->field8)){
                            $submissionEntity->setField8($param->field8);
                        }

                        /***********************************/
                        /***   Product Selection Data    ***/
                        /***********************************/
                        if(isset($param->product)){
                            $submissionEntity->setField10($param->product);
                        }
                        // Set status and reject reason based on duplicate check
                        if ($isDuplicate) {
                            $submissionEntity->setStatus('REJECTED');
                            $submissionEntity->setRejectReason('DUPLICATE RECEIPT');
                            $submissionEntity->setStatus1('REJECTED');
                            $submissionEntity->setReason1('DUPLICATE RECEIPT');
                        } else {
                            $submissionEntity->setStatus('PROCESSING');
                        }

                        $submissionEntity->setCreatedDate(new \DateTime);

                        /***********************************/
                        /***     Upload File Fields      ***/
                        /***********************************/
                        if (str_starts_with($contentType, 'multipart/form-data')) {
                            // To commonize the function below whether is multipart or not.
                            // eventually we are moving away from base64 due to complication.
                            $param->upload_receipt = 1;
                        }

                        if(isset($param->upload_receipt)){
                            $newGen = $this->uniqueGen->generate("RC-".uniqid());
                            if($this->getParameter('app.s3_secret_key') != ""){
                                if (str_starts_with($contentType, 'multipart/form-data')) {
                                    // This part is now active. multipart/form-data mode.
                                    $fileObject = $request->files->get('upload_receipt');
                                    $extension = $this->mimeTypeToExtension( $fileObject->getMimeType() );
                                    $this->uploadFileObjectLocal($newGen.$extension , $fileObject, $folderPath) ;

                                    $target_path = $folderPath."//".$newGen.$extension;
                                    $target_file = file_get_contents($target_path);

                                    $this->uploadToBucketFileObject($newGen.$extension, $target_file) ; 
                                    $submissionEntity->setAttachment( $newGen.$extension );

                                } else {
                                    // Added a localize upload.
                                    $this->uploadBase64StringLocal($newGen, $param->upload_receipt, $folderPath); 

                                    $target_img = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '',$param->upload_receipt));
                                    $this->uploadToBucketBase64($newGen.".jpg",$target_img);

                                    $submissionEntity->setAttachment($newGen.'.jpg');
                                }
                            }
                            else{
                                if (str_starts_with($contentType, 'multipart/form-data')) {
                                    // 1. get extension for upload_receipt field and upload_receipt2.
                                    // 2. collect fileobject from field upload_receipt and upload_receipt2.
                                    // $this->uploadFileObjectLocal($newGen, $fileObject, $folderPath) 

                                    // $submissionEntity->setAttachment( $newGen. 'extension' );
                                } else {
                                    $this->uploadBase64StringLocal($newGen, $param->upload_receipt, $folderPath);
                                
                                    $submissionEntity->setAttachment($fullPathGen);
                                }
                            }
                        } 
                        
                        $this->em->persist($submissionEntity);
                        $this->em->flush();
                        
                        if (isset($submissionCaptcha)) {
                            $submissionCaptcha->setRefUserId($_NEWUSER->getId());
                            $this->em->persist($submissionCaptcha);
                            $this->em->flush();
                        }
                        
                        // to DIY
                        $diyRes = $this->toDIY(
                            $this->convertFieldtoQuest($param->channel),
                            $submissionEntity,
                            $param->full_name,
                            $param->mobile_no,
                            $param->receipt_no,
                            $param->email,
                            $param->national_id,
                            isset($param->product) ? $param->product : null,
                            isset($param->state) ? $param->state : null,
                            isset($param->field8) ? $param->field8 : null
                        );
                        if($diyRes){
                            $this->sendActivity('TO DIY SUCCESS', $request, "submission ID:".$submissionEntity->getId(), "", "", "");
                        }
                        else{
                            $this->sendActivity('TO DIY FAILED', $request, "submission ID:".$submissionEntity->getId(), "", "", "");
                        }

                        // EMAIL EDM
                        // $start_msg = "Dear ".$submissionEntity->getFullName().",<BR><BR>";
                        // if($param->form_code != 'CVSTOFT'){
                        //     $mid_msg = '
                        //     <a href="'.$this->getParameter('app.proxy_url').$request->getLocale().'/chk_status">
                        //     <img style="display: block; width:100%;border:0;" src="'.$this->getParameter('app.base_url').'/build/images/EDM_REDEMPTION_'.$param->locale.'.png" alt="" />
                        //     </a>
                        //     ';
                        // }
                        // else{
                        //     $mid_msg = '
                        //     <a href="'.$this->getParameter('app.proxy_url').'">
                        //     <img style="display: block; width:100%;border:0;" src="'.$this->getParameter('app.base_url').'/build/images/EDM_CONTEST_'.$param->locale.'.png" alt="" />
                        //     </a>
                        //     ';
                        // }
                        // $end_msg = "";
                        // $message = $start_msg.$mid_msg.$end_msg;
                        // $this->mailer->sendTwigEmail(
                        //     $message,
                        //     [$submissionEntity->getEmail()],
                        //     $this->getParameter('app.site_name'),
                        //     $this->getParameter('app.email_title'),
                        //     "",
                        //     false
                        // );

                        return new JsonResponse([
                            'result' => true,
                            'message' => EnumError::ALLGOOD
                        ], Response::HTTP_OK);

                    } catch(\Exception $e){
                        $this->sendActivity('Submission Failed', $request, $param->form_code, "", "", $username);
                        
                        $errorDetails = $this->getDetailedErrorMessage($e);
                        $this->logger->error('Submit Endpoint Error (New User)', [
                            'exception_class' => get_class($e),
                            'error_message' => $e->getMessage(),
                            'error_details' => $errorDetails,
                            'trace' => $e->getTraceAsString()
                        ]);
                        
                        return new JsonResponse([
                            'result' => false,
                            'message' => $errorDetails
                        ], Response::HTTP_OK);
                    }
                }
            break;
            case "checkentry":
                if (null != $param->mobile_no) {
                    // TODO: Backend follow Frontend code
                    $RESULT_RAW = $this->em->getRepository(Submission::class)->getAllSubmissionResult($param->mobile_no); 
                    $RESULTS = [];
                    foreach ($RESULT_RAW as $record) {
                        $productDetail = $this->em->getRepository(Submission::class)->getMatchingSubmissionProductDetail(
                            $record['id'], 
                            $record['product_category']
                        );
                        $mergedRecord = array_merge($record, $productDetail ?? []);
                        $RESULTS[] = $mergedRecord;
                    }
                    
                    foreach ($RESULTS as $key => $value) {
                        $RESULTS[$key]['channel'] = $this->convertFieldtoTitle($value['submit_type']);
                    }
                    return new JsonResponse([
                        'result' => $RESULTS,
                        'message' => EnumError::ALLGOOD
                    ], Response::HTTP_OK);
                }
                return new JsonResponse([
                    'result' => false,
                    'message' => EnumError::INVALID_METHOD
                ], Response::HTTP_OK);
            break;
            case "get_postal":
                $filter = isset($param->region) ? $param->region : null;
                $RESULTS = $this->em->getRepository(Postal::class)->getAllPostal($filter);
                $RESULTS ?? [];
                return new JsonResponse([
                    'result' => $RESULTS,
                    'message' => EnumError::ALLGOOD
                ], Response::HTTP_OK);
            break;
            case "get_otp":
                $otp = new Otp();
                $existingOtp = $this->em->getRepository(Otp::class)->findOneBy(
                    array( 'phone_no' => $param->mobile_no )
                );
                if(isset($existingOtp)){
                    $existingOtp->setCode(rand(100000,999999));
                    $existingOtp->setValid(true);
                    $this->em->persist($existingOtp);
                    $this->em->flush();

                    // TEMPORARY TO 1664B SERVER TO POST SMS. DELETE AFTER INIT
                    // $headers =  array(
                    //     'Content-Type: application/x-www-form-urlencoded',
                    //     'Authorization: Bearer '.$this->getParameter('app.backup_sms_url_key')
                    // );
                    // $postData = [
                    //     'message'=>'1664: Your OTP is '.$existingOtp->getCode().'. Use this code to complete your submission. This OTP expires in 1 minute. DO NOT SHARE IT WITH ANYONE.',
                    //     'mobile_no'=>$param->mobile_no
                    // ];
                    // try{
                    //     $RES = $this->cts->curlToUrl($this->getParameter('app.backup_sms_url').'sms/backup',null,true, $postData, $headers);
                    //     error_log(print_r('SUCCESS TO BACKUP SMS -'.$RES,true));
                    // }
                    // catch(\Exception $e){
                    //     error_log(print_r('FAILED TO BACKUP SMS -'.$RES,true));
                    // }
                    $this->sms->smsBlast($param->mobile_no,'1664: Your OTP is '.$existingOtp->getCode().'. Use this code to complete your submission. This OTP expires in 1 minute. DO NOT SHARE IT WITH ANYONE.');
                    return new JsonResponse([
                        'result' => true,
                        'message' => EnumError::ALLGOOD
                    ], Response::HTTP_OK);
                }
                $otp->setPhoneNo($param->mobile_no);
                $otp->setCode(rand(100000,999999));
                $otp->setCreatedDate(new \DateTime);
                $otp->setValid(true);
                $this->em->persist($otp);
                $this->em->flush();

                // TEMPORARY TO 1664B SERVER TO POST SMS. DELETE AFTER INIT
                // $headers =  array(
                //     'Content-Type: application/x-www-form-urlencoded',
                //     'Authorization: Bearer '.$this->getParameter('app.backup_sms_url_key')
                // );
                // $postData = [
                //     'message'=>'1664: Your OTP is '.$otp->getCode().'. Use this code to complete your submission. This OTP expires in 1 minute. DO NOT SHARE IT WITH ANYONE.',
                //     'mobile_no'=>$param->mobile_no
                // ];
                //     try{
                //         $RES = $this->cts->curlToUrl($this->getParameter('app.backup_sms_url').'sms/backup',null,true, $postData, $headers);
                //         error_log(print_r('SUCCESS TO BACKUP SMS -'.$RES,true));
                //     }
                //     catch(\Exception $e){
                //         error_log(print_r('FAILED TO BACKUP SMS -'.$RES,true));
                //     }
                $this->sms->smsBlast($param->mobile_no,'1664: Your OTP is '.$otp->getCode().'. Use this code to complete your submission. This OTP expires in 1 minute. DO NOT SHARE IT WITH ANYONE.');
                return new JsonResponse([
                    'result' => true,
                    'message' => EnumError::ALLGOOD
                ], Response::HTTP_OK);
            break;
            case "check_otp":
                if(isset($param->otp)){
                    $existingOtp = $this->em->getRepository(Otp::class)->findOneBy(
                        array( 'phone_no' => $param->mobile_no, 'code' => $param->otp, 'is_valid' => true )
                    );
                    if(isset($existingOtp)){
                        $existingOtp->setValid(false);
                        $this->em->persist($existingOtp);
                        $this->em->flush();
                        return new JsonResponse([
                            'result' => true,
                            'message' => EnumError::ALLGOOD
                        ], Response::HTTP_OK);
                    } else {
                        return new JsonResponse([
                            'result' => false,
                            'message' => EnumError::INVALID_OTP
                        ], Response::HTTP_OK);
                    }
                }
                return new JsonResponse([
                    'result' => false,
                    'message' => EnumError::INVALID_METHOD
                ], Response::HTTP_OK);
            break;
            case "get_user_role":
                $role = $profile->getRoles()[0];
                $response = new JsonResponse([
                    'role' => $role,
                ], Response::HTTP_OK);
                return $response;
            break;
            case "enquiry":
                if (null != $param->email) {

                    if($_SERVER['APP_ENV'] !== 'dev'){
                        /* this is not a relay smtp email, but a legit support enquiry email, check faq for that. */ 
                        $emails = ['carlsberg@s360plus.com'];
                    }
                    else{
                        $emails = ['icywolfy@gmail.com'];
                    }
                    $subject = "Enquiry from CNY 2026 Enquiry Form";
                    $message = '';
                    $message.="From: ".$param->full_name;
                    $message.="<BR>Contact Email: ".$param->email;
                    $message.="<BR>Contact No: ".$param->mobile_no;                    
                    $message.="<BR>Enquiry: ".$param->enquiry;
                    $message.="<BR>From Carlsberg Contact Form, Carlsberg Cny2026";
                    $this->mailer->sendEmail( $message, $emails, 'Carlsberg Contact Form', $subject);
                    return new JsonResponse([
                        'result' => true,
                        'message' => EnumError::ALLGOOD
                    ], Response::HTTP_OK);
                }
                return new JsonResponse([
                    'result' => false,
                    'message' => EnumError::INVALID_METHOD
                ], Response::HTTP_OK);
            break;
            default:
                return new JsonResponse([
                    'result' => false,
                    'message' => EnumError::INVALID_METHOD
                ], Response::HTTP_OK);
            break;
        }
    }

    #[Route('/endpoint/{method}',
        name: 'app_endpoint_get',
        methods: ['GET'],
        requirements: ["method" => "^[a-zA-Z0-9]+(?:[\w _]*[a-zA-Z0-9]+)*$"]
    )]
    public function getFn(Request $request, $method = null): JsonResponse
    {
        $RESULT = null;
        $param = json_decode( $request->getContent() );

        if (!$this->checkLegitToken($request)) {
            return new JsonResponse([
                'result' => false,
                'message' => EnumError::SESSION_OUT
            ], Response::HTTP_OK);
        }

        /***********************************/
        /***      Add Get Logic          ***/
        /***********************************/


        return new JsonResponse([
            'result' => false,
            'message' => EnumError::ALLGOOD
        ], Response::HTTP_OK);
    }


    /*************************************** OPTIONAL REST ****************************************/
    #[Route('/endpoint/{method}',
        name: 'app_endpoint_del',
        methods: ['DELETE'],
        requirements: ["method" => "^[a-zA-Z0-9]+(?:[\w _]*[a-zA-Z0-9]+)*$"]
    )]
    public function deleteFn(Request $request, $method = null): JsonResponse
    {
        $RESULT = null;
        $param = json_decode( $request->getContent() );

        if (!$this->checkLegitToken($request)) {
            return new JsonResponse([
                'result' => false,
                'message' => EnumError::SESSION_OUT
            ], Response::HTTP_OK);
        }

        /***********************************/
        /***      Add Delete Logic       ***/
        /***********************************/


        return new JsonResponse([
            'result' => false,
            'message' => EnumError::ALLGOOD
        ], Response::HTTP_OK);
    }

    #[Route('/endpoint/{method}',
        name: 'app_endpoint_put',
        methods: ['PUT'],
        requirements: ["method" => "^[a-zA-Z0-9]+(?:[\w _]*[a-zA-Z0-9]+)*$"]
    )]
    public function putFn(Request $request, $method = null): JsonResponse
    {
        $RESULT = null;
        $param = json_decode( $request->getContent() );

        if (!$this->checkLegitToken($request)) {
            return new JsonResponse([
                'result' => false,
                'message' => EnumError::SESSION_OUT
            ], Response::HTTP_OK);
        }

        /***********************************/
        /***       Add Put Logic         ***/
        /***********************************/

        return new JsonResponse([
            'result' => false,
            'message' => EnumError::ALLGOOD
        ], Response::HTTP_OK);
    }

    #[Route('/endpoint/{method}',
        name: 'app_endpoint_get_roles',
        methods: ['GET'],
        requirements: ["method" => "^[a-zA-Z0-9]+(?:[\w _]*[a-zA-Z0-9]+)*$"]
    )]

    public function getUserRoles(Request $request): JsonResponse
    {
        dd('DIE');

    }

    private function saveToBlackBox($payLoad, $status, $score , $userAgent) {
        $bBox = new Payload();
        $bBox->setCreatedDate(new \DateTime());
        $bBox->setPayload($payLoad);
        $bBox->setStatus($status);
        $bBox->setScore($score);
        $bBox->setUserAgent( $userAgent );
        $this->em->persist($bBox);
        $this->em->flush();
    }

    private function toDIY($questId, $submission, $full_name, $mobile_no, $receipt_no,$email,$nationalId,$product=null, $state=null, $qna=null) {
        // PRODUCT still waiting for YM to give variable.
        if(!$this->getParameter('app.to_diy')){
            return true;
        }
        // PRECAUTIONARY STEPS just incase.
        // if ( !str_contains("bestwithcarlsberg.my", $this->getParameter('app.proxy_url')) ) {
        //     return true;
        // }

        if (empty($email)) {
            $email = "NA";
        }
        
        $headers =  array(
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Bearer '.$this->getParameter('app.diy_integration_key')
        );
        
        // Determine receipt URL based on S3 configuration
        $receiptUrl = '';
        if($this->getParameter('app.s3_secret_key') != "") {
            $receiptUrl = $this->getParameter('app.s3_base_url').$this->getParameter('app.s3_bucket_name').'/'.$submission->getAttachment();
        } else {
            $receiptUrl = $this->getParameter('app.base_url').'images/uploaded/receipt/'.$submission->getAttachment();
        }
        
        // Base data for all integrations
        $postData = array(
            'contest_id'=>$questId,
            'sub_id'=>$submission->getId(),
            'full_name'=>$full_name,
            'mobile_number'=>$mobile_no,
            'email_address'=>$email,
            'mykad'=>$nationalId,
        );

        if($qna != null){
            $postData['qna'] = $qna;
        }
        
        // Add data based on integration ID
        // GWP integrations (139, 140, 141): include receipt_no and receipt
        // CVS integration (142): include receipt only
        if ($questId == 152) {
            // GWP: name, nric, mobile no, email, receipt no, receipt, state, product
            $postData['receipt_no'] = $receipt_no;
            $postData['receipt'] = $receiptUrl;
            $postData['gwp'] = $product;
            $postData['state_del'] = $state;
        } else {
            // Default behavior for other integration IDs (include all data)
            // $postData['receipt_no'] = $receipt_no;
            $postData['receipt'] = $receiptUrl;
        }

        try {
            $RES = $this->cts->curlToUrl($this->getParameter('app.diy_whatsapp_api').'submission',null,true, $postData, $headers);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function convertFieldtoQuest($channel) {
        switch($channel) {
            case "MONT":
                return $this->getParameter('app.integration_id1');
            break;
            case "SHM":
            case "SHM_EM":
            case "SHM_WM":
            case "SHM_AEON":
            case "SHM_LOTUS":
            case "SHM_APPEAL":
            case "SHM_FULL_REDEEM":
                return $this->getParameter('app.integration_id2');
            break;
            case "TONT":
                return $this->getParameter('app.integration_id4');
            break;
            case "99SM":
                return $this->getParameter('app.integration_id3');
            break;
            case "ECOMM":
                return $this->getParameter('app.integration_id2');
            break;
            case "CVSTOFT":
                return $this->getParameter('app.integration_id5');
            break;
        }
    }

    private function convertFieldtoTitle($channel) {
        switch($channel) {
            case "MONT":
                return 'Bars, Cafes & Restaurants​';
            break;
            case "TONT":
                return 'Coffee Shop & Food Court';
            break;
            case "CVSTOFT":
                return 'CONVENIENCE STORES & MINI MARTS';
            break;
            case "SHM_WM":
            case "SHM_AEON":
            case "SHM_LOTUS":
            case "SHM_APPEAL":
            case "SHM_FULL_REDEEM":
                return 'Supermarket, Hypermarket & E-commerce';
            break;
            case "SHM_EM":
                return 'Supermarket, Hypermarket & E-commerce';
            break;
            case "S99":
                return '99 SPEEDMART';
            break;
            case "ECOMM":
                return 'E-COMMERCE';
            break;
        }
    }

    private function getAgeRange($age) {
        if ($age >= 21 && $age <= 25) {
            return "21-25";
        } else if ($age >= 26 && $age <= 30) {
            return "26-30";
        } else if ($age >= 31 && $age <= 35) {
            return "31-35";
        } else if ($age >= 36 && $age <= 40) {
            return "36-40";
        } else if ($age >= 41 && $age <= 45) {
            return "41-45";
        } else if ($age >= 46 && $age <= 50) {
            return "46-50";
        } else {
            return "50>";
        }
    }

    private function maleFemale($nationalId){
        $last2Digit = substr($nationalId, -2);
        if(intval($last2Digit) % 2 == 0){
            return "M";
        } else {
            return "F";
        }
    }

    private function isMalaysianNRIC($nationalId) {
        // Remove any hyphens or spaces
        $cleanId = preg_replace('/[-\s]/', '', $nationalId);

        // Malaysian NRIC should be exactly 12 digits
        if (strlen($cleanId) !== 12 || !ctype_digit($cleanId)) {
            return false;
        }

        // Additional validation: check if first 6 digits form a valid date (YYMMDD)
        $year = substr($cleanId, 0, 2);
        $month = substr($cleanId, 2, 2);
        $day = substr($cleanId, 4, 2);

        // Basic date validation
        if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
            return false;
        }

        return true;
    }

    private function checkCaptcha($captcha){
        $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify'; // URL to the reCAPTCHA server
        $recaptcha_secret = $this->getParameter('app.google_captcha_secret_key'); // Secret key
        $recaptcha_response = $captcha; // Response from reCAPTCHA server, added to the form during processing
        $recaptcha = file_get_contents($recaptcha_url.'?secret='.$recaptcha_secret.'&response='.$recaptcha_response); // Send request to the server
        $recaptcha = json_decode($recaptcha); // Decode the JSON response
        return $recaptcha;
    }
}
