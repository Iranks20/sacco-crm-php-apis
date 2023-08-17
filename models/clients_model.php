<?php

//error_reporting(0);
   
class Clients_model extends Model{
	
	public function __construct(){
		parent::__construct();
    	$this->logUserActivity(NULL);
    	if (!$this->checkTransactionStatus()) {
    		header('Location: ' . URL); 
    	}
	}

	public function getAllMembers(){

		$office=$_SESSION['office'];
		$query= $this->db->SelectData("SELECT * FROM members c JOIN m_branch b WHERE c.office_id =b.id AND c.office_id='".$office."' ORDER BY c.c_id desc");

		$count=count($query);
	 	if($count>0){

	 		foreach ($query as $key => $value) {		

				$account=$this->getAccountNo($query[$key]['c_id']);	
				
				if(empty($query[$key]['company_name'])){
				    $rset[$key]['name'] =$query[$key]['firstname']." ".$query[$key]['middlename']." ".$query[$key]['lastname']; 
				    if (empty($query[$key]['referer_id'])) {
				    	$rset[$key]['referer'] = '-';
				    	$rset[$key]['ref_mobile'] = '-';
				    } else {
					    $referer = $this->getRefeerer($query[$key]['referer_id']);

						$rset[$key]['referer'] = $referer[0]['firstname'] . ' ' . $referer[0]['middlename'] . ' ' . $referer[0]['lastname'];
						$rset[$key]['ref_mobile'] = $referer[0]['mobile_no'];
					}
					$rset[$key]['type'] = "Personal";
				}else{

				    if (empty($query[$key]['referer_id'])) {
				    	$rset[$key]['referer'] = '-';
				    	$rset[$key]['ref_mobile'] = '-';
				    } else {
					    $referer = $this->getRefeerer($query[$key]['referer_id']);
						$rset[$key]['referer'] = $referer[0]['firstname'] . ' ' . $referer[0]['middlename'] . ' ' . $referer[0]['lastname'];
						$rset[$key]['ref_mobile'] = $referer[0]['mobile_no'];
					}
					$rset[$key]['name'] =$query[$key]['company_name'];	
					$rset[$key]['incorporation_date'] =$query[$key]['incorporation_date'];	
					$rset[$key]['incorporation_expiry'] =$query[$key]['incorporation_expiry'];	
					$rset[$key]['incorporation_no'] =$query[$key]['incorporation_no'];	
					$rset[$key]['business_line'] =$query[$key]['business_line'];	
					$rset[$key]['type'] = "Company";
				}

				$rset[$key]['accountno'] =$account; 
			    $rset[$key]['c_id'] =$query[$key]['c_id']; 
			    $rset[$key]['branch'] = $this->getbranch($query[$key]['branch_id']); 
			    $rset[$key]['mobile_no'] =$query[$key]['mobile_no']; 
			    $rset[$key]['status'] =$query[$key]['status']; 
			    $rset[$key]['office'] =$query[$key]['name'];
			    $rset[$key]['clic_user_name'] =$query[$key]['clic_user_name'];

			}
			return $rset;
		}
	}

	function getStellarDetails($phone_no){

		$stellar_details = array();
		$stellar_address = $this->getStellarAddress($phone_no);

		if (!empty($stellar_address)) {

			//'GDFBYH6R6U2C2TRESMHXVM3ZGGKFHNDKRHDUKPXGDR3QJTYWNTB3OJRA'
			$public_key = $stellar_address['account_id'];
			$stellar_balances = $this->getStellarBalances($public_key);

			if (!empty($stellar_balances)) {
				$stellar_details['address'] = $stellar_address['stellar_address'];
				foreach ($stellar_balances['balances'] as $key => $value) {
					$balances = (array) $value;
					if (isset($balances['asset_code'])) {
						$stellar_details[$balances['asset_code']] = $balances['balance'];
					}
				}
			}
		}
		return $stellar_details;
	}

	function getStellarAddress($phone_no){

		/*$url = STELLAR;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,"tel=".$phone_no);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$server_output = curl_exec ($ch);
		curl_close ($ch);
		return (array) json_decode($server_output);*/

		/*
		# Form our options
		$opts = array('http' =>
		    array(
		        'method'  => 'POST',
		        'header'  => 'Content-type: application/x-www-form-urlencoded',
		        'tel' => $phone_no
		    )
		);
		# Create the context
		$context = stream_context_create($opts);
		# Get the response (you can use this for GET)
		$result = file_get_contents('/api/update', false, $context);
		*/
		/*

		$url = STELLAR . "?q=" . $phone_no;
		error_reporting(0);
		$server_output = file_get_contents($url);
		return (array) json_decode($server_output);
		*/

	}

	function getStellarBalances($public_key){
		
	//	$url = STELLAR_BAL . $public_key;

		/* $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , 30); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$server_output = curl_exec ($ch);
		curl_close ($ch); 

		echo "string";
		print_r($server_output);
		die();
		*/
		/*
		error_reporting(0);
		$server_output = file_get_contents($url);
		return (array) json_decode($server_output);
		*/
	}

	public function getMembersMissingRequirements(){

		$office=$_SESSION['office'];
		$query= $this->db->SelectData("SELECT * FROM members c JOIN m_branch b WHERE c.office_id =b.id AND c.office_id='".$office."' AND status != 'Active' AND status != 'Pending Approval' ORDER BY c.c_id desc");

		$count=count($query);
	 	if($count>0){

	 		foreach ($query as $key => $value) {		

				$account=$this->getAccountNo($query[$key]['c_id']);	

				if(empty($query[$key]['company_name'])){
				    $rset[$key]['name'] =$query[$key]['firstname']." ".$query[$key]['middlename']." ".$query[$key]['lastname']; 
				    $referer = $this->getRefeerer($query[$key]['referer_id']);
				    if (!empty($referer)) {
						$rset[$key]['referer'] = $referer[0]['firstname'] . ' ' . $referer[0]['middlename'] . ' ' . $referer[0]['lastname'];
						$rset[$key]['ref_mobile'] = $referer[0]['mobile_no'];
					}
				}else{
					$rset[$key]['name'] =$query[$key]['company_name'];	
					$rset[$key]['incorporation_date'] =$query[$key]['incorporation_date'];	
					$rset[$key]['incorporation_expiry'] =$query[$key]['incorporation_expiry'];	
					$rset[$key]['incorporation_no'] =$query[$key]['incorporation_no'];	
					$rset[$key]['business_line'] =$query[$key]['business_line'];	
				}
				$rset[$key]['accountno'] =$account; 
			    $rset[$key]['c_id'] =$query[$key]['c_id']; 
			    $rset[$key]['mobile_no'] =$query[$key]['mobile_no']; 
			    $rset[$key]['status'] =$query[$key]['status']; 
			    $rset[$key]['office'] =$query[$key]['name'];
			}
			return $rset;
		}
	}

	public function getMembersPendingApproval(){

		$office=$_SESSION['office'];
		$query= $this->db->SelectData("SELECT * FROM members c JOIN m_branch b WHERE c.office_id =b.id AND c.office_id='".$office."' AND status = 'Pending Approval' ORDER BY c.c_id desc");

		$count=count($query);
	 	if($count>0){

	 		foreach ($query as $key => $value) {		

				$account=$this->getAccountNo($query[$key]['c_id']);	
				//-----bob --------
				if(empty($query[$key]['company_name'])){
				    $rset[$key]['name'] =$query[$key]['firstname']." ".$query[$key]['middlename']." ".$query[$key]['lastname']; 
				    $referer = $this->getRefeerer($query[$key]['referer_id']);
				    if (!empty($referer)) {
						$rset[$key]['referer'] = $referer[0]['firstname'] . ' ' . $referer[0]['middlename'] . ' ' . $referer[0]['lastname'];
						$rset[$key]['ref_mobile'] = $referer[0]['mobile_no'];
					}
				}else{
					$rset[$key]['name'] =$query[$key]['company_name'];	
					$rset[$key]['incorporation_date'] =$query[$key]['incorporation_date'];	
					$rset[$key]['incorporation_expiry'] =$query[$key]['incorporation_expiry'];	
					$rset[$key]['incorporation_no'] =$query[$key]['incorporation_no'];	
					$rset[$key]['business_line'] =$query[$key]['business_line'];	
				}
				$rset[$key]['accountno'] =$account; 
			    $rset[$key]['c_id'] =$query[$key]['c_id']; 
			    $rset[$key]['mobile_no'] =$query[$key]['mobile_no']; 
			    $rset[$key]['status'] =$query[$key]['status']; 
			    $rset[$key]['office'] =$query[$key]['name'];
			}
			return $rset;
		}
	}

	public function processRequest($id, $pic){

		$sta =$this->getClient($id);

		$status = $sta[0]['status'];

		if($pic =='profile'){
			if ($status != 'Closed') {
				$postData['status'] = explode(':',$this->getNewStatus($status, $pic))[0];
				$postData['status_code'] = explode(':',$this->getNewStatus($status, $pic))[1];
				$this->db->UpdateData('members',$postData, "`c_id` = {$id}");
			}

			$imageData['status'] = "Old";
			$this->db->UpdateData('m_pictures',$imageData, "`member_id` = {$id} AND `type` = 'image' AND `status` = 'Current'");

			header('Location: ' . URL . 'members/statusone/'.$id.'?req=success');  	
		}elseif ($pic =='national') {
			if ($status != 'Closed') {
				$postData['status'] = explode(':',$this->getNewStatus($status, $pic))[0];
				$postData['status_code'] = explode(':',$this->getNewStatus($status, $pic))[1];
				$this->db->UpdateData('members',$postData, "`c_id` = {$id}");
			}

			$imageData['status'] = "Old";
			$this->db->UpdateData('m_pictures',$imageData, "`member_id` = {$id} AND `type` = 'id_passport' AND `status` = 'Current'");

			header('Location: ' . URL . 'members/statusone/'.$id.'?req=success');  	
		}elseif ($pic =='signature') {
			if ($status != 'Closed') {
				$postData['status'] = explode(':',$this->getNewStatus($status, $pic))[0];
				$postData['status_code'] = explode(':',$this->getNewStatus($status, $pic))[1];
				$this->db->UpdateData('members',$postData, "`c_id` = {$id}");
			}

			$imageData['status'] = "Old";
			$this->db->UpdateData('m_pictures',$imageData, "`member_id` = {$id} AND `type` = 'signature' AND `status` = 'Current'");

			header('Location: ' . URL . 'members/statusone/'.$id.'?req=success');  	
		}elseif ($pic =='regdoc') {
			if ($status != 'Closed') {
				$postData['status']= 'Pending Approval';
				$postData['status_code']= '4';
				$this->db->UpdateData('members',$postData, "`c_id` = {$id}");
			}

			$imageData['status'] = "Old";
			$this->db->UpdateData('m_pictures',$imageData, "`member_id` = {$id} AND `type` = 'image' AND `status` = 'Current'");

			header('Location: ' . URL . 'clients/details/'.$id.'?req=success');  	
		}elseif ($pic =='licence') {
			if ($status != 'Closed') {
				$postData['status']= 'Pending Approval';
				$postData['status_code']= '4';
			$this->db->UpdateData('members',$postData, "`c_id` = {$id}");
			}

			$imageData['status'] = "Old";
			$this->db->UpdateData('m_pictures',$imageData, "`member_id` = {$id} AND `type` = 'id_passport' AND `status` = 'Current'");

			header('Location: ' . URL . 'clients/details/'.$id.'?req=success');  	
		}else{
			
			header('Location: ' . URL . 'members/statusone/'.$id.'?activation=rejected');  	
		}

	}

	function getNewStatus($status, $pic){
	
		if($status=='Missing requirements' && $pic =='profile'){
			return 'Missing requirements:0';
		}else if($status=='Missing requirements' && $pic =='national'){
			return 'Missing requirements:0';
		}else if($status=='Missing requirements' && $pic =='signature'){
			return 'Missing requirements:0';
		}else if($status=='Missing Photo & Signature' && $pic =='profile'){
			return 'Missing Photo & Signature:13';
		}else if($status=='Missing Photo & Signature' && $pic =='national'){
			return 'Missing requirements:0';
		}else if($status=='Missing Photo & Signature' && $pic =='signature'){
			return 'Missing Photo & Signature:13';
		}else if($status=='Missing Photo & ID' && $pic =='profile'){
			return "Missing Photo & ID:23";
		}else if($status=='Missing Photo & ID' && $pic =='national'){
			return "Missing Photo & ID:23";
		}else if($status=='Missing Photo & ID' && $pic =='signature'){
			return "Missing requirements:0";
		}else if($status=='Missing Signature & ID' && $pic =='profile'){
			return 'Missing requirements:0';
		}else if($status=='Missing Signature & ID' && $pic =='national'){
			return 'Missing Signature & ID:12';
		}else if($status=='Missing Signature & ID' && $pic =='signature'){
			return 'Missing Signature & ID:12';
		}else if($status=='Missing ID' && $pic =='profile'){
			return 'Missing Photo & ID:23';
		}else if($status=='Missing ID' && $pic =='national'){
			return 'Missing ID:2';
		}else if($status=='Missing ID' && $pic =='signature'){
			return 'Missing Signature & ID:12';
		}else if($status=='Missing Photo' && $pic =='profile'){
			return 'Missing Photo:3';
		}else if($status=='Missing Photo' && $pic =='national'){
			return 'Missing Photo & ID:23';
		}else if($status=='Missing Photo' && $pic =='signature'){
			return 'Missing Photo & Signature:13';
		}else if($status=='Missing Signature' && $pic =='profile'){
			return 'Missing Photo & Signature:13';
		}else if($status=='Missing Signature' && $pic =='national'){
			return 'Missing Signature & ID:23';
		}else if($status=='Missing Signature' && $pic =='signature'){
			return 'Missing Signature:1';
		}else if ($status=='Pending Approval' && $pic =='profile') {
			return 'Missing Photo:3';
		}else if ($status=='Pending Approval' && $pic =='national') {
			return 'Missing ID:2';
		}else if ($status=='Pending Approval' && $pic =='signature') {
			return 'Missing Signature:1';
		}else if ($status=='Active' && $pic =='profile') {
			return 'Missing Photo:3';
		}else if ($status=='Active' && $pic =='national') {
			return 'Missing ID:2';
		}else if ($status=='Active' && $pic =='signature') {
			return 'Missing Signature:1';
		}else if ($status=='Missing Photo') {
			return 'Pending Approval:4';
		}else if ($status=='Missing ID') {
			return 'Pending Approval:4';
		}else if ($status=='Missing Signature') {
			return 'Pending Approval:4';
		}
	}

	function getRefeerer($cid){
		$result= $this->db->selectData("SELECT firstname, middlename, lastname, mobile_no FROM members WHERE c_id='".$cid."' ");
		return $result;
	}

	function getAccountNo($cid){
		$result= $this->db->selectData("SELECT min(account_no) as account FROM m_savings_account WHERE member_id='".$cid."' ");
		return $result[0]['account'];
	}

	function getClient($id){
		return $this->db->SelectData("SELECT * FROM members c INNER JOIN m_branch b where c.office_id  = b.id AND c.c_id='".$id."'  order by c.c_id desc");
	}

function getClientPhone($id){
		return $this->db->SelectData("SELECT * FROM members c INNER JOIN m_branch b where c.office_id  = b.id AND c.mobile_no='".$id."'  order by c.c_id desc");
	}
	function getClientSavingsdDetails($id){
		return $this->db->SelectData("SELECT p.name,member_id, running_balance as amount, s.id ,account_no FROM m_savings_product p INNER JOIN m_savings_account s where s.product_id = p.id and  s.member_id='".$id."' order by s.id DESC");
	}

	function getClientInsurancedDetails($id){
		return $this->db->SelectData("SELECT * FROM insurance_subscriptions AS a JOIN members AS b JOIN insurance_products AS c ON a.product_id = c.id WHERE b.c_id = a.member_id AND a.member_id = '".$id."' order by a.id DESC");
	}

	function getMemberLoans($id){
		return $this->db->SelectData("SELECT * FROM m_loan l INNER JOIN  m_product_loan p WHERE l.loan_status != 'Closed' AND l.product_id=p.id AND l.member_id='".$id."' ORDER BY l.loan_id DESC LIMIT 5");
	}

	function getMemberShares($id){
		return $this->db->SelectData("SELECT * FROM share_account s INNER JOIN  share_products p WHERE s.product_id=p.id AND s.member_id='".$id."'");
	}

	function getClientAge($id){
		return $this->db->SelectData("SELECT TIMESTAMPDIFF(YEAR,date_of_birth,CURDATE()) as age FROM members where c_id='".$id."' ");
	}

	function getClientWalletdDetails($id){
	    
		$data = $this->db->SelectData("SELECT  * from sm_mobile_wallet where member_id = '$id'");
		
		$data[0]['clix_wallet_balance'] = $this->getClixWalletbalance($data[0]['wallet_account_number']);
		return $data;
	}
	
	function getClixWalletbalance($member_id)
	{
	    
	    $settings = $this->db->SelectData("SELECT  * from system_settings where instance_id = " . $_SESSION['office']);
	    
	    $url = STELLAR_WALLET_BALANCE . "?username=". $member_id . "&currency=" . $settings[0]['currency'];
	    //$url = STELLAR_WALLET_BALANCE . "?username=". $member_id . "&currency=UGX";
	    
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		//curl_setopt($ch, CURLOPT_POST, 1);
		//curl_setopt($ch, CURLOPT_POSTFIELDS,"tel=".$phone_no);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$server_output = curl_exec ($ch);
		curl_close ($ch);
		
		$response = (array) json_decode($server_output);
	    //return $response['balance'];
		
		$balance = "-";
		for ($x = 0; $x <= 10; $x++) {
		
		    if($response['wallet'][$x]->asset_code == $settings[0]['currency'])
		    {
                $balance = $response['wallet'][$x]->balance;
		    }
		    
		}
	    return $balance;
	}

	function ApproveBusiness($id){

		$status=$this->getClient($id);
		if($status[0]['status']=='Pending Approval'){

		$postData['status']='Active';	
		$postData['status_code']='5';	

			$this->db->UpdateData('members',$postData, "`c_id` = {$id}");

			header('Location: ' . URL . 'clients/details/'.$id.'?msg=success');  	
		}else{
			
			header('Location: ' . URL . 'members/statusone/'.$id.'?activation=rejected');  	
		}
	}

	function getClientImage($id){
		$results =  $this->db->selectData("SELECT * FROM m_pictures WHERE type='image' AND status='Current' AND member_id='".$id."'");

		if (count($results) > 0) {
			return $results[0]['image'];
		} else {
			return "";
		}
	}

	function getClientPassport($id){
		$results =  $this->db->selectData("SELECT * FROM m_pictures WHERE type='id_passport' AND status='Current' AND member_id='".$id."'");

		if (count($results) > 0) {
			return $results[0]['image'];
		} else {
			return "";
		}
	}

	function getClientSignature($id){
		$results =  $this->db->selectData("SELECT * FROM m_pictures WHERE type='signature' AND status='Current' AND member_id='".$id."'");

		if (count($results) > 0) {
			return $results[0]['image'];
		} else {
			return "";
		}

	}

	function getLoanDetails($id){
		return $this->db->SelectData("SELECT * FROM m_loan l INNER JOIN  m_product_loan p WHERE l.product_id=p.id AND l.member_id='".$id."'");
	}

	function CreateAccounts($id){
		die('Trying to figure it out');
	}
}