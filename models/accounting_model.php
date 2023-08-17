<?php

class Accounting_model extends Model{
	
public function __construct(){
	parent::__construct();	
	//Auth::handleSignin();
    $this->logUserActivity(NULL); 

    $url = explode("/", $_SERVER['REQUEST_URI']);
    if (isset($url[3])) {
    	if ($url[3] != 'returncash') {
	    	if (!$this->checkTransactionStatus()) {
	    		header('Location: ' . URL); 
	    	}
		}
	}
}

function getCGAPAccountsCount(){	
	$office_id = $_SESSION['office'];
	$result=$this->db->selectData("SELECT COUNT(account) AS COUNT FROM reports_mapping WHERE sacco_id='".$office_id."'");

	return $result[0][0];
}

function getSelectedCGAPAccounts(){
	$office_id = $_SESSION['office'];
	$result=$this->db->selectData("SELECT * FROM reports_mapping WHERE sacco_id='".$office_id."'");

	$accounts = array();
	foreach ($result as $key => $value) {
		$accounts[$key]['account'] = $value['account'];

		$sub_accounts = array();
		$accs = explode(",", $value['sub_accounts']);
		foreach ($accs as $key1 => $value1) {
			array_push($sub_accounts, $value1);
		} 

		$accounts[$key]['sub_accounts'] = $sub_accounts;

	}

	return $accounts;
}

function insertcgapaccounts($data){

	$this->insertCGAPdata($data);
	header("Location:".URL ."accounting?msg=cgapsuccess");

}

function updatecgapaccounts($data){

	$this->deleteSaccoCGAPdata($_SESSION['office']);
	$this->insertCGAPdata($data);
	header("Location:".URL ."accounting?msg=cgapudate");
}

function insertCGAPdata($data){	

	foreach ($data['account'] as $key => $value) {
		
		$accounts = "";
		foreach ($data[$value . "_accounts"] as $key1 => $value1) {
			$accounts .= $value1 . ",";
		}
		$accounts = rtrim($accounts, ",");

		$postData = array(
			'sacco_id' => $_SESSION['office'],
			'report' => "CGAP",
			'account' => $value,
			'sub_accounts' => $accounts
		);
		$this->db->InsertData('reports_mapping', $postData);

	}
}

function deleteSaccoCGAPdata($sacco){

	$sth = $this->db->prepare("DELETE FROM `reports_mapping` WHERE `sacco_id` = '$sacco'");
    $success = $sth->execute();
}

function ImportExcelAccounts($data){

	$ext = strtolower(pathinfo($data['audit_file_temp'],PATHINFO_EXTENSION));		
	
	$now = date('d_m_Y');
	$file_name = $_SESSION['office'].'_'.$_SESSION['user_id'] . '_' . $now;

	if (!file_exists('public/accounts')) {
	    mkdir('public/accounts', 0777, true);
	}
	$dest = 'public/accounts/' . $file_name . '.csv';

	if(move_uploaded_file($data['audit_file_temp'], $dest)){
		header("Location:".URL ."accounting/imgexcelaccounts?msg=success&action=verify&path=".$dest);
	} else {
		header("Location:".URL ."accounting/imgexcelaccounts?msg=failed");
	}
}

function verifycsv($uploadedfile){

	$filerec = file_get_contents($uploadedfile);
	$string = str_getcsv($filerec, "\r");

	$first_row_items = explode(',', $string[0]);
	if(sizeof($first_row_items) == 7){
		$final_verdict = false;
		foreach ($string as $key => $value) {
			$verdict = true;//$this->checkValues($data[1], $data[2]);
			if($verdict){
				$final_verdict = true;
			}else{
				$final_verdict = false;
			}   
		}
		if ($final_verdict) {
			header("Location:".URL ."accounting/imgexcelaccounts?ver=success&action=process&path=$uploadedfile");
		} else {
			header("Location:".URL ."accounting/imgexcelaccounts?ver=data");
		}
	}else{
		header("Location:".URL ."accounting/imgexcelaccounts?ver=fileformat");
	}
}

function processcsv($uploadedfile){

	$filerec = file_get_contents($uploadedfile);
	$string = str_getcsv($filerec, "\r");
	
	foreach ($string as $key => $value) {
		if ($key != 0) {
			$data = explode(',', $value);
			if ($data[1] != '') {
				$postData = array(
					'sacco_id' => $_SESSION['office'],
					'name' =>$data[1],
					'parent_id' => $data[2],
					'hierarchy' => NULL,//$data[3],
					'gl_code' => $data[3],
					'disabled' => 'No',
					'account_usage' => $data[4],
					'classification' => $data[5],
					'description' => $data[6],
					);
				$this->db->InsertData('acc_ledger_account', $postData);
			}
		}   
	}
	header("Location:".URL ."accounting/imgexcelaccounts?msg=success");
}

function getTellers(){
	$office=$_SESSION['office'];
	$acc = $this->db->selectData("SELECT * FROM m_staff WHERE access_level != 'A' AND can_transact = 'Yes' AND office_id='".$office."'");
	return $acc;
}

function getTellerDetails($id){
	$office=$_SESSION['office'];
	$acc = $this->db->selectData("SELECT * FROM m_teller_transactions AS a JOIN m_staff AS b ON a.user_id = b.id WHERE a.user_id = '$id' AND a.sacco_id='".$office."'");
	return $acc;	
}

function getCashAccountDetails(){
	$office=$_SESSION['office'];
	$acc = $this->db->selectData("SELECT * FROM acc_ledger_account_mapping AS a JOIN acc_ledger_account AS b ON a.account_id = b.id WHERE a.product_id=0 AND a.office_id='".$office."'");

	if (!empty($acc)) {
		return $acc[0];
	} else {
		return '';
	}
}

function getCashAccountBalance(){
	$office=$_SESSION['office'];
	$acc = $this->db->selectData("SELECT account_id FROM acc_ledger_account_mapping WHERE product_id=0 AND office_id='".$office."'");

	$balance = 0;
	if (!empty($acc)) {
		$res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry WHERE account_id='".$acc[0]['account_id']."' AND office_id='".$office."'");

		foreach ($res as $key => $value) {
			if($value['transaction_type'] == 'DR'){
				$balance += $value['amount'];
			}else if ($value['transaction_type'] == 'CR'){
				$balance -= $value['amount'];
			}
		}
	}
	return $balance;
}

function getGLTellerCashAccountBalance(){
	$office=$_SESSION['office'];
	$id = $this->getTellerAccountID();

	$balance = 0;
	if (!empty($id)) {
		$res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry WHERE account_id='".$id."' AND office_id='".$office."'");

		foreach ($res as $key => $value) {
			if($value['transaction_type'] == 'DR'){
				$balance += $value['amount'];
			}else if ($value['transaction_type'] == 'CR'){
				$balance -= $value['amount'];
			}
		}
	}
	return $balance;
}

function getTellerReturns(){
	$office=$_SESSION['office'];
	$results = $this->db->selectData("SELECT * FROM m_teller_transactions AS a JOIN m_staff AS b ON a.user_id = b.id WHERE a.status = 'Returned' AND a.sacco_id='".$office."'");

	return $results;}

function returnCash($data){

	if ($data['expected'] == $data['amount']){

	    $postData = array(
			'sacco_id' => $_SESSION['office'],
			'user_id' => $_SESSION['user_id'],
			'amount' => $data['amount'],
			'transaction_type' => 'Return',
			'date' => date('Y-m-d H:i:s'),
			'description' => "Cash Return from " . $_SESSION['name'],
			'balance' => $this->getTellerAccountBalance($_SESSION['user_id']),
			'approved_amount' => '',
			'approval_date' => '',
			'approved_by' => '',
			'close_date' => date('Y-m-d H:i:s'),
			'status' => "Returned"
		);
		
		$this->db->InsertData('m_teller_transactions', $postData);
		header('Location: ' . URL . '?msg=returned'); 
	} else {
		header('Location: ' . URL . '?msg=returned');
	}
}

function requestCash($data){

    $postData = array(
		'sacco_id' => $_SESSION['office'],
		'user_id' => $_SESSION['user_id'],
		'amount' => $data['amount'],
		'transaction_type' => 'Request',
		'date' => date('Y-m-d H:i:s'),
		'description' => "Cash Request from " . $_SESSION['name'],
		'balance' => $this->getTellerAccountBalance($_SESSION['user_id']),
		'approved_amount' => '',
		'approval_date' => '',
		'approved_by' => '',
		'close_date' => '',
		'status' => "Pending"
	);
	
	$this->db->InsertData('m_teller_transactions', $postData);
	header('Location: ' . URL . '?msg=sent'); 
}

function getTellerRequests(){
	$office=$_SESSION['office'];
	$results = $this->db->selectData("SELECT * FROM m_teller_transactions AS a JOIN m_staff AS b ON a.user_id = b.id WHERE a.status = 'Pending' AND a.sacco_id='".$office."'");

	return $results;
}


function getTellerAccountDetails($id){
	$office=$_SESSION['office'];
	
	return $this->db->selectData("SELECT * FROM acc_ledger_account WHERE id='".$id."' AND sacco_id ='".$office."'");
}

function createNewGlAccount(){
 
	$data =  $_POST;
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
//$glcode =   str_pad($glcode, 5, '0', STR_PAD_RIGHT);

		if(!empty($glcode)){
	        $postData = array(
				'sacco_id' =>$office,
				'classification' =>$data['account_type'],
				'gl_code' =>$glcode,
				'account_usage' => $data['account_usage'],
				'name' => $data['account_name'],
				'parent_id' => $data['parent'],
				'description' => $data['description']
			);


		    $this->db->InsertData('acc_ledger_account', $postData);
		    header('Location: ' . URL . 'accounting/chartsofaccount?msg=success'); 
		}else{
			header('Location: ' . URL . 'accounting/newglaccount?msg=failed'); 		
		}
	}else{
		header('Location: ' . URL . 'accounting/newglaccount?msg=failed');
	}

}

function UpDateGlAccount(){
			$data =  $_POST;
			$id=$data['id'];
	        $postData = array(
         
			'classification' => $data['account_type'],
            'account_usage' => $data['account_usage'],
            'name' => $data['account_name'],
            'parent_id' => $data['parent'],
			'description' => $data['description']
                  );
       
		 $this->db->UpdateData('acc_ledger_account', $postData,"`id` = '{$id}'");
         header('Location: ' . URL . 'accounting/chartsofaccount?msg=success'); 
}
function getPaymentType(){
	
	
	return $this->db->selectData("SELECT * FROM payment_mode");
}
function getAssets(){
	 $office=$_SESSION['office'];
	
	return $this->db->selectData("SELECT * FROM acc_ledger_account where classification='Assets' AND sacco_id='".$office."'");
}
function getLiability(){
		 $office=$_SESSION['office'];	
	return $this->db->selectData("SELECT * FROM acc_ledger_account where classification='Liabilities' AND sacco_id='".$office."'");
}
function getEquity(){
	 $office=$_SESSION['office'];	
	return $this->db->selectData("SELECT * FROM acc_ledger_account where classification='Equity' AND sacco_id='".$office."'");
}
function getIncome(){
	$office=$_SESSION['office'];
	return $this->db->selectData("SELECT * FROM acc_ledger_account where classification='Incomes' AND sacco_id='".$office."'");
}
function getExpenses(){
	$office=$_SESSION['office'];		
	return $this->db->selectData("SELECT * FROM acc_ledger_account where classification='Expenses' and sacco_id='".$office."'  ");
}
function getGlaccount(){
$office=$_SESSION['office'];		
   return $this->db->selectData("SELECT * FROM acc_ledger_account where account_usage='Account' and sacco_id = $office ORDER BY name ASC");
}
function getGlparent($id=null){
	$office=$_SESSION['office'];	
	if($id==null){
		return $this->db->selectData("SELECT DISTINCT name FROM acc_ledger_account where account_usage='Heading' and sacco_id ='".$office."'");	
	}else{	
		return $this->db->selectData("SELECT DISTINCT name FROM acc_ledger_account where account_usage='Heading' and sacco_id ='".$office."' and id!=$id ");
	}
}

function getHeaderAccounts($id) {
	$office=$_SESSION['office'];
	$query=$this->db->selectData("SELECT * FROM acc_ledger_account where account_usage='Heading' and sacco_id ='".$office."' AND classification='".$id."' ");
	print_r(json_encode($query));
	die();
}

function getallglaccounts() {
$office=$_SESSION['office'];

   $query=$this->db->selectData("SELECT * FROM acc_ledger_account where account_usage='Account' AND sacco_id='".$office."' ORDER BY classification ASC");
  
 // print_r($query);die();
 	 print_r(json_encode($query));
               die();

}

function getCharts(){
	$office=$_SESSION['office'];
	  $result =  $this->db->SelectData("SELECT * FROM acc_ledger_account WHERE sacco_id='".$office."'   order by classification ");
     
		$count=count($result);
		if($count>0){	
	foreach ($result as $key => $value) {
                $rset[$key]['id'] = $result[$key]['id']; 
                $rset[$key]['name'] = $result[$key]['name']; 
				$rset[$key]['gl_code'] = $result[$key]['gl_code'];
				$rset[$key]['account_type'] =$result[$key]['classification'];
				$rset[$key]['disabled'] = $result[$key]['disabled'];
                $rset[$key]['account_usage'] = $result[$key]['account_usage'];
          }
        return $rset;
		}
}

function getSampleCharts(){
	$office=$_SESSION['office'];
	$result =  $this->db->SelectData("SELECT gl_code FROM acc_ledger_account WHERE sacco_id='".$office."'   order by classification ");

	$codes = "";
	foreach ($result as $key => $value) {
		$codes .= "AND gl_code != " . $value['gl_code'] . " ";
	}
	$result =  $this->db->SelectData("SELECT * FROM acc_ledger_account_template WHERE gl_code != '' ". $codes . " order by classification ");
     
 	$count=count($result);
	if($count>0){	
		foreach ($result as $key => $value) {
            $rset[$key]['id'] = $result[$key]['id']; 
            $rset[$key]['name'] = $result[$key]['name']; 
			$rset[$key]['gl_code'] = $result[$key]['gl_code'];
			$rset[$key]['account_type'] =$result[$key]['classification'];
			$rset[$key]['disabled'] = $result[$key]['disabled'];
            $rset[$key]['account_usage'] = $result[$key]['account_usage'];
      	}
    	return $rset;
	}
}

function CopyAccounts($data){

	$office=$_SESSION['office'];
	
	foreach ($data as $key => $value) {
		$data_table =  $this->db->SelectData("SELECT * FROM acc_ledger_account_template WHERE id = $value");
		
		$code = $data_table[0]['gl_code'];
		$name = $data_table[0]['name'];
		$result =  $this->db->SelectData("SELECT * FROM acc_ledger_account WHERE gl_code = $code AND sacco_id = $office");

		$count = count($result);
		if($count == 0){
       		$postDr = array();
       		$postDr['sacco_id'] = $office;
            $postDr['name'] = $data_table[0]['name']; 
            $postDr['parent_id'] = $data_table[0]['parent_id']; 
            $postDr['hierarchy'] = $data_table[0]['hierarchy']; 
			$postDr['gl_code'] = $data_table[0]['gl_code'];
			$postDr['disabled'] = $data_table[0]['disabled'];
            $postDr['account_usage'] = $data_table[0]['account_usage'];
			$postDr['classification'] =$data_table[0]['classification'];
			$postDr['description'] = $data_table[0]['description'];
       		$this->db->InsertData('acc_ledger_account', $postDr);
     	}
	}
  
  	header('Location: ' . URL . 'accounting/chartsofaccount?msg=imported'); 
}

function getGlaccountdetails($id){
	
	
	$result= $this->db->selectData("SELECT * FROM acc_ledger_account where id=$id");
		foreach ($result as $key => $value) {
                $rset[$key]['id'] = $result[$key]['id'];
	            $rset[$key]['parent_id'] = $result[$key]['parent_id']; 			
                $rset[$key]['name'] = $result[$key]['name']; 
				$rset[$key]['gl_code'] = $result[$key]['gl_code'];
				$rset[$key]['description'] = $result[$key]['description'];
				$rset[$key]['account_type'] = $result[$key]['classification'];
				$rset[$key]['disabled'] = $result[$key]['disabled'];
                $rset[$key]['usage_id'] = $result[$key]['account_usage'];
                $rset[$key]['account_usage'] = $result[$key]['account_usage'];
          }
        return $rset;
	
}
function getGlaccountdetailsvalue($id){
	
	$result= $this->db->selectData("SELECT * FROM acc_ledger_account where id=$id");
		
        return $result;
	}

function getbranches(){
	
	
	return $this->db->selectData("SELECT * FROM m_branch");
}

function getGlaccountname($id){
	if($id!=''){
		$results =  $this->db->SelectData("SELECT * FROM acc_ledger_account where id=$id");

    return  $results[0]['name'];
	}else{
		 return  '';
	}
	
	
}
function initializeOpeningBalance(){

	$cash_account = $this->getCashAccountDetails();

	$data = $_POST;
	$transaction_id = 'OP'. uniqid();
	$assetDataDR = $liabilityDataDR = $equityDataDR = $incomeDataDR = $expensesDataDR = array();
	$assetDataCR = $liabilityDataCR = $equityDataCR = $incomeDataCR = $expensesDataCR = array();
	// ASSETS
	foreach ($data['id_asset'] as $key => $value) {
		
		if ($value == $cash_account['id']) {
			$this->updateAdminCashAccountBalance($data['dr_asset'][$key]);
		}

		if ($value != "") {
			$assetDataDR['account_id'] = $value;
			$assetDataDR['office_id'] = $_SESSION['office'];
			$assetDataDR['branch_id'] = $_SESSION['branchid'];
			$assetDataDR['transaction_id'] = $transaction_id;
			$assetDataDR['manual_entry'] = 'Yes';		
			$assetDataDR['amount'] = $data['dr_asset'][$key];
			$assetDataDR['transaction_type'] = "DR";
			$assetDataDR['description'] = "Opening Balance for " . ucfirst($data['name_asset'][$key]);
			$assetDataDR['trial_balance_side'] = $this->getAccountSide($value);
			$assetDataDR['createdby_id'] = $_SESSION['user_id'];

			$this->db->InsertData('acc_gl_journal_entry', $assetDataDR);

			$assetDataCR['account_id'] = $value;
			$assetDataCR['office_id'] = $_SESSION['office'];
			$assetDataCR['branch_id'] = $_SESSION['branchid'];
			$assetDataCR['transaction_id'] = $transaction_id;
			$assetDataCR['manual_entry'] = 'Yes';		
			$assetDataCR['amount'] = $data['cr_asset'][$key];
			$assetDataCR['transaction_type'] = "CR";
			$assetDataCR['description'] = "Opening Balance for " . ucfirst($data['name_asset'][$key]);
			$assetDataCR['trial_balance_side'] = $this->getAccountSide($value);
			$assetDataCR['createdby_id'] = $_SESSION['user_id'];

			$this->db->InsertData('acc_gl_journal_entry', $assetDataCR);
		}
	}

	// LIABILITIES
	foreach ($data['id_liability'] as $key => $value) {
		
		if ($value != "") {
			$liabilityDataDR['account_id'] = $value;
			$liabilityDataDR['office_id'] = $_SESSION['office'];
			$liabilityDataDR['branch_id'] = $_SESSION['branchid'];
			$liabilityDataDR['transaction_id'] = $transaction_id;
			$liabilityDataDR['manual_entry'] = 'Yes';		
			$liabilityDataDR['amount'] = $data['dr_liability'][$key];
			$liabilityDataDR['transaction_type'] = "DR";
			$liabilityDataDR['description'] = "Opening Balance for " . ucfirst($data['name_liability'][$key]);
			$liabilityDataDR['trial_balance_side'] = $this->getAccountSide($value);
			$liabilityDataDR['createdby_id'] = $_SESSION['user_id'];

			$this->db->InsertData('acc_gl_journal_entry', $liabilityDataDR);

			$liabilityDataCR['account_id'] = $value;
			$liabilityDataCR['office_id'] = $_SESSION['office'];
			$liabilityDataCR['branch_id'] = $_SESSION['branchid'];
			$liabilityDataCR['transaction_id'] = $transaction_id;
			$liabilityDataCR['manual_entry'] = 'Yes';		
			$liabilityDataCR['amount'] = $data['cr_liability'][$key];
			$liabilityDataCR['transaction_type'] = "CR";
			$liabilityDataCR['description'] = "Opening Balance for " . ucfirst($data['name_liability'][$key]);
			$liabilityDataCR['trial_balance_side'] = $this->getAccountSide($value);
			$liabilityDataCR['createdby_id'] = $_SESSION['user_id'];

			$this->db->InsertData('acc_gl_journal_entry', $liabilityDataCR);
		}
	}

	// EQUITY
	foreach ($data['id_equity'] as $key => $value) {
	
		if ($value != "") {
			$equityDataDR['account_id'] = $value;
			$equityDataDR['office_id'] = $_SESSION['office'];
			$equityDataDR['branch_id'] = $_SESSION['branchid'];
			$equityDataDR['transaction_id'] = $transaction_id;
			$equityDataDR['manual_entry'] = 'Yes';		
			$equityDataDR['amount'] = $data['dr_equity'][$key];
			$equityDataDR['transaction_type'] = "DR";
			$equityDataDR['description'] = "Opening Balance for " . ucfirst($data['name_equity'][$key]);
			$equityDataDR['trial_balance_side'] = $this->getAccountSide($value);
			$equityDataDR['createdby_id'] = $_SESSION['user_id'];

			$this->db->InsertData('acc_gl_journal_entry', $equityDataDR);

			$equityDataCR['account_id'] = $value;
			$equityDataCR['office_id'] = $_SESSION['office'];
			$equityDataCR['branch_id'] = $_SESSION['branchid'];
			$equityDataCR['transaction_id'] = $transaction_id;
			$equityDataCR['manual_entry'] = 'Yes';		
			$equityDataCR['amount'] = $data['cr_equity'][$key];
			$equityDataCR['transaction_type'] = "CR";
			$equityDataCR['description'] = "Opening Balance for " . ucfirst($data['name_equity'][$key]);
			$equityDataCR['trial_balance_side'] = $this->getAccountSide($value);
			$equityDataCR['createdby_id'] = $_SESSION['user_id'];

			$this->db->InsertData('acc_gl_journal_entry', $equityDataCR);
		}
	}

	// INCOME
	foreach ($data['id_income'] as $key => $value) {
		
		if ($value != "") {
			$incomeDataDR['account_id'] = $value;
			$incomeDataDR['office_id'] = $_SESSION['office'];
			$incomeDataDR['branch_id'] = $_SESSION['branchid'];
			$incomeDataDR['transaction_id'] = $transaction_id;
			$incomeDataDR['manual_entry'] = 'Yes';		
			$incomeDataDR['amount'] = $data['dr_income'][$key];
			$incomeDataDR['transaction_type'] = "DR";
			$incomeDataDR['description'] = "Opening Balance for " . ucfirst($data['name_income'][$key]);
			$incomeDataDR['trial_balance_side'] = $this->getAccountSide($value);
			$incomeDataDR['createdby_id'] = $_SESSION['user_id'];

			$this->db->InsertData('acc_gl_journal_entry', $incomeDataDR);

			$incomeDataCR['account_id'] = $value;
			$incomeDataCR['office_id'] = $_SESSION['office'];
			$incomeDataCR['branch_id'] = $_SESSION['branchid'];
			$incomeDataCR['transaction_id'] = $transaction_id;
			$incomeDataCR['manual_entry'] = 'Yes';		
			$incomeDataCR['amount'] = $data['cr_income'][$key];
			$incomeDataCR['transaction_type'] = "CR";
			$incomeDataCR['description'] = "Opening Balance for " . ucfirst($data['name_income'][$key]);
			$incomeDataCR['trial_balance_side'] = $this->getAccountSide($value);
			$incomeDataCR['createdby_id'] = $_SESSION['user_id'];

			$this->db->InsertData('acc_gl_journal_entry', $incomeDataCR);
		}
	}

	// EXPENSES
	foreach ($data['id_expense'] as $key => $value) {
		
		if ($value != "") {
			$expensesDataDR['account_id'] = $value;
			$expensesDataDR['office_id'] = $_SESSION['office'];
			$expensesDataDR['branch_id'] = $_SESSION['branchid'];
			$expensesDataDR['transaction_id'] = $transaction_id;
			$expensesDataDR['manual_entry'] = 'Yes';		
			$expensesDataDR['amount'] = $data['dr_expense'][$key];
			$expensesDataDR['transaction_type'] = "DR";
			$expensesDataDR['description'] = "Opening Balance for " . ucfirst($data['name_expenses'][$key]);
			$expensesDataDR['trial_balance_side'] = $this->getAccountSide($value);
			$expensesDataDR['createdby_id'] = $_SESSION['user_id'];

			$this->db->InsertData('acc_gl_journal_entry', $expensesDataDR);

			$expensesDataCR['account_id'] = $value;
			$expensesDataCR['office_id'] = $_SESSION['office'];
			$expensesDataCR['branch_id'] = $_SESSION['branchid'];
			$expensesDataCR['transaction_id'] = $transaction_id;
			$expensesDataCR['manual_entry'] = 'Yes';		
			$expensesDataCR['amount'] = $data['cr_expense'][$key];
			$expensesDataCR['transaction_type'] = "CR";
			$expensesDataCR['description'] = "Opening Balance for " . ucfirst($data['name_expenses'][$key]);
			$expensesDataCR['trial_balance_side'] = $this->getAccountSide($value);
			$expensesDataCR['createdby_id'] = $_SESSION['user_id'];

			$this->db->InsertData('acc_gl_journal_entry', $expensesDataCR);
		}
	}

	header('Location: ' . URL . 'accounting?msg=openingsuccess'); 
		
}

function skipOpeningBalances(){
	$cashAcountdetails = $this->getCashAccountDetails();

	if (!empty($cashAcountdetails)) {
	
		$transaction_id = 'OP'. uniqid();

		$dataDr['account_id'] = $cashAcountdetails['id'];
		$dataDr['office_id'] = $_SESSION['office'];
		$dataDr['branch_id'] = $_SESSION['branchid'];
		$dataDr['transaction_id'] = $transaction_id;
		$dataDr['manual_entry'] = 'Yes';		
		$dataDr['amount'] = 0;
		$dataDr['transaction_type'] = "DR";
		$dataDr['description'] = "Opening Balance for " . ucfirst($cashAcountdetails['name']);
		$dataDr['trial_balance_side'] = $this->getAccountSide($cashAcountdetails['id']);
		$dataDr['createdby_id'] = $_SESSION['user_id'];

		$this->db->InsertData('acc_gl_journal_entry', $dataDr);

		$dataCr['account_id'] = $cashAcountdetails['id'];
		$dataCr['office_id'] = $_SESSION['office'];
		$dataCr['branch_id'] = $_SESSION['branchid'];
		$dataCr['transaction_id'] = $transaction_id;
		$dataCr['manual_entry'] = 'Yes';		
		$dataCr['amount'] = 0;
		$dataCr['transaction_type'] = "CR";
		$dataCr['description'] = "Opening Balance for " . ucfirst($cashAcountdetails['name']);
		$dataCr['trial_balance_side'] = $this->getAccountSide($cashAcountdetails['id']);
		$dataCr['createdby_id'] = $_SESSION['user_id'];

		$this->db->InsertData('acc_gl_journal_entry', $dataCr);

		header('Location: ' . URL . 'accounting?msg=openingsuccess'); 

	} else {
		header('Location: ' . URL . 'accounting/setupglaccounts?msg=missing'); 
	}
}

function updateAdminCashAccountBalance($amount){

	$admin_id = $this->getSaccoAdminID();

	$postData = array(
		'account_balance' => $amount,
	);

	$this->db->UpdateData('m_staff', $postData,"`id` = '".$admin_id."'");
	header('Location: ' . URL . 'staff?msg=success'); 
}

function getSaccoAdminID(){
	$office = $_SESSION['office'];
	$results = $this->db->SelectData("SELECT * FROM m_staff WHERE access_level = 'A' AND office_id = '" . $office. "'");

	$id = 0;
	foreach ($results as $key => $value) {

		if ($value['access_level'] == "A") {
			return $value['id'];
		}
	}
}

function insertIntoJournal($data){

	$transaction_id = 'OP'. uniqid();

}


function getAccountingRules(){
	$office = $_SESSION['office'];
		
	$result=  $this->db->selectData("SELECT r.id as rule_id, r.name as rule, r.cash_type, r.debit_account_id,credit_account_id, r.description as comments,o.name as office FROM acc_accounting_rule r JOIN m_branch o ON o.id=r.office_id WHERE office_id = '".$office."'");
  		$count=count($result);
		if($count>0){
     	foreach ($result as $key => $value) {
	   $debit_account=$this->getGlaccountname($result[$key]['debit_account_id']);
	   $credit_account=$this->getGlaccountname($result[$key]['credit_account_id']);
	        $rset[$key]['rule_id'] = $result[$key]['rule_id']; 
	        $rset[$key]['office'] = $result[$key]['office']; 
	        $rset[$key]['cash_type'] = $result[$key]['cash_type']; 
            $rset[$key]['rule'] = $result[$key]['rule']; 
			$rset[$key]['debit'] = $debit_account;
			$rset[$key]['credit'] = $credit_account;
			
	} 
        return $rset;

		}
}

function getJournals(){
	$office = $_SESSION['office'];
$result= $this->db->selectData("SELECT jr.journal_id,jr.transaction_type,o.name as office,jr.created_date as transaction_date,jr.transaction_id,jr.trial_balance_side,gl.name as gl_name,gl.gl_code,gl.classification,jr.amount FROM (acc_gl_journal_entry jr JOIN m_branch o ON jr.office_id=o.id) JOIN acc_ledger_account gl ON gl.id=jr.account_id WHERE jr.office_id='".$office."'  group by jr.journal_id,jr.office_id");		
 //AND jr.manual_entry='Yes'
				$count=count($result);
		if($count>0){
		foreach ($result as $key => $value) {
                $rset[$key]['journal_id'] = $result[$key]['journal_id']; 
                $rset[$key]['office'] = $result[$key]['office']; 
                $rset[$key]['transaction_date'] = $result[$key]['transaction_date']; 
				$rset[$key]['transaction_id'] = $result[$key]['transaction_id'];
				$rset[$key]['account_type'] =$result[$key]['classification'];
				$rset[$key]['gl_code'] = $result[$key]['gl_code'];
				$rset[$key]['gl_name'] = $result[$key]['gl_name'];
				$rset[$key]['amount'] = $result[$key]['amount'];
				$rset[$key]['transaction_type'] = $result[$key]['transaction_type'];
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
function getJournalsTransaction($id){
	
$result=$this->db->selectData("SELECT jr.journal_id,jr.transaction_type,o.name as office,jr.created_date as transaction_date,jr.transaction_id,jr.trial_balance_side,gl.name as gl_name,gl.gl_code,gl.classification,jr.amount,jr.manual_entry FROM (acc_gl_journal_entry jr JOIN m_branch o ON jr.office_id=o.id) JOIN acc_ledger_account gl ON gl.id=jr.account_id  WHERE  jr.transaction_id='".$id."' ");		

//$result=$this->db->selectData("SELECT jr.journal_id,o.name as office,jr.created_date as transaction_date,jr.transaction_id,jr.trial_balance_side,gl.name as gl_name,gl.gl_code,gl.classification,jr.amount,jr.manual_entry FROM (acc_gl_journal_entry jr JOIN m_branch o ON jr.office_id=o.id) JOIN acc_ledger_account gl ON gl.id=jr.account_id JOIN (m_payment_detail pd JOIN payment_mode pt ON pd.payment_type_id=pt.id) WHERE jr.payment_details_id=pd.id AND jr.transaction_id='".$id."' ");		
		foreach ($result as $key => $value) {
                $rset[$key]['journal_id'] = $result[$key]['journal_id']; 
                $rset[$key]['office'] = $result[$key]['office']; 
                $rset[$key]['transaction_date'] = $result[$key]['transaction_date']; 
				$rset[$key]['transaction_id'] = $result[$key]['transaction_id'];
				$rset[$key]['gl_code'] = $result[$key]['gl_code'];
				$rset[$key]['gl_name'] = $result[$key]['gl_name'];
				$rset[$key]['account_type'] =$result[$key]['classification'];
				$rset[$key]['transaction_type'] = $result[$key]['transaction_type'];
				$rset[$key]['amount'] = $result[$key]['amount'];
          }
        return $rset;
}
function createPost($data){
$transaction_id=uniqid();
 $postData = array();
 $postDr = array();
 $postDr = array();

if($data['payment_type']=="Mobile Money"||$data['payment_type']=="Cheque"){
		$postData['payment_type'] =$data['payment_type'];        
		if($data['payment_type']=="Mobile Money"){
           $postData['mobile_number'] = $data['mobile_number'];	   
		   }else{

			$postData['check_number'] =$data['cheque_num'];
			$postData['bank_name'] = $data['bank_name'];
            $postData['account_number'] =$data['account_num'];
            $postData['cheque_amount'] = $data['cheque_amount'];		   
			   
		   }

              
}

	$cc=count($postData);
		$sideA=$this->getAccountSide($data['gldebit']);

		    $postDr['account_id']= $data['gldebit'];
			$postDr['office_id']=$_SESSION['office'];
			$postDr['branch_id']=$_SESSION['branchid'];
            $postDr['transaction_id'] = $transaction_id;
            $postDr['manual_entry'] ='Yes';		
            $postDr['amount'] = $data['amount'];
            $postDr['transaction_type'] ="DR";
            $postDr['description'] = $data['description'];
            $postDr['trial_balance_side'] =$sideA;
			$postDr['createdby_id'] = $_SESSION['user_id'];

		$sideB=$this->getAccountSide($data['glcredit']);

			$postCr['account_id']= $data['glcredit'];
			$postCr['office_id']=$_SESSION['office'];
			$postCr['branch_id']=$_SESSION['branchid'];
            $postCr['transaction_id'] = $transaction_id;
            $postCr['manual_entry'] ='Yes';
            $postCr['amount'] = $data['amount'];
            $postCr['transaction_type'] ="CR";
            $postCr['description'] = $data['description'];
            $postCr['trial_balance_side'] =$sideB;
			$postCr['createdby_id'] =$_SESSION['user_id'];
	// print_r($postDr);die();			
	if($cc==0){
       $this->db->InsertData('acc_gl_journal_entry', $postDr);
       $this->db->InsertData('acc_gl_journal_entry', $postCr);
	if($data['type']=="Cash Payment"){ 
  header('Location: ' . URL . 'accounting/payoutcash?msg=success'); 
	}else{
  header('Location: ' . URL . 'accounting/receivepayments?msg=success'); 
	
	}	}else{
		
	 $payment_details_id=  $this->db->InsertData('m_payment_detail', $postData);	
$postDr['payment_details_id'] = $payment_details_id;
$postCr['payment_details_id'] = $payment_details_id;
	       
	 $this->db->InsertData('acc_gl_journal_entry', $postDr);
	 $this->db->InsertData('acc_gl_journal_entry', $postCr);
	if($data['type']=="Cash Payment"){ 
  header('Location: ' . URL . 'accounting/payoutcash?msg=success'); 
	}else{
  header('Location: ' . URL . 'accounting/receivepayments?msg=success'); 
	
	}
		
	}
}

function insertJournalEntry($data){

	$postDr = array();
	$postCr = array();
	$batch = $this->GetBatchNo();
	$debits = $data['debit'];
	$credits = $data['credit'];
	$amount = $data['amount'];
	$description=$data['decription'];

	$total = 0;
	for($i=0; $i<count($debits); $i++){
		$transaction_id = uniqid();		
		if(!empty($debits[$i])&&!empty($credits[$i])){	

			$total += $amount[$i];
			$sideA = $this->getAccountSide($debits[$i]);
			$postDr['account_id'] = $debits[$i];
			$postDr['office_id'] = $_SESSION['office'];
			$postDr['branch_id'] = $_SESSION['branchid'];
			$postDr['transaction_id'] = $transaction_id;
			$postDr['manual_entry'] ='Yes';		
			$postDr['amount'] = $amount[$i];
			$postDr['transaction_type'] = "DR";
			$postDr['description'] = $description;
			$postDr['trial_balance_side'] = $sideA;
			$postDr['batch_no'] = $batch;
			$postDr['createdby_id'] = $_SESSION['user_id'];

			$sideB = $this->getAccountSide($credits[$i]);
			$postCr['account_id'] = $credits[$i];
			$postCr['office_id'] = $_SESSION['office'];
			$postCr['branch_id'] = $_SESSION['branchid'];
			$postCr['transaction_id'] = $transaction_id;
			$postCr['manual_entry'] = 'Yes';
			$postCr['amount'] = $amount[$i];
			$postCr['transaction_type'] = "CR";
			$postCr['description'] = $description;
			$postCr['trial_balance_side'] = $sideB;
			$postCr['batch_no'] = $batch;
			$postCr['createdby_id'] = $_SESSION['user_id'];

			$this->db->InsertData('acc_gl_journal_entry', $postDr);
			$this->db->InsertData('acc_gl_journal_entry', $postCr);
		}
	}
	header('Location: ' . URL . 'accounting/journals?msg=success');
}

function createJournal($data){

	$postDr = array();
	$postCr = array();
	$batch=$this->GetBatchNo();
	$debits=$data['debit'];
	$credits=$data['credit'];
	$status=$data['status'];
	$amount=$data['amount'];
	$idz=$data['tellerids'];
	$requests=$data['requestids'];
	$descriptions=$data['description'];


	$total = 0;
	for($i=0; $i<count($debits); $i++){
		$transaction_id=uniqid();		
		if(!empty($debits[$i])&&!empty($credits[$i])&&$status[$i] == 'Approved'){	

			$total += $amount[$i];
			$sideA=$this->getAccountSide($debits[$i]);
			$postDr['account_id']=$debits[$i];
			$postDr['office_id']=$_SESSION['office'];
			$postDr['branch_id']=$_SESSION['branchid'];
			$postDr['transaction_id'] = $transaction_id;
			$postDr['manual_entry'] ='Yes';		
			$postDr['amount'] =$amount[$i];
			$postDr['transaction_type'] ="DR";
			$postDr['description'] = $descriptions[$i];
			$postDr['trial_balance_side'] =$sideA;
			$postDr['batch_no'] =$batch;
			$postDr['createdby_id'] = $_SESSION['user_id'];

			$sideB=$this->getAccountSide($credits[$i]);
			$postCr['account_id']=$credits[$i];
			$postCr['office_id']=$_SESSION['office'];
			$postCr['branch_id']=$_SESSION['branchid'];
			$postCr['transaction_id'] = $transaction_id;
			$postCr['manual_entry'] ='Yes';
			$postCr['amount'] =$amount[$i];
			$postCr['transaction_type'] ="CR";
			$postCr['description'] = $descriptions[$i];
			$postCr['trial_balance_side'] =$sideB;
			$postDr['batch_no'] =$batch;
			$postCr['createdby_id'] =$_SESSION['user_id'];

			$balance = $this->getTellerAccountBalance($idz[$i]) + $amount[$i];
			$postData = array(
				'last_request_date' => date('Y-m-d'),//'cash_account' => $amount[$i],
				'account_balance' => $balance
			);
			$this->db->UpdateData('m_staff', $postData,"`id` = '{$idz[$i]}'");
			$this->db->InsertData('acc_gl_journal_entry', $postDr);
			$this->db->InsertData('acc_gl_journal_entry', $postCr);
		}

		if($status[$i] == 'Approved'){
			$postData = array(
				'balance' => $balance,
				'approved_amount' => $amount[$i],
				'approval_date' => date("Y-m-d H:i:s"),
				'approved_by' => $_SESSION['user_id'],
				'status' => $status[$i]
			);
		} else if($status[$i] == 'Closed'){
			$postData = array(
				'close_date' => date("Y-m-d H:i:s"),
				'status' => $status[$i]
			);
		} else {
			$postData = array(
				'status' => $status[$i]
			);
		}

		$this->db->UpdateData('m_teller_transactions', $postData,"`id` = '{$requests[$i]}'");
	}
	header('Location: ' . URL . 'accounting/cashmanagement?msg=success');
}

function createReturnJournal($data){

	$postDr = array();
	$postCr = array();
	$batch=$this->GetBatchNo();
	$debits=$data['credit'];
	$credits=$data['debit'];
	$status=$data['status'];
	$amount=$data['amount'];
	$idz=$data['tellerids'];
	$requests=$data['requestids'];
	$descriptions=$data['description'];
	
	$total = 0;
	for($i=0; $i<count($debits); $i++){
		$transaction_id=uniqid();		
		if(!empty($debits[$i])&&!empty($credits[$i])&&$status[$i] == 'Received'){	

			$total += $amount[$i];
			$sideA=$this->getAccountSide($debits[$i]);
			$postDr['account_id']=$debits[$i];
			$postDr['office_id']=$_SESSION['office'];
			$postDr['branch_id']=$_SESSION['branchid'];
			$postDr['transaction_id'] = $transaction_id;
			$postDr['manual_entry'] ='Yes';		
			$postDr['amount'] =$amount[$i];
			$postDr['transaction_type'] ="DR";
			$postDr['description'] = $descriptions[$i];
			$postDr['trial_balance_side'] =$sideA;
			$postDr['batch_no'] =$batch;
			$postDr['createdby_id'] = $_SESSION['user_id'];

			$sideB=$this->getAccountSide($credits[$i]);
			$postCr['account_id']=$credits[$i];
			$postCr['office_id']=$_SESSION['office'];
			$postCr['branch_id']=$_SESSION['branchid'];
			$postCr['transaction_id'] = $transaction_id;
			$postCr['manual_entry'] ='Yes';
			$postCr['amount'] =$amount[$i];
			$postCr['transaction_type'] ="CR";
			$postCr['description'] = $descriptions[$i];
			$postCr['trial_balance_side'] =$sideB;
			$postCr['batch_no'] =$batch;
			$postCr['createdby_id'] =$_SESSION['user_id'];

			$balance = $this->getTellerAccountBalance($idz[$i]) - $amount[$i];
			$postData = array(
				'last_request_date' => date('Y-m-d'),//$amount[$i],
				'account_balance' => $balance
			);
			$this->db->UpdateData('m_staff', $postData,"`id` = '{$idz[$i]}'");
			$this->db->InsertData('acc_gl_journal_entry', $postDr);
			$this->db->InsertData('acc_gl_journal_entry', $postCr);
		}

		if($status[$i] == 'Received'){
			$postData = array(
				'balance' => $balance,
				'approved_amount' => $amount[$i],
				'approval_date' => date("Y-m-d H:i:s"),
				'approved_by' => $_SESSION['user_id'],
				'status' => $status[$i]
			);
		} else if($status[$i] == 'Closed'){
			$postData = array(
				'close_date' => date("Y-m-d H:i:s"),
				'status' => $status[$i]
			);
		} else {
			$postData = array(
				'status' => $status[$i]
			);
		}

		$this->db->UpdateData('m_teller_transactions', $postData,"`id` = '{$requests[$i]}'");
	}
	header('Location: ' . URL . 'accounting/cashmanagement?msg=success');
}

function getSearchResults($data){

			if (!empty($data['glAccount'])||($data['filters']!= "")||!empty($data['filters'])||(!empty($data['date_from'])&&!empty($data['date_to']))||!empty($data['transaction_id'])){
			$name = $data['glAccount'];
            $office  = $_SESSION['office'];
			$date_from  = date('Y-m-d',strtotime($data['date_from']));
			$date_to  = date('Y-m-d',strtotime($data['date_to']));
			$transaction_id  = $data['transaction_id'];
			$filters  = $data['filters'];
		//$results = $this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id ='".$name."' OR office_id ='".$office."'  OR transaction_id = '".$transaction_id."' OR  entry_date BETWEEN $date_from AND $date_to   LIMIT 0 , 10");
			$results = $this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id ='$name' OR office_id ='$office' OR manual_entry ='$filters' OR transaction_id ='$transaction_id' OR  date(created_date) BETWEEN '".$date_from."' AND '".$date_to."' ");
  
       
// Display search result
       if (!empty($results)) {
		 		//echo "Search found :<br/>";
				return $results;
						
        } else {
           return false;
        }
}else{
	return $this->db->selectData("SELECT * FROM acc_gl_journal_entry ");

}
	
}


function getCashinRules(){
		
	$office=$_SESSION['office'];
	$result=  $this->db->selectData("SELECT r.id as rule_id,r.name as rule,r.debit_account_id,credit_account_id,r.description as comments,o.name as office FROM acc_accounting_rule r JOIN m_branch o ON o.id=r.office_id where r.cash_type='Cash In' and office_id = $office");
  		$count=count($result);
		if($count>0){
     	foreach ($result as $key => $value) {
	   $debit_account=$this->getGlaccountname($result[$key]['debit_account_id']);
	   $credit_account=$this->getGlaccountname($result[$key]['credit_account_id']);
	        $rset[$key]['rule_id'] = $result[$key]['rule_id']; 
	        $rset[$key]['office'] = $result[$key]['office']; 
            $rset[$key]['rule'] = $result[$key]['rule']; 
			$rset[$key]['debit'] = $debit_account;
			$rset[$key]['credit'] = $credit_account;
			
	} 
        return $rset;

		}
}

function getCashOutRules(){
		
	$office=$_SESSION['office'];
		
	$result=  $this->db->selectData("SELECT r.id as rule_id,r.name as rule,r.debit_account_id,credit_account_id,r.description as comments,o.name as office FROM acc_accounting_rule r JOIN m_branch o ON o.id=r.office_id where r.cash_type='Cash Out' and office_id = $office");
  		$count=count($result);
		if($count>0){
     	foreach ($result as $key => $value) {
	   $debit_account=$this->getGlaccountname($result[$key]['debit_account_id']);
	   $credit_account=$this->getGlaccountname($result[$key]['credit_account_id']);
	        $rset[$key]['rule_id'] = $result[$key]['rule_id']; 
	        $rset[$key]['office'] = $result[$key]['office']; 
            $rset[$key]['rule'] = $result[$key]['rule']; 
			$rset[$key]['debit'] = $debit_account;
			$rset[$key]['credit'] = $credit_account;
			
	} 
        return $rset;

		}
}

function ruleDetails($id){
		
	$result=  $this->db->selectData("SELECT r.id as rule_id,r.name as rule,r.debit_account_id,credit_account_id,r.description as comments,r.office_id,o.name as office FROM acc_accounting_rule r JOIN m_branch o ON o.id=r.office_id WHERE r.id='".$id."'");
  if(count($result)>0){
     	foreach ($result as $key => $value) {
	   $debit_account=$this->getGlaccountname($result[$key]['debit_account_id']);
	   $credit_account=$this->getGlaccountname($result[$key]['credit_account_id']);
	        $rset[$key]['rule_id'] = $result[$key]['rule_id']; 
	        $rset[$key]['office_id'] = $result[$key]['office_id']; 
	        $rset[$key]['office'] = $result[$key]['office']; 
            $rset[$key]['rule'] = $result[$key]['rule']; 
			$rset[$key]['comments'] = $result[$key]['comments']; 
			$rset[$key]['debit_account_id'] = $result[$key]['debit_account_id'];
			$rset[$key]['debit'] = $debit_account;
			$rset[$key]['credit_account_id'] =$result[$key]['credit_account_id'];
			$rset[$key]['credit'] = $credit_account;
			
	} 
        return $rset;
  }

}
	
	function getruledetails($id=null){
  if($id==null){
 return ;	  
  }else{
	$result=  $this->db->selectData("SELECT id as rule_id,name as rule,debit_account_id,credit_account_id,description as comments FROM acc_accounting_rule  WHERE id='".$id."'");
	 $opt='<div class="col-md-12">
		<span>Affected GL entries</span><hr/>
		</div>
		<div class="col-md-12">
        <div class="col-lg-4">
	    <fieldset class="form-group">
		<label   class="control-label">
		<span style="float:right;">Debit<span class="star">*</span></span>
		</label>';
        foreach ($result as $key => $value) {
	   $debit_account=$this->getGlaccountname($result[$key]['debit_account_id']);
	    $opt .= '<input type="hidden" value="'.$value['debit_account_id'].'" class="form-control"  name="gldebit"/>
		<input type="text" readonly value="'.$debit_account.'" class="form-control" />';
       
	   }
      $opt .='</fieldset>
			</div>
        	<div class="col-lg-4">
	        <fieldset class="form-group">
			<label   class="control-label">
			<span style="float:right;">Credit</span>
			</label>';
 foreach ($result as $key => $value) {
$credit_account=$this->getGlaccountname($result[$key]['credit_account_id']);
	    $opt .= '<input type="hidden" value="'.$value['credit_account_id'].'" class="form-control"  name="glcredit"/>
		<input type="text" readonly value="'.$credit_account.'" class="form-control" />';
        }
    $opt .='</fieldset>
			</div>
			</div>
		<div class="col-md-12">
        <div class="col-lg-4">
	    <fieldset class="form-group">
		<label   class="control-label">	
		<span style="float:right;">Transaction Amount</span>
		</label>		
			<input type="text" class="form-control" id="num"  name="amount" required="true"/>  
			</fieldset>
			</div><div class="col-lg-4">
	       <fieldset class="form-group">
			<label   class="control-label">
			<span>Transaction Description<span class="star">*</span></span>
			</label>
           <textarea type="text" rows="2" name="description" class="form-control"  required="true"></textarea>
	       </fieldset>
		</div>
		</div>';
				print_r($opt);
		die();			
           }
}

	
function createAccountingrule($data){
$office=$_SESSION['office'];
	        $postData = array(
         
			'name' => $data['accounting_rule'],
			'office_id' =>$office,
			'cash_type' =>$data['cash_type'],
            'debit_account_id' => $data['debit_type'],
            'credit_account_id' => $data['credit_type'],
            'description' => $data['description'],
                  );
       	
		
       $this->db->InsertData('acc_accounting_rule', $postData);
  header('Location: ' . URL . 'accounting/accountingrules?msg=success'); 

}

function UpdateAccountingRule($data){
	
			$id=$data['rid'];
	        $postData = array(
			'name' => $data['accounting_rule'],
			'office_id' => $_SESSION['office'],
            'debit_account_id' => $data['debit_type'],
            'credit_account_id' => $data['credit_type'],
			'description' => $data['description']
                  );
       
		 $this->db->UpdateData('acc_accounting_rule', $postData,"`id` = '{$id}'");
         header('Location: ' . URL . 'accounting/accountingrules?msg=success'); 
}

function deleteglaccount($id){
 

			 
       	$this->db->DeleteData('acc_ledger_account',"`id` = '{$id}'");
  header('Location: ' . URL . 'accounting/chartsofaccount?msg=success');
	
}

function DeleteRule($id){
 
$postData = array(         
			'status' => '0'
                            );
       	$this->db->UpdateData('acc_accounting_rule', $postData,"`id` = '{$id}'");
  header('Location: ' . URL . 'accounting/accountingrules?msg=success');
	
}	
/////closure of accoungs
function getClosedAccounts($id=null){
  if($id==null){
    $result=  $this->db->selectData("SELECT * FROM acc_gl_closure c JOIN m_branch b ON c.office_id=b.id where is_deleted='0'");
  
  }else{
	$result=  $this->db->selectData("SELECT * FROM acc_gl_closure c JOIN m_branch b ON c.office_id=b.id where c.closure_id='".$id."'");
  }
        return $result;
	
}

function postClosure($data){
	
	        $postData = array(
         
			'closing_date' =>date('Y-m-d',strtotime($data['closedon'])),
			'office_id' => $_SESSION['office'],
			'createdby_id' => $_SESSION['user_id'],
            'comments' => $data['comments'],
                  );
       	
		
    $this->db->InsertData('acc_gl_closure', $postData);
  header('Location: ' . URL . 'accounting/closedaccounts?msg=success'); 

}
function deleteClosure($id){
	
	        $postData = array(
         
			'is_deleted' => '1',
                  );
       	
		
 $this->db->UpdateData('acc_gl_closure', $postData,"`closure_id` = '{$id}'");
  header('Location: ' . URL . 'accounting/closedaccounts?msg=success'); 

}
///Balancing for the day
function getGLDayreport(){
	$today='2016-08-23';
$rset=null;
	$office_id=$_SESSION['office'];
	//AND created_date between '".$from."' AND '".$to."'
  $result=$this->db->selectData("SELECT * FROM acc_ledger_account WHERE sacco_id='".$office_id."'  order by  gl_code ASC");

 if(count($result)>0){	
 	$balance=0;
	$debit=0;
	$credit=0;
	
	foreach ($result as $keys => $value) {

 $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$result[$keys]['id']."' AND office_id='".$office_id."' and date(created_date) ='".$today."' ");

	$counts=count($res);	
		if($counts!=0){ print_r($res);die();  }
	foreach ($res as $key => $value) {
                //$officename = $this->officeName($result[$key]['id']);
                $rset[$key]['created_date'] = $res[$key]['created_date']; 
                $rset[$key]['account_id'] = $res[$key]['account_id']; 
                $rset[$key]['office_id'] = $res[$key]['office_id']; 
				$rset[$key]['description'] = $res[$key]['description']; 
				$side = $res[$key]['trial_balance_side']; 
				$type = $res[$key]['type_enum']; 
                $amount = $res[$key]['amount']; 
				if($side=='SIDE_A'){
				if($type=='DR'){
				$balance=$balance+$amount;
			    $debit=$amount ;
				}else{
				$balance=$balance-$amount; 
			    $credit=$amount ;			
				}				 
				}else{
				if($type=='DR'){
				$balance=$balance-$amount; 
			    $debit=$amount ;						
				}else{
				$balance=$balance+$amount; 
			    $credit=$amount ;						
				}		
					
				}
               
          }//inner foreach
        return $rset;
}//outer foreach
 	
                $rset['debit'] =$debit; 
                $rset['credit'] =$credit; 
				print_r($rset);die();

}//outer if
}

	function getGlaccountsA($data){
	$from=date('Y-m-d',strtotime($data['startdon']));
	$to=date('Y-m-d',strtotime($data['endon']));
	$office_id=$data['office'];
	$office=$_SESSION['office'];
	//AND created_date between '".$from."' AND '".$to."'
  $result=$this->db->selectData("SELECT * FROM acc_gl_account where classification_enum='1' || classification_enum='5' AND sacco_id = '".$office."' order by   gl_code ASC");
 if(count($result)>0){	
	foreach ($result as $keys => $value) {
 $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$result[$keys]['id']."' AND office_id='".$office_id."' and entry_date between '".$from."' AND '".$to."' ");
	$counts=count($res);
   if($counts==0){
	  
	  
  }else{
			$balance=0;

	foreach ($res as $key => $value) {
				$type = $res[$key]['type_enum']; 
                $amount = $res[$key]['amount']; 
				if($type==1){
				$balance=$balance+$amount;
				}else{
				$balance=$balance-$amount; 
				}				 
          }
	 $rset[$keys]['name'] =$result[$keys]['name']; 
	 $rset[$keys]['gl_code'] =$result[$keys]['gl_code']; 
     $rset[$keys]['debit'] =$balance; 
	}	
	}

// print_r($rset);
// die();
        return $rset;
	}
}	



function getProducts(){
    return $this->db->SelectData("SELECT p_id, p_name FROM products where p_id >=0 AND p_id <=5");
}

function insertglaccounts($data){

	$pdts = $this->getProducts();

	foreach ($pdts as $key => $value) {

		if ($value['p_id'] == $data['product_id'][$key]) {
			$code = $this->getGlCode($data['account_id'][$key]);
		    $postData = array(
			 	'office_id' => $_SESSION['office'],
				'product_id' => $data['product_id'][$key],
			    'account_id' => $data['account_id'][$key],
			    'gl_code' => $code,
		    );
	    	$this->db->InsertData('acc_ledger_account_mapping', $postData);
	    }
	} 
    header('Location: ' . URL . 'accounting/?msg=mapped'); 
}

function getGlCode($id){
   $code =  $this->db->selectData("SELECT gl_code FROM acc_ledger_account where id= $id");
   return $code[0]['gl_code'];
}

function getGlAccountsCount(){
	$id = $_SESSION['office'];
   	$data = $this->db->selectData("SELECT count(product_id) FROM acc_ledger_account_mapping where office_id= $id");
	
	return $data[0][0];
}

function getSelectedGlaccounts(){
	$id = $_SESSION['office'];
   	$data = $this->db->selectData("SELECT * FROM acc_ledger_account_mapping where office_id= $id");
	return $data;
}

function updateglaccountmappings($data, $id){
	$code = $this->getGlCode($data['account_id']);
    $postData = array(
		'product_id' => $data['product_id'],
	    'account_id' => $data['account_id'],
	    'gl_code' => $code,
	);
	$this->db->UpdateData('acc_ledger_account_mapping', $postData,"`id` = '{$id}'");
    header('Location: ' . URL . 'accounting/editglaccounts?msg=success'); 

}

}