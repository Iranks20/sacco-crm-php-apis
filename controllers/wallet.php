<?php

class Wallet extends Controller{

	public function __construct(){
		parent::__construct();
		$this->post= new SavingsPostings();
	}
	
	// function WalletAccountStatement($acc=null, $accno=null, $all=NULL){
	// 	if ($accno!=null) {
	// 		$this->view->savingsAcc= $acc;
	// 		$this->view->memberid = $accno;	
	// 	}
	// 	$account=null;
	// 	if($acc!=null){
	// 		$account=$acc;	   
	// 	} else {
	// 		$account=$_POST['accno'];   
	// 	}

	// 	$this->view->memberzid = $this->model->getWalletAccountMemberID($account);
		
	// 	if(!empty($account)){
    // 		$this->view->accountholder=$this->model->getWalletMemberaccount($account);
    // 		$this->view->wallet_transactions = $this->model->getClixWalletTransactions($acc);
            
    //         $this->view->savingsAcc = $account;
    // 		$this->view->render('forms/wallets/clix_wallet_statement');
	// 	}else{
	// 		header('Location: ' . URL . 'members/savingsaccount/');    
	// 	}
	// }
	// jjjjjjjj
	function WalletAccountStatement($office, $accno, $member_id, $acc=null, $all = NULL){
		try {
	
			$account = $accno;
	
			$memberzid = $this->model->getWalletAccountMemberID($account, $office);
			
			if (!empty($account)) {
				$accountholder = $this->model->getWalletMemberaccount($account);
				$wallet_transactions = $this->model->getClixWalletTransactions($acc);
				
				$savingsAcc = $account;
				$resultData = array(
					'memberid' => $accno,
					'accountholder' => $accountholder,
					'wallet_transactions' => $wallet_transactions,
					'savingsAcc' => $account,
				);
	
				// Return JSON response with status and result
				header('Content-Type: application/json');
				echo json_encode(array("status" => 200, "message" => "Success", "result" => $resultData));
			} else {
				throw new Exception("Account not found.");
			}
		} catch (Exception $e) {
			// Handle any exceptions and return a JSON error response
			$errorResponse = array("status" => 500, "message" => $e->getMessage());
			header('Content-Type: application/json');
			http_response_code($errorResponse['status']);
			echo json_encode($errorResponse);
		}
	}

	function savingsstatement($acc=null){
		if(!empty($acc)){
			$this->view->savingsAcc= $acc;
		}
		$this->view->render('forms/wallets/wallet_statement_form');
	}

	// function savingsaccountdetails(){

	// 	$data = $_POST;
	// 	$this->walletaccountstatement($data['accno'], $data['member_id']);

	// }
	function savingsaccountdetails(){
		try {
			$headers = getallheaders();
			$office = $headers['office'];
			$member_id = $headers['member_id'];
	
			if (empty($office)) {
				throw new Exception("Office value not found in headers.");
			}
			$data = json_decode(file_get_contents('php://input'), true);
	
			if (empty($data['accno'])) {
				throw new Exception("Invalid JSON data received.");
			}
			$accno = $data['accno'];
		
			$this->WalletAccountStatement($office, $accno, $member_id );
	
		} catch (Exception $e) {
			$errorResponse = array("status" => 500, "message" => $e->getMessage());
			header('Content-Type: application/json');
			http_response_code($errorResponse['status']);
			echo json_encode($errorResponse);
		}
	}	


	function getmembersavings($acc){
		if(!empty($acc)){ 
			$this->model->getMembersavings($acc);
		}else{			
			
		}
	}
	
}