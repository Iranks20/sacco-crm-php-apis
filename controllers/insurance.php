<?php

class Insurance extends Controller{

	public function __construct(){
		parent::__construct();
		Auth::handleSignin();
		Auth::CheckSession();
		Auth::CheckAuthorization();
		$_SESSION['timeout'] = time(); 
	}

	function index(){
		$this->view->subs = $this->model->getInsuranceSubscriptions();
		$this->view->render('forms/insurance/subscriptions');
	}

	function insurancestatement($id){
		$this->view->subs = $this->model->getInsuranceTransactions($id);
		$this->view->render('forms/insurance/statement');
	}

	function newsubscription($id=null){

		if ($id!=null) {
			$this->view->mem_id = $id;
		}else{
			$this->view->mem_id = null;
		}

		$this->view->subs = $this->model->getInsuranceTransactions($id);
		$this->view->category = $this->model->getInsuranceCategories($id);
		$this->view->render('forms/insurance/newsubscription');

	}

	function insuranceaccountdetails($acc=null, $id=null, $all=null){
		$account=null;
		if($acc!=null){
			$this->view->memberid = $id;
			$account=$acc;	   
		}else{
			$account=$_POST['accno'];   
		}

		$this->view->memberzid = $this->model->getMemberID($account);

		if(!empty($account)){
			$this->view->pdts = $this->model->getInsuranceProductDetails($account);
			$this->view->insuranceAcc = $account;
			$this->view->accountholder = $this->model->getMemberaccountName($account);
			$this->view->transactions = $this->model->getInsuranceAccountTransactions($account);
			if ($all != NULL) {
				$this->view->alltransactions=$this->model->getAllInsuranceAccountTransactions($account);
			}
			$this->view->render('forms/insurance/statement');
		}else{
			header('Location: ' . URL . 'insurance/insuranceaccount/');    
		}
	}

	function insuranceaccount(){
		$this->view->render('forms/insurance/insuranceaccount');
	}

	function getinsuracememberdetails($acc){
		if(!empty($acc)){ 
			$this->model->getinsuracememberdetails($acc);
		}else{			
			
		}	
	}

	function claims($id=null){	
		if (empty($id)) {
			$this->view->claims = $this->model->getAllInsuranceClaims();
		} else {
			$this->view->claims = $this->model->getInsuranceClaims($id);
		}
		$this->view->render('forms/insurance/insuranceclaims');		
	}

	function pendingclaims($id=null){	
		if (empty($id)) {
			$this->view->claims = $this->model->getAllPendingInsuranceClaims();
		} else {
			$this->view->claims = $this->model->getPendingInsuranceClaims($id);
		}
		$this->view->render('forms/insurance/pendingclaims');		
	}

	function approved($id=null){
		if (empty($id)) {
			$this->view->claims = $this->model->getAllApprovedInsuranceClaims();
		} else {
			$this->view->claims = $this->model->getApprovedInsuranceClaims($id);
		}
		$this->view->render('forms/insurance/insuranceclaims');
	}

	function closed($id=null){
		if (empty($id)) {
			$this->view->claims = $this->model->getAllClosedInsuranceClaims();
		} else {
			$this->view->claims = $this->model->getClosedInsuranceClaims($id);
		}
		$this->view->render('forms/insurance/insuranceclaims');
	}

	function processclaims($id, $state){
		if ($state == 'approve') {
			$this->view->claims = $this->model->changeInsuranceStatus($id, $state);
			header('Location: ' . URL . 'insurance/approved');
		} else if ($state == 'close') {
			$this->view->claims = $this->model->changeInsuranceStatus($id, $state);
			header('Location: ' . URL . 'insurance/closed');
		}
	}

	function newclaim(){
		$this->view->claims = $this->model->getAllInsuranceClaims();
		$this->view->render('forms/insurance/newclaim');		
	}

	function getInsuranceProductapp($amt, $id, $category){
		if(!empty($amt)){	
			$this->model->getInsuranceProductapp($amt, $id, $category);
		}else{
			header('Location: ' . URL . 'insurance'); 					
		}		
	}

	function getinsuranceproductapplied($acc, $amt){
		if(!empty($acc)){	
			$this->model->getinsuranceproductapplied($acc, $amt);
		}else{
			header('Location: ' . URL . 'insurance'); 					
		}		
	}

	function applyinsurance(){
		$this->model->applyinsurancesubscription($_POST);
	}

	function applyinsuranceclaim(){
		$this->model->applyinsuranceclaim($_POST);
	}

	function prepareinsurancememberdetails($id, $actno){
		$this->model->prepareinsurancememberdetails($id, $actno);
	}
}

?>