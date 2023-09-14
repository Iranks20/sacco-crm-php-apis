<?php

//error_reporting(0);
   
class Wallets_model extends Model{
	
	public function __construct(){
		parent::__construct();
    	$this->logUserActivity(NULL); 
    	if (!$this->checkTransactionStatus()) {
    		header('Location: ' . URL); 
    	}
	}

	function getWallets($office)
	{
		try {
			$results = $this->db->SelectData("SELECT * FROM sm_mobile_wallet AS a JOIN members AS b ON a.member_id = b.c_id WHERE a.bank_no = '$office'");
			return $results;
		} catch (Exception $e) {
			throw $e;
		}
	}

	function getWalletTransactions($start, $end)
	{
		try {
			$data = array(
				'app_id' => 4,
				'from_date' => $start,
				'to_date' => $end,
				'status' => "all" 
			);

			$url = SEARCH_TRANSACTIONS;

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$server_output = curl_exec($ch);
			curl_close($ch);

			$response = (array) json_decode($server_output);

			return (array) $response['payments'];
		} catch (Exception $e) {
			throw $e;
		}
	}

	function getWalletRangeTransactions($start, $end)
	{
		try {
			$data = array(
				'app_id' => 4,
				'from_date' => date_format(date_create($start), "Ymd"),
				'to_date' => date_format(date_create($end), "Ymd"),
				'status' => "all"
			);

			$url = SEARCH_TRANSACTIONS;

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$server_output = curl_exec($ch);
			curl_close($ch);

			$response = (array) json_decode($server_output);

			return (array) $response['payments'];
		} catch (Exception $e) {
			throw $e;
		}
	}

}