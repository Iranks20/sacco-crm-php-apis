<?php

class Messages extends Controller{

	public function __construct(){
		parent::__construct();
		Auth::handleSignin();
		Auth::CheckSession();
		Auth::CheckAuthorization();
		$_SESSION['timeout'] = time(); 
	}

	function index(){
		$this->view->details = $this->model->getAllMessageTemplates();
		$this->view->render('forms/messages/viewmessagetemplates');
	}
	function viewmessagesdetails($id){
		$this->view->details = $this->model->getMessageDetails($id);
		$this->view->render('forms/messages/viewmessagesdetails');
	}

	function addmessagetemplate(){	
		$this->view->products = $this->model->getProducts();
		$this->view->template_methods = $this->model->getAllMessageTemplateMethods();
		$this->view->render('forms/messages/addmessagetemplate');
	}

	function insertmessagetemplate(){
		$data=$_POST;
   		$this->model->messagetemplate($data);

	}

	public function editmessagetemplate($id){
		$this->view->details = $this->model->getMessageDetails($id);
		//print_r($this->model->getMessageDetails($id));
		//die();
		$this->view->render('forms/messages/editmessagetemplate');
	}

	function updatemessagetemplate($id){
		$data=$_POST;
   		$this->model->updatemessagetemplate($data,$id);

	}
}

?>