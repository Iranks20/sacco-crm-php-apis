<?php

class Wallets extends Controller{

	public function __construct(){
		parent::__construct();
	}

	function index()
	{
		try {
			$office = $_SERVER['HTTP_OFFICE'];

			$wallets = $this->model->getWallets($office);

			$response = array(
				"status" => 200,
				"message" => "Wallets retrieved successfully.",
				"wallets" => $wallets
			);

			header('Content-Type: application/json');
			http_response_code($response['status']);
			echo json_encode($response);
		} catch (Exception $e) {
			$errorResponse = array("status" => 500, "message" => $e->getMessage());
			header('Content-Type: application/json');
			http_response_code($errorResponse['status']);
			echo json_encode($errorResponse);
		}
	}

	function transactions()
	{
		try {
			$start = date("Ymd", strtotime("-1 months"));
			$end = date("Ymd");

			$transactions = $this->model->getWalletTransactions($start, $end);

			$response = array(
				"status" => 200,
				"message" => "Wallet transactions retrieved successfully.",
				"transactions" => $transactions
			);

			header('Content-Type: application/json');
			http_response_code($response['status']);
			echo json_encode($response);
		} catch (Exception $e) {
			$errorResponse = array("status" => 500, "message" => $e->getMessage());
			header('Content-Type: application/json');
			http_response_code($errorResponse['status']);
			echo json_encode($errorResponse);
		}
	}
	
	function rangetransactions()
	{
		try {
			$requestData = json_decode(file_get_contents('php://input'), true);
			$start = $requestData['start'];
			$end = $requestData['end'];

			$transactions = $this->model->getWalletRangeTransactions($start, $end);

			$response = array(
				"status" => 200,
				"message" => "Wallet transactions retrieved successfully.",
				"transactions" => $transactions
			);

			header('Content-Type: application/json');
			http_response_code($response['status']);
			echo json_encode($response);
		} catch (Exception $e) {
			$errorResponse = array("status" => 500, "message" => $e->getMessage());
			header('Content-Type: application/json');
			http_response_code($errorResponse['status']);
			echo json_encode($errorResponse);
		}
	}


}