<?php

//error_reporting(0);
   
class Organisation_model extends Model{
	
	public function __construct(){
		parent::__construct();
    	$this->logUserActivity(NULL);
    	if (!$this->checkTransactionStatus()) {
    		header('Location: ' . URL); 
    	}
	}

	function genesisLogin($data){

		if ($_SESSION['access_level'] == 'SA') {
			$this->logintosacco($data);
		} else {
			header('location:' . URL .'organisation?login=fail');
		}
	}

	function logintosacco($new_data){

		$sth = $this->db->prepare("SELECT * FROM m_staff WHERE  email or username = :username AND password = :password");
		$sth->execute(array(
			':username' => $new_data['username'],
			':password' => Hash::create('sha256',$new_data['password'],HASH_ENCRIPT_PASS_KEYS)
		));

		$data = $sth->fetch();
		$count = $sth->rowCount();
		$start = time();

		if ($count > 0){

			$this->logSAOut();

			@session_start();
			$branch=$this->getbranch($new_data['office']);
			$_SESSION['user_id']=$data['id'];
			$rs = $this->GetSettings();
			$instance = $rs['instance_id'];
			Session::set('loggedin', true);
			Session::set('email', $data['email']);
			Session::set('username',$data['username']);
			Session::set('office', $new_data['office']);
			Session::set('branch', $branch);
			Session::set('timeout', $start);
			Session::set('instance', $instance);
			Session::set('access_level', 'A');
			Session::set('name', $data['firstname']." ".$data['lastname']);

			header('location:' . URL);
		} else {

			header('location:' . URL .'organisation?login=fail');
		}
	}

	function logSAOut(){		

		session_start();

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
		
		session_destroy();
	}

	function GetSettings(){
		 $result =  $this->db->SelectData("SELECT * FROM system_settings WHERE `status` = 'Active'");
	     return $result[0];
	}

	function checkorganisation($org){
		$office=$_SESSION['office'];
		$results = $this->db->selectData("SELECT * FROM m_organisation WHERE organisation_name = '".$org."'");

		$result = false;
		if (count($results) <= 0) {
			$result = false;
		} else {
			$result = true;
		}
		echo json_encode($result);
		die();
	}

	function checkorg($org){
		$office=$_SESSION['office'];
		$results = $this->db->selectData("SELECT * FROM m_organisation WHERE organisation_name = '".$org."'");

		$result = false;
		if (count($results) <= 0) {
			$result = false;
		} else {
			$result = true;
		}
		return $result;
	}

	function SendRequest($data){
		$name = $data['name'];
		$contactPerson= $data['fname'] . " " . $data['lname'];
		$phone = $data['phone'];
		$username=$data['username'];
		$access_url=$data['server_address'];
		$rs = $this->GetSettings();
		$instance = $rs['instance_id'];
		$currency = $rs['currency'];

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, CLIC_API);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,"name=".$name."&access_url=".$access_url."&contactPerson=".$contactPerson."&phone=".$phone."&username=".$username."&instance=".$instance."&currency=".$currency);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$server_output = curl_exec($ch);
		curl_close ($ch);

		return  $server_output;
		 
	}

	function CreateNewBranch($data, $sacco_ID){

		try{

			$data['branch_no'] = $sacco_ID;
			$user_id = $this->CreateEmployee($data);	
			$postData = array();           
			$postData['id'] = $sacco_ID;
    		$postData['parent_id'] = $_SESSION['office'];
			$postData['name'] = $data['name'];
			$postData['opening_date'] = date('Y-m-d');
			$postData['head_office'] = 'Yes'; 
			$postData['admin'] = $user_id;
			
			$saccoID = $this->db->InsertData('m_branch',$postData);

			$data['responsibility'] = "System Administrator";
			$data['user_id'] = $user_id;
			$data['office_id'] = $sacco_ID;		 
			$data['access_name'] = "ADMIN";		 
			$rs = $this->CreateAccessLevels($data);	 

			return $this->MakeJsonResponse(100,"success" );
		}catch(Exception $e){
			return $this->MakeJsonResponse(203,"unexpected error".$e->getMessage() );
		}
	}

	function CreateAccessLevels($data){
		$postData = array();	
		$postData['access_denotor'] = 'A';	
		$postData['office_id'] = $data['office_id'];	 
		$postData['user_id'] = $data['user_id'];	 
		$postData['creator_access'] = $_SESSION['user_id'];
		$postData['access_name'] = $data['access_name'];
		$postData['allowed_access'] = '1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,62,63,63,64,65,66,67,68,69,70,71,72,73,74,75,76,77,78,79,80,81,82,83,84,85,86,87,88,89,90,91,92,93,94,95,96,97,98,99,100,101,102,103,104,105,106,107,108,109,110,111,112,113,114,115,116,117,118,119,120';
		$this->db->InsertData('sch_user_levels',$postData);
		return true;
	}


	function getOrganisations(){
		 $result =  $this->db->SelectData("SELECT * FROM m_organisation WHERE `status` = 'Active'");
	     return $result;
	}

	function getOrganisationDetails($id){
		 $result =  $this->db->SelectData("SELECT * FROM m_organisation WHERE id='".$id."' ");
	     return $result;
	}
	function getLastOrganisation(){
		 $result =  $this->db->SelectData("SELECT id from m_organisation order by id DESC LIMIT 1");
	     return $result;
	}

	function InsertOrganisation($data){
		try{
		if ($this->checkorg($data['name'])) {
        	return $this->MakeJsonResponse(101,"organisation name exists" );
		}

		if ($this->checkuname($data['username'])) {
        	return $this->MakeJsonResponse(101,"organisation username exists" );
		}



/*
		$response = $this->SendRequest($data); 
		$rsp = json_decode($response, true);
		$status = $rsp['status'];

		if ($status != 1) {
        	header('Location: ' . URL . 'organisation?msg=status'.$status); 
			die();
		}
		
*/
$saccoArray = $this->getLastOrganisation();
if(sizeof($saccoArray)>0){
	$sacco_id = $saccoArray[0]['id']+1;
}else{
	$sacco_id = 1000;
}

		//$sacco_id = $rsp['saccoID'];

		$valid_extensions = array('jpeg','jpg','png','JPEG','PNG'); 
		
		if(isset($_FILES['logo'])){

			$img = $_FILES['logo']['name'];
			$tmp = $_FILES['logo']['tmp_name'];

			$ext = strtolower(pathinfo($img,PATHINFO_EXTENSION));

			$path = 'public/images/avatar/';

			$postData=array();

 			if(in_array($ext, $valid_extensions))  {					
		
				$path = $path.strtolower($img);
				move_uploaded_file($tmp,$path);
			}
			
  		}

		$postData = array(
			'id' => $sacco_id,
			'organisation_name' => $data['name'],
            'organisation_address' => $data['address'],
			'contact_person' => $data['fname'] . " ". $data['lname'],
            'server_address' => $data['server_address'],
            'logo' => $img
        );

		$this->db->InsertData('m_organisation', $postData);
		$rs = $this->CreateNewBranch($data, $sacco_id);
		return $this->MakeJsonResponse(100,"success",URL."organisation" );
		
		}catch(Exception $e){
			return $this->MakeJsonResponse(203,"unexpected error".$e->getMessage() );
		}
	}

	function UpdateOrganisation($data, $id){

		if(isset($_FILES['logo'])){

			if ($_FILES['logo']['name'] == '') {
				$img = $data['previous'];
			} else {
				$img = $_FILES['logo']['name'];
			}
			
			$tmp = $_FILES['logo']['tmp_name'];

			$ext = strtolower(pathinfo($img,PATHINFO_EXTENSION));

			$path = 'public/images/avatar/';

			$postData=array();

 			if(in_array($ext, $valid_extensions))  {					
		
				$path = $path.strtolower($img);
				move_uploaded_file($tmp,$path);
			}
			
  		}

		$postData = array(
			'organisation_name' =>$data['name'],
            'organisation_address' => $data['address'],
			'contact_person' =>$data['contact_person'],
            'server_address' => $data['server_address'],
            'logo' => $img,
        );

		$this->db->UpdateData('m_organisation', $postData,"`id` = '".$id."'");
        header('Location: ' . URL . 'organisation?msg=updated'); 
	}

	function DeleteOrganisation($id){
		 $data = array(
			'status' => 'Closed'
        );

		$this->db->UpdateData('m_organisation', $data,"`id` = '".$id."'");
  		header('Location: ' . URL . 'organisation?msg=deleted');

	} 


	
}