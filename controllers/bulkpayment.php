<?php

class Bulkpayment extends Controller{

	public function __construct(){
		parent::__construct();
		Auth::handleSignin();
		Auth::CheckSession();
		Auth::CheckAuthorization();
		$_SESSION['timeout'] = time(); 
	}

	function index(){
	    $url = "https://bulkpayment.clicmonkey.xyz";
		header('Location: ' . $url);
	}
	
}
	
	?>