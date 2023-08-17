<?php

class tests extends Controller{

	public function __construct(){
		parent::__construct();
		Auth::handleSignin();
		Auth::CheckSession();
		Auth::CheckAuthorization();
		$_SESSION['timeout'] = time(); 
	}

	function savingswithdraw(){
		$rs = $this->model->testWithdraw();
		echo json_encode($rs );
	}

 

}