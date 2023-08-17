<?php

//error_reporting(0);
   
class insurance_model extends Model{
	
	public function __construct(){
		parent::__construct();
    	$this->logUserActivity(NULL);
    	if (!$this->checkTransactionStatus()) {
    		header('Location: ' . URL); 
    	}
	}

	public function getAllInsuranceClaims(){

		$claims = $this->db->SelectData("SELECT * FROM insurance_claims AS a JOIN members AS b ON a.member_id = b.c_id JOIN insurance_products AS c ON a.product_id = c.id JOIN insurance_categories AS d ON c.category = d.id JOIN insurance_subscriptions AS e ON a.subscription_id = e.id WHERE a.office_id = '".$_SESSION['office']."'");

		return $claims;
	}

	function getAllPendingInsuranceClaims(){

		$claims = $this->db->SelectData("SELECT * FROM insurance_claims AS a JOIN members AS b ON a.member_id = b.c_id JOIN insurance_products AS c ON a.product_id = c.id JOIN insurance_categories AS d ON c.category = d.id JOIN insurance_subscriptions AS e ON a.subscription_id = e.id WHERE a.office_id = '".$_SESSION['office']."' AND a.status = 'Pending'");

		return $claims;
	}

	function getAllApprovedInsuranceClaims(){

		$claims = $this->db->SelectData("SELECT * FROM insurance_claims AS a JOIN members AS b ON a.member_id = b.c_id JOIN insurance_products AS c ON a.product_id = c.id JOIN insurance_categories AS d ON c.category = d.id JOIN insurance_subscriptions AS e ON a.subscription_id = e.id WHERE a.office_id = '".$_SESSION['office']."' AND a.status = 'Approved'");

		return $claims;
	}

	function getAllClosedInsuranceClaims(){

		$claims = $this->db->SelectData("SELECT * FROM insurance_claims AS a JOIN members AS b ON a.member_id = b.c_id JOIN insurance_products AS c ON a.product_id = c.id JOIN insurance_categories AS d ON c.category = d.id JOIN insurance_subscriptions AS e ON a.subscription_id = e.id WHERE a.office_id = '".$_SESSION['office']."' AND a.status = 'Closed'");

		return $claims;
	}

	function getInsuranceSubscriptions(){
		$office = $_SESSION['office'];
		$result =  $this->db->SelectData("SELECT * FROM insurance_subscriptions AS a JOIN members AS b ON a.member_id = b.c_id WHERE a.office_id = '".$office."' AND a.status = 'Active'");
	    return $result;		
	}

	function getInsuranceTransactions($id){
		$office = $_SESSION['office'];
		$result =  $this->db->SelectData("SELECT * FROM insurance_transactions WHERE member_id = '".$id."' AND office_id = '".$office."' AND status = 'Active'");
	    return $result;	
	}

	function changeInsuranceStatus($id, $status){

		if ($status == 'close') {
			$new_data = array(
				'status' => 'Closed'
			);
		}

		if ($status == 'approve') {
			$new_data = array(
				'approval_date' => date('Y-m-d H:i:s'),
				'status' => 'Approved'
			);
		}


		$this->db->UpdateData('insurance_claims', $new_data,"`id` = '{$id}'");
	}

	function getInsuranceCategories(){
		$office = $_SESSION['office'];
		$result =  $this->db->SelectData("SELECT * FROM insurance_categories WHERE office_id = '".$office."' AND status = 'Active'");
	    return $result;	
	}

	function getMemberID($id){
		$office = $_SESSION['office'];
		$result =  $this->db->SelectData("SELECT * FROM insurance_subscriptions WHERE account_no = '".$id."' AND office_id = '".$office."' AND status = 'Active'");
		if (empty($result)) {
	    	return "";
		} else {
	    	return $result[0]['member_id'];
	    }

	}

	function getinsuracememberdetails($acc){
		$office=$_SESSION['office'];
		$result =  $this->db->SelectData("SELECT * FROM insurance_subscriptions AS a JOIN members AS b JOIN insurance_products AS c ON a.product_id = c.id WHERE b.c_id = a.member_id AND a.account_no = '".$acc."'");
		if(empty($result[0]['firstname'])){
			$displayname=$result[0]['company_name'];
		}else{
			$displayname=$result[0]['firstname']." ".$result[0]['middlename']." ".$result[0]['lastname'];
		}	
		if(count($result)>0){
			$rset=array();
			foreach ($result as $key => $value) {
				array_push($rset,array(
					'member_id'=>$result[$key]['c_id'],
					'displayname'=>$displayname,
					'dob'=>$result[$key]['date_of_birth'],
					'national_id'=>$result[$key]['national_id'],
					'address'=>$result[$key]['address'],
					'product'=>$result[$key]['name'],
					'product_id'=>$result[$key]['product_id'],
					'description'=>$result[$key]['description']
					));
			}

			echo json_encode(array("result" =>$rset));
			die();
		}

	}

	function getMemberaccountName($id){
		$result =  $this->db->SelectData("SELECT * FROM insurance_subscriptions AS a JOIN members AS b ON a.member_id = b.c_id WHERE a.account_no = '".$id."' AND a.status = 'Active'");

		if (empty($result)) {
	    	return "";
		} else {

			if(empty($result[0]['firstname'])){
				$displayname=$result[0]['company_name'];
			}else{
				$displayname=$result[0]['firstname']." ".$result[0]['middlename']." ".$result[0]['lastname'];
			}
	    	
		return $displayname;
	    }

	}

	function getInsuranceAccountTransactions($acc){
		$office=$_SESSION['office'];
		$result =  $this->db->SelectData("SELECT * FROM insurance_transactions WHERE account_no='$acc' ORDER BY id DESC LIMIT 20 ");
		if(count($result)>0){
			foreach ($result as $key => $value) {  
				$rset[$key]['amount'] = $result[$key]['amount'];
				$rset[$key]['transaction_type'] = $result[$key]['transaction_type'];
				$rset[$key]['balance'] = $result[$key]['running_balance'];
				$rset[$key]['depositor'] = $result[$key]['depositor_name'];
				$rset[$key]['trans_date'] = $result[$key]['transaction_date'];
			}
			$reversed_array = array_reverse($rset);
	        return $reversed_array;
		}
		
	}

	function getAllInsuranceAccountTransactions($acc){
		$result =  $this->db->SelectData("SELECT * FROM insurance_transactions WHERE account_no = '".$acc."' ");

		if(count($result)>0){
			foreach ($result as $key => $value) {  
				$rset[$key]['amount'] = $result[$key]['amount'];
				$rset[$key]['transaction_type']=$result[$key]['transaction_type'];
				$rset[$key]['balance'] = $result[$key]['running_balance'];
				$rset[$key]['depositor'] = $result[$key]['depositor_name'];
				$rset[$key]['trans_date'] = $result[$key]['transaction_date'];
			}
			return $rset;
		}
		
	}

	function getInsuranceProductDetails($id){
		$result =  $this->db->SelectData("SELECT * FROM insurance_subscriptions AS a JOIN insurance_products AS b ON a.product_id = b.id WHERE a.account_no = '".$id."' AND a.status = 'Active'");

		if (empty($result)) {
	    	return "";
		} else {
			return $result;
		}
	}

	function getInsuranceProductapp($amt, $id, $category){
		$product_id =0;

		$office=$_SESSION['office'];
		$rset=array(); 
		$client_pdt= $this->db->SelectData("SELECT DISTINCT(product_id) AS product_id FROM insurance_subscriptions where member_id='".$id."'");

		$no =count($client_pdt);
		$add=null;
		if($no>0){
			
			for($i=0;$i<$no;$i++){
				$add .= " AND id!='".$client_pdt[$i]["product_id"]."'";		
			}

			$pdts= $this->db->SelectData("SELECT * FROM insurance_products WHERE category = '".$category."' AND min_amount <= '".$amt."' AND max_amount >= '".$amt."' AND office_id = $office AND product_status='Active' $add");

			if(count($pdts)>0){

				foreach ($pdts as $key => $value) {
					$pointers= $this->db->SelectData("SELECT * FROM acc_gl_pointers P JOIN  transaction_type T ON P.transaction_type_id=T.transaction_type_id where T.product_type='8' AND P.product_id='".$value['id']."'");		

					array_push($rset,array(
						'id'=>$value['id'],
						'name'=>$value['name'],
					));

				}	
				echo json_encode(array("result" =>$rset));		  

			}else{
				die();
			}

		}else{
			$pdts= $this->db->SelectData("SELECT * FROM insurance_products WHERE category = '".$category."' AND min_amount <= '".$amt."' AND max_amount >= '".$amt."' AND office_id = $office AND product_status='Active'");
			foreach($pdts as $key => $value) {
				$pointers= $this->db->SelectData("SELECT * FROM acc_gl_pointers P JOIN  transaction_type T ON P.transaction_type_id=T.transaction_type_id where T.product_type='8' AND P.product_id='".$value['id']."'");		
				//if(count($pointers)>5){	
				array_push($rset,array(
					'id'=>$value['id'],
					'name'=>$value['name'],
				));
				//}
			}	
			echo json_encode(array("result" =>$rset));			  
			die();		
			
			
		}

	}


	function applyinsurancesubscription($data){	

		$new_data = array(
			'office_id' => $_SESSION['office'],
			'member_id' => $data['cid'],
			'user_id' => $_SESSION['user_id'],
			'product_id' => $data['product_id'],
			'registration_date' => date('Y-m-d H:i:s'),
			'account_no' => $this->generateInsuranceAccountNo()
		);

		$this->db->InsertData('insurance_subscriptions', $new_data);
		header('Location: ' . URL .'insurance?msg=success'); 
	}

	function applyinsuranceclaim($data){

		$new_data = array(
			'office_id' => $_SESSION['office'],
			'member_id' => $data['cid'],
			'product_id' => $data['product_id'],
			'subscription_id' => $data['subscription_id'],
			'claim_date' => date('Y-m-d H:i:s'),
			'user_id' => $_SESSION['user_id'],
			'status' => "Pending"
		);

		$this->db->InsertData('insurance_claims', $new_data);
		header('Location: ' . URL .'insurance/newclaim?msg=success'); 

	}

	function generateInsuranceAccountNo(){
		$accno = $_SESSION['office'].date("mdhis").rand(1,9);
		return (string) $accno;
	}

	function getInsuranceClaims($id){

		$claims = $this->db->SelectData("SELECT * FROM insurance_claims AS a JOIN members AS b ON a.member_id = b.c_id JOIN insurance_products AS c ON a.product_id = c.id JOIN insurance_categories AS d ON c.category = d.id JOIN insurance_subscriptions AS e ON a.subscription_id = e.id WHERE a.office_id = '".$_SESSION['office']."' AND a.member_id='".$id."'");

		return $claims;
	}

	function getApprovedInsuranceClaims($id){

		$claims = $this->db->SelectData("SELECT * FROM insurance_claims AS a JOIN members AS b ON a.member_id = b.c_id JOIN insurance_products AS c ON a.product_id = c.id JOIN insurance_categories AS d ON c.category = d.id JOIN insurance_subscriptions AS e ON a.subscription_id = e.id WHERE a.status = 'Approved' AND a.office_id = '".$_SESSION['office']."' AND a.member_id='".$id."'");

		return $claims;
	}

	function getPendingInsuranceClaims($id){

		$claims = $this->db->SelectData("SELECT * FROM insurance_claims AS a JOIN members AS b ON a.member_id = b.c_id JOIN insurance_products AS c ON a.product_id = c.id JOIN insurance_categories AS d ON c.category = d.id JOIN insurance_subscriptions AS e ON a.subscription_id = e.id WHERE a.status = 'Pending' AND a.office_id = '".$_SESSION['office']."' AND a.member_id='".$id."'");

		return $claims;
	}

	function getClosedInsuranceClaims($id){

		$claims = $this->db->SelectData("SELECT * FROM insurance_claims AS a JOIN members AS b ON a.member_id = b.c_id JOIN insurance_products AS c ON a.product_id = c.id JOIN insurance_categories AS d ON c.category = d.id JOIN insurance_subscriptions AS e ON a.subscription_id = e.id WHERE a.status = 'Closed' AND a.office_id = '".$_SESSION['office']."' AND a.member_id='".$id."'");

		return $claims;
	}
}

?>