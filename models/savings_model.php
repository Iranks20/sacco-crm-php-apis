<?php



class Savings_model extends Model{

public function __construct(){

parent::__construct();

 	@session_start();
 	$this->logUserActivity(NULL);
	if (!$this->checkTransactionStatus()) {
		header('Location: ' . URL); 
	}
}


function getMemberShares($id){

return $this->db->SelectData("SELECT * FROM share_account s INNER JOIN  share_products p WHERE s.product_id=p.id AND s.member_id='".$id."'");

}

function getEmployees() {
    try {
        return $this->db->SelectData("SELECT * FROM m_staff");

    } catch (Exception $e) {
        throw new Exception("Unknown error: " . $e->getMessage());
    }
}

function member_details($actno){

 	$rset=array();
	$result=  $this->db->selectData("SELECT * FROM m_savings_account WHERE account_no='".$actno."' and account_status='Active' ");

	if(count($result)>0){
	$cid=$result[0]['member_id'];
	$results= $this->db->selectData("SELECT * FROM members WHERE c_id='".$cid."' ");
		if(!empty($results[0]['company_name'])){
		$displayname=$results[0]['company_name'];
		  }else{
		$displayname=$results[0]['firstname']." ".$results[0]['middlename']." ".$results[0]['lastname'];
		  }
		array_push($rset,array(
		'signature'=>$results[0]['signature'],
		'displayname'=>$displayname,
		));
echo json_encode(array("result" =>$rset));
die();		
	
}
}

function getAccountNo($cid){

$result= $this->db->selectData("SELECT min(account_no) as account FROM m_savings_account WHERE member_id='".$cid."' ");

return $result[0]['account'];

}
function getAccountName($id){

$result= $this->db->selectData("SELECT member_id FROM m_savings_account WHERE account_no='".$id."' and account_status='Active' || account_status='Open' ");
if(count($result)>0){
	$cid=$result[0]['member_id'];
	$results= $this->db->selectData("SELECT * FROM members WHERE c_id='".$cid."' ");

	echo $results[0]['firstname']." ".$results[0]['middlename']." ".$results[0]['lastname'] ;
	die();
	
}
}
function getMemberName($id){

$result= $this->db->selectData("SELECT * FROM members WHERE c_id='".$id."' ");
if(!empty($result[0]['company_name'])){
	$name = $result[0]['company_name'];
}else{
	$name = $result[0]['firstname']." ".$result[0]['middlename']." ".$result[0]['lastname'] ;
}
	return $name;



}
function getAccountfromSavings($id){

$result= $this->db->selectData("SELECT * FROM m_savings_account WHERE id='".$id."' ");

return $result;

}

function getsavingsProductcharge($savings_product_id) {

   
   $query= $this->db->SelectData("SELECT * FROM m_savings_product_charge sc JOIN m_charge mc 

	  where sc.charge_id =mc.id  and sc.savings_product_id ='".$savings_product_id."'  and mc.charge_applies_to =2 order by sc.charge_id desc");

   
   return $query;
 	

}
function getsavingsProductcharge_application() {

   
   $query= $this->db->SelectData("SELECT * FROM m_savings_product_charge sc JOIN m_charge mc 

	  where sc.charge_id =mc.id  and mc.charge_applies_to =2 order by sc.charge_id desc");

   
   return $query;
 	

}

function getSavingsProducts(){

   return $this->db->SelectData("SELECT * FROM m_savings_product where product_status ='Active' ");


}


function getSavingsProduct($id){


   return $this->db->SelectData("SELECT * FROM m_savings_product WHERE id='".$id."'");

 	

}
function getSavings_Product($id){


return $this->db->SelectData("SELECT * FROM m_savings_product WHERE id='".$id."'");

}

function getSavingsProducttoapply($id){

  $client_pdt= $this->db->SelectData("SELECT DISTINCT(product_id) AS product_id FROM  m_savings_account where member_id='".$id."' and account_status!='Activate' and account_status!='Domant' ");

		$no =count($client_pdt);

		$add=null;
		if($no>0){
			
		for($i=0;$i<$no;$i++){
				$add .= " AND id!='".$client_pdt[$i]["product_id"]."'";		
		}

		$pdts= $this->db->SelectData("SELECT * FROM m_savings_product where product_status='Active' $add");
		if(count($pdts)>0){
	$option = '<option value="">---Select Product---</option>';
	  foreach ($pdts as $key => $value) {
      $option .= '<option value="' . $value['id'] . '">' . $value['name'] . '</option>';
        }
print_r($option);
die();
		}else{
		
		}

		}else{
		$pdts= $this->db->SelectData("SELECT * FROM m_savings_product where product_status='Active'");
			
		if(count($pdts)>0){
	$option = '<option value="">---Select Product---</option>';
	  foreach ($pdts as $key => $value) {
      $option .= '<option value="' . $value['id'] . '">' . $value['name'] . '</option>';
        }
print_r($option);
die();
		}else{
		
		}		}

}


function getClientDetails($id){

   return $this->db->SelectData("SELECT * FROM members c INNER JOIN m_branch b where c.office_id  = b.id AND c.c_id='".$id."'  order by c.c_id desc");

}

function member_infom($id){

   $result = $this->db->SelectData("SELECT * FROM members c INNER JOIN m_branch b where c.office_id  = b.id AND c.c_id='".$id."'  order by c.c_id desc");
 $displayname=null;
 if(count($result)>0){
	 if(!empty($result[0]['company_name'])){
	$displayname=$result[0]['company_name'];	 
	 }else{
	$displayname=$result[0]['firstname']." ".$result[0]['middlename']." ".$result[0]['lastname'];
	 }
	
	$rset=array();
	foreach ($result as $key => $value) {
		array_push($rset,array(
		'member_id'=>$result[$key]['c_id'],
		'displayname'=>$displayname,
		'dob'=>$result[0]['date_of_birth'],
		'national_id'=>$result[0]['national_id'],
		'address'=>$result[0]['address'],
				
		));
          }

print_r( json_encode(array("result" =>$rset)));
die();
}else{
	
	echo "No results";
	die();
}
 
}


function getClientSaving($id){

 return $this->db->SelectData("SELECT * FROM m_savings_account where member_id='".$id."' ");

 }



function getClientSavingsdDetails($id){

return $this->db->SelectData("SELECT p.name,member_id, running_balance as amount, s.id ,account_no FROM m_savings_product p INNER JOIN m_savings_account s

where s.product_id = p.id and  s.member_id='".$id."' order by s.id DESC");

}

function ClientSavingsdDetailsSearch($id){

$result = $this->db->SelectData("SELECT p.name,member_id, running_balance as amount, s.id ,account_no FROM m_savings_product p INNER JOIN m_savings_account s

where s.product_id = p.id and  s.member_id='".$id."' order by s.id DESC");

 if(count($result)>0){
  	$rset=array();
	foreach ($result as $key => $value) {
		array_push($rset,array(
		'savings_account'=>$result[$key]['account_no'],
		'savings_name'=>$result[$key]['name'],
		));
          }

echo json_encode(array("result" =>$rset));
die();
	

}


}


function transactiondetails($id){

 return $this->db->SelectData("SELECT * FROM m_savings_account_transaction where 

savings_account_id='".$id."' ");

	

 }

function getCharges($id){

	return $this->db->selectData("SELECT * FROM m_charge where charge_applies_to = '".$id."' order by id ");

}

function submitapplication($data, $office_id) {
    try {
        $client_details = $this->getMember($data['cid']);
        $str = date('isH') . rand();
        $acc_no = $office_id . substr($str, 0, 7);

        if (empty($client_details[0]['company_name'])) {
            $name = $client_details[0]['firstname'] . " " . $client_details[0]['middlename'] . " " . $client_details[0]['lastname'];
        } else {
            $name = $client_details[0]['company_name'];
        }

        $this->db->beginTransaction();

        $postData = [
            'account_name' => $name,
            'account_no' => $acc_no,
            'member_id' => $data['cid'],
            'submittedon_userid' => $_SESSION['user_id'],
            'office_id' => $office_id,
            'account_status' => 'Active',
            'product_id' => $data['product_id'],
        ];

        $clientsaving_id = $this->db->InsertData('m_savings_account', $postData);

        $this->db->commit();

        return ["clientsaving_id" => $clientsaving_id];

    } catch (Exception $e) {
        $this->db->rollBack();
        throw $e;
    }
}




function makeSavingsJournalEntry($acc_id,$office,$user,$savings_trans_id,$trans_id,$amount,$type,$side,$description){
		$postData = array(
			'account_id' =>$acc_id,
			'office_id' => $office,
			'office_id' => $_SESSION['branchid'],
			'createdby_id' =>$user,
			'savings_transaction_id' =>$savings_trans_id,
			'transaction_id' =>$trans_id,
			'amount' => $amount,
           	'transaction_type' =>$type,
            'trial_balance_side' =>$side,							
            'description' =>$description,							
        );

 
 $this->db->InsertData('acc_gl_journal_entry', $postData);	
	
}

function updatesavingsApplication($data){

$acc = $data['account_no'];

		
        $m_savings_postData = array(
             'product_id' =>$data['product_id'],

        );
	       
	

		$this->db->UpdateData('m_savings_account', $m_savings_postData,"`account_no` = '{$acc}'");



  header('Location: ' . URL . 'members/modifysavings/?acc'.$acc.'&msg=updated'); 

}

function  GetPayment($id){
 $result= $this->db->SelectData("SELECT * FROM m_savings_account_transaction where id='".$id."' ");
	$account_details = $this->getClientAccount($result[0]['savings_account_no']);
$currency=$this->db->SelectData("SELECT * FROM m_currency");
	$client_details = $this->getMember($account_details[0]['member_id']);
	//print_r($result);
	//die();
	foreach ($result as $key => $value) {
                $rset[$key]['account_name'] = $client_details[0]['firstname']." ".$client_details[0]['middlename']." ".$client_details[0]['lastname']; 
                $rset[$key]['account_number'] = $account_details[0]['account_no']; 
				$rset[$key]['transaction_date'] = $result[$key]['transaction_date'];
				$rset[$key]['amount_deposited'] = $result[$key]['amount'];
				$rset[$key]['currency'] = $currency[0]['code'];
				$rset[$key]['new_balance'] = $result[$key]['running_balance'];
          }
        return $rset;
		
 }
function getClientAccount($id){

 return $this->db->SelectData("SELECT * FROM m_savings_account where account_no='".$id."' ");

 }

function getClientSaveddetails($acc){

return $this->db->SelectData("SELECT * FROM m_savings_account s INNER JOIN  members c WHERE s.member_id=c.c_id AND s.account_no='".$acc."'");

}
 function getClientSaveddetailsid($id){

 return $this->db->SelectData("SELECT * FROM m_savings_account where id='".$id."' ");

 }

function postsavingtransaction($data,$new_balance,$type){
	$name=null;

	if($type=='Withdraw'){
		$name=$data['name'];	
	}else{
		$name=$data['depositor'];		
	}
	
	if($type == "deposit"){
	    $optype = "CR";
	}else{
        $optype = "DR";
	}
	
	$data['amount_in_words']=$this->convertNumber($data['amount']);

	$transaction_postData = array(
		'savings_account_no' => $data['accountno'],
		'payment_detail_id' =>  $data['trans_type'],
		'amount' =>str_replace(",","",$data['amount']),
		'running_balance' => $new_balance,
		'depositor_name' => $name,
		"op_type"=>$optype,
		'amount_in_words' => $data['amount_in_words'],
		'telephone_no' => $data['tel'],
		'branch' =>$_SESSION['office'],
		'transaction_type' =>$type,
		'transaction_id' => "S". uniqid(),
		'user_id' => $_SESSION['user_id'],
	);

	$transaction_id = $this->db->InsertData('m_savings_account_transaction', $transaction_postData);

	return 	$transaction_id;
}

function depositaccount($data){
	
$this->db->beginTransaction();//beginning transaction
$update_time=date('Y-m-d H:i:s');
$acc=$data['accountno'];
$result= $this->db->selectData("SELECT * FROM m_savings_account WHERE account_no='".$acc."' ");

$amount = str_replace(",","",$data['amount']);
$balance = $result[0]['running_balance'];
$availabledeposit = $result[0]['total_deposits'];

$new_total_deposits = $availabledeposit + $amount ;
$new_balance = $amount + $balance ;
		$office_id =  $_SESSION['office'];
		
$prodType=3;

	$mapping = $this->GetGLPointers($result[0]['product_id'],$prodType,'Deposit on Savings');

if(!empty($mapping[0]["debit_account"])&&!empty($mapping[0]["credit_account"])){

try{
$deposit_transaction_id=$this->postsavingtransaction($data,$new_balance,'Deposit');



//$deposit_transaction_id = $this->db->InsertData('m_savings_account_transaction', $transaction_postData);

 $depositstatus = array(
                    'total_deposits' => $new_total_deposits,
                    'running_balance' => $new_balance,
                    'last_updated_on' =>$update_time,
					);

	$this->db->UpdateData('m_savings_account', $depositstatus,"`account_no` = '{$acc}'");

$transaction_uniqid=uniqid();

$deposit_transaction_uniqid = $deposit_transaction_id."".$transaction_uniqid;

$transaction_id = "S".$deposit_transaction_uniqid;
		$debt_id =$mapping[0]["debit_account"]; //debit savings  Control account
		$credit_id =$mapping[0]["credit_account"]; //credit cash savings reference	
		$sideA=$this->getAccountSide($debt_id);
		$sideB=$this->getAccountSide($credit_id);
		///JOURNAL ENTRY POSTINGS
		$client = $this->getClientSaveddetails($acc);
		$name=null;
		if(empty($client[0]['company_name'])){
		$name=$client[0]['firstname']." ".$client[0]['middlename']." ".$client[0]['lastname'];	
		}else{
		$name=$client[0]['company_name'];	
		}
		$description="Savings Deposit for ".$name;
$this->makeSavingsJournalEntry($debt_id,$office_id,$_SESSION['user_id'],$deposit_transaction_id,$transaction_id,str_replace(",","",$data['amount']),'DR',$sideA,$description);//DR
$this->makeSavingsJournalEntry($credit_id,$office_id,$_SESSION['user_id'],$deposit_transaction_id,$transaction_id,str_replace(",","",$data['amount']),'CR',$sideB,$description);//CR

$this->db->commit();

header('Location:'.URL.'savings/newsaving/'.$deposit_transaction_id.'?msg=receipt');
 
 }catch(Exception $e){
	  $this->db->rollBack();
$error=$e->getMessage();
header('Location:'.URL.'savings/newsaving?msg=fail&error='.$error);
exit(); 	  
  }	

}else{

header('Location:'.URL.'savings/newsaving?msg=fail');


}
}

function withdrawaccount($data){


$this->db->beginTransaction();//beginning transaction
$charge=0;
$update_time=date('Y-m-d H:i:s');	
$acc=stripslashes($data['accountno']);
$result= $this->db->selectData("SELECT * FROM m_savings_account WHERE account_no='".$acc."' ");
$product=  $this->db->selectData("SELECT * FROM m_savings_product WHERE id='".$result[0]['product_id']."'");
$product_charges=  $this->db->selectData("SELECT * FROM m_savings_product_charge WHERE savings_product_id='".$result[0]['product_id']."'");

$amount = str_replace(",","",$data['amount']);
$balance = $result[0]['running_balance'];
$availablewithdraw = $result[0]['total_withdrawals'];

$new_total_withdraws = $availablewithdraw + $amount ;

$office_id =  $_SESSION['office'];

if(count($product_charges)>0){
foreach ($product_charges as $key => $value){ 
    $charges=$this->getchargeDetails($value['charge_id'],'Saving');

	if(count($charges>0)){
foreach ($charges as $key => $values){ 		
$charge =$charge + $values['amount'];

}
	}			
	}
}
$new_balance =  $balance-($amount+$charge);
$actualbalance=$new_balance;
$min_balance = $product[0]['min_required_balance'];

if($actualbalance>=$min_balance){
					
$prodType=3;
	$mapping = $this->GetGLPointers($result[0]['product_id'],$prodType,'Withdraw on Savings');

	$charges_mappings = $this->GetGLPointers($result[0]['product_id'],$prodType,'Charges On savings Income');
//$charges_mappings=$this->db->SelectData("SELECT * FROM  acc_gl_pointers JOIN transaction_type ON transaction_type.transaction_type_id=acc_gl_pointers.transaction_type_id where product_id='".$result[0]['product_id']."' AND transaction_type.product_type='".$prodType."' AND transaction_type.transaction_type_name='Charges On savings Income' ");
	
  if(!empty($mapping[0]["debit_account"])&&!empty($mapping[0]["credit_account"])){
try{
	//withdraw

		$client = $this->getClientSaveddetails($acc);
		$name=null;
		if(empty($client[0]['company_name'])){
		$name=$client[0]['firstname']." ".$client[0]['middlename']." ".$client[0]['lastname'];	
		}else{
		$name=$client[0]['company_name'];	
		}
     $data['name']=$name;
     $withdraw_transaction_id=$this->postsavingtransaction($data,$actualbalance,'Withdraw');		

	        $withdrawstatus = array(
                    'total_withdrawals' => $new_total_withdraws,
                    'running_balance' => $actualbalance,
                    'last_updated_on' =>$update_time,
						);

		$this->db->UpdateData('m_savings_account', $withdrawstatus,"`account_no` = '{$acc}'");

$transaction_uniqid=uniqid();

$deposit_transaction_uniqid = $withdraw_transaction_id."".$transaction_uniqid;

$transaction_id = "S".$deposit_transaction_uniqid;
		$debt_id =$mapping[0]["debit_account"]; //debit cash savings reference	
		$credit_id =$mapping[0]["credit_account"]; //credit savings  Control account

		$sideA=$this->getAccountSide($debt_id);
		$sideB=$this->getAccountSide($credit_id);
$description="Savings Withdraw by ".$name;

$this->makeSavingsJournalEntry($debt_id,$office_id,$_SESSION['user_id'],$withdraw_transaction_id,$transaction_id,$amount,'DR',$sideA,$description);//DR
$this->makeSavingsJournalEntry($credit_id,$office_id,$_SESSION['user_id'],$withdraw_transaction_id,$transaction_id,$amount,'CR',$sideB,$description);//CR

if($charge>0&&count($charges_mappings)>0){
		$charge_debt_id =$charges_mappings[0]["debit_account"]; //debit cash savings reference	
		$charge_credit_id =$charges_mappings[0]["credit_account"]; //credit savings  Control account

		$charge_sideA=$this->getAccountSide($charge_debt_id);
		$charge_sideB=$this->getAccountSide($charge_credit_id);	

$this->makeSavingsJournalEntry($charge_debt_id,$office_id,$_SESSION['user_id'],$withdraw_transaction_id,$transaction_id,$charge,'DR',$charge_sideA,$description);//DR
$this->makeSavingsJournalEntry($charge_credit_id,$office_id,$_SESSION['user_id'],$withdraw_transaction_id,$transaction_id,$charge,'CR',$charge_sideB,$description);//CR
	
}
	

$this->db->commit();

header('Location:'.URL.'members/withdraw?msg=success');
 
 }catch(Exception $e){
	  $this->db->rollBack();
$error=$e->getMessage();
header('Location:'.URL.'members/withdraw?msg=transactionfailed&error='.$error);
exit(); 	  
  }	

}else{

header('Location:'.URL.'members/withdraw?msg=transactionfailed');

}

}else{

header('Location:'.URL.'members/withdraw?msg=insuffient funds');

}

}
function getchargeDetails($id,$apply){

$query=$this->db->SelectData("SELECT * FROM m_charge where id='".$id."' AND charge_applies_to='".$apply."' AND charge_type='Charge' ");

return $query;	
}



function paymentType(){

   return $this->db->SelectData("SELECT * FROM payment_mode order by id ");

}		



function searchaccount($data){

$acc=$data['accno'];

$fname=$data['fname'];

$lname=$data['lname'];
$rset=null;	


 $ch= $this->db->selectData("SELECT * FROM m_savings_account WHERE account_no LIKE '%".$acc."%'");	

	if(!empty($ch)&&!empty($acc)){

	foreach ($ch as $key => $value) {		

	$members=$this->getMember($ch[$key]['member_id']);	
  if($members[0]['account_status']==500){
	if(empty($members[0]['company_name'])){
    $rset[$key]['name'] =$members[0]['firstname']." ".$members[0]['middlename']." ".$members[0]['lastname']; 
    }else{
	$rset[$key]['name'] =$search[$key]['company_name'];	
	
	}
	$rset[$key]['accountno'] =$ch[$key]['account_no']; 
	$rset[$key]['key_id'] =$ch[$key]['id']; 
    $rset[$key]['c_id'] =$ch[$key]['member_id']; 
    $rset[$key]['office'] =$members[0]['name']; 
  }else{
$rset=null;	  
  }
	}
	

return  $rset;	

	}else{

		//|| company_name LIKE '%".$fname."%' || lastname LIKE '%".$lname."%' or middlename LIKE '%".$lname."%'  (firstname LIKE '%".$fname."%' or company_name LIKE '%".$fname."%') or 

$search= $this->db->selectData("SELECT * FROM members WHERE (firstname LIKE '%".$fname."%' or company_name LIKE '%".$fname."%') and (lastname LIKE '%".$lname."%' or middlename LIKE '%".$lname."%')  ");	

 if((!empty($search)and !empty($fname)) or (!empty($search)and !empty($lname))){

foreach ($search as $key => $value){		
  if($search[$key]['status']=='Active'){
	$account=$this->getAccount($search[$key]['c_id']);	

	foreach ($account as $key1 => $value){	
    if(empty($search[$key]['company_name'])){
    $rset[$key]['name'] =$search[$key]['firstname']." ".$search[$key]['middlename']." ".$search[$key]['lastname']; 
	}else{
	$rset[$key]['name'] =$search[$key]['company_name'];		
	}
	$rset[$key]['c_id'] =$search[$key]['c_id']; 
	$rset[$key]['accountno'] =$account[$key1]['account_no']; 
    $rset[$key]['key_id'] =$account[$key1]['id'];
    $rset[$key]['office'] =$this->officeName($search[$key]['office_id']); 

	}

}else{
$rset=null;	  
  }	 

} 

return $rset;
 }
	}

}	

function getAccount($cid){

	
return $this->db->selectData("SELECT * FROM m_savings_account WHERE member_id='".$cid."' ");

}

function savingsProductCharges($id) {

   
   $query= $this->db->SelectData("SELECT * FROM m_savings_product_charge mp JOIN m_charge mc 

	  where mp.charge_id =mc.id  and mc.charge_applies_to='Saving'  and mp.savings_product_id ='".$id."' order by mp.charge_id desc");
	$option="";
	if(count($query)>0){
	  foreach ($query as $key => $value) {
      $option .= '<tr><td>'.$value['id'].'</td><td>'.$value['name'].'</td><tr>';
        }
print_r($option);
die();
	
	}else{
	
	}
   return $query;
 	

}



function getsavingproduct($id){


$product= $this->db->selectData("SELECT * FROM m_savings_product WHERE id='".$id."' ");

	if(count($product)>0){
	$rset=array();
	foreach ($product as $key => $value) {
		array_push($rset,array(
		'id'=>$product[0]['id'],
		'name'=>$product[0]['name'],
		'description'=>$product[0]['description'],
		'interest_rate'=>$product[0]['nominal_interest_rate'],
		'interest_cal'=>$product[0]['interest_calculation_method'],
		'interest_post'=>$product[0]['interest_posting_period'],
		'opening_balance'=>$product[0]['min_required_opening_balance'],	
		'min_balance'=>$product[0]['min_required_balance'],	
		'interest_balance'=>$product[0]['minimum_balance_for_interest_calculation'],	
		));
          }

echo json_encode(array("result" =>$rset));
die();
	}
}

function getallSavingsProducttoapply(){

  $option="";
		$pdts= $this->db->SelectData("SELECT * FROM m_savings_product where product_status='Active'");
		if(count($pdts)>0){
	  foreach ($pdts as $key => $value) {
      $option .= '<option value="' . $value['id'] . '">' . $value['name'] . '</option>';
        }
		
print_r($option);
die();
		}		

}

function getMembersavings($acc){
	$office=$_SESSION['office'];
	  $result =  $this->db->SelectData("SELECT * FROM (m_savings_account s JOIN members m ON s.member_id=m.c_id) JOIN m_savings_product p ON s.product_id=p.id  where  account_no='".$acc."' ");
  		if(empty($result[0]['firstname'])){
		$displayname=$result[0]['company_name'];
		  }else{
		$displayname=$result[0]['firstname']." ".$result[0]['middlename']." ".$result[0]['lastname'];
		  }	
if(count($result)>0){
  	$rset=array();
	foreach ($result as $key => $value) {
		array_push($rset,array(
		'member_id'=>$result[$key]['c_id'],
		'displayname'=>$displayname,
		'dob'=>$result[$key]['date_of_birth'],
		'national_id'=>$result[$key]['national_id'],
		'address'=>$result[$key]['address'],
		'product'=>$result[$key]['name'],
		'product_id'=>$result[$key]['product_id'],
		'description'=>$result[$key]['description'],
		'interest_rate'=>$result[$key]['nominal_interest_rate'],
		'interest_post'=>$result[$key]['interest_posting_period'],
		'interest_cal'=>$result[$key]['interest_calculation_method'],
		'min_balance'=>$result[$key]['min_required_balance'],
		'opening_balance'=>$result[$key]['min_required_opening_balance'],
		'days'=>$result[$key]['days_in_year'],
		'status'=>$result[$key]['account_status'],
		));
          }

echo json_encode(array("result" =>$rset));
die();
	
}


}




function deletesavingsaccount($data){
$acc=$data['account_no'];
$date=date('Y-m-d');

		$postData = array(
			'closedon_date' =>$date,
			'closedon_userid' =>$_SESSION['user_id'],
			'closesure_reason' =>$data['reason'],
			'account_status' =>'Closed',
        );

 $this->db->UpdateData('m_savings_account', $postData,"`account_no` = '{$acc}'");
 header('Location: ' . URL . 'members/savingsaccount?closed='.$acc.''); 
	
}

function OpenclosedSavings($acc){

$date=date('Y-m-d');

		$postData = array(
			're_activatedon_date' =>$date,
			're_activatedon_userid' =>$_SESSION['user_id'],
			'account_status' =>'Active',
        );

 $this->db->UpdateData('m_savings_account', $postData,"`account_no` = '{$acc}'");
 header('Location: ' . URL . 'members/reopensavingsaccount?activated='.$acc.''); 
	
}




function getMemberaccount($acc){
	
  $result= $this->db->SelectData("SELECT * FROM m_savings_account JOIN members on m_savings_account.member_id=members.c_id where  m_savings_account.account_no='".$acc."' ");

 if(count($result)>0){
		  foreach ($result as $key => $value) {  
		if(empty($result[$key]['firstname'])){
		$rset[$key]['name'] =$result[$key]['company_name'];  
		  }else{
         $rset[$key]['name'] = $result[$key]['firstname']." ".$result[$key]['middlename']." ".$result[$key]['lastname'];
		  }		  
                $rset[$key]['member'] = $result[$key]['member_id'];
                $rset[$key]['account'] = $result[$key]['account_no'];
				$rset[$key]['office_id'] = $result[$key]['office_id']; 
				$rset[$key]['office'] = $this->getoffice($result[$key]['office_id']); 
                $rset[$key]['amount'] = $result[$key]['running_balance'];
                $rset[$key]['status'] = $result[$key]['account_status'];
                $rset[$key]['image'] = $result[$key]['image'];
			}
        return $rset;
  }
	
}
function getSavingsAccountTransactions($acc){
	$office=$_SESSION['office'];
	  $result =  $this->db->SelectData("SELECT * FROM m_savings_account_transaction where savings_account_no='".$acc."' ");
         if(count($result)>0){
		 foreach ($result as $key => $value) {  
                $rset[$key]['amount'] = $result[$key]['amount'];
                $rset[$key]['transaction_type']=$result[$key]['transaction_type'];
                $rset[$key]['balance'] = $result[$key]['running_balance'];
                $rset[$key]['depositor'] = $result[$key]['depositor_name'];
                $rset[$key]['trans_date'] = $result[$key]['transaction_date'];
			    }
        return $rset;
		 }
	
}

function getsavingtransaction($acc,$transno,$tdate){
//$result=null;
 $today=date('Y-m-d');
if($tdate==null){
	  $result =  $this->db->SelectData("SELECT * FROM m_savings_account_transaction where savings_account_no='".$acc."' and id='".$transno."' and date(transaction_date)='".$today."' ");

	  }else{ 
   $trans_date= date('Y-m-d',strtotime(str_replace('-','/',$tdate)));
 
	 $result =  $this->db->SelectData("SELECT * FROM m_savings_account_transaction where savings_account_no='".$acc."' and id='".$transno."'and date(transaction_date)='".$trans_date."' ");
}
   if(count($result)>0){  
       print_r($result[0]['amount']);
	   die();
	   }
	
}
function getpendingsaving($acc,$transno,$tdate){

	  $result =  $this->db->SelectData("SELECT * FROM m_savings_account_transaction where savings_account_no='".$acc."' and id='".$transno."' and transaction_status='Pending' ");

   if(count($result)>0){  
       print_r($result[0]['amount']);
	   die();
	   }
	
}
function approvesavings($data){

$tansno=$data['tnumber'];
$tansamount=$data['tamount'];
$accno=$data['account_no'];


		$postData = array(
			'approved_by' =>$_SESSION['user_id'],
			'transaction_status' =>'Approved',
        );

 $this->db->UpdateData('m_savings_account_transaction', $postData,"`id` = '{$tansno}'");
 header('Location: ' . URL . 'members/approvependingsavings?trans=approved'); 
	
}
function reversesavings($data){
$this->db->beginTransaction();//beginning transaction

$tansno=$data['tnumber'];
$tansamount=$data['tamount'];
$accno=$data['account_no'];
	  $result =  $this->db->SelectData("SELECT * FROM m_savings_account_transaction where id='".$tansno."' ");
	  $account_b =  $this->db->SelectData("SELECT * FROM m_savings_account where account_no='".$accno."' and account_status='Active' ");
if((count($result)>0)&&(count($account_b)>0)){
	try{
	$tansaction_type=$result[0]['transaction_type'];
	
	$tansaction_amount=$result[0]['amount'];
	
	$account_running_balance=$account_b[0]['running_balance'];
	
	$trans_running_balance=$result[0]['running_balance'];
		$client = $this->getClientSaveddetails($accno);
		$name=null;
		if(empty($client[0]['company_name'])){
		$name=$client[0]['firstname']." ".$client[0]['middlename']." ".$client[0]['lastname'];	
		}else{
		$name=$client[0]['company_name'];	
		}
	
	$mapping = $this->GetGLPointers($result[0]['product_id'],3,'Withdraw on Savings');

//$mapping = $this->db->SelectData("SELECT * FROM acc_gl_pointers where product_id='".$result[0]['product_id']."' AND product_type='SAVING' AND activity_name ='Saving Withdraw'");

	if($tansaction_type=='Deposit'){
	
	$deposits=$account_b[0]['total_deposits'];

	$total_deposits=($deposits-$tansaction_amount);

	$new_acc_balance=($account_running_balance-$tansaction_amount);

	$new_trans_balance=($trans_running_balance-$tansaction_amount);
	 		$postData = array(
			'reversed_by' =>$_SESSION['user_id'],
			'transaction_reversed' =>'Yes',
			'running_balance' =>$new_trans_balance,
        );

 $this->db->UpdateData('m_savings_account_transaction', $postData,"`id` = '{$tansno}'");	
	
	$postDataD = array(
			'total_deposits' =>$total_deposits,
			'running_balance' =>$new_acc_balance,
        );

 $this->db->UpdateData('m_savings_account', $postDataD,"`account_no` = '{$accno}'");

 $debt_id =$mapping[0]["credit_account"]; //debit cash savings reference	
		$credit_id =$mapping[0]["debit_account"]; //credit savings  Control account
		$sideA=$this->getAccountSide($debt_id);
		$sideB=$this->getAccountSide($credit_id);
///JOURNAL ENTRY POSTINGS
		$description="Savings Deposit for ".$name." Reversed";
$this->makeSavingsJournalEntry($debt_id,$office_id,$_SESSION['user_id'],$deposit_transaction_id,$transaction_id,$tansaction_amount,'DR',$sideA,$description);//DR
$this->makeSavingsJournalEntry($credit_id,$office_id,$_SESSION['user_id'],$deposit_transaction_id,$transaction_id,$tansaction_amount,'CR',$sideB,$description);//CR


	}else if($tansaction_type=='Withdraw'){
		
		$withdraws=$account_b[0]['total_withdrawals'];
 
 $new_acc_balance=($account_running_balance+$tansaction_amount);
 
 $new_trans_balance=($trans_running_balance+$tansaction_amount);
        
		$total_withdrawals=($withdraws-$tansaction_amount);
 
 		$postData = array(
			'reversed_by' =>$_SESSION['user_id'],
			'transaction_reversed' =>'Yes',
			'running_balance' =>$new_trans_balance,
        );

 $this->db->UpdateData('m_savings_account_transaction', $postData,"`id` = '{$tansno}'");
		
		$postDataW = array(
			'total_withdrawals' =>$total_withdrawals,
			'running_balance' =>$new_acc_balance,
        );

 $this->db->UpdateData('m_savings_account', $postDataW,"`account_no` = '{$accno}'");

 
 		$debt_id =$mapping[0]["debit_account"]; //debit savings  Control account
		$credit_id =$mapping[0]["credit_account"]; //credit cash savings reference	
		$sideA=$this->getAccountSide($debt_id);
		$sideB=$this->getAccountSide($credit_id);
///JOURNAL ENTRY POSTINGS
		$description="Savings Withdraw for ".$name." Reversed";
$this->makeSavingsJournalEntry($debt_id,$office_id,$_SESSION['user_id'],$deposit_transaction_id,$transaction_id,$tansaction_amount,'DR',$sideA,$description);//DR
$this->makeSavingsJournalEntry($credit_id,$office_id,$_SESSION['user_id'],$deposit_transaction_id,$transaction_id,$tansaction_amount,'CR',$sideB,$description);//CR



}
  
  $this->db->commit();
  if(isset($data['trans_date'])){
 header('Location: ' . URL . 'members/reversesavingstransaction/prev?trans=reversed'); 
  }else{
 header('Location: ' . URL . 'members/reversesavingstransaction?trans=reversed'); 	  
	  
  }
  
  
}catch(Exception $e){
	  $this->db->rollBack();
$error=$e->getMessage();	
  if(isset($data['trans_date'])){
 header('Location: ' . URL . 'members/reversesavingstransaction/prev?trans=failed&error='.$error); 
  }else{
 header('Location: ' . URL . 'members/reversesavingstransaction?trans=failed&error='.$error); 	  
	  
  }		
	
}

}else{
	  if(isset($data['trans_date'])){
 header('Location: ' . URL . 'members/reversesavingstransaction/prev?trans=failed'); 
  }else{
 header('Location: ' . URL . 'members/reversesavingstransaction?trans=failed'); 	  
	  
  }	
}
}
function stopsavingsaccrualinterest($data){

$acc_name=$data['account_name'];
$accno=$data['account_no'];


		$postData = array(
			'interest_stopped_by' =>$_SESSION['user_id'],
			'interest_stopped' =>'Yes',
        );

 $this->db->UpdateData('m_savings_account', $postData,"`account_no` = '{$accno}'");
 header('Location: ' . URL . 'members/stopinterestaccrualsavings?acc='.$accno.'&interest=stopped'); 
	
}

function savingslist($office) {
    try {
        $query = $this->db->SelectData("SELECT * FROM m_savings_account s  JOIN (members m  JOIN m_branch b ON m.office_id=b.id)
        ON  s.member_id  = m.c_id where m.office_id='$office'");

        return $query;

    } catch (Exception $e) {
        throw new Exception("Unknown error: " . $e->getMessage());
    }
}


function getOffice($id){


		$results =  $this->db->SelectData("SELECT * FROM m_branch where id='".$id."'");

return $results;
		}

function getsavingsAccountData($id){
	$actno = $id;
    try {
        $result = $this->db->selectData("SELECT * FROM m_savings_account WHERE account_no='".$actno."'");
        
        if(count($result)>0){
			$product=  $this->db->selectData("SELECT * FROM m_savings_product WHERE id='".$result[0]['product_id']."'");		
			$max_id= $this->db->selectData("SELECT max(id) as id FROM m_savings_account_transaction WHERE savings_account_no='".$actno."'");
			$r_balance= $this->db->selectData("SELECT * FROM m_savings_account_transaction WHERE id='".$max_id[0]['id']."'");
			if(count($r_balance)==0){
			$rbalance=0;
			$actualbalance=0;
			}else{
			$rbalance=($result[0]['running_balance'])-($product[0]['min_required_balance']);
				$actualbalance=$result[0]['running_balance'];	
			}
			$cid=$result[0]['member_id'];
			$client=$this->getMember($cid);
			$rset=array();
			if(!empty($client[0]['company_name'])){
			$displayname=$client[0]['company_name'];	 
			 }else{
			$displayname=$client[0]['firstname']." ".$client[0]['middlename']." ".$client[0]['lastname'];
			 }
			foreach ($result as $key => $value) {
				array_push($rset,array(
				'member_id'=>$result[$key]['member_id'],
				'displayname'=>$displayname,
				'dob'=>date('d-m-Y',strtotime($client[0]['date_of_birth'])),
				'national_id'=>$client[0]['national_id'],
				'address'=>$client[0]['address'],
				'product'=>$product[0]['name'],
				'last_trans_amount'=>$result[$key]['running_balance'],
				'account_opened'=>$result[$key]['submittedon_date'],
				'status'=>$result[$key]['account_status'],
				'actualbalance'=>$actualbalance,		
				'acc_update_date'=>date('M j Y g:i A',strtotime($result[0]['last_updated_on'])),
				'rbalance'=>$rbalance,		
				));
			}
            
            return array("result" => $rset);
        } else {
            $rset = array();
            array_push($rset, array(
                'member_id' => '0',
                'rbalance' => '0',        
            ));
            return array("result" => $rset);
        }
    } catch (Exception $e) {
        throw new Exception("Failed to fetch savings account data: " . $e->getMessage());
    }
}

function getMember($id){

   return $this->db->SelectData("SELECT * FROM members c INNER JOIN m_branch b where c.office_id  = b.id AND c.c_id='".$id."'  order by c.c_id desc");

}

function importBulkSavings($mdata){
       $now = date('d_m_Y');
        $file_name = $_SESSION['office'].'_'.$_SESSION['user_id'] . '_' . $now;
        $dest = 'public/systemlog/member_list/' . $file_name . '.csv';
        move_uploaded_file($mdata['audit_file_temp'], $dest);
        $filerec = file_get_contents($dest);
        $string = str_getcsv($filerec, "\r");
	
        foreach ($string as $key => $value) {	
		 $data = explode(',', $value);
		  $cid=trim($data[0]);
		$client = $this->getMember($cid);
		$name=null;
		if(empty($client[0]['company_name'])){
		$name=$client[0]['firstname']." ".$client[0]['middlename']." ".$client[0]['lastname'];	
		}else{
		$name=$client[0]['company_name'];	
		}
$office_id = $_SESSION['office'];
$str=date('dHis').rand();		
$acc_no= trim($office_id."".$cid."".substr($str,0,7));

	  $postData = array(
            'account_no' => $acc_no,
             'member_id' => $cid,
			 'submittedon_userid' => $_SESSION['user_id'],
             'account_status' =>'Active',
             'product_id' => 1,
			 'office_id' => $_SESSION['office'],
        );
		
		  $postTrans = array(
            'accountno' =>$acc_no,
            'amount' => $data[2],
			'depositor' =>$name,
            'tel' => $client[0]['mobile_no'],
            'trans_type' =>2,
        );
	
   $clientsaving_id= $this->db->InsertData('m_savings_account', $postData);
  $this->completeBulkprocess($postTrans);
}
header('Location:'.URL.'savings');
}

function completeBulkprocess($data){
$update_time=date('Y-m-d H:i:s');
$acc=$data['accountno'];
$result= $this->db->selectData("SELECT * FROM m_savings_account WHERE account_no='".$acc."' ");

$amount = str_replace(",","",$data['amount']);
$balance = $result[0]['running_balance'];
$availabledeposit = $result[0]['total_deposits'];

$new_total_deposits = $availabledeposit + $amount ;
$new_balance = $amount + $balance ;
		$office_id =  $_SESSION['office'];
		
$prodType=3;
	$mapping = $this->GetGLPointers($result[0]['product_id'],$prodType,'Deposit on Savings');

if(!empty($mapping[0]["debit_account"])&&!empty($mapping[0]["credit_account"])){

$deposit_transaction_id=$this->postsavingtransaction($data,$new_balance,'Deposit');



//$deposit_transaction_id = $this->db->InsertData('m_savings_account_transaction', $transaction_postData);

 $depositstatus = array(
                    'total_deposits' => $new_total_deposits,
                    'running_balance' => $new_balance,
                    'last_updated_on' =>$update_time,
					);

	$this->db->UpdateData('m_savings_account', $depositstatus,"`account_no` = '{$acc}'");

$transaction_uniqid=uniqid();

$deposit_transaction_uniqid = $deposit_transaction_id."".$transaction_uniqid;

$transaction_id = "S".$deposit_transaction_uniqid;
		$debt_id =$mapping[0]["debit_account"]; //debit savings  Control account
		$credit_id =$mapping[0]["credit_account"]; //credit cash savings reference	
		$sideA=$this->getAccountSide($debt_id);
		$sideB=$this->getAccountSide($credit_id);
///JOURNAL ENTRY POSTINGS
         
		$description="Savings Deposit for ".$data['depositor'];
$this->makeSavingsJournalEntry($debt_id,$office_id,$_SESSION['user_id'],$deposit_transaction_id,$transaction_id,str_replace(",","",$data['amount']),'DR',$sideA,$description);//DR
$this->makeSavingsJournalEntry($credit_id,$office_id,$_SESSION['user_id'],$deposit_transaction_id,$transaction_id,str_replace(",","",$data['amount']),'CR',$sideB,$description);//CR
	
	

}
}




}