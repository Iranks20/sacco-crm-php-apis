<?php

class notfound extends Controller{

public function __construct(){
parent::__construct();

//Auth::handleSignin();
//Auth::CheckSession();

 $_SESSION['timeout'] = time(); 
}
public function index(){

$this->view->renders('404');
}


}