<?php

class Groups extends Controller{

	public function __construct(){
		parent::__construct();
		Auth::handleSignin();
		Auth::CheckSession();
		//Auth::CheckAuthorization();
		$_SESSION['timeout'] = time(); 
	}

	public function index(){
		$this->view->groups = $this->model->GetAllGroups();
		$this->view->render('forms/groups/viewgroups');
	}

	public function newgroup(){
		$this->view->render('forms/groups/newgroup');
	}

	public function viewgroup($id){
		$this->view->details = $this->model->GetGroupDetails($id);
		$this->view->render('forms/groups/viewgroupdetails');
	}

	public function editgroup($id){
		$this->view->details = $this->model->GetGroupDetails($id);
		$this->view->render('forms/groups/editgroupdetails');
	}

	public function addgroup(){
		$this->model->InsertGroupDetails($_POST);
	}

	public function updategroup($id){
		$this->model->UpdateGroupDetails($_POST, $id);
	}

	public function deletegroup($id){
		$this->model->DeleteGroupDetails($id);
	}
}

?>