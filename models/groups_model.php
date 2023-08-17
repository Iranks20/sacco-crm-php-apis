<?php
//require __DIR__ . '/../vendor/autoload.php';
class groups_model extends Model{
	
	public function __construct(){
		parent::__construct();
    	$this->logUserActivity(NULL);
    	if (!$this->checkTransactionStatus()) {
    		header('Location: ' . URL); 
    		exit();
    	} 
	}
	
	public function getGroupLoanProduct($id){
	    $office = $_SESSION['office'];
	    //echo "SELECT * FROM m_product_loan WHERE group_id = '".$id."' AND office_id = '".$office."' AND status = 'open'";
	    //die();
	    $result = $this->db->SelectData("SELECT * FROM m_product_loan WHERE group_id = '".$id."' AND office_id = '".$office."' AND status = 'open'");
	    //print_r($result);
	    //die();
		return $result;
	}

	public function GetAllGroups(){
		$office = $_SESSION['office'];
		return $this->db->SelectData("SELECT  * FROM m_group AS a JOIN m_savings_account AS b ON a.id = b.group_id WHERE a.office_id = '".$office."' AND a.status = 'Active' ORDER BY a.id DESC");
	}
	
    function CheckGroupName($name){
		$result= $this->db->SelectData("SELECT * FROM m_group WHERE name = '$name' ");
		return  $result;
	}
	
    function verifycsv($uploadedfile){

		$filerec = file_get_contents($uploadedfile);
		$string = str_getcsv($filerec, "\n");
        print_r($filerec);
		$first_row_items = explode(',', $string[0]);
		
		if(sizeof($first_row_items) == 2){
		
		header("Location:".URL ."groups/uploadgroup?ver=success&action=process&path=$uploadedfile");
		exit();
		
		}else{
			header("Location:".URL ."groups/uploadgroup?ver=fileformat");
			exit();
		}
	}
	
	
  function importBulkGrp($data){
	    $ext = strtolower(pathinfo($data['audit_file_temp'],PATHINFO_EXTENSION));		
		
		
		$now = date('d_m_Y');
		$file_name = $_SESSION['office'].'_'.$_SESSION['user_id'] . '_' . $now;
		$dest = 'public/groupreg/' . $file_name . '.csv';
        
        if(move_uploaded_file($data['audit_file_temp'], $dest)){
            
            
         
            header("Location:".URL."/groups/uploadgroup?msg=success&action=verifycsv&path=".$dest);
            exit();
        	         
        } else {
         header("Location:".URL."groups/uploadgroup?msg=failed");
         exit();
        		}
        		
      
}
	
	function GetGroupDefaultDetails($saccoid){

        $defaults = $this->getDefaultGroupProductID($saccoid);

        return $defaults[0]['p_id'];

    }

    function getDefaultGroupProductID($saccoid){
        $data =  $this->db->SelectData("SELECT * FROM m_reg_settings WHERE product_type = -1 AND sacco_id = '$saccoid' AND status = 'Active'");

        if (!empty($data)) {
            return $data;
        }
    }
    
    function getMemberByPhone($phone){
        return $this->db->SelectData("SELECT  * from members where office_id ='".$_SESSION['office']."' AND mobile_no = '".$phone."' ");

    }
    
	function InsertGroupDetails($data){
            
	     try{
	        
            $member_Staff_id = $this->db->SelectData("SELECT  c_id from members where office_id ='".$_SESSION['office']."' AND mobile_no = '".$data['staff_phone']."'");
    
    		$memberID = is_null($member_Staff_id[0][0]) ? $data['staff_phone'] : $member_Staff_id[0][0];
            $officeID =  $_SESSION['office'];
            $group_name = $data['display_name'];
            $grpInfo = $this->CheckGroupName($group_name);
            if(sizeof($grpInfo)>0){
                return $this->MakeJsonResponse(101,"group name exists"); 
            }
            
            $str=date('isH').rand();
            
            $Savings_acc_no = $officeID.substr($str,7,7);

            $this->db->beginTransaction();
			$data = array(
				'name' =>$group_name,
				'office_id' => $officeID, 
				'user_id' =>  $memberID,
				'payout_date' => $data['payout_date'],
	            'registration_date' => date('Y-m-d H:i:s'),
				'percentage' => $data['percentage'],
				'member_contribution' => $data['member_contribution'],
				'purpose' => $data['purpose'],
				'description' => $data['description'],
				'deposit_frequency' => $data['deposit_frequency'],
				'payout_frequency' => $data['payout_frequency'],
				'payment_order' => $data['payment_order'],
				'group_type' => 1,
				'activatedon_userid' => $memberID,
				'submittedon_date' => date('Y-m-d H:i:s'),
				'submittedon_userid' => $memberID,
				'account_no' => $Savings_acc_no, // Account number needed
				'status' => 'Active', //needed in api
				'next_person' => "" //needed in api
	        );
	        
			$grpId = $this->db->InsertData('m_group', $data);


			$postData = array(
				'client_id' => $memberID,
				'group_id' => $grpId,
	            'registration_date' => date('Y-m-d H:i:s'),
	            'status' => 'Active',
	            'role' => 'Admin',
	            'rank'=>'0'
	        );

            $this->db->InsertData('m_group_client', $postData);
            $result_phones = array_unique($_POST["phone"]);

            foreach ($result_phones as $key => $value) {
            	# code...
            	$value =str_replace(' ', '', $value);
            	
            	$cid = $this->getMemberByPhone($value);
            	if(sizeof($cid)>0){
            	    $data_collection = array(
                        'group_id'  => $grpId,
                		'client_id' => $cid[0]['c_id'],
        	            'registration_date' => date('Y-m-d H:i:s'),
        	            'status' => 'Active',
        	            'role' => 'Member',
        	            'rank'=> $key+1
                	);
                	$this->db->InsertData('m_group_client', $data_collection);
                }
            }
            
                
            if($_POST['from'] == "backend"){

                foreach ($result_phones as $key => $value) {
                	if($value != ""){
                	    $data_collection_2 = array(
                            'group_id'  => $grpId,
                    		'client_id' => $value,
            	            'registration_date' => date('Y-m-d H:i:s'),
            	            'status' => 'Active',
            	            'role' => 'Member',
            	            'rank'=> $key+1
                    	);
                    	$this->db->InsertData('m_group_client', $data_collection_2);
                    }
                    
                }
            }

            $memebr_id =  $memberID;
            $product_id =  $this->GetGroupDefaultDetails($officeID);
					   
            $postDataDB = array(
                'account_name' => $group_name,
                'account_no' => $Savings_acc_no,
                'member_id' => $memebr_id,
                'submittedon_userid' => $memebr_id,
                'account_status' =>'Active',
                'product_id' =>  $product_id,
                'office_id' => $officeID,
                'group_id' => $grpId
            );
            
            $clientsaving_id= $this->db->InsertData('m_savings_account', $postDataDB);
            
            $newdata = array('account_no' => $Savings_acc_no );
            $this->db->UpdateData('m_group',$newdata, "`id` = {$grpId}");
            
            $this->db->commit();
            
            $postResponse = array();
            $postResponse['account_no'] = $Savings_acc_no;
            $postResponse['group_id'] = $grpId;
            
            if(isset($data['from']) && $data['from'] == "backend"){
                header('Location: ' . URL . 'groups/viewgroup/'. $grpId. '?msg=success');
            } else{
                return $this->MakeJsonResponse(100,"success",URL."groups/viewgroup/".$grpId);
            }
            
	        
	     }catch(Exception $e){
	         
	        $this->db->rollBack();
            return $this->MakeJsonResponse(103,"Unknown error".$e->getMessage());
            exit();
        }   

	}



	function getSaccoGroupLoans(){

		$office = $_SESSION['office'];

		$result = $this->db->SelectData("SELECT  * FROM m_loan AS a JOIN m_group AS b ON a.group_id = b.id WHERE a.sacco_id = '".$office."'");

		return $result;

	}

public function GetGroupMembers($id){
	$result = $this->db->SelectData("SELECT * from m_group_client where group_id = '$id'");
	$membersArray = array();
   
	//$variable = array();
	foreach ($result as $key => $value) {
		array_push($membersArray,array($value["client_id"],$value["status"],$value["loan_amount"],$value["group_id"],$value["loan_status"]));
	}
	 //var_dump($membersArray);
	 //exit();

	$membersNames = array();

	for ($x = 0; $x < sizeof($membersArray); $x++) {
		$value = $membersArray[$x][0];

		$members = $this->db->SelectData("SELECT * from members where office_id ='".$_SESSION['office']."' AND c_id = '".$value."'");

   		$name = is_null($members[0]['company_name']) ?  $members[0]['firstname']." ".$members[0]['lastname'] : $members[0]['company_name'];
		$membersNames[$x]["name"] = $name;
		$membersNames[$x]["phone"] = $value;
		$membersNames[$x]["status"] = $membersArray[$x][1];
		$membersNames[$x]["loan_amount"] = $membersArray[$x][2];
		$membersNames[$x]["loan_status"] = $membersArray[$x][4];
		$membersNames[$x]["id"] = $membersArray[$x][3];

	} 
	return $membersNames;
}

	public function ChangeMemberStatus($id, $mem_id, $state){

		if ($state == 'activate') {
			$new_state = 'Active';
		} else if ($state == 'inactivate') {
			$new_state = 'Closed';
		}

		$data = array(
			'status' => $new_state
        );
        
        
		$this->db->UpdateData('m_group_client', $data, "`group_id` = {$id} AND `client_id` = {$mem_id}");
		
		header("Location: ". URL ."groups/viewgroup/".$id."?msg=success");
  	    exit();

	}

	public function UpdateGroupDetails($id, $data){

		$postData = array(
			'name' => $_POST['display_name']
        );

        $this->db->UpdateData('m_group',$postData, "`id` = {$id}");

		header('Location: ' . URL . 'groups/viewgroup/'.$id.'?msg=editsuccess');
		exit();
	}

	public function updateMemberLoanAmount(){

		$grp = $_POST['group_id'];
		$clt = $_POST['client_id'];
		$postData = array(
			'group_id' => $grp,
			'client_id' => $clt,
			'loan_amount' => $_POST['loan_amount']
        );

        $this->db->UpdateData('m_group_client',$postData, "`client_id` = {$clt} AND `group_id` = {$grp}");
		header('Location: ' . URL . 'groups/viewgroup/'.$grp.'?msg=editsuccess');
		exit();
	}

	public function DeleteGroupDetails($id){
		 $data = array(
			'status' => "Closed"
        );

		$this->db->UpdateData('m_group', $data,"`id` = '".$id."'");
  		header('Location: ' . URL . 'groups?msg=success');
  		exit();

	}


	public function SavingsAccountDetails($id){

		$office = $_SESSION['office'];

		$result = $this->db->SelectData("SELECT  * FROM m_group AS a JOIN m_savings_account AS b ON a.id = b.group_id WHERE a.office_id = '".$office."' AND a.id = '$id'");

		return $result;

	}
	
	function getAccountGroupID($acc){
		$office=$_SESSION['office'];
		
		$member_details =  $this->db->SelectData("SELECT * FROM m_savings_account WHERE account_no='$acc' AND office_id = '$office' ");

		return $member_details[0]['group_id'];
	}	

	function getSavingsAccountTransactions($acc){
		$office=$_SESSION['office'];
		$result =  $this->db->SelectData("SELECT * FROM m_savings_account_transaction WHERE savings_account_no='$acc' ORDER BY id DESC LIMIT 100 ");
		if(count($result)>0){
			foreach ($result as $key => $value) {  
				$rset[$key]['amount'] = $result[$key]['amount'];
				$rset[$key]['transaction_type'] = $result[$key]['transaction_type'];
				$rset[$key]['balance'] = $result[$key]['running_balance'];
				$rset[$key]['depositor'] = $result[$key]['depositor_name'];
				$rset[$key]['trans_date'] = $result[$key]['transaction_date'];
				$rset[$key]['payment_detail_id'] = $result[$key]['payment_detail_id'];
								$rset[$key]['op_type'] = $result[$key]['op_type'];
								$rset[$key]['user_id'] = $result[$key]['user_id'];

			}
			$reversed_array = array_reverse($rset);
	        return $reversed_array;
		}
		
	}

	function getAllSavingsAccountTransactions($acc){
		$office=$_SESSION['office'];
		$result =  $this->db->SelectData("SELECT * FROM m_savings_account_transaction WHERE savings_account_no='".$acc."' ");

		if(count($result)>0){
			foreach ($result as $key => $value) {  
				$rset[$key]['amount'] = $result[$key]['amount'];
				$rset[$key]['transaction_type']=$result[$key]['transaction_type'];
				$rset[$key]['balance'] = $result[$key]['running_balance'];
				$rset[$key]['depositor'] = $result[$key]['depositor_name'];
				$rset[$key]['trans_date'] = $result[$key]['transaction_date'];
				$rset[$key]['payment_detail_id'] = $result[$key]['payment_detail_id'];
			}
			return $rset;
		}
		
	}

	public function GetSpecificMembers(){

			$client_id = $_GET["phone"];
			$group_id = $_GET["group_id"];
			# code...
			//check if exits
        	return $this->db->SelectData("SELECT  * from m_group_client where group_id = '$group_id' AND client_id= '$client_id'  ");
		}

		
	public function ChangeStatus($id){
			 $data = array(
			'status' => $_POST["status"]
        );

		$this->db->UpdateData('m_group_client',$data, "`group_id` = '".$id."' AND `client_id` = '".$_POST["phone"][0]."'");

			header('Location: ' . URL . 'groups?msg=success');
			exit();
		}


	public function DeleteGroupMember($id,$idg){
		 $data = array(
			'status' => 0
        );

		$this->db->UpdateData('m_group_client', $data,"`client_id` = '".$id."' AND `group_id` = '".$idg."' ");
  		header('Location: ' . URL . 'groups?msg=success');
  		exit();

	}

	public function InsertGroupMembers($data){
	    $id = $data['group_id'];
	    try{

		$result_phones = array_unique($data["phone"]);

		$selected_idz = array();
		foreach ($result_phones as $key => $value) {
        	$selected_id = $this->db->SelectData("SELECT c_id from members where mobile_no = '$value'  ");
        	array_push($selected_idz, $selected_id[0]['c_id']);
		}

		foreach ($result_phones as $key => $value) {
        	$value =str_replace(' ', '', $value);

        	$group_client_selected = $this->db->SelectData("SELECT  * from m_group_client where group_id = '$id'  ");

        	$phone_arrays = array();

        	foreach ($group_client_selected as $key => $value) {
        		array_push($phone_arrays, $value["client_id"]);
        	}        

      		$new_numbers = array_diff($selected_idz , $phone_arrays);

        	foreach ($new_numbers as $key => $value) {
        		# code...
        		$data_collection = array(
	        		'group_id'  => $id,
	        		'client_id' => $value,
	        		'status' => 'Active'
	        	);

	        	$this->db->InsertData('m_group_client', $data_collection);

        	}

		}
        return $this->MakeJsonResponse(100,"Success", "#");

	    }catch(Exception $e){
        return $this->MakeJsonResponse(203,$e->getMessage(),"#");
	    }
		
	}

	public function getgrouploan($id){
		
	}

	function GetGroupLoanDetails($id){
		$office = $_SESSION['office'];
		return $this->db->SelectData("SELECT * from m_loan where group_id = '$id' AND sacco_id = '$office' AND loan_status != 'Closed'");
	}

	
	

}

?>



