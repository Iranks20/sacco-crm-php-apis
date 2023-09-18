<?php
class Model {

	function __construct() {
		$this->db = new Database();
		// $this->pushy = new PushyAPI();
		$this->val = new Validations();
		// $this->img = new ImageLoader();
		// $this->img2 = new ImageLoaderBack();
		// // $this->pdfi = new PDFloader();

	}

	
//check products by name
function checkProductLoanExists($pname){
	  $rs =  $this->db->SelectData("SELECT * FROM m_product_loan where name = '".$pname."'");
	  if(sizeof($rs)>0){
		  return true;
	  }
	  return false;
}

//SAVINGS ACCOUNTING
	function GetSavingsAccount($actno){
		return $this->db->selectData("SELECT * FROM m_savings_account WHERE account_no='".$actno."' and account_status='Active' ");
	}

	function getMemberChargeExemptions($id){
		
		$results = $this->db->SelectData("SELECT charge_exemptions FROM members WHERE c_id = $id");

		if ($results[0]['charge_exemptions'] != "" && $results[0]['charge_exemptions'] != " ") {
			$exps = explode(",", $results[0]['charge_exemptions']);
			return $exps;
		} else {
			return NULL;
		}

	}
	
	function MakeJsonResponse($status,$message, $data=null){
    	$response['status']=$status;
    	$response['response']= $message;
    	if (isset($data)){ $response['data']= $data;}
    	return $response;
	}
	

	function GetGroupDetails($id){

		$office = $_SESSION['office'];

		$result = $this->db->SelectData("SELECT  * FROM m_group AS a JOIN m_savings_account AS b ON a.id = b.group_id WHERE a.office_id = '".$office."' AND a.id = '$id'");

		return $result;
	}

	///////////////////////////////////////////CGAP REPORT//////////////////////////////////////////////



	function getColorCode($amt, $details, $key){

		$color = 'black';
		if (empty($details)) {
			$color = 'black';
		} else {
			if ($amt == 0) {
				$color = 'black';
			} else {

				if ($amt > $details[$key]) {
					$color = 'green';
				} else if ($amt == $details[$key]) {
					$color = 'orange';
				} else if($amt < $details[$key]){
					$color = 'red';
				} 
			}
		}

		return $color;
	}

	function getOverallSaccoColorCode($colors){

		$allcolors = $this->countColors($colors);

		$saccoColor = "";
		foreach ($allcolors as $key => $value) {
			if ($value == max($allcolors)) {
				$saccoColor = $key;
			}
		}

		return $saccoColor;
	}

	function countColors($colors){

		$allcolors = array();

		$black = $red = $orange = $green = 0;
		foreach ($colors as $key => $value) {
			if ($value == 'black') {
				$black += 1;
			}
			if ($value == 'red') {
				$red += 1;
			}
			if ($value == 'orange') {
				$orange += 1;
			}
			if ($value == 'green') {
				$green += 1;
			}
		}

		$allcolors['black'] = $black;
		$allcolors['red'] = $red;
		$allcolors['orange'] = $orange;
		$allcolors['green'] = $green;

		return $allcolors;
	}

	
	function getSaccoLoanBalanceTotal(){
		$office = $_SESSION['office'];
		$loans = $this->db->SelectData("SELECT SUM(total_outstanding) AS total FROM  m_loan WHERE sacco_id = $office");

		return $loans[0]['total'];
	}

	function getProducts(){
	  return $this->db->SelectData("SELECT p_id, p_name FROM products");
	}

	function getOutStandingPrincipalTotal(){
	$office=$_SESSION['office'];
	$today=date('Y-m-d');
	$total_unpaid_principal_past_due =   $this->db->SelectData("SELECT SUM(principal_amount - principal_completed) AS total FROM m_loan_repayment_schedule s JOIN m_loan l ON s.account_no=l.account_no  WHERE l.sacco_id='".$office."' AND DATEDIFF('".$today."',s.duedate)>0");

	return $total_unpaid_principal_past_due[0]['total'];

	}

	function getUnPaidBalanceOverDueTotal(){
	$office=$_SESSION['office'];
	$today=date('Y-m-d');
	$total_unpaid_balance_past_due =   $this->db->SelectData("SELECT SUM((principal_amount - principal_completed)+(interest_amount - interest_completed)) AS total FROM m_loan_repayment_schedule s JOIN m_loan l ON s.account_no=l.account_no  WHERE l.sacco_id='".$office."' AND DATEDIFF('".$today."',s.duedate)>0");

	return $total_unpaid_balance_past_due[0]['total'];

	}

	function getTotalNumberOfDisbursements(){
	$office = $_SESSION['office'];
	$loans = $this->db->SelectData("SELECT COUNT(total_outstanding) AS total FROM m_loan WHERE loan_status ='Disbursed' AND sacco_id = $office");

	return $loans[0]['total'];

	}

	function getAnnualTotalDisbursed(){
	$office = $_SESSION['office'];
	$date = date('Y-');
	$loans = $this->db->SelectData("SELECT SUM(approved_principal) AS total FROM m_loan WHERE submittedon_date LIKE '$date%' AND sacco_id = $office");

	return $loans[0]['total'];

	}

	function getTotalNumberOfFieldAgents(){
	$office = $_SESSION['office'];
	$loans = $this->db->SelectData("SELECT COUNT(firstname) AS total FROM m_staff WHERE access_level != 'A' AND office_id = $office");

	return $loans[0]['total'];

	}

	function getTotalNumberOfActiveBorrowers(){
	$office = $_SESSION['office'];
	$loans = $this->db->SelectData("SELECT COUNT(DISTINCT member_id) AS total FROM m_loan WHERE loan_status != 'Closed' AND sacco_id = $office");

	return $loans[0]['total'];

	}

	function getAverageLoansOutstanding(){
	$office = $_SESSION['office'];
	
	$loans = $this->db->SelectData("SELECT COUNT(account_no) AS count, SUM(principal_outstanding+outstanding_interest) AS total FROM m_loan WHERE loan_status != 'Closed' AND sacco_id = $office");
    
        try{
            if(is_null($loans[0]['count']) || $loans[0]['count'] <= 0){
                return 0;
            } else {
                return ($loans[0]['total']/$loans[0]['count']);
        	}
        } catch(Exeption $e) {
            return 0;
        }
	}

	function getTotalOperatingExpenses(){
	$office = $_SESSION['office'];

	$array=$this->db->selectData("SELECT * FROM acc_ledger_account where sacco_id = '".$_SESSION['office']."' AND classification='Expenses' AND sacco_id = '".$office. "'");

	$total_balance=0;
	foreach ($array as $key => $value) {
	  $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry WHERE account_id='".$value['id']."' AND office_id='".$office."'");

	  $balance=0;
	  foreach ($res as $key1 => $value1) {
	    if($value1['transaction_type']=='DR'){
	      $balance-+$value1['amount']; 
	    }else{
	      $balance+=$value1['amount']; 
	    }
	  }               

	  $total_balance += $balance; 
	}

	return $total_balance;
	}

	function getTotalCurrentAssets(){
	$office = $_SESSION['office'];

	$array=$this->db->selectData("SELECT * FROM acc_ledger_account where sacco_id = '".$_SESSION['office']."' AND classification='Assets' AND gl_code LIKE'22%' AND sacco_id = '".$office. "'");

	$total_balance=0;
	foreach ($array as $key => $value) {
	  $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry WHERE account_id='".$value['id']."' AND office_id='".$office."'");

	  $balance=0;
	  foreach ($res as $key1 => $value1) {
	    if($value1['transaction_type']=='DR'){
	      $balance+=$value1['amount']; 
	    }else{
	      $balance-=$value1['amount']; 
	    }
	  }               

	  $total_balance += $balance; 
	}

	return $total_balance;
	}

	function getTotalAssets(){
	$office = $_SESSION['office'];

	$array=$this->db->selectData("SELECT * FROM acc_ledger_account where sacco_id = '".$_SESSION['office']."' AND classification='Assets' AND sacco_id = '".$office. "'");

	$total_balance=0;
	foreach ($array as $key => $value) {
	  $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry WHERE account_id='".$value['id']."' AND office_id='".$office."'");

	  $balance=0;
	  foreach ($res as $key1 => $value1) {
	    if($value1['transaction_type']=='DR'){
	      $balance+=$value1['amount']; 
	    }else{
	      $balance-=$value1['amount']; 
	    }
	  }               

	  $total_balance += $balance; 
	}

	return $total_balance;
	}

	function getTotalFinancialIncome(){
	$office = $_SESSION['office'];

	$array=$this->db->selectData("SELECT * FROM acc_ledger_account where sacco_id = '".$_SESSION['office']."' AND classification='Incomes' AND sacco_id = '".$office. "'");

	$total_balance=0;
	foreach ($array as $key => $value) {
	  $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry WHERE account_id='".$value['id']."' AND office_id='".$office."'");

	  $balance=0;
	  foreach ($res as $key1 => $value1) {
	    if($value1['transaction_type']=='DR'){
	      $balance+=$value1['amount']; 
	    }else{
	      $balance-=$value1['amount']; 
	    }
	  }               

	  $total_balance += $balance; 
	}

	return $total_balance;
	}
	
	    ///////////////////////////////////////END CGAP CODE/////////////////////////////////////////////
	function getSaccoCGAPDetails(){
		$office_id = $_SESSION['office'];
		$result = $this->db->selectData("SELECT cgap_thresholds FROM m_branch WHERE id='".$office_id."'");

		$data = explode(",", $result[0]['cgap_thresholds']);
		return $data;	
	}
	
	function getTotalAccountBalance($account){
		$office_id = $_SESSION['office'];
		$result=$this->db->selectData("SELECT * FROM reports_mapping WHERE account = '$account' AND sacco_id='".$office_id."'");

		if (empty($result)) {
      		header('Location: ' . URL . 'accounting/cgapsetup/?msg=missing'); 
		} else {

			$total = 0;
			foreach ($result as $key => $value) {
				$accs = explode(",", $value['sub_accounts']);
				foreach ($accs as $key1 => $value1) {
					$total += $this->getGLAccountBalance($value1);
				}
			}
		}

		return $total;
	}

	function getGLAccountBalance($account){
		$office = $_SESSION['office'];

		$total_balance=0;
		$res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry WHERE account_id='".$account."' AND office_id='".$office."'");

		$balance=0;
		foreach ($res as $key1 => $value1) {
			if($value1['transaction_type']=='DR'){
				$balance-+$value1['amount']; 
			}else{
				$balance+=$value1['amount']; 
			}
		}

		$total_balance += $balance; 

		return $total_balance;
  }

	function checkForBranches($office_id){
		$result = $this->db->SelectData("SELECT COUNT(id) FROM m_branch WHERE parent_id = '".$office_id."'");
		if (empty($result)) {
			return 0;
		} else {
			return $result[0][0];
		}
	}

	function getAccesslevels(){
		$office_id = $_SESSION['office'];
		$result =  $this->db->SelectData("SELECT * FROM sch_user_levels WHERE office_id = '".$office_id."'");
		return $result;
	}

	function getSaccoBranches($office) {
		try {
			$results = $this->db->SelectData("SELECT * FROM m_branch WHERE parent_id = '$office' AND b_status = 'Active' AND head_office = 'No'");
	
			if (!empty($results)) {
				return $results;
			} else {
				return NULL;
			}
	
		} catch (Exception $e) {
			throw $e;
		}
	}	
	
	function getDefaultWalletDetails($type){

		$office = $_SESSION['office'];
		$results =  $this->db->SelectData("SELECT * FROM m_reg_settings WHERE product_type = $type AND sacco_id = '".$office."' AND status = 'Active' ");

		if (!empty($results)) {
			return $results;
		} else {
			return NULL;
		}

	}

	function GetSettings(){
		$result =  $this->db->SelectData("SELECT * FROM system_settings WHERE `status` = 'Active'");
		return $result[0];
	} 

	function getbranch($id){

		if ($id == 0 && $_SESSION['Isheadoffice'] == 'Yes') {
			return $_SESSION['branch'];
		} elseif ($id == 0 && $_SESSION['Isheadoffice'] == 'No') {

			$parent = $this->db->SelectData("SELECT * FROM m_branch where id='".$_SESSION['branchid']."' ");

			if (!empty($parent)) {
				$result = $this->db->SelectData("SELECT * FROM  m_branch where id='".$parent[0]['parent_id']."' ");
				return  $result[0]['name'];
			} else {
				return NULL;
			}
		} else {
			$result = $this->db->SelectData("SELECT * FROM  m_branch where id='".$id."' ");
			return  $result[0]['name'];

		}
	}

	function GetsaccoDetails($id){
		$result= $this->db->SelectData("SELECT * FROM  m_branch where id='".$id."' ");
		return  $result[0];
	}

	function getQuickProducts($sacco){

		$loan_pdt = $this->db->selectData("SELECT * FROM m_product_loan WHERE office_id = $sacco AND status = 'open' AND installment_option = 'Equal Installment' AND interest_method = 'Flat' AND product_type = 2");

		return $loan_pdt[0];
	}

	function getDefaultProducts($type, $sacco = null){
		if ($type == 6) {
			$table = "m_charge";
		} else if ($type == 1) {
			$table = "share_products";
		} else if ($type == 3 || $type == -1) {
			$table = "m_savings_product";
		}

		if (is_null($sacco)) {
			$office = $_SESSION['office'];
		} else {
			$office = $sacco;
		}
		 
		$results =  $this->db->SelectData("SELECT * FROM m_reg_settings JOIN $table ON m_reg_settings.p_id = $table.id WHERE m_reg_settings.product_type = $type AND m_reg_settings.sacco_id = '".$office."' AND m_reg_settings.status = 'Active'");

		if (!empty($results)) {
			return $results;
		} else {
			return NULL;
		}
	}

	function checkTransactionStatus(){

		$id = $_SESSION['user_id'];
		$office =$_SESSION['office'];
		$result = $this->db->selectData("SELECT * FROM m_staff where id='".$id."' AND office_id = '". $office."'");

		if (empty($result)) {
			return true;
		} else {
			if ($result[0]['last_request_date'] != date('Y-m-d') && $result[0]['account_balance'] != 0 && $result[0]['can_transact'] == 'Yes') {
				return true;
			} else if (($result[0]['can_transact'] == 'No' && $result[0]['access_level'] == 'A') || $result[0]['access_level'] == 'SA') {
				return true;
			} else {
				return true;
			}
		}

	}
	
	function getSavingsAccWithDrawStatus($actno){

		$client = $this->db->SelectData("SELECT * FROM m_savings_account WHERE account_no = '$actno' AND office_id = '". $_SESSION['office']. "'");

		$message = NULL;
		$currency = $this->db->SelectData("SELECT * FROM system_settings WHERE status ='Active'");

		if($client[0]['withdraw_status']=="Inactive"){
			if($client[0]['withdraw_target_type']=="days"){	

				$date = date('Y-m-d h:i:s');
				$closure_date = strtotime($client[0]['withdraws_disabled_on']); 
				$current_date = strtotime($date); 
				$datediff = $current_date - $closure_date;
				$days = floor($datediff/(60*60*24));

				if($days>=$client[0]['withdraw_period']){						
					$rs =   $this->UnLockAccount($actno);
				}else{
					$datearrear = date('D d/m/Y', strtotime($client[0]['withdraws_disabled_on']. ' + '.$client[0]['withdraw_period'].' days'));
					$message = "Your account is locked until ".$datearrear;
				}  	  
			}else{ 
				if($client[0]['running_balance']>=$client[0]['withdraw_target_amount']){ 
					$rs =   $this->UnLockAccount($actno);	
				}else{
					$message = "Your account is locked untill your target of ".$currency[0]['currency']." ".number_format($client[0]['withdraw_target_amount'])." is reached";
				}
			}

		}

		return $message;
	}

	function UnLockAccount($acc){
		$depositstatus = array(
			'withdraw_status' => "Active",
			'withdraws_activated_on' => date('Y-m-d')
		);

		$this->db->UpdateData('m_savings_account', $depositstatus,"`account_no` = '{$acc}'");
	}

	function getClient($id){
		return $this->db->SelectData("SELECT * FROM members c INNER JOIN m_branch b where c.office_id  = b.id AND c.c_id='".$id."'  order by c.c_id desc");
	}

	function getOrgDetails(){

		$results = $this->db->SelectData("SELECT * FROM m_organisation WHERE id = '". $_SESSION['office']. "'");

		if (!empty($results)) {
			return $results[0];
		} else {
			return NULL;
		}

	}

	function GetWalletPayment($id){

		$result = $this->db->SelectData("SELECT * FROM sm_mobile_wallet_transactions where wallet_transaction_id=".$id);
		$account_details = $this->getClientWalletAccount($result[0]['wallet_account_number']);
		$currency=$this->db->SelectData("SELECT * FROM system_settings WHERE status ='Active'");
		$client_details = $this->getClient($account_details[0]['member_id']);

		foreach ($result as $key => $value) {
			$rset[$key]['account_name'] = $client_details[0]['firstname']." ".$client_details[0]['middlename']." ".$client_details[0]['lastname']; 
			$rset[$key]['account_number'] = $account_details[0]['wallet_account_number']; 
			$rset[$key]['transaction_date'] = $result[$key]['transaction_date'];
			$rset[$key]['transaction_id'] = $result[$key]['transaction_ID'];
			$rset[$key]['transaction_type'] = $result[$key]['transaction_type'];
			$rset[$key]['running_balance'] = $result[$key]['running_balance'];
			$rset[$key]['amount_deposited'] = $result[$key]['amount'];
			$rset[$key]['currency'] = $currency[0]['currency'];
			$rset[$key]['new_balance'] = $account_details[$key]['wallet_balance'];
		}
		return $rset;
	}

	function GetSavingsPayment($id){

		$result = $this->db->SelectData("SELECT * FROM m_savings_account_transaction where id='".$id."' ");
		$account_details = $this->getClientAccount($result[0]['savings_account_no']);
		$currency=$this->db->SelectData("SELECT * FROM system_settings WHERE status ='Active'");
		$client_details = $this->getClient($account_details[0]['member_id']);

		foreach ($result as $key => $value) {
			$rset[$key]['account_name'] = $client_details[0]['firstname']." ".$client_details[0]['middlename']." ".$client_details[0]['lastname']; 
			$rset[$key]['account_number'] = $account_details[0]['account_no']; 
			$rset[$key]['transaction_date'] = $result[$key]['transaction_date'];
			$rset[$key]['transaction_id'] = $result[$key]['transaction_id'];
			$rset[$key]['transaction_type'] = $result[$key]['transaction_type'];
			$rset[$key]['running_balance'] = $result[$key]['running_balance'];
			$rset[$key]['amount_deposited'] = $result[$key]['amount'];
			$rset[$key]['currency'] = $currency[0]['currency'];
			$rset[$key]['new_balance'] = $result[$key]['running_balance'];
		}
		return $rset;
	}

	function getClientWalletAccount($id){
		return $this->db->SelectData("SELECT * FROM sm_mobile_wallet where wallet_account_number='".$id."'");
	}

	
	function logUserActivity($id = NULL){

		$ip = $_SERVER['REMOTE_ADDR']?:($_SERVER['HTTP_X_FORWARDED_FOR']?:$_SERVER['HTTP_CLIENT_IP']);
		
		$last_record = $this->db->selectData("SELECT * FROM system_logs WHERE operation_type='login' AND ip_address = '".$ip."' ORDER BY id DESC LIMIT 1");

		if (!empty($last_record)) {

			$p_data = array(
	            'sacco_id' => isset($_SESSION['office']) ? $_SESSION['office'] : NULL,  
	            'user_id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL		
	        );
			$this->db->UpdateData('system_logs', $p_data, "id = {$last_record[0]['id']}");
		}


		$postData = array(        
			'transaction_id' =>  isset($id) && $id != NULL ? $id : NULL,
            'operation_type' => isset($_GET['url']) ? $_GET['url'] : '',
			'is_transaction' =>  isset($id) && $id != NULL ? 'Yes' : 'No',
            'sacco_id' => isset($_SESSION['office']) ? $_SESSION['office'] : NULL,  
            'user_id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL,
			'request_data' => serialize($_POST),
            'status' => 'No', 
            'response_data' => http_response_code(),
			'ip_address' => $_SERVER['REMOTE_ADDR']?:($_SERVER['HTTP_X_FORWARDED_FOR']?:$_SERVER['HTTP_CLIENT_IP'])			
        );
			
        $this->db->InsertData('system_logs', $postData);
	}

	function getThisSaccoCurrency(){
		$results =  $this->db->selectData("SELECT currency FROM system_settings");
		return $results[0]['currency'];
	}

	function getThisSaccoName(){
		$office = $_SESSION['office'];
		$results = $this->db->SelectData("SELECT name FROM m_branch WHERE id ='" . $office . "'");
		return $results[0]['name'];
	}

	function checkAccounts(){
		$office=$_SESSION['office'];
		$result=  $this->db->selectData("SELECT * FROM acc_ledger_account where sacco_id = '".$_SESSION['office']."' AND sacco_id='".$office."'");

		if (count($result) <= 0) {
			return false;
		} else {
			return true;
		}
	}

	function checkLedgerAccounts(){
		$office=$_SESSION['office'];
		$result=  $this->db->selectData("SELECT * FROM acc_ledger_account_mapping where office_id='".$office."'");
		if (count($result) >= 5) {
			return true;
		} else {
			return false;
		}
	}

	function checkPointers(){
		$office=$_SESSION['office'];
		$result=  $this->db->selectData("SELECT * FROM acc_accounting_rule where office_id='".$office."' AND status = 1");

		if (count($result) <= 0) {
			return false;
		} else {
			return true;
		}
	}
	
	function checkBalances(){
		$office=$_SESSION['office'];
		$result=  $this->db->selectData("SELECT * FROM acc_gl_journal_entry where transaction_id LIKE 'OP%' AND office_id='".$office."'");

		if (count($result) <= 0) {
			return false;
		} else {
			return true;
		}
	}

	function checkThirdpartyProducts(){
		$office=$_SESSION['office'];
		$result=  $this->db->selectData("SELECT * FROM thirdparty_products WHERE office_id='".$office."'");

		if (count($result) <= 0) {
			return false;
		} else {
			return true;
		}
	}

	function checkWalletPointers(){
		$office=$_SESSION['office'];

		$wallet_transactions = $this->db->selectData("SELECT transaction_type_id FROM transaction_type where product_type=5");

		$ids = array();

		foreach ($wallet_transactions as $key => $value) {
			$result = $this->db->selectData("SELECT * FROM acc_gl_pointers where sacco_id='".$office."' AND transaction_type_id = '".$value['transaction_type_id']."'");
			if (!empty($result)) {
				$ids[$key]['name'] = $result[0]['pointer_id'];
			}
		}

		if ((count($ids) >= count($wallet_transactions)) && count($ids) > 0) {
			return true;
		} else {
			return false;
		}
	}

	function checktelephone($tel){
		$office=$_SESSION['office'];
		$results = $this->db->selectData("SELECT * FROM members WHERE mobile_no = '".$tel."' AND office_id='".$office."'");

		$number = false;
		if (count($results) <= 0) {
			$number = false;
		} else {
			$number = true;
		}
		echo json_encode($number);
		die();

	}

	function checkmembertelephone($tel){
		$office=$_SESSION['office'];
		$results = $this->db->selectData("SELECT * FROM members WHERE mobile_no = '".$tel."' AND office_id='".$office."'");

		$number = false;
		if (count($results) <= 0) {
			$number = false;
		} else {
			$number = true;
		}
		return $number;
	}

	function checkemail($mail){
		$office=$_SESSION['office'];
		$results = $this->db->selectData("SELECT * FROM members WHERE email = '".$mail."' AND office_id='".$office."'");

		$number = false;
		if (count($results) <= 0) {
			$number = false;
		} else {
			$number = true;
		}
		echo json_encode($number);
		die();

	}

	function checkusername($name){
		$office=$_SESSION['office'];
		$results = $this->db->selectData("SELECT * FROM m_staff WHERE username = '".$name."'");

		$number = false;
		if (count($results) <= 0) {
			$number = false;
		} else {
			$number = true;
		}
		echo json_encode($number);
		die();

	}

	function getUserCashBalance($office, $user_id){
		$results = $this->db->selectData("SELECT * FROM m_staff WHERE id = '".$user_id."' AND office_id='".$office."'");

		return $results[0]['account_balance'];
	}

	function getTellerCashAccountBalance(){

		$office=$_SESSION['office'];
		$results = $this->db->selectData("SELECT * FROM m_staff WHERE can_transact = 'Yes' AND office_id='".$office."'");

		$total = 0;
		foreach ($results as $key => $value) {
			$total += $value['account_balance'];
		}
		return $total;
	}

	function getTellerAccountBalance($id){
		
		$result = $this->db->selectData("SELECT * FROM m_staff WHERE  id ='".$id."'");

		return $result[0]['account_balance'];
	}

	function getTellerAccountID(){
		$office=$_SESSION['office'];

		$results = 	$this->db->selectData("SELECT id FROM acc_ledger_account where sacco_id = '".$_SESSION['office']."' AND name = 'Teller Float Account(DR)' AND sacco_id = '".$office. "'");

		if (empty($results)) {

			$result = $this->db->selectData("SELECT * FROM m_staff WHERE office_id = '".$office. "'");

			$id = '';
			foreach ($result as $key => $value) {
				if ($value['cash_account'] != '') {
					$id = $value['cash_account'];
					break;
				}
			}
			return $id;
		} else {
			return $results[0]['id'];
		}

	}

	/////////////////////////////////////START OF PORTIFOLIO REPORT//////////////////////////////////////////

	/////1   
	function no_customers_loans(){
		$office=$_SESSION['office'];
		return $this->db->SelectData("SELECT  (count(member_id)) as members FROM  (SELECT * FROM  m_loan GROUP BY member_id) as ss WHERE sacco_id = $office");

	}

	function no_customers_savings(){
		$office=$_SESSION['office'];
		return $this->db->SelectData("SELECT  (count(member_id)) as members FROM  (SELECT * FROM  m_savings_account GROUP BY member_id) as ss WHERE office_id = $office");

	}

	function no_customers_timedeposits(){
		$office=$_SESSION['office'];
		return $this->db->SelectData("SELECT  (count(member_id)) as members FROM  (SELECT * FROM  fixed_deposit_account GROUP BY member_id) as ss WHERE office_id = $office");
	}

	function no_customers_shares(){
		$office=$_SESSION['office'];
		return $this->db->SelectData("SELECT  (count(member_id)) as members FROM  (SELECT * FROM  share_account GROUP BY member_id) as ss WHERE office_id = $office");
	}
	///end 1


	function no_accounts_loans(){
		$office=$_SESSION['office'];
		return $this->db->SelectData("SELECT  (count(member_id)) as members FROM  m_loan WHERE sacco_id = $office");

	}

	function no_accounts_savings(){
		$office=$_SESSION['office'];
		return $this->db->SelectData("SELECT  (count(member_id)) as members FROM  m_savings_account WHERE office_id = $office");
	}

	function no_accounts_timedeposits(){ 
		$office=$_SESSION['office'];
		return $this->db->SelectData("SELECT  (count(member_id)) as members FROM  fixed_deposit_account WHERE office_id = $office");
	}

	function no_accounts_shares(){
		$office=$_SESSION['office'];
		return $this->db->SelectData("SELECT  (count(member_id)) as members FROM  share_account WHERE office_id = $office");

	}

	function balance_loans(){
		$office=$_SESSION['office'];
		return $this->db->SelectData("SELECT  sum(principal_disbursed) as members  FROM  m_loan where loan_status = 'Disbursed' AND sacco_id = $office");
	}

	function balance_savings(){
		$office=$_SESSION['office'];
		return $this->db->SelectData("SELECT  sum(running_balance) as members  FROM  m_savings_account WHERE office_id = $office");

	}

	function balance_timedeposits(){
		$office=$_SESSION['office'];
		return $this->db->SelectData("SELECT  sum(running_balance) as members  FROM   fixed_deposit_account WHERE office_id = $office");
	}

	function balance_shares(){
		$office=$_SESSION['office'];
		return $this->db->SelectData("SELECT  sum(running_balance) as members  FROM  share_account WHERE office_id = $office");
	}

 	/////30

	function amount_approved(){
		$office=$_SESSION['office'];
		return $this->db->SelectData("SELECT  sum(approved_principal) as members  FROM  m_loan where loan_status = 'Approved' AND sacco_id = $office");
	}
	function amount_disbursed(){
		$office=$_SESSION['office'];
		return $this->db->SelectData("SELECT  sum(principal_disbursed) as members  FROM  m_loan where loan_status = 'Disbursed' AND sacco_id = $office");
	}

	///END OF PORTIFOLIO REPORT

	//////////////////////////////GL COMPARISON REPORT///////////////////////////////
	
	function scheduledetailsdue() {
		$results = $this->db->SelectData("SELECT * FROM m_loan_repayment_schedule  where principal_amount!=principal_completed order by id ");
		
		if(empty($results)){
			return "";
		}else{
			
			$cumulative_principal =0;
			$cumulative_interest =0;
			foreach ($results  as $key => $value) {		
				$principal_amount = ($results[$key]['principal_amount'] - $results[$key]['principal_completed']);
				$interest_amount =($results[$key]['interest_amount'] - $results[$key]['interest_completed']);
				$cumulative_principal =$cumulative_principal + $principal_amount;
				$cumulative_interest =$cumulative_interest + $interest_amount;
				
				
				$rset[$key]['id'] =$results[$key]['id'];	
				$rset[$key]['account_no'] =$results[$key]['account_no'];	
				$rset[$key]['principal_amount'] =$principal_amount;	
				$rset[$key]['interest_amount'] =$interest_amount;	
				$rset[$key]['original_principal'] =$results[$key]['principal_amount'];	
				$rset[$key]['original_interest'] =$results[$key]['interest_amount'];	
				$rset[$key]['fromdate'] =$results[$key]['fromdate'];	
				$rset[$key]['duedate'] =$results[$key]['duedate'];	
				$rset[$key]['installment'] =$results[$key]['installment'];	
				$rset[$key]['cumulative_principal'] =$cumulative_principal;	
				$rset[$key]['cumulative_interest'] =$cumulative_interest;	
				
			}

			
			

			return $rset;

		}
	}
	
	function getWalletGL($sacco = NULL){
		$amount = $code = 0;
		$data = array();
		if ($sacco == NULL) {
			$office=$_SESSION['office']; 
		} else {
			$office=$sacco;
		}

		$acc = $this->db->SelectData("SELECT account_id from acc_ledger_account_mapping where office_id = $office and product_id = 5");
		if (!empty($acc)) {
			$acc_id = $acc[0]['account_id'];
			$query =   $this->db->SelectData("SELECT * FROM acc_gl_journal_entry where account_id = $acc_id");
			$query2 =   $this->db->SelectData("SELECT * FROM acc_ledger_account where sacco_id = '".$_SESSION['office']."' AND id = $acc_id");
			foreach($query as $key => $value){
				if($value['transaction_type'] == 'CR'){
					$amount = $amount + $value['amount'];
				}else{
					$amount = $amount - $value['amount'];
				}
			}

			$data['amount'] = $amount;
			$data['gl_code'] = $query2[0]['gl_code'];
		} else {
			$data['amount'] = $amount;
			$data['gl_code'] = $code;
		}
		return $data;     
	}

	function getWalletBalance($sacco = NULL){
		if ($sacco == NULL) {
			$office=$_SESSION['office']; 
		} else {
			$office=$sacco;
		}
		
		$query =   $this->db->SelectData("SELECT sum(wallet_balance) as wallet_balance FROM sm_mobile_wallet where bank_no = $office");

		return $query[0]['wallet_balance'];
	}

	function getSavingsGL($sacco = NULL){
		$amount = $code =0;
		if ($sacco == NULL) {
			$office=$_SESSION['office']; 
		} else {
			$office=$sacco;
		} 
		$acc = $this->db->SelectData("SELECT account_id from acc_ledger_account_mapping where office_id = $office and product_id = 3");
		if (!empty($acc)) {
			$acc_id = $acc[0]['account_id']; 
			$query =   $this->db->SelectData("SELECT * FROM acc_gl_journal_entry where account_id = $acc_id");
			$query2 =   $this->db->SelectData("SELECT * FROM acc_ledger_account where sacco_id = '".$_SESSION['office']."' AND id = $acc_id");
			foreach($query as $key => $value){
				if($value['transaction_type'] == 'CR'){
					$amount = $amount + $value['amount'];
				}else{
					$amount = $amount - $value['amount'];
				}
			}
			$data['amount'] = $amount;
			$data['gl_code'] = $query2[0]['gl_code'];
		} else {
			$data['amount'] = $amount;
			$data['gl_code'] = $code;
		}
		return $data;     
	}

	function getSavingsBalance($sacco = NULL){
		if ($sacco == NULL) {
			$office=$_SESSION['office']; 
		} else {
			$office=$sacco;
		}
		$query =   $this->db->SelectData("SELECT sum(running_balance) as account_balance FROM m_savings_account where office_id = $office");

		return $query[0]['account_balance'];
	}

	function getloansGL($sacco = NULL){
		$amount = $code = 0;
		if ($sacco == NULL) {
			$office=$_SESSION['office']; 
		} else {
			$office=$sacco;
		}  
		$acc = $this->db->SelectData("SELECT account_id from acc_ledger_account_mapping where office_id = $office and product_id = 2");
		if (!empty($acc)) {
			$acc_id = $acc[0]['account_id']; 
			$query =   $this->db->SelectData("SELECT * FROM acc_gl_journal_entry where account_id = $acc_id");
			$query2 =   $this->db->SelectData("SELECT * FROM acc_ledger_account where sacco_id = '".$_SESSION['office']."' AND id = $acc_id");
			foreach($query as $key => $value){
				if($value['transaction_type'] == 'DR'){
					$amount = $amount + $value['amount'];
				}else{
					$amount = $amount - $value['amount'];
				}
			}
			$data['amount'] = $amount;
			$data['gl_code'] = $query2[0]['gl_code'];
		} else {
			$data['amount'] = $amount;
			$data['gl_code'] = $code;
		}
		return $data;     
	}

	function getloansBalance($sacco = NULL){
		if ($sacco == NULL) {
			$office=$_SESSION['office']; 
		} else {
			$office=$sacco;
		} 
		$query =   $this->db->SelectData("SELECT sum(total_outstanding) as account_balance FROM m_loan where sacco_id = $office");
		return $query[0]['account_balance'];
	}

	function getsharesGL($sacco = NULL){
		$amount = $code = 0;
		if ($sacco == NULL) {
			$office=$_SESSION['office']; 
		} else {
			$office=$sacco;
		}  
		$acc = $this->db->SelectData("SELECT account_id from acc_ledger_account_mapping where office_id = $office and product_id = 1");
		if (!empty($acc)) {
			$acc_id = $acc[0]['account_id']; 
			$query =   $this->db->SelectData("SELECT * FROM acc_gl_journal_entry where account_id = $acc_id");
			$query2 =   $this->db->SelectData("SELECT * FROM acc_ledger_account where sacco_id = '".$_SESSION['office']."' AND id = $acc_id");
			foreach($query as $key => $value){
				if($value['transaction_type'] == 'CR'){
					$amount = $amount + $value['amount'];
				}else{
					$amount = $amount - $value['amount'];
				}
			}
			$data['amount'] =  $amount;
			$data['gl_code'] = $query2[0]['gl_code'];
		} else {
			$data['amount'] =  $amount;
			$data['gl_code'] = $code;			
		}
		return $data;     
	}

	function getsharesBalance($sacco = NULL){
		if ($sacco == NULL) {
			$office=$_SESSION['office']; 
		} else {
			$office=$sacco;
		} 
		$query =   $this->db->SelectData("SELECT sum(running_balance) as account_balance FROM share_account WHERE office_id = $office");

		return $query[0]['account_balance'];
	}

	function gettimedepositsGL($sacco = NULL){
		$amount = $code = 0;
		if ($sacco == NULL) {
			$office=$_SESSION['office']; 
		} else {
			$office=$sacco;
		}  
		$acc = $this->db->SelectData("SELECT account_id from acc_ledger_account_mapping where office_id = $office and product_id = 4");
		if (!empty($acc)) {
			$acc_id = $acc[0]['account_id']; 
			$query =   $this->db->SelectData("SELECT * FROM acc_gl_journal_entry where account_id = $acc_id");
			$query2 =   $this->db->SelectData("SELECT * FROM acc_ledger_account where sacco_id = '".$_SESSION['office']."' AND id = $acc_id");
			foreach($query as $key => $value){
				if($value['transaction_type'] == 'CR'){
					$amount = $amount + $value['amount'];
				}else{
					$amount = $amount - $value['amount'];
				}
			}
			$data['amount'] = $amount;
			$data['gl_code'] = $query2[0]['gl_code'];
		} else {
			$data['amount'] = $amount;
			$data['gl_code'] = $code;
		}
		return $data;     
	}

	function gettimedepositsBalance($sacco = NULL){
		if ($sacco == NULL) {
			$office=$_SESSION['office']; 
		} else {
			$office=$sacco;
		} 
		$query =   $this->db->SelectData("SELECT sum(running_balance) as account_balance FROM fixed_deposit_account WHERE office_id = $office");

		return $query[0]['account_balance'];
	}


	//////////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////

	function InsertAccess($class , $method) {

		$check = Model::ACList($method);

		if(count($check)>0 || $method== $class.'/__construct/'){

		}else{
			$postData = array(
				'menu_title' =>  $class,
				'menu_type' => 'single',
				'load_page' =>  $method,
				'parent_option' => 3,  
				'rank' => 3,
				'css' =>  NULL,
				'on_menu' => 'No',     			
			);
			
			if($this->db->InsertData('sch_access_rights', $postData)){
				echo "Yeah";
			}else{
				echo "waaaa";
			}
		}
	}


	function ACList() {

		$result=  $this->db->selectData("SELECT * FROM sch_access_rights WHERE  id > 110");
		foreach($result as $key => $value){
			$loadpage =  $value['load_page'];
			$id =  $value['id'];
			$rs =  explode('/',$loadpage );
			$parent = $rs[0];
			$name = $rs[1];
			if($name=='index'){ 
				$name =   $rs[0]." Index ";
				$postData = array(
					'menu_title' =>  $name,
					'load_page'  =>  $parent."/",
				);
				$this->db->UpdateData('sch_access_rights', $postData, "id = {$id}");
			}		      
		}
		return   $result;
	}

	
	function getAccountSide($id){

		$result=  $this->db->selectData("SELECT classification FROM acc_ledger_account where sacco_id = '".$_SESSION['office']."' AND id='".$id."'");
		if($result[0]['classification']=='Assets'||$result[0]['classification']=='Expenses'){
			return 'SIDE_A';  
		}else{
			return 'SIDE_B';
		}

	}

	function getMapping($pointer_name){
		return $this->db->SelectData("SELECT * FROM  acc_gl_pointers  where pointer_name = '".$pointer_name."'");
	}	




	function CreateWalletAccount($data) {
		try{
 		    //$this->db->beginTransaction();

			$now = date('Y-m-d H:i:s');
			$postData = array();
			$postData['creation_date'] = $now;
			$postData['member_id'] = $data['member_id'];
			$postData['bank_no'] = $data['bank_no'];
			$postData['savings_account'] = $data['accountno'];
			$postData['wallet_account_number'] = $data['wallet_account_number'];
			$postData['wallet_balance'] = 0;

			$trans_id = $this->db->InsertData("sm_mobile_wallet", $postData);

            //$this->db->commit();
            
			return $trans_id;

		}catch(Exception $e){
		    return $e->getMessage();
		    
		  // header("Location:".URL ."settings/batchregistration?msg=error".$e);
		  
		  //echo $e;
		  // $this->db->rollBack();
		  // exit(); 	  
		}
	}

	function logWalletTransaction($req_array){

		$transaction_postData = array();
		if(isset($req_array['accounttransfer'])){
			$transaction_postData['wallet_account_number'] =$req_array['accounttransfer'];		
			$transaction_postData['running_balance']=$req_array['receiving_balance'];
		}else{
			$transaction_postData['wallet_account_number'] =$req_array['wallet_account_number'];	
			$transaction_postData['running_balance']=$req_array['wallet_balance'];
		}

		$transaction_postData['description']=$req_array['description'];
		$transaction_postData['transaction_type']=$req_array['transaction_type'];
		$transaction_postData['amount']=$req_array['amount'];
		$transaction_postData['amount_in_words']=$req_array['amount_in_words'];

		$transaction_postData['transaction_ID']= $req_array['transaction_id'];
		$transaction_postData['fee']= "0";

		$withdraw_transaction_id = $this->db->InsertData('sm_mobile_wallet_transactions', $transaction_postData);

		$this->UpdateWalletAccount($req_array);

		return $withdraw_transaction_id;

	}
	
	function UpdateWalletAccount($req_array) {
		$postData = array();

		if(isset($req_array['new_wallet_accountnumber'])){
			$postData['wallet_account_number'] = $req_array['new_wallet_accountnumber'];				
		}			
		
		if(isset($req_array['accounttransfer'])){
			$walletaccount = $req_array['accounttransfer'];	
			$postData['wallet_balance'] = $req_array['receiving_balance'];				

		}else{
			$walletaccount= $req_array['wallet_account_number'];	
			if(isset($req_array['wallet_balance'])){
				$postData['wallet_balance'] = $req_array['wallet_balance'];			 
			}
		}

		$rs = $this->db->UpdateData('sm_mobile_wallet', $postData, "wallet_account_number = {$walletaccount}");

	}


	function GetWalletTransactionBalance($wallet_account) {
		return $this->db->SelectData("SELECT * FROM sm_mobile_wallet WHERE wallet_account_number=:tid", array('tid' =>$wallet_account));
	}


	function MemberNo($office){
		$result = $this->db->SelectData("SELECT MAX(c_id) as last_id FROM members WHERE office_id=:office", array('office' => $office));
		$ct=count($result) ;

		if ($ct==0){
			$next = $office.'001';
		}else {
			$last = $result[0]['last_id'];
			$next = $last+1;
		}

		return $next;
	}


	function getThirdPartyNo($office){

		$result = $this->db->SelectData("SELECT MAX(thirdparty_accountno) as last_id FROM thirdparty_products");
		$ct=count($result[0]['last_id']) ;

		if ($ct==0){
			$next = $office.'00001';
		}else {
			$last = $result[0]['last_id'];
			$next = $last+1;
		}

		return $next;
	}


	function LogToFile($log) {
		$filename = 'pdf/report.txt';
		$logtofile = $this->PrepareLog($log);
		file_put_contents($filename, $logtofile . "\n", FILE_APPEND);
	}

	function PrepareLog($log) {
		$logcont = '';
		$logcont .= $log;
		return $logcont;
	}


	function GetBatchNo() {
		$results = $this->db->SelectData("SELECT MAX(batch_no) as batch_no FROM acc_gl_journal_entry");
		$result=count($results[0]['batch_no']) ;
		if ($result== 0) {
			$next = '1001';
		} else {
			$last = $result[0]['batch_no'];
			$next = $last + 1;
		}
		return $next;
	}   

	function BranchNo(){
		$result = $this->db->SelectData("SELECT MAX(id) as last_id FROM m_branch");
		if (count($result) == 0) {
			$next = '100';
		} else {
			$last = $result[0]['last_id'];
			$next = $last + 1;
		}
		return $next;
	}

	function OrganisationNo(){
		$result = $this->db->SelectData("SELECT MAX(id) as last_id FROM m_organisation");
		if (count($result) == 0) {
			$next = '100';
		} else {
			$last = $result[0]['last_id'];
			$next = $last + 1;
		}
		return $next;
	}


	function checkuname($name){
		
		$results = $this->db->selectData("SELECT * FROM m_staff WHERE username = '".$name."'");

		$number = false;
		if (count($results) <= 0) {
			$number = false;
		} else {
			$number = true;
		}
		return $number;

	}


	function checkbranchname($name){
		
		$results = $this->db->selectData("SELECT * FROM m_branch WHERE name = '".$name."'");

		$number = false;
		if (count($results) <= 0) {
			$number = false;
		} else {
			$number = true;
		}
		return $number;

	}

	function checkphone($phone){
		
		$results = $this->db->selectData("SELECT * FROM m_staff WHERE mobile_no = '".$phone."'");

		$number = false;
		if (count($results) <= 0) {
			$number = false;
		} else {
			$number = true;
		}
		return $number;

	}
	
	function CreateEmployee($data){
		try{

		$ra = trim(substr(md5(uniqid(mt_rand(), true)), 0, 10));
		$password = Hash::create('sha256',$ra, HASH_ENCRIPT_PASS_KEYS);	 
		$postData = array();	
		$postData['firstname']=$data['fname'];	 
		$postData['lastname']=$data['lname'];	 
		$postData['username']=$data['username'];	 
		$postData['password']= $password;
		$postData['mobile_no']=$data['phone'];	 
		$postData['email']=$data['email'];	 
		$postData['office_id']=$data['branch_no'];
		$postData['organisational_role_enum']=$data['responsibility'];	 
		$postData['gender']=$data['gender'];
		$postData['access_level']= isset($data['access_level']) ? $data['access_level'] : "A";
		$postData['external_id'] = $data['external_id'];
		$postData['can_transact'] = isset($data['can_transact']) ? $data['can_transact'] : "No";
		$postData['cash_account'] = $this->getTellerAccountID();
		$postData['joining_date'] = date('Y-m-d H-i-s');

		$message =  "Hello, your CLIC FMS login details are<br>Username: ".$data['username']."<br>Password: ".$ra;
		$this->sendEmail($data['email'],$message);
		$id=$this->db->InsertData('m_staff',$postData);	

		return $id;	
		}catch(Exception $s){
			return false;
		}
	}

	function sendEmail($email, $message, $sub = NULL){
		$to = $email;
		if ($sub == NULL) {
			$subject="Social Banking Login";
		} else {
			$subject=$sub;
		}
      

	$url = "https://clic.world/fedapi/v3/users/sendmailclient";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,"source=jwtsdfrtopurtrwadsd9043898754&message=".$message."&email=".$email."&subject=".$subject."");
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));

	// receive server response 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$server_output = curl_exec ($ch);
		curl_close ($ch);

		return  $server_output;
		
	}
	
	function LoanAccount() {
		$result = $this->db->SelectData("SELECT MAX(account_no) as last_id FROM m_loan");
		$ct=count($result[0]['last_id']) ;

		if ($ct==0){
			$next = '10001';
		}else {
			$last = $result[0]['last_id'];
			$next = $last + 1;
		} 
		return $next;
	}

	function SavingsAccountNo() {
		$result = $this->db->SelectData("SELECT MAX(account_no) as last_id FROM m_savings_account");

		if (count($result) == 0) {
			$next = '10001';
		} else {
			$last = $result[0]['last_id'];
			$next = $last + 1;
		}
		return $next;
	}       

	function GenerateCSV($file_part, $data) {
		$file_ext = time();
		$file_name = $file_part . '_' . $file_ext . '.csv';
		$path = 'systemlog/file_dumps/' . $file_name;
		$fp = fopen($path, 'w');

		foreach ($data as $fields) {
			fputcsv($fp, $fields);
		}

		fclose($fp);

		return $file_name;
	}
function GetSetUpCode($parent){
$rs =  $this->db->selectData("SELECT * FROM acc_ledger_account_main_headers where gl_id='".$parent."' ");
     return $rs[0]['gl_code'];
}
	///GL CODES GENERATION.
	function getAssetCodes($usage,$parent_id=null) {
	     $parent_code =   $this->GetSetUpCode(2);
	     
		if($usage=="Account"&&!empty($parent_id)){
			$results = $this->db->SelectData("SELECT MAX(gl_code) as last_id FROM acc_ledger_account where sacco_id = '".$_SESSION['office']."' AND account_usage='".$usage."' AND parent_id='".$parent_id."'  AND classification='Assets'");
		}else if($usage=="Heading"){
			$results = $this->db->SelectData("SELECT MAX(gl_code) as last_id FROM acc_ledger_account where sacco_id = '".$_SESSION['office']."' AND account_usage='".$usage."' AND  classification='Assets'");
		}else{

			return null;;   
		}
		$result=count($results[0]['last_id']) ;
		if ($result==0) {
 			if($usage=="Account"){
				$parent = $this->db->SelectData("SELECT gl_code FROM acc_ledger_account where sacco_id = '".$_SESSION['office']."' AND id='".$parent_id."' ");
				$last = (int)$parent[0]['gl_code'];
				//$next = $last;
                $next = $this->GLCodeNext($last);

			}else{			
        $next = $this->GLCodeNext($parent_code);
			}
		} else {
			$last = $results[0]['last_id'];
			//$next = $last + 1;
			$next = $this->GetNewGLCode($last);
		}
		return $next;
	}   


	function getExpenseCodes($usage,$parent_id=null) {
        $parent_code =   $this->GetSetUpCode(4);

		if($usage=="Account"){      
			$results = $this->db->SelectData("SELECT MAX(gl_code) as last_id FROM acc_ledger_account where sacco_id = '".$_SESSION['office']."' AND account_usage='".$usage."' AND parent_id='".$parent_id."'  AND  classification='Expenses'");
		}else if($usage=="Heading"){
			$results = $this->db->SelectData("SELECT MAX(gl_code) as last_id FROM acc_ledger_account where sacco_id = '".$_SESSION['office']."' AND account_usage='".$usage."' AND  classification='Expenses'");
		}else{

			return null;;   
		}
		$result=count($results[0]['last_id']) ;
		if ($result==0) {
 			if($usage=="Account"){
				$parent = $this->db->SelectData("SELECT gl_code FROM acc_ledger_account where sacco_id = '".$_SESSION['office']."' AND id='".$parent_id."' ");
				$last = (int)$parent[0]['gl_code'];
				//$next = $last;
                $next = $this->GLCodeNext($last);

			}else{			
        $next = $this->GLCodeNext($parent_code);
			}
		} else {
			$last = $results[0]['last_id'];
			//$next = $last + 1;
			$next = $this->GetNewGLCode($last);
		}
		return $next;
	} 
	
	function getLiabilityCodes($usage,$parent_id=null) {
	        $parent_code =   $this->GetSetUpCode(3);
	if($usage=="Account"){  
			$results = $this->db->SelectData("SELECT MAX(gl_code) as last_id FROM acc_ledger_account where sacco_id = '".$_SESSION['office']."' AND account_usage='".$usage."' AND parent_id='".$parent_id."'  AND  classification='Liabilities'");
		}else if($usage=="Heading"){
			$results = $this->db->SelectData("SELECT MAX(gl_code) as last_id FROM acc_ledger_account where sacco_id = '".$_SESSION['office']."' AND account_usage='".$usage."' AND  classification='Liabilities'");
		}else{

			return null;;   
		}
		$result=count($results[0]['last_id']) ;
		if ($result==0) {
 			if($usage=="Account"){
				$parent = $this->db->SelectData("SELECT gl_code FROM acc_ledger_account where sacco_id = '".$_SESSION['office']."' AND id='".$parent_id."' ");
				$last = (int)$parent[0]['gl_code'];
				//$next = $last;
                $next = $this->GLCodeNext($last);

			}else{			
        $next = $this->GLCodeNext($parent_code);
			}
		} else {
			$last = $results[0]['last_id'];
			//$next = $last + 1;
			$next = $this->GetNewGLCode($last);
		}
		return $next;
	}  
	
	function getIncomeCodes($usage,$parent_id=null) {
	    	        $parent_code =   $this->GetSetUpCode(5);

		if($usage=="Account"){ 
			$results = $this->db->SelectData("SELECT MAX(gl_code) as last_id FROM acc_ledger_account where sacco_id = '".$_SESSION['office']."' AND account_usage='".$usage."' AND parent_id='".$parent_id."'  AND  classification='Incomes'");
		}else if($usage=="Heading"){
			$results = $this->db->SelectData("SELECT MAX(gl_code) as last_id FROM acc_ledger_account where sacco_id = '".$_SESSION['office']."' AND account_usage='".$usage."' AND  classification='Incomes'");
		}else{

			return null;;   
		}

		$result=count($results[0]['last_id']) ;
		if ($result==0) {
 			if($usage=="Account"){
				$parent = $this->db->SelectData("SELECT gl_code FROM acc_ledger_account where sacco_id = '".$_SESSION['office']."' AND id='".$parent_id."' ");
				$last = (int)$parent[0]['gl_code'];
				//$next = $last;
                $next = $this->GLCodeNext($last);

			}else{			
        $next = $this->GLCodeNext($parent_code);
			}
		} else {
			$last = $results[0]['last_id'];
			//$next = $last + 1;
			$next = $this->GetNewGLCode($last);
		}
		return $next;
	}
	
	function getEquityCodes($usage,$parent_id=null) {
	    	        $parent_code =   $this->GetSetUpCode(1);

		if($usage=="Account"){
			$results = $this->db->SelectData("SELECT MAX(gl_code) as last_id FROM acc_ledger_account where sacco_id = '".$_SESSION['office']."' AND account_usage='".$usage."' AND parent_id='".$parent_id."' AND  classification='Equity'");
		}else if($usage=="Heading"){
			$results = $this->db->SelectData("SELECT MAX(gl_code) as last_id FROM acc_ledger_account where sacco_id = '".$_SESSION['office']."' AND account_usage='".$usage."' AND  classification='Equity'");
		}else{
			return null;
		}

		$result=count($results[0]['last_id']) ;
		if ($result==0) {
 			if($usage=="Account"){
				$parent = $this->db->SelectData("SELECT gl_code FROM acc_ledger_account where sacco_id = '".$_SESSION['office']."' AND id='".$parent_id."' ");
				$last = (int)$parent[0]['gl_code'];
				//$next = $last;
                $next = $this->GLCodeNext($last);

			}else{			
        $next = $this->GLCodeNext($parent_code);
			}
		} else {
			$last = $results[0]['last_id'];
			//$next = $last + 1;
			$next = $this->GetNewGLCode($last);
		}
		return $next;
	}  



function GLCodeNext($code){
$stlen = strlen($code);
$glcode1 = rtrim($code, '0');
$glcode1 = $glcode1."1";
$stlen1 = strlen($glcode1);
$exp = $stlen-$stlen1;
$val = pow(10,$exp);
$glcode = $glcode1*$val;
return $glcode;
}

function GetNewGLCode($code){
$stlen = strlen($code);
$glcode1 = rtrim($code, '0');
$newglcode1 = $glcode1+1;

$stlen1 = strlen($newglcode1);
$exp = $stlen-$stlen1;
$val = pow(10,$exp);
$glcode = $newglcode1*$val;
return $glcode;
}
function GetClientDetails($cid){
	return $this->db->selectData("SELECT * FROM members WHERE c_id='".$cid."' ");
}
function GetStaffDetails($cid){
	return $this->db->selectData("SELECT * FROM staff WHERE id ='".$cid."' ");
}

	function getclient_details($actno){

		$rset=array();
		$result=  $this->GetSavingsAccount($actno);

		if(count($result)>0){
			$cid=$result[0]['member_id'];
			$results= $this->GetClientDetails($cid);
			if(!empty($results[0]['company_name'])){
				$displayname=$results[0]['company_name'];
			}else{
				$displayname=$results[0]['firstname']." ".$results[0]['middlename']." ".$results[0]['lastname'];
			}
			array_push($rset,array(
				'signature'=>$results[0]['signature'],
				'displayname'=>$displayname,
			));
			echo json_encode(array("result" =>$rset));
			die();
		}
	}

	function getfixedclient_details($actno){

		$result=  $this->db->selectData("SELECT * FROM  fixed_deposit_account WHERE account_no='".$actno."'");

		if(count($result)>0){
			$cid=$result[0]['member_id'];
			$results= $this->db->selectData("SELECT * FROM members WHERE c_id='".$cid."' ");
			echo $results[0]['firstname']." ".$results[0]['middlename']." ".$results[0]['lastname'] ;
			die();
		}

	}


	function getmemberimage($actno){

		$result=  $this->db->selectData("SELECT * FROM m_savings_account WHERE account_no='".$actno."'");

		if(count($result)==0){
			$result=  $this->db->selectData("SELECT * FROM sm_mobile_wallet WHERE wallet_account_number='".$actno."'");
		}

		if(count($result)>0){
			$cid=$result[0]['member_id'];
			$results= $this->db->selectData("SELECT * FROM m_pictures WHERE type='image' AND status='Current' AND member_id='".$cid."'");
			if (!empty($results[0]['image'])) {
				$imagename = $results[0]['image'];
			}else{
				$imagename = URL. 'public/images/avatar/default.jpg';
			}
			echo "<img src='".$imagename."' style='width:100%; float: center;' class='img-responsive col-lg-12' id ='image' alt='PHOTO'>";
			die();
		}else{	
			echo "";
		}
	}

	function getmemberreceiverimage($actno){

		$result=  $this->db->selectData("SELECT * FROM m_savings_account WHERE account_no='".$actno."'");

		if(count($result)==0){
			$result=  $this->db->selectData("SELECT * FROM sm_mobile_wallet WHERE wallet_account_number='".$actno."'");
		}

		if(count($result)>0){
			$cid=$result[0]['member_id'];
			$results= $this->db->selectData("SELECT * FROM m_pictures WHERE type='image' AND status='Current' AND member_id='".$cid."'");
			if (!empty($results[0]['image'])) {
				$imagename = $results[0]['image'];
			}else{
				$imagename = URL. 'public/images/avatar/default.jpg';
			}
			echo "<img src='".$imagename."' style='width:150px;' class='img-responsive col-lg-12' id ='image_r' alt='PHOTO'>";
			die();
		}else{	
			echo "";
		}
	}


	function getmembersharesimage($actno){ 

		$result=  $this->db->selectData("SELECT * FROM share_account WHERE share_account_no='".$actno."'");

		if(count($result)>0){
			$cid=$result[0]['member_id'];
			$results= $this->db->selectData("SELECT * FROM m_pictures WHERE type='image' AND status='Current' AND member_id='".$cid."'");
			if (!empty($results[0]['image'])) {
				$imagename = $results[0]['image'];
			}else{
				$imagename = URL. 'public/images/avatar/default.jpg';
			}
			echo "<img src='".$imagename."' style='width:150px;' class='img-responsive' id ='image' alt='PHOTO'>";
			die();
		}else{
			echo "";
		}

	}

	function getmembersharesimager($actno){ 

		$result=  $this->db->selectData("SELECT * FROM share_account WHERE share_account_no='".$actno."'");

		if(count($result)>0){
			$cid=$result[0]['member_id'];

			$results= $this->db->selectData("SELECT * FROM m_pictures WHERE type='image' AND status='Current' AND member_id='".$cid."'");
			if (!empty($results[0]['image'])) {
				$imagename = $results[0]['image'];
			}else{
				$imagename = URL. 'public/images/avatar/default.jpg';
			}
			echo "<img src='".$imagename."' style='width:150px;' class='img-responsive' id ='image_r' alt='PHOTO'>";
			die();
		}else{
			echo "";
		}
	}

	function getmembersimage($actno){
		$office=$_SESSION['office'];

	//old query SELECT * FROM members WHERE c_id='".$actno."' AND office_id='".$office."'
	//new query SELECT * FROM m_pictures WHERE type='image' AND status='Current' AND member_id='".$id."'

		$results= $this->db->selectData("SELECT * FROM m_pictures WHERE type='image' AND status='Current' AND member_id='".$actno."'");
		if(count($results)>0){
			if (!empty($results[0]['image'])) {
				$imagename = $results[0]['image'];
			}else{
				$imagename = URL. 'public/images/avatar/default.jpg';
			}
			echo "<img src='".$imagename."' style='width:150px;' class='img-responsive' id ='image' alt='PHOTO'>";
			die();
		}else{
			echo "";
		}

	}

	function getwithdrawnbalance($actno){

		$result=  $this->db->selectData("SELECT * FROM m_savings_account WHERE account_no='".$actno."'");

		if(count($result)>0){
			$product=  $this->db->selectData("SELECT * FROM m_savings_product WHERE id='".$result[0]['product_id']."'");
			$max_id= $this->db->selectData("SELECT max(id) as id FROM m_savings_account_transaction WHERE savings_account_no='".$actno."'");
			$r_balance= $this->db->selectData("SELECT * FROM m_savings_account_transaction WHERE id='".$max_id[0]['id']."'");

			if(count($r_balance)>0){
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
						'dob'=>$client[0]['date_of_birth'],
						'national_id'=>$client[0]['national_id'],
						'address'=>$client[0]['address'],
						'last_trans_id'=>$r_balance[0]['id'],
						'product'=>$product[0]['name'],
						'last_trans_amount'=>$r_balance[0]['amount'],
						'account_opened'=>$result[$key]['submittedon_date'],
						'status'=>$result[$key]['account_status'],
						'actualbalance'=>$result[0]['running_balance'],		
						'acc_update_date'=>date('M j Y g:i A',strtotime($result[0]['last_updated_on'])),
						'rbalance'=>(($result[0]['running_balance'])-($product[0]['min_required_balance']))		
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

	function preparegroupdetails($actno){

		$office=$_SESSION['office'];

		$grp_details =  $this->db->selectData("SELECT * FROM m_group WHERE id='".$actno."' and office_id='".$office."' and status='Active'");

		if(count($grp_details)>0){
			$rset=array();
			array_push($rset,array(
				'member_id'=>$grp_details[0]['id'],
				'displayname'=>$grp_details[0]['name'],
				'dob'=>date("d-m-Y",strtotime($grp_details[0]['registration_date'])),
				'national_id'=>$grp_details[0]['purpose'],
				'address'=>$grp_details[0]['description'],		
			));

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

	function preparememberdetails($actno){

		$office=$_SESSION['office'];


	 
			$result=  $this->db->selectData("SELECT * FROM members WHERE c_id='".$actno."' and office_id='".$office."' and status='Active'");

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
						'dob'=>date("d-m-Y",strtotime($result[0]['date_of_birth'])),
						'national_id'=>$result[0]['national_id'],
						'address'=>$result[0]['address'],		
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

	function prepareinsurancememberdetails($id, $actno){

		$office=$_SESSION['office'];

		$checkshares=  $this->db->selectData("SELECT * FROM insurance_subscriptions WHERE account_no = '$actno' AND member_id='".$id."' AND status='Active'");
		if(count($checkshares)>0){
			$result=  $this->db->selectData("SELECT * FROM members WHERE c_id='".$id."' and office_id='".$office."' and status='Active'");

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
						'dob'=>date("d-m-Y",strtotime($result[0]['date_of_birth'])),
						'national_id'=>$result[0]['national_id'],
						'address'=>$result[0]['address'],	
						'p_id'=>$checkshares[0]['product_id'],	
						's_id'=>$checkshares[0]['id'],		
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
		}else{
			$rset=array();
			array_push($rset,array(
				'member_id'=>'0',
			));
			echo json_encode(array("result" =>$rset));		
			die();
		}
	}

	function loanProductApplied($id){

		$result=  $this->db->selectData("SELECT * FROM m_product_loan WHERE id='".$id."'");
		if(count($result)>0){

			$rset=array();
			foreach ($result as $key => $value) {
				array_push($rset,array(
					'id'=>$result[$key]['id'],
					'purpose'=>$result[$key]['description'],
					'nominal_interest'=>$result[$key]['nominal_interest_rate_per_period'],
					'days'=>$result[0]['days_in_year'],
					'installment'=>$result[0]['installment_option'],
					'interest_option'=>$result[0]['interest_method'],
					'duration'=>$result[0]['duration'],
					'min_duration_val'=>$result[0]['min_duration_value'],		
					'max_duration_val'=>$result[0]['max_duration_value'],	
					'min_principal_amount'=>$result[0]['min_principal_amount'],
					'max_principal_amount'=>$result[0]['max_principal_amount'],
					'grace_period'=>$result[0]['grace_period'],
					'grace_period_val'=>$result[0]['grace_period_value'],
					'interest_period'=>$result[0]['interest_period'],		
					'insurance'=>$result[0]['insurance'],		
					'stamp_duty'=>$result[0]['stamp_duty'],		
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


	function getinsuranceproductapplied($id, $amt){

		$result=  $this->db->selectData("SELECT * FROM insurance_products AS a JOIN insurance_categories AS b ON a.category = b.id WHERE a.id='".$id."'");

		if(count($result)>0){

			$rset=array();
			foreach ($result as $key => $value) {
				if ($result[0]['claim_penalty'] == 'flat') {
					$penalty = $result[0]['claim_penalty'];
				} else {
					$penalty = (($result[0]['claim_penalty'] * $amt)/100);
				}
				array_push($rset,array(
					'id' => $result[$key]['id'],
					'name' => $result[0][2],
					'purpose' => $result[$key]['description'],
					'min_principal_amount' => $result[0]['min_amount'],
					'max_principal_amount' => $result[0]['max_amount'],
					'category' => $result[0]['name'],
					'payment_freq' => $result[0]['payment_freq'],
					'payout_freq' => $result[0]['payout_freq'],
					'cover' => ($result[0]['cover'] * $amt),
					'reward' => (($result[0]['reward'] * $amt)/100),
					'claim_penalty' => $penalty,
					'recovery_time' => $result[0]['recovery_time'],
					'recovery_time_type' => $result[0]['recovery_time_type']	
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

	function convertNumber($number) {

		list($integer) = explode(".", (string) $number);

		$output = "";

		if ($integer[0] == "-") {
			$output = "negative ";
			$integer    = ltrim($integer, "-");
		} else if ($integer[0] == "+") {
			$output = "positive ";
			$integer    = ltrim($integer, "+");
		}

		if ($integer[0] == "0") {
			$output .= "zero";
		} else {
			$integer = str_pad($integer, 36, "0", STR_PAD_LEFT);
			$group   = rtrim(chunk_split($integer, 3, " "), " ");
			$groups  = explode(" ", $group);

			$groups2 = array();
			foreach ($groups as $g){
				$groups2[] = $this->convertThreeDigit($g[0], $g[1], $g[2]);
			}

			for ($z = 0; $z < count($groups2); $z++) {
				if ($groups2[$z] != "") {
					$output .= $groups2[$z] .$this->convertGroup(11 - $z) . (
						$z < 11
						&& !array_search('', array_slice($groups2, $z + 1, -1))
						&& $groups2[11] != ''
						&& $groups[11][0] == '0'
						? " and "
						: ", "
					);
				}
			}

			$output = rtrim($output, ", ");
		}


		return $output;
	}

	function convertGroup($index) {
		switch ($index) {
			case 11:
			return " decillion";
			case 10:
			return " nonillion";
			case 9:
			return " octillion";
			case 8:
			return " septillion";
			case 7:
			return " sextillion";
			case 6:
			return " quintrillion";
			case 5:
			return " quadrillion";
			case 4:
			return " trillion";
			case 3:
			return " billion";
			case 2:
			return " million";
			case 1:
			return " thousand";
			case 0:
			return "";
		}
	}

	function convertThreeDigit($digit1, $digit2, $digit3) {
		$buffer = "";

		if ($digit1 == "0" && $digit2 == "0" && $digit3 == "0"){
			return "";
		}

		if ($digit1 != "0") {
			$buffer .=  $this->convertDigit($digit1) . " hundred";
			if ($digit2 != "0" || $digit3 != "0"){
				$buffer .= " and ";
			}
		}

		if ($digit2 != "0") {
			$buffer .=  $this->convertTwoDigit($digit2, $digit3);
		} else if ($digit3 != "0") {
			$buffer .=  $this->convertDigit($digit3);
		}

		return $buffer;
	}

	function convertTwoDigit($digit1, $digit2){
		if ($digit2 == "0"){
			switch ($digit1){
				case "1":
				return "ten";
				case "2":
				return "twenty";
				case "3":
				return "thirty";
				case "4":
				return "forty";
				case "5":
				return "fifty";
				case "6":
				return "sixty";
				case "7":
				return "seventy";
				case "8":
				return "eighty";
				case "9":
				return "ninety";
			}
		} else if ($digit1 == "1"){
			switch ($digit2){
				case "1":
				return "eleven";
				case "2":
				return "twelve";
				case "3":
				return "thirteen";
				case "4":
				return "fourteen";
				case "5":
				return "fifteen";
				case "6":
				return "sixteen";
				case "7":
				return "seventeen";
				case "8":
				return "eighteen";
				case "9":
				return "nineteen";
			}
		} else {
			$temp = $this->convertDigit($digit2);
			switch ($digit1) {
				case "2":
				return "twenty-$temp";
				case "3":
				return "thirty-$temp";
				case "4":
				return "forty-$temp";
				case "5":
				return "fifty-$temp";
				case "6":
				return "sixty-$temp";
				case "7":
				return "seventy-$temp";
				case "8":
				return "eighty-$temp";
				case "9":
				return "ninety-$temp";
			}
		}
	}

	function convertDigit($digit){
		switch ($digit) {
			case "0":
			return "zero";
			case "1":
			return "one";
			case "2":
			return "two";
			case "3":
			return "three";
			case "4":
			return "four";
			case "5":
			return "five";
			case "6":
			return "six";
			case "7":
			return "seven";
			case "8":
			return "eight";
			case "9":
			return "nine";
		}
	}

// ACCOUNTING PART AND GL ENTRY

	function GetGLPointers($id,$prodType,$transtype, $office) {
		$parent_office =$office;
		return $this->db->SelectData("SELECT * FROM  acc_gl_pointers JOIN transaction_type ON transaction_type.transaction_type_id=acc_gl_pointers.transaction_type_id where acc_gl_pointers.product_type_id='".$prodType."' AND sacco_id = '".$parent_office."' and product_id = '".$id."'  AND transaction_type.transaction_type_name='".$transtype."' ");   
	}

	function GetGLChargePointers($id, $prodType, $transtype, $transID) {
		$prodType = 6;
		$parent_office =$_SESSION['office'];
		return $this->db->SelectData("SELECT * FROM  acc_gl_pointers JOIN transaction_type ON transaction_type.transaction_type_id=acc_gl_pointers.transaction_type_id where acc_gl_pointers.transaction_type_id = '$transID' AND acc_gl_pointers.product_type_id='$prodType' AND sacco_id = '$parent_office' AND acc_gl_pointers.product_id = '$id' ");  
	}

	function getTransactionID($name){
		$results =  $this->db->SelectData("SELECT * FROM transaction_type WHERE transaction_type_name = '$name'");

		if (count($results) > 0) {
			return $results[0]['transaction_type_id'];
		} else{
			return NULL;
		}

	}

	function getTransactionCharges($id){
		$office = $_SESSION['office'];
		return $this->db->SelectData("SELECT * FROM m_charge WHERE transaction_type_id = '$id' AND office_id = '$office' AND status = 'Active'");
	}
	
	function getLoanProductTransactionCharges($product_id,$id){
		$office = $_SESSION['office'];
		return $this->db->SelectData("SELECT * FROM m_charge c inner join m_product_loan_charge p ON c.id = p.charge_id WHERE p.product_loan_id = '$product_id' AND  transaction_type_id = '$id' AND office_id = '$office' AND status = 'Active'");
	}

	function GetGLSAPointers($id,$prodType,$transtype) {
		return $this->db->SelectData("SELECT * FROM  acc_gl_pointers JOIN transaction_type ON transaction_type.transaction_type_id=acc_gl_pointers.transaction_type_id where transaction_type.product_type='".$prodType."' AND acc_gl_pointers.product_id = '".$id."'  AND transaction_type.transaction_type_name='".$transtype."' ");

	}

	function getGlaccountdetails($id){
		return $this->db->selectData("SELECT * FROM acc_ledger_account where sacco_id = '".$_SESSION['office']."' AND id='".$id."'");
	}

	function GetSaccoGLPointers($id,$prodType,$transtype,$sacco) {
		return $this->db->SelectData("SELECT * FROM  acc_gl_pointers JOIN transaction_type ON transaction_type.transaction_type_id=acc_gl_pointers.transaction_type_id where transaction_type.product_type='".$prodType."' AND sacco_id = '".$sacco."' and product_id = '".$id."'  AND transaction_type.transaction_type_name='".$transtype."' ");

	}

	function SendSMS($phone, $message){

		$url = SMS_URL;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,"message=".$message."&phone=".$phone."");
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));

	// receive server response 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$server_output = curl_exec ($ch);
		curl_close ($ch);

		return  $server_output;

	}

	function formatNumber($mm_number){

		$output = substr($mm_number, 0, 1);
		$output2 = substr($mm_number, 0, 4);

		if($output=="0" || $output2=="+256"){
			if($output=="0" ){
				$FormatedPhoneNumber =  "256".(int)$mm_number;
			}else{
				$FormatedPhoneNumber =  str_replace("+","",$mm_number);
			}
			return $FormatedPhoneNumber;
		}else{
			return $mm_number;
		}

	}

	function Devicetoken($wallet){
		$member_details=$this->db->SelectData("SELECT * FROM members WHERE mobile_no='".trim($wallet)."' ");
		return $member_details[0]['device_token'];
	}
	


	function sendPushNotification($message, $wallet){

		$dev = $this->Devicetoken($wallet);
		$d = explode("_",$dev);
		$device = $d[0];
		$data = array(
			'message' => $message,
			"m_from" => "Clic.World",
			"date" => date('Y-m-d h:i'),
		);

		$ids = array($device);

		$this->pushy->sendPushNotification($data, $ids,PUSHY_KEY);
		return;
	}

}

?>
