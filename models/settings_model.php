<?php
require __DIR__ . '/../vendor/autoload.php';
include ("members_model.php");
class Settings_model extends Model{
	
public function __construct(){
	parent::__construct();
	//Auth::handleSignin();
    $this->logUserActivity(NULL); 
	if (!$this->checkTransactionStatus()) {
		header('Location: ' . URL); 
	}
 
}
function insertnewbank($data){
 	$postData = array(
		'office_id' => $_SESSION['office'],
		'name' =>$data['name'],
    );
  	
  	$this->db->InsertData('m_bank', $postData);
	header('Location: ' . URL . 'settings/banks?msg=success');
}

function insertnewbulk($data){
	$details = explode(":", $data['member_id']);
 	$postData = array(
		'office_id' => $_SESSION['office'],
		'member_id' => $details[0],
		'telephone' => $details[1],
		'amount' => $data['amount'],
		'transaction_date' => date("Y-m-d"),
		'from_account' => $data['from_account'],
		'payment_date' => date_format(date_create($data['payment_date']), "Y-m-d"),
		'frequency' =>$data['frequency'],
    );
  	
  	$this->db->InsertData('bulk_payments', $postData);
	header('Location: ' . URL . 'settings/batchpayments?msg=inserted');

}

function updatebank($data){
 	$postData = array(
		'name' =>$data['name'],
    );

 	$id = $data['id'];
   	$this->db->UpdateData('m_bank', $postData, "`id` = '{$id}'");
	header('Location: ' . URL . 'settings/viewbank/'. $id . '?msg=success'); 

}

function updatebulk($data, $id){

	$details = explode(":", $data['member_id']);
 	$postData = array(
		'office_id' => $_SESSION['office'],
		'member_id' => $details[0],
		'telephone' => $details[1],
		'amount' => $data['amount'],
		'transaction_date' => date("Y-m-d"),
		'from_account' => $data['from_account'],
		'payment_date' => date_format(date_create($data['payment_date']), "Y-m-d"),
		'frequency' =>$data['frequency'],
    );
    
   	$this->db->UpdateData('bulk_payments', $postData, "`id` = '{$id}'");
	header('Location: ' . URL . 'settings/batchpayments/'. $id . '?msg=edited'); 

}

function deletebank($id){
 	$postData = array(
		'status' => 'Closed',
    );

   	$this->db->UpdateData('m_bank', $postData, "`id` = '{$id}'");
	header('Location: ' . URL . 'settings/banks?msg=success'); 

}

function getBanks(){
	$id = $_SESSION['office'];
	$result =  $this->db->SelectData("SELECT * FROM m_bank where status='Active' AND office_id = '".$id."'");
    return $result;
}

function getAllMembers(){
	$id = $_SESSION['office'];
	$result =  $this->db->SelectData("SELECT * FROM members where status='Active' AND office_id = '".$id."'");
    return $result;
}

function checkBulkAccountFrom($tel){

	$office=$_SESSION['office'];
	$result=  $this->db->selectData("SELECT * FROM members WHERE mobile_no='".$tel."' and office_id='".$office."' and status='Active'");

	if(count($result)>0){
		$rset=array();
		if(!empty($result[0]['company_name'])){
			$displayname=$result[0]['company_name'];
		}else{
			$displayname=$result[0]['firstname']." ".$result[0]['middlename']." ".$result[0]['lastname'];
		}
		foreach ($result as $key => $value) {
			array_push($rset,array(
				'member_id'=>$result[$key]['c_id'],
				'displayname'=>$displayname,		
			));
		}

		echo json_encode(array("result" =>$rset));
		die();
	}else{
		$rset=array();
		array_push($rset,array(
			'member_id'=>'0',
		));
		echo json_encode(array("result" =>$rset));
		die();
	}
}

function getBankDetails($id){
	$result =  $this->db->SelectData("SELECT * FROM m_bank where id = '".$id."'");
    return $result[0];
}

function getRoles(){
	 $result =  $this->db->SelectData("SELECT * FROM sch_access_rights WHERE on_menu = 'Yes' ORDER BY parent_option ASC");
     return $result;
	
}
function getroleid($id){	
	return $this->db->SelectData("SELECT * FROM  sch_access_rights where id = '".$id."' order by id");
	
}

function getEmployee($id){
 	$query= $this->db->SelectData("SELECT * FROM m_staff  where id='".$id."' ");	
 	if(count($query)>0){
 		foreach ($query as $key => $value) {	
			$office=$this->getOfficeName($value['office_id']);
			$rset[$key]['id'] =$query[$key]['id']; 
			$rset[$key]['firstname'] =$query[$key]['firstname']; 
			$rset[$key]['lastname'] =$query[$key]['lastname']; 
			$rset[$key]['gender'] =$query[$key]['gender']; 
			$rset[$key]['username'] =$query[$key]['username']; 
			$rset[$key]['email'] =$query[$key]['email']; 
			$rset[$key]['mobile_no'] =$query[$key]['mobile_no']; 
			$rset[$key]['image_id'] =$query[$key]['image_id']; 
			$rset[$key]['password'] =$query[$key]['password'];
			$rset[$key]['office'] =$office[0]['name'];
		}
	return $rset;
	}
}
 	
	
function updatepermissions($data){

	$id=$data['employee'];
	$permission=$data['permission'];

	if(!empty($id)&&!empty($permission)){
		$list=null;
		$count=count($permission);
		for($i=0;$i<$count;$i++){
			if($i==0){
				$list=$permission[$i];	
			}else{
				$list=$list.','.$permission[$i];
			}
		}

		$query= $this->db->SelectData("SELECT user_id FROM sch_user_levels  where user_id='".$id."' ");	

		if(count($query)>0){
			$count=count($permission);
			$postData = array(
				'allowed_access' => $list,
            );
            $this->db->UpdateData('sch_user_levels', $postData, "`user_id` = '{$id}'");
        }else{
        	$postData = array(
				'user_id' =>$id,
				'access_name' =>'User',
				'creator_access' => $_SESSION['user_id'],
				'allowed_access' => $list,
			);
			$this->db->InsertData('sch_user_levels', $postData);
		}

		header('Location: ' . URL . 'manage/employeedetails/'.$id.'?msg=success');
	}else{
		header('Location: ' . URL . 'manage/employeedetails/'.$id.'?msg=failed');
	}
}
 	
function getEmployeePermissions($id){
	return $this->db->SelectData("SELECT * FROM sch_user_levels where id='".$id."'");
}

function getOfficeName($id){
	return $this->db->SelectData("SELECT * FROM m_branch where id='".$id."' ");
}

function updateaccount($data){
	$id = $_SESSION['user_id'];
		$postData = array(
			'password' => Hash::create('sha256',$data['unpass'],HASH_ENCRIPT_PASS_KEYS)
		); 
		$this->db->UpdateData('m_staff', $postData, "`id` = '{$id}'");
		header('Location: ' . URL . 'settings/myaccountInfo/?msg=success');  	
}

function updateaccountfailed($data){

	header('Location: ' . URL . 'settings/changemypassword/?msg=failed');  
}
 	
	function getSystemLogs(){
   		return $this->db->SelectData("SELECT * FROM `thirdparty_savings_transaction` t LEFT JOIN tr_log_file_index l ON t.transaction_id  = l.transaction_id ORDER BY t.`transaction_id` DESC");
	}

	function getLogDetails($id){
   		return $this->db->SelectData("SELECT * FROM thirdparty_savings_transaction WHERE transaction_id = ".$id);
	}	

	function getBulkPaymentDetails(){
		$office_id = $_SESSION['office'];
   		return $this->db->SelectData("SELECT * FROM bulk_payments AS a JOIN members AS b ON a.member_id = b.c_id WHERE a.status = 'Active' AND b.status = 'Active' AND a.office_id = ".$office_id);
	}

	function getBulkDetails($id){
		$office_id = $_SESSION['office'];
   		return $this->db->SelectData("SELECT * FROM bulk_payments WHERE id='".$id."' AND status = 'Active' AND office_id = ".$office_id);
	}
	function removepayment($id){
	 	$postData = array(
			'status' => "Closed",
	    );

	   	$this->db->UpdateData('bulk_payments', $postData, "`id` = '{$id}'");
		header('Location: ' . URL . 'settings/batchpayments/'. $id . '?msg=success');
	}

	function verifycsv($uploadedfile){

		$filerec = file_get_contents($uploadedfile);
		$string = str_getcsv($filerec, "\r");
        print_r($filerec);
		$first_row_items = explode(',', $string[0]);
		if(sizeof($first_row_items) == 7){
			$final_verdict = false;
			foreach ($string as $key => $value) {
				$data = explode(',', $value);
				if (strlen($data[2]) == 9) {
					$data[2] = '0'.$data[2];
				}
				$verdict = $this->checkMemIdAndTelephone($data[1], $data[2]);
				//$acc_verdict = $this->checkAccountNo($data[4]);
				$acc_verdict = $this->checkFromTelephone($data[4]);
				if($verdict && $acc_verdict){
					$final_verdict = true;
				}else{
					$final_verdict = false;
					//header("Location:".URL ."settings/batchpayments?ver=failed");
				}   
			}
			if ($final_verdict) {
				header("Location:".URL ."settings/batchpayments?ver=success&action=process&path=$uploadedfile");
			}
		}else{
			header("Location:".URL ."settings/batchpayments?ver=fileformat");
		}
	}

	function verifyregcsv($uploadedfile){

		$filerec = file_get_contents($uploadedfile);
		
		$string = str_getcsv($filerec, "\n");
		$first_row_items = explode(',', $string[0]);
		
	//	echo (sizeof($first_row_items));
		
		if(sizeof($first_row_items) == 12){
			$final_verdict = false;
			/*foreach ($string as $key => $value) {
				if ($key > 0) {
					$data = explode(',', $value);
					
					if (strlen($data[10]) == 9) {
						$data[10] = '0'.$data[10];
					}
					if($this->checkTelephone($data[10])){
						$verdict = $this->checkNIN($data[0]);
					} else {
						$final_verdict = false;
					}
					if($verdict){
						$final_verdict = true;
					}else{
						$final_verdict = false;
					}
				}
			}
			*/
            
			if (!$final_verdict) {
				header("Location:".URL ."settings/batchregistration?ver=success&action=processregcsv&path=$uploadedfile");
			}
		}else{
			header("Location:".URL ."settings/batchregistration?ver=fileformat");
		}
	}

	function checkMemIdAndTelephone($id, $telephone){
		$tel = $this->db->SelectData("SELECT mobile_no FROM members WHERE c_id = ".$id);
		
		if ($tel[0]['mobile_no'] == $telephone) {
			return true;
		} else {
			$telephone = trim(preg_replace('/\s\s+/', "", $telephone));
			header("Location:".URL ."settings/batchpayments?ver=fail&tel=$telephone&id=$id");
			return false;
		}
	}

	function checkAccountNo($acc_no){
		$office_id = $_SESSION['office'];
		$acc_det = $this->db->SelectData("SELECT * FROM m_savings_account WHERE office_id = '$office_id' AND account_no = ".$acc_no ." AND account_status ='Active'");

		if (!empty($acc_det)) {
			return true;
		} else {
			$acc_no = trim(preg_replace('/\s\s+/', "", $acc_no));
			header("Location:".URL ."settings/batchpayments?ver=accfail&acc=$acc_no");
			return false;
		}
	}

	function checkTelephone($telephone){
		$tel = $this->db->SelectData("SELECT * FROM members WHERE mobile_no = ".$telephone);

		if (empty($tel)) {
			return true;
		}else {
			$telephone = trim(preg_replace('/\s\s+/', "", $telephone));
			header("Location:".URL ."settings/batchregistration?ver=exist&tel=$telephone");
			return false;
		}
	}

	function checkFromTelephone($telephone){
		$tel = $this->db->SelectData("SELECT * FROM members WHERE mobile_no = ".$telephone);

		if (empty($tel)) {
			return true;
		}else {
			$telephone = trim(preg_replace('/\s\s+/', "", $telephone));
			header("Location:".URL ."settings/batchpayments?ver=exist&tel=$telephone");
			return false;
		}
	}

	function checkNIN($nin){
		$n_id = $this->db->SelectData("SELECT * FROM members WHERE national_id = ".$nin);

		if (empty($n_id)) {
			return true;
		}else {
			$nin = trim(preg_replace('/\s\s+/', "", $nin));
			header("Location:". URL ."settings/batchregistration?ver=ninexist&tel=$nin");
			return false;
		}
	}

	function processcsv($uploadedfile){

		$filerec = file_get_contents($uploadedfile);
		$string = str_getcsv($filerec, "\r");
		
		if ($data[5] == "") {
			$date = date('Y-m-d H:i:s');
		} else {
			$date = $data[5];
		}
		foreach ($string as $key => $value) {
			$data = explode(',', $value);
			$postData = array(
				'office_id' => $_SESSION['office'],
				'member_id' => $data[1],
				'telephone' =>$data[2],
				'amount' => $data[3],
				'transaction_date' => date('Y-m-d H:i:s'),
				'from_account' => $data[4],
				'payment_date' => $date,
				'frequency' => ucfirst($data[6]),
				);
			$this->db->InsertData('bulk_payments', $postData);     
		}
		header("Location:".URL ."settings/batchpayments?msg=success");
	}	

	function processregcsv($uploadedfile){

		$filerec = file_get_contents($uploadedfile);
		$string = str_getcsv($filerec, "\n");
	    
		$postData = array();
		
		foreach ($string as $key => $value) {
		   
			if ($key > 0) {
				$data = explode(',', $value);
	
				//$postData['c_id'] = $this->MemberNo($_SESSION['office']);
				$postData['office_id'] = $_SESSION['office'];
				$postData['referer_id'] = '';
				$postData['nid'] = trim(preg_replace('/\s\s+/', "", $data[0]));
				$postData['fname'] = trim(preg_replace('/\s\s+/', "", $data[1]));
				$postData['mname'] = trim(preg_replace('/\s\s+/', "", $data[2]));
				$postData['lname'] = trim(preg_replace('/\s\s+/', "", $data[3]));
				$postData['gender'] = trim(preg_replace('/\s\s+/', "", $data[4]));
				$postData['dob'] = date('Y-m-d',strtotime(trim(preg_replace('/\s\s+/', "", $data[5]))));
				//$postData['country'] = $data[6];  
				$postData['town_city'] = trim(preg_replace('/\s\s+/', "", $data[6])) . "_" . trim(preg_replace('/\s\s+/', "", $data[7]));
				$postData['address'] = trim(preg_replace('/\s\s+/', "", $data[8]));
				$postData['mmphone'] = trim(preg_replace('/\s\s+/', "", $data[9]));
				$postData['mphone'] = trim(preg_replace('/\s\s+/', "", $data[10])); 
				$postData['email'] = trim(preg_replace('/\s\s+/', "", $data[11]));
			    $postData['form'] = "Personal";
			    
			    $postData['shares_product'] = true;
			    
                $postData['savings_product'] = true;
                
                
                $this->sendToRegForm($postData);
                sleep(5);
      
			}
			
		
		
		}
		return  header("Location:".URL ."settings/batchregistration?msg=imported");
		
		
		
	}
	
	function sendToRegForm($data){

	    $regModel = new Members_model();
        $regModel->uploadMembers($data);
	}

	function ImportBulk($data) {

		$ext = strtolower(pathinfo($data['audit_file_temp'],PATHINFO_EXTENSION));		
		
		$now = date('d_m_Y');
		$file_name = $_SESSION['office'].'_'.$_SESSION['user_id'] . '_' . $now;
		$dest = 'public/bulkpayments/' . $file_name . '.csv';

		if(move_uploaded_file($data['audit_file_temp'], $dest)){
			header("Location:".URL ."settings/batchpayments?msg=success&action=verify&path=".$dest);
		} else {
			header("Location:".URL ."settings/batchpayments?msg=failed");
		}
	}

	function ImportBulkReg($data){

		$ext = strtolower(pathinfo($data['audit_file_temp'],PATHINFO_EXTENSION));		
		
		$now = date('d_m_Y');
		$file_name = $_SESSION['office'].'_'.$_SESSION['user_id'] . '_' . $now;
		$dest = 'public/bulkreg/' . $file_name . '.csv';

		if(move_uploaded_file($data['audit_file_temp'], $dest)){
			header("Location:".URL ."settings/batchregistration?msg=success&action=verify&path=".$dest);
		} else {
			header("Location:".URL ."settings/batchregistration?msg=failed");
		}
	}

}