<?php

class Equity extends Controller{
	
public function __construct(){
parent::__construct();
Auth::handleSignin();
Auth::CheckSession();
Auth::CheckAuthorization();
 $_SESSION['timeout'] = time(); 

}
function index(){
	
$this->view->shareholders = $this->model->ShareHoldersLists();
$this->view->render('forms/shares/shares_account');
}
/* share capital    */
//shares
function sharesaccount(){	
$this->view->shareholders = $this->model->ShareHoldersLists();	
$this->view->render('forms/shares/shares_account');

}
function shareholdersinfo(){	
$this->view->render('forms/shares/shareholdersinfo');

}
function preparememberinfo($acc){
$this->model->preparememberinfo($acc);
	
}
function newshareapplication($id=null){
	if($id != null){
		$this->view->memberid = $id;
	} else {
		$this->view->memberid = null;
	}
	
   $this->view->render('forms/shares/newshareapplication');	
}

function getshareProductstoapply($id){
	$this->model->getshareProductstoapply($id);
	
}
function getshareproduct($id){
 
   $this->model->getshareproduct($id);	

}
function submitshareapplication(){
 $data=$_POST;
   $this->model->submitshareapplication($data);	

}

function buyshares($acc=null){
$this->view->acc_id=$acc;
$this->view->render('forms/shares/buyshares');


}
function addshares(){
$data=$_POST;
$this->model->addshares($data);	

}
function transfer($acc=null){
$this->view->acc_id=$acc;
$this->view->render('forms/shares/transfershares');


}
function sellshares(){
$data=$_POST;
$this->model->sellshares($data);	

}
function createShareHolder(){
	$data =  $_POST;
	$this->model->createShareHolder($data);

 
}
function shareholdersstatement(){

	$this->view->render('forms/shares/shareholdersstatementform');

 
}
function ShareHolderDetails($accno=null, $acc=null){

	if ($acc!=null) {
		$this->view->savingsAcc= $acc;
	}
if(empty($accno)){
$acc=$_POST['account_no'];
	
		
}else{
$acc=$accno;	
}
    $this->view->shareholder=$this->model->getShareHolder($acc);
    $this->view->transactions=$this->model->getShareTransactions($acc);
	$this->view->render('forms/shares/shareholderdetails');

 
}
function getshareaccount($acc){
   $this->model->getshareaccount($acc);

}


function UpdateShareHolder(){
    $data =  $_POST;
	$this->model->UpdateShareHolder($data );
 
}
function closeaccount($acc=null){
if($acc!=null){
$this->view->acc_id=$acc;	
$this->view->acc_holder=$this->model->getShareHolder($acc);
}
	$this->view->render('forms/shares/closeaccount');
 
}
function DeleteAccount(){
	$data=$_POST;
	$this->model->DeleteAccount($data);
 
}
function getsharestransaction($acc,$transno,$tdate=null){
$this->model->getsharestransaction($acc,$transno,$tdate);
}

function getpendingtransaction($acc,$transno){
$this->model->getpendingtransaction($acc,$transno);
}
function confirmpendingtransaction($acc=null){
	if($acc!=null){
    $this->view->account=$acc;
	}	
	$this->view->render('forms/shares/confirmpendingtransaction');
}
function confirmtransaction(){
	$data=$_POST;
$this->model->confirmtransaction($data);
}
function reversesamedaytransaction($desc=null){
	if($desc!=null){
    $this->view->authorisor=$desc;
	}

	$this->view->render('forms/shares/reversesamedaytransaction');
}


function reversesharestransaction(){
	$data=$_POST;
$this->model->reversesharestransaction($data);
}	
function getshareaccountclosed($acc){
$this->model->getshareaccountclosed($acc);
}
function reopensharesaccount(){

	$this->view->render('forms/shares/openclosed_shareholder');
}
function OpenclosedShareHolder(){
	$data=$_POST['accno'];
$this->model->OpenclosedShareHolder($data);
}
function getmembersimage($acc){

$this->model->getmembersimage($acc);

}
function getmembersharesimage($acc){

$this->model->getmembersharesimage($acc);
}
function getmembersharesimager($acc){

$this->model->getmembersharesimager($acc);
}

//dividend posting
function postdividendsform(){
	
	$this->view->render('forms/shares/postdividendsform');

}

 function importbulk() {
$this->view->render('forms/shares/import_bulk');  
  }


   function processbulkImport() {
        $data = array();
        $data['audit_file_temp'] = $_FILES['file_name']['tmp_name'];
        $data['audit_file_type'] = $_FILES['file_name']['type'];

        $this->model->ImportBulk($data);
        header("Location:".URL ."equity");
    }



}