<?php


class Otp extends Controller{
    public function __construct(){
        parent::__construct();
        // Auth::handleSignin();
        // Auth::CheckAuthorization();
        // $_SESSION['timeout'] = time();
        header('Access-Control-Allow-Origin: http://localhost:3000');
	    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
	    header('Access-Control-Allow-Headers: Content-Type');
    }
    
    function index(){        
        $userDetails = $this->model->getUserNameDetails($_SESSION['username']);
        $id = $userDetails['id'];
        $time_now = date_create();
        $next_reset = date_create($userDetails['next_reset']);
         
        if(is_null($userDetails['next_reset']) || $userDetails['next_reset'] == "" || $time_now > $next_reset){
            header('Location: ' . URL . 'otp/changePassword/' . $id);
        }
        
        if(isset($_SESSION['verified_otp'])){
            header('Location: '. URL);
			exit;
		}
		
    	$email = $_SESSION['email'];
    	$this->sendotp();
    	list($first, $last) = explode('@', $email);
        $first = str_replace(substr($first, '3'), str_repeat('*', strlen($first)-3), $first);
        $last = explode('.', $last);
        $last_domain = str_replace(substr($last['0'], '1'), str_repeat('*', strlen($last['0'])-1), $last['0']);
        $hideEmailAddress = $first.'@'.$last_domain.'.'.$last['1'];
        $this->view->email = $hideEmailAddress;
    	$this->view->renders('otppage');
    	
    
       // print_r($userDetails);
        die();
    
    }
    
    function password(){
        
        $this->model->password();
    }
    
    function submitotp(){
        $data = json_decode(file_get_contents('php://input'), true);
    
        if (isset($data['email']) && isset($data['otp'])) {
            $email = $data['email'];
            $otpvalue = $data['otp'];
            $rs = $this->model->verfy_otp($otpvalue, $email);
            // Handle $rs if needed
        } else {
            $response = array(
                'status' => 'error',
                'message' => 'Email or OTP is missing in JSON input.'
            );
            echo json_encode($response);
        }
    }
    
    
    function sendotp(){
        $data = json_decode(file_get_contents('php://input'), true);
    
        if (isset($data['email'])) {
            $email = $data['email'];
            $otpnumber = rand(1231, 7879);
            $message = "Clic Social Banking OTP " . $otpnumber;
            $createotp = $this->model->create_otp($otpnumber, $email);
            // Handle successful OTP creation if needed
        } else {
            $response = array(
                'status' => 'error',
                'message' => 'Email is missing in JSON input.'
            );
            echo json_encode($response);
        }
    }
    
    
	function changePassword($id){
	    if($id == $_SESSION['user_id']){
    		$this->view->details = $this->model->getStaffDetails($id);
    		$this->view->render('forms/staff/changepassword');
	    } else {
	        header('Location: ' . URL);
	    }
	}
	
	function updatePassword($id){
	    $this->model->ChangePassword($id);
	}
}