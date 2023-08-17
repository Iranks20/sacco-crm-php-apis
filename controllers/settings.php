<?php
require __DIR__ . '/../vendor/autoload.php';
class Settings extends Controller{

	public function __construct(){
		parent::__construct();
		Auth::handleSignin();
		Auth::CheckSession();
		Auth::CheckAuthorization(); 
		$_SESSION['timeout'] = time(); 
	}

	function index(){
		$this->view->render('forms/system/dashboard');
	}

	function addbank(){
		$this->view->render('forms/system/addbank');	
	}

	function addbatchpayment(){
		$this->view->members = $this->model->getAllMembers();
		$this->view->render('forms/system/addbatchpayment');	
	}

	function checkBulkAccountFrom($acc){
		if(!empty($acc)){ 	
			$this->model->checkBulkAccountFrom($acc);
		}else{
		}	
	}

	function insertbulk(){
		$this->model->insertnewbulk($_POST);
	}

	function editbank($id){
		$this->view->details = $this->model->getBankDetails($id);
		$this->view->render('forms/system/updatebank');	
	}

	function editpayment($id){
		$this->view->members = $this->model->getAllMembers();
		$this->view->details = $this->model->getBulkDetails($id);
		$this->view->render('forms/system/editbatchpayment');
	}

	function insertbank(){
		$this->model->insertnewbank($_POST);
	}

	function updatebank(){
		$this->model->updatebank($_POST);
	}

	function updatebulk($id){
		$this->model->updatebulk($_POST,$id);
	}

	function banks(){
		$this->view->details = $this->model->getBanks();
		$this->view->render('forms/system/viewbanks');	
	}

	function viewbank($id){
		$this->view->details = $this->model->getBankDetails($id);
		$this->view->render('forms/system/viewbank');	
	}

	function viewpermissions(){
		$this->view->permissions = $this->model->getRoles();
		$this->view->render('forms/system/system_permissions');
	}

	function permissions($id){
		$this->view->employee = $this->model->getEmployee($id);
		$this->view->permissions = $this->model->getRoles();
		$this->view->render('forms/system/permissions_form');
	}

	function viewrole($id){
		$this->view->getrole = $this->model->getroleid($id);
		$this->view->render('forms/system/viewrole');
	}

	function myaccountInfo(){
		$id=$_SESSION['user_id'];	
		$this->view->account = $this->model->getEmployee($id);
		$this->view->render('forms/system/useraccount');
	}

	function changemypassword(){
		$id=$_SESSION['user_id'];	
		$this->view->account = $this->model->getEmployee($id);
		$this->view->render('forms/system/accountpasswordform');
	}

	function checkpass($password){

		$id=$_SESSION['user_id'];
		$oldpassword = $this->model->getEmployee($id)[0]['password'];
		$pwd = Hash::create('sha256',$password ,HASH_ENCRIPT_PASS_KEYS);
		
		echo $oldpassword;

		if ($oldpassword == $pwd){

			$res = ["status"=>"True"];
			echo json_encode($res);

		}else{
			
			$res = ["status"=>"False"];
			echo json_encode($res);
		}
	}
	
	function updateaccount(){
		//Get current user Password.
		$data=$_POST;
		$password = $data['unpass'];
		$validator = new \Password\Validator(new \Password\StringHelper);
		$validator->setMinLength(10);
		$validator->setMinLowerCaseLetters(1);
		$validator->setMinUpperCaseLetters(1);
		$validator->setMinNumbers(1);
		$validator->setMinSymbols(1);

		if ($validator->isValid($password)) {
		   // printf('password %s is valid' . PHP_EOL, $password);
		    $this->model->updateaccount($data);
		} else {
		    header('Location: ' . URL . 'settings/changemypassword/?msg=failed');  
		}
	

	}

	function updatepermissions(){
		$data=$_POST;	
		$this->model->updatepermissions($data);
	}

	function template(){
		$this->view->render('forms/system/template');
	}

	////////////////////////////////////////////
	function logs(){
		$this->view->logs = $this->model->getSystemLogs();
		$this->view->render('forms/system/logs');	
	}

	function logdetails($id){
		$this->view->details = $this->model->getLogDetails($id);
		$this->view->render('forms/system/logdetails');	
	}
	
	function batchpayments(){
		$this->view->details = $this->model->getBulkPaymentDetails();
		$this->view->render('forms/system/bulkpayments');	
	}
	
	function newbatchpayment(){
		$this->view->render('forms/system/batchpayments');	
	}

	function batchregistration(){
		$this->view->render('forms/system/batchregistration');	
	}

	function removepayment($id){
		$this->model->removepayment($id);
	}

	function verifycsv(){
		$this->model->verifycsv($_POST['file_path']);
	}

	function verifyregcsv(){
		$this->model->verifyregcsv($_POST['file_path']);
	}

	function bulkimport() {
		$data = array();
		$data['audit_file_temp'] = $_FILES['file_name']['tmp_name'];
		$data['audit_file_type'] = $_FILES['file_name']['type'];

		if($data['audit_file_type'] == 'application/vnd.ms-excel'){
			$this->model->ImportBulk($data);
		} else {
			header("Location:".URL ."settings/batchpayments?msg=invalid");
		}
	}

	function bulkreg() {
		$data = array();
		$data['audit_file_temp'] = $_FILES['file_name']['tmp_name'];
		$data['audit_file_type'] = $_FILES['file_name']['type'];

		if($data['audit_file_type'] == 'text/csv'){
			$this->model->ImportBulkReg($data);
		} else {
			header("Location:". URL ."settings/batchregistration?msg=invalid");
		}
	}

	function processcsv(){
		$this->model->processcsv($_POST['file_path']);
	}

	function processregcsv(){
		$this->model->processregcsv($_POST['file_path']);
	}

}