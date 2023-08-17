<?php

class Loans extends Controller{

	public function __construct(){
		parent::__construct();
		$this->loans_calculations = new LoanCalculations(); 

		Auth::handleSignin();
		Auth::CheckSession();
		Auth::CheckAuthorization();
		$_SESSION['timeout'] = time(); 
	}


	///loan application form steven

	function loanappform($loanAccNo){

		$this->view->loandetails = $this->model->getloandetails($loanAccNo);
		$memberdetails = $this->model->getMemberRefDetails($this->model->getloandetails($loanAccNo)[0]['referer_id']);
		$bankdetails1 = $this->model->getBanksDetails($this->model->getloandetails($loanAccNo)[0]['bank1']);
		$bankdetails2 = $this->model->getBanksDetails($this->model->getloandetails($loanAccNo)[0]['bank2']);
		$this->view->collateraldetails = $this->model->getCollateralDetails($loanAccNo);
		$this->view->loandetails[0]['refferer_id'] = $memberdetails[0]['firstname'] . " " . $memberdetails[0]['middlename'] . " " . $memberdetails[0]['lastname'];
		$this->view->loandetails[0]['ref_mobile_no'] = $memberdetails[0]['mobile_no'];
		$this->view->loandetails[0]['bank1'] = $bankdetails1[0]['name'];
		$this->view->loandetails[0]['bank2'] = $bankdetails2[0]['name'];
		$loans_officer_details = $this->model->getstaffList($this->model->getloandetails($loanAccNo)[0]['loan_officer_id']);
		$this->view->currency = $this->model->getThisSaccoCurrency();
		$this->view->saccoName = $this->model->getThisSaccoName();
		$this->view->loandetails[0]['loan_officer'] = $loans_officer_details[0]['firstname'] . " " . $loans_officer_details[0]['lastname'];
		$this->view->render('forms/loans/loanappform');
	}

	function pendingloans(){
		$this->view->loan = $this->model->pendingLoans();
		$this->view->render('forms/loans/pending');
	}

	function index(){
		$this->view->loan = $this->model->loansList();
		$this->view->render('forms/loans/loans_account');
	}
	function newloanapplication($id=null, $grp=null){


		if ($id=='all' && $grp!=null) {

			$this->view->grp_id = $grp;
			$this->view->loan_amount = $this->model->getGroupTotalLoanAmount($grp);	

		} else {

			if ($id!=null) {
				$this->view->mem_id = $id;
			}else{
				$this->view->mem_id = null;
			}
		}

		$this->view->currency = $this->model->getThisSaccoCurrency();
		$this->view->employee = $this->model->getEmployees();
		$this->view->banks = $this->model->getBanks();
		$this->view->render('forms/loans/newloanapplication');

	}

	function applyLoan(){

		$rs = $this->model->applyLoan($_POST);
		echo json_encode($rs);

	}

	function getApplicationFees($id){
		$this->model->getApplicationFees($id);

	}
 	
 	function getLoanApplicationFees(){
		$this->model->getLoanApplicationFees(); 		
 	}
	function getloanProductapp($acc){
		if(!empty($acc)){	
			$this->model->getloanProductapp($acc);
		}else{
			header('Location: ' . URL . 'loans'); 					
		}		
	}

	function getloanProductApplied($acc){
		if(!empty($acc)){	
			$this->model->loanProductApplied($acc);
		}else{
			header('Location: ' . URL . 'loans'); 					
		}		
	}
	function loanchargeApplication($acc){

		$this->model->loanchargeApplication($acc);

	}
	function loanProductCollateral($acc){

		$this->model->loanProductCollateral($acc);


	}
	function loanProductCollateralview($id){
		if(!empty($id)){		
			$this->model->loanProductCollateralview($id);
		}else{
			header('Location: ' . URL . 'loans'); 					
		}

	}
	function m_loan_collat($id){
		$this->model->m_loan_collat($id);
	}
	function get_client_loandetails($id){
		if(!empty($id)){	       
			$this->model->get_client_loandetails($id);
		}else{
			header('Location: ' . URL . 'loans'); 					
		}
		
	}
	function editeloansProductappleid($account_no,$product_id){
		if(!empty($account_no)){		
			$many = $this->model->editeloansProductappleid($account_no,$product_id);
		}else{
			header('Location: ' . URL . 'loans'); 					
		}

	}
	function updateNewclientLoanproduct(){
	//die();
		$data=$_POST;
		if(!empty($data)){		
			$this->model->updatenewclientloanproduct($data);	
		}else{
			header('Location: ' . URL . 'loans'); 					
		}
		

	}
	function updateloancolleteral(){

		$this->model->updateloancolleteral();	

	}
	function customersupportshedule($id,$p,$np,$d1){
		if(!empty($id)&&!empty($p)&&!empty($np)&&!empty($d1)){		
			$this->model->customersupportshedule($id,$p,$np,$d1);
		}else{
			header('Location: ' . URL . 'loans'); 					
		}

	}
	function modifyLoanAplication($id){
		if(!empty($id)){		
			$this->view->account_no=$id;
			$this->view->employee = $this->model->getEmployees();
			$this->view->loan = $this->model->loandetails($id);
			$this->view->member_id =$this->view->loan[0]["member_id"];

			$this->view->members = $this->model->getclient($this->view->member_id);
			$this->view->m_loan_collateral = $this->model->m_loan_collateral($id);
			$this->view->collateral = $this->model->collateral($this->view->loan[0]["product_id"]);
			$this->view->getGuarantor = $this->model->getGuarantor($id);
			$this->view->product = $this->model->getloanProducts($this->view->loan[0]["product_id"]);
			$this->view->getloanProductcharge = $this->model->getloanProductcharge($this->view->product[0]["id"]);
			$this->view->productapplied = $this->model->loansProductappleid($this->view->loan[0]["member_id"]);

			$this->view->render('forms/loans/editnewclientloanproduct');
		}else{
			header('Location: ' . URL . 'loans'); 					
		}
	}

	function ApproveLoan($id){
		if(!empty($id)){	
			$this->view->loan = $this->model->loandetails($id);
			$this->view->members = $this->model->getclient($this->view->loan[0]["member_id"]);

			$this->view->render('forms/loans/approveloan');
		}else{
			header('Location: ' . URL . 'loans'); 				
		}
	}


	function approvedloan(){
		$this->model->approvedloan();

	}
	function disburseloanform($id){
		$this->view->loan = $this->model->loandetails($id);
		$this->view->members = $this->model->getclient($this->view->loan[0]["member_id"]);
		$this->view->disburs = $this->model->disburs($id);
		$this->view->m_loan_charge = $this->model->m_loan_charge($id);
		$this->view->insurance_stampduty = $this->model->insurance_stampduty($id);

		$prodType = 2;
		$transactionType = 'Loan Disbursement';
		$tran_id = $this->model->getTransactionID($transactionType);
		$this->view->otherCharges =  $this->model->getLoanProductTransactionCharges($this->view->loan[0]["product_id"],$tran_id);
 
		$this->view->walletaccountdetails = $this->model->getWallet($this->view->loan[0]["member_id"]);
		$this->view->savingsaccountdetails = $this->model->getSavingsAccounts($this->view->loan[0]["member_id"]);
		$this->view->render('forms/loans/disbursetosavings');

	}

	function loandisbursal(){
		$rs=$this->model->loandisbursal($_POST);
		echo json_encode($rs);
	}
	function makePayment($id){

		$this->view->charges = $this->model->getTransactionCharges($this->model->getTransactionID('Loan Repayment'));

		$this->view->loan = $this->model->loan_tobe_Paid($id);
		$this->view->paymentdetails = $this->model->getPaymentNo($id);
		$this->view->render('forms/loans/repayment');

	}

	function fullPayment($id){
		if(!empty($id)){
			$this->view->loan = $this->model->loan_tobe_Paid($id);
			$this->view->paymentdetails = $this->model->scheduledetailsdue($id);

			$this->view->render('forms/loans/fullrepayment');
		}else{
			header('Location: ' . URL . 'loans/makepaymententernunber/fullPayment');  

		}
	}
	function loanrepayment(){
		$rs = $this->model->loanrepayment($_POST);
		echo json_encode($rs);

	}
	function fullloanrepayment(){
		$this->model->full_loanrepayment();

	}
	function amountRedistribution($account_no,$transaction_amount){
		$this->model->amountRedistribution($account_no,$transaction_amount);
	}
	function CompleteLoanRepayment($account_no,$trans){
		print_r("dsds" ); die();
		  $this->model->CompleteLoanRepayment($account_no,$trans);
	}
	function reverseloanrepayment(){
		$this->model->reverseloanrepayment();

	}
	function upDateLoanSavingsAccount(){

		$this->model->upDateLoanSavingsAccount();	


	}
	function specifiedRepayment($id){
		if(!empty($id)){	
			$this->view->loan = $this->model->loandetails($id);

			$this->view->currency = $this->model->currency();
			$this->view->staff = $this->model->getstaffList($this->view->loan[0]['loan_officer_id']);
			$this->view->members = $this->model->getclient($this->view->loan[0]["member_id"]);
			if($this->view->loan[0]["loan_status"]=='Disbursed'){
				$this->view->schedule = $this->model->scheduledetails($id);	
			} else if ($this->view->loan[0]['loan_status'] == 'Pending') {
				header('Location: ' . URL . 'loans?response=Pending&&acc=' . $id); 
			} else if ($this->view->loan[0]['loan_status'] == 'Approved') {
				header('Location: ' . URL . 'loans/disburseloanenternunber?response=Approved&&acc=' . $id); 
			} else {
				$this->view->schedule = $this->loans_calculations->repaymentCalculations($id);	
			}
			$this->view->render('forms/loans/specified_repayment');
		}else{
			header('Location: ' . URL . 'loans'); 			
		}
	}

	function loandetailsreports($id){
		if(!empty($id)){	
			$this->view->loan = $this->model->loandetails($id);
			$this->view->currency = $this->model->currency();
			$this->view->staff = $this->model->getstaffList($this->view->loan[0]['loan_officer_id']);
			$this->view->members = $this->model->getclient($this->view->loan[0]["member_id"]);
			$this->view->membername = $this->model->getMemberName($this->view->loan[0]["member_id"]);

			$this->view->loanproduct = $this->model->getloanproducts($this->view->loan[0]["product_id"]);
			$this->view->curname = $this->model->currency();
			$this->view->schedule = $this->model->scheduledetailsdue($id);	
			$this->view->loantransaction = $this->model->loantransaction($id);	
			$this->view->m_loan_collateral = $this->model->m_loan_collateral_details($id);
			$this->view->m_loan_charge = $this->model->m_loan_charge($id);
			$this->view->insurance_stampduty = $this->model->insurance_stampduty($id);

			$this->view->render('forms/loans/loan_details_reports');
		}else{
			header('Location: ' . URL . 'loans'); 		
		}
	}



	function loancustomersupport(){
		$this->view->product = $this->model->getloanproducts();
		$this->view->render('forms/loans/loancustomersupport');
	}
	function EnterAccountNumber($status=null){
		$this->view->status = $status;
		$this->view->render('forms/loans/enteraccountnumber');

	}
	function backofficeapproveloan(){
		$this->view->render('forms/loans/accountnumberapproveloan');

	}
	function disburseLoanEnterNunber(){
		$this->view->loan = $this->model->loansToDisibus();
		$this->view->render('forms/loans/disburseloanenternunber');

	}
	function EnterLoanNunber($status){
		if(!empty($status)){		
			$this->view->status = $status;
			$this->view->render('forms/loans/enterloannunber');
		}else{
			header('Location: ' . URL . 'loans'); 	
		}
	}
	function makePaymentEnterNunber($status){
		
		$this->view->teller_balance = $this->model->getTellerAccountBalance($_SESSION['user_id']);
		if(!empty($status)){	
			$this->view->status = $status;
			$this->view->render('forms/loans/makepaymententernunber');
		}else{
			header('Location: ' . URL . 'loans'); 	
		}
	}

	
	function redirect(){
		$data=$_POST;

		if(!empty($data)){	
			$status = strtolower($data['status']);
			$account_no = $data['account_number'];

			if($status=='approveloan'){
				$this->approveloan($account_no);
			}else if($status=='modifyloanaplication'){
				$this->modifyloanaplication($account_no);
			}else if($status=='nextdisbursement'){
				$this->nextdisbursement($account_no);
			}else if($status=='loandetailsreports'){
				$this->loandetailsreports($account_no);
			}else if($status=='loanaccountequiry'){
				$this->loanAccountEquiry($account_no);
			}else if($status=='disbursetosavings'){
				$this->disbursetosavings($account_no);
			}else if($status=='makepayment'){
				$this->makepayment($account_no);
			}else if($status=='loancustomersupport'){
				$this->loancustomersupport($account_no);
			}else if($status=='specifiedrepayment'){
				$this->specifiedrepayment($account_no);
			}else if($status=='fullpayment'){
				$this->fullPayment($account_no);
			}else if($status=='loanstatementoption'){
				$this->loanStatementOption($account_no);
			}
		}else{
			header('Location: ' . URL . 'loans'); 	
		}

	}
	function loanStatementOption($id){
		if(!empty($id)){	
			$this->view->loans = $this->model->loandetails($id);

			$this->view->render('forms/loans/loanstatementoption');
		}else{
			header('Location: ' . URL . 'loans');  

		}
	}

	function getLoanStatement(){
		$data = $_POST;
		if(!empty($data)){
			$account_no =$data['account_no'];
			$this->view->transaction = $this->model->getLoanStatement($data);
			$this->view->loans = $this->model->loandetails($account_no);
			$this->view->members = $this->model->getclient($this->view->loans[0]["member_id"]);
			$this->view->membername = $this->model->getMemberName($this->view->loans[0]["member_id"]);
			$this->view->loanproduct = $this->model->getloanproducts($this->view->loans[0]["product_id"]);
			$this->view->render('forms/loans/view_loan_statement');
		}else{
			header('Location: ' . URL . 'loans/enteraccountnumber/loanstatementoption');  

		}
	}
	function scheduledetailsdue($id,$resultreturn){
	//$this->model->scheduledetailsdue($id,$resultreturn);

	}
	function loanAccountEquiry($id=null, $acc=null){

		if(!empty($acc)){
			$this->view->savingsAcc= $acc;
		} else {
			$this->view->savingsAcc= NULL;
		}

		$this->view->loan = $this->model->loandetails($id);
		 

		$this->view->currency = $this->model->currency();
		$this->view->schedule = $this->model->scheduledetailsdue($id);

		$this->view->schedule_details = $this->model->scheduledetails($id);	
		$this->view->paid = $this->model->scheduledetailspaid($id);	

		$this->view->loantransaction = $this->model->loantransaction($id);	
		$this->view->m_loan_collateral = $this->model->m_loan_collateral_details($id);
		$this->view->m_loan_charge = $this->model->m_loan_charge($id);
		$this->view->insurance_stampduty = $this->model->insurance_stampduty($id);
		$this->view->staff = $this->model->getstaffList($this->view->loan[0]['loan_officer_id']);

		if ($this->view->loan[0]["member_id"] == 0 && $this->view->loan[0]["group_id"] != 0) {
			$this->view->group = $this->view->loan[0]["group_id"];
			$this->view->members = $this->model->getgroupdetails($this->view->loan[0]["group_id"]);	
		} else {
			$this->view->members = $this->model->getclient($this->view->loan[0]["member_id"]);	
		}
		
		$this->view->loanproduct = $this->model->getloanproducts($this->view->loan[0]["product_id"]);
		$this->view->curname = $this->model->currency();
 
 $memberdetails = $this->model->getMemberRefDetails($this->model->getloandetails($id)[0]['referer_id']);
$loanData = array();
		if (!empty($memberdetails)) {
			$loanData= $this->model->getloandetails($id);
		} else {
			$loanData = $this->model->getloandetails($id, $this->view->loan[0]["group_id"]);
		}
	
		
		$bankdetails1 = $this->model->getBanksDetails($this->model->getloandetails($id)[0]['bank1']);
		$bankdetails2 = $this->model->getBanksDetails($this->model->getloandetails($id)[0]['bank2']);
		$this->view->collateraldetails = $this->model->getCollateralDetails($id);
		if (!empty($memberdetails)) {
			$this->view->loandetails[0]['refferer_id'] = $memberdetails[0]['firstname'] . " " . $memberdetails[0]['middlename'] . " " . $memberdetails[0]['lastname'];
			$this->view->loandetails[0]['ref_mobile_no'] = isset($memberdetails[0]['mobile_no']) ? $memberdetails[0]['mobile_no'] : "";
		}
		$loanData[0]['bank1'] = isset($bankdetails1[0]['name']) ? $bankdetails1[0]['name'] : "";
		$loanData['bank2'] = isset($bankdetails2[0]['name']) ? $bankdetails2[0]['name'] : "";
		$loans_officer_details = $this->model->getstaffList($loanData[0]['loan_officer_id']);
		$this->view->currency = $this->model->getThisSaccoCurrency();
		if(count($loans_officer_details)>0){
		$loanData[0]['loan_officer'] = $loans_officer_details[0]['firstname'] . " " . $loans_officer_details[0]['lastname'];
		}else{
		    	$loanData[0]['loan_officer']  = '';
		}
 $this->view->loandetails = $loanData;

		$this->view->render('forms/loans/loan_account_equiry');

	}

	function nextDisbursement($id){
		$this->view->account_no=$id;
		$this->view->employee = $this->model->getEmployees();
		$this->view->loan = $this->model->loandetails($id);
		$this->view->member_id =$this->view->loan[0]["member_id"];
		$this->view->members = $this->model->getclient($this->view->member_id);
		$this->view->m_loan_collateral = $this->model->m_loan_collateral($id);
		$this->view->collateral = $this->model->collateral($this->view->loan[0]["product_id"]);
		$this->view->getGuarantor = $this->model->getGuarantor($id);
		$this->view->product = $this->model->getloanProducts($this->view->loan[0]["product_id"]);
		$this->view->getloanProductcharge = $this->model->getloanProductcharge($this->view->product[0]["id"]);
		$this->view->productapplied = $this->model->loansProductappleid($this->view->loan[0]["member_id"]);
		$this->view->render('forms/loans/nextdisbursement');

	}
	function updateDisbursement(){
		$this->model->updateDisbursement();	
	}
	function reverseloanstransaction($desc=null){
		if($desc!=null){
			$this->view->authorisor=$desc;
		}
		$this->view->render('forms/loans/reverseloanstransaction');
	}

	function getloanstransaction($acc,$transno,$tdate=null){
		$this->model->getloanstransaction($acc,$transno,$tdate);
	}

	function getmemberimage($actno){
		$result=  $this->db->selectData("SELECT * FROM m_loan WHERE account_no='".$actno."'");

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

	

}