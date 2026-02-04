<?php 
namespace App\Service;

use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
/**
 *
 * A GoogleSheet API Interface Class.
 * No database required.
 * 
 */

class GSheetHelperService {
    public function __construct( EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }
	/**
	 * Connects to GSheetAPI. 
	 * 
	 * @param $secretKey required object from the json file. 
     * @param $spreadsheetId required String with at least denote the spreadsheetId. 
	 * @param $targetRange the range of the sheet to target. 
	 * @param $valueArr the array of values.
     * @return object the result.
	 *
	 */
	function updateValues($secretKey, $spreadsheetId, $targetRange, $valuesArr)
    {
        $client = new \Google_Client();
		$client->setApplicationName('Google Sheets API');
		$client->setAuthConfig($secretKey);
		$client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
		$service = new \Google_Service_Sheets($client);
		try{
			$values = [[] , [$valuesArr]];
			$body = new \Google_Service_Sheets_ValueRange([
				'values' => $values
			]);
			$params = [
				'valueInputOption' => 'RAW'
			];
			// executing the request
			$result = $service->spreadsheets_values->update(
				$spreadsheetId, 
				$targetRange, 
				$body, 
				$params
			);
			// printf("%d cells updated.", $result->getUpdatedCells());
			return $result;
    	} catch(Exception $e) {
            // TODO(developer) - handle error appropriately
            $this->logger->info('Message: ' .$e->getMessage());
    	}
    }
	
	/**
	 * Connects to GSheetAPI. 
	 * 
	 * @param $secretKey required object from the json file. 
     * @param $spreadsheetId required String with at least denote the spreadsheetId. 
	 * @param $targetRange the range of the sheet to target. 
	 *
	 * @return object the result.
	 *
	 */
	public function getRangeData($secretKey, $spreadsheetId, $targetRange) {
		$client = new \Google_Client();
        $client->setApplicationName('Google Sheets API');
        $client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
        $client->setAccessType('offline');
            
        $client->setAuthConfig($secretKey);
        // configure the Sheets Service
        $service = new \Google_Service_Sheets($client);
        $spreadsheet = $service->spreadsheets->get($spreadsheetId);
        $response = $service->spreadsheets_values->get($spreadsheetId, $targetRange);
        $values = $response->getValues();

        return $values;            
	}

	public function numToLetters($num, $uppercase = true) {
		$letters = '';
		while ($num > 0) {
			$code = ($num % 26 == 0) ? 26 : $num % 26;
			$letters .= chr($code + 64);
			$num = ($num - $code) / 26;
		}
		return ($uppercase) ? strtoupper(strrev($letters)) : strrev($letters);
	}
	/**
	 * Finds matching first row from the results of the targetted range. 
	 * 
	 * @param $dataSheet the sheet array. 
     * @param $cellIndex the index of the column. 
	 * @param $search the string to search. 
	 *
	 * @return object the result.
	 *
	 */
	public function findMatchingInRange($dataSheet, $cellIndex, $search) {
		if (is_array($dataSheet)) {
			for ($i = 1; $i < count($dataSheet); $i++) {
				$rowData = (array) $dataSheet[$i] ;				
				if (isset($rowData[$cellIndex])) {
					if ($rowData[$cellIndex] == $search) {
						return [
							array (
								"column" => intval($cellIndex),
								"row" => $i,
								"range" => $this->numToLetters($cellIndex + 1) . ($i + 1), 
								"result" => $dataSheet[$i]
							)							
						];
					}
				} else {
					return null;
				}
			}
		} else {
			return null;
		}
	}
	/**
	 * Finds matching first row from the results of the targetted range. 
	 * 
	 * @param $gdriveLink link from regular uploaded photo something like https://drive.google.com/file/d/1XV7vL-sqwCF8P_8ecaA_bZJHb40IzLBl/view?usp=drive_link. 
     *
	 * @return string the export url for hotlink.
	 *
	 */
	public function getProcessDriveLink($gdriveLink) {
		// @2023 when i wrote this the gdrive shared link photo was of a kind
		// https://drive.google.com/file/d/1XV7vL-sqwCF8P_8ecaA_bZJHb40IzLBl/view?usp=drive_link
		
		if (str_contains($gdriveLink, 'https://drive.google.com/file')) {
			$finalArray = explode("/",$gdriveLink);
			$uniqueKey = $finalArray[5];
			$finalExportURL = "https://drive.google.com/uc?export=view&id=".$uniqueKey;
			return $finalExportURL;
		} else {
			return "";
		}
	}
	
}


?>