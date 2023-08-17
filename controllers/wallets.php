<?php

class Wallets extends Controller{

	public function __construct(){
		parent::__construct();
		Auth::handleSignin();
		Auth::CheckSession();
		Auth::CheckAuthorization();
		$_SESSION['timeout'] = time(); 
	}

	function index(){
		$this->view->wallets = $this->model->getWallets();
		$this->view->render('forms/wallets/viewwallets');
	}
	
	function transactions()
	{
	    $this->view->start = date("Ymd", strtotime("-1 months"));
	    $this->view->end = date("Ymd");
		$this->view->wallet_transactions = $this->model->getWalletTransactions();
    	$this->view->render('forms/wallets/wallet_transactions');
	}
	
	function rangetransactions()
	{
	    $this->view->start = $_POST['start'];
	    $this->view->end = $_POST['end'];
		$this->view->wallet_transactions = $this->model->getWalletRangeTransactions();
    	$this->view->render('forms/wallets/wallet_transactions');
	}

}