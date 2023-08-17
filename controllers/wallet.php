<?php

class Wallet extends Controller{

	public function __construct(){
		parent::__construct();
		$this->post= new SavingsPostings();

		Auth::handleSignin();
		Auth::CheckSession();
		Auth::CheckAuthorization();
		$_SESSION['timeout'] = time(); 
	}
	
	function WalletAccountStatement($acc=null, $accno=null, $all=NULL){
		if ($accno!=null) {
			$this->view->savingsAcc= $acc;
			$this->view->memberid = $accno;	
		}
		$account=null;
		if($acc!=null){
			$account=$acc;	   
		} else {
			$account=$_POST['accno'];   
		}

		$this->view->memberzid = $this->model->getWalletAccountMemberID($account);
		
		///////NEW/////
		if(!empty($account)){
    		$this->view->accountholder=$this->model->getWalletMemberaccount($account);
    		$this->view->wallet_transactions = $this->model->getClixWalletTransactions($acc);
    		//$this->view->wallet_transactions = $this->model->getClixWalletTransactions($this->view->memberzid);
            
            $this->view->savingsAcc = $account;
    		$this->view->render('forms/wallets/clix_wallet_statement');
		}else{
			header('Location: ' . URL . 'members/savingsaccount/');    
		}
		
        /////////////
        /*
		if(!empty($account)){
			$this->view->accountholder=$this->model->getWalletMemberaccount($account);
			$this->view->transactions=$this->model->getWalletAccountTransactions($account);			
			if ($all != NULL) {
				$this->view->alltransactions=$this->model->getAllWalletAccountTransactions($account);
			}
			$this->view->savingsAcc = $account;
			$this->view->render('forms/savings/wallet_accountstatement');
		}else{
			header('Location: ' . URL . 'members/savingsaccount/');    
		}*/
		
	}

	function savingsstatement($acc=null){
		if(!empty($acc)){
			$this->view->savingsAcc= $acc;
		}
		$this->view->render('forms/wallets/wallet_statement_form');
	}

	function savingsaccountdetails(){

		$data = $_POST;
		$this->walletaccountstatement($data['accno'], $data['member_id']);

	}	

	function getmembersavings($acc){
		if(!empty($acc)){ 
			$this->model->getMembersavings($acc);
		}else{			
			
		}
	}
	
}