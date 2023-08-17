<?php

class Clients extends Controller{

	public function __construct(){
		parent::__construct();
		Auth::handleSignin();
		Auth::CheckSession();
		Auth::CheckAuthorization();
		$_SESSION['timeout'] = time(); 
	}

	function index(){
		$this->view->branches = $this->model->checkForBranches($_SESSION['office']);
		$this->view->Members = $this->model->getAllMembers();
		$this->view->render('forms/clients/viewclients');
	}


	function missing(){
		$this->view->Members = $this->model->getMembersMissingRequirements();
		$this->view->render('forms/clients/viewclients');
	}
	

	function pending(){
		$this->view->Members = $this->model->getMembersPendingApproval();
		$this->view->render('forms/clients/viewclients');
	}
	
	function inquiry(){
	    $this->view->Members = $this->model->getMembersPendingApproval();
		$this->view->render('forms/clients/inquiry');

	}

	function request($id, $pic){
		$this->model->processRequest($id, $pic);
	}

	function loanstatement($id){
		$this->view->mem_id = $id;
		$this->view->loans = $this->model->getLoanDetails($id);
		$this->view->render('forms/clients/viewloanstatement');
	}

function info(){
        $id = $_POST['accountno'];
        
        //commented out because the account number is now the c_id as regards to ABC
        //$member_details = $this->model->getClientPhone($id);
        //$id = $member_details[0]['c_id']; 
        
		header('Location: '.URL.'clients/details/'.$id);
	}
	
	function details($id){
		if(!empty($id)){ 

			$this->view->member_id=$id;
			$member_details = $this->model->getclient($id);
			$this->view->members = $member_details;
			$this->view->clic_world_details = $this->model->getStellarAddress($member_details[0]['mobile_no']);
			$this->view->stellar_details = $this->model->getStellarDetails($member_details[0]['mobile_no']);
			$this->view->clientsavings = $this->model->getClientSavingsdDetails($id);	
			$this->view->clientinsurances = $this->model->getClientInsurancedDetails($id);	
			$this->view->loans = $this->model->getMemberLoans($id);	
			$this->view->shares = $this->model->getMemberShares($id);	
			$this->view->age = $this->model->getClientAge($id);

			$this->view->members[0]['new_image'] = $this->base64ToImage($this->model->getClientImage($id));
			$this->view->members[0]['new_id_passport'] = $this->base64ToImage($this->model->getClientPassport($id));
			$this->view->members[0]['new_signature'] = $this->base64ToImage($this->model->getClientSignature($id));

			$path = "public/images/avatar/". $_SESSION['username'] . ".txt" ;

			$txt = ":" . $this->view->members[0]['new_image'] . ":" . $this->view->members[0]['new_id_passport'] . ":". $this->view->members[0]['new_signature'] . ":";
			file_put_contents($path, $txt.PHP_EOL , FILE_APPEND | LOCK_EX);

			$this->view->wallet= $this->model->getClientWalletdDetails($id);	

			if($this->view->members[0]['status']=='Active'||$this->view->members[0]['status']=='Closed'){
				$this->view->render('forms/clients/memberdetails');
			}elseif (!empty($this->view->members[0]['company_name'])) {
				$this->view->render('forms/clients/companydetails');
			}else{
				header('Location: '.URL.'members/statusone/'.$id);
			}

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

	function approve($id){
		if(!empty($id)){	
		$this->model->ApproveBusiness($id);	
		}else{
					
		}
	}

	function createaccounts($id){	
		$this->model->CreateAccounts($id);
	}
}