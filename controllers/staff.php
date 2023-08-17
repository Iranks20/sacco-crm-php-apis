<?php

class Staff extends Controller{

	public function __construct(){
		parent::__construct();
		Auth::handleSignin();
		Auth::CheckSession();
		Auth::CheckAuthorization();
		$_SESSION['timeout'] = time(); 
	}

	function index(){
		$this->view->details = $this->model->getStaff();
		$this->view->render('forms/staff/viewstaff');
	}

	function addstaff(){
		$this->view->accounts = $this->model->getGLAccounts();
		$this->view->teller_id = $this->model->getTellerAccountID();
		$this->view->accesslevels = $this->model->getAccesslevels();
		$this->view->render('forms/staff/addstaff');
	}

	function insertstaff(){
        $this->model->InsertStaff($_POST);
	}

	function viewstaffdetails($id){
		$this->view->details = $this->model->getStaffDetails($id);
		$this->view->render('forms/staff/viewstaffdetails');
	}
	
	function verifystaff($id){
	    $this->model->verifyStaffAccount($id);
	}

	function editstaff($id){
		$this->view->accesslevels = $this->model->getAccesslevels();
		$this->view->details = $this->model->getStaffDetails($id);
		$this->view->account_details = $this->model->getStaffAccountDetails($id);
		$this->view->render('forms/staff/editstaff');
	}

	function updatestaff($id){
		$this->view->allowed_access = $this->model->UpdateStaff($_POST, $id);
	}

	function deletestaff($id){
		$this->model->DeleteStaff($id);
	}

	function resetstaff($id){
		$this->model->ResetPassword($id);
	}
	
	function changePassword($id){
	    if($id == $_SESSION['user_id']){
    		$this->view->details = $this->model->getStaffDetails($id);
    		$this->view->render('forms/staff/changepassword');
	    } else {
	        header('Location: ' . URL);
	    }
	}
	
	function updatePassword($id){
	    $this->model->ChangePassword($id);
	}

}