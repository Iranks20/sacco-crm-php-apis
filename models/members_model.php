<?php

class Members_model extends Model{

	public function __construct(){

		parent::__construct();
		@session_start();
    	$this->logUserActivity(NULL); 
    	if (!$this->checkTransactionStatus()) {
    		header('Location: ' . URL); 
    	}

	}
	
 function GetMemberData($phone){
    $office = $_SESSION['office'];
return $this->db->selectData("SELECT * from members where c_id='$phone' AND  office_id = '" . $office . "'");
}
 	function base64ToImage($base64_string) {

		if ($base64_string != '') {

		    $img = $base64_string;
			$img = str_replace('data:image/jpeg;base64,', '', $img);
			$img = str_replace(' ', '+', $img);
			$data = base64_decode($img);
			$file = 'public/images/avatar/'. uniqid() . '.jpeg';
			if (file_put_contents($file, $data)){
				return $file;
			}else{
				return '';
			}
		}else{
			return '';
		}
	}
	

function ImageLoad($tel){
	$rs = $this->GetMemberData($tel);
	$imaeg = $this->getClientImage($rs[0]['c_id']);
   // echo $rs[0]['image'];exit();
	if(count($imaeg)>0){
	    $pp = $this->base64ToImage($imaeg[0]['image']);
	}else{
	    $pp = "public/images/avatar/".$rs[0]['image'];
	    //echo $pp; exit();
	}
    
	if(count($rs)>0){
	    
	   $accountno = $rs[0]['c_id'];
	   $name = $rs[0]['firstname'];
	   $lname = $rs[0]['lastname'];
	   //$tel  = $rs[0]['mobile_no'];
	   $dob = $rs[0]['date_of_birth'];
	   $nin = $rs[0]['national_id'];
	   $image = $pp;
	   $regno = $rs[0]['secondary_id'];
	   $this->img->ImageLoad($tel,$name, $lname, $dob, $nin, $regno, $pp, $accountno); 
	   
	}
	
}
	
	
	function ImageLoadBack($tel){
 
	   $this->img2->ImageLoader($tel,$name);
	   
	

}

function PdfLoad($tel){

    $rs = $this->GetMemberData($tel);
	$imaeg = $this->getClientImage($rs[0]['c_id']);
   // echo $rs[0]['image'];exit();
	if(count($imaeg)>0){
	    $pp = $this->base64ToImage($imaeg[0]['image']);
	}else{
	    $pp = "public/images/avatar/".$rs[0]['image'];
	    //echo $pp; exit();
	}
    
	//if(count($rs)>0){
	    
	   $accountno = $rs[0]['c_id'];
	   $name = $rs[0]['firstname'];
	   $lname = $rs[0]['lastname'];
	   //$tel  = $rs[0]['mobile_no'];
	   $dob = $rs[0]['date_of_birth'];
	   $nin = $rs[0]['national_id'];
	   $image = $pp;
	   $regno = $rs[0]['secondary_id'];
	  
	   $this->pdfi->LoadPdf($tel,$name, $lname, $dob, $nin, $regno, $pp, $accountno);
//	}
	
}

function getSaccoMessageTemplates(){

		$office_id = $_SESSION['office'];
		$results = $this->db->selectData("SELECT * FROM messages WHERE office_id='".$office_id."' AND status = 'Active'");
		return $results;
	}

	/////////////////////////////////////////Standing order////////////////////////////////////////////

	function addstandingorder($data){
		try{
			$this->db->beginTransaction();
			$postData = array(
				'from_office_id' => $data['sacco_id'],
				'to_office_id' => $data['sacco_id_r'],
				'from_client_id' => $data['sender_id'],
				'to_client_id' => $data['receiver_id'],
				'from_savings_account_id' => $data['account_no'],
				'to_savings_account_id' => $data['account_rec'],
				'from_loan_account_id' => NULL,
				'to_loan_account_id' => NULL,
				'transfer_amount' => $data['trans_shares'],
				'transfer_type' => $data['duration'],
			);

			$this->db->InsertData('m_account_transfer_details', $postData);
			$this->db->commit();
			header('Location: ' . URL . 'members/makeastandingorder/?msg=success'); 
			
		}catch(Exception $e){
			$this->db->rollBack();
			$error=$e->getMessage();
			header('Location: ' . URL . 'members/makeastandingorder/?msg=fail&error='.$error); 	  
			exit(); 	  
		}
	}

	function addsavingstansfer($data){

		$this->db->beginTransaction();

		$acc_sender = $data['account_no'];
		$result_send = $this->db->selectData("SELECT * FROM m_savings_account WHERE account_no='".$acc_sender."'");

		$sender_amount = str_replace(",","",$data['trans_shares']);
		$sender_runnning_balance = $result_send[0]['running_balance'];
		$availablewithdraws = $result_send[0]['total_withdrawals'];
		$new_total_withdraws = $availablewithdraws + $sender_amount ;
		$new_sender_balance = $sender_runnning_balance - $sender_amount;

		$update_time = date('Y-m-d H:i:s');
		$acc = $data['account_rec'];
		$result = $this->db->selectData("SELECT * FROM m_savings_account WHERE account_no='".$acc."'");

		$amount = str_replace(",","",$data['trans_shares']);
		$balance = $result[0]['running_balance'];
		$availabledeposit = $result[0]['total_deposits'];
		$new_total_deposits = $availabledeposit + $amount ;
		$new_balance = $amount + $balance;

		try{

			$senderData = array(
				'savings_account_no' => $acc_sender,
				'transaction_type' => "Transfer",
				'payment_detail_id' => "Transfer",
				'amount' => $sender_amount,
                'op_type'=>'DR',
				'running_balance' => $new_sender_balance,
				'depositor_name' => $data['account_name'],
				'amount_in_words' => $this->convertNumber($sender_amount),
				'telephone_no' => NULL,
				'branch' => $_SESSION['office'],
				'user_id' => $_SESSION['user_id'],
			);

			$receiverData = array(
				'savings_account_no' => $acc,
				'transaction_type' => "Transfer",
				'payment_detail_id' =>  "Transfer",
				'amount' => $amount,
                'op_type'=>'CR',
				'running_balance' => $new_balance,
				'depositor_name' => $data['account_name'],
				'amount_in_words' => $this->convertNumber($amount),
				'telephone_no' => NULL,
				'branch' =>$_SESSION['office'],
				'user_id' => $_SESSION['user_id'],
			);

			$withdraw_trans_id = $this->db->InsertData('m_savings_account_transaction', $senderData);
			$deposit_trans_id = $this->db->InsertData('m_savings_account_transaction', $receiverData);

			$withdrawstatus = array(
				'total_withdrawals' => $new_total_withdraws,
				'running_balance' => $new_sender_balance,
				'last_updated_on' =>$update_time,
			);

			$depositstatus = array(
				'total_deposits' => $new_total_deposits,
				'running_balance' => $new_balance,
				'last_updated_on' =>$update_time,
			);

			$this->db->UpdateData('m_savings_account', $withdrawstatus,"`account_no` = '{$acc_sender}'");
			$this->db->UpdateData('m_savings_account', $depositstatus,"`account_no` = '{$acc}'");

			$this->db->commit();
			header('Location:'.URL.'members/makesavingstransfer?msg=success');

		}catch(Exception $e){
			$this->db->rollBack();
			$error=$e->getMessage();
			header('Location:'.URL.'members/makesavingstransfer?msg=fail&error='.$error);
			exit(); 	  
		}
	}

	//////////////////////////////////////////////////////////////////////////////////////////////////


	function uploadAndProcess(){
		
		$response = $this->upload($_FILES);

		$file_details = explode(",", $response);
		$fileCode = trim(strtolower(str_replace('"', "", rtrim(explode(":", $file_details[0])[1]))));
		$fileName = str_replace('}', "", str_replace('\\', "", str_replace('"', "", rtrim(explode(":", $file_details[2])[1]))));
		
		$filename = $fileName . ".jpg";
		//$filename = "amon.jpg";
		$final_response = $this->process(trim($filename));

		$details = explode(",", $final_response);
		return $details;
	}
	
	
	
	function checkNationalIdWithNira(){
	    print_r($_POST);
	    die();
	}

	function upload($data){
		$url = OCR_API . OCR_UPLOAD;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array(
												'file' => '@' . base64_encode(file_get_contents($data['file']['tmp_name']))
											));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$server_output = curl_exec ($ch);
		curl_close ($ch);
		return  $server_output;
	}

	function process($filename){
		$data = array (
			'image' => $filename
        );
        
        $params = '';
        foreach($data as $key=>$value){
        	$params .= $key.'='.$value.'&';
        }
         
        $params = trim($params, '&');
		$url = OCR_API . OCR_PROCESS;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url.'?'.$params);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , 30); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$server_output = curl_exec ($ch);
		curl_close ($ch);
		return  $server_output;
	}

	function ImportBulk($data) {
		$id = $_POST['members'];	
		$status_code=$this->getClientDetails($id);

		// get uploaded file's extension
		$ext = strtolower(pathinfo($data['audit_file_temp'],PATHINFO_EXTENSION));		
		
		$now = date('d_m_Y');
		$file_name = $_SESSION['office'].'_'.$_SESSION['user_id'] . '_' . $now;
		$dest = 'public/systemlog/member_list/' . $file_name . '.csv';
		move_uploaded_file($data['audit_file_temp'], $dest);
		$filerec = file_get_contents($dest);
		$string = str_getcsv($filerec, "\r");
		foreach ($string as $key => $value) {
			$data = explode(',', $value);
			$postData = array(
				'c_id' => $data[1],
				'office_id' =>$_SESSION['office'],
				'firstname' =>$data[2],
				'middlename' =>$data[4],
				'lastname' => $data[3],
				'submittedon_date' =>date('Y-m-d H:i:s'),
				);
			$this->db->InsertData('members', $postData);     
		}
		
	}

	function getOfficeShareProducts(){
         $office=$_SESSION['office'];
         return $this->db->SelectData("SELECT * FROM share_products  where product_status = 'Active' AND office_id = '".$office."'");
    }

    function getOfficeSavingsProducts(){
         $office=$_SESSION['office'];
         return $this->db->SelectData("SELECT * FROM m_savings_product where product_status = 'Active' AND office_id = '".$office."'");
    }
    function getRegistrationProducts(){
		$office=$_SESSION['office'];
		return $this->db->SelectData("SELECT * FROM m_reg_settings WHERE status = 'Active' AND sacco_id = '$office' ");
		
	}
	function getDefaultShareProduct(){
		$office=$_SESSION['office'];
		$defaults = $this->db->SelectData("SELECT * FROM m_reg_settings WHERE status = 'Active' AND sacco_id = '$office' AND product_type = 1");

		if (count($defaults) > 0) {
			return $this->db->SelectData("SELECT * FROM share_products  where product_status = 'Active' AND office_id = '".$office."' AND id = '".$defaults[0]['p_id']."'");
		} else {
			return NULL;
		}
    }

	function getDefaultSavingsProduct(){
		$office=$_SESSION['office'];
		$defaults = $this->db->SelectData("SELECT * FROM m_reg_settings WHERE status = 'Active' AND sacco_id = '$office' AND product_type = 3");
		if (count($defaults) > 0) {
			return $this->db->SelectData("SELECT * FROM m_savings_product where product_status = 'Active' AND office_id = '".$office."' AND id = '".$defaults[0]['p_id']."'");
		} else {
			return NULL;
		}
    }

	function getMemberLoans($id){
		return $this->db->SelectData("SELECT * FROM m_loan l INNER JOIN  m_product_loan p WHERE l.product_id=p.id AND l.member_id='".$id."'");
	}

	function getMemberShares($id){
		return $this->db->SelectData("SELECT * FROM share_account s INNER JOIN  share_products p WHERE s.product_id=p.id AND s.member_id='".$id."'");
	}
	
	
	function uploadMembers($data){
	    
        	 try{
        	    	    
        			$postData = array();	
        			$str=date('isH').rand();
        			$office_id = $_SESSION['office'];

        			$acc_no= $office_id .substr($str,0,7);
        			$memebr_id= $this->MemberNo($office_id);
        			$path = 'public/images/avatar/'; // upload directory		
        			$valid_extensions = array('jpeg','jpg','png','pdf'); // valid extensions
        			$photosext = array('jpeg','JPEG','jpg','png','PNG'); // valid extensions
         			$user = $_SESSION['user_id'];
        			$member_type=$data['form'];
        		
        
        			$phonestatus = $this->checkmembertelephone(str_replace("+", "", $data['mphone']));
        
        
        			if ($_SESSION['Isheadoffice'] == 'No') {
        				$postData['branch_id'] = $_SESSION['branchid'];
        			}
        
        
        			$postData['firstname'] = $data['fname'];
        			$postData['gender'] = $data['gender'];
        			$postData['middlename'] = $data['mname'];
        			$postData['email'] = $data['email'];
        			$postData['watsappnumber'] =  "";
        			$postData['lastname'] = $data['lname'];
        			$postData['date_of_birth'] =date('Y-m-d',strtotime($data['dob']));
        			$postData['national_id'] = $data['nid'];
        			$postData['address'] = $data['address'];
        			$postData['mobile_no'] = str_replace("+", "", $data['mphone']); 
        			$postData['mobile_phone2'] = str_replace("+", "", $data['mphone']); 
        			$postData['mobile_money_number'] = str_replace("+", "", $data['mmphone']); 
        			$postData['legal_form'] =$data['form'];
        			$postData['submittedon_userid'] = $user; 
        			$postData['c_id'] =$memebr_id; 
        			$postData['office_id'] = $office_id; 
        			$postData['status']='Active';	
		            $postData['status_code']='5';
        			
        			/*
        			
        			if(empty($data['fname'])&&empty($data['mname'])&&empty($data['lname'])&&empty($data['company_name'])){			
        				$acc_no ='Null';
        				//	header('Location: ' . URL . 'members/newmember?member='.$memebr_id);  
        				 
        			}else{
        			
        			*/
        			    
        				$instance_id =$_SESSION['instance'];
        						$acc_no= $office_id .substr($str,0,7);

        		
        				
                        $rs = $this->CreateClicAccount($memebr_id, uniqid(), $data['fname']." ".$data['lname'], $postData['mobile_no'], $office_id, $instance_id,$data['email'], $postData['mobile_no'], $memebr_id);
                        
                        
        			
        				
        				$this->db->beginTransaction();
        				
        				$this->db->InsertData('members', $postData);
        			//	$this->db->commit();
        			
        				$userID = $this->getLastAddedID();
        
        				$sharesAccount = $savingsAccount = NULL;
        				
        				
        
        				// create share product
        			
        				$shareProduct = $this->getDefaultShareProduct();
        				$savingsProducts = $this->getDefaultSavingsProduct();
        	
        			
        				if (isset($shareProduct)) {
        				    
        					if (count($shareProduct) > 0) {	
        						$shares_product = $shareProduct[0]['id'];
        						$sharesAccount = $this->CreateShareApplication($userID, $shares_product);
        					}
        					
        				}
        				
        				if (isset($savingsProducts)) {
        				    
        					if (count($savingsProducts) > 0) {			
        						$savingsProduct = $savingsProducts[0]['id'];
        						$rs = $this->CreateSavingApplication($userID, $savingsProduct, $acc_no);
        						if($rs['status'] == 100){
        							$savingsAccount = $rs['response'];
        						}
        						
        					}
        				}
        				
        				
        
        				$new_wallet = $this->getDefaultWalletDetails(5);
        			
        			    if (!empty($new_wallet)) {
        	    			// CREATE A WALLET ACCOUNT
        	    			$wallet = $this->getClientWalletdDetails($userID);
        	    		
        	    			
        	    			if(count($wallet) == 0){
        	  
        	    				$client_details = $this->getClient($userID);
        	    				    $datas['wallet_account_number'] = $client_details[0]['mobile_no'];    
        	    					$datas['accountno'] = $savingsAccount;
        	    					$datas['bank_no'] = $_SESSION['office'];
        	    					$datas['member_id'] = $userID;
        	    					$wallet_id = $this->CreateWalletAccount($datas);
        	    					
        	    					
        	    				}
        	    			}
        	    			
        					$tran_id = $this->getTransactionID('Member Registration');
        					$transaction_charges = $this->getTransactionCharges($tran_id);
        					$exemptions = $this->getMemberChargeExemptions($userID);
        					$total_charge_amount = 0;
        
        					foreach ($transaction_charges as $key => $value) {
        						$prodType = 6;
        						if ((is_null($exemptions)) || (!in_array($value, $exemptions))) {
        							$mapping_charges = $this->GetGLChargePointers($value['id'], $prodType, 'Member Registration', $tran_id);
        
        							$debt_id = $mapping_charges[0]["debit_account"];	
        							$credit_id = $mapping_charges[0]["credit_account"];		
        
        							$sideA = $this->getAccountSide($debt_id);
        							$sideB = $this->getAccountSide($credit_id);	
        									  
        							$description = ucfirst($value['name']) . " Charge";	
        
        							$uniq_id =  $_SESSION['user_id'].uniqid();
        							$transaction_id =  "M".$uniq_id;
        
        							$amount = $value['amount'];
        
        							$this->makeJournalEntry($debt_id,$_SESSION['office'],$_SESSION['user_id'],$uniq_id,$transaction_id,$amount,'DR',$sideA,$description);//DR
        							$this->makeJournalEntry($credit_id,$_SESSION['office'],$_SESSION['user_id'],$uniq_id,$transaction_id,$amount,'CR',$sideB,$description);//CR						
        
        							$postTransCharges = array(
        								'transaction_id' => $transaction_id,
        								'charge_id' => $value['id'],
        								'trans_amount' => $amount,
        								'date' => date("Y-m-d H:i:s"),
        							);
        
        							$this->db->InsertData('m_charge_transaction', $postTransCharges);
        						}  
        					//}
        				
        			 
        			}
        			
        			$this->db->commit();
                    return $this->MakeJsonResponse(100,"Success", URL."clients/details/".$userID);
            }catch(Exception $e){
    			$this->db->rollBack();
    			$error=$e->getMessage(); 
            	return $this->MakeJsonResponse(203,$error, "#");
		    }
	}

	function NewMemeber(){
	    
    	try{
			$data =  $_POST;
			$postData = array();	
			$str=date('isH').rand();
			$office_id = $_SESSION['office'];
			  

			$acc_no= $office_id .substr($str,0,7);
			$memebr_id=$this->MemberNo($office_id);
			$path = 'public/images/avatar/'; // upload directory		
			$valid_extensions = array('jpeg','jpg','png','pdf'); // valid extensions
			$photosext = array('jpeg','JPEG','jpg','png','PNG'); // valid extensions

 			$user = $_SESSION['user_id'];
			$member_type=$data['form'];
		

			$phonestatus = $this->checkmembertelephone(str_replace("+", "", $data['mphone']));

		

			if ($_SESSION['Isheadoffice'] == 'No') {
				$postData['branch_id'] = $_SESSION['branchid'];
			}

			$postData['office_id'] = $office_id;
		    // ABC Test //
		    //This breaks if its a business being registered...
		    // so i added -> && !isset($data['company_name']
			    
			if(($data['fname'] == "" || $data['mphone'] == "") && !isset($data['company_name'])){
			    return $this->MakeJsonResponse(404,"enter all required fields", "#");
			}
			
			if ($phonestatus) {
			    return $this->MakeJsonResponse(404,"Phone number exists", "#");
			}
			
			
			if($member_type=='Personal'){
				$postData['firstname'] = $data['fname'];
				$postData['gender'] = $data['gender'];
				$postData['middlename'] = $data['mname'];
				$postData['email'] = $data['email'];
				$postData['watsappnumber'] =  "";
				$postData['lastname'] = $data['lname'];
				$postData['date_of_birth'] =date('Y-m-d',strtotime($data['dob']));
				$postData['national_id'] = $data['nid'];
				$postData['address'] = $data['address'];
				$postData['mobile_no'] = str_replace("+", "", $data['mphone']); 
				$postData['mobile_phone2'] = str_replace("+", "", $data['mphone']); 
				$postData['mobile_money_number'] = str_replace("+", "", $data['mmphone']); 
				
				$postData['clic_user_name'] = $data['clic_user_name'] == "" ? $memebr_id : $data['clic_user_name'];
				
				$postData['legal_form'] =$data['form']; 			 
			}else{
				$postData['company_name'] = $data['company_name'];
				$postData['incorporation_date'] = date('Y-m-d',strtotime($data['incdate']));
				//$postData['incorporation_expiry'] = date('Y-m-d',strtotime($data['incdate_end']));
				$postData['incorporation_no'] = $data['incno'];
				$postData['business_line'] = $data['businessline'];
				//$postData['address'] = $data['address1'];
				$postData['mobile_no'] = str_replace("+", "", $data['mphone']); 
				$postData['legal_form'] = 'Entity';			    
			    ////
				$postData['tradingas'] = $data['tradingas']; 
				$postData['tinnumber'] = $data['tinnumber']; 
				$postData['vatnumber'] = $data['vatnumber'];
				$postData['contact_person'] = $data['contact_person'];
				$postData['status_code'] = "4";//'5';
				$postData['status'] = "Pending Approval";//"Active";
			}
			
            //$postData['status']='Active';	
            //$postData['status_code']='5';
            $postData['status']="Pending Approval";
            $postData['status_code']= "4";
			$postData['submittedon_userid'] = $user; 
			$postData['c_id'] = $memebr_id; 
			
			
			if(empty($data['fname'])&&empty($data['mname'])&&empty($data['lname'])&&empty($data['company_name'])){			
				$acc_no ='Null';
				//	header('Location: ' . URL . 'members/newmember?member='.$memebr_id);  
				
			}else{
				$instance_id =$_SESSION['instance'];

                $user_name = $data['clic_user_name'] == "" ? $memebr_id : $data['clic_user_name'];
				
		        if (isset($postData['company_name'])) {
		            //$rs = $this->CreateClicAccount($postData['mobile_no'], uniqid(), $postData['company_name'], $postData['mobile_no'], $office_id, $instance_id, $data['email'], $memebr_id);
    				$rs = $this->CreateClicAccount($user_name, $postData['mobile_no'], 12345, $postData['company_name'], $acc_no, $office_id, $instance_id, $data['email'], $memebr_id);
		        } else {
				 	//$rs = $this->CreateClicAccount($memebr_id, $postData['mobile_no'],uniqid(), $data['fname']." ".$data['lname'], $acc_no, $office_id, $instance_id,$data['email'], $memebr_id);
				    $rs = $this->CreateClicAccount($user_name, $postData['mobile_no'], 12345, $data['fname']." ".$data['lname'], $acc_no, $office_id, $instance_id,$data['email'], $memebr_id);
				}
				$rt = json_decode($rs, true);
				if($rt['response']  != 1){
				    return $this->MakeJsonResponse(404,$rt['message'], "#");
				}
				// return $this->MakeJsonResponse(404,$rt['message'], "#");


 
                $this->db->beginTransaction();

 			    $this->db->InsertData('members', $postData);

				$userID = $this->getLastAddedID();

				$sharesAccount = $savingsAccount = NULL;

				// create share product
			
				$shareProduct = $this->getDefaultShareProduct();
				$savingsProducts = $this->getDefaultSavingsProduct();
                $new_wallet = $this->getDefaultWalletDetails(5);

					if (isset($shareProduct)) {
					if (count($shareProduct) > 0) {	
						$shares_product = $shareProduct[0]['id'];
						$sharesAccount = $this->CreateShareApplication($userID, $shares_product);
					}
				}
                $savingsAccount = "";
				if (isset($savingsProducts)) {
					if (count($savingsProducts) > 0) {			
						$savingsProduct = $savingsProducts[0]['id'];
                        $rs = $this->CreateSavingApplication($userID, $savingsProduct, $acc_no);

						
						if($rs['status'] == 100){
							$savingsAccount = $rs['response'];
						}
						
					}
				}

				

					if (!empty($new_wallet)) {
	    				// CREATE A WALLET ACCOUNT
	    				$wallet = $this->getClientWalletdDetails($userID);
	    				
	    				if(count($wallet) == 0){
	  						
	    					$client_details = $this->getClient($userID);
	    				    //$datas['wallet_account_number'] = $userID;//$client_details[0]['mobile_no'];   
	    				    
	    				    $datas['wallet_account_number'] = $data['clic_user_name'] == "" ? $userID : $data['clic_user_name'];
	    				    $datas['accountno'] = $savingsAccount;
	    					$datas['bank_no'] = $_SESSION['office'];
	    					$datas['member_id'] = $userID;
	    					

	    					$wallet_id = $this->CreateWalletAccount($datas);	
	    					
	    				}
	    			}
				
				
				

					$tran_id = $this->getTransactionID('Member Registration');
					$transaction_charges = $this->getTransactionCharges($tran_id);
					$exemptions = $this->getMemberChargeExemptions($userID);
					$total_charge_amount = 0;

					foreach ($transaction_charges as $key => $value) {
						$prodType = 6;
						if ((is_null($exemptions)) || (!in_array($value, $exemptions))) {
							$mapping_charges = $this->GetGLChargePointers($value['id'], $prodType, 'Member Registration', $tran_id);

							$debt_id = $mapping_charges[0]["debit_account"];	
							$credit_id = $mapping_charges[0]["credit_account"];		

							$sideA = $this->getAccountSide($debt_id);
							$sideB = $this->getAccountSide($credit_id);	
									  
							$description = ucfirst($value['name']) . " Charge";	

							$uniq_id =  $_SESSION['user_id'].uniqid();
							$transaction_id =  "M".$uniq_id;

							$amount = $value['amount'];

							$this->makeJournalEntry($debt_id,$_SESSION['office'],$_SESSION['user_id'],$uniq_id,$transaction_id,$amount,'DR',$sideA,$description);//DR
							$this->makeJournalEntry($credit_id,$_SESSION['office'],$_SESSION['user_id'],$uniq_id,$transaction_id,$amount,'CR',$sideB,$description);//CR						

							$postTransCharges = array(
								'transaction_id' => $transaction_id,
								'charge_id' => $value['id'],
								'trans_amount' => $amount,
								'date' => date("Y-m-d H:i:s"),
							);

							$this->db->InsertData('m_charge_transaction', $postTransCharges);
						}  
					}
				

				$this->db->commit();
				if(isset($data['company_name'])){
				    header('Location: ' . URL . 'clients/details/'.$userID.'?msg=success');
				} else {
				    return $this->MakeJsonResponse(100, "Success", URL."clients/details/".$userID);
				}
			}
			
    	}catch(Exception $e){
			$this->db->rollBack();
			$error=$e->getMessage();
			return $this->MakeJsonResponse(203,$error, "");


		}
	 
	}

function getLastAddedID(){
	$office = $_SESSION['office'];
	$id = 0;
	$ids =  $this->db->selectData("SELECT c_id from members where office_id = '" . $office . "'");

	foreach ($ids as $key => $value) {
		$id = $value['c_id'];
	}

	return $id;
}


function getMember($id){

   return $this->db->SelectData("SELECT * FROM members c INNER JOIN m_branch b where c.office_id  = b.id AND c.c_id='".$id."'  order by c.c_id desc");

}

function getProduct($id){

   return $this->db->SelectData("SELECT * FROM share_products where id='".$id."'");

}

function CreateShareApplication($id, $pdt){
	try{
	$mapping = $this->GetGLPointers($pdt,1,'Purchase Shares'); 

	if (empty($mapping)) {
	return $this->MakeJsonResponse(203,"pointers missing", "");
	}

	$name=null;
	$office = $_SESSION['office'];
	$str=date('isH').rand();		
	$acc_no= substr($office.$id.$pdt.substr($str,0,7),0,11);
	$client_details = $this->getMember($id);
	$share_product=$this->getProduct($pdt);
	$amount=str_replace(",","",$share_product[0]['amount_per_share']);
    	
    if(count($share_product)>0){
		$share_cost=$share_product[0]['amount_per_share'];
 
		$total_shares=($amount/$share_cost);

            $transaction_postData = array(
            	'share_account_no' => $acc_no,
             	'branch' => $office,
	            'no_of_shares' => $total_shares,			 
	            'amount' =>$amount,
	            //'amount_in_words' => $data['amount_in_words'],
	            'running_balance' =>$amount,
	            'recorded_by' =>$_SESSION['user_id'],
        	);
			$updated_on=date('Y-m-d H:i:s');
 
		$share_transaction_id = $this->db->InsertData('share_account_transaction', $transaction_postData);

		if(!empty($share_transaction_id)){
	   		$share_postData = array(
				 'office_id' => $_SESSION['office'],
	             'share_account_no' => $acc_no,
	             'member_id' => $id,
				 'submittedon_userid' => $_SESSION['user_id'],
	             'account_status' =>'Active',
	             'product_id' => $pdt,
	             'total_shares' =>$total_shares,
				 'running_balance' =>$amount,
				 'last_updated_on' => $updated_on,
			);

			$this->db->InsertData('share_account', $share_postData);

			$trans_uniqid=uniqid();
			$deposit_transaction_uniqid = $share_transaction_id."".$trans_uniqid;
			$prodType=1;
 
			$mapping = $this->GetGLPointers($pdt,$prodType,'Purchase Shares'); 
	
       		$transaction_id = "SH".$deposit_transaction_uniqid;

			$new_data['transaction_id'] = $transaction_id;
			$this->db->UpdateData('share_account_transaction', $new_data,"`share_trans_id` = '{$share_transaction_id}'");

 				$debt_id=$mapping[0]["debit_account"];	
				$credit_id=$mapping[0]["credit_account"];		
				$sideA=$this->getAccountSide($debt_id);
				$sideB=$this->getAccountSide($credit_id);
				if(empty($client_details[0]['company_name'])){
					$name=$client_details[0]['firstname']." ".$client_details[0]['middlename']." ".$client_details[0]['lastname']; 	
				}else{
					$name=$client_details[0]['company_name'];		
				}		
		
				$description="Shares Bought by ".$name;	

				$this->makeJournalEntry($debt_id,$office,$_SESSION['user_id'],$share_transaction_id,$transaction_id,$amount,'DR',$sideA,$description);
				$this->makeJournalEntry($credit_id,$office,$_SESSION['user_id'],$share_transaction_id,$transaction_id,$amount,'CR',$sideB,$description);
 			 
return $this->MakeJsonResponse(100,$acc_no, "");
		}

	}else{
  
  		return $this->MakeJsonResponse(203,"failed", "");
			
	}
	}catch(Exception $e){
 		return $this->MakeJsonResponse(203,$e->getMessage(), "");
		
	}
}

function CreateSavingApplication($id, $pdt_id,$acc_no){

	$mapping = $this->GetGLPointers($pdt_id, 3, 'Deposit on Savings'); 



		$client_details = $this->getMember($id);
		$office_id = $_SESSION['office'];
		$str=date('isH').rand();		

		$savings_pdt = $this->getSavingsProduct($pdt_id);
		if(empty($client_details[0]['company_name'])){
					$name = $client_details[0]['firstname']." ".$client_details[0]['middlename']." ".$client_details[0]['lastname']; 	
				}else{
					$name = $client_details[0]['company_name'];		
				}	
		try{
		

			$postData = array(
				'account_name' => $name,
				'account_no' => $acc_no,
				'member_id' => $id,
				'submittedon_userid' => $_SESSION['user_id'],
				'office_id' => $_SESSION['office'],
				'account_status' => 'Active',
				'product_id' => $pdt_id,
			);
		
			$this->db->InsertData('m_savings_account', $postData);
	

				

				$amount = $savings_pdt[0]['min_required_opening_balance'];
				if($amount<=0){
					return $this->MakeJsonResponse(100,$acc_no, "");
				}
	if (empty($mapping)) {
	return $this->MakeJsonResponse(404, "no ponters", "");
	}
	

				$transaction_id = "S".uniqid();
				$transaction_postData = array(
					'savings_account_no' => $acc_no,
					'payment_detail_id' =>  'CASH',
					'amount' => $amount,
					'running_balance' => $amount,
					'depositor_name' => $name,
					'amount_in_words' => $this->convertNumber($amount),
					'telephone_no' => $client_details[0]['mobile_no'],
					'branch' => $_SESSION['office'],
					'transaction_type' => 'Deposit',
					'transaction_id' => $transaction_id,
					'user_id' => $_SESSION['user_id'],
				);

				$dep_transaction_id = $this->db->InsertData('m_savings_account_transaction', $transaction_postData);

				$depositstatus = array(
					'total_deposits' => $amount,
					'running_balance' => $amount,
					'last_updated_on' => date("Y-m-d H:i:s"),
				);

				$this->db->UpdateData('m_savings_account', $depositstatus,"`account_no` = '{$acc_no}'");
 
		
				$debt_id =$mapping[0]["debit_account"]; //debit savings control account
				$credit_id =$mapping[0]["credit_account"]; //credit cash savings reference	
		
				$sideA=$this->getAccountSide($debt_id);
				$sideB=$this->getAccountSide($credit_id);

				$description="Savings Deposit for ".$name;

				$this->makeSavingsJournalEntry($debt_id,$_SESSION['office'],$_SESSION['user_id'],$dep_transaction_id,$transaction_id,$amount,'DR',$sideA,$description);//DR
				$this->makeSavingsJournalEntry($credit_id,$_SESSION['office'],$_SESSION['user_id'],$dep_transaction_id,$transaction_id,$amount,'CR',$sideB,$description);//CR
				
				return $this->MakeJsonResponse(100,$acc_no, "");
			

		}catch(Exception $e){
	return $this->MakeJsonResponse(203,$e->getMessage(), "");
		}	

		
	


}
function updateMember($id){
	$data = $_POST;
	$user = $_SESSION['user_id'];

	$entity=$data['form'];
	$postData = array();
	$postData['office_id'] = $data['office'];
	if($entity=='Entity'){	
		$postData['company_name'] = $data['company_name'];
		$postData['incorporation_date'] = date('Y-m-d',strtotime($data['incdate']));
		$postData['incorporation_expiry'] = date('Y-m-d',strtotime($data['incdate_end']));
		$postData['incorporation_no'] = $data['incno'];
		$postData['business_line'] = $data['businessline'];
		$postData['address'] = $data['address2'];
		$postData['mobile_no'] = $data['mphone2']; 
		$postData['legal_form'] = 'Entity'; 
	}else{
		$postData['firstname'] = $data['fname'];
		$postData['gender'] = $data['gender'];
		$postData['middlename'] = $data['mname'];
		$postData['lastname'] = $data['lname'];
		//$postData['fullname'] = $data['fname']."  ".$data['mname']."  ".$data['lname'];
		$postData['date_of_birth'] =date('Y-m-d',strtotime($data['dob']));
		$postData['national_id'] = $data['nid'];
		$postData['address'] = $data['address'];
		$postData['mobile_no'] = $data['mphone']; 
		$postData['legal_form'] ='Personal'; 			
	}	
	$postData['submittedon_userid'] = $user; 

	
	
	
	if(empty($data['fname'])&&empty($data['mname'])&&empty($data['lname'])&&empty($data['company_name'])){
		
		header('Location: ' . URL . 'members/editmember/'.$id.'?msg= Not Updated'); 
		
	}else{
		
		$this->db->UpdateData('members',$postData, "`c_id` = {$id}");
		

		header('Location: ' . URL . 'members/details/'.$id.'?msg= Updated');  

	}

}


function uploaddocument(){

	$valid_extensions = array('jpeg','jpg','png','pdf'); // valid extensions
	$photosext = array('jpeg','JPEG','jpg','png','PNG'); // valid extensions
	
	if(isset($_FILES['docs'])){
		$final_image=null;
		$img = $_FILES['docs']['name'];
		$tmp = $_FILES['docs']['tmp_name'];
		$doc = $_POST['document'];
		$id = $_POST['members'];	

		$status_code=$this->getClientDetails($id);

		// get uploaded file's extension
		$ext = strtolower(pathinfo($img,PATHINFO_EXTENSION));
		// can upload same image using rand function
		if($doc==1){
			$final_image = "signature_".$id;		
		}else if($doc==2){
			$final_image = "id_passport_".$id;		
		}else if($doc==3){
			$final_image = "photo_".$id;		
		}

		$path = 'public/images/avatar/'; // upload directory

		// check's valid format

		$postData=array();

		if(in_array($ext, $valid_extensions))  {

			$path = $path.strtolower($final_image);	

			$data = file_get_contents($tmp);
			$base64 = 'data:image/jpeg;base64,' . base64_encode($data);

			$code=$status_code[0]['status_code'];
			
			if($doc==1){

				$imageData['member_id'] = $id;
				$imageData['type'] = 'signature';
				$imageData['image'] = $base64;

				if ($_POST['type'] == 'yes') {
					$postData['status']='Pending Approval';	
					$postData['status_code']='4';
				}else{

					if($code==0){
						$postData['status']='Missing Photo & ID';	
						$postData['status_code']='23';	
					}else if($code==13){
						$postData['status']='Missing Photo';	
						$postData['status_code']='3';		
					}else if($code==12){
						$postData['status']='Missing ID';	
						$postData['status_code']='2';	
					}else if($code==1){
						$postData['status']='Pending Approval';	
						$postData['status_code']='4';	
					}
				}

			}else if($doc==2){

				$imageData['member_id'] = $id;
				$imageData['type'] = 'id_passport';
				$imageData['image'] = $base64;
				
				if ($_POST['type'] == 'yes') {
					$postData['status']='Pending Approval';	
					$postData['status_code']='4';
				}else{

					if($code==0){
						$postData['status']='Missing Photo & Signature';	
						$postData['status_code']='13';	
					}else if($code==12){
						$postData['status']='Missing Signature';	
						$postData['status_code']='1';		
					}else if($code==23){
						$postData['status']='Missing Photo';	
						$postData['status_code']='3';	
					}else if($code==2){
						$postData['status']='Pending Approval';	
						$postData['status_code']='4';	
					}	
				}		

			}else if($doc==3){
				
				if(in_array($ext, $photosext))  {

					$imageData['member_id'] = $id;
					$imageData['type'] = 'image';
					$imageData['image'] = $base64;				
					
					if ($_POST['type'] == 'yes') {
						$postData['status']='Pending Approval';	
						$postData['status_code']='4';
					}else{

						if($code==0){
							$postData['status']='Missing Signature & ID';	
							$postData['status_code']='12';	
						}else if($code==13){
							$postData['status']='Missing Signature';	
							$postData['status_code']='1';		
						}else if($code==23){
							$postData['status']='Missing ID';	
							$postData['status_code']='2';	
						}else if($code==3){
							$postData['status']='Pending Approval';	
							$postData['status_code']='4';	
						}
					}
				}else{
					header('Location: ' . URL . 'members/docsform/'.$id.'?msg=invalid');  
					die();		
				}
			}

			$this->db->UpdateData('members',$postData, "`c_id` = {$id}");
			$this->db->InsertData('m_pictures', $imageData);

			if ($_POST['type'] == 'yes') {
				header('Location: ' . URL . 'clients/details/'.$id.'?msg=success');
			}else{
				header('Location: ' . URL . 'members/statusone/'.$id.'?msg=success'); 
			} 

		}

	}

}

function ApproveMember($id){

	$status = $this->getClientDetails($id);
	
	if($status[0]['status']=='Pending Approval'){

		$postData['status']='Active';	
		$postData['status_code']='5';	

		$this->db->UpdateData('members',$postData, "`c_id` = {$id}");

		header('Location: ' . URL . 'members/details/'.$id.'?msg=success');  	
	}else{
		
		header('Location: ' . URL . 'members/statusone/'.$id.'?activation=rejected');  	
	}


}

function ActivateMember($id){
	$status=$this->getClientDetails($id);
	$postData['status']='Active';
	$postData['status_code']='5';
	$this->db->UpdateData('members',$postData, "`c_id` = {$id}");
	header('Location: ' . URL . 'members/details/'.$id.'?msg=success');  	

}

function ResetPin($id){
	$postData['password']='94e2609970b5127ce5495f37e5f60d137c641024083de0e42427d0b0cde61dd4';	
	$this->db->UpdateData('members',$postData, "`c_id` = {$id}");
	header('Location: ' . URL . 'clients/details/'.$id.'?msg=reset');  	

}

function ResetDevice($id){
	$postData['device_token'] = '';	
	$this->db->UpdateData('members',$postData, "`c_id` = {$id}");
	header('Location: ' . URL . 'clients/details/'.$id.'?msg=device'); 
}

function DeleteMember($id){
	$status=$this->getClientDetails($id);
	$postData['status']='Closed';
	$this->db->UpdateData('members',$postData, "`c_id` = {$id}");
	header('Location: ' . URL . 'clients/details/'.$id.'?msg=deleted'); 
}


function addsavingsAccount($acc_no,$data,$last){

	$postData = array(
		'account_no' => $acc_no,
		'member_id' =>$last,
		'product_id' =>$data['product_id'],
		'submittedon_userid' => $_SESSION['user_id'],
		'office_id' => $_SESSION['office'],
		);

	$this->db->InsertData('m_savings_account', $postData);	
}

function getAccountNo($cid){
	$result= $this->db->selectData("SELECT min(account_no) as account FROM m_savings_account WHERE member_id='".$cid."' ");
	return $result[0]['account'];

}
function getAccountName($id){

	$result= $this->db->selectData("SELECT member_id FROM m_savings_account WHERE account_no='".$id."' and account_status='Active' || account_status='Open' ");
	if(count($result)>0){
		$cid=$result[0]['member_id'];
		$results= $this->db->selectData("SELECT * FROM members WHERE c_id='".$cid."' ");
		echo $results[0]['firstname']." ".$results[0]['middlename']." ".$results[0]['lastname'] ;
		die();
		
	}
}


function getShareAccountName($id){

	$result= $this->db->selectData("SELECT member_id FROM share_account WHERE share_account_no='".$id."' and account_status='Active' || account_status='Open' ");
	if(count($result)>0){
		$cid=$result[0]['member_id'];
		$results= $this->db->selectData("SELECT * FROM members WHERE c_id='".$cid."' ");
		echo $results[0]['firstname']." ".$results[0]['middlename']." ".$results[0]['lastname'] ;
		die();
		
	}
}


function getClosedAccountName($id){

	$result= $this->db->selectData("SELECT member_id FROM m_savings_account WHERE account_no='".$id."' and account_status='Closed'");
	if(count($result)>0){
		$cid=$result[0]['member_id'];
		$results= $this->db->selectData("SELECT * FROM members WHERE c_id='".$cid."' ");
		echo $results[0]['firstname']." ".$results[0]['middlename']." ".$results[0]['lastname'] ;
		die();
		
	}
}

function getAccountDetails($id){

	$result= $this->db->selectData("SELECT * FROM m_savings_account WHERE account_no='".$id."'");
	if(count($result)>0){
		if ($result[0]['account_status'] == "Closed") {
			echo 'Closed';
			die();
		} else {
			$cid=$result[0]['member_id'];
			$results= $this->db->selectData("SELECT * FROM members WHERE c_id='".$cid."' ");
			echo $results[0]['firstname']." ".$results[0]['middlename']." ".$results[0]['lastname'] ;
			die();
		}
	}
}

function checkifclosed($id){

	$result= $this->db->selectData("SELECT member_id FROM m_savings_account WHERE account_no='".$id."' and account_status='Closed'");
	if(count($result)>0){
		$cid=$result[0]['member_id'];
		$results= $this->db->selectData("SELECT * FROM members WHERE c_id='".$cid."' ");
		echo $results[0]['firstname']." ".$results[0]['middlename']." ".$results[0]['lastname'] ;
		die();
		
	}}

function getWalletAccountName($id){

	$result= $this->db->selectData("SELECT member_id FROM sm_mobile_wallet WHERE wallet_account_number='".$id."' and wallet_status='Active'  ");
	if(count($result)>0){
		$cid=$result[0]['member_id'];
		$results= $this->db->selectData("SELECT * FROM members WHERE c_id='".$cid."' ");
		if (!empty($results[0]['company_name'])) {
			echo $results[0]['company_name'];
		} else {
			echo $results[0]['firstname']." ".$results[0]['middlename']." ".$results[0]['lastname'];
		}
		die();
		
	}
}

function getMemberNameABC($id){
	$result= $this->db->selectData("SELECT * FROM members WHERE c_id='".$id."' ");
	if(!empty($result[0]['company_name'])){
		$name = $result[0]['company_name'];
	}else{
		$name = $result[0]['firstname']." ".$result[0]['middlename']." ".$result[0]['lastname'] ;
	}
	echo $name;
}


function getMemberName($id){
	$result= $this->db->selectData("SELECT * FROM members WHERE c_id='".$id."' ");
	if(!empty($result[0]['company_name'])){
		$name = $result[0]['company_name'];
	}else{
		$name = $result[0]['firstname']." ".$result[0]['middlename']." ".$result[0]['lastname'] ;
	}
	return $name;
}

function getAccountfromSavings($id){
	$result= $this->db->selectData("SELECT * FROM m_savings_account WHERE id='".$id."' ");
	return $result;

}


function MembersList(){

	$office=$_SESSION['office'];
	$query= $this->db->SelectData("SELECT * FROM members c JOIN m_branch b where c.office_id =b.id and c.office_id='".$office."' order by c.c_id desc");

	$count=count($query);
	if($count>0){
		foreach ($query as $key => $value) {		

			$account=$this->getAccountNo($query[$key]['c_id']);	
			//-----bob --------
			if(empty($query[$key]['company_name'])){
				$rset[$key]['name'] =$query[$key]['firstname']." ".$query[$key]['middlename']." ".$query[$key]['lastname']; 
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


function getsavingsProductcharge($savings_product_id) {	
	$query= $this->db->SelectData("SELECT * FROM m_savings_product_charge sc JOIN m_charge mc where sc.charge_id =mc.id  and sc.savings_product_id ='".$savings_product_id."'  and mc.charge_applies_to =2 order by sc.charge_id desc");	
	return $query;
}

function getsavingsProductcharge_application() {	
	$query = $this->db->SelectData("SELECT * FROM m_savings_product_charge sc JOIN m_charge mc where sc.charge_id =mc.id  and mc.charge_applies_to =2 order by sc.charge_id desc");
	return $query;
}

function getSavingsProducts(){
	return $this->db->SelectData("SELECT * FROM m_savings_product where product_status ='Active' ");
}


function getSavingsProduct($id){
	return $this->db->SelectData("SELECT * FROM m_savings_product WHERE id='".$id."'");
}

function getSavings_Product($id){
	return $this->db->SelectData("SELECT * FROM m_savings_product WHERE id='".$id."'");
}

function getFixedDepositProducts($id){
	
	$client_pdt= $this->db->SelectData("SELECT DISTINCT(product_id) AS product_id FROM  fixed_deposit_account where member_id='".$id."' and account_status!='Activate' and account_status!='Domant'  ");

	$no =count($client_pdt);
	$add=null;
	if($no>0){
		
		for($i=0;$i<$no;$i++){
			$add .= " AND id!='".$client_pdt[$i]["product_id"]."'";		
		}

		$office = $_SESSION['office'];

		$pdts= $this->db->SelectData("SELECT * FROM fixed_deposit_product where office_id = $office and product_status='Active' $add");
		if(count($pdts)>0){
			$option = '<option value="">---Select Product---</option>';
			foreach ($pdts as $key => $value) {
				$option .= '<option value="' . $value['id'] . '">' . $value['name'] . '</option>';
			}
			print_r($option);
			die();
		}else{
			
		}

	}else{

		$office = $_SESSION['office'];
		$pdts= $this->db->SelectData("SELECT * FROM fixed_deposit_product where office_id = $office and product_status='Active'");
		
		if(count($pdts)>0){
			$option = '<option value="">---Select Product---</option>';
			foreach ($pdts as $key => $value) {
				$option .= '<option value="' . $value['id'] . '">' . $value['name'] . '</option>';
			}
			print_r($option);
			die();
		}else{
			
		}		
	}

}

	function getFixedDepositapplied($id){

		$result=  $this->db->selectData("SELECT * FROM  fixed_deposit_product WHERE id='".$id."'");
		if(count($result)>0){

			$rset=array();
			foreach ($result as $key => $value) {
				array_push($rset,array(
					'id'=>$result[$key]['id'],
					'description'=>$result[$key]['description'],
					'interest_calculation'=>$result[$key]['interest_calculation_method'],
					'interest_posting'=>$result[$key]['interest_posting_period'],
					'days'=>$result[0]['days_in_year'],
					'minimum_amount'=>$result[0]['minimum_deposit_amount'],
					'maximum_amount'=>$result[0]['maximum_deposit_amount'],	
					'minimum_term'=>$result[0]['minimum_deposit_term'],
					'minimum_value'=>$result[0]['minimum_term_value'],	
					'maximum_term'=>$result[0]['maximum_deposit_term'],
					'maximum_value'=>$result[0]['maximum_term_value'],
					));
			}

			echo json_encode(array("result" =>$rset));
			die();
		}else{
			$rset=array();
			array_push($rset,array(
				'id'=>'0',
				));
			echo json_encode(array("result" =>$rset));		
			die();
		}
		


	}
	function getSavingsProducttoapply($id){

		$client_pdt= $this->db->SelectData("SELECT DISTINCT(product_id) AS product_id FROM  m_savings_account where member_id='".$id."' and account_status!='Activate' and account_status!='Domant' ");

		$no =count($client_pdt);

		$add=null;
		if($no>0){
			
			for($i=0;$i<$no;$i++){
				$add .= " AND id!='".$client_pdt[$i]["product_id"]."'";		
			}

			$office_id = $_SESSION['office'];

			$pdts= $this->db->SelectData("SELECT * FROM m_savings_product where office_id = $office_id and product_status='Active' $add");
			if(count($pdts)>0){
				$option = '<option value="">---Select Product---</option>';
				foreach ($pdts as $key => $value) {
					$option .= '<option value="' . $value['id'] . '">' . $value['name'] . '</option>';
				}
				print_r($option);
				die();
			}else{
				
			}

		}else{

			$office_id = $_SESSION['office'];
			$pdts= $this->db->SelectData("SELECT * FROM m_savings_product where office_id = $office_id and product_status='Active'");
			
			if(count($pdts)>0){
				$option = '<option value="">---Select Product---</option>';
				foreach ($pdts as $key => $value) {
					$option .= '<option value="' . $value['id'] . '">' . $value['name'] . '</option>';
				}
				print_r($option);
				die();
			}else{
				
			}
		}

	}


		function getClientDetails($id){
			return $this->db->SelectData("SELECT * FROM members c INNER JOIN m_branch b where c.office_id  = b.id AND c.c_id='".$id."'  order by c.c_id desc");
		}

		function member_infom($id){

			$result = $this->db->SelectData("SELECT * FROM members c INNER JOIN m_branch b where c.office_id  = b.id AND c.c_id='".$id."'  order by c.c_id desc");
			$displayname=null;
			if(count($result)>0){
				if(!empty($result[0]['company_name'])){
					$displayname=$result[0]['company_name'];	 
				}else{
					$displayname=$result[0]['firstname']." ".$result[0]['middlename']." ".$result[0]['lastname'];
				}
				
				$rset=array();
				foreach ($result as $key => $value) {
					array_push($rset,array(
						'member_id'=>$result[$key]['c_id'],
						'displayname'=>$displayname,
						'dob'=>$result[0]['date_of_birth'],
						'national_id'=>$result[0]['national_id'],
						'address'=>$result[0]['address'],
						
						));
				}
				print_r( json_encode(array("result" =>$rset)));
				die();
			}else{
				echo "No results";
				die();
			}
			
		}

		function getClientAge($id){
			return $this->db->SelectData("SELECT TIMESTAMPDIFF(YEAR,date_of_birth,CURDATE()) as age FROM members where c_id='".$id."' ");
		}

		function getEmployees(){
			return $this->db->SelectData("SELECT * FROM m_staff");
		}


		function getClientSaving($id){
			return $this->db->SelectData("SELECT * FROM m_savings_account where member_id='".$id."' ");
		}



		function getClientSavingsdDetails($id){
			return $this->db->SelectData("SELECT p.name,member_id, running_balance as amount, s.id ,account_no FROM m_savings_product p INNER JOIN m_savings_account s where s.product_id = p.id and  s.member_id='".$id."' order by s.id DESC");

		}

		function getClientWalletdDetails($id){
			return $this->db->SelectData("SELECT * from sm_mobile_wallet where member_id = '$id'");
		}



		function ClientSavingsdDetailsSearch($id){

			$result = $this->db->SelectData("SELECT p.name,member_id, running_balance as amount, s.id ,account_no FROM m_savings_product p INNER JOIN m_savings_account s where s.product_id = p.id and  s.member_id='".$id."' order by s.id DESC");

			if(count($result)>0){
				$rset=array();
				foreach ($result as $key => $value) {
					array_push($rset,array(
						'savings_account'=>$result[$key]['account_no'],
						'savings_name'=>$result[$key]['name'],
						));
				}
				echo json_encode(array("result" =>$rset));
				die();
			}
		}


		function transactiondetails($id){
			return $this->db->SelectData("SELECT * FROM m_savings_account_transaction where savings_account_id='".$id."' ");
		}

		function getCharges($id){
			$office = $_SESSION['office'];

			return $this->db->selectData("SELECT * FROM m_charge where office_id = '". $office ."' AND charge_applies_to = '".$id."' AND transaction_type_id != '39' AND status = 'Active' AND is_deleted != 1 order by id ");

		}


		function UpdateClient($data){

			$postData = array();

			if(!empty($data['members'])){

				$id=$data['members'];

				if(!empty($data['office'])){
					$postData['office_id']=$data['office'];
				}

				if(!empty($data['form'])){
					$postData['legal_form']=$data['form'];
				} 

				if(!empty($data['fname'])){
					$postData['firstname']=$data['fname'];
				}

				if(!empty($data['mname'])){
					$postData['middlename']=$data['mname'];
				}

				if(!empty($data['lname'])){
					$postData['lastname']=$data['lname'];
				}

				if(!empty($data['dob'])){
					$postData['date_of_birth']=date('Y-m-d',strtotime($data['dob']));
				}

				if(!empty($data['gender'])){
					$postData['gender_cv_id']=$data['gender'];
				}

				if(!empty($data['type'])){
					$postData['client_type_cv_id']=$data['type'];
				} 

				if(!empty($data['staffname'])){
					$postData['staff_id']=$data['staffname'];
				}

				if(!empty($data['mphone'])){
					$postData['mobile_no']=$data['mphone'];
				}

				if(!empty($data['vdate'])){
					$postData['closedon_date']=$data['vdate'];
				} 

				if(!empty($data['classification_enum'])){
					$postData['client_classification_enum_cv_id']=$data['classification_enum'];
				}

				if(!empty($data['cdate'])){
					$postData['closedon_date']=$data['closedon_date'];
				}

				if(!empty($data['adate'])){
					$postData['activation_date']=$data['adate'];
				} 

				if(!empty($data['account'])){
					$postData['default_savings_account']=$data['account'];
				}

				$this->db->UpdateData('members',$postData, "`c_id` = {$id}");
				header('Location: ' . URL . 'members?msg=success');

			}else{
				print_r("failed to update ");
				die();
			}

		}


		function grouplist(){

			return $this->db->SelectData("SELECT * FROM m_group where level_id='2' and status = '1'");

		}

		function getstaff($officeid=null){

			if($officeid==null){

				return ;	  

			}else{

				$result=  $this->db->selectData("SELECT * FROM m_staff WHERE office_id='".$officeid."'");

				$opt='<option value="">Select staff</option>';


				foreach ($result as $key =>$values){
					

					$opt .= '<option value="'.$values['id'].'">'. $values['firstname']."  ".$values['lastname'].' </option>';

				} 
				
				return	$opt;

			}

		}

		
		function submitapplication($data){

			$client_details = $this->getClient($data['cid']);
			$office_id = $_SESSION['office'];
			$str=date('isH').rand();		
			$acc_no= $office_id .substr($str,0,7);

			///transactions tracking
			$this->db->beginTransaction();
			try{

				$postData = array(
					'account_no' => $acc_no,
					'member_id' => $data['cid'],
					'submittedon_userid' => $_SESSION['user_id'],
					'office_id' => $_SESSION['office'],
					'account_status' =>'Active',
					'product_id' => $data['product_id'],
					);
				
				$clientsaving_id= $this->db->InsertData('m_savings_account', $postData);
				
				 // CREATING A WALLET
				$wallet = $this->getClientWalletdDetails($data['cid']);
				
				if(count($wallet) == 0){
			    $datas['wallet_account_number'] = $client_details[0]['mobile_no'];    
				$datas['accountno'] = $acc_no;
				$datas['bank_no'] = $office_id;
				$datas['member_id'] =$data['cid'];
				$this->CreateWalletAccount($datas);	
				}
		
				$this->db->commit();
				header('Location: ' . URL . 'members/newsavingapplication?acc='.$acc_no); 

			}catch(Exception $e){
				$this->db->rollBack();
				$error=$e->getMessage();
				header('Location: ' . URL . 'members/newsavingapplication?acc=fail&error='.$error); 	  
				exit(); 	  
			}	


		}


	function MakeWalletJounalEntry($acc_id,$office,$user,$wallet_transaction_id,$trans_id,$amount,$type,$side,$description){
			$postData = array(
				'account_id' =>$acc_id,
				'office_id' => $office,
				'branch_id' => $_SESSION['branchid'],
				'createdby_id' =>$user,
				'wallet_transaction_id' =>$wallet_transaction_id,
				'transaction_id' =>$trans_id,
				'amount' => $amount,
				'transaction_type' =>$type,
				'trial_balance_side' =>$side,							
				'description' =>$description,							
				);

			$this->logUserActivity($trans_id);
			$this->db->InsertData('acc_gl_journal_entry', $postData);	
			
		}
		
		function makeSavingsJournalEntry($acc_id,$office, $branch_id, $user,$savings_trans_id,$trans_id,$amount,$type,$side,$description){
			$postData = array(
				'account_id' =>$acc_id,
				'office_id' => $office,
				'branch_id' => $branch_id,
				'createdby_id' =>$user,
				'savings_transaction_id' =>$savings_trans_id,
				'transaction_id' =>$trans_id,
				'amount' => $amount,
				'transaction_type' =>$type,
				'trial_balance_side' =>$side,							
				'description' =>$description,							
				);

			$this->logUserActivity($trans_id);
			$this->db->InsertData('acc_gl_journal_entry', $postData);	
			
		}
		
		function submitfixeddepositApplication($data){

			$prodType=4;
			$mapping = $this->GetGLPointers($data['product_id'],$prodType,'Deposit on fixed Deposit');

			if (empty($mapping)) {
				header('Location: ' . URL . 'products/fixeddepositproducts?msg=fixdep'); 
				die();
			}

			$this->db->beginTransaction();

			$office = $_SESSION['office'];
			$str=date('isH').rand();	
			$update_time=date('Y-m-d H:i:s');	
			$acc_no= $office.$data['cid'].substr($str,0,2);
			
			$today = date("Y-m-d");
			$term_value=$data['term_value'];
			$term=$data['term'];
			$maturity_date=null;
			if($term_value=='Years'){
				$maturity_date = date('Y-m-d', strtotime('+'.$term.' years', strtotime($today)));
			}else{
				$maturity_date = date('Y-m-d', strtotime('+'.$term.' months', strtotime($today)));
			}
			try{

				$client_details = $this->getClient($data['cid']);
				if(empty($client_details[0]['company_name'])){
					$name=$client_details[0]['firstname']." ".$client_details[0]['middlename']." ".$client_details[0]['lastname'];	
				}else{
					$name=$client_details[0]['company_name'];	
				}
				$amount=str_replace(",","",$data['amount']);
				$transaction_postData = array(
					'fixed_account_no' => $acc_no,
					'branch' => $office,
					'amount' =>$amount,
					'amount_in_words' => $data['amount_in_words'],
					'depositor_name' =>$name,
					'telephone_no' => $client_details[0]['mobile_no'],
					'running_balance' =>$amount,
					'user_id' =>$_SESSION['user_id'],
					'approved_by' =>$_SESSION['user_id'],

					);


				$fixed_transaction_id = $this->db->InsertData('fixed_deposit_transactions', $transaction_postData);

				$m_fixed_postData = array(
					'office_id' => $_SESSION['office'],
					'account_no' => $acc_no,
					'member_id' => $data['cid'],
					'submittedon_userid' => $_SESSION['user_id'],
					'product_id' => $data['product_id'],
					'amount_fixed' =>$amount,
					'running_balance' =>$amount,
					'interest_rate' => $data['interest_rate'],
					'term' => $data['term'],
					'maturity_date' =>$maturity_date,
					'term_value' => $data['term_value'],
					'last_updated_on' =>$update_time,

					);  	
				$this->db->InsertData('fixed_deposit_account', $m_fixed_postData);

				$trans_uniqid=uniqid();

				$deposit_transaction_uniqid = $fixed_transaction_id."".$trans_uniqid;
				
				$prodType=4;
				$mapping = $this->GetGLPointers($data['product_id'],$prodType,'Deposit on fixed Deposit');

				$transaction_id = "FD".$deposit_transaction_uniqid;
				if(!empty($mapping[0]["debit_account"])&&!empty($mapping[0]["credit_account"])){

					$debt_id =$mapping[0]["debit_account"]; //debit fixed  Control account
					$credit_id =$mapping[0]["credit_account"]; //credit cash fixed reference	
					$sideA=$this->getAccountSide($debt_id);
					$sideB=$this->getAccountSide($credit_id);
					$description="Fixed Deposit for ".$name;

					$new_data['transaction_id'] = $transaction_id;
					$this->db->UpdateData('fixed_deposit_transactions', $new_data,"`id` = '{$fixed_transaction_id}'");

					$this->makeFixedJournalEntry($debt_id,$office,$_SESSION['user_id'],$fixed_transaction_id,$transaction_id,$amount,'DR',$sideA,$description);//DR
					$this->makeFixedJournalEntry($credit_id,$office,$_SESSION['user_id'],$fixed_transaction_id,$transaction_id,$amount,'CR',$sideB,$description);//CR
				}

				$this->db->commit();	
			}catch(Exception $e){
				$this->db->rollBack();
				$error=$e->getMessage();

				header('Location: ' . URL . 'members/newfixeddepositApllication?acc=failed'); 	  
				exit(); 	  

			}
			header('Location: ' . URL . 'members/newfixeddepositApllication/?acc='.$acc_no.''); 


		}


		function updatesavingsApplication($data){
			$acc = $data['account_no'];
			$m_savings_postData = array(
				'product_id' =>$data['product_id'],
			);

			$this->db->UpdateData('m_savings_account', $m_savings_postData,"`account_no` = '{$acc}'");
			header('Location: ' . URL . 'members/modifysavings/?acc'.$acc.'&msg=updated'); 
		}

		function updatefixedDepositApplication($data){
			$acc = $data['account_no'];
			$fixed_postData = array(
				'product_id' =>$data['product_id'],
				'interest_rate' =>$data['interest_rate'],
				'term' =>$data['term'],
				'term_value' =>$data['term_value'],
				);

			$this->db->UpdateData('fixed_deposit_account', $fixed_postData,"`account_no` = '{$acc}'");
			header('Location: ' . URL . 'members/modifyfixeddeposit/?acc='.$acc.'&msg=updated'); 
		}

		function getClientAccount($id){
			return $this->db->SelectData("SELECT * FROM m_savings_account where account_no='".$id."'");
		}

		function getClientSaveddetails($acc){
			return $this->db->SelectData("SELECT * FROM m_savings_account s INNER JOIN  members c WHERE s.member_id=c.c_id AND s.account_no='".$acc."'");
		}

		function getClientWalletSaveddetails($acc){
			return $this->db->SelectData("SELECT * FROM sm_mobile_wallet s INNER JOIN  members c WHERE s.member_id=c.c_id AND s.wallet_account_number='".$acc."'");
		}

		function getClientSaveddetailsid($id){
			return $this->db->SelectData("SELECT * FROM m_savings_account where id='".$id."' ");
		}

		function depositOnWalletaccount($data){

			$this->db->beginTransaction();//beginning transaction
			$update_time=date('Y-m-d H:i:s');
			$acc=$data['accountno'];
			$result= $this->db->selectData("SELECT * FROM sm_mobile_wallet WHERE wallet_account_number='".$acc."' ");

			$amount = str_replace(",","",$data['amount']);
			$balance = $result[0]['wallet_balance'];

			$new_balance = $amount + $balance ;
			$office_id =  $_SESSION['office'];

			$product_id = 0;
			$prodType = 5;
			$mapping = $this->GetGLPointers($product_id,$prodType,'Wallet Cash Deposit');
                file_put_contents('log.txt',serialize($mapping));
			if (empty($mapping)) {
				header('Location: ' . URL . 'products/addglpointersWallet/0?msg=wcd'); 
				die();
			}

			//Add money to teller/users cash account
			if (isset($data['amount'])) {
				$cashdata = array(
					'account_balance' => $this->getUserCashBalance() + $data['amount'],
				);
				$this->db->UpdateData('m_staff', $cashdata,"`id` = '{$_SESSION['user_id']}'"); 
			}

			if(!empty($mapping[0]["debit_account"])&&!empty($mapping[0]["credit_account"])){

				try{
					
					$data['amount_in_words']=$this->convertNumber($amount);
					$data['wallet_balance']=$new_balance;
					$data['amount']=$amount;
					$data['transaction_type']='Cash Deposit'; 
					$data['description']='From : Cash Deposit';
					$data['wallet_account_number'] = $acc;
  
					$transaction_id = "W".uniqid();
					$data['transaction_id']=$transaction_id;
					
					$wallet_transaction_id = $this->logWalletTransaction($data);

					$debt_id =$mapping[0]["debit_account"]; //debit savings  Control account
					$credit_id =$mapping[0]["credit_account"]; //credit cash savings reference	
					$sideA=$this->getAccountSide($debt_id);
					$sideB=$this->getAccountSide($credit_id);
			
					///JOURNAL ENTRY POSTINGS
					$client = $this->getClientWalletSaveddetails($acc);
					$name=null;
					if(empty($client[0]['company_name'])){
						$name=$client[0]['firstname']." ".$client[0]['middlename']." ".$client[0]['lastname'];	
					}else{
						$name=$client[0]['company_name'];	
					}
					$description="Wallet deposit for ".$acc;
					
					
					$this->MakeWalletJounalEntry($debt_id,$office_id,$_SESSION['user_id'],$wallet_transaction_id,$transaction_id,$amount,'DR',$sideA,$description);//DR
					$this->MakeWalletJounalEntry($credit_id,$office_id,$_SESSION['user_id'],$wallet_transaction_id,$transaction_id,$amount,'CR',$sideB,$description);//CR
					$smsNumber = $this->formatNumber($acc);
					$curr = $this->getThisSaccoCurrency();
					$nodeName = $_SESSION['branch'];
					$message = "Hello, Your ".$nodeName." wallet account has been credited with ".$curr. " ". number_format($amount,2). ". Your new balance is ".$curr." ".number_format($new_balance,2)." Txn ID ".$transaction_id;
				    $this->SendSMS($smsNumber,$message);
					$this->sendPushNotification($acc,$message);
					$this->db->commit();

					header('Location:'.URL.'members/newwalletdeposit/'.$data['accountno'].'/'.$client[0]['member_id'].'/'.$wallet_transaction_id.'?msg=receipt');

				}catch(Exception $e){
					$this->db->rollBack();
					$error=$e->getMessage();
					header('Location:'.URL.'members/newwalletdeposit?msg=fail&error='.$error);
					exit(); 	  
				}	

			}else{
				header('Location:'.URL.'members/newwalletdeposit?msg=fail');
			}
		}

		function withdrawFromWalletaccount($data){

			$this->db->beginTransaction();//beginning transaction
			$update_time=date('Y-m-d H:i:s');
			$acc=$data['accountno'];
			$result= $this->db->selectData("SELECT * FROM sm_mobile_wallet WHERE wallet_account_number='".$acc."' ");

			$amount = str_replace(",","",$data['amount']);
			$balance = $result[0]['wallet_balance'];

			if ($amount <= 0) {
				header('Location: ' . URL . 'members/walletwithdraw?msg=' . $amount); 
				die();
			}

			$new_balance = $balance - $amount;
			$office_id =  $_SESSION['office'];

			$product_id = 0;
			$prodType = 5;
			$mapping = $this->GetGLPointers($product_id,$prodType,'Wallet Cash Withdraw');
                file_put_contents('log.txt',serialize($mapping));
			if (empty($mapping)) {
				header('Location: ' . URL . 'products/addglpointersWallet/0?msg=wcw'); 
				die();
			}

			//Add money to teller/users cash account
			if (isset($data['amount'])) {
				$cashdata = array(
					'account_balance' => $this->getUserCashBalance() - $data['amount'],
				);
				$this->db->UpdateData('m_staff', $cashdata,"`id` = '{$_SESSION['user_id']}'"); 
			}

			if ($balance >= $amount) {

				if(!empty($mapping[0]["debit_account"])&&!empty($mapping[0]["credit_account"])){

					try{
						
						$data['amount_in_words']=$this->convertNumber($amount);
						$data['wallet_balance']=$new_balance;
						$data['amount']=$amount;
						$data['transaction_type']='Cash Withdraw'; 
						$data['description']='To : '. $acc;
						$data['wallet_account_number'] = $acc;
	  
						$transaction_id = "W".uniqid();
						$data['transaction_id']=$transaction_id;
						
						$wallet_transaction_id = $this->logWalletTransaction($data);

						$debt_id =$mapping[0]["debit_account"]; //debit savings  Control account
						$credit_id =$mapping[0]["credit_account"]; //credit cash savings reference	
						$sideA=$this->getAccountSide($debt_id);
						$sideB=$this->getAccountSide($credit_id);
				
						///JOURNAL ENTRY POSTINGS
						$client = $this->getClientWalletSaveddetails($acc);
						$name=null;
						if(empty($client[0]['company_name'])){
							$name=$client[0]['firstname']." ".$client[0]['middlename']." ".$client[0]['lastname'];	
						}else{
							$name=$client[0]['company_name'];	
						}
						$description="Wallet Cash Withdraw for ".$acc;
						
						
						$this->MakeWalletJounalEntry($debt_id,$office_id,$_SESSION['user_id'],$wallet_transaction_id,$transaction_id,$amount,'DR',$sideA,$description);//DR
						$this->MakeWalletJounalEntry($credit_id,$office_id,$_SESSION['user_id'],$wallet_transaction_id,$transaction_id,$amount,'CR',$sideB,$description);//CR
						$smsNumber = $this->formatNumber($acc);
						$curr = $this->getThisSaccoCurrency();
						$nodeName = $_SESSION['branch'];
						$message = "Hello, Your ".$nodeName." wallet account has been debited with ".$curr. " ". number_format($amount,2). ". Your new balance is ".$curr." ".number_format($new_balance,2)." Txn ID ".$transaction_id;
					    $this->SendSMS($smsNumber,$message);
						$this->sendPushNotification($acc,$message);
						$this->db->commit();

						header('Location:'.URL.'members/walletwithdraw/'.$data['accountno'].'/'.$client[0]['member_id'].'/'.$wallet_transaction_id.'?msg=receipt');

					}catch(Exception $e){
						$this->db->rollBack();
						$error=$e->getMessage();
						header('Location:'.URL.'members/walletwithdraw?msg=fail&error='.$error);
						exit(); 	  
					}	

				}else{
					header('Location:'.URL.'members/walletwithdraw?msg=fail');
				}
			} else{
				header('Location:'.URL.'members/walletwithdraw?msg=balance');
			}
		}

function depositaccount($data, $office, $user_id, $branch)
{
	try {
		$this->db->beginTransaction();
		$update_time = date('Y-m-d H:i:s');
		$acc = $data['accountno'];
		$result = $this->db->selectData("SELECT * FROM m_savings_account WHERE account_no = '".$acc."' ");

		$amount = str_replace(",", "", $data['amount']);
		$balance = $result[0]['running_balance'];
		$availabledeposit = $result[0]['total_deposits'];

		$product_id = $result[0]['product_id'];
		$member_id = $result[0]['member_id'];

		$new_total_deposits = $availabledeposit + $amount;
		$new_balance = $amount + $balance;
		$office_id = $office;

		$prodType = 3;
		$transactionName = 'Deposit on Savings';

		$id = $product_id;
		$transtype = $transactionName;

		$mapping = $this->GetGLPointers($id, $prodType, $transtype, $office);

		if (empty($mapping)) {
			throw new Exception("GL Pointers not found.");
		}

		if (!empty($mapping[0]["debit_account"]) && !empty($mapping[0]["credit_account"])) {

			$transaction_id = "S" . uniqid();
			$transaction_postData = array(
				'savings_account_no' => $acc,
				'payment_detail_id' => "CASH",
				'transaction_type' => "Deposit",
				"op_type" => 'CR',
				'amount' => str_replace(",", "", $data['amount']),
				'running_balance' => $new_balance,
				'depositor_name' => $data['depositor'],
				'amount_in_words' => $data['amount_in_words'],
				'telephone_no' => $data['tel'],
				'branch' => $office_id,
				'transaction_id' => $transaction_id,
				'user_id' => $user_id
			);

			// Add money to teller/users cash account
			if (isset($data['amount'])) {
				$cashdata = array(
					'account_balance' => $this->getUserCashBalance() + $data['amount'],
				);
				$this->db->UpdateData('m_staff', $cashdata, "`id` = '{$user_id}'");
			}

			$deposit_transaction_id = $this->db->InsertData('m_savings_account_transaction', $transaction_postData);

			$charge_results = $this->MakeSavingsDepChargeTransaction($prodType, $transactionName, $acc, $new_balance, $new_total_deposits, $name, $member_id);

			$new_balance = $charge_results['balance'];
			$new_total_deposits = $charge_results['new_total_deposits'];

			$depositstatus = array(
				'total_deposits' => $new_total_deposits,
				'running_balance' => $new_balance,
				'last_updated_on' => $update_time,
			);

			$this->db->UpdateData('m_savings_account', $depositstatus, "`account_no` = '{$acc}'");

			$debt_id = $mapping[0]["debit_account"]; // debit savings Control account
			$credit_id = $mapping[0]["credit_account"]; // credit cash savings reference
			$sideA = $this->getAccountSide($debt_id);
			$sideB = $this->getAccountSide($credit_id);

			// JOURNAL ENTRY POSTINGS
			$client = $this->getClientSaveddetails($acc);
			$name = null;
			if (empty($client[0]['company_name'])) {
				$name = $client[0]['firstname'] . " " . $client[0]['middlename'] . " " . $client[0]['lastname'];
			} else {
				$name = $client[0]['company_name'];
			}
			$mobile_number = $client[0]['mobile_no'];
			$description = "Savings Deposit for " . $name;
			$acc_id = $debt_id;
			$branch_id = $branch;
			$user = $user_id;
			$savings_trans_id = $deposit_transaction_id;
			$trans_id = $transaction_id;
			$amount =  $data['amount'];
			$side = $sideA;

			$this->makeSavingsJournalEntry($acc_id, $office, $branch_id, $user, $savings_trans_id, $trans_id, str_replace(",", "", $amount), 'DR', $side, $description); // DR
			$acc_id = $credit_id;
			$branch_id = $branch;
			$user = $user_id;
			$savings_trans_id = $deposit_transaction_id;
			$trans_id = $transaction_id;
			$amount =  $data['amount'];
			$side = $sideB;
			$this->makeSavingsJournalEntry($acc_id, $office, $branch_id, $user, $savings_trans_id, $trans_id, str_replace(",", "", $amount), 'CR', $side, $description); // CR
			
            
			$smsNumber = $mobile_no;
			$curr = $this->getThisSaccoCurrency();
			$nodeName = $branch;
			$message = "Hello, Your " . $nodeName . " Savings account has been credited with " . $curr . " " . number_format($amount, 2) . ". Your new balance is " . $curr . " " . number_format($new_balance, 2) . " Txn ID " . $transaction_id;
			
			$this->SendSMS($smsNumber, $message);
			
			// $this->sendPushNotification($mobile_no, $message);
			// echo 'ddnd';
			$resultData = array(
				'total_deposits' => $new_total_deposits,
				'running_balance' => $new_balance,
				'last_updated_on' => $update_time,
			);

			$this->db->commit();
			return $resultData; // Return the transaction ID

		} else {
			throw new Exception("Debit and credit accounts not found.");
		}

	} catch (Exception $e) {
		$this->db->rollBack(); // Rollback the transaction in case of failure
		throw new Exception("Failed to deposit: " . $e->getMessage());
	}
}

function withdrawaccount($data, $office, $user_id, $branch) {
    try {
        $this->db->beginTransaction(); // Beginning transaction
        $charge = 0;
        $update_time = date('Y-m-d H:i:s');
        $acc = stripslashes($data['accountno']);
        $result = $this->db->selectData("SELECT * FROM m_savings_account WHERE account_no = '".$acc."' ");

        $product_id = $result[0]['product_id'];
        $product =  $this->db->selectData("SELECT * FROM m_savings_product WHERE id = '".$result[0]['product_id']."'");

        $amount = str_replace(",","",$data['amount']); 

        if ($amount <= 0) {
            throw new Exception("Invalid withdrawal amount.");
        }

        $results = $this->db->selectData("SELECT * FROM m_savings_account WHERE account_no = '".$acc."' AND withdraw_status = 'Inactive' ");

        if (count($results) > 0) {
            throw new Exception("Account is inactive for withdrawal.");
        }

        $balance = $result[0]['running_balance'];
        $availablewithdraw = $result[0]['total_withdrawals'];

        $new_total_withdraws = $availablewithdraw + $amount ;

        $prodType = 3;
        $transactionName = 'Withdraw on Savings';

        $tran_id = $this->getTransactionID($transactionName);
		
        $transaction_charges = $this->getTransactionCharges($tran_id);
        $total_charge_amount = 0;
        if (!empty($transaction_charges)) {
            foreach ($transaction_charges as $key => $value) {
                $mappingCharges = $this->GetGLChargePointers($value['id'],$prodType,$transactionName,$tran_id);
                if (!empty($mappingCharges)) {
                    $total_charge_amount += $value['amount'];
                }
            }
        }
        $charge = $total_charge_amount;
        $balance_before_charge =  $balance-($amount);
        $new_balance =  $balance-($amount+$charge);
        $actualbalance = $new_balance;
        $min_balance = $product[0]['min_required_balance'];
        $data['amount_in_words'] = $this->convertNumber($data['amount']);    
        $data['charge_amount_in_words'] = $this->convertNumber($charge);

        if ($actualbalance >= $min_balance) {
			$id = $product_id;
			$transtype =$transactionName;
            $mapping = $this->GetGLPointers($id, $prodType, $transtype, $office);

            if (empty($mapping)) {
                throw new Exception("Failed to get GL pointers for withdrawal.");
            }

            $current_cash = $this->getUserCashBalance($office, $user_id);
			

            if ($current_cash <= 0) {
                throw new Exception("Teller's cash balance is insufficient.");
            } 

            if ($current_cash < $amount) {
                throw new Exception("Teller's cash balance is insufficient for withdrawal.");
            }

            if (!empty($mapping[0]["debit_account"]) && !empty($mapping[0]["credit_account"])) {
                // Withdrawal logic
                // Subtract money from teller/user's cash account
                if (isset($amount)) {
                    $cashdata = array(
                        'account_balance' => $this->getUserCashBalance($office, $user_id) - $amount,
                    );
                    $this->db->UpdateData('m_staff', $cashdata,"`id` = '{$user_id}'"); 
                }
				

                $client = $this->getClientSaveddetails($acc);
                $name = null;

                if (empty($client[0]['company_name'])){
                    $name = $client[0]['firstname']." ".$client[0]['middlename']." ".$client[0]['lastname'];    
                } else {
                    $name = $client[0]['company_name'];    
                }
                $transaction_postData = array(
                    'savings_account_no' =>$acc,
                    'amount' =>$amount,
                    'transaction_type' =>'Withdraw',
                    'depositor_name' => $name,
                    'running_balance' =>$balance_before_charge,
                    'amount_in_words' =>$data['amount_in_words'],
                    'branch' =>$office,
                    'user_id' => $user_id,
                );

                $withdraw_transaction_id = $this->db->InsertData('m_savings_account_transaction', $transaction_postData);

                $charge_results = $this->MakeSavingsChargeTransaction($prodType,$transactionName,$acc,$balance_before_charge,$new_total_withdraws,$name, $client[0]['c_id']);

                $actualbalance = $charge_results['balance'];
                $new_total_withdraws = $charge_results['withdraws'];

                $withdrawstatus = array(
                    'total_withdrawals' => $new_total_withdraws,
                    'running_balance' => $actualbalance,
                    'last_updated_on' =>$update_time,
                );

                $this->db->UpdateData('m_savings_account', $withdrawstatus,"`account_no` = '{$acc}'");

                $transaction_uniqid = uniqid();
                $deposit_transaction_uniqid = $withdraw_transaction_id."".$transaction_uniqid;

                $transaction_id = "S".$deposit_transaction_uniqid;
                $debt_id =$mapping[0]["debit_account"]; // Debit cash savings reference    
                $credit_id =$mapping[0]["credit_account"]; // Credit savings control account

                $sideA = $this->getAccountSide($debt_id);
                $sideB = $this->getAccountSide($credit_id);
                $description = "Savings Withdraw by ".$name;

                $new_data['transaction_id'] = $transaction_id;
                $this->db->UpdateData('m_savings_account_transaction', $new_data,"`id` = '{$withdraw_transaction_id}'");
				
                $this->db->UpdateData('m_savings_account_transaction', $new_data,"`id` = '{$chargeID}'");

				$acc_id = $debt_id;
				$branch_id = $branch;
				$user = $user_id;
				$savings_trans_id = $withdraw_transaction_id;
				$trans_id = $transaction_id;
				$amount =  $data['amount'];
				$side = $sideA;

				$this->makeSavingsJournalEntry($acc_id, $office, $branch_id, $user, $savings_trans_id, $trans_id, str_replace(",", "", $amount), 'DR', $side, $description); // DR

				$acc_id = $debt_id;
				$branch_id = $branch;
				$user = $user_id;
				$savings_trans_id = $withdraw_transaction_id;
				$trans_id = $transaction_id;
				$amount =  $data['amount'];
				$side = $sideB;

				$this->makeSavingsJournalEntry($acc_id, $office, $branch_id, $user, $savings_trans_id, $trans_id, str_replace(",", "", $amount), 'CR', $side, $description); // CR
				
                $this->db->commit();
                $mmID = $client[0]['c_id'];

                // Send SMS or push notification

                $smsNumber = $client[0]['mobile_no'];
				echo $smsNumber;
                $curr = $this->getThisSaccoCurrency();
                $nodeName = $branch;
                $message = "Hello, Your ".$nodeName." Savings account has been debited with ".$curr. " ". number_format($amount,2). ". Your new balance is ".$curr." ".number_format($actualbalance,2)." Txn ID ".$transaction_id;
                $this->SendSMS($smsNumber,$message);
                // $this->sendPushNotification($smsNumber,$message);

                return array("status" => 200, "message" => "Withdrawal successful", "transaction_id" => $transaction_id);

            } else {
                throw new Exception("Failed to get GL pointers for withdrawal.");
            }
        } else {
            throw new Exception("Insufficient funds for withdrawal.");
        }
    } catch (Exception $e) {
        $this->db->rollBack(); // Rollback the transaction in case of failure
        throw new Exception("Failed to withdraw: " . $e->getMessage());
    }
}

function MakeSavingsChargeTransaction($prodType,$transactionName,$acc,$actualbalance,$new_total_withdraws,$name, $member_id){

	$tran_id = $this->getTransactionID($transactionName);
	$mapping_charges = '';
	if ($tran_id != NULL) {
		$actual_charges = $this->getTransactionCharges($tran_id);
		$exemptions = $this->getMemberChargeExemptions($member_id);
		foreach ($actual_charges as $key => $value) {

			if ((is_null($exemptions)) || (!in_array($value, $exemptions))) {
				$mapping_charges = $this->GetGLChargePointers($value['id'],$prodType,$transactionName,$tran_id);
				if (!empty($mapping_charges)) {

					$actualbalance -= $value['amount'];
					$chargeData = array(
						'savings_account_no' => $acc,
						'amount' => $value['amount'],
						'op_type'=>'DR',
						'transaction_type' => $value['name'] . " Charge",
						'depositor_name' => $name,
						'running_balance' => $actualbalance,
						'amount_in_words' => $this->convertNumber($value['amount']),
						'branch' => $_SESSION['office'],
						'user_id' => $_SESSION['user_id'],
						);

					$charge_transaction_id = $this->db->InsertData('m_savings_account_transaction', $chargeData);

					$new_total_withdraws += $value['amount'];
					$chargeUpdateData = array(
						'total_withdrawals' => $new_total_withdraws,
						'running_balance' => $actualbalance,
						'last_updated_on' => date('Y-m-d H:i:s'),
						);

					$this->db->UpdateData('m_savings_account', $chargeUpdateData,"`account_no` = '{$acc}'");

					$transaction_uniqid=uniqid();

					$charge_transaction_uniqid = $charge_transaction_id."".$transaction_uniqid;

					$transaction_id = "S".$charge_transaction_uniqid;
					$debt_id =$mapping_charges[0]["debit_account"]; //debit cash savings reference	
					$credit_id =$mapping_charges[0]["credit_account"]; //credit savings  Control account

					$sideA = $this->getAccountSide($debt_id);
					$sideB = $this->getAccountSide($credit_id);
					$description = "Charge on ".$transactionName . " Transaction";

					$new_data['transaction_id'] = $transaction_id;
					$this->db->UpdateData('m_savings_account_transaction', $new_data,"`id` = '{$charge_transaction_id}'");

					$this->makeSavingsJournalEntry($debt_id,$_SESSION['office'],$_SESSION['user_id'],$charge_transaction_id,$transaction_id,$value['amount'],'DR',$sideA,$description);//DR
					$this->makeSavingsJournalEntry($credit_id,$_SESSION['office'],$_SESSION['user_id'],$charge_transaction_id,$transaction_id,$value['amount'],'CR',$sideB,$description);//CR

					$postTransCharges = array(
						'transaction_id' => $transaction_id,
						'charge_id' => $value['id'],
						'trans_amount' => $value['amount'],
						'date' => date("Y-m-d H:i:s"),
					);

					$this->db->InsertData('m_charge_transaction', $postTransCharges);
				}
			} else {
				//echo "Member " . $member_id . " Exepted From " . ucwords($value['name']) . "</br>";
			}
		}
	}

	$results['balance'] = $actualbalance;
	$results['withdraws'] = $new_total_withdraws;

	return $results; 

}


function MakeSavingsDepChargeTransaction($prodType,$transactionName,$acc,$actualbalance,$new_total_deposits,$name, $member_id){

	$tran_id = $this->getTransactionID($transactionName);
	$mapping_charges = '';
	if ($tran_id != NULL) {
		$actual_charges = $this->getTransactionCharges($tran_id);
		$exemptions = $this->getMemberChargeExemptions($member_id);
		foreach ($actual_charges as $key => $value) {

			if ((is_null($exemptions)) || (!in_array($value, $exemptions))) {
				$mapping_charges = $this->GetGLChargePointers($value['id'],$prodType,$transactionName,$tran_id);
				if (!empty($mapping_charges)) {

					$actualbalance -= $value['amount'];
					$chargeData = array(
						'savings_account_no' => $acc,
						'amount' => $value['amount'],
						'transaction_type' => $value['name'],
						'depositor_name' => $name,
						'op_type'=>'DR',
						'running_balance' => $actualbalance,
						'amount_in_words' => $this->convertNumber($value['amount']),
						'branch' => $_SESSION['office'],
						'user_id' => $_SESSION['user_id'],
					);

					$charge_transaction_id = $this->db->InsertData('m_savings_account_transaction', $chargeData);

					$new_total_deposits += $value['amount'];
					$depositstatus = array(
						'total_deposits' => $new_total_deposits,
						'running_balance' => $actualbalance,
						'last_updated_on' => date('Y-m-d H:i:s'),
					);

					$this->db->UpdateData('m_savings_account', $depositstatus,"`account_no` = '{$acc}'");

					$transaction_uniqid=uniqid();

					$charge_transaction_uniqid = $charge_transaction_id."".$transaction_uniqid;

					$transaction_id = "S".$charge_transaction_uniqid;
					$debt_id =$mapping_charges[0]["debit_account"]; //debit cash savings reference	
					$credit_id =$mapping_charges[0]["credit_account"]; //credit savings  Control account

					$sideA = $this->getAccountSide($debt_id);
					$sideB = $this->getAccountSide($credit_id);
					$description = "Charge on ".$transactionName . " Transaction";

					$new_data['transaction_id'] = $transaction_id;
					$this->db->UpdateData('m_savings_account_transaction', $new_data,"`id` = '{$charge_transaction_id}'");

					$this->makeSavingsJournalEntry($debt_id,$_SESSION['office'],$_SESSION['user_id'],$charge_transaction_id,$transaction_id,$value['amount'],'DR',$sideA,$description);//DR
					$this->makeSavingsJournalEntry($credit_id,$_SESSION['office'],$_SESSION['user_id'],$charge_transaction_id,$transaction_id,$value['amount'],'CR',$sideB,$description);//CR

					$postTransCharges = array(
						'transaction_id' => $transaction_id,
						'charge_id' => $value['id'],
						'trans_amount' => $value['amount'],
						'date' => date("Y-m-d H:i:s"),
					);

					$this->db->InsertData('m_charge_transaction', $postTransCharges);
				}
			} else {
				//echo "Member " . $member_id . " Exepted From " . ucwords($value['name']) . "</br>";
			}
		}
	}

	$results['balance'] = $actualbalance;
	$results['new_total_deposits'] = $new_total_deposits;

	return $results; 

}

function getchargeDetails($id,$apply){

	$query=$this->db->SelectData("SELECT * FROM m_charge where id='".$id."' AND charge_applies_to='".$apply."' AND charge_type='Charge' ");

	return $query;	
}


function withdrawfromFixed($data){

	$this->db->beginTransaction();//transaction beginning

	$acc=stripslashes($data['accountno']);

	$result=$this->db->SelectData("SELECT * FROM fixed_deposit_account WHERE account_no='".$acc."'");
	$product_id = $result[0]['product_id'];

	$amount = str_replace(",","",$data['amount']);
	$balance = $result[0]['running_balance'];
	$availablewithdraw = $result[0]['total_withdrawals'];

	$new_total_withdraws = $availablewithdraw + $amount ;
	$new_balance =  $balance-$amount;
	$office_id =  $_SESSION['office'];

	$charge = 0;
	$actualbalance=$new_balance+$charge;

	if($actualbalance>=0){

		$prodType = 4;
		$mapping = $this->GetGLPointers($product_id,$prodType,'Withdraw on fixed Deposit');


		if (empty($mapping)) {
			header('Location: ' . URL . 'products/fixeddepositproducts?msg=withdep'); 
			die();
		}

		if(!empty($mapping[0]["debit_account"])&&!empty($mapping[0]["credit_account"])){
			
			try{			

				$client = $this->getClientSaveddetails($acc);
				if(empty($client[0]['company_name'])){
					$name=$client[0]['firstname']." ".$client[0]['middlename']." ".$client[0]['lastname'];	
				}else{
					$name=$client[0]['company_name'];	
				}	

				$transaction_postData = array(
					'fixed_account_no' =>$acc,
					'amount' =>$amount,
					'transaction_type' =>'Withdraw',
					'depositor_name' =>$name,
					'running_balance' =>$new_balance,
					'amount_in_words' =>$data['amount_in_words'],
					'branch' =>$_SESSION['office'],
					'user_id' => $_SESSION['user_id'],
					'approved_by' => $_SESSION['user_id'],
				);
			
				$withdraw_fixed_trans_id = $this->db->InsertData('fixed_deposit_transactions', $transaction_postData);

				//withdraw
				$withdrawstatus = array(
					'total_withdrawals' => $new_total_withdraws,
					'running_balance' => $new_balance,
				);

				$this->db->UpdateData('fixed_deposit_account', $withdrawstatus,"`account_no` = '{$acc}'");

				//Add money to teller/users cash account
				if (isset($amount)) {
					$cashdata = array(
						'account_balance' => $this->getUserCashBalance() - $amount,
					);
					$this->db->UpdateData('m_staff', $cashdata,"`id` = '{$_SESSION['user_id']}'"); 
				}

				$transaction_uniqid=uniqid();
				$withdraw_transaction_uniqid = $withdraw_fixed_trans_id."".$transaction_uniqid;

				$transaction_id = "FD".$withdraw_transaction_uniqid;

				$debt_id =$mapping[0]["debit_account"]; //debit fixed  Control account
				$credit_id =$mapping[0]["credit_account"]; //credit cash fixed reference	
				$sideA=$this->getAccountSide($debt_id);
				$sideB=$this->getAccountSide($credit_id);
				$description="Fixed Deposit Withdraw by ".$name;

				$new_data['transaction_id'] = $transaction_id;
				$this->db->UpdateData('fixed_deposit_transactions', $new_data,"`id` = '{$withdraw_fixed_trans_id}'");

				$this->makeFixedJournalEntry($debt_id,$office_id,$_SESSION['user_id'],$withdraw_fixed_trans_id,$transaction_id,$amount,'DR',$sideA,$description);//DR
				$this->makeFixedJournalEntry($credit_id,$office_id,$_SESSION['user_id'],$withdraw_fixed_trans_id,$transaction_id,$amount,'CR',$sideB,$description);//CR
				$this->db->commit();

				header('Location:'.URL.'members/withdrawfixed?msg=success');

			}catch(Exception $e){
				$this->db->rollBack();
				$error=$e->getMessage();	
				header('Location:'.URL.'members/withdrawfixed?msg=transactionfailed&error='.$error);
				exit;	
				
			}
		}else{
			header('Location:'.URL.'members/withdrawfixed?msg=transactionfailed');
		}
	}else{
		header('Location:'.URL.'members/withdrawfixed?msg=insuffient funds');
	}
}

function makeFixedJournalEntry($acc_id,$office,$user,$fixed_trans_id,$trans_id,$amount,$type,$side,$description){
	$postData = array(
		'account_id' =>$acc_id,
		'office_id' => $office,
		'branch_id' => $_SESSION['branchid'],
		'createdby_id' =>$user,
		'fixed_deposit_transaction_id' =>$fixed_trans_id,
		'transaction_id' =>$trans_id,
		'amount' => $amount,
		'transaction_type' =>$type,
		'trial_balance_side' =>$side,							
		'description' =>$description,							
		);

	
	$this->db->InsertData('acc_gl_journal_entry', $postData);	
	
}

function officeList(){


	$result =  $this->db->SelectData("SELECT * FROM m_branch order by id ");

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

function officeName($id){

	if($id!=''){

		$results =  $this->db->SelectData("SELECT * FROM m_branch where id='".$id."'");



		return  $results[0]['name'];

	}else{

		return  '';

	}

}

function getOffice($id){


	$results =  $this->db->SelectData("SELECT * FROM m_branch where id='".$id."'");

	return $results;
}



/********************  centres  *******************************/

function staffList(){

	return $this->db->selectData("SELECT * FROM m_staff ");

}

function centresList(){

	return $this->db->SelectData("SELECT * FROM m_group where level_id='1' and status='1' ");

}

function getCentreDetails($id){

	return $this->db->SelectData("SELECT * FROM m_group where id='".$id."'  ");

}

function getstaffList($id){

	return $this->db->SelectData("SELECT * FROM m_staff where office_id='".$id."' ");
}



function createCentre(){

	$data =  $_POST;

	$d = strtotime($data['createdOn']);

	$createdOn = date('Y-m-d',$d);

	$d1 = strtotime($data['activation_date']);

	$activation_date = date('Y-m-d',$d1);

	$postData = array(
		'level_id' => 1,

		'office_id' => $data['office'],

		'display_name' => $data['name'],

		'staff_id' => $data['staff'],

		'activatedon_userid' => $data["activate"],

		'external_id' => $data['externalid'],

		'submittedon_date' => $createdOn,

		'activation_date' => $activation_date,

		);


	$this->db->InsertData('m_group', $postData);

	header('Location: ' . URL . 'members/centres?msg=success');  

}

function UpdateCentre(){

	

	$data =  $_POST;

	$id = $_POST['id'];
	$d1 = strtotime($data['activation_date']);

	$activation_date = date('Y-m-d',$d1);

	$postData = array(

		'display_name' => $data['name'],

		'staff_id' => $data['staff'],

		'external_id' => $data['externalid'],

		'activation_date' => $activation_date,

		);

	$this->db->UpdateData('m_group', $postData,"`id` = '{$id}'");

	header('Location: ' . URL . 'members/centres?msg=success');  

}



function DeleteCentre($id){

	$postData =   array('status' => '0');

	$this->db->UpdateData('m_group', $postData,"`id` = '{$id}'");

	header('Location: ' . URL . 'members/centres?msg=success');  


}



function paymentType(){

	return $this->db->SelectData("SELECT * FROM payment_mode order by id ");

}		


function savingsProductCharges($id) {

	
	$query= $this->db->SelectData("SELECT * FROM m_savings_product_charge mp JOIN m_charge mc 

		where mp.charge_id =mc.id  and mc.charge_applies_to='Fixed Deposit'  and mp.savings_product_id ='".$id."' order by mp.charge_id desc");
	$option="";
	if(count($query)>0){
		foreach ($query as $key => $value) {
			$option .= '<tr><td>'.$value['id'].'</td><td>'.$value['name'].'</td><tr>';
		}
		print_r($option);
		die();
		
	}else{
		
	}
	return $query;
	

}


function searchaccount($data){

	$acc=$data['accno'];

	$fname=$data['fname'];

	$lname=$data['lname'];
	$rset=null;	


	$ch= $this->db->selectData("SELECT * FROM m_savings_account WHERE account_no LIKE '%".$acc."%'");	

	if(!empty($ch)&&!empty($acc)){

		foreach ($ch as $key => $value) {		

			$members=$this->getClient($ch[$key]['member_id']);	
			if($members[0]['account_status']==500){
				if(empty($members[0]['company_name'])){
					$rset[$key]['name'] =$members[0]['firstname']." ".$members[0]['middlename']." ".$members[0]['lastname']; 
				}else{
					$rset[$key]['name'] =$search[$key]['company_name'];	
					
				}
				$rset[$key]['accountno'] =$ch[$key]['account_no']; 
				$rset[$key]['key_id'] =$ch[$key]['id']; 
				$rset[$key]['c_id'] =$ch[$key]['member_id']; 
				$rset[$key]['office'] =$members[0]['name']; 
			}else{
				$rset=null;	  
			}
		}
		

		return  $rset;	

	}else{

		//|| company_name LIKE '%".$fname."%' || lastname LIKE '%".$lname."%' or middlename LIKE '%".$lname."%'  (firstname LIKE '%".$fname."%' or company_name LIKE '%".$fname."%') or 

		$search= $this->db->selectData("SELECT * FROM members WHERE (firstname LIKE '%".$fname."%' or company_name LIKE '%".$fname."%') and (lastname LIKE '%".$lname."%' or middlename LIKE '%".$lname."%')  ");	

		if((!empty($search)and !empty($fname)) or (!empty($search)and !empty($lname))){

			foreach ($search as $key => $value){		
				if($search[$key]['status']=='Active'){
					$account=$this->getAccount($search[$key]['c_id']);	

					foreach ($account as $key1 => $value){	
						if(empty($search[$key]['company_name'])){
							$rset[$key]['name'] =$search[$key]['firstname']." ".$search[$key]['middlename']." ".$search[$key]['lastname']; 
						}else{
							$rset[$key]['name'] =$search[$key]['company_name'];		
						}
						$rset[$key]['c_id'] =$search[$key]['c_id']; 
						$rset[$key]['accountno'] =$account[$key1]['account_no']; 
						$rset[$key]['key_id'] =$account[$key1]['id'];
						$rset[$key]['office'] =$this->officeName($search[$key]['office_id']); 

					}

				}else{
					$rset=null;	  
				}	 

			} 

			return $rset;
		}
	}

}	

function getAccount($cid){

	
	return $this->db->selectData("SELECT * FROM m_savings_account WHERE member_id='".$cid."' ");

}


function getsavingproduct($id){


	$product= $this->db->selectData("SELECT * FROM m_savings_product WHERE id='".$id."' ");

	if(count($product)>0){
		$rset=array();
		foreach ($product as $key => $value) {
			array_push($rset,array(
				'id'=>$product[0]['id'],
				'name'=>$product[0]['name'],
				'description'=>$product[0]['description'],
				'interest_rate'=>$product[0]['nominal_interest_rate'],
				'interest_cal'=>$product[0]['interest_calculation_method'],
				'interest_post'=>$product[0]['interest_posting_period'],
				'opening_balance'=>$product[0]['min_required_opening_balance'],	
				'min_balance'=>$product[0]['min_required_balance'],	
				'interest_balance'=>$product[0]['minimum_balance_for_interest_calculation'],	
				));
		}

		echo json_encode(array("result" =>$rset));
		die();
	}
}


function getfixedaccountbalance($actno){

	$result=  $this->db->selectData("SELECT * FROM fixed_deposit_account WHERE account_no='".$actno."'");
	$product=  $this->db->selectData("SELECT * FROM  fixed_deposit_product WHERE id='".$result[0]['product_id']."'");
	if(count($result)>0){
		$max_id= $this->db->selectData("SELECT max(id) as id FROM  fixed_deposit_transactions WHERE fixed_account_no='".$actno."'");
		$r_balance= $this->db->selectData("SELECT * FROM  fixed_deposit_transactions WHERE id='".$max_id[0]['id']."'");

		$cid=$result[0]['member_id'];
		$client=$this->getClient($cid);
		
		if(empty($client[0]['firstname'])){
			$displayname=$client[0]['company_name'];
		}else{
			$displayname=$client[0]['firstname']." ".$client[0]['middlename']." ".$client[0]['lastname'];
		}	
	//$years=  $this->db->selectData("SELECT TIMESTAMPDIFF(YEAR,DATE(submittedon_date),CURDATE()) AS years FROM fixed_deposit_account WHERE account_no='".$actno."'");
	//$months=  $this->db->selectData("SELECT TIMESTAMPDIFF(YEAR,DATE(submittedon_date),CURDATE()) AS years FROM fixed_deposit_account WHERE account_no='".$actno."'");
	//$days=  $this->db->selectData("SELECT TIMESTAMPDIFF(YEAR,DATE(submittedon_date),CURDATE()) AS years FROM fixed_deposit_account WHERE account_no='".$actno."'");
		
		$rset=array();
		foreach ($result as $key => $value) {
			array_push($rset,array(
				'member_id'=>$result[$key]['member_id'],
				'displayname'=>$displayname,
				'dob'=>date('d-m-Y',strtotime($client[0]['date_of_birth'])),
				'national_id'=>$client[0]['national_id'],
				'address'=>$client[0]['address'],
				'last_trans_id'=>$r_balance[0]['id'],
				'last_trans_amount'=>$r_balance[0]['amount'],
				'rbalance'=>$result[0]['running_balance'],
				'last_trans_date'=>$r_balance[0]['transaction_date'],
				'account_opened'=>$result[$key]['submittedon_date'],
				'product'=>$product[0]['name'],
				'status'=>$result[$key]['account_status'],
				'fixedamount'=>$result[0]['amount_fixed'],		
				'interest_rate'=>$result[0]['interest_rate'],		
				'period'=>$result[0]['term'],		
				'period_value'=>$result[0]['term_value'],		
				'acc_update_date'=>date('M j Y g:i A',strtotime($result[0]['last_updated_on'])),
				));
		}

		echo json_encode(array("result" =>$rset));
		die();
	}else{
		$rset=array();
		array_push($rset,array(
			'member_id'=>'0',
			'rbalance'=>'0',		
			));
		echo json_encode(array("result" =>$rset));		
		die();
	}

}


function getallFixedProducttoapply(){

	$pdts= $this->db->SelectData("SELECT * FROM fixed_deposit_product where product_status='Active'");
	$option=null;	
	if(count($pdts)>0){
		foreach ($pdts as $key => $value) {
			$option .= '<option value="' . $value['id'] . '">' . $value['name'] . '</option>';
		}
		print_r($option);
		die();
	}else{
		
	}	

}

function getallSavingsProducttoapply(){
	$office=$_SESSION['office'];
	$option="";
	$pdts= $this->db->SelectData("SELECT * FROM m_savings_product where office_id='$office' AND product_status='Active'");
	if(count($pdts)>0){
		foreach ($pdts as $key => $value) {
			$option .= '<option value="' . $value['id'] . '">' . $value['name'] . '</option>';
		}
		
		print_r($option);
		die();
	}		

}


function getmemberFixedacc($acc){
	$office=$_SESSION['office'];
	$result =  $this->db->SelectData("SELECT * FROM (fixed_deposit_account f JOIN members m ON f.member_id=m.c_id) JOIN fixed_deposit_product p ON f.product_id=p.id  where  f.account_no='".$acc."' ");
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
				'description'=>$result[$key]['description'],
				'amount'=>$result[$key]['amount_fixed'],
				'term'=>$result[$key]['term'],
				'term_val'=>$result[$key]['term_value'],
				'rate'=>$result[$key]['interest_rate'],
				'minimum_amount'=>$result[$key]['minimum_deposit_amount'],
				'maximum_amount'=>$result[$key]['maximum_deposit_amount'],
				'minimum__term'=>$result[$key]['minimum_deposit_term'],
				'minimum__value'=>$result[$key]['minimum__term_value'],
				'maximum_term'=>$result[$key]['maximum_deposit_term'],
				'maximum_value'=>$result[$key]['maximum__term_value'],
				'interest_posting'=>$result[$key]['interest_posting_period'],
				'interest_calculation'=>$result[$key]['interest_calculation_method'],
				'days'=>$result[$key]['days_in_year'],
				'status'=>$result[$key]['account_status'],
				));
		}

		echo json_encode(array("result" =>$rset));
		die();
		
	}

}
function getMembersavings($acc){
	$office=$_SESSION['office'];
	$result =  $this->db->SelectData("SELECT * FROM (m_savings_account s JOIN members m ON s.member_id=m.c_id) JOIN m_savings_product p ON s.product_id=p.id WHERE account_no='".$acc."' AND p.office_id = '$office'");
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
				'description'=>$result[$key]['description'],
				'interest_rate'=>$result[$key]['nominal_interest_rate'],
				'interest_post'=>$result[$key]['interest_posting_period'],
				'interest_cal'=>$result[$key]['interest_calculation_method'],
				'min_balance'=>$result[$key]['min_required_balance'],
				'opening_balance'=>$result[$key]['min_required_opening_balance'],
				'days'=>$result[$key]['days_in_year'],
				'status'=>$result[$key]['account_status'],
				));
		}

		echo json_encode(array("result" =>$rset));
		die();
		
	}


}
function deletefixedaccount($data){
	$acc=$data['account_no'];
	$date=date('Y-m-d');

	$postData = array(
		'closedon_date' =>$date,
		'closedon_userid' =>$_SESSION['user_id'],
		'closesure_reason' =>$data['reason'],
		'account_status' =>'Closed',
		);

	$this->db->UpdateData('fixed_deposit_account', $postData,"`account_no` = '{$acc}'");
	header('Location: ' . URL . 'members/closefixed?closed='.$acc.''); 
	
}
function openclosedfixedaccount($data){
	$acc=$data['account_no'];
	$date=date('Y-m-d');

	$postData = array(
		're_activatedon_date' =>$date,
		're_activatedon_userid' =>$_SESSION['user_id'],
		'account_status' =>'Active',
		);

	$this->db->UpdateData('fixed_deposit_account', $postData,"`account_no` = '{$acc}'");
	header('Location: ' . URL . 'members/reopenfixedaccount?activated='.$acc.''); 
	
}


function deletesavingsaccount($data, $user_id) {
    try {
        $acc = $data['account_no'];
        $date = date('Y-m-d');

        $postData = array(
            'closedon_date' => $date,
            'closedon_userid' => $user_id,
            'closesure_reason' => $data['reason'],
            'account_status' => 'Closed',
        );

        $this->db->UpdateData('m_savings_account', $postData, "`account_no` = '{$acc}'");

        $resultData = array(
            'account_status' => 'Closed',
        );

        return $resultData;

    } catch (Exception $e) {
        throw new Exception("Failed to delete savings account: " . $e->getMessage());
    }
}
// function OpenclosedSavings($acc){

// 	$date=date('Y-m-d');

// 	$postData = array(
// 		're_activatedon_date' =>$date,
// 		're_activatedon_userid' =>$_SESSION['user_id'],
// 		'account_status' =>'Active',
// 		);

// 	$this->db->UpdateData('m_savings_account', $postData,"`account_no` = '{$acc}'");
// 	header('Location: ' . URL . 'members/reopensavingsaccount?activated='.$acc.''); 
	
// }
function OpenclosedSavings($data, $user_id) {
    try {
		$acc = $data['accno'];
        $date = date('Y-m-d');

        $postData = array(
            're_activatedon_date' => $date,
            're_activatedon_userid' => $user_id,
            'account_status' => 'Active',
        );

        $this->db->UpdateData('m_savings_account', $postData, "`account_no` = '{$acc}'");

        // After the status update is successfully completed, you can return a success message or necessary data.
        $resultData = array(
            'account_status' => 'Active',
        );

        return $resultData;

    } catch (Exception $e) {
        throw new Exception("Failed to update savings account status: " . $e->getMessage());
    }
}

function getMemberaccount($account) {
    try {
        $result = $this->db->SelectData("SELECT * FROM m_savings_account JOIN members ON m_savings_account.member_id = members.c_id WHERE m_savings_account.account_no='".$account."' ");
        
        if (count($result) > 0) {
            foreach ($result as $key => $value) {  
                if (empty($result[$key]['firstname'])) {
                    $rset[$key]['name'] = $result[$key]['company_name'];  
                } else {
                    $rset[$key]['name'] = $result[$key]['firstname']." ".$result[$key]['middlename']." ".$result[$key]['lastname'];
                }		  
                $rset[$key]['member'] = $result[$key]['member_id'];
                $rset[$key]['account'] = $result[$key]['account_no'];
                $rset[$key]['office_id'] = $result[$key]['office_id']; 
                $rset[$key]['office'] = $this->getoffice($result[$key]['office_id']); 
                $rset[$key]['amount'] = $result[$key]['running_balance'];
                $rset[$key]['status'] = $result[$key]['account_status'];
                $rset[$key]['image'] = $result[$key]['image'];
            }
            
            return $rset;
        } else {
            return array();
        }
    } catch (Exception $e) {
        return array(
            'error' => 'An error occurred while fetching member account details: ' . $e->getMessage()
        );
    }
}


function getSavingsAccountTransactions($account) {
    try {
        $result =  $this->db->SelectData("SELECT * FROM m_savings_account_transaction WHERE savings_account_no='$account' ORDER BY id DESC LIMIT 20 ");
        
        if (count($result) > 0) {
            foreach ($result as $key => $value) {  
                $rset[$key]['amount'] = $result[$key]['amount'];
                $rset[$key]['transaction_type'] = ucfirst($result[$key]['transaction_type']);
                $rset[$key]['balance'] = $result[$key]['running_balance'];
                $rset[$key]['depositor'] = $result[$key]['depositor_name'];
                $rset[$key]['trans_date'] = $result[$key]['transaction_date'];
                $rset[$key]['op_type'] = $result[$key]['op_type'];
                $rset[$key]['payment_detail_id'] = ucfirst(strtolower($result[$key]['payment_detail_id']));
            }
            
            return $rset;
        } else {
            return array();
        }
    } catch (Exception $e) {
        return array(
            'error' => 'An error occurred while fetching transactions: ' . $e->getMessage()
        );
    }
}

function getAllSavingsAccountTransactions($account, $office){
	$office=$office;
	$result =  $this->db->SelectData("SELECT * FROM m_savings_account_transaction WHERE savings_account_no='".$account."' ");

	if(count($result)>0){
		foreach ($result as $key => $value) {  
			$rset[$key]['amount'] = $result[$key]['amount'];
			$rset[$key]['transaction_type']=$result[$key]['transaction_type'];
			$rset[$key]['balance'] = $result[$key]['running_balance'];
			$rset[$key]['depositor'] = $result[$key]['depositor_name'];
			$rset[$key]['trans_date'] = $result[$key]['transaction_date'];
			$rset[$key]['payment_detail_id'] = ucfirst(strtolower($result[$key]['payment_detail_id']));
		}
		//return $rset;
		$reversed_array = array_reverse($rset);
        return $reversed_array;
	}
	
}

function getAccountMemberID($account, $office) {
    try {
        $member_details =  $this->db->SelectData("SELECT * FROM m_savings_account WHERE account_no='$account' AND office_id = '$office' ");
        
        if (!empty($member_details) && count($member_details) > 0) {
            return $member_details[0]['member_id'];
        } else {
            return null;
        }
    } catch (Exception $e) {
        return null;
    }
}


function getMemberfixedaccount($acc){
	
	$result= $this->db->SelectData("SELECT * FROM fixed_deposit_account JOIN members on fixed_deposit_account.member_id=members.c_id where  fixed_deposit_account.account_no='".$acc."' ");

	if(count($result)>0){
		foreach ($result as $key => $value) {  
			if(empty($result[$key]['firstname'])){
				$rset[$key]['name'] =$result[$key]['company_name'];  
			}else{
				$rset[$key]['name'] = $result[$key]['firstname']." ".$result[$key]['middlename']." ".$result[$key]['lastname'];	  
			}
			$rset[$key]['member'] = $result[$key]['member_id'];
			$rset[$key]['account'] = $result[$key]['account_no'];
			$rset[$key]['office_id'] = $result[$key]['office_id']; 
			$rset[$key]['office'] = $this->getoffice($result[$key]['office_id']); 
			$rset[$key]['amount'] = $result[$key]['running_balance'];
			$rset[$key]['status'] = $result[$key]['account_status'];
			$rset[$key]['image'] = $result[$key]['image'];
		}
		return $rset;
	}
	
	
}
function getfixedAccountTransactions($acc){
	$office=$_SESSION['office'];
	$result =  $this->db->SelectData("SELECT * FROM fixed_deposit_transactions where fixed_account_no='".$acc."' ");
	foreach ($result as $key => $value) {  
		$rset[$key]['amount'] = $result[$key]['amount'];
		$rset[$key]['transaction_type']=$result[$key]['transaction_type'];
		$rset[$key]['balance'] = $result[$key]['running_balance'];
		$rset[$key]['depositor'] = $result[$key]['depositor_name'];
		$rset[$key]['trans_date'] = $result[$key]['transaction_date'];
	}
	return $rset;
	
	
}
function getsavingtransaction($acc,$transno,$tdate){

	$today=date('Y-m-d');
	if($tdate==null){
		$result =  $this->db->SelectData("SELECT * FROM m_savings_account_transaction where savings_account_no='".$acc."' and transaction_id='".$transno."' and date(transaction_date)='".$today."' ");
	}else{ 
		$trans_date= date('Y-m-d',strtotime(str_replace('-','/',$tdate)));		
		$result =  $this->db->SelectData("SELECT * FROM m_savings_account_transaction where savings_account_no='".$acc."' and transaction_id='".$transno."'and date(transaction_date)='".$trans_date."' ");
	}
	if(count($result)>0){  
		print_r($result[0]['amount']);
		die();
	}
	
}
function getpendingsaving($acc,$transno,$tdate){

	$result =  $this->db->SelectData("SELECT * FROM m_savings_account_transaction where savings_account_no='".$acc."' and id='".$transno."' and transaction_status='Pending' ");

	if(count($result)>0){  
		print_r($result[0]['amount']);
		die();
	}
	
}

function approvesavings($data, $user_id) {
    try {
        $tansno = $data['tnumber'];
        $tansamount = $data['tamount'];
        $accno = $data['account_no'];

        $postData = array(
            'approved_by' => $user_id,
            'transaction_status' => 'Approved',
        );

        $this->db->UpdateData('m_savings_account_transaction', $postData, "`id` = '{$tansno}'");

        $resultData = array(
            'transaction_status' => 'Approved',
        );

        return $resultData;

    } catch (Exception $e) {
        throw new Exception("Failed to approve savings: " . $e->getMessage());
    }
}


function reversesavings($data){

	$this->db->beginTransaction();//beginning transaction

	$tansno=$data['tnumber'];
	$tansamount=$data['tamount'];
	$accno=$data['account_no'];
	$result =  $this->db->SelectData("SELECT * FROM m_savings_account_transaction AS a JOIN m_savings_account AS b ON a.savings_account_no = B.account_no WHERE b.account_status = 'Active' AND a.transaction_id='".$tansno."' ");
	$account_b =  $this->db->SelectData("SELECT * FROM m_savings_account where account_no='".$accno."' and account_status='Active' ");
	if((count($result)>0)&&(count($account_b)>0)){
		try{

			$prodType = 3;
			$product_id = $result[0]['product_id'];

			$dep_mapping = $this->GetGLPointers($product_id,$prodType,'Deposit on Savings'); 

			if (empty($dep_mapping)) {
				header('Location: ' . URL . 'members/reversesavingstransaction?msg=dep'); 
				die();
			}

			
			$office_id = $_SESSION['office'];
			$tansaction_type=$result[0]['transaction_type'];			
			$tansaction_amount=$result[0]['amount'];			
			$account_running_balance=$account_b[0]['running_balance'];			
			$trans_running_balance=$result[0]['running_balance'];
			$client = $this->getClientSaveddetails($accno);
			$name=null;
			if(empty($client[0]['company_name'])){
				$name=$client[0]['firstname']." ".$client[0]['middlename']." ".$client[0]['lastname'];	
			}else{
				$name=$client[0]['company_name'];	
			}

			if($tansaction_type=='Deposit'){
				
				$deposits=$account_b[0]['total_deposits'];
				$total_deposits=($deposits-$tansaction_amount);
				$new_acc_balance=($account_running_balance-$tansaction_amount);
				$new_trans_balance=($trans_running_balance-$tansaction_amount);
				$postData = array(
					'reversed_by' =>$_SESSION['user_id'],
					'transaction_reversed' =>'Yes',
					'running_balance' =>$new_trans_balance,
					);

				$this->db->UpdateData('m_savings_account_transaction', $postData,"`id` = '{$tansno}'");	
				
				$postDataD = array(
					'total_deposits' =>$total_deposits,
					'running_balance' =>$new_acc_balance,
					);

				$this->db->UpdateData('m_savings_account', $postDataD,"`account_no` = '{$accno}'");

				$mapping = $this->GetGLPointers($product_id,$prodType,'Deposit on Savings');

				$debt_id =$mapping[0]["credit_account"]; //debit cash savings reference	
				$credit_id =$mapping[0]["debit_account"]; //credit savings  Control account
				$sideA=$this->getAccountSide($debt_id);
				$sideB=$this->getAccountSide($credit_id);
				$deposit_transaction_id = uniqid();
				$transaction_id = "S".$deposit_transaction_id;
				///JOURNAL ENTRY POSTINGS
				$description="Savings Deposit for ".$name." Reversed";

				$this->makeSavingsJournalEntry($debt_id,$office_id,$_SESSION['user_id'],$deposit_transaction_id,$transaction_id,$tansaction_amount,'DR',$sideA,$description);//DR
				$this->makeSavingsJournalEntry($credit_id,$office_id,$_SESSION['user_id'],$deposit_transaction_id,$transaction_id,$tansaction_amount,'CR',$sideB,$description);//CR

			} else if($tansaction_type=='Withdraw'){
	
				$withdraws=$account_b[0]['total_withdrawals'];
				
				$new_acc_balance=($account_running_balance+$tansaction_amount);
				
				$new_trans_balance=($trans_running_balance+$tansaction_amount);
				
				$total_withdrawals=($withdraws-$tansaction_amount);
				
				$postData = array(
					'reversed_by' =>$_SESSION['user_id'],
					'transaction_reversed' =>'Yes',
					'running_balance' =>$new_trans_balance,
					);

				$this->db->UpdateData('m_savings_account_transaction', $postData,"`id` = '{$tansno}'");
				
				$postDataW = array(
					'total_withdrawals' =>$total_withdrawals,
					'running_balance' =>$new_acc_balance,
					);

				$this->db->UpdateData('m_savings_account', $postDataW,"`account_no` = '{$accno}'");
				
				$mapping = $this->GetGLPointers($product_id,$prodType,'Withdraw on Savings');

		 		$debt_id =$mapping[0]["debit_account"]; //debit savings  Control account
				$credit_id =$mapping[0]["credit_account"]; //credit cash savings reference	
				$sideA=$this->getAccountSide($debt_id);
				$sideB=$this->getAccountSide($credit_id);
				$deposit_transaction_id = uniqid();
				$transaction_id = "S".$deposit_transaction_id;
				///JOURNAL ENTRY POSTINGS
				$description="Savings Withdraw for ".$name." Reversed";
		
				$this->makeSavingsJournalEntry($debt_id,$office_id,$_SESSION['user_id'],$deposit_transaction_id,$transaction_id,$tansaction_amount,'DR',$sideA,$description);//DR
				$this->makeSavingsJournalEntry($credit_id,$office_id,$_SESSION['user_id'],$deposit_transaction_id,$transaction_id,$tansaction_amount,'CR',$sideB,$description);//CR
			}

			$this->db->commit();
			if(isset($data['trans_date'])){
				header('Location: ' . URL . 'members/reversesavingstransaction/prev?trans=reversed'); 
			}else{
				header('Location: ' . URL . 'members/reversesavingstransaction?trans=reversed');
			}
		}catch(Exception $e){
			$this->db->rollBack();
			$error=$e->getMessage();	
			if(isset($data['trans_date'])){
				header('Location: ' . URL . 'members/reversesavingstransaction/prev?trans=failed&error='.$error); 
			}else{
				header('Location: ' . URL . 'members/reversesavingstransaction?trans=failed&error='.$error);
			}
		}
	}else{
		if(isset($data['trans_date'])){
			header('Location: ' . URL . 'members/reversesavingstransaction/prev?trans=failed'); 
		}else{
			header('Location: ' . URL . 'members/reversesavingstransaction?trans=failed');
		}	
	}
}
function stopsavingsaccrualinterest($data){

	$acc_name=$data['account_name'];
	$accno=$data['account_no'];

	$postData = array(
		'interest_stopped_by' =>$_SESSION['user_id'],
		'interest_stopped' =>'Yes',
		);

	$this->db->UpdateData('m_savings_account', $postData,"`account_no` = '{$accno}'");
	header('Location: ' . URL . 'members/stopinterestaccrualsavings?acc='.$accno.'&interest=stopped'); 
	
}

function getfixedtransaction($acc,$transno,$tdate){

	if($tdate==null){
		$result =  $this->db->SelectData("SELECT * FROM fixed_deposit_transactions where fixed_account_no='".$acc."' and transaction_id='".$transno."' ");
	}else{ 
		$trans_date= date('Y-m-d',strtotime(str_replace('-','/',$tdate)));
		$result =  $this->db->SelectData("SELECT * FROM fixed_deposit_transactions where fixed_account_no='".$acc."' and transaction_id='".$transno."'and date(transaction_date)='".$trans_date."'  ");
	}
	if(count($result)>0){  
		print_r($result[0]['amount']);
		die();
	}
	
}
function getpendingfixedtransaction($acc,$transno){
//$result=null;

	$result =  $this->db->SelectData("SELECT * FROM fixed_deposit_transactions where fixed_account_no='".$acc."' and id='".$transno."' and transaction_status='Pending' ");

	if(count($result)>0){  
		print_r($result[0]['amount']);
		die();
	}
	
}
function approvefixeddepost($data){

	$tansno=$data['tnumber'];
	$tansamount=$data['tamount'];
	$accno=$data['account_no'];

	$postData = array(
		'approved_by' =>$_SESSION['user_id'],
		'transaction_status' =>'Approved',
	);

	$this->db->UpdateData('fixed_deposit_transactions', $postData,"`id` = '{$tansno}'");
	header('Location: ' . URL . 'members/approvependingfixed?trans=approved'); 
	
}

function reversefixed($data){
	$this->db->beginTransaction();
	$tansno=$data['tnumber'];
	$tansamount=$data['tamount'];
	$accno=$data['account_no'];
	$result =  $this->db->SelectData("SELECT * FROM fixed_deposit_transactions where id='".$tansno."' and transaction_reversed='No' ");
	$account_b =  $this->db->SelectData("SELECT * FROM fixed_deposit_account where account_no='".$accno."'");
	if((count($result)>0)&&(count($account_b)>0)){


		$postData = array(
			'reversed_by' =>$_SESSION['user_id'],
			'transaction_reversed' =>'Yes',
			);

		$this->db->UpdateData('fixed_deposit_transactions', $postData,"`id` = '{$tansno}'");
		if(isset($data['trans_date'])){
			header('Location: ' . URL . 'members/reversefixedtransaction/prev?trans=reversed'); 
			
		}else{
			header('Location: ' . URL . 'members/reversefixedtransaction?trans=reversed'); 
		}	
		
	}
	
	
}
function stopfixedaccrualinterest($data){

	$acc_name=$data['account_name'];
	$accno=$data['account_no'];


	$postData = array(
		'interest_stopped_by' =>$_SESSION['user_id'],
		'interest_stopped' =>'Yes',
		);

	$this->db->UpdateData('fixed_deposit_account', $postData,"`account_no` = '{$accno}'");
	header('Location: ' . URL . 'members/stopinterestaccrualfixed?acc='.$accno.'&interest=stopped'); 
	
}

function savingslist(){
	$query= $this->db->SelectData("SELECT * FROM m_savings_account s  JOIN (members m  JOIN m_branch b ON m.office_id=b.id)
		ON  s.member_id  = m.c_id where m.office_id='".$_SESSION['office']."'");

	return $query;
	
}

function fixeddepositList($office) {
    try {
        $query = $this->db->SelectData("SELECT * FROM fixed_deposit_account f JOIN (members m  JOIN m_branch b ON m.office_id=b.id)
            ON  f.member_id  = m.c_id WHERE m.office_id='$office'");

        return $query;

    } catch (Exception $e) {
        throw new Exception("Failed to retrieve fixed deposit data: " . $e->getMessage());
    }
}


function getmemberFixedPhoto($actno){

	

	$result=  $this->db->selectData("SELECT * FROM fixed_deposit_account WHERE account_no='".$actno."'");

	if(count($result)>0){
		$cid=$result[0]['member_id'];

		$results= $this->db->selectData("SELECT * FROM members WHERE c_id='".$cid."' ");
		$imagename = $results[0]['image'];
		$signature = $results[0]['signature'];

		echo "<img src='".URL."public/images/avatar/".$imagename."' class='img-responsive' id ='image' alt='PHOTO'>";
		die();
	}else{
		
		echo "";
	}
	


}

function getsavingsAccountData($actno){

	$result=  $this->db->selectData("SELECT * FROM m_savings_account WHERE account_no='".$actno."'");

	if(count($result)>0){
		$product=  $this->db->selectData("SELECT * FROM m_savings_product WHERE id='".$result[0]['product_id']."'");		
		$max_id= $this->db->selectData("SELECT max(id) as id FROM m_savings_account_transaction WHERE savings_account_no='".$actno."'");
		$r_balance= $this->db->selectData("SELECT * FROM m_savings_account_transaction WHERE id='".$max_id[0]['id']."'");

		if(count($r_balance)==0){
			$rbalance=0;
			$actualbalance=0;
		}else{
			$rbalance=($result[0]['running_balance'])-($product[0]['min_required_balance']);
			$actualbalance=$result[0]['running_balance'];	
		}

		$cid=$result[0]['member_id'];
		$client=$this->getClient($cid);
		$rset=array();
		if(!empty($client[0]['company_name'])){
			$displayname=$client[0]['company_name'];	 
		}else{
			$displayname=$client[0]['firstname']." ".$client[0]['middlename']." ".$client[0]['lastname'];
		}
		foreach ($result as $key => $value) {
			array_push($rset,array(
				'member_id'=>$result[$key]['member_id'],
				'displayname'=>$displayname,
				'dob'=>date('d-m-Y',strtotime($client[0]['date_of_birth'])),
				'national_id'=>$client[0]['national_id'],
				'address'=>$client[0]['address'],
				'product'=>$product[0]['name'],
				'office_id'=>$product[0]['office_id'],
				'last_trans_amount'=>$result[$key]['running_balance'],
				'account_opened'=>$result[$key]['submittedon_date'],
				'status'=>$result[$key]['account_status'],
				'actualbalance'=>$actualbalance,		
				'acc_update_date'=>date('M j Y g:i A',strtotime($result[0]['last_updated_on'])),
				'rbalance'=>$rbalance,		
			));
		}
		echo json_encode(array("result" =>$rset));
		die();		  

	}else{
		$rset=array();
		array_push($rset,array(
			'member_id'=>'0',
			'rbalance'=>'0',		
		));
		echo json_encode(array("result" =>$rset));		
		die();
	}
	


}

function makeJournalEntry($acc_id,$office,$user,$loan_trans_id,$trans_id,$amount,$type,$side,$description){
	
	$postData = array(
		'account_id' =>$acc_id,
		'office_id' => $office,
		'branch_id' => $_SESSION['branchid'],
		'createdby_id' =>$user,		
		'transaction_id' =>$trans_id,
		'amount' => $amount,
		'transaction_type' =>$type,
		'trial_balance_side' =>$side,							
		'description' =>$description,							
	);

	$this->db->InsertData('acc_gl_journal_entry', $postData);	

}


function getAccountProducts($acc) {
	return $this->db->SelectData("SELECT * FROM m_savings_account JOIN m_savings_product on m_savings_account.product_id = m_savings_product.id where  m_savings_account.account_no='".$acc."' ");
}

function getMembers() {
	$office = $_SESSION['office'];
	return $this->db->SelectData("SELECT * FROM members WHERE office_id = $office AND firstname != '' AND status='Active'");
}

//////////

function getClientImage($id){
	return $this->db->selectData("SELECT * FROM m_pictures WHERE type='image' AND status='Current' AND member_id='".$id."'");
}

function getClientPassport($id){
	return $this->db->selectData("SELECT * FROM m_pictures WHERE type='id_passport' AND status='Current' AND member_id='".$id."'");
}

function getClientSignature($id){
	return $this->db->selectData("SELECT * FROM m_pictures WHERE type='signature' AND status='Current' AND member_id='".$id."'");
}




function CreateClicAccount($user_name, $phone, $us_password, $full_name, $account_no, $sacco_id, $instance_id, $email, $member_id=NULL){
	
	$url=REG_CLIENT;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS,"username=".$user_name."&pwd=".$us_password."&fullname=".$full_name."&tel=".$phone."&account_no=".$account_no."&saccoid=".$sacco_id."&instance_id=".$instance_id."&email=".$email."&member_id=".$member_id."");
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$server_output = curl_exec ($ch);
	
	curl_close ($ch);
	return   $server_output;
}






 
}