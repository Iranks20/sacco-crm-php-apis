<?php

class dashboard extends Controller{

	public function __construct(){
		parent::__construct();
		Auth::handleSignin();
		Auth::CheckSession();
		$_SESSION['timeout'] = time(); 
	};
	function index(){	 

	};
};