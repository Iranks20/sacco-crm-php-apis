<?php

class Access extends Controller{

	public function __construct(){
		parent::__construct();
		Auth::handleSignin();
		Auth::CheckSession();
		Auth::CheckAuthorization();
		$_SESSION['timeout'] = time(); 
	}

	function index(){
		$this->view->rights = $this->model->getAccessLevels();
		$this->view->render('forms/access/viewpermissions');
	}

	function viewpermissions(){
		$this->view->rights = $this->model->getAccessRights();
		$this->view->pagerights = $this->model->getPageAccessRights();
		$this->view->render('forms/access/dashboard');
	}

	function addpermissions(){

		$permissions = $_POST['permission'];

		$perm = '';

		if (isset($permissions)) {
			foreach ($permissions as $key => $value) {
				$parent = $this->model->getParent($value);
				if ($parent[0]['parent_option']!=0) {
					array_push($permissions, $parent[0]['parent_option']);
				}
			}
			sort($permissions);
			array_unique($permissions, SORT_REGULAR);
			foreach ($permissions as $key => $value) {
				$perm .= $value . ",";
			}
		}

		$page_perm = '';
		if (isset($_POST['page_permission'])) {
			sort($_POST['page_permission']);

			foreach ($_POST['page_permission'] as $key => $value) {
				$page_perm .= $value . ",";
			}
		}
	    $postData = array(
			'access_denotor' => $_POST['level'],
			'office_id' => $_SESSION['office'],
			'user_id' => $_SESSION['user_id'],
			'access_name' =>$_POST['name'],
            'creator_access' => $_POST['level'],
            'allowed_access' => $perm,
            'allowed_access_menu' => $page_perm,
        );

        $this->model->InsertPermission($postData);

	}

	function viewaccessdetail($id){
		$this->view->details = $this->model->getAccessDetails($id);
		$this->view->rights = $this->model->getAccessRights();
		$this->view->pagerights = $this->model->getPageAccessRights();

		$details = $this->model->getAccessDetails($id);
		$allowed = explode(',', $details[0]['allowed_access']);
		$allowed_menus = explode(',', $details[0]['allowed_access_menu']);

		$this->view->allowed_access = $allowed;
		$this->view->allowed_access_menus = $allowed_menus;
		$this->view->render('forms/access/viewaccessdetail');

	}

	function editpermission($id){

		//$this->view->allowed_access = $this->model->editpermission($id);
		//$this->view->details = $this->model->getAccessLevel($id);

		$this->view->details = $this->model->getAccessDetails($id);
		$this->view->rights = $this->model->getAccessRights();
		$this->view->pagerights = $this->model->getPageAccessRights();

		$details = $this->model->getAccessDetails($id);
		$allowed = explode(',', $details[0]['allowed_access']);
		$allowed_menus = explode(',', $details[0]['allowed_access_menu']);

		$this->view->allowed_access = $allowed;
		$this->view->allowed_access_menus = $allowed_menus;
		$this->view->render('forms/access/editpermission');
	}

	function updatepermission($id){
		$this->view->allowed_access = $this->model->UpdatePermission($_POST, $id);
	}

	function deletepermission($id){
		$this->model->DeletePermission($id);
	}

	/************** THIRD PARTY ACOOUNTS ************/

	function viewthirdparties(){
		$this->view->parties = $this->model->getThirdPartyAccounts();
		$this->view->render('forms/access/thirdparty');
	}

	function newthirdparty(){
		$this->view->render('forms/access/newthirdparty');
	}

	function addthirdparty(){
		$this->model->InsertThirdParty($_POST);
	}

	function editparty($id){
		$this->view->details = $this->model->getPartyDetails($id);
		$this->view->render('forms/access/editthirdparty');
	}

	function updatethirdparty($id){
		$this->model->UpdateThirdParty($_POST, $id);
	}

	function deleteparty($id){
		$this->model->DeleteThirdParty($id);
	}

	function viewpartydetails($id){
		$this->view->details = $this->model->getPartyDetails($id);
		$this->view->render('forms/access/viewpartydetails');
	}

}