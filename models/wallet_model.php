<?php
class Wallet_model extends Model{

public function __construct(){

	parent::__construct();
	@session_start();
    $this->logUserActivity();
	if (!$this->checkTransactionStatus()) {
		header('Location: ' . URL); 
	}
}

function getWalletMemberaccount($acc){
	
  $result= $this->db->SelectData("SELECT * FROM sm_mobile_wallet JOIN members on sm_mobile_wallet.member_id=members.c_id where  sm_mobile_wallet.wallet_account_number='".$acc."' ");

 if(count($result)>0){
		  foreach ($result as $key => $value) {  
		if(empty($result[$key]['firstname'])){
		$rset[$key]['name'] =$result[$key]['company_name'];  
		  }else{
         $rset[$key]['name'] = $result[$key]['firstname']." ".$result[$key]['middlename']." ".$result[$key]['lastname'];
		  }		  
                $rset[$key]['member'] = $result[$key]['member_id'];
                $rset[$key]['account'] = $result[$key]['wallet_account_number'];
				$rset[$key]['office_id'] = $result[$key]['bank_no']; 
				//$rset[$key]['office'] = $this->getoffice($result[$key]['office_id']); 
               $rset[$key]['office'] = $result[$key]['bank_no']; 

			   $rset[$key]['amount'] = $result[$key]['wallet_balance'];
                $rset[$key]['status'] = $result[$key]['wallet_status'];
                $rset[$key]['image'] = $result[$key]['image'];
			}
        return $rset;
  }
	
}

function getWalletAccountMemberID($tel){
	$office = $_SESSION['office'];
	$result = $this->db->SelectData("SELECT * FROM sm_mobile_wallet WHERE wallet_account_number='$tel' AND bank_no = '$office' ");

	return $result[0]['member_id'];

}

function getWalletAccountTransactions($acc){
	$office=$_SESSION['office'];
	$result =  $this->db->SelectData("SELECT * FROM sm_mobile_wallet_transactions where wallet_account_number='$acc' ORDER BY wallet_transaction_id DESC LIMIT 20 ");
        if(count($result)>0){
		 	foreach ($result as $key => $value) {  
                $rset[$key]['amount'] = $result[$key]['amount'];
                $rset[$key]['transaction_type']=$result[$key]['transaction_type'];
                $rset[$key]['balance'] = $result[$key]['running_balance'];
				$rset[$key]['description'] = $result[$key]['description'];
               	$rset[$key]['trans_date'] = $result[$key]['transaction_date'];
			}
		$reversed_array = array_reverse($rset);
        return $reversed_array;
	}
	
}


function getAllWalletAccountTransactions($acc){
	$office=$_SESSION['office'];
	$result =  $this->db->SelectData("SELECT * FROM sm_mobile_wallet_transactions where wallet_account_number='".$acc."' ");
    if(count($result)>0){
		foreach ($result as $key => $value) {  
	        $rset[$key]['amount'] = $result[$key]['amount'];
	        $rset[$key]['transaction_type']=$result[$key]['transaction_type'];
	        $rset[$key]['balance'] = $result[$key]['running_balance'];
			$rset[$key]['description'] = $result[$key]['description'];
		    $rset[$key]['trans_date'] = $result[$key]['transaction_date'];
		}
        return $rset;
	}
	
}

function getMembersavings($acc){
	$office=$_SESSION['office'];
	$result =  $this->db->SelectData("SELECT * FROM sm_mobile_wallet s JOIN members m ON s.member_id=m.c_id  WHERE wallet_account_number='".$acc."' ");

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
				'displayname'=>$displayname
				));
		}

		echo json_encode(array("result" =>$rset));
		die();
		
	}


}


	function getClixWalletTransactions($member_id)
	{
	    $settings = $this->db->SelectData("SELECT  * from system_settings where instance_id = " . $_SESSION['office']);
	    $url = STELLAR_WALLET_STATEMENT . "?currency=" . $settings[0]['currency'];

		/* $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array("username" => $member_id)));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		$server_output = curl_exec ($ch);
		curl_close ($ch);
		
		$response = (array) json_decode($server_output);

		//print_r($response);
		//die();
	    return (array) $response['payments']; */
		
		$data = array(
            "username" => $member_id
        );
		
		$encodedData = json_encode($data);
        $curl = curl_init($url);
        $data_string = urlencode(json_encode($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt( $curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $encodedData);
		$server_output = curl_exec($curl);
        curl_close($curl);
		
		$response = (array) json_decode($server_output);
		
		//print_r($response);
		//die();
	    return (array) $response['payments'];
	}

}