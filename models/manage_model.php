<?php

class Manage_Model extends Model{

	public function __construct(){
		parent::__construct();
		//Auth::handleSignin();
    	$this->logUserActivity(NULL);
    	if (!$this->checkTransactionStatus()) {
    		header('Location: ' . URL); 
    	}
	}

	function getAllPointers(){
		$id=$_SESSION['office'];
		$result =  $this->db->SelectData("SELECT * FROM acc_gl_pointers AS a JOIN transaction_type AS b ON a.transaction_type_id = b.transaction_type_id WHERE a.sacco_id = '".$id."' ");

		$new_result = array();
		foreach ($result as $key => $value) {
			$new_result[$key]['pointer_name'] = $value['pointer_name'];
			$new_result[$key]['description'] = $value['description'];
			$new_result[$key]['transaction_type_name'] = $value['transaction_type_name'];
			$new_result[$key]['debit_account'] = $this->getAccountName($value['debit_account']);
			$new_result[$key]['credit_account'] = $this->getAccountName($value['credit_account']);
		}
		return $new_result;		
	}

	function getAccountName($id){
		$office=$_SESSION['office'];
		$result =  $this->db->SelectData("SELECT * FROM acc_ledger_account WHERE id = '$id' AND disabled = 'No' AND sacco_id = '".$office."'");

		return $result[0]['name'];
	}

	function getBranchStaff($id){
		$result =  $this->db->SelectData("SELECT * FROM m_staff WHERE `status` = 'Active' and office_id = '".$id."' ");

		return $result;
	}

	function getSaccoCampaigns(){
		$id=$_SESSION['office'];
		$result =  $this->db->SelectData("SELECT * FROM m_campaigns where status='Active' AND office_id = '".$id."'");
	}

	function ResetOfficePassword($id){

		$postData = array(         
			'password' => Hash::create('sha256','12345', HASH_ENCRIPT_PASS_KEYS)
			);
		$this->db->UpdateData('m_staff', $postData,"`id` = '{$id}'");
		header('Location: ' . URL . 'manage/branches/?msg=reset'); 
	}

 	function changelogo($data){

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
			'logo' => $img
		);
		$this->db->UpdateData('m_organisation', $postData,"`id` = '{$_SESSION['office']}'");
		header('Location:'.URL.'manage?msg=logo');

 	}

	function SendRequest($data, $sacco){

		$saccoId=$sacco;
		$name=$data['branch'];
		$country=$data['country'];
		$contactPerson= $data['fname'] . " " . $data['lname'];
		$phone=$data['phone'];
		$username=$data['username'];
		$stellarPublic="JKHSD7434343446S7TD87S5D7T9STD87STD";
		$stellarPrivate="OYD9SD70S4334344TD79TS8JD7SDSD7SSD";
		$saccoCurrency=$data['currency'];

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, CLIC_API);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,"saccoId=".$saccoId."&name=".$name."&country=".$country."&contactPerson=".$contactPerson."&phone=".$phone."&username=".$username."&stellarPublic=".$stellarPublic."&stellarPrivate=".$stellarPrivate."&saccoCurrency=".$saccoCurrency."");
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$server_output = curl_exec($ch);
		curl_close ($ch);

		return  $server_output;
		 
	}

	function createbranch(){

		try{

			$data = $_POST;

			$branch_no= $this->BranchNo(); 
			$today=date("Y-m-d");
			$d = strtotime($today);
			$date = date('Y-m-d',$d);
			$data['branch_no'] = $branch_no;
			$postData = array();           
			$postData['id'] = $branch_no;
    		$postData['parent_id'] = $_SESSION['office'];
			$postData['name'] = $data['branch'];
			$postData['opening_date'] = $date;

			if ($this->checkbranchname($data['branch'])) {
	        	header('Location: ' . URL . 'manage/newbranch?bname='.$data['branch']); 
				die();
			}

			if($data['admin']=="new"){	 

				if ($this->checkuname($data['username'])) {
		        	header('Location: ' . URL . 'manage/newbranch?uname='.$data['username']); 
					die();
				}

				if ($this->checkphone($data['phone'])) {
					header('Location: ' . URL . 'manage/newbranch?phone='.$data['phone']); 
					die();
				}

				$user_id=$this->CreateEmployee($data);				
				$postData['admin'] = $user_id;

			}else{
				$postData['admin'] = $data['employee'];

				$staff_details = $this->getStaffDetails($data['employee']);
				$user_id = $staff_details[0]['id'];

				$postData_u = array();
				$id=$data['employee'];
				$postData_u['office_id']=$data['branch_no'];				
				$postData_u['access_level']=$data['access_level'];
				$postData_u['organisational_role_enum']="Branch Administrator";	 
				$this->db->UpdateData('m_staff',$postData_u, "`id` = {$id}");		
			}		

			$saccoID = $this->db->InsertData('m_branch',$postData);
			//$response = $this->SendRequest($_POST, $saccoID);

			//header('Location:'.URL.'manage/branches?res='$response);
			header('Location:'.URL.'manage/branches?msg=added');
		}catch(Exception $e){
			header('Location:'.URL.'manage/branches?msg='.$e->getMessage());
			die();
		}
	}

	function AuthAccessLevels($data){
		$postData = array();	
		$postData['access_denotor']= 'A';	
		$postData['office_id']=$data['branch_no'];	 
		$postData['user_id']=$data['user_id'];	 
		$postData['creator_access']=$_SESSION['user_id'];
		$postData['access_name']=$data['access_name'];
		$postData['allowed_access']='1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,62,63,63,64,65,66,67,68,69,70,71,72,73,74,75,76,77,78,79,80,81,82,83,84,85,86,87,88,89,90,91,92,93,94,95,96,97,98,99,100,101,102,103,104,105,106,107,108,109,110,111,112,113,114,115,116,117,118,119,120';
		$this->db->InsertData('sch_user_levels',$postData);
	}

	function PaymentTypeList(){

		return $this->db->SelectData("SELECT * FROM payment_mode  where status='yes' order by id desc");

	}
	function AddPaymentType(){
		$data =  $_POST;
		$postData = array(

			'value' => $data['payment_type'],
			'description' => $data['description'],
			'order_position' => $data['Position'],
			'status' => 'yes',               
			);

		$this->db->InsertData('payment_mode', $postData);
		header('Location: ' . URL . 'manage/paymenttype?msg=success');  
	}
	function GetPaymentType($id){
		$result =  $this->db->SelectData("SELECT * FROM payment_mode where id='".$id."' order by id ");

		return $result;


	}

	function UpdatePaymentType($data){

	 //$postData = array();
		$id=$data['id'];
		$postData = array(
			'value' => $data['payment_type'],
			'description' => $data['description'],
			'order_position' => $data['Position']
			);

		$this->db->UpdateData('payment_mode', $postData,"`id` = '{$id}'");
		header('Location: ' . URL . 'manage/paymenttype?msg=success');

	}
	function DeletePaymentType($id){


		$postData = array(         
			'status' => 'no'
			);
		$this->db->UpdateData('payment_mode', $postData,"`id` = '{$id}'");
		header('Location: ' . URL . 'manage/paymenttype?msg=success');

	}
	function getOfficeNUserDetails($id){
		$result =  $this->db->SelectData("SELECT * FROM m_branch join m_staff on m_branch.admin = m_staff.id where m_branch.b_status='Active' and m_branch.id='".$id."'");
		return $result[0];
	}

	function getOfficeDetails($id){

		$result =  $this->db->SelectData("SELECT * FROM m_branch where b_status='Active' and id='".$id."'");
		if(count($result)>0){
			foreach ($result as $key => $value) {
				$officename = $this->officeName($result[$key]['id']);
				$parent_name = $this->officeName($result[$key]['parent_id']);
				$rset[$key]['office_id'] = $result[$key]['id']; 
				$rset[$key]['parent_name'] = $parent_name;
				$rset[$key]['name'] = $officename;
				$rset[$key]['opening_date'] = $value['opening_date'];
			}
			return $rset;
		}
	}

	function officeList(){

		$id=$_SESSION['office'];
		$result =  $this->db->SelectData("SELECT * FROM m_branch where b_status='Active' AND parent_id = '".$id."' order by id ");
		if(count($result)>0){
			foreach ($result as $key => $value) {
				$officename = $this->officeName($result[$key]['id']);
				$parent_name = $this->officeName($result[$key]['parent_id']);
				$rset[$key]['office_id'] = $result[$key]['id']; 
				$rset[$key]['parent_name'] = $parent_name;
				$rset[$key]['name'] = $officename;
				$rset[$key]['admin'] = $result[$key]['admin'];
				$rset[$key]['admin_name'] = $this->getAdmin($result[$key]['admin']);
				$rset[$key]['opening_date'] = $value['opening_date'];
				//$rset[$key]['currency'] = $value['currency'];
				//$rset[$key]['country'] = $value['country'];
			}
			return $rset;
		}
	}
	function officeName($id){
		if($id!=''){
			$results =  $this->db->SelectData("SELECT * FROM m_branch where id='".$id."'");
			return  $results[0]['name'];
		}else{
			return  '';
		}


	}

	function getAdmin($id){
		if($id!=''){
			$results =  $this->db->SelectData("SELECT * FROM m_staff where id='".$id."' AND status = 'Active'");

			$names = $results[0]['lastname'] . " " . $results[0]['firstname'];
			return  $names;

		}
	}
	function getOffice($id){

		$result =  $this->db->SelectData("SELECT * FROM m_branch where id='".$id."' ");
		$officename = $this->officeName($result[0]['id']);
		$parent_name = $this->officeName($result[0]['parent_id']);
		$rset['office_id'] = $result[0]['id']; 
		$rset['parent_id'] = $result[0]['parent_id']; 
		$rset['parent_name'] = $parent_name;
		$rset['head_office'] = $result[0]['head_office'];
		$rset['name'] = $officename;
		$rset['odate'] = $result[0]['opening_date'];

		return $rset;


	}

	function UpdateOffice($data){


		$postData = array();
		$id=$data['id'];
		if(!empty($data['oname'])){
			$postData['name'] =$data['oname'];

		}
		if(!empty($data['parent_office'])){
			$postData['parent_id'] = $data['parent_office'];
		}

		$this->db->UpdateData('m_branch', $postData,"`id` = '{$id}'");
		header('Location: ' . URL . 'manage/branches?msg=updated');  

	}
	function DeleteOffice($id){
		$postData = array();
		$postData['b_status'] ='0';
		$this->db->UpdateData('m_branch', $postData,"`id` = '{$id}'");
		header('Location: ' . URL . 'manage/branches?msg=deleted');  

	}
	
	/*  STAFFF          */

	
	function getAccesslevels(){
		$office=$_SESSION['office'];
		$result =  $this->db->SelectData("SELECT * FROM sch_user_levels where office_id = $office");
		return $result;
	}


	function EmployeeList(){
		$office = $_SESSION['office'];
		$user=$this->getAdminDetails();
		$id=$user[0]['id'];
		return $this->db->SelectData("SELECT * FROM m_staff  where office_id='".$office."' and id!='".$id."' order by id desc");
	}

	function SaccoStaff(){
		$office = $_SESSION['office'];
		return $this->db->SelectData("SELECT * FROM m_staff  where office_id=".$office);
	}

	function getStaffDetails($id){
		$office = $_SESSION['office'];
		return $this->db->SelectData("SELECT * FROM m_staff  where office_id=".$office." AND id=".$id);		
	}

	function getStaffTransactions($id){
		$office = $_SESSION['office'];
		return $this->db->SelectData("SELECT * FROM acc_gl_journal_entry AS a JOIN acc_ledger_account AS b ON a.account_id = b.id WHERE a.office_id=".$office." AND b.sacco_id=".$office." AND createdby_id=".$id." ORDER BY journal_id DESC");	
	}

	function getStaffActivities($id){
		$office = $_SESSION['office'];
		return $this->db->SelectData("SELECT * FROM system_logs WHERE sacco_id=".$office." AND user_id=".$id." ORDER BY id DESC");	
	}

	function getTodayTransactions($id){
		$office = $_SESSION['office'];
		$today = date("Y-m-d");
		return $this->db->SelectData("SELECT * FROM acc_gl_journal_entry AS a JOIN acc_ledger_account AS b ON a.account_id = b.id WHERE a.created_date LIKE '".$today."%' AND a.office_id=".$office." AND b.sacco_id=".$office." AND createdby_id=".$id." ORDER BY journal_id DESC");	

	}

	function getTodayActivities($id){
		$office = $_SESSION['office'];
		$today = date("Y-m-d");
		return $this->db->SelectData("SELECT * FROM system_logs WHERE date_created LIKE '".$today."%' AND sacco_id=".$office." AND user_id=".$id." ORDER BY id DESC");	

	}

	function getTodayTransactionActivities($id){
		$office = $_SESSION['office'];
		$today = date("Y-m-d");
		return $this->db->SelectData("SELECT * FROM system_logs WHERE is_transaction = 'Yes' AND date_created LIKE '".$today."%' AND sacco_id=".$office." AND user_id=".$id." ORDER BY id DESC");	

	}

	function getYesterdayTransactions($id){
		$office = $_SESSION['office'];
		$today = date("Y-m-d",strtotime("-1 days"));
		return $this->db->SelectData("SELECT * FROM acc_gl_journal_entry AS a JOIN acc_ledger_account AS b ON a.account_id = b.id WHERE a.created_date LIKE '".$today."%' AND a.office_id=".$office." AND b.sacco_id=".$office." AND createdby_id=".$id." ORDER BY journal_id DESC");	

	}

	function getYesterdayActivities($id){
		$office = $_SESSION['office'];
		$today = date("Y-m-d",strtotime("-1 days"));
		return $this->db->SelectData("SELECT * FROM system_logs WHERE date_created LIKE '".$today."%' AND sacco_id=".$office." AND user_id=".$id." ORDER BY id DESC");		

	}

	function getYesterdayTransactionActivities($id){
		$office = $_SESSION['office'];
		$today = date("Y-m-d",strtotime("-1 days"));
		return $this->db->SelectData("SELECT * FROM system_logs WHERE is_transaction = 'Yes' AND date_created LIKE '".$today."%' AND sacco_id=".$office." AND user_id=".$id." ORDER BY id DESC");		

	}

	function getMonthTransactions($id){
		$office = $_SESSION['office'];
		$today = date("Y-m");
		return $this->db->SelectData("SELECT * FROM acc_gl_journal_entry AS a JOIN acc_ledger_account AS b ON a.account_id = b.id WHERE a.created_date LIKE '".$today."%' AND a.office_id=".$office." AND b.sacco_id=".$office." AND createdby_id=".$id." ORDER BY journal_id DESC");	

	}

	function getMonthActivities($id){
		$office = $_SESSION['office'];
		$today = date("Y-m");
		return $this->db->SelectData("SELECT * FROM system_logs WHERE date_created LIKE '".$today."%' AND sacco_id=".$office." AND user_id=".$id." ORDER BY id DESC");		

	}

	function getMonthTransactionActivities($id){
		$office = $_SESSION['office'];
		$today = date("Y-m");
		return $this->db->SelectData("SELECT * FROM system_logs WHERE is_transaction = 'Yes' AND date_created LIKE '".$today."%' AND sacco_id=".$office." AND user_id=".$id." ORDER BY id DESC");		

	}

	function getRangeTransactions($id, $data){
		$office = $_SESSION['office'];
		$start = date_format(date_create($data['start']), "Y-m-d H:i:s");
		$end = date_format(date_create($data['end']), "Y-m-d H:i:s");
		return $this->db->SelectData("SELECT * FROM acc_gl_journal_entry AS a JOIN acc_ledger_account AS b ON a.account_id = b.id WHERE a.created_date BETWEEN '".$start."' AND '".$end."' AND a.office_id=".$office." AND b.sacco_id=".$office." AND createdby_id=".$id." ORDER BY journal_id DESC");	

	}

	function getRangeActivities($id, $data){
		$office = $_SESSION['office'];
		$start = date_format(date_create($data['start']), "Y-m-d H:i:s");
		$end = date_format(date_create($data['end']), "Y-m-d H:i:s");
		return $this->db->SelectData("SELECT * FROM system_logs WHERE date_created BETWEEN '".$start."' AND '".$end."' AND sacco_id=".$office." AND user_id=".$id." ORDER BY id DESC");	

	}

	function getRangeTransactionActivities($id, $data){
		$office = $_SESSION['office'];
		$start = date_format(date_create($data['start']), "Y-m-d H:i:s");
		$end = date_format(date_create($data['end']), "Y-m-d H:i:s");
		return $this->db->SelectData("SELECT * FROM system_logs WHERE is_transaction = 'Yes' AND date_created BETWEEN '".$start."' AND '".$end."' AND sacco_id=".$office." AND user_id=".$id." ORDER BY id DESC");	

	}

	function getEmployee($id){

		return $this->db->SelectData("SELECT * FROM m_staff where id='".$id."' order by id desc");
	}

	function getAdminDetails(){
		$username = $_SESSION['username'];
		return $this->db->SelectData("SELECT * FROM m_staff where username='".$username."' ");
	}

	function UpdateEmployee($data){

		$postData = array();
		if(!empty($data['employee'])){
			$id=$data['employee'];
			if(!empty($data['fname'])){
				$postData['firstname']=$data['fname'];	 
			}
			if(!empty($data['lname'])){
				$postData['lastname']=$data['lname'];	 
			} 
			if(!empty($data['username'])){
				$postData['username']=$data['username'];	 
			}
			if(!empty($data['phone'])){
				$postData['mobile_no']=$data['phone'];	 
			}
			if(!empty($data['email'])){
				$postData['email']=$data['email'];	 
			}
			if(!empty($data['responsibility'])){
				$postData['organisational_role_enum']=$data['responsibility'];	 
			}	
			if(!empty($data['gender'])){
				$postData['gender']=$data['gender'];	 
			}
			$this->db->UpdateData('m_staff',$postData, "`id` = {$id}");
			header('Location: ' . URL . 'manage/employeedetails/'.$id.'?msg=success'); 
		}
	}
	function transferemployee($data){

		$postData = array();
		if(!empty($data['office'])&&$data['enumber']){
			$id=$data['enumber'];

			$postData['office_id']=$data['office'];	 

			$this->db->UpdateData('m_staff',$postData, "`id` = {$id}");
			header('Location: ' . URL . 'manage/employeedetails/'.$id.'?msg=success'); 	
		}else{
			header('Location: ' . URL . 'manage/employees/?msg=failed'); 			 
		}

	}
	function DeleteEmployee($id){



	}	

	/*  CURRENCY          */
	function currencyList(){
		$office=$_SESSION['office'];
		return $this->db->SelectData("SELECT * FROM m_currency where active= 'yes' and sacco_id = '".$office."'");

	}

	function createCurrency(){

		$data =  $_POST;
		$postData = array(

			'name' => $data['name'],
			'code' => $data['code'],
			);
		
		$this->db->InsertData('m_currency', $postData);
		header('Location: ' . URL . 'manage/currency?msg=success'); 

	}
	function getCurrency($id){
		$result =  $this->db->SelectData("SELECT * FROM m_currency where id='".$id."' ");

		return $result;
	}

	function getCurrencies(){
		$office=$_SESSION['office'];
		//$result =  $this->db->SelectData("SELECT * FROM m_currency where sacco_id='".$office."' and active= 'yes'");
		return "";		
	}
	function updateCurrency(){
		$data =  $_POST;

		$id=$data['id'];
		$postData = array(
			'name' => $data['name'],
			'code' => $data['code'],
		);
		
		$this->db->UpdateData('m_currency',$postData, "`id` = {$id}");
		
		$sacco = $_SESSION['office'];
		$saccoData['currency'] = $data['code'];
		$this->db->UpdateData('m_currency',$saccoData, "`id` = {$sacco}");

		header('Location: ' . URL . 'manage/currency?msg=updated'); 

	}
	function deleteCurrency($id){
		

		$postData = array();
	 // $id=$data['id'];

		$postData['active'] ='no';
		$this->db->UpdateData('m_currency',$postData, "`id` = {$id}");

		header('Location: ' . URL . 'manage/currency?msg=deleted'); 

	}

	function getAssets(){


		return $this->db->selectData("SELECT * FROM acc_ledger_account where classification='1'");
	}
	function getLiability(){


		return $this->db->selectData("SELECT * FROM acc_ledger_account where classification='2'");
	}
	function getEquity(){


		return $this->db->selectData("SELECT * FROM acc_ledger_account where classification='3'");
	}
	function getIncome(){


		return $this->db->selectData("SELECT * FROM acc_ledger_account where classification='4'");
	}

	function getAccessRights(){
		$office=$_SESSION['office'];
		return $this->db->selectData("SELECT * FROM sch_user_levels where office_id = $office");
	}


}