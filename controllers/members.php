<?php

class Members extends Controller{

	public function __construct(){
		parent::__construct();
		$this->post= new SavingsPostings();
	}
	
	function checkNIN(){
	    $this->model->checkNationalIdWithNira();
	}

    function getcard($tel){
        $this->model->ImageLoad($tel);
    }

    function getcardback($tel){
        $this->model->ImageLoadBack($tel);
    }


    function getpdfcard($tel){
       $this->model->PdfLoad($tel);
    }

	/********************  Members  *******************************/
	function index(){
	    header('location:'.URL.'clients');
	    die();
		$this->view->Members = $this->model->MembersList();
		$this->view->render('forms/members/memberslist');
	}

	function MembersList(){
	    header('location:'.URL.'clients');
	    die();
		$this->view->Members = $this->model->MembersList();
		$this->view->render('forms/members/MembersList');	
	}

	function checktelephone($tel){
		$this->model->checktelephone($tel);
	}

	function checkemail($mail){
		$this->model->checkemail($mail);
	}

	function checkusername($name){
		$this->model->checkusername($name);
	}

	function newMember(){

		$office=$_SESSION['office'];
		$this->view->branches=$this->model->getOffice($office); 
		$this->view->staff=$this->model->getstaff($office); 
		$this->view->render('forms/members/newmember');

	}

	function sendMessage($id){
		$this->view->memberid = $id;
		$this->view->templates = $this->model->getSaccoMessageTemplates();
		$this->view->render('forms/clients/sendmessage');
	}	

	function uploadNID(){

		if (!empty($_FILES)) {
			$details = $this->model->uploadAndProcess(); 

			$office=$_SESSION['office'];
			$this->view->branches = $this->model->getOffice($office); 
			$this->view->staff = $this->model->getstaff($office); 
			$this->view->reg_charges = $this->model->getDefaultProducts(6); 
			
			$this->view->sharepdts = $this->model->getOfficeShareProducts(); 
			$this->view->savingspdts = $this->model->getOfficeSavingsProducts();

			$this->view->shares = $this->model->getDefaultShareProduct(); 
			$this->view->savings = $this->model->getDefaultSavingsProduct(); 		

			$responseCode = trim(strtolower(str_replace('"', "", rtrim(explode(":", $details[0])[1]))));

			$this->view->responseCode = NULL;
			$this->view->surname = NULL;
			$this->view->given_name = NULL;
			$this->view->national_id_number = NULL;
			$this->view->date_of_birth = NULL;

			if ($responseCode == 'successful') {
				$this->view->surname = ucfirst(trim(strtolower(str_replace('"', "", rtrim(explode(":", $details[2])[1])))));
				$this->view->given_name = ucfirst(trim(strtolower(str_replace('"', "", rtrim(explode(":", $details[3])[1])))));
				$this->view->national_id_number = trim(str_replace('"', "", rtrim(explode(":", $details[4])[1])));
				$date_of_birth = trim(str_replace(' }', "", str_replace('"', "", rtrim(explode(":", $details[5])[1]))));

				if ($date_of_birth != "") {
					$this->view->date_of_birth = date_format(date_create($date_of_birth), "m/d/Y");
				}
				$this->view->NINerror = "non";
			} else {
				$this->view->NINerror = "error";
			}
			
			$this->view->type = 'Personal';
			$this->view->render('forms/members/newmember_individual');
		} else {
			header('Location: '.URL.'members/newmember/');
		}
	}

	function newmembertype(){

		$data=$_POST;
		if(!empty($data)){	
			$office=$_SESSION['office'];
			$this->view->branches=$this->model->getOffice($office); 
			$this->view->staff=$this->model->getstaff($office); 
			$this->view->reg_charges=$this->model->getDefaultProducts(6); 
 			$this->view->sharepdts=$this->model->getOfficeShareProducts(); 
			$this->view->savingspdts=$this->model->getOfficeSavingsProducts();

			$this->view->shares = $this->model->getDefaultShareProduct(); 
			$this->view->savings = $this->model->getDefaultSavingsProduct(); 
			
			
			$this->view->type=$data['form'];
			if($data['form']=='Personal'){
				$this->view->render('forms/members/newmember_individual');
			} else if($data['form']=='Group'){
				header('Location: '.URL.'groups/newgroup'); 	
			} else {				
				$this->view->members=$this->model->getMembers($office); 
				$this->view->render('forms/members/newmember_non_individual');
			}
		}else{
			header('Location: '.URL.'members/newmember'); 	
		}	

	}
	function memberinfo(){

		$this->view->render('forms/members/member_infom');

	}
	function member_infom($id){
		if(!empty($id)){	
			$this->model->member_infom($id);	
		}else{
			
		}	

	}

	function getLegalform($id){	
		if(!empty($id)){
			$this->model->getLegalform($id); 
		}else{
			
		}		
	}

	function savingsaccount(){	
		$this->view->savings = $this->model->savingslist();	
		$this->view->render('forms/savings/savings_account');

	}
	function fixeddeposits(){
		try {
			$headers = getallheaders();
			$office = $headers['office'];
	
			$fixedDepositData = $this->model->fixeddepositList($office);
	
			header('Content-Type: application/json');
			echo json_encode(array("status" => 200, "message" => "Fixed deposit data retrieved successfully", "result" => $fixedDepositData));
	
		} catch (Exception $e) {
			$errorResponse = array("status" => 500, "message" => $e->getMessage());
			header('Content-Type: application/json');
			http_response_code($errorResponse['status']);
			echo json_encode($errorResponse);
		}
	}	

	function statusone($id){
		if(!empty($id)){

			$member_details = $this->model->getClientDetails($id);

			$this->view->member_id = $id;	
			$this->view->members = $member_details;

			$client_image = $this->model->getClientImage($id);
			$client_passport = $this->model->getClientPassport($id);
			$client_signature = $this->model->getClientSignature($id);

			if (!empty($client_image)) {
				$this->view->members[0]['new_image'] = $this->base64ToImage($client_image[0]['image']);
			} else {
				$this->view->members[0]['new_image'] = "";				
			}

			if (!empty($client_passport)) {
				$this->view->members[0]['new_id_passport'] = $this->base64ToImage($client_passport[0]['image']);
			} else {
				$this->view->members[0]['new_id_passport'] = "";				
			}

			if (!empty($client_signature)) {
				$this->view->members[0]['new_signature'] = $this->base64ToImage($client_signature[0]['image']);
			} else {
				$this->view->members[0]['new_signature'] = "";				
			}

			$path = "public/images/avatar/". $_SESSION['username'] . ".txt" ;

			$txt = ":" . $this->view->members[0]['new_image'] . ":" . $this->view->members[0]['new_id_passport'] . ":". $this->view->members[0]['new_signature'] . ":";
			file_put_contents($path, $txt.PHP_EOL , FILE_APPEND | LOCK_EX);

			$this->view->age = $this->model->getClientAge($id);	
			$this->view->render('forms/members/memeberstep_one');
			
		}else{
			
		}

	}

	function docsform($id,$range,$edit=null,$type=null){
		
		if($edit!=null){
			$this->view->edit =$id;	
		}else{
			$this->view->edit =null;
		}

		if($type!=null){
			$this->view->type =$type;	
		}else{
			$this->view->type =null;
		}

		if(!empty($id)){
			$this->view->doc =$range;	
			$this->view->members = $this->model->getClientDetails($id);	

			$client_image = $this->model->getClientImage($id);
			$client_passport = $this->model->getClientPassport($id);
			$client_signature = $this->model->getClientSignature($id);

			if (!empty($client_image)) {
				$this->view->members[0]['new_image'] = $this->base64ToImage($client_image[0]['image']);
			} else {
				$this->view->members[0]['new_image'] = "";				
			}

			if (!empty($client_passport)) {
				$this->view->members[0]['new_id_passport'] = $this->base64ToImage($client_passport[0]['image']);
			} else {
				$this->view->members[0]['new_id_passport'] = "";				
			}

			if (!empty($client_signature)) {
				$this->view->members[0]['new_signature'] = $this->base64ToImage($client_signature[0]['image']);
			} else {
				$this->view->members[0]['new_signature'] = "";				
			}

			$path = "public/images/avatar/". $_SESSION['username'] . ".txt" ;

			$txt = ":" . $this->view->members[0]['new_image'] . ":" . $this->view->members[0]['new_id_passport'] . ":". $this->view->members[0]['new_signature'] . ":";
			file_put_contents($path, $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
			
			$this->view->render('forms/members/uploaddocs');
		}else{
			
		}

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

	function uploaddoc(){
		if(!empty($_POST)){
			$this->model->uploaddocument();	
		}else{
			
		}
	}
	function approve($id){
		if(!empty($id)){	
			$this->model->ApproveMember($id);	
		}else{
			
		}
	}
	
	function newwalletdeposit($id=null, $memId=null, $transID=null){ 
		if($id!=null){
			$this->view->savingsAcc = $id;		
		}

		if ($transID!=null) {
			$this->view->data = $transID;
		}

		if($memId!=null){
			$this->view->memberid = $memId;		
		}
		
		$this->view->teller_balance = $this->model->getTellerAccountBalance($_SESSION['user_id']);
		$this->view->paymenttype = $this->model->paymentType();
		$this->view->render('forms/savings/newwalletdeposit');

	}	

	function walletwithdraw($id=null, $memId=null, $transID=null){ 
		if($id!=null){
			$this->view->savingsAcc = $id;		
		}

		if ($transID!=null) {
			$this->view->data = $transID;
		}

		if($memId!=null){
			$this->view->memberid = $memId;		
		}
		
		$this->view->teller_balance = $this->model->getTellerAccountBalance($_SESSION['user_id']);
		$this->view->paymenttype = $this->model->paymentType();
		$this->view->render('forms/savings/walletwithdraw');

	}
	
	function newsaving($id=null, $memId=null, $data=null){

		$prodType=3;
		$transactionName = 'Deposit on Savings';

		$this->view->teller_balance = $this->model->getTellerAccountBalance($_SESSION['user_id']);

		$tran_id = $this->model->getTransactionID($transactionName);
		$this->view->charges = $this->model->getTransactionCharges($tran_id);
		if($id!=null){
			$this->view->savingsAcc = $id;		
		}

		if ($data!=null) {
			$this->view->data= $data;
		}

		if($memId!=null){
			$this->view->memberid = $memId;		
		}
		$this->view->paymenttype = $this->model->paymentType();
		$this->view->render('forms/savings/newsaving');

	}

	function savings(){
		$this->view->render('forms/savings/newsaving');

	}
	function getClientSaveddetails($id){
		if(!empty($id)){	
			$this->model->getClientSaveddetails($id);	
		}else{
			
		}
	}
	function searchaccount(){
		$data=$_POST;
		if(!empty($data)){		
			$this->view->clist=$this->model->searchaccount($data); 
			$this->view->render('forms/members/accountsearchlist');
		}else{
			
		}
	}
	function getAccountName($id){
		if(!empty($id)){		
			$this->model->getAccountName($id); 
		}else{
			
		}
	}

	function getClosedAccountName($id){
		if(!empty($id)){		
			$this->model->getClosedAccountName($id); 
		}else{
			
		}
	}
	
	function getAccountDetails($id){
		if(!empty($id)){		
			$this->model->getAccountDetails($id); 
		}else{
			
		}
	}
	
	function getSharesAccountName($id){
	   if(!empty($id)){		
			$this->model->getShareAccountName($id); 
		}else{
			
		} 
	}

	function getSavingsAccountName($id){
		if(!empty($id)){		
			$this->model->getAccountName($id); 
            //$this->model->getWalletAccountName($id); 
		}else{
			
		}
	}
	
	function getMemberNameABC($id){
	   if(!empty($id)){		
			$this->model->getMemberNameABC($id); 
		}else{
			
		} 
	}


	function getWalletAccountName($id){
		if(!empty($id)){		
			$this->model->getWalletAccountName($id); 
		}else{
			
		}
	}


	function getmemberimage($id){
		if(!empty($id)){		
			$this->model->getmemberimage($id); 
		}else{
			
		}
	}
	
	function getmemberreceiverimage($id){
		if(!empty($id)){		
			$this->model->getmemberreceiverimage($id); 
		}else{
			
		}
	}

	function getmemberFixedPhoto($id){
		if(!empty($id)){		
			$this->model->getmemberFixedPhoto($id); 
		}else{
			
		}
	}

	function getsavingsaccountdata($id){
		if(!empty($id)){		
			$this->model->getsavingsaccountdata($id); 
		} 
	}
	function getwithdrawnbalance($id){
		if(!empty($id)){	
			$this->model->getwithdrawnbalance($id); 
		}else{
			
		}
	}
	function accountsearch(){
		$data=$_POST;
		if(!empty($data)){		
			$this->view->clist=$this->model->searchaccount($data); 
			$this->view->render('forms/members/searchlist');
		}else{
			
		}
	}

	function membersearch(){
		$data=$_POST;
		if(!empty($data)){	
			$this->view->clist=$this->model->searchaccount($data); 
			$this->view->render('forms/members/membersearchlist');
		}else{
			
			
		}
	}

	function ClientSavingsdDetailsSearch($id){
		if(!empty($id)){		
			$this->model->ClientSavingsdDetailsSearch($id);
		}else{
			
			
		}
	}

	function ClientLoansSearch($id){
		if(!empty($id)){	
			$this->model->ClientLoansSearch($id);
		}else{
			
			
		}
	}


	function createMember(){
		$data=$_POST;
	   
			$rs = $this->model->NewMemeber($data);
			echo json_encode($rs);
		 
	}
	function updateMember($id){
		if(!empty($id)){        
			$this->model->updateMember($id);
		}else{
			
			
		}
	}

	function details($id){
	    
	     header('location: '.URL.'clients/details/'.$id);
	    die();
	    
		if(!empty($id)){	

			$member_details = $this->model->getClientDetails($id);	

			$this->view->members[0]['status'] = $member_details[0]['status'];

			if($this->view->members[0]['status']=='Active'||$this->view->members[0]['status']=='Closed'){
				$this->view->member_id=$id;
				$this->view->members = $this->model->getclient($id);	
				$this->view->clientsavings = $this->model->getClientSavingsdDetails($id);	
				$this->view->loans = $this->model->getMemberLoans($id);	
				$this->view->shares = $this->model->getMemberShares($id);	
				$this->view->age = $this->model->getClientAge($id);	

				$this->view->wallet= $this->model->getClientWalletdDetails($id);
				$this->view->render('forms/members/memberdetails');

			}else{
				header('Location: '.URL.'members/statusone/'.$id);
			}
		}else{
			header('Location: '.URL.'members/');  				
			
		}	
	}


	function preparememberdetails($acc){
		if(!empty($acc)){ 	
			$this->model->preparememberdetails($acc);
		}else{
			
			
		}	
	}

	function preparegroupdetails($acc){
		if(!empty($acc)){ 	
			$this->model->preparegroupdetails($acc);
		}else{
			
			
		}	
	}



	/* fixed deposit application */

	function newfixeddepositApllication($id=null){
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
			
			
		}		
	}
	function getFixedDepositProducts($id){
		if(!empty($id)){ 	
			$this->model->getFixedDepositProducts($id);
		}else{
			
			
		}	 
	}

	function getFixedDepositapplied($id){
		if(!empty($id)){ 	
			$this->model->getFixedDepositapplied($id);	
		}else{
			
			
		} 
	}


	function getfixedaccountbalance($acc){
		if(!empty($acc)){ 	
			$this->model->getfixedaccountbalance($acc);
		}else{
			
			
		}	
	}
	function getfixedclient_details($acctno){
		if(!empty($acctno)){ 
			$this->model->getfixedclient_details($acctno);
		}else{
			
			
		}	
	}	

	function submitfixeddepositApplication(){
		$data = $_POST;
		if(!empty($data)){ 
			$this->model->submitfixeddepositApplication($data);
		}else{
			
			
		}	
	}
	function updatefixedDepositApplication(){
		$data = $_POST;
		if(!empty($data)){ 
			$this->model->updatefixedDepositApplication($data);
		}else{
			
			
		}	
	}


	function withdrawfromFixed(){
		$data = $_POST;
		if(!empty($data)){ 
			$this->model->withdrawfromFixed($data);
		}else{
			
			
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
			
			
		}
	}

	function depositaccount(){
		$data = $_POST;
		if(!empty($data)){ 
			$this->model->depositOnWalletaccount($data);
		}else{
			
			
		}
	}

	function walletaccountwithdraw(){
		$data = $_POST;
		if(!empty($data)){ 
			$this->model->withdrawFromWalletaccount($data);
		}else{
			
		}
	}

    function depositsavingsaccount(){
		try {
			$headers = getallheaders();
			$office = $headers['office'];
			$user_id = $headers['user_id'];
			$branch = $headers['branch'];
	
			$data = json_decode(file_get_contents('php://input'), true);
	
			if (empty($data)) {
				throw new Exception("Invalid JSON data received.");
			}
	
			$result = $this->model->depositaccount($data, $office, $user_id, $branch);
	
			header('Content-Type: application/json');
			echo json_encode(array("status" => 200, "message" => "Deposit successful", "result" => $result));
	
		} catch (Exception $e) {
 			$errorResponse = array("status" => 500, "message" => $e->getMessage());
			header('Content-Type: application/json');
			http_response_code($errorResponse['status']);
			echo json_encode($errorResponse);
		}
	}

	function withdrawaccount(){
		try {
			$headers = getallheaders();
			$office = $headers['office'];
			$user_id = $headers['user_id'];
			$branch = $headers['branch'];
	
			$data = json_decode(file_get_contents('php://input'), true);
	
			if (empty($data)) {
				throw new Exception("Invalid JSON data received.");
			}
	
			$result = $this->model->withdrawaccount($data, $office, $user_id, $branch);
	
			header('Content-Type: application/json');
			echo json_encode(array("status" => 200, "message" => "Withdrawal successful", "result" => $result));
	
		} catch (Exception $e) {
			$errorResponse = array("status" => 500, "message" => $e->getMessage());
			header('Content-Type: application/json');
			http_response_code($errorResponse['status']);
			echo json_encode($errorResponse);
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
			
			
		}
	}


	function withdraw($acc=null, $id=null, $data=null){

		$prodType=3;
		$transactionName = 'Withdraw on Savings';

		$tran_id = $this->model->getTransactionID($transactionName);
		$this->view->charges = $this->model->getTransactionCharges($tran_id);
		
		$this->view->teller_balance = $this->model->getTellerAccountBalance($_SESSION['user_id']);
		
		if($acc!=null){
			$this->view->savingsAcc = $acc;
			$this->view->withdraw_status = $this->model->getSavingsAccWithDrawStatus($acc);
		}

		if($id!=null){
			$this->view->memberid = $id;	
		}

		if($data!=null){
			$this->view->data = $data;	
		}

		$this->view->render('forms/savings/withdraw');


	}

	function checkWithdrawStatus($acc){
		echo $this->model->getSavingsAccWithDrawStatus($acc);
		die();
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
			
			
		}
	}
	function getfixedtransaction($acc,$transno,$tdate=null){
		if(!empty($acc)&&!empty($transno)){ 
			$this->model->getfixedtransaction($acc,$transno,$tdate);
		}else{
			
			
		}
	}

	function reversefixed(){
		$data=$_POST;
		if(!empty($data)){ 
			$this->model->reversefixed($data);
		}else{
			
			
		}
	}

	function approvefixeddepost(){
		$data=$_POST;
		if(!empty($data)){ 
			$this->model->approvefixeddepost($data);
		}else{
			
			
		}
	}
	function deletefixedaccount(){
		$data=$_POST;
		if(!empty($data)){ 
			$this->model->deletefixedaccount($data);
		}else{
			
			
		}
	}
	function openclosedfixedaccount(){
		$data=$_POST;
		if(!empty($data)){ 
			$this->model->openclosedfixedaccount($data);
		}else{
			
			
		}
	}
	function addCharge($id){
		if(!empty($id)){ 
			$this->view->member_id=$id;
			$this->view->charges = $this->model->getCharges(3);
			$this->view->render('forms/members/addcharge');
		}else{
			
			
		}
	}
	function getcharge(){
		$data = $_POST;
		if(!empty($data)){ 
			$this->view->member_id=$data['id'];
			$this->view->render('forms/members/getchargeform');
		}else{
			
			
		}
	}
	function transferMember($id){
		if(!empty($id)){ 
			$this->view->member_id=$id;
			$this->view->branches=$this->model->officeList();
			$this->view->render('forms/members/transfermember');
		}else{
			
			
		}
	}


	/*end savings application */
	function editmember($id){
		if(!empty($id)){ 
			$office=$_SESSION['office'];
			$this->view->branches=$this->model->getOffice($office); 
			$this->view->staff=$this->model->getstaff($office); 
			$this->view->members = $this->model->getclient($id);
//print_r($this->model->getclient($id));
//die();
			$prdt_code=100;
			$this->view->account=$this->model->getClientSaving($id); 
			$this->view->render('forms/members/editmember');
		}else{
			
			
		}	
	}
	function UpdateClient(){
		$data=$_POST;
		if(!empty($data)){ 
			$this->view->members =$this->model->UpdateClient($data);	
		}else{
			
			
		}	
	}
	function DeleteMember($id){
		if(!empty($id)){ 
			$this->model->DeleteMember($id);	
		}else{
			
			
		}
	}
	function ActivateMember($id){
		if(!empty($id)){ 
			$this->model->ActivateMember($id);	
		}else{
			
			
		}
	}

	function ResetPin($id){
		if(!empty($id)){ 
			$this->model->ResetPin($id);	
		}else{
			
			
		}
	}

	function ResetDevice($id){
		if(!empty($id)){ 
			$this->model->ResetDevice($id);	
		}else{
			
			
		}
	}


	function getstaff($officeid=null){
		$this->model->getstaff($officeid);

	}	
	function getclient_details($acctno){
		if(!empty($acctno)){ 
			$this->model->getclient_details($acctno);
		}else{
			
			
		}	
	}	

///savings application
	function newsavingApplication($acc=null, $id=null){
		if(!empty($acc)){
			$this->view->savingsAcc= $acc;
		}

		if(!empty($id)){
			$this->view->memberid= $id;
		}

		$this->view->employee = $this->model->getEmployees();	
		$this->view->render('forms/savings/newsavingsapplication');	
	}
	
	function modifysavings($acc=null, $id=null){
		if($acc!=null){
			$this->view->savingsAcc =$acc;	
		}

		if ($id!=null) {
			$this->view->memberid = $id;
		}
		$this->view->render('forms/savings/editsavingsapplication');

	}

	function getallSavingsProducttoapply(){
		$this->model->getallSavingsProducttoapply();
	}

	function getmembersavings($acc){
		if(!empty($acc)){ 
			$this->model->getMembersavings($acc);
		}else{
			
			
		}	
	}
	
	function savingsProductCharges($id){
		if(!empty($id)){ 
			$this->model->savingsProductCharges($id);	
		}else{
			
			
		}	
	}

	function submitapplication(){
		$data = $_POST;
		if(!empty($data)){ 
			$this->model->submitapplication($data);
		}else{
			
			
		}	
	}
	function getSavingsProducttoapply($id){
		if(!empty($id)){ 
			$this->model->getSavingsProducttoapply($id);
		}else{
			
			
		}		
	}
	function getsavingproduct($id){
		if(!empty($id)){ 
			$this->model->getsavingproduct($id);	
		}else{
			
			
		}	
	}
	function getclientaccount($id){
		if(!empty($id)){ 
			$this->model->getSavingsProduct($id);
		}else{
			
			
		}		
	}

	function deletesavingsaccount(){
		try {
			$headers = getallheaders();
			$user_id = $headers['user_id'];
	
			$data = json_decode(file_get_contents('php://input'), true);
	
			if (empty($data)) {
				throw new Exception("Invalid JSON data received.");
			}
	
			$result = $this->model->deletesavingsaccount($data, $user_id);
	
			header('Content-Type: application/json');
			echo json_encode(array("status" => 200, "message" => "Savings account deleted successfully", "result" => $result));
	
		} catch (Exception $e) {
			$errorResponse = array("status" => 500, "message" => $e->getMessage());
			header('Content-Type: application/json');
			http_response_code($errorResponse['status']);
			echo json_encode($errorResponse);
		}
	}	
	function savingsStatement($acc=null){
		if(!empty($acc)){
			$this->view->savingsAcc= $acc;
		}
		$this->view->render('forms/savings/savings_statement_form');

	}

	function SavingsAccountDetails($acc = null, $id = null, $all = null) {
		try {
			$headers = getallheaders();
			$office = $headers['office'];
	
			$account = null;
			if ($acc != null) {
				$memberid = $id;
				$account = $acc;
			} else {
				$data = json_decode(file_get_contents('php://input'), true);
	
				if (empty($data) || empty($data['accno'])) {
					throw new Exception("Invalid JSON data received.");
				}
	
				$account = $data['accno'];
			}
	
			$memberzid = $this->model->getAccountMemberID($account, $office);
	
			if (!empty($account)) {
				$savingsAcc = $account;
				$accountholder = $this->model->getMemberaccount($account);
				$transactions = $this->model->getSavingsAccountTransactions($account);
	
				if ($all != null) {
					$alltransactions = $this->model->getAllSavingsAccountTransactions($account);
				}
	
				header('Content-Type: application/json');
				echo json_encode(array("status" => 200, "message" => "Savings account details retrieved successfully", "data" => array(
					"memberid" => $memberid,
					"memberzid" => $memberzid,
					"savingsAcc" => $savingsAcc,
					"accountholder" => $accountholder,
					"transactions" => $transactions,
					"alltransactions" => $alltransactions
				)));
	
			} else {
				$errorResponse = array("status" => 400, "message" => "Invalid account number.");
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
			
			
		}	
	}


	function savingsenquiry($acc = null, $id = null){

		if(!empty($acc)){
			$this->view->savingsAcc= $acc;
		}
		if(!empty($id)){
			$this->view->memberid= $id;
		}
		$this->view->render('forms/savings/savings_account_info');
	}


	function reopensavingsaccount($acc=null, $id=null){

		if($acc!=null){
			$this->view->savingsAcc =$acc;	
		}

		if($id!=null){
			$this->view->memberid =$id;	
		}

		$this->view->render('forms/savings/openclosed_savings');
	}
	function OpenclosedSavings() {
		try {
			$headers = getallheaders();
			$office = $headers['office'];
	
			$data = json_decode(file_get_contents('php://input'), true);
	
			if (empty($data) || empty($data['accno'])) {
				throw new Exception("Invalid JSON data received.");
			}
	
			$result = $this->model->OpenclosedSavings($data, $office);
	
			header('Content-Type: application/json');
			echo json_encode(array("status" => 200, "message" => "Savings account status updated successfully", "result" => $result));
	
		} catch (Exception $e) {
			$errorResponse = array("status" => 500, "message" => $e->getMessage());
			header('Content-Type: application/json');
			http_response_code($errorResponse['status']);
			echo json_encode($errorResponse);
		}
	}	

	function getpendingsaving($acc,$transno,$tdate=null){
		if(!empty($acc)&&!empty($transno)){ 
			$this->model->getpendingsaving($acc,$transno,$tdate);
		}else{
			
			
		}	
	}
	function getsavingtransaction($acc,$transno,$tdate=null){
		if(!empty($acc)&&!empty($transno)){ 
			$this->model->getsavingtransaction($acc,$transno,$tdate);
		}else{
			
			
		}	
	}

	function approvesavings(){
		try {
			$headers = getallheaders();
			$user_id = $headers['user_id'];
	
			$data = json_decode(file_get_contents('php://input'), true);
	
			if (empty($data)) {
				throw new Exception("Invalid JSON data received.");
			}
	
			$result = $this->model->approvesavings($data, $user_id);
	
			header('Content-Type: application/json');
			echo json_encode(array("status" => 200, "message" => "Savings approved successfully", "result" => $result));
	
		} catch (Exception $e) {
			$errorResponse = array("status" => 500, "message" => $e->getMessage());
			header('Content-Type: application/json');
			http_response_code($errorResponse['status']);
			echo json_encode($errorResponse);
		}
	}	
	function reversesavingstransaction($acc=null, $id=null, $desc=null){
		if($id!=null){
			$this->view->memberid =$id;	
		}
		if($acc!=null){
			$this->view->savingsAcc =$acc;	
		}else{
			$this->view->savingsAcc =$acc;
		}
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
			
			
		}	
	}

	function stopinterestaccrualsavings($acc=null, $id=null){
		if($acc!=null){
			$this->view->savingsAcc =$acc;	
		}

		if($id!=null){
			$this->view->memberid =$id;	
		}

		$this->view->render('forms/savings/stopinterestaccrualsavings');
	}
	function stopsavingsaccrualinterest(){
		$data=$_POST;
		if(!empty($data)){ 
			$this->model->stopsavingsaccrualinterest($data);
		}else{
			
			
		}	
	}
	function getmembersimage($acc){
		if(!empty($acc)){ 
			$this->model->getmembersimage($acc);
		}else{
			
			
		}	

	}
//SAVINGS STANDING ORDER
	function makeastandingorder($acc=null, $id=null){
		if($acc!=null){
			$this->view->savingsAcc =$acc;	
		} else{
			$this->view->savingsAcc = NULL;	
		}

		if($id!=null){
			$this->view->memberid =$id;	
		} else{
			$this->view->memberid = NULL;	
		}
		$this->view->render('forms/savings/standingorderform');

	}

	function addstandingorder(){
		$data=$_POST;
		if(!empty($data)){ 
			$this->model->addstandingorder($data);
		}else{
			
			
		}
	}

	function makesavingstransfer($acc=null, $id=null){
		if($acc!=null){
			$this->view->savingsAcc =$acc;	
		} else{
			$this->view->savingsAcc = NULL;	
		}

		if($id!=null){
			$this->view->memberid =$id;	
		} else{
			$this->view->memberid = NULL;	
		}
		
		$this->view->render('forms/savings/makesavingstransfer');

	}

	function addsavingstansfer(){
		$data=$_POST;
		if(!empty($data)){ 
			$this->model->addsavingstansfer($data);
		}else{
			
			
		}
	}

	//interst postings methods
	function postinterest(){
		$data=$_POST;
		if(!empty($data)){ 
			$this->post->postinterest($data);
		}else{
			
			
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