<?php

class LoanCalculations {

    function __construct() {
        $this->db = new Database();
    }

function repaymentCalculations($account_no) {

        $loan_details = $this->db->SelectData("SELECT * FROM m_loan where account_no='" . $account_no . "' ");;

        $id = $loan_details[0]['product_id'];
        $product_loan_details = $this->db->SelectData("SELECT * FROM m_product_loan  where id='" . $id . "' order by id ");
        if ($loan_details[0]['installment_option'] == "One Installment") {
			return false;
        }else{
            if($loan_details[0]['interest_method'] == "Flat"){
                if ($loan_details[0]['loan_status'] == 'Disbursed') {
                   return $this->equal_installment_Flat_interest($account_no);
                } else {
                    return $this->equal_installment_Flat_interest($account_no);
                }
            } else if($loan_details[0]['interest_method'] =="Fixed Principal"){
                if ($loan_details[0]['loan_status'] == 'Disbursed') {
                   return $this->calculate_fixedPrincipal_declining_interest($account_no,$loan_details);
                } else {
                   return $this->calculate_fixedPrincipal_declining_interest($account_no,$loan_details);
                }
            }else if($loan_details[0]['interest_method'] == "Declining Balance"){		
               if ($loan_details[0]['loan_status'] == 'Disbursed') {
                //for declining, fixed principal, fixed principal by period
                return $this->create_Repayment_Schedule($account_no,$loan_details);
            } else {
                return $this->create_Repayment_Schedule($account_no,$loan_details);
            }
        }	
    }	
}

function period($loanTermFrequencyType,$loan_details){
	
	if ($loanTermFrequencyType == "days") {

        $period = 1 / $loan_details[0]['days_in_year'];
    } else if ($loanTermFrequencyType == "weeks") {

        $period = 1 /number_format($loan_details[0]['days_in_year']/7);
        
    } else if ($loanTermFrequencyType == "months") {

        $period = 1 / 12;
        
    } else{

        $period = 1;
        
    }
    return $period ;
}

function repaymentdate($repayment_period,$date,$grace_period_value){
	
	
  if ($repayment_period == "days") {
    $date = date("Y-m-d", strtotime($date . " +" . number_format ($grace_period_value) . " day"));
    
} else if ($repayment_period == "weeks") {
 
  $date = date("Y-m-d", strtotime($date . " +" . $grace_period_value . " week"));
  
} else if ($repayment_period == "months") {
   $date = date("Y-m-d", strtotime($date . " +" . $grace_period_value . " month"));
   
} else if ($repayment_period == "years") {
    
    $date = date("Y-m-d", strtotime($date . " +" . $grace_period_value*12 . " month"));
    
}
return $date ;
}

function equal_installment_Flat_interest($account_no) {
	try{

    $loaninstallationcharges = $this->getloaninstallationcharges($account_no);


    $loan_details = $this->db->SelectData("SELECT * FROM m_loan  where account_no='" . $account_no . "' order by account_no ");
    $id = $loan_details[0]['product_id'];
    $product_loan_details = $this->db->SelectData("SELECT * FROM m_product_loan  where id='" . $id . "' order by id ");

    if ($loan_details[0]['loan_status'] == 'Pending') {
        $principal = $loan_details[0]['principal_amount_proposed'];
    } else if ($loan_details[0]['loan_status'] == 'Disbursed') {
        $principal = $loan_details[0]['principal_disbursed'];
    }else {
        $principal = $loan_details[0]['approved_principal'];
    }

    $annual_interest_percent = $loan_details[0]['annual_nominal_interest_rate'];

    $loan_term = $loan_details[0]['number_of_installments'];
    

    

    $loanTermFrequencyType = $loan_details[0]['duration'];
    $duration = $loan_details[0]['duration'];
    
    $loan_duration_value = $loan_details[0]['duration_value'];
    $duration_value = $loan_details[0]['duration_value'];
    $number_of_repayments = $loan_details[0]['number_of_installments'];
    
    
    


    if ($loanTermFrequencyType == "days") {
     $period_cal = $duration_value/$loan_details[0]['days_in_year'];
     $period_modulus = $duration_value % $loan_details[0]['days_in_year'];
     $number_of_month = $loan_details[0]['days_in_year']/12 ;
     $period = $duration_value / $loan_details[0]['days_in_year'];
     
     $change_months = $duration_value*12/$loan_details[0]['days_in_year'];
     
     if($period_cal == 1){
        $loop = $number_of_repayments;
        
        
    }else{
     
        if($duration_value > $loan_details[0]['days_in_year']){
           $loop = $number_of_repayments * $period_cal ;
           
       }else{
        $loop = $number_of_repayments;
        
    }
    
    
    
}




} else if ($loanTermFrequencyType == "weeks") {  

    

  $period = $duration_value /number_format($loan_details[0]['days_in_year']/7);
  $period_cal = $duration_value/number_format($loan_details[0]['days_in_year']/7);
  $period_modulus = $duration_value % $loan_details[0]['days_in_year'];
  $change_months = $duration_value*7*12/$loan_details[0]['days_in_year'];
  if($period_cal == 1){
    $loop = $number_of_repayments;
    
    
}else{
    if($duration_value > number_format($loan_details[0]['days_in_year']/7)){
        
      $loop = $number_of_repayments * ($duration_value*$period_cal/(number_format($loan_details[0]['days_in_year']/7) )) ;

  }else{
    $loop = $number_of_repayments;
    
}

}




        } else if ($loanTermFrequencyType == "months") { //$monthly_interest_rate = 5/100*(12/7)/12;

            $period =  $duration_value/ 12;
            $period_cal = $duration_value/12;
            $period_modulus = $duration_value % $loan_details[0]['days_in_year'];
            if($period_cal == 1){
                $loop = $number_of_repayments;
                
                
                
            }else{
                if($duration_value > 12){
 $loop = $number_of_repayments;                   
                   
                   
               }else{
                $loop = $number_of_repayments;
                
                
            }
            
        }
        
        
    } else{
     
     if($duration_value==1 || $duration_value < 1){
       $loop = $number_of_repayments ;
   } else{
    
    $loop = $number_of_repayments * $duration_value ;	
}


$period =  $duration_value;

}


$no_duration  =  $loan_duration_value/$number_of_repayments;


$interest = $principal * $period * ($annual_interest_percent / 100);

$Principal_amount = ($interest + $principal);

$monthly_pay = $Principal_amount / $loop;

$monthly_interest = $interest / $loop;

$monthly_Principal = $principal / $loop;
$daycount = 0;


$disburse_date = date("Y-m-d", strtotime($loan_details[0]['disbursedon_date']));
$rdate = date('Y-m-d', strtotime('+1 month', strtotime($disburse_date)));
$repayment_period = $loan_details[0]['grace_period'];

$grace_period_value = $loan_details[0]['grace_period_value'];
$grace_period = $loan_details[0]['grace_period'];

$grace_period_value = $loan_details[0]['grace_period_value'];




$date = $this-> repaymentdate($grace_period,$rdate,$grace_period_value);



$total_principle = $monthly_Principal;

$total_interest = $monthly_interest;

$total_charge = $loaninstallationcharges;
$number_of_repayments = ceil($loop);


for ($i = 0; $i < $number_of_repayments; $i++) {

    $from = $date;

    if ($loanTermFrequencyType == "days") {
        
        
        

        $date = date("Y-m-d", strtotime($date. " +".number_format ($no_duration)." day"));
    } else if ($loanTermFrequencyType == "weeks") {
        
        
        if(is_float($no_duration)){
           $repay_every = number_format (7*$no_duration);
           $date = date("Y-m-d", strtotime($date. " +".$repay_every." day"));
       }else{
           $date = date("Y-m-d", strtotime($date . " +".number_format ($no_duration)." week"));	
           
       }

       
   } else if ($loanTermFrequencyType =="months") {
    $no_duration  =  1;
    $date = date("Y-m-d", strtotime($date . " +".number_format ($no_duration)." month"));	
    
    
} else if ($loanTermFrequencyType == "years") {
  $no_duration  =  1;
  $date = date("Y-m-d", strtotime($date . " +".number_format ($no_duration*12)." month"));	
  
  
}

$date = date("Y-m-d", strtotime($date));

$to = $date;






$postData = array(
    'account_no' => $account_no,
    'installment' => $monthly_pay,
    'fromdate' => $from,
    'duedate' => $to,
    'principal_amount' => $monthly_Principal,
    'interest_amount' => $monthly_interest,
    'fee_charges_amount' => $loaninstallationcharges,
    
    
    'createdby_id' => $_SESSION['user_id'],
    'created_date' => date('Y-m-d'),
    'lastmodified_date' => date('Y-m-d')
);



    $this->db->InsertData('m_loan_repayment_schedule', $postData);


$total_principle = $total_principle + $monthly_Principal;

$total_interest = $total_interest + $monthly_interest;

$total_charge = $total_charge + $loaninstallationcharges;
}
if ($loan_details[0]['loan_status'] != 'Disbursed') {
    return true;
}
return false;
}catch(Exception $e){
	return false;
}
}

function getloanapplicationcharges($product_id) {
 			
    $query = $this->db->SelectData("SELECT * FROM m_product_loan_charge sc INNER JOIN m_charge mc ON  sc.charge_id =mc.id  where product_loan_id = '".$product_id."' AND mc.charge_applies_to =2 AND transaction_type_id = 5 AND is_active = 1 ");


    return $query;
}

function getloaninstallationcharges($account_no) {
$loan_details = $this->db->SelectData("SELECT * FROM m_loan  where account_no='" . $account_no . "' order by loan_id ");
        $id = $loan_details[0]['product_id'];
        $principle =  $loan_details[0]['principal_amount_proposed'];
		
    $chargedetails = $this->getloanapplicationcharges($id);
	
    $charge = 0;
    foreach ($chargedetails as $key => $value) {
            if ($value['charge_calculation_enum'] == 1) {
                $charge = $charge + $value['amount'];
            } else {
                $charge = $charge + ($principle*($value['amount']/100));
            }
    }
    return $charge;
}

function calculate_fixedPrincipal_declining_interest($account_no,$loan_details){
    try {
        $loaninstallationcharges = $this->getloaninstallationcharges($account_no);
        $loan_details = $this->db->SelectData("SELECT * FROM m_loan  where account_no='" . $account_no . "' order by loan_id ");
        $id = $loan_details[0]['product_id'];


        $form_complete = true;
        $show_progress = true;
        $account_no = $loan_details[0]['account_no'];
        $dateformate = date('Y-m-j');
        $no_date = 1;
        $date = date('Y-m-d');
        $principle  = 0;
        $annual_interest_percent = 0;
        $year_term = 0;
        $down_percent = 0;
        $this_year_interest_paid = 0;
        $this_year_principal_paid = 0;
        $year_term = 1;
        $principal_paid = 0;
        
        
        if ($loan_details[0]['loan_status'] == 'Pending') {
            $principle  = $loan_details[0]['principal_amount_proposed'];
        } else if ($loan_details[0]['loan_status'] == 'Disbursed') {
            $principle  = $loan_details[0]['principal_disbursed'];
        } else {
            $principle  = $loan_details[0]['approved_principal'];
        }
        
        $loan_term = $loan_details[0]['number_of_installments'];

        $number_of_repayments = $loan_term;

        $loanTermFrequencyType = $loan_details[0]['duration'];
        $duration_value = $loan_details[0]['duration_value'];
        

        $annual_interest_percent = $loan_details[0]['annual_nominal_interest_rate'];
        $annual_interest_rate = $annual_interest_percent / 100;	
        if($loanTermFrequencyType =="days"){
          $monthly_interest_rate = $annual_interest_rate/$loan_details[0]['days_in_year'];
      }else{		
          $monthly_interest_rate = $annual_interest_rate/12;
      }		  
      $down_percent = 0;
      if ($loanTermFrequencyType == "days") {
         $period_cal = $duration_value/$loan_details[0]['days_in_year'];
         $period_modulus = $duration_value % $loan_details[0]['days_in_year'];
         $number_of_month = $loan_details[0]['days_in_year']/12 ;

         $change_months = $duration_value*12/$loan_details[0]['days_in_year'];
         
         if($period_cal == 1){
            $loop = $number_of_repayments;			  
        }else{			
            if($duration_value > $loan_details[0]['days_in_year']){
               $loop = $number_of_repayments * $period_cal ;		 
           }else{
            $loop = $number_of_repayments;
            
        }
        
    }
    
       }else if ($loanTermFrequencyType =="months"){ //$monthly_interest_rate = 5/100*(12/7)/12;

         
         $period_cal = $duration_value/12;
         $period_modulus = $duration_value % $loan_details[0]['days_in_year'];
         if($period_cal == 1){
            $loop = $number_of_repayments;

            
        }else{
         if($duration_value > 12){
          $loop = $number_of_repayments;
       }else{
        $loop = $number_of_repayments;
    }
    
}


} else{
			 //$loop = ($number_of_repayments * ($duration_value*12)) ;
    $loop = $number_of_repayments * $duration_value ;
   // print_r($loop);die();
    
}
if ($form_complete && $show_progress) {

    $step = 1;
    $month_term = $number_of_repayments;
    $down_payment = $principle *($down_percent / 100);
		//	print_r($monthly_interest_rate );die();
    



           // $financing_price = $principle  - $down_payment;
    $financing_price = $principle;
    
    $loan_duration_value = $loan_details[0]['duration_value'];
    $no_duration  =  $loan_duration_value/$number_of_repayments;
    
            //$monthly_payment = $financing_price / $monthly_factor;

            //$month_constant          = $month_term/12 ;             
            // Set some base variables

    $principal = $financing_price;
    $current_month = 1;
    $current_no = 0;
    $current_year = 1;
    $power = -($loop);
    $denom = pow((1 + $monthly_interest_rate), $power);
    $monthly_payment = $principal * ($monthly_interest_rate / (1 - $denom));
    
    $monthly_payment_installment = number_format($monthly_payment,0,".", "");

            // Loop through and get the current month's payments for 
            // the length of the loan 

    $total_principle = 0;

    $total_interest = 0;
    $total_charge = 0;
    $totalloaninstallationcharges = $loaninstallationcharges * $month_term;
    $disburse_date = date("Y-m-d", strtotime($loan_details[0]['disbursedon_date']));
    $rdate = date('Y-m-d', strtotime('+1 month', strtotime($disburse_date)));
    
    $repayment_period = $loan_details[0]['grace_period'];
    $grace_period = $loan_details[0]['grace_period'];
    $grace_period_value = $loan_details[0]['grace_period_value'];

		//print_r(date("Y-m-d", strtotime($date)));
    $date = $this->repaymentdate($grace_period,$rdate,$grace_period_value);
    
    $month_term = ceil($loop);
	//print_r($month_term);die();
    if($loanTermFrequencyType =="years"){
        $principal_paid = $principal/$month_term;
    }else if($loanTermFrequencyType =="days"){
        $principal_paid = $principal/$month_term;
    }else{
        $principal_paid = $principal/$number_of_repayments;
    }
    

    while ($current_month <= $month_term) {
			 //echo $principal_paid." .. step 1..<br>";
     
		//	 print_r($monthly_interest_rate);die();
     
        $interest_paid = $principal * $monthly_interest_rate;						
        
        $remaining_balance = $principal - $principal_paid;

        $from = $date;

        if ($loanTermFrequencyType == "days") {
            $date = date("Y-m-d", strtotime($date . " +".number_format ($no_duration)." day"));
        } else if ($loanTermFrequencyType == "weeks") {

            if(is_float($no_duration)){
               $repay_every = number_format (7*$no_duration);
               $date = date("Y-m-d", strtotime($date. " +".$repay_every." day"));
           }else{
               $date = date("Y-m-d", strtotime($date . " +".number_format ($no_duration)." week"));	
               
           }
           
       } else if ($loanTermFrequencyType =="months") {
           
        $no_duration  =  1/12;  
        $date = date("Y-m-d", strtotime($date . " +".number_format ($no_duration)." month"));	
        
    } else if ($loanTermFrequencyType =="years") {
        $loan_duration_value = $loan_details[0]['duration_value'];
        $no_duration  =  $loan_duration_value/$number_of_repayments;
        $no_duration  =  1/12;

			//	print_r($no_duration ); die();
             //   if(is_float($no_duration)){
				//	$repay_every = number_format ($loan_details[0]['days_in_year']*$no_duration);
				//		$date = date("Y-m-d", strtotime($date. " +".$repay_every." day"));
			//	}else{
        $date = date("Y-m-d", strtotime($date . " +".number_format ($no_duration*12)." month"));	
				//print_r(number_format ($no_duration*12)); die();

					// }
    }
               // $date = date('Y-m-d', $date);
    $date = date("Y-m-d", strtotime($date));
				//print_r($date);

    $to = $date;
    $this_year_interest_paid = $this_year_interest_paid + $interest_paid;
    $this_year_principal_paid = $this_year_principal_paid + $principal_paid;
    
    $princial_per = str_replace(",", "", number_format($principal_paid, 2));
    $interest_per = str_replace(",", "", number_format($interest_paid, 2));
    $total_principle = $total_principle + $princial_per;
    $total_interest = $total_interest + $interest_per;
    $total_charge = $total_charge + $loaninstallationcharges;
    $postData = array(
        'account_no' => $account_no,
        'installment' => $monthly_payment_installment,
        'fromdate' => $from,
        'duedate' => $to,
        'principal_amount' => $princial_per,
        'interest_amount' => $interest_per,
        'fee_charges_amount' => $loaninstallationcharges,
        
        'createdby_id' => $_SESSION['user_id'],
        'created_date' => date('Y-m-d'),
        'lastmodified_date' => date('Y-m-d')
    );
    if ($loan_details[0]['loan_status'] != 'Disbursed') {

        $rset[$current_no]['account_no'] = $account_no;
        $rset[$current_no]['installment'] = $monthly_payment_installment;
        $rset[$current_no]['fromdate'] = $from;
        $rset[$current_no]['duedate'] = $to;
        $rset[$current_no]['principal_amount'] = $princial_per;
        $rset[$current_no]['interest_amount'] = $interest_per;
        $rset[$current_no]['fee_charges_amount'] = $loaninstallationcharges;
        $rset[$current_no]['principal_completed'] = $total_principle;
        $rset[$current_no]['interest_completed'] = $total_interest;
        $rset[$current_no]['fee_charges_completed'] = $total_charge;
        $rset[$current_no]['createdby_id'] = $loan_details[0]['created_by'];
        $rset[$current_no]['created_date'] = $loan_details[0]['submittedon_date'];
    } else{
       
        $this->db->InsertData('m_loan_repayment_schedule', $postData);
        
    }

    ($current_month % 12) ? $show_legend = FALSE : $show_legend = TRUE;

    if ($show_legend) {

        $current_year++;

        $this_year_interest_paid = 0;

        $this_year_principal_paid = 0;

        if (($current_month + 6) < $month_term) {

                       // echo $legend;
        }
    }

    $principal = $remaining_balance;

    $current_month++;
    $current_no++;
}

if ($loan_details[0]['loan_status'] != 'Disbursed') {
    return true;
}

}

return true;
} catch (Exception $e) {
   return false;

}

}

function create_Repayment_Schedule($account_no) {
   $account_no = $account_no;
   $loaninstallationcharges = $this->getloaninstallationcharges($account_no);

   $loan_details = $this->db->SelectData("SELECT * FROM m_loan  where account_no='" . $account_no . "' order by loan_id ");
   $id = $loan_details[0]['product_id'];
   $product_loan_details = $this->db->SelectData("SELECT * FROM m_product_loan  where id='" . $id . "' order by id ");

   $form_complete = true;
   $show_progress = true;
   $account_no = $loan_details[0]['account_no'];
   $dateformate = date('Y-m-j');
   $no_date = 1;
   $date = date('Y-m-d');
   $principle  = 0;
   $annual_interest_percent = 0;
   $year_term = 0;
   $down_percent = 0;
   $this_year_interest_paid = 0;
   $this_year_principal_paid = 0;
   $year_term = 1;
   
   
   if ($loan_details[0]['loan_status'] == 'Pending') {
    $principle  = $loan_details[0]['principal_amount_proposed'];
} else if ($loan_details[0]['loan_status'] == 'Disbursed') {
    $principle  = $loan_details[0]['principal_disbursed'];
} else {
    $principle  = $loan_details[0]['approved_principal'];
}

$loan_term = $loan_details[0]['number_of_installments'];

$number_of_repayments = $loan_term;

$loanTermFrequencyType = $loan_details[0]['duration'];
$duration_value = $loan_details[0]['duration_value'];

$annual_interest_percent = $loan_details[0]['annual_nominal_interest_rate'];

$down_percent = 0;
if ($loanTermFrequencyType == "days") {
 $period_cal = $duration_value/$loan_details[0]['days_in_year'];
 $period_modulus = $duration_value % $loan_details[0]['days_in_year'];
 $number_of_month = $loan_details[0]['days_in_year']/12 ;
 $period =  (12/$number_of_repayments)/ 12;
 $change_months = $duration_value*12/$loan_details[0]['days_in_year'];
 
 if($period_cal == 1){
    $loop = $number_of_repayments;			  
}else{			
    if($duration_value > $loan_details[0]['days_in_year']){
       $loop = $number_of_repayments * $period_cal ;		 
   }else{
    $loop = $number_of_repayments;
    
}

}

} else if ($loanTermFrequencyType == "weeks"){  

    $period = $number_of_repayments /number_format($loan_details[0]['days_in_year']/7);
    $period_cal = $duration_value/number_format($loan_details[0]['days_in_year']/7);
    $period_modulus = $duration_value % $loan_details[0]['days_in_year'];
    $change_months = $duration_value*7*12/$loan_details[0]['days_in_year'];
    if($period_cal == 1){
        $loop = $number_of_repayments;
        
			//  $loop = $number_of_repayments * (12 *ceil($period_cal)/12) ;
        
        $period =  (12/$number_of_repayments)/ 12;
    }else{
        if($duration_value > number_format($loan_details[0]['days_in_year']/7)){
            
          $loop = $number_of_repayments * ($duration_value*$period_cal/(number_format($loan_details[0]['days_in_year']/7) )) ;
          $period =  (12/$number_of_repayments)/ 12;
      }else{
        $loop = $number_of_repayments;
        $period =  (12/$number_of_repayments)/ 12;
    }
    
}




        } else if ($loanTermFrequencyType =="months"){ //$monthly_interest_rate = 5/100*(12/7)/12;

            $period =  (12/$number_of_repayments)/ 12;
            $period_cal = $duration_value/12;
            $period_modulus = $duration_value % $loan_details[0]['days_in_year'];
            if($period_cal == 1){
                $loop = $number_of_repayments;
                
                $period =  (12/$number_of_repayments)/ 12;
                
            }else{
                if($duration_value > 12){
                    $loop = $number_of_repayments;
                   
                   $period =  (12/$number_of_repayments)/ 12;

                   
               }else{
                $loop = $number_of_repayments;
                $period =  (12/$number_of_repayments)/ 12;
                
            }
            
        }
        
        
    } else{
        $loop = $number_of_repayments * $duration_value ;

        $period =  (12/$number_of_repayments)/ 12;
        
    }

    if ($form_complete && $show_progress) {


        $step = 1;
        $month_term = $number_of_repayments;
        $down_payment = $principle  * ($down_percent / 100);
        $annual_interest_rate = $annual_interest_percent / 100;
        $monthly_interest_rate = $annual_interest_rate * $period;
        $financing_price = $principle  - $down_payment;
        
        $loan_duration_value = $loan_details[0]['duration_value'];
        $no_duration  =  $loan_duration_value/$number_of_repayments;

            //$monthly_payment = $financing_price / $monthly_factor;

            //$month_constant          = $month_term/12 ;             
            // Set some base variables

        $principal = $financing_price;
        $current_month = 1;
        $current_no = 0;
        $current_year = 1;
        $power = -($loop);
        $denom = pow((1 + $monthly_interest_rate), $power);
        $monthly_payment = $principal * ($monthly_interest_rate / (1 - $denom));
        $monthly_payment_installment = number_format($monthly_payment, 0,".", "");

            // Loop through and get the current month's payments for 
            // the length of the loan 

          //  $repayment_period = $loan_details[0]['term_period_frequency'];

           // $repay_every = $loan_details[0]['repay_every'];

        $total_principle = 0;

        $total_interest = 0;
        $total_charge = 0;
        $totalloaninstallationcharges = $loaninstallationcharges * $month_term;
        $disburse_date = date("Y-m-d", strtotime($loan_details[0]['disbursedon_date']));
        $rdate = date('Y-m-d', strtotime('+1 month', strtotime($disburse_date)));

        $repayment_period = $loan_details[0]['grace_period'];
        $grace_period = $loan_details[0]['grace_period'];
        $grace_period_value = $loan_details[0]['grace_period_value'];

		//print_r(date("Y-m-d", strtotime($date)));
        $date = $this->repaymentdate($grace_period,$rdate,$grace_period_value);
        $month_term = ceil($loop);
	

        while ($current_month <= $month_term   ) {

            $interest_paid = $principal * $monthly_interest_rate;

            $principal_paid = $monthly_payment - $interest_paid;

            $remaining_balance = $principal - $principal_paid;

            $from = $date;

            if ($loanTermFrequencyType == "days") {
                $date = date("Y-m-d", strtotime($date . " +".number_format ($no_duration)." day"));
            } else if ($loanTermFrequencyType == "weeks") {

                if(is_float($no_duration)){
                   $repay_every = number_format (7*$no_duration);
                   $date = date("Y-m-d", strtotime($date. " +".$repay_every." day"));
               }else{
                   $date = date("Y-m-d", strtotime($date . " +".number_format ($no_duration)." week"));	
                   
               }
               
           } else if ($loanTermFrequencyType =="months") {

              $no_duration  =  1;
              $date = date("Y-m-d", strtotime($date . " +".number_format ($no_duration)." month"));	
              
          } else if ($loanTermFrequencyType == "years") {
           $no_duration  =  1/12;
           $date = date("Y-m-d", strtotime($date . " +".number_format ($no_duration*12)." month"));	
           
       }

       $date = date("Y-m-d", strtotime($date));
				//print_r($date);

       $to = $date;

       $this_year_interest_paid = $this_year_interest_paid + $interest_paid;

       $this_year_principal_paid = $this_year_principal_paid + $principal_paid;

       $princial_per = number_format($principal_paid,0,".", "");

       $interest_per = number_format($interest_paid,0,".", "");

       $total_principle = $total_principle + $princial_per;

       $total_interest = $total_interest + $interest_per;

       $total_charge = $total_charge + $loaninstallationcharges;

       $postData = array(
        'account_no' => $account_no,
        'installment' => $monthly_payment_installment,
        'fromdate' => $from,
        'duedate' => $to,
        'principal_amount' => $princial_per,
        'interest_amount' => $interest_per,
        'fee_charges_amount' => $loaninstallationcharges,        
        
        'createdby_id' => $_SESSION['user_id'],
        'created_date' => date('Y-m-d'),
        'lastmodified_date' => date('Y-m-d')
    );
       
       if ($loan_details[0]['loan_status'] != 'Disbursed') {

        $rset[$current_no]['account_no'] = $account_no;
        $rset[$current_no]['installment'] = $monthly_payment_installment;
        $rset[$current_no]['fromdate'] = $from;
        $rset[$current_no]['duedate'] = $to;
        $rset[$current_no]['principal_amount'] = $princial_per;
        $rset[$current_no]['interest_amount'] = $interest_per;
        $rset[$current_no]['fee_charges_amount'] = $loaninstallationcharges;
        $rset[$current_no]['principal_completed'] = $total_principle;
        $rset[$current_no]['interest_completed'] = $total_interest;
        $rset[$current_no]['fee_charges_completed'] = $total_charge;
        $rset[$current_no]['createdby_id'] = $loan_details[0]['created_by'];
        $rset[$current_no]['created_date'] = $loan_details[0]['submittedon_date'];
    } else {
        $this->db->InsertData('m_loan_repayment_schedule', $postData);
    }

    ($current_month % 12) ? $show_legend = FALSE : $show_legend = TRUE;

    if ($show_legend) {

        $current_year++;

        $this_year_interest_paid = 0;

        $this_year_principal_paid = 0;

        if (($current_month + 6) < $month_term) {

                       // echo $legend;
        }
    }

    $principal = $remaining_balance;

    $current_month++;
    $current_no++;
}

if ($loan_details[0]['loan_status'] != 'Disbursed') {
    return $rset;
}
}
}

}
?>
