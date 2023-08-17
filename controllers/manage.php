<?php

//require __DIR__ . '/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use League\Csv\Reader;
define('FPDF_FONTPATH', __DIR__ . '/../vendor/setasign/fpdf/font/');
require(__DIR__ . '/../vendor/setasign/fpdf/fpdf.php');

class Manage extends Controller{

	public function __construct(){
		parent::__construct();
		Auth::handleSignin();
		Auth::CheckSession();
		Auth::CheckAuthorization();
		$_SESSION['timeout'] = time();
	}

	function socialmedia(){
		$this->view->render('forms/manage/socialmedia');
	}

	function campaigns(){
		$this->view->details = $this->model->getSaccoCampaigns();
		$this->view->render('forms/manage/campaigns');
	}

	function addcampaign(){
		$this->view->render('forms/manage/addcampaign');
	}

	function index(){
		$this->view->currency = $this->model->getThisSaccoCurrency();
		$this->view->render('forms/manage/dashboard');
	}

	function changelogo(){
		$this->model->changelogo($_POST);
	}

	function logs(){
		$this->view->details = $this->model->SaccoStaff();
		$this->view->render('forms/manage/logs');
	}

	function viewstafflogs($id){
		$this->view->currency = $this->model->getThisSaccoCurrency();
		$this->view->staff = $this->model->getStaffDetails($id);
		$this->view->details = $this->model->getStaffTransactions($id);
		$this->view->render('forms/manage/stafflogs');
	}
	
	function viewstaffactivities($id){
		$this->view->currency = $this->model->getThisSaccoCurrency();
		$this->view->staff = $this->model->getStaffDetails($id);
		$this->view->details = $this->model->getStaffActivities($id);
		$this->view->render('forms/manage/staffactivities');
	}
	
    function exportLogTransactionspdf($id){
    
		$currency = $this->model->getThisSaccoCurrency();
		$staff = $this->model->getStaffDetails($id);
		$details = $this->model->getStaffTransactions($id);
    	
    	
    	$pdf = new FPDF();
    	$pdf->AddPage();
    	$pdf->SetFont('Helvetica','b',16);
    	$pdf->Cell(30,7, ucfirst($staff[0]['firstname']) . ' ' . ucfirst($staff[0]['lastname']) . ' Activity Report',2);
        $pdf->Ln();
    	$pdf->Ln();
    	$pdf->SetFont('Helvetica','',6);
    	$pdf->Cell(25,6,'Transaction Id',1);
    	$pdf->Cell(20,6,'Journal ID',1);
    	$pdf->Cell(25,6,'Date Created',1);
    	$pdf->Cell(20,6,'Transaction Type',1);
    	$pdf->Cell(35,6,'Name',1);
    	$pdf->Cell(20,6,'Amount (' . ucwords($currency) . ')',1);
    	$pdf->Cell(20,6,'Details',1);
    	
    	//loop through the data
    	foreach ($details as $key => $value){
        	$pdf->Ln();
        	$pdf->Cell(25,6,$value["transaction_id"],1);
        	$pdf->Cell(20,6,$value["journal_id"],1);
        	$pdf->Cell(25,6,date_format(date_create($value["created_date"]), "d/m/Y"),1);
        	$pdf->Cell(20,6,$value["transaction_type"],1);
        	$pdf->Cell(35,6,$value["name"],1);
        	$pdf->Cell(20,6,number_format($value["amount"]),1);
        	$pdf->Cell(20,6,$value[15],1);
    	}
    
    	$pdf->Output();

    }
	
    function exportLogActivitiespdf($id){
    
		$staff = $this->model->getStaffDetails($id);
		$details = $this->model->getStaffActivities($id);
    	
    	
    	$pdf = new FPDF();
    	$pdf->AddPage();
    	$pdf->SetFont('Helvetica','b',16);
    	$pdf->Cell(30,7, ucfirst($staff[0]['firstname']) . ' ' . ucfirst($staff[0]['lastname']) . ' Activity Report',2);
        $pdf->Ln();
    	$pdf->Ln();
    	$pdf->SetFont('Helvetica','',6);
    	$pdf->Cell(20,6,'Transaction Id',1);
    	$pdf->Cell(20,6,'ID',1);
    	$pdf->Cell(25,6,'Date Created',1);
    	$pdf->Cell(20,6,'Transaction Id',1);
    	$pdf->Cell(65,6,'Operation Type',1);
    	$pdf->Cell(20,6,'Is Transaction',1);
    	$pdf->Cell(20,6,'Response Data',1);
    	
    	//loop through the data
    	foreach ($details as $key => $value){
        	$pdf->Ln();
        	$pdf->Cell(20,6,$value["transaction_id"],1);
        	$pdf->Cell(20,6,$value["id"],1);
        	$pdf->Cell(25,6,$value["date_created"] ,1);
        	$pdf->Cell(20,6,$value["transaction_id"],1);
        	$pdf->Cell(65,6,$value["operation_type"],1);
        	$pdf->Cell(20,6,$value["is_transaction"],1);
        	$pdf->Cell(20,6,$value["response_data"],1);
    	}
    
    	$pdf->Output();

    }
    

	function todaytransactions($id){
		$this->view->currency = $this->model->getThisSaccoCurrency();
		$this->view->staff = $this->model->getStaffDetails($id);
		$this->view->details = $this->model->getTodayTransactions($id);
		$this->view->render('forms/manage/stafflogs');		
	}

	function todaysystemtransactions($id){
		$this->view->currency = $this->model->getThisSaccoCurrency();
		$this->view->staff = $this->model->getStaffDetails($id);
		$this->view->details = $this->model->getTodayActivities($id);
		$this->view->render('forms/manage/staffactivities');		
	}

	function todaylogtransactions($id){
		$this->view->currency = $this->model->getThisSaccoCurrency();
		$this->view->staff = $this->model->getStaffDetails($id);
		$this->view->details = $this->model->getTodayTransactionActivities($id);
		$this->view->render('forms/manage/staffactivities');		
	}

	function yesterdaytransactions($id){
		$this->view->currency = $this->model->getThisSaccoCurrency();
		$this->view->staff = $this->model->getStaffDetails($id);
		$this->view->details = $this->model->getYesterdayTransactions($id);
		$this->view->render('forms/manage/stafflogs');		
	}

	function yesterdaysystemtransactions($id){
		$this->view->currency = $this->model->getThisSaccoCurrency();
		$this->view->staff = $this->model->getStaffDetails($id);
		$this->view->details = $this->model->getYesterdayActivities($id);
		$this->view->render('forms/manage/staffactivities');		
	}

	function yesterdaylogtransactions($id){
		$this->view->currency = $this->model->getThisSaccoCurrency();
		$this->view->staff = $this->model->getStaffDetails($id);
		$this->view->details = $this->model->getYesterdayTransactionActivities($id);
		$this->view->render('forms/manage/staffactivities');		
	}

	function monthtransactions($id){
		$this->view->currency = $this->model->getThisSaccoCurrency();
		$this->view->staff = $this->model->getStaffDetails($id);
		$this->view->details = $this->model->getMonthTransactions($id);
		$this->view->render('forms/manage/stafflogs');		
	}

	function monthsystemtransactions($id){
		$this->view->currency = $this->model->getThisSaccoCurrency();
		$this->view->staff = $this->model->getStaffDetails($id);
		$this->view->details = $this->model->getMonthActivities($id);
		$this->view->render('forms/manage/staffactivities');		
	}

	function monthlogtransactions($id){
		$this->view->currency = $this->model->getThisSaccoCurrency();
		$this->view->staff = $this->model->getStaffDetails($id);
		$this->view->details = $this->model->getMonthTransactionActivities($id);
		$this->view->render('forms/manage/staffactivities');		
	}

	function rangetransactions($id){
		$this->view->currency = $this->model->getThisSaccoCurrency();
		$this->view->staff = $this->model->getStaffDetails($id);
		$this->view->details = $this->model->getRangeTransactions($id, $_POST);
		$this->view->render('forms/manage/stafflogs');		
	}

	function rangesystemtransactions($id){
		$this->view->currency = $this->model->getThisSaccoCurrency();
		$this->view->staff = $this->model->getStaffDetails($id);
		$this->view->details = $this->model->getRangeActivities($id, $_POST);
		$this->view->render('forms/manage/staffactivities');		
	}

	function rangelogtransactions($id){
		$this->view->currency = $this->model->getThisSaccoCurrency();
		$this->view->staff = $this->model->getStaffDetails($id);
		$this->view->details = $this->model->getRangeTransactionActivities($id, $_POST);
		$this->view->render('forms/manage/staffactivities');		
	}

	/* OFFFICES   */
	
	function newbranch(){
		$this->view->admin="new";
		$this->view->currency = $this->model->getCurrencies();
		//$this->view->office=$data['branch'];	
		//$this->view->render('forms/manage/newbranchoptions');
		$this->view->render('forms/manage/newbranch');
	}

	function viewoffice($id){
		$this->view->details = $this->model->getOfficeNUserDetails($id);
		$this->view->render('forms/manage/viewOffice');
	}

	function createbranchproceed(){
		$data=$_POST;
		if(!empty($data)){
			$this->view->admin=$data['admin'];	
			$this->view->office=$data['branch'];
			$this->view->accesslevels = $this->model->getAccesslevels();	
			if($data['admin']!='new'){
				$this->view->employees=$this->model->EmployeeList();		
				$this->view->administrator=$this->model->getAdminDetails();		
			}
			$this->view->render('forms/manage/newbranchoptions');
		}else{
			header('location:' . URL .'manage/newbranch');
		}
	}

	function createbranch(){
		$this->model->createbranch();
	}

	function branchstaff($id){
		$this->view->details = $this->model->getBranchStaff($id);
		$this->view->render('forms/manage/branchestaff');
	}
	function branches(){
		$this->view->office = $this->model->officeList();
		$this->view->render('forms/manage/brancheslist');
	}
	function editoffice($id){
		$this->view->branches = $this->model->getOfficeDetails($id);
		$this->view->office = $this->model->getoffice($id);
		$this->view->render('forms/manage/editoffice');
	}
	
	function UpdateOffice(){
		$data=$_POST;
		$this->model->UpdateOffice($data);		
	}

	function DeleteOffice($id){
		$data=$_POST;
		$this->model->DeleteOffice($id);	
	}

	function resetpassword($id){
		$this->model->ResetOfficePassword($id);	
	}
	
	///payment types ///
	function AddPaymentType(){
		$this->model->AddPaymentType();
	}

	function PaymentType(){
		$this->view->payment = $this->model->PaymentTypeList();
		$this->view->render('forms/manage/paymenttype');
	}

	function NewPaymentType(){
		$this->view->render('forms/manage/addpaymenttype');
	}

	function EditePaymentType($id){
		$this->view->paymenttype = $this->model->GetPaymentType($id);

		$this->view->render('forms/manage/editepaymenttype');
	}
	function UpdatePaymentType(){
		$data=$_POST;
		$this->model->UpdatePaymentType($data);
		
	}
	function DeletePaymentType($id){
		$data=$_POST;
		$this->model->DeletePaymentType($id);		   
	}


	/* Employees  */
	
	function newEmployee(){
		$this->view->accesslevels = $this->model->getAccesslevels();
		$this->view->allowed_rights = $this->model->getAccessRights();
		$this->view->render('forms/manage/newemployee');
	}

	function createEmployee(){
		$data=$_POST;
		$this->model->CreateEmployee($data);

		header('Location: ' . URL . 'manage/employees?msg=success'); 	
		
	}

	function employeeDetails($id){
		$this->view->employee_id=$id;
		$this->view->employee=$this->model->getEmployee($id);
		$this->view->render('forms/manage/employeedetails');

		
	}

	function editEmployee($id){
		$this->view->employee_id=$id;
		$this->view->office = $this->model->officeList();
		$this->view->employee= $this->model->getEmployee($id);
		$this->view->accesslevels = $this->model->getAccesslevels();
		$this->view->render('forms/manage/editemployee');

		
	}
	function updateEmployee(){
		$data=$_POST;
		$this->model->updateEmployee($data);
	}
	function deleteEmployee($id){
	//$this->model->deleteEmployee($id);

		
	}

	function employeetransfer($id){

		$this->view->employee=$this->model->getEmployee($id);
//print_r($this->view->employee);die();
		$this->view->office = $this->model->getOfficeDetails($this->view->employee[0]['office_id']);	
		$this->view->render('forms/manage/employeetransferform');
		
	}

	function transferemployee(){
		$data=$_POST;
		$this->model->transferemployee($data);
		
	}


	function employees(){
		$this->view->staff = $this->model->EmployeeList();
		$this->view->render('forms/manage/employeeList');
	}

	/* Currency  */
	function currency(){
		$this->view->currency  = $this->model->currencyList();	
		$this->view->render('forms/manage/currconfig');
	}
	
	function newcurrency(){
		$this->view->render('forms/manage/addcurrency');
	}

	function createCurrency(){
		$this->model->createCurrency();
		$this->view->render('forms/manage/addcurrency');		
	}

	function editCurrency($id){
		$this->view->currencyid = $this->model->getCurrency($id);
		$this->view->render('forms/manage/editcurrency');
		
	}
	function updateCurrency(){
		$data=$_POST;
		$this->model->updateCurrency($data);
	}
	function deleteCurrency($id){
		$data=$_POST;
		
		$this->model->deleteCurrency($id);
	}	

	function pointers(){
		$this->view->pointers = $this->model->getAllPointers();
		$this->view->render('forms/manage/pointers');
	}

}