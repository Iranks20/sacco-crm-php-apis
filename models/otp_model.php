<?php
class otp_model extends Model{
	
    public function __construct(){
        parent::__construct();
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    }

    // creating otp
    function create_otp($otpvalue, $email) {    
        $table = 'otp';
        $data = array(
            'name' => $otpvalue,
            'email' => $email
        );
        $id = $this->db->InsertData($table, $data, $return_id = true);
    
        if ($id > 0) {
            $message = "Clic Social Banking OTP ".$otpvalue;
            $this->sendEmail($email, $message, $sub = 'Clic social banking OTP');
            $response = array(
                'status' => 'success',
                'message' => 'OTP has been successfully sent.'
            );
        } else {
            $response = array(
                'status' => 'error',
                'message' => 'Failed to send OTP.'
            );
        }
    
        echo json_encode($response);
    }    
    
    function password(){
        
        $pwd = Hash::create('sha256','admin',HASH_ENCRIPT_PASS_KEYS);
        echo $pwd;
    }
    
    function verfy_otp($otpvalue, $email){
    
        $otp = $this->db->prepare("SELECT status FROM otp WHERE name = '$otpvalue' and email = '$email'");
        $a = $otp->execute();
        $b = $otp->fetch();
    
        if ($b['status'] == 'pending'){
            $otpupdate = array(
                'status' => 'verified'
            );
            $this->db->UpdateData('otp', $otpupdate, "name = {$otpvalue} ");
            Session::set('verified_otp', $otpvalue);
            header('Location: ' . URL);
        }else{
            header('Location: ' . URL . 'otp?msg=failed');
        }
    
    }

	function getStaffDetails($id){
		$office_id=$_SESSION['office'];
		if ($_SESSION['Isheadoffice'] == 'Yes') {
			$result =  $this->db->SelectData("SELECT * FROM m_staff WHERE id='".$id."'");
		} else {
			$result =  $this->db->SelectData("SELECT * FROM m_staff WHERE id='".$id."' and office_id = '".$office_id."' ");
		}
		return $result;
	}
    
    function getUserNameDetails($username){

        $ipsth = $this->db->prepare("SELECT * FROM m_staff WHERE username = '$username'");
        $a = $ipsth->execute();
        $b = $ipsth->fetch();

        return $b;

    }
	
	function ChangePassword($id){
	    
        $user = $this->GetStaffDetails($id);
        $email = $user[0]['email'];
        $reset_by = $_SESSION['office'];
    	$password = Hash::create('sha256',$_POST['new_password'], HASH_ENCRIPT_PASS_KEYS);	 
           
        $today = date('Y-m-d');
		$data = array(
			'password' => $password,
			'next_reset' => date('Y-m-d 00:00:00', strtotime($today. ' + 30 days'))
		);

		$this->db->UpdateData('m_staff', $data,"`id` = '".$id."'");
	
		$message =  "Hello, your social banking password has been reset. <p>Password: ".$_POST['new_password']."</p>";
	    $this->sendEmail($email, $message);
		   
		header('Location: ' . URL . 'otp?reset=success');
		
	}

}