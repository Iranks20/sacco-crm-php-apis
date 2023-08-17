<?php

//error_reporting(0);

class Staff_model extends Model{
	
	public function __construct(){
		parent::__construct(); 
		$this->logUserActivity(NULL); 
		if (!$this->checkTransactionStatus()) {
			header('Location: ' . URL); 
		}
	}


	function getGLAccounts(){
		$id = $_SESSION['office'];
		$result = $this->db->selectData("SELECT id, name FROM acc_ledger_account where sacco_id= $id ORDER BY name ASC");
		return $result;
	}

	function getStaff(){
		$office_id=$_SESSION['office'];
		if ($_SESSION['Isheadoffice'] == 'Yes') {
			$result =  $this->db->SelectData("SELECT * FROM m_staff WHERE `status` = 'Active' and office_id = '".$office_id."' ");
		} else {
			$result =  $this->db->SelectData("SELECT * FROM m_staff WHERE `status` = 'Active' and office_id = '".$_SESSION['branchid']."' ");
		}
		return $result;
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

	function getStaffAccountDetails($id){
		$office_id=$_SESSION['office'];
		
		$result =  $this->db->SelectData("SELECT cash_account FROM m_staff WHERE id='".$id."' and office_id = '".$office_id."' ");
		
		$results =  $this->db->SelectData("SELECT * FROM acc_ledger_account WHERE id='".$result[0]['cash_account']."' and sacco_id = '".$office_id."' ");

		$acc_details = array();

		if (empty($results)) {
			$acc_details['account_type'] = '';
			$acc_details['account_usage'] = '';
			$acc_details['name'] = '';
			$acc_details['description'] = '';
		} else {
			$acc_details['account_type'] = $result[0]['classification'];
			$acc_details['account_usage'] = $result[0]['account_usage'];
			$acc_details['name'] = $result[0]['name'];
			$acc_details['description'] = $result[0]['description'];
		}
		return $acc_details;
	}

	function InsertStaff($data){
	    try{

		if ($this->checkuname($data['uname'])) {
			header('Location: ' . URL . 'staff/addstaff?uname='.$data['username']); 
			die();
		}

		$office_id=$_SESSION['office'];
		$valid_extensions = array('jpeg','jpg','png','JPEG','PNG');
		
		if(isset($_FILES['docs'])){

			$img = $_FILES['docs']['name'];
			$tmp = $_FILES['docs']['tmp_name'];

			$ext = strtolower(pathinfo($img,PATHINFO_EXTENSION));

			$path = 'public/images/avatar/';

			$postData=array();

			if(in_array($ext, $valid_extensions))  {					

				$path = $path.strtolower($img);
				move_uploaded_file($tmp,$path);
			}
			
		}

		$tellerAccount = $this->getTellerAccountID();

  		//create cash account for user
		if (empty($tellerAccount) && $data['can_transact'] == "yes" && isset($data['account_type']) && isset($data['parent'])){

			$tellerAccount = $this->createTellerGlAccount($data);

			if ($tellerAccount == 'glcode') {
				header('Location: ' . URL . 'staff?msg=glcode');
				die();
			} elseif ($tellerAccount == 'failed') {
				header('Location: ' . URL . 'staff?msg=failed');
				die();
			}

		} else if ($data['can_transact'] == "No") {
			$tellerAccount = '';
		}

		if ($_SESSION['Isheadoffice'] == 'Yes') {
			$branch_id = $office_id;
		} else {
			$branch_id = $_SESSION['branchid'];
		}

		$rand = trim(substr(md5(uniqid(mt_rand(), true)), 0, 10));
		$password = Hash::create('sha256',$rand, HASH_ENCRIPT_PASS_KEYS);	 

		$postData = array(
			'office_id' => $branch_id,
			'firstname' => $data['fname'],
			'lastname' => $data['lname'],
			'gender' =>$data['gender'],
			'username' =>$data['uname'],
			'password' => $password,
			'email' => $data['email'],
			'mobile_no' => $data['mobile_no'],
			'external_id' => $data['external_id'],
			'organisational_role_enum' => $data['organisational_role_enum'],
			'organisational_role_parent_staff_id' => $data['organisational_role_parent_staff_id'],
			'access_level' => $data['access_level'],
			'created_by' => $_SESSION['user_id'],
			'can_transact' => $data['can_transact'],
			'cash_account' => $tellerAccount,
			'joining_date' => date('Y-m-d H-i-s'),
			'image_id' => $img,
		);

		$this->db->InsertData('m_staff', $postData);

		$message =  "Hello, your social banking password is ".$rand."<p>Please change this password after you login";
		$this->sendEmail($data['email'],$message);

		header('Location: ' . URL . 'staff?msg=success'); 

	}catch(Exception $e){
	    header('Location: ' . URL . 'staff?msg='.$e->getMessage()); 
	    die();
	}
	}

	function createTellerGlAccount($data){

		$glcode=null;
		$office=$_SESSION['office'];

		if(!empty($data['account_type'])){
			if($data['account_type']=='Assets'){
				$glcode=$this->getAssetCodes($data['account_usage'],$data['parent']);
			}else if($data['account_type']=='Liabilities'){			
				$glcode=$this->getLiabilityCodes($data['account_usage'],$data['parent']);			
			} else if($data['account_type']=='Equity'){
				$glcode=$this->getEquityCodes($data['account_usage'],$data['parent']);			
			} else if($data['account_type']=='Incomes'){
				$glcode=$this->getIncomeCodes($data['account_usage'],$data['parent']);				
			} else if($data['account_type']=='Expenses'){
				$glcode=$this->getExpenseCodes($data['account_usage'],$data['parent']);			
			}

			if(!empty($glcode)){
				$postData = array(
					'sacco_id' => $office,
					'classification' => $data['account_type'],
					'gl_code' => $glcode,
					'account_usage' => $data['account_usage'],
					'name' => $data['account_name'],
					'parent_id' => $data['parent'],
					'description' => $data['description']
				);

				$id = $this->db->InsertData('acc_ledger_account', $postData);
				return $id;
			}else{
				return "glcode";		
			}
		}else{
			return "failed";
		}
	}

	function UpdateStaff($data, $id){

		if ($data['can_transact'] == 'Yes') {
			$tellerAccount = $this->getTellerAccountID();
		} else if ($data['can_transact'] == 'No') {
			$tellerAccount = '';
		}

		$office_id=$_SESSION['office'];
		$postData = array(
			'office_id' =>$office_id,
			'firstname' => $data['fname'],
			'lastname' => $data['lname'],
			'gender' =>$data['gender'],
			'username' =>$data['uname'],
            //'password' => Hash::create('sha256',$_POST['password'],HASH_ENCRIPT_PASS_KEYS),
			'email' => $data['email'],
			'mobile_no' => $data['mobile_no'],
			'external_id' => $data['external_id'],
			'organisational_role_enum' => $data['organisational_role_enum'],
			'organisational_role_parent_staff_id' => $data['organisational_role_parent_staff_id'],
			'access_level' => $data['access_level'],
			'can_transact' => $data['can_transact'],
			'cash_account' => $tellerAccount,
			'joining_date' => date('Y-m-d H-i-s'),
		);

		$this->db->UpdateData('m_staff', $postData,"`id` = '".$id."'");
		header('Location: ' . URL . 'staff?msg=success'); 
	}

	function DeleteStaff($id){
		$data = array(
			'status' => 'Closed'
		);

		$this->db->UpdateData('m_staff', $data,"`id` = '".$id."'");
		header('Location: ' . URL . 'staff?msg=success');

	} 

	function ResetPassword($id){
	    
        $user = $this->GetStaffDetails($id);
        $email = $user[0]['email'];
        $reset_by = $_SESSION['office'];
    	$rand = strtoupper(trim(substr(md5(uniqid(mt_rand(), true)), 0, 10)));
    	$password = Hash::create('sha256',$rand, HASH_ENCRIPT_PASS_KEYS);	 
           
		$today = date('Y-m-d');
		$data = array(
			'password' => $password,
			'next_reset' => NULL
		);

		$this->db->UpdateData('m_staff', $data,"`id` = '".$id."'");
	
			$message =  "Hello, your social banking password has been reset. <p>Password: ".$rand."</p><p>Please change this password after you log in";
		    $this->sendEmail($email,$message);
		   
		    	header('Location: ' . URL . 'staff?msg=success');
		
		
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
		   
		header('Location: ' . URL . '?reset=success');
		
	}
	
	
	function verifyStaffAccount($id){
	    
        $user = $this->GetStaffDetails($id);
        $email = $user[0]['email'];
        
        if($id != $_SESSION['user_id']){
            
            $data = array(
    			'verified_by' => $_SESSION['user_id']
    		);
    
    		$this->db->UpdateData('m_staff', $data,"`id` = '".$id."'");
    	
    		$message =  "Hello, your social banking account has been verified by the Admin. Please click to Log in";
    		header('Location: ' . URL . '/staff/viewstaffdetails/' . $id . '?msg=success');
        } else {
            header('Location: ' . URL . '/staff/viewstaffdetails/' . $id . '?msg=error');
        }
		
	}
	
}