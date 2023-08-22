<?php

class login extends Controller{

public function __construct(){
	parent::__construct();
	// Auth::handlesession();
    header('Access-Control-Allow-Origin: http://localhost:3000');
	header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
	header('Access-Control-Allow-Headers: Content-Type');
}

function index(){
echo "clic is running";
}

function admin(){
	$this->view->renders('login');
}

function qrlogin(){
	$this->view->renders('qrlogin');
	//$this->view->renders('login');
}

function getUserData() {
	
	if(hash('sha512', "{$_POST['token']}{$_POST['email']}{$_POST['phone']}{$_POST['name']}") == $_POST['signature']){
	
	    $file_name = "qrs/" . $_POST['token'] . ".txt";
	    $postData = $_POST['token'] . "-" . $_POST['email'] . "-" . $_POST['phone'];
	    echo file_put_contents($file_name, $postData);
	    //return $this->model->MakeJsonResponse(100,"Success");
	} else{
	    echo "Please check User data!!!";
	    //return $this->model->MakeJsonResponse(203,"Please check User data!!!");
	}
}

function waitData($string)
{
    header('Access-Control-Allow-Origin: *');
	$file_name = "qrs/" . $string . ".txt";
	
    $string	= file_get_contents($file_name);
	$data = $this->model->getAuthQRUser($string);
	/*
    	if(isset($data['username']) && isset($data['hashed_password'])){
    	    unlink($file_name);
    	}
    */
	$rs = $this->model->authUser($data);
	echo json_encode($rs);
}

function unlocklockscreen(){	
	$this->view->renders('lock_screen');	
}

function forgotpass(){
	$this->view->renders('passchangeform');	
}

function auth() {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    if ($data === null) {
        $response = array('error' => 'Invalid JSON data');
    } else {
        $rs = $this->model->authUser($data);
        $response = $rs;
    }

    echo json_encode($response);
    exit;
}

function pass(){
 $pass = "admin";
 $pwd = Hash::create('sha256',$pass,HASH_ENCRIPT_PASS_KEYS);
 echo $pwd;
}

function logout(){
    $db = new Database();
    session_start();

    $db->InsertLog("LOGOUT","","LOGIN",1);

	$path = "public/images/avatar/". $_SESSION['username'] . ".txt" ;

	if (file_exists($path)) {
		$text = file_get_contents($path);
		$todelete = explode(':', $text);
		for ($i=0; $i < sizeof($todelete); $i++) {
			if (file_exists($todelete[$i])) {
				unlink($todelete[$i]);
			}
		}
		unlink($path);
	}
	
	$id = $_SESSION['user_id'];
	$session_data = array(
        'login_session' => NULL,
    );
    $db->UpdateData('m_staff', $session_data, "`id` = {$id}");
	
	session_destroy();
	header('Location: ' . URL . 'login');
	exit;
}


function changePass($data){
	$data=$_POST;
	$this->model->changePass();
}

	

}