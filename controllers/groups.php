<?php
error_reporting(E_ALL);
require __DIR__ . '/../vendor/autoload.php';
class groups extends Controller{

	public function __construct(){
		parent::__construct();
		Auth::handleSignin();
		Auth::CheckSession();
		Auth::CheckAuthorization();
		$_SESSION['timeout'] = time(); 
	}

	public function index(){
		$this->view->groups = $this->model->GetAllGroups();
		$this->view->render('forms/groups/viewgroups');
	}


	public function addmembersgroup($id){
		$this->view->details = $this->model->GetGroupDetails($id);
		$this->view->members = $this->model->GetGroupMembers($id);
		$this->view->render('forms/groups/newmembergroup');
	}

	public function updateMemberLoanAmount(){
		$this->model->updateMemberLoanAmount();
	}
	
	public function newgrouploanproduct($id){
		$this->view->group_id = $id;
	    $this->group_loan = $this->model->getGroupLoanProduct($id);
		$this->view->details = $this->model->GetGroupDetails($id);
		$this->view->render('forms/groups/newgrouploanproduct');
	}

	public function newgroup(){
		$this->view->render('forms/groups/newgroup');
	}
	
	public function uploadgroup(){
	    $this->view->render('forms/groups/uploadgroup');
	}
	
	public function verifycsv(){
		$this->model->verifycsv($_POST['file_path']);
	}
	
	
	public function bulkupload() {
		$data = array();
		$data['audit_file_temp'] = $_FILES['file_name']['tmp_name'];
		$data['audit_file_type'] = $_FILES['file_name']['type'];
		if($data['audit_file_type'] == 'text/csv'){
		   $this->model->importBulkGrp($data);
		} else {
			header("Location:". URL ."groups/uploadgroup?msg=invalid");
			exit();
		}
	}

	public function processgroup(){
	    
		$this->model->processregcsv($_POST['file_path']);
	}

	public function viewgroup($id){
	    
	    $this->group_loan = $this->model->getGroupLoanProduct($id);
		$this->view->details = $this->model->GetGroupDetails($id);
		$this->view->members = $this->model->GetGroupMembers($id);
		$grp_details = $this->model->GetGroupLoanDetails($id);

		if (!empty($grp_details)) {
			$this->view->details[0]['loan_account'] = $grp_details[0]['account_no'];
		}
		$this->view->render('forms/groups/viewgroupdetails');
	}

	public function editgroup($id){
		$this->view->details = $this->model->GetGroupDetails($id);
		$this->view->members = $this->model->GetGroupMembers($id);
		$this->view->render('forms/groups/editgroupdetails');
	}


	public function memberstatus($id, $mem_id, $state){
		$this->model->ChangeMemberStatus($id, $mem_id, $state);
	}

	public function savingsaccountdetails($acc=null, $id=null, $all=null){

		$account=null;
		if($acc!=null){
			$this->view->memberid = $id;
			$account=$acc;	   
		}else{
			$account=$_POST['accno'];   
		}

		$memberzid = $this->model->getAccountGroupID($account);
		$this->view->memberzid = $this->model->getAccountGroupID($account);

		if(!empty($account)){
			$this->view->savingsAcc = $acc;
			$this->view->accountholder = $this->model->SavingsAccountDetails($memberzid);
			$this->view->transactions = $this->model->getSavingsAccountTransactions($account);
			if ($all != NULL) {
				$this->view->alltransactions=$this->model->getAllSavingsAccountTransactions($account);
			}
			$this->view->render('forms/groups/savingsaccountdetails');
		}else{
			header('Location: ' . URL . 'groups');   
			exit();
		}
	}

	public function changestatus($id){
		
		$this->model->ChangeStatus($id,$_POST);
	}

	public function DeleteMember($id,$idg){
		
		$this->model->DeleteGroupMember($id,$idg);
	}

	public function addgroup(){
		$data = $_POST;
    	$rs  = 	$this->model->InsertGroupDetails($data);
    	echo json_encode($rs);
	}

	public function addgroupmember(){
		$data = $_POST;
		$rs =  $this->model->InsertGroupMembers($data);
		echo json_encode($rs);
	}

	public function updategroup($id){
		$this->model->UpdateGroupDetails($id,$_POST);
	}

	public function deletegroup($id){
		$this->model->DeleteGroupDetails($id);
	}


	public function getgrouploan($id){
		$this->model->getgrouploan($id);
	}

	function grouploans(){
		$this->view->loan = $this->model->getSaccoGroupLoans();
		$this->view->render('forms/groups/grouploans');
	}
}