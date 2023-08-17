<?php

class Accounting extends Controller{
	
public function __construct(){
parent::__construct();
Auth::handleSignin();
Auth::CheckSession();
Auth::CheckAuthorization();
 $_SESSION['timeout'] = time(); 
}

function index(){
	
	$this->view->coa = $this->model->checkAccounts();	
	$this->view->gla = $this->model->checkLedgerAccounts();	
	$this->view->cicop = $this->model->checkPointers();	
	$this->view->cgapcount = $this->model->getCGAPAccountsCount();	
	$this->view->mop = $this->model->checkBalances();	
	$this->view->thdp = $this->model->checkThirdpartyProducts();	
	$this->view->suwp = $this->model->checkWalletPointers();

	$this->view->render('forms/accounting/dashboard');
}

function cgapthresholds(){

	$this->view->details = $this->model->getSaccoCGAPDetails();
	$this->view->render('forms/accounting/cgapthresholds');

}

function cgapsetup(){

	$count = $this->model->getCGAPAccountsCount();
	if($count >= 6){
		$this->view->accounts = $this->model->getGlaccount();
		$this->view->detail = $this->model->getSelectedCGAPAccounts();
		$this->view->render('forms/accounting/viewcgapsetup');
	} else {
		$this->view->accounts = $this->model->getGlaccount();
		$this->view->render('forms/accounting/cgapsetup');
	}

}

function insertcgapaccounts(){
	$this->model->insertcgapaccounts($_POST);
}

function editcgapaccounts(){
	$this->view->accounts = $this->model->getGlaccount();
	$this->view->detail = $this->model->getSelectedCGAPAccounts();
	$this->view->render('forms/accounting/editcgapaccounts');	
}

function updatecgapaccounts(){
	$this->model->updatecgapaccounts($_POST);
}

function chartsOfAccount(){	
	$this->view->charts=$this->model->getCharts();
	$this->view->render('forms/accounting/chartsofaccounts');
}

function imgexcelaccounts(){
	$this->view->render('forms/accounting/accountsImport');
}

function uploadfile(){
	$data = array();
	$data['audit_file_temp'] = $_FILES['file_name']['tmp_name'];
	$data['audit_file_type'] = $_FILES['file_name']['type'];

	if($data['audit_file_type'] == 'application/vnd.ms-excel'){
		$this->model->ImportExcelAccounts($data);
	} else {
		header("Location:". URL ."accounting/imgexcelaccounts?msg=invalid");
	}
}

function verifyfile(){
	$this->model->verifycsv($_POST['file_path']);
}

function processcsv(){
	$this->model->processcsv($_POST['file_path']);
}

function tellers(){
	$this->view->details=$this->model->getTellers();
	$this->view->render('forms/accounting/tellers');

}

function viewtellerrequests($id){
	$this->view->details=$this->model->getTellerDetails($id);
	$this->view->render('forms/accounting/tellerdetails');
}
function requestcash(){
	$this->model->requestCash($_POST);
}
function returncash(){
	$this->model->returnCash($_POST);
}
function sampleAccounts(){
	$this->view->charts=$this->model->getSampleCharts();
	$this->view->render('forms/accounting/sampleaccounts');
}
function copytemplate(){
	$this->model->CopyAccounts($_POST['accounts']);
}
function gldetails($id){
	$this->view->charts=$this->model->getGlaccountdetails($id);
	$this->view->render('forms/accounting/gldetails');
	
}
function Journals(){
	$this->view->journals=$this->model->getJournals();
	$this->view->render('forms/accounting/journals');
	
}
function journaltransactiondetails($id){
	$this->view->journals=$this->model->getJournalsTransaction($id);
	$this->view->render('forms/accounting/journaltransactiondetails');
	
}
	function Journal(){
		$this->view->office=$this->model->getbranches();
		$this->view->parentgl=$this->model->getGlaccount();
		$this->view->payment=$this->model->getPaymentType();
		$this->view->render('forms/accounting/journal');
	}
	
	function cashmanagement(){

		$this->view->cashAccbalance = $this->model->getCashAccountBalance();
		$this->view->cashbalance = $this->model->getGLTellerCashAccountBalance();	
		//$this->view->accbalance = $this->model->getUserCashBalance();
		$this->view->accbalance = $this->model->getTellerCashAccountBalance();
		$this->view->cashaccount = $this->model->getCashAccountDetails();
		$this->view->currency = $this->model->getThisSaccoCurrency();
		$this->view->details = $this->model->getTellerRequests();
		$this->view->closed = $this->model->getTellerReturns();
		$this->view->teller_account = $this->model->getTellerAccountID();

		$teller = $this->model->getTellerAccountID();
		if (isset($teller)) {
			$this->view->teller_account_details = $this->model->getTellerAccountDetails($teller);
		} else {
			$this->view->teller_account_details = NULL;
		}

		$this->view->office=$this->model->getbranches();
		$this->view->parentgl=$this->model->getGlaccount();
		$this->view->payment=$this->model->getPaymentType();
		$this->view->render('forms/accounting/tellerfloat');
	}
	
	function createreturnjournal(){
		$this->model->createReturnJournal($_POST);
	}
	
function createJournal(){
	$data=$_POST;
	$this->model->createJournal($data);
}

function insertJournalEntry(){
	$data=$_POST;
	$this->model->insertJournalEntry($data);
}
function AccountingRules(){
	
	$this->view->rules=$this->model->getAccountingRules();
	$this->view->render('forms/accounting/accountingrules');
	
}
function NewAccountingRule(){
     $this->view->office=$this->model->getbranches();
	$this->view->parentgl=$this->model->getGlaccount();
	$this->view->render('forms/accounting/newaccountingrule');
	
}

function SearchJournal(){
	$this->view->office=$this->model->getbranches();
	$this->view->parentgl=$this->model->getGlaccount();
	$this->view->render('forms/accounting/searchjournal');
	
}
function getSearchresults(){
	$data=$_POST;

	$this->view->results=$this->model->getSearchresults($data);
	$this->view->render('forms/accounting/sech');
	
	
	
}

function ruleDetails($id){
	
	$this->view->rule=$this->model->ruleDetails($id);
	$this->view->render('forms/accounting/ruledetails');
	
}
function EditRule($id){
	$this->view->office=$this->model->getbranches();
	$this->view->rule=$this->model->ruleDetails($id);
	$this->view->parentgl=$this->model->getGlaccount();
	$this->view->render('forms/accounting/editaccountingrule');
	
}
function createAccountingrule(){
	  $data=$_POST;
	$this->model->createAccountingrule($data);
	
}
function UpdateAccountingRule(){
	 $data=$_POST;
	$this->model->UpdateAccountingRule($data);
	
}
function DeleteRule($id){
	
	$this->model->DeleteRule($id);
	
}
function deleteglaccount($id){	
	$this->model->deleteglaccount($id);
	
}
function newGlAccount($id=null){
if($id!=null){
$this->view->glaccountdetails = $this->model->getGlaccountdetails($id);
}else{
$this->view->glaccountdetails =0;
	
}
$this->view->parents=$this->model->getGlparent();
$this->view->render('forms/accounting/newglaccount');

}

function getHeaderAccounts($id){

$this->model->getHeaderAccounts($id);
	
}
function EditGlAccount($id){
 $this->view->parents=$this->model->getGlparent($id);
 $this->view->glaccountdetails = $this->model->getGlaccountdetails($id);
$this->view->render('forms/accounting/editglaccount');

}

function UpDateGlAccount(){
  $data=$_POST;
  $this->model->UpDateGlAccount($data);
}
function createNewGlAccount(){
$this->model->createnewGlaccount();

}




/**********Postings *******************/
function receivePayments(){	
$this->view->office=$this->model->getbranches();	
$this->view->rules=$this->model->getCashinRules();
$this->view->payment=$this->model->getPaymentType();	
    $this->view->render('forms/accounting/receivepayments');
	
}

function PayoutCash(){	
$this->view->office=$this->model->getbranches();	
$this->view->rules=$this->model->getCashOutRules();
$this->view->payment=$this->model->getPaymentType();	
    $this->view->render('forms/accounting/paycash');
	
}

function createPost(){
	$data=$_POST;
$this->model->createPost($data);
}

function getruledetails($id=null){
	$this->model->getruledetails($id);
	
}	

	
/**********Accounts closure *******************/
function ClosedAccounts(){

$this->view->closed=$this->model->getClosedAccounts();
$this->view->render('forms/accounting/closedaccounts');
	
}
function closureDetails($id){

$this->view->closed=$this->model->getClosedAccounts($id);
$this->view->render('forms/accounting/closuredetails');
	
}
function createclosure(){
	$this->view->office=$this->model->getbranches();	
$this->view->render('forms/accounting/createclosure');
	
}	
function postClosure(){
$data=$_POST;
$this->model->postClosure($data);
	
}
function deleteClosure($id){
$this->model->deleteClosure($id);
	
}	

/**********Migrate Opening Balances *******************/
function openingBalances(){

	$this->view->balances = $this->model->checkBalances();	

	$this->view->office=$this->model->getbranches();
	$this->view->assets=$this->model->getAssets();
	$this->view->liability=$this->model->getLiability();
	$this->view->equity=$this->model->getEquity();
	$this->view->income=$this->model->getIncome();
	$this->view->expenses=$this->model->getExpenses();
	$this->view->render('forms/accounting/openingbalances');	
}

function getallglaccounts(){
	$this->model->getallglaccounts();
}

function initializeOpeningBalance(){
	$this->model->initializeOpeningBalance();
	//$this->view->render('forms/accounting/openingbalances');
}

function skipinitializeopeningbalance(){
	$this->model->skipOpeningBalances();	
}

///BALANCING OF ACCOUNTS	
function balancetoday(){
$this->model->getGLDayreport();
}

////////////////////////////////////////////// STEVEN //////////////////////////////////
function setupglaccounts(){
	$count = $this->model->getGlAccountsCount();
	if($count >= 5){
		$this->view->accounts = $this->model->getGlaccount();
		$this->view->products = $this->model->getProducts();
		$this->view->detail = $this->model->getSelectedGlaccounts();
		$this->view->render('forms/accounting/viewaccountsetup');
	} else {
		$this->view->accounts = $this->model->getGlaccount();
		$this->view->products = $this->model->getProducts();
		$this->view->render('forms/accounting/accountsetup');
	}
}

function insertglaccounts(){
	$this->model->insertglaccounts($_POST);
}

function editglaccounts(){
	$this->view->accounts = $this->model->getGlaccount();
	$this->view->products = $this->model->getProducts();
	$this->view->detail = $this->model->getSelectedGlaccounts();
	$this->view->render('forms/accounting/editaccountsetup');
}

function updateglaccountmappings($id){
	$this->model->updateglaccountmappings($_POST, $id);	
}

}