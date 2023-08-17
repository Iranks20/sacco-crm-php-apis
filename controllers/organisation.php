<?php

class Organisation extends Controller{

	public function __construct(){
		parent::__construct();
		Auth::handleSignin();
		Auth::CheckSession();
		Auth::CheckAuthorization();
		$_SESSION['timeout'] = time(); 
	}

	function index(){
		$this->view->details = $this->model->getOrganisations();
		$this->view->render('forms/organisation/vieworganisations');
	}

	function login($id){
		$this->view->id = $id;
		$this->view->details = $this->model->getOrganisationDetails($id);
		$this->view->render('org_login');		
	}

	function authlogin(){
		$this->model->genesisLogin($_POST);
	}

	function addorganisation(){
		$this->view->render('forms/organisation/addorganisation');
	}

	function checkorganisation($org){
		$this->model->checkorganisation($org);
	}

	function insertorganisation(){
        $rs = $this->model->InsertOrganisation($_POST);
		echo json_encode($rs);
	}

	function vieworganisationdetails($id){
		$this->view->details = $this->model->getOrganisationDetails($id);
		$this->view->render('forms/organisation/vieworganisationdetails');
	}

	function editorganisation($id){
		$this->view->details = $this->model->getOrganisationDetails($id);
		$this->view->render('forms/organisation/editorganisation');
	}

	function updateorganisation($id){
		$this->view->allowed_access = $this->model->UpdateOrganisation($_POST, $id);
	}

	function deleteorganisation($id){
		$this->model->DeleteOrganisation($id);
	}

	function orgDetails(){
		//echo "Jesus";
		print_r($_POST);
		die();
	}

}