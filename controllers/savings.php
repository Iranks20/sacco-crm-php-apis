<?php

class Savings extends Controller{

	public function __construct(){
		parent::__construct();
		$this->post= new SavingsPostings();
	}

	/********************  Members  *******************************/
	function index(){
		$this->view->Members = $this->model->MembersList();
		$this->view->render('forms/members/memberslist');
	}

	function receipt($id, $type){

		$this->view->receipt = $this->model->getOrgDetails();

		if ($type == 2) {
			$this->view->data = $this->model->GetSavingsPayment($id);
		} else if ($type == 5) {
			$this->view->data = $this->model->GetWalletPayment($id);
		} 
		$this->view->renders('receipt');
	}

	function MembersList(){
		$this->view->Members = $this->model->MembersList();
		$this->view->render('forms/members/MembersList');	
	}

	function newMember(){

		$office=$_SESSION['office'];
		$this->view->branches=$this->model->getOffice($office); 
		$this->view->staff=$this->model->getstaff($office); 
		$this->view->render('forms/members/newmember');

	}
	function newmembertype(){
		
		$data=$_POST;
		if(!empty($data)){	
			$office=$_SESSION['office'];
			$this->view->branches=$this->model->getOffice($office); 
			$this->view->staff=$this->model->getstaff($office); 
			$this->view->type=$data['form'];
			if($data['form']=='Individual'){
				
				$this->view->render('forms/members/newmember_individual');
				
			}else{
				
				$this->view->render('forms/members/newmember_non_individual');
				
			}
		}else{
			header('Location: ' . URL . 'members'); 					
		}	

	}
	function memberinfo(){
		
		$this->view->render('forms/members/member_infom');

	}
	function member_infom($id){
		if(!empty($id)){	
			$this->model->member_infom($id);	
		}else{
			header('Location: ' . URL . 'members'); 					
		}	

	}

	function walletaccountstatement($tel){		
		$this->view->render('forms/savings/statementform');
	}

	function getLegalform($id){	
		if(!empty($id)){
			$this->model->getLegalform($id); 
		}else{
			header('Location: ' . URL . 'members'); 					
		}		
	}

	function savingsaccount(){
		try {
			$headers = getallheaders();
			$office = $headers['office'];
	
			$savingsData = $this->model->savingslist($office);
	
			$response = [
				"status" => 200,
				"message" => "Success",
				"data" => $savingsData,
			];

			header('Content-Type: application/json');
			echo json_encode($response);
	
		} catch (Exception $e) {

			$errorResponse = [
				"status" => 500,
				"message" => $e->getMessage(),
			];
			header('Content-Type: application/json');
			http_response_code($errorResponse['status']);
			echo json_encode($errorResponse);
		}
	}	

	function fixeddeposits(){
		$this->view->fixedlist = $this->model->fixeddepositList();		
		$this->view->render('forms/fixeddeposit/fixed_deposit_account');

	}

	function statusone($id){
		if(!empty($id)){	
			$this->view->member_id=$id;	
			$this->view->members = $this->model->getClientDetails($id);	
			$this->view->age = $this->model->getClientAge($id);	
			$this->view->render('forms/members/memeberstep_one');
		}else{
			header('Location: ' . URL . 'members'); 					
		}

	}
	function docsform($id,$range){
		if(!empty($id)){
			$this->view->doc =$range;	
			$this->view->members = $this->model->getClientDetails($id);	
			$this->view->render('forms/members/uploaddocs');
		}else{
			header('Location: ' . URL . 'members'); 					
		}
	}

	function uploaddoc(){
		if(!empty($_POST)){
			$this->model->uploaddocument();	
		}else{
			header('Location: ' . URL . 'members'); 					
		}
	}
	function approve($id){
		if(!empty($id)){	
			$this->model->ApproveMember($id);	
		}else{
			header('Location: ' . URL . 'members'); 					
		}
	}

	function newsaving($id=null){
		try {
			if ($id != null) {
				$data = $this->model->GetPayment($id);
	
				header('Content-Type: application/json');
				echo json_encode($data);
	
			} else {
				$paymentTypes = $this->model->paymentType();
				header('Content-Type: application/json');
				echo json_encode($paymentTypes);
			}
	
		} catch (Exception $e) {
			$errorResponse = array("status" => 500, "message" => $e->getMessage());
			header('Content-Type: application/json');
			http_response_code($errorResponse['status']);
			echo json_encode($errorResponse);
		}
	}

	function savings(){
		$this->view->render('forms/savings/newsaving');

	}
	function getClientSaveddetails($id){
		if(!empty($id)){	
			$this->model->getClientSaveddetails($id);	
		}else{
			header('Location: ' . URL . 'members'); 					
		}
	}
	function searchaccount(){
		$data=$_POST;
		if(!empty($data)){		
			$this->view->clist=$this->model->searchaccount($data); 
			$this->view->render('forms/members/accountsearchlist');
		}else{
			header('Location: ' . URL . 'members'); 					
		}
	}
	function getAccountName($id){
		if(!empty($id)){		
			$this->model->getAccountName($id); 
		}else{
			header('Location: ' . URL . 'members'); 					
		}
	}
	function getmemberimage($id){
		try {
			if (!empty($id)) {        
				$imageData = $this->model->getmemberimage($id);
				
				if (!empty($imageData)) {
					header('Content-Type: image/jpeg');
					echo $imageData;
				} else {
					$errorResponse = array("status" => 404, "message" => "Image not found for ID: $id");
					header('Content-Type: application/json');
					http_response_code($errorResponse['status']);
					echo json_encode($errorResponse);
				}
			} else {
				$errorResponse = array("status" => 400, "message" => "Invalid input. 'id' parameter is empty.");
				header('Content-Type: application/json');
				http_response_code($errorResponse['status']);
				echo json_encode($errorResponse);
			}
		} catch (Exception $e) {
			$errorResponse = array("status" => 500, "message" => $e->getMessage());
			header('Content-Type: application/json');
			http_response_code($errorResponse['status']);
			echo json_encode($errorResponse);
		}
	}	

	function getmemberFixedPhoto($id){
		if(!empty($id)){		
			$this->model->getmemberFixedPhoto($id); 
		}else{
			header('Location: ' . URL . 'members'); 					
		}
	}

    function getsavingsaccountdata($id){
		try {
			if(!empty($id)){        
				$data = $this->model->getsavingsaccountdata($id);
				
				header('Content-Type: application/json');
				echo json_encode($data);
			} else {
				$errorResponse = array("status" => 400, "message" => "Invalid input. 'id' parameter is empty.");
				header('Content-Type: application/json');
				http_response_code($errorResponse['status']);
				echo json_encode($errorResponse);
			}
		} catch (Exception $e) {
			$errorResponse = array("status" => 500, "message" => $e->getMessage());
			header('Content-Type: application/json');
			http_response_code($errorResponse['status']);
			echo json_encode($errorResponse);
		}
	}	

	function getwithdrawnbalance($id){
		if(!empty($id)){	
			$this->model->getwithdrawnbalance($id); 
		}else{
			header('Location: ' . URL . 'members'); 					
		}
	}
	function accountsearch(){
		$data=$_POST;
		if(!empty($data)){		
			$this->view->clist=$this->model->searchaccount($data); 
			$this->view->render('forms/members/searchlist');
		}else{
			header('Location: ' . URL . 'members'); 					
		}
	}

	function membersearch(){
		$data=$_POST;
		if(!empty($data)){	
			$this->view->clist=$this->model->searchaccount($data); 
			$this->view->render('forms/members/membersearchlist');
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}
	}

	function ClientSavingsdDetailsSearch($id){
		if(!empty($id)){		
			$this->model->ClientSavingsdDetailsSearch($id);
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}
	}

	function ClientLoansSearch($id){
		if(!empty($id)){	
			$this->model->ClientLoansSearch($id);
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}
	}


	function createMember(){
		$data=$_POST;
		if(!empty($data)){       
			$this->model->NewMemeber();
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}
	}
	function updateMember($id){
		if(!empty($id)){        
			$this->model->updateMember($id);
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}
	}

	function details($id){
		if(!empty($id)){ 
			$this->view->member_id=$id;
			$this->view->members = $this->model->getclient($id);	
			$this->view->clientsavings = $this->model->getClientSavingsdDetails($id);	
			$this->view->loans = $this->model->getMemberLoans($id);	
			$this->view->shares = $this->model->getMemberShares($id);	
			$this->view->age = $this->model->getClientAge($id);	

			if($this->view->members[0]['status']=='Active'||$this->view->members[0]['status']=='Closed'){
				$this->view->render('forms/members/memberdetails');

			}else{
				header('Location: '.URL.'members/statusone/'.$id);  	
				
			}
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}	
	}


	function preparememberdetails($acc){
		if(!empty($acc)){ 	
			$this->model->preparememberdetails($acc);
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}	
	}



	/* fixed deposit application */

	function newfixeddepositApllication(){
		$this->view->employee = $this->model->getEmployees();	
		$this->view->render('forms/fixeddeposit/fixeddepositapplication');

	}
	function fixedaccountenquiry(){
		$this->view->render('forms/fixeddeposit/fixed_deposit_account_info');

	}

	function modifyfixeddeposit(){
		$this->view->render('forms/fixeddeposit/editfixeddepositapplication');

	}

	function getallFixedProducttoapply(){	
		$this->model->getallFixedProducttoapply();
	} 
	function getmemberFixedacc($acc){
		if(!empty($acc)){ 		
			$this->model->getmemberFixedacc($acc);
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}		
	}
	function getFixedDepositProducts($id){
		if(!empty($id)){ 	
			$this->model->getFixedDepositProducts($id);
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}	 
	}

	function getFixedDepositapplied($id){
		if(!empty($id)){ 	
			$this->model->getFixedDepositapplied($id);	
		}else{
			header('Location: ' . URL . 'members'); 					
			
		} 
	}


	function getfixedaccountbalance($acc){
		if(!empty($acc)){ 	
			$this->model->getfixedaccountbalance($acc);
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}	
	}
	function getfixedclient_details($acctno){
		if(!empty($acctno)){ 
			$this->model->getfixedclient_details($acctno);
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}	
	}	

	function submitfixeddepositApplication(){
		$data = $_POST;
		if(!empty($data)){ 
			$this->model->submitfixeddepositApplication($data);
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}	
	}
	function updatefixedDepositApplication(){
		$data = $_POST;
		if(!empty($data)){ 
			$this->model->updatefixedDepositApplication($data);
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}	
	}


	function withdrawfromFixed(){
		$data = $_POST;
		if(!empty($data)){ 
			$this->model->withdrawfromFixed($data);
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}
	}

	function deposit($id,$member_id){
		if(!empty($id)&&!empty($member_id)){ 
			$this->view->paymenttype = $this->model->paymentType();
			$this->view->members = $this->model->getclient($member_id);	
			$this->view->member_id=$member_id;
			$this->view->acc_id=$id;

			$this->view->render('forms/savings/deposit');
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}
	}

	function depositaccount(){
		$data = $_POST;
		if(!empty($data)){ 
			$this->model->depositaccount($data);
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}
	}

	function withdrawaccount(){
		$data = $_POST;
		if(!empty($data)){ 
			$this->model->withdrawaccount($data);
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}
	}


	function statementform(){
		
		$this->view->render('forms/members/statementform');
	}
	function searchmember(){
		
		$this->view->render('forms/members/searchtoviewdetails');
	}

	function transactiondetails($id){
		if(!empty($id)){ 
			$this->view->savings = $this->model->transactiondetails($id);	
			$this->view->render('forms/members/transaction_details');
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}
	}


	function withdraw(){
		$this->view->render('forms/savings/withdraw');


	}
	function withdrawfixed(){
		$this->view->render('forms/fixeddeposit/withdraw_fixed');


	}
	function closefixed(){
		$this->view->render('forms/fixeddeposit/closeafixedaccount');


	}

	function reopenfixedaccount(){
		$this->view->render('forms/fixeddeposit/reopenfixedaccount');


	}
	function stopinterestaccrualfixed(){

		$this->view->render('forms/fixeddeposit/stopinterestaccrualfixed');
	}
	function stopfixedaccrualinterest(){
		$data=$_POST;
		if(!empty($data)){ 
			$this->model->stopfixedaccrualinterest($data);
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}
	}
	function reversefixedtransaction($desc=null){
		if($desc!=null){
			$this->view->authorisor=$desc;
		}
		$this->view->render('forms/fixeddeposit/reversefixedstransaction');
	}
	function approvependingfixed(){
		$this->view->render('forms/fixeddeposit/approvefixedtransaction');
	}

	function getpendingfixedtransaction($acc,$transno){
		if(!empty($acc)&&!empty($transno)){ 
			$this->model->getpendingfixedtransaction($acc,$transno);
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}
	}
	function getfixedtransaction($acc,$transno,$tdate=null){
		if(!empty($acc)&&!empty($transno)){ 
			$this->model->getfixedtransaction($acc,$transno,$tdate);
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}
	}

	function reversefixed(){
		$data=$_POST;
		if(!empty($data)){ 
			$this->model->reversefixed($data);
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}
	}

	function approvefixeddepost(){
		$data=$_POST;
		if(!empty($data)){ 
			$this->model->approvefixeddepost($data);
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}
	}
	function deletefixedaccount(){
		$data=$_POST;
		if(!empty($data)){ 
			$this->model->deletefixedaccount($data);
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}
	}
	function openclosedfixedaccount(){
		$data=$_POST;
		if(!empty($data)){ 
			$this->model->openclosedfixedaccount($data);
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}
	}
	function addCharge($id){
		if(!empty($id)){ 
			$this->view->member_id=$id;
			$this->view->charges = $this->model->getCharges(3);
			$this->view->render('forms/members/addcharge');
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}
	}
	function getcharge(){
		$data = $_POST;
		if(!empty($data)){ 
			$this->view->member_id=$data['id'];
			$this->view->render('forms/members/getchargeform');
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}
	}
	function transferMember($id){
		if(!empty($id)){ 
			$this->view->member_id=$id;
			$this->view->branches=$this->model->officeList();
			$this->view->render('forms/members/transfermember');
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}
	}


	/*end savings application */
	function editmember($id){
		if(!empty($id)){ 
			$office=$_SESSION['office'];
			$this->view->branches=$this->model->getOffice($office); 
			$this->view->staff=$this->model->getstaff($office); 
			$this->view->members = $this->model->getclient($id);
			$prdt_code=100;
			$this->view->account=$this->model->getClientSaving($id); 
			$this->view->render('forms/members/editmember');
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}	
	}
	function UpdateClient(){
		$data=$_POST;
		if(!empty($data)){ 
			$this->view->members =$this->model->UpdateClient($data);	
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}	
	}
	function DeleteMember($id){
		if(!empty($id)){ 
			$this->model->DeleteMember($id);	
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}
	}
	function ActivateMember($id){
		if(!empty($id)){ 
			$this->model->ActivateMember($id);	
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}
	}

	function getstaff($officeid=null){
		$this->model->getstaff($officeid);
		
	}	
	function getclient_details($acctno){
		if(!empty($acctno)){ 
			$this->model->getclient_details($acctno);
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}	
	}	

	function newsavingApplication($acc = null) {
		try {
			$employees = $this->model->getEmployees();
	
			$response = [
				"status" => 200,
				"message" => "Success",
				"employees" => $employees,
			];
	
			header('Content-Type: application/json');
			echo json_encode($response);
	
		} catch (Exception $e) {
			$errorResponse = [
				"status" => 500,
				"message" => $e->getMessage(),
			];
			header('Content-Type: application/json');
			http_response_code($errorResponse['status']);
			echo json_encode($errorResponse);
		}
	}
	
	function modifysavings(){
		$this->view->render('forms/savings/editsavingsapplication');

	}

	function getallSavingsProducttoapply(){
		$this->model->getallSavingsProducttoapply();
	}

	function getmembersavings($acc){
		if(!empty($acc)){ 
			$this->model->getMembersavings($acc);
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}	
	}
	function savingsProductCharges($id){
		if(!empty($id)){ 
			$this->model->savingsProductCharges($id);	
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}	
	}
	
	function submitapplication() {
		try {
			$headers = getallheaders();
            $office = $headers['office'];
	
			$jsonInput = file_get_contents('php://input');
			$data = json_decode($jsonInput, true);
	
			if (!empty($data)) {
				$result = $this->model->submitapplication($data, $office);
	
				$response = [
					"status" => 200,
					"message" => "Success",
					"data" => $result,
				];
	
				header('Content-Type: application/json');
				echo json_encode($response);
			} else {
				$errorResponse = [
					"status" => 400,
					"message" => "Bad Request",
				];
	
				header('Content-Type: application/json');
				http_response_code($errorResponse['status']);
				echo json_encode($errorResponse);
			}
		} catch (Exception $e) {
			$errorResponse = [
				"status" => 500,
				"message" => $e->getMessage(),
			];
			header('Content-Type: application/json');
			http_response_code($errorResponse['status']);
			echo json_encode($errorResponse);
		}
	}

	function getSavingsProducttoapply($id){
		if(!empty($id)){ 
			$this->model->getSavingsProducttoapply($id);
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}		
	}
	function getsavingproduct($id){
		if(!empty($id)){ 
			$this->model->getsavingproduct($id);	
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}	
	}
	function getclientaccount($id){
		if(!empty($id)){ 
			$this->model->getSavingsProduct($id);
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}		
	}

	function closesavings(){
		$this->view->render('forms/savings/closesavingsaccount');
	}

	function deletesavingsaccount(){
		$data=$_POST;
		if(!empty($data)){ 
			$this->model->deletesavingsaccount($data);
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}	
	}
	function savingsStatement(){

		$this->view->render('forms/savings/savings_statement_form');

	}
	function SavingsAccountDetails($acc=null,$mid=null){
		$account=null;
		if($acc!=null){
			$account=$acc;	   
		}else{
			$account=$_POST['accno'];   
		}
		if(!empty($account)){
			$this->view->accountholder=$this->model->getMemberaccount($account);
			$this->view->transactions=$this->model->getSavingsAccountTransactions($account);
	
			$this->view->render('forms/savings/savings_accountstatement');
		}else{
			header('Location: ' . URL . 'members/savingsaccount/');    
		}
		
	}

	function Fixedstatement(){

		$this->view->render('forms/fixeddeposit/fixed_statement_form');

	}
	function FixedAccountDetails(){
		$data=$_POST;
		if(!empty($data)){ 
			$this->view->accountholder=$this->model->getMemberfixedaccount($data['accno']);
			$this->view->transactions=$this->model->getfixedAccountTransactions($data['accno']);
			$this->view->render('forms/fixeddeposit/fixed_accountstatement');
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}	
	}
	function savingsenquiry(){
		$this->view->render('forms/savings/savings_account_info');
	}
	function reopensavingsaccount(){

		$this->view->render('forms/savings/openclosed_savings');
	}
	function OpenclosedSavings(){
		$data=$_POST['accno'];
		if(!empty($data)){ 
			$this->model->OpenclosedSavings($data);
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}	
	}


	function approvependingsavings(){
		$this->view->render('forms/savings/approvesavingtransaction');
	}
	function getpendingsaving($acc,$transno,$tdate=null){
		if(!empty($acc)&&!empty($transno)){ 
			$this->model->getpendingsaving($acc,$transno,$tdate);
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}	
	}
	function getsavingtransaction($acc,$transno,$tdate=null){
		if(!empty($acc)&&!empty($transno)){ 
			$this->model->getsavingtransaction($acc,$transno,$tdate);
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}	
	}
	function approvesavings(){
		$data=$_POST;
		if(!empty($data)){ 
			$this->model->approvesavings($data);
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}
	}
	function reversesavingstransaction($desc=null){
		if($desc!=null){
			$this->view->authorisor=$desc;
		}
		$this->view->render('forms/savings/reversesavingstransaction');
	}

	function reversesavings(){
		$data=$_POST;
		if(!empty($data)){ 
			$this->model->reversesavings($data);
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}	
	}

	function stopinterestaccrualsavings(){
		$this->view->render('forms/savings/stopinterestaccrualsavings');
	}
	function stopsavingsaccrualinterest(){
		$data=$_POST;
		if(!empty($data)){ 
			$this->model->stopsavingsaccrualinterest($data);
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}	
	}
	function getmembersimage($acc){
		if(!empty($acc)){ 
			$this->model->getmembersimage($acc);
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}	

	}
//SAVINS STANDING ORDER
	function makeastandingorder(){

		$this->view->render('forms/savings/standingorderform');

	}
//interst postings methods
	function postinterest(){
		$data=$_POST;
		if(!empty($data)){ 
			$this->post->postinterest($data);
		}else{
			header('Location: ' . URL . 'members'); 					
			
		}	
	}
	

	function importbulk() {
		$this->view->render('forms/members/import_bulk');  
	}

	function processbulkImport() {
		$data = array();
		$data['audit_file_temp'] = $_FILES['file_name']['tmp_name'];
		$data['audit_file_type'] = $_FILES['file_name']['type'];

		$this->model->ImportBulk($data);
		header("Location:".URL ."members");
	}





}