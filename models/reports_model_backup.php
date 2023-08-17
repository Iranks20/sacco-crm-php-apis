<?php

class Reports_model extends Model{
public function __construct(){
parent::__construct();
 
}

////////LOANS///////



function getPendingLoans(){
	$office=$_SESSION['office'];
$query =   $this->db->SelectData("SELECT * FROM m_loan JOIN members on members.c_id=m_loan.member_id where members.office_id='".$office."' AND loan_status ='Pending'");

   return $query;
  
}

function getDisbursedLoans(){
	$office=$_SESSION['office'];
$query =   $this->db->SelectData("SELECT * FROM m_loan JOIN members on members.c_id=m_loan.member_id where members.office_id='".$office."' AND loan_status ='Disbursed'");

   return $query;
  
}

function getApprovedLoans(){
	$office=$_SESSION['office'];
$query =   $this->db->SelectData("SELECT * FROM m_loan JOIN members on members.c_id=m_loan.member_id where members.office_id='".$office."' AND loan_status ='Approved'");

   return $query;
  
}


function memebersList(){
	  $query= $this->db->SelectData("SELECT * FROM members m INNER JOIN m_branch b 
	  where m.office_id  = b.id and office_id='".$_SESSION['office']."' order by m.office_id desc");

   return $query;
  
}

function savingslist(){
	  $query= $this->db->SelectData("SELECT * FROM m_savings_account s  JOIN (members m  JOIN m_branch b ON m.office_id=b.id)
	  ON  s.member_id  = m.c_id where m.office_id='".$_SESSION['office']."'");

   return $query;
  
}
function getSavingsProductAccount($id){
	  $query= $this->db->SelectData("SELECT count(s.product_id) as number,sum(running_balance) as balance FROM m_savings_account s  JOIN (members m  JOIN m_branch b ON m.office_id=b.id)
	  ON  s.member_id  = m.c_id where m.office_id='".$_SESSION['office']."' and s.product_id='".$id."'");

   return $query;
}

function getSavingsByProduct(){
	  $result= $this->db->SelectData("SELECT * FROM m_savings_product");
	 $count=count($result);  	  
	 if($count>0){
               foreach ( $result as $key => $value) {
				  $accounts=$this->getSavingsProductAccount($value['id']); 
                $rset[$key]['product_type'] = $value['name']; 
                $rset[$key]['no_of_accounts'] = $accounts[0]['number']; 
                $rset[$key]['balance_of_account'] = $accounts[0]['balance'];
			   }	  
			 return $rset;	

	 }

  
}
function getSavingsAccountByStatus($status){
	  $query= $this->db->SelectData("SELECT count(s.account_status) as number,sum(running_balance) as balance FROM m_savings_account s  JOIN (members m  JOIN m_branch b ON m.office_id=b.id)
	  ON  s.member_id  = m.c_id where m.office_id='".$_SESSION['office']."' and s.account_status='".$status."'");
	
   return $query;
}

function getSavingsByStatus(){
	 $array = array(
            '0' =>'Active',
            '1' =>'Domant',
            '2' =>'Closed',
            '2' =>'Opened',
        );

	$count=count($array);  	
   //print_r($array);die();
	 if($count>0){
               for($i=0;$i<$count; $i++) {
					
				$status= $this->getSavingsAccountByStatus($array[$i]);
			    $rset[$i]['status'] =$array[$i]; 
                $rset[$i]['no_of_accounts'] = $status[0]['number']; 
                $rset[$i]['balance_of_account'] = $status[0]['balance'];
			   }	  
			 return $rset;	

	 }

  
}

////fixed_deposit
function fixeddepositList(){
	  $query= $this->db->SelectData("SELECT * FROM fixed_deposit_account f JOIN (members m  JOIN m_branch b ON m.office_id=b.id)
	  ON  f.member_id  = m.c_id where m.office_id='".$_SESSION['office']."'");

   return $query;
  
}

function getFixedProductAccount($id){
	  $query= $this->db->SelectData("SELECT count(f.product_id) as number,sum(running_balance) as balance FROM fixed_deposit_account f  JOIN (members m  JOIN m_branch b ON m.office_id=b.id)
	  ON  f.member_id  = m.c_id where m.office_id='".$_SESSION['office']."' and f.product_id='".$id."'");

   return $query;
}

function getFixedByProduct(){
	  $result= $this->db->SelectData("SELECT * FROM fixed_deposit_product");
	 $count=count($result);  	  
	 if($count>0){
               foreach ( $result as $key => $value) {
				  $accounts=$this->getFixedProductAccount($value['id']); 
                $rset[$key]['product_type'] = $value['name']; 
                $rset[$key]['no_of_accounts'] = $accounts[0]['number']; 
                $rset[$key]['balance_of_account'] = $accounts[0]['balance'];
			   }	  
			 return $rset;	

	 }

  
}
function getFixedAccountByStatus($status){
	  $query= $this->db->SelectData("SELECT count(f.account_status) as number,sum(running_balance) as balance FROM fixed_deposit_account f  JOIN (members m  JOIN m_branch b ON m.office_id=b.id)
	  ON  f.member_id  = m.c_id where m.office_id='".$_SESSION['office']."' and f.account_status='".$status."'");
	
   return $query;
}

function getFixedByStatus(){
	 $array = array(
            '0' =>'Active',
            '1' =>'Closed',
        );

	$count=count($array);  	
   //print_r($array);die();
	 if($count>0){
               for($i=0;$i<$count; $i++) {
					
				$status= $this->getFixedAccountByStatus($array[$i]);
			    $rset[$i]['status'] =$array[$i]; 
                $rset[$i]['no_of_accounts'] = $status[0]['number']; 
                $rset[$i]['balance_of_account'] = $status[0]['balance'];
			   }	  
			 return $rset;	

	 }

  
}


////share_accounts
function ShareHoldersLists(){
	
  $result= $this->db->SelectData("SELECT * FROM share_account JOIN members on share_account.member_id=members.c_id where members.office_id='".$_SESSION['office']."' ");
		 $count=count($result);
		
	 if($count>0){
               foreach ( $result as $key => $value) {
				  $office=$this->getbranches($value['office_id']); 
                $rset[$key]['member'] = $value['c_id']; 
                $rset[$key]['account_no'] = $value['share_account_no']; 
				$rset[$key]['office_id'] = $value['office_id']; 
				$rset[$key]['office'] =$office[0]['name'];
                $rset[$key]['shares'] = $result[$key]['total_shares'];
                $rset[$key]['amount'] = $result[$key]['running_balance'];
                $rset[$key]['opened'] = $result[$key]['submittedon_date'];
                $rset[$key]['last_updated_on'] = $result[$key]['last_updated_on'];
                $rset[$key]['status'] = $result[$key]['account_status'];
				if(empty($value['firstname'])){
                $rset[$key]['name'] = $value['company_name'];
					
				}else{
                $rset[$key]['name'] = $value['firstname']." ".$value['middlename']." ".$value['lastname'];
				}
				 }
							  
			 return $rset;	

	 }
}

function getSharesProductAccount($id){
	  $query= $this->db->SelectData("SELECT count(s.product_id) as number,sum(running_balance) as balance FROM share_account s JOIN (members m  JOIN m_branch b ON m.office_id=b.id)
	  ON  s.member_id  = m.c_id where m.office_id='".$_SESSION['office']."' and s.product_id='".$id."'");

   return $query;
}

function getSharesByProduct(){
	  $result= $this->db->SelectData("SELECT * FROM share_products");
	 $count=count($result);  	  
	 if($count>0){
               foreach ( $result as $key => $value) {
				  $accounts=$this->getSharesProductAccount($value['id']); 
                $rset[$key]['product_type'] = $value['share_name']; 
                $rset[$key]['no_of_accounts'] = $accounts[0]['number']; 
                $rset[$key]['balance_of_account'] = $accounts[0]['balance'];
			   }	  
			 return $rset;	

	 }

  
}
function getShareAccountByStatus($status){
	  $query= $this->db->SelectData("SELECT count(s.account_status) as number,sum(running_balance) as balance FROM share_account s  JOIN (members m  JOIN m_branch b ON m.office_id=b.id)
	  ON  s.member_id  = m.c_id where m.office_id='".$_SESSION['office']."' and s.account_status='".$status."'");
	
   return $query;
}

function getSharesByStatus(){
	 $array = array(
            '0' =>'Active',
            '1' =>'Closed',
        );

	$count=count($array);  	
   //print_r($array);die();
	 if($count>0){
               for($i=0;$i<$count; $i++) {
					
				$status= $this->getShareAccountByStatus($array[$i]);
			    $rset[$i]['status'] =$array[$i]; 
                $rset[$i]['no_of_accounts'] = $status[0]['number']; 
                $rset[$i]['balance_of_account'] = $status[0]['balance'];
			   }	  
			 return $rset;	

	 }

  
}



///////////branches
function getbranches($id=null){
	
	if($id==null){
		return $this->db->selectData("SELECT * FROM m_branch");
	
	}else{
		
		return $this->db->selectData("SELECT * FROM m_branch where id='".$id."' ");
	
	}
}
//GENEEAL LEGER Reports_model
	
function getGlaccounts(){
		
	return $this->db->selectData("SELECT * FROM acc_ledger_account order by  gl_code ASC,classification");
}
function getGLreport($data=null){
	$from=date('Y-m-d',strtotime($data['startdon']));
	$to=date('Y-m-d',strtotime($data['endon']));
	$acc_id=$data['glaccount'];
	$office_id=$_SESSION['office'];
if(isset($data)){
  $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$acc_id."' AND office_id='".$office_id."' and DATE(created_date) BETWEEN '".$from."' AND '".$to."' ");
}else{
  $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$acc_id."' AND office_id='".$office_id."' ");
}
 if(count($res)>0){	
 //print_r($res);
//	die();
	$balance=0;
	$debit=0;
	$credit=0;
	
	foreach ($res as $key => $value) {
                //$officename = $this->officeName($result[$key]['id']);
                $rset[$key]['created_date'] = $res[$key]['created_date']; 
                $rset[$key]['account_id'] = $res[$key]['account_id']; 
                $rset[$key]['office_id'] = $res[$key]['office_id']; 
				$rset[$key]['description'] = $res[$key]['description']; 
				$side = $res[$key]['trial_balance_side']; 
				$type = $res[$key]['transaction_type']; 
                $amount = $res[$key]['amount']; 
				if($side=='SIDE_A'){
				if($type=='DR'){
				$balance=$balance+$amount;
			    $debit=$amount ;
                $credit="";
				}else{
				$balance=$balance-$amount; 
			    $credit=$amount ;			
			    $debit="";			
				}				 
				}else{
				if($type=='DR'){
				$balance=$balance-$amount; 
			    $debit=$amount ;						
			    $credit="";				
				}else{
				$balance=$balance+$amount; 
			    $credit=$amount ;						
			    $debit="";						
				}		
					
				}
                $rset[$key]['debit'] =$debit; 
                $rset[$key]['credit'] =$credit; 
                $rset[$key]['balance'] =$balance; 
          }
        return $rset;
}
}
/* CONTAINS ASSETS, EXPENSES,COST OF GOODS SOLD */

	function getGlaccountsA($data=null){
	$from=date('Y-m-d',strtotime($data['startdon']));
	$to=date('Y-m-d',strtotime($data['endon']));
	$office_id=$_SESSION['office'];
	//AND created_date between '".$from."' AND '".$to."'
  $result=$this->db->selectData("SELECT * FROM acc_ledger_account where classification='Assets' || classification='Expenses' order by  gl_code ASC");
 
 if(count($result)>0){	
	foreach ($result as $keys => $value) {
if(isset($data)){		
 $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$result[$keys]['id']."' AND office_id='".$office_id."' and DATE(created_date) between '".$from."' AND '".$to."' ");
}else{
 $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$result[$keys]['id']."' AND office_id='".$office_id."' ");
}
	$counts=count($res);
   if($counts==0){
	 $rset[$keys]['name'] =$result[$keys]['name']; 
	 $rset[$keys]['gl_code'] =$result[$keys]['gl_code']; 
     $rset[$keys]['debit'] ="-"; 	  
	  
  }else{
			$balance=0;

	foreach ($res as $key => $value) {
				$type = $res[$key]['transaction_type']; 
                $amount = $res[$key]['amount']; 
				if($type=='DR'){
				$balance=$balance+$amount;
				}else{
				$balance=$balance-$amount; 
				}				 
          }
	 $rset[$keys]['name'] =$result[$keys]['name']; 
	 $rset[$keys]['gl_code'] =$result[$keys]['gl_code']; 
     $rset[$keys]['debit'] =$balance; 
	}	
	}

 //print_r($rset);
// die();
        return $rset;
	}
}	

	/* CONTAINS Liability, EQUITY OR SHARES,INCOMES */

	function getGlaccountsB($data=null){
		
	$from=date('Y-m-d',strtotime($data['startdon']));
	$to=date('Y-m-d',strtotime($data['endon']));
	$office_id=$_SESSION['office'];
	//AND created_date between '".$from."' AND '".$to."'
  $results=$this->db->selectData("SELECT * FROM acc_ledger_account where classification='Liabilities' || classification='Equity' || classification='Incomes' order by   gl_code ASC");

 $count=count($results);
 if($count>0){
  for($i=0;$i<$count;$i++) {
	
$acc=$results[$i]['id'];
if(isset($data)){
  $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$acc."' AND office_id='".$office_id."' and DATE(created_date) between '".$from."' AND '".$to."' ");	
}else{
  $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$acc."' AND office_id='".$office_id."' ");	
}
  $counts=count($res);
   if($counts==0){
	 $rset[$i]['name'] =$results[$i]['name']; 
	 $rset[$i]['gl_code'] =$results[$i]['gl_code']; 
     $rset[$i]['credit'] ="-"; 	  
	  
  }else{
	$balance=0;
	foreach ($res as $key => $value) {
		
				$type = $res[$key]['transaction_type']; 
                $amount = $res[$key]['amount']; 
				if($type=='DR'){
				$balance=$balance-$amount; 
				}else{
				$balance=$balance+$amount; 
				}
	}				
	 $rset[$i]['name'] =$results[$i]['name']; 
	 $rset[$i]['gl_code'] =$results[$i]['gl_code']; 
     $rset[$i]['credit'] =$balance; 
           
	}
  } 
   //  print_r($rset);
 //die();
        return $rset;
 }
}	

	/* CONTAINS  EQUITY OR SHARES,INCOMES */

	function getIncomeAccounts($data=null){

	$from=date('Y-m-d',strtotime($data['startdon']));
	$to=date('Y-m-d',strtotime($data['endon']));
	$office_id=$_SESSION['office'];
	//AND created_date between '".$from."' AND '".$to."'
  $array=$this->db->selectData("SELECT * FROM acc_ledger_account where 	classification='Incomes' order by gl_code ASC");
  		$counter=count($array);
  if($counter>0){			
	for($t=0;$t<$counter;$t++) {
if(isset($data)){ 	
  $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' and created_date between '".$from."' AND '".$to."' ");
}else{
  $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' ");
}
  $count=count($res);
	 // print_r($count);
		 // die();
  if($count==0){
	 $rset[$t]['name'] =$array[$t]['name']; 
	 $rset[$t]['gl_code'] =$array[$t]['gl_code']; 
     $rset[$t]['balance'] ="-"; 	  
	  
  }else{
 // print_r($count);
		 // die();
	$balance=0;
	foreach ($res as $key => $value) {
		
				$type = $res[$key]['transaction_type']; 
                $amount = $res[$key]['amount']; 
				if($type=='DR'){
				$balance=$balance-$amount; 
				}else{
				$balance=$balance+$amount; 
				}
	}				
	 $rset[$t]['name'] =$array[$t]['name']; 
	 $rset[$t]['gl_code'] =$array[$t]['gl_code']; 
     $rset[$t]['balance'] =$balance; 
           
  }
	}
        return $rset;
  }
}
		/* CONTAINS  OPERATING EXPENSES */

	function getExpenseAccounts($data=null){
		
	$from=date('Y-m-d',strtotime($data['startdon']));
	$to=date('Y-m-d',strtotime($data['endon']));
	$office_id=$_SESSION['office'];

	//AND created_date between '".$from."' AND '".$to."'
  $array=$this->db->selectData("SELECT * FROM acc_ledger_account where 	classification='Expenses' order by gl_code ASC");
  		$counter=count($array);  	
 
   if($counter>0){		
	for($t=0;$t<$counter;$t++) {
if(isset($data)){ 		
  $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' and created_date between '".$from."' AND '".$to."' ");
}else{
  $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' ");
}
 	 $count=count($res);
	 // print_r($count);
		 // die();
  if($count==0){
	 $rset[$t]['name'] =$array[$t]['name']; 
	 $rset[$t]['gl_code'] =$array[$t]['gl_code']; 
     $rset[$t]['balance'] ="-"; 	  
	  
  }else{
	$balance=0;
	foreach ($res as $key => $value) {
		
				$type = $res[$key]['transaction_type']; 
                $amount = $res[$key]['amount']; 
				if($type=='DR'){
				$balance=$balance+$amount; 
				}else{
				$balance=$balance-$amount; 
				}
	}				
	 $rset[$t]['name'] =$array[$t]['name']; 
	 $rset[$t]['gl_code'] =$array[$t]['gl_code']; 
     $rset[$t]['balance'] =$balance; 
           
  }
	}

        return $rset;
	}
}	
function getGlaccountdetails($id){
		
	return $this->db->selectData("SELECT * FROM acc_ledger_account where id='".$id."'");
}


/*BALANCE SHEET   */

function getAssets($data=null){
		
	$from=date('Y-m-d',strtotime($data['startdon']));
	$to=date('Y-m-d',strtotime($data['endon']));
	$office_id=$_SESSION['office'];
	//AND created_date between '".$from."' AND '".$to."'
  $array=$this->db->selectData("SELECT * FROM acc_ledger_account where 	classification='Assets' order by gl_code ASC");
  		$counter=count($array);
	for($t=0;$t<$counter;$t++) {
 if(isset($data)){ 	
  $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' and created_date between '".$from."' AND '".$to."' ");
 }else{
  $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' ");
 }
 $count=count($res);
	 // print_r($count);
		 // die();
  if($count==0){
	 $rset[$t]['name'] =$array[$t]['name']; 
	 $rset[$t]['gl_code'] =$array[$t]['gl_code']; 
     $rset[$t]['balance'] ="-"; 	  
	  
  }else{
	$balance=0;
	foreach ($res as $key => $value) {
		
				$type = $res[$key]['transaction_type']; 
                $amount = $res[$key]['amount']; 
				if($type=='DR'){
				$balance=$balance+$amount; 
				}else{
				$balance=$balance-$amount; 
				}
	}				
	 $rset[$t]['name'] =$array[$t]['name']; 
	 $rset[$t]['gl_code'] =$array[$t]['gl_code']; 
     $rset[$t]['balance'] =$balance; 
           
  }
	}

        return $rset;
}	
function getLiabilities($data=null){
		
	$from=date('Y-m-d',strtotime($data['startdon']));
	$to=date('Y-m-d',strtotime($data['endon']));
	$office_id=$_SESSION['office'];
	//AND created_date between '".$from."' AND '".$to."'
  $array=$this->db->selectData("SELECT * FROM acc_ledger_account where 	classification='Liabilities' order by gl_code ASC");
  		$counter=count($array);
	for($t=0;$t<$counter;$t++) {
 if(isset($data)){ 	
  $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' and created_date between '".$from."' AND '".$to."' ");
 }else{
 $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' ");
 }
 $count=count($res);
	 // print_r($count);
		 // die();
  if($count==0){
	 $rset[$t]['name'] =$array[$t]['name']; 
	 $rset[$t]['gl_code'] =$array[$t]['gl_code']; 
     $rset[$t]['balance'] ="-"; 	  
	  
  }else{
	$balance=0;
	foreach ($res as $key => $value) {
		
				$type = $res[$key]['transaction_type']; 
                $amount = $res[$key]['amount']; 
				if($type=='DR'){
				$balance=$balance-$amount; 
				}else{
				$balance=$balance+$amount; 
				}
	}				
	 $rset[$t]['name'] =$array[$t]['name']; 
	 $rset[$t]['gl_code'] =$array[$t]['gl_code']; 
     $rset[$t]['balance'] =$balance; 
           
  }
	}

        return $rset;
}
function getEquity($data=null){
		
	$from=date('Y-m-d',strtotime($data['startdon']));
	$to=date('Y-m-d',strtotime($data['endon']));
	$office_id=$data['office'];
	//AND created_date between '".$from."' AND '".$to."'
  $array=$this->db->selectData("SELECT * FROM acc_ledger_account where 	classification='Equity' order by gl_code ASC");
  
  		$counter=count($array);
	for($t=0;$t<$counter;$t++) {
if(isset($data)){  	
  $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' and created_date between '".$from."' AND '".$to."' ");
}else{
  $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' ");
}
  $count=count($res);
	 // print_r($count);
		 // die();
  if($count==0){
	 $rset[$t]['name'] =$array[$t]['name']; 
	 $rset[$t]['gl_code'] =$array[$t]['gl_code']; 
     $rset[$t]['balance'] ="-"; 	  
	  
  }else{
	$balance=0;
	foreach ($res as $key => $value) {
		
				$type = $res[$key]['transaction_type']; 
                $amount = $res[$key]['amount']; 
				if($type=='DR'){
				$balance=$balance-$amount; 
				}else{
				$balance=$balance+$amount; 
				}
	}				
	 $rset[$t]['name'] =$array[$t]['name']; 
	 $rset[$t]['gl_code'] =$array[$t]['gl_code']; 
     $rset[$t]['balance'] =$balance; 
           
  }
	}

        return $rset;
}

}

	