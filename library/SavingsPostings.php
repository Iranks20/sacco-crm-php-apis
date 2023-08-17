<?php

class SavingsPostings {

    function __construct() {
        $this->db = new Database();
    }

function postinterest(){
	$daily_rate=0;
	$interest_rate=0;
	$interest_amount=0;
	$today=date('Y-m-d');
	$days_in_year=0;
	$interest_cal_method=null;
//m_savings_account_accrued_interest
  $account_details=$this->db->selectData("SELECT * FROM m_savings_account ac JOIN m_savings_product p ON ac.product_id=p.id");
	       foreach ($account_details as $key => $value) { 
			 $account_no=$value['account_no']; 
			 $days_in_year=$value['days_in_year']; 
			 $interest_rate=$value['nominal_interest_rate']; 
			 $interest_cal_method=$value['interest_calculation_method']; 
			 $acc_balance=$value['running_balance']; 
         $trans=$this->getaccountTransactions($account_no);
		  $trans_date=date('Y-m-d',strtotime($trans['transaction_date']));
          $trans_type=$trans['transaction_type'];
          $running_balance=$trans['running_balance'];
		  if($interest_cal_method=='Daily Balance'){
  		$daily_rate=$interest_rate/$days_in_year;
		$interest_amount=$acc_balance*$daily_rate*$no_of_days;
		  }else{
  		$daily_rate=($interest_rate*$month_days)/(100*$days_in_year);
		$interest_amount=$acc_balance*$daily_rate*$no_of_days;		  
			  
		  }
		 print_r($days_in_year);
	 // die();
		    }

			print_r($daily_rate);
	die();
}


function getaccountTransactions($account_no){
		  $last_trans=$this->db->selectData("SELECT MAX(id) as last FROM m_savings_account_transaction  where savings_account_no='".$account_no."'");	  

		  $transactions=$this->db->selectData("SELECT * FROM m_savings_account_transaction  where id='".$last_trans[0]['last']."'");	  

return 	$transactions[0];
}
	
   	
}

?>
