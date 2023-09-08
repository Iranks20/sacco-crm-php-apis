<?php
//error_reporting(0);
class Reports_model extends Model{
  public function __construct(){
    parent::__construct();
    $this->logUserActivity(NULL); 
    if (!$this->checkTransactionStatus()) {
      header('Location: ' . URL); 
    }

  }

  ///////////////////////////////////////////STEVEN/////////////////////////////////////////////

    function getWalletTransactions($start, $end)
    {
        $data = array();
		$data['app_id'] = 4;
		$data['from_date'] = date_format(date_create($start), "Ymd");
		$data['to_date'] = date_format(date_create($end), "Ymd");
		$data['status'] = "all";
    	
        $url = SEARCH_TRANSACTIONS;
        
        $ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL,$url);
    	curl_setopt($ch, CURLOPT_POST, 1);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	$server_output = curl_exec ($ch);
    	curl_close ($ch);
    	
    	$response = (array) json_decode($server_output);
    	
    	return (array) $response['payments'];
    }

  //////////////////////////////////////////////////////////////////////////////////////////////

  function getGlAccountsCount(){
    $id = $_SESSION['office'];
    $data = $this->db->selectData("SELECT count(product_id) FROM acc_ledger_account_mapping where office_id= $id");
    
    return $data[0][0];
  }
    function getDormantMembersList($office, $range)
    {
        try {
            $date = date('Y-m-d', strtotime('-' . $range . 'days'));

            $data = $this->db->selectData("SELECT member_id, account_no, account_name FROM m_savings_account WHERE office_id = $office");

            foreach ($data as $key => $value) {
                $transaction_data = $this->db->selectData("SELECT count(savings_account_no) as count FROM m_savings_account_transaction WHERE transaction_date >= '" . $date . "' AND savings_account_no = " . $value['account_no']);

                if ($transaction_data[0]['count'] > 0) {
                    unset($data[$key]);
                }
            }

            $response = array("status" => 200, "message" => "Dormant accounts retrieved successfully", "data" => array_values($data));
            return $response;
        } catch (Exception $e) {
            $errorResponse = array("status" => 500, "message" => "Failed to retrieve dormant accounts: " . $e->getMessage());
            return $errorResponse;
        }
    }
  
    function getDormantMemebersListRange(){
        print_r($_POST);
        die();
    }

  ////////LOANS///////

  function Getloanslist(){
    $query= $this->db->SelectData("SELECT * FROM m_loan l JOIN (members m  JOIN m_branch b ON m.office_id=b.id)
      ON  l.member_id  = m.c_id where m.office_id='".$_SESSION['office']."'");

    return $query;

  }


  function getPendingLoans(){
    $office=$_SESSION['office'];
    $query =   $this->db->SelectData("SELECT * FROM m_loan JOIN members on members.c_id=m_loan.member_id where members.office_id='".$office."' AND loan_status ='Pending'");

    return $query;

  }




  function getDisbursedLoans(){
    $office=$_SESSION['office'];
    $query =   $this->db->SelectData("SELECT * FROM m_loan JOIN members on members.c_id=m_loan.member_id where members.office_id='".$office."' AND loan_status ='Disbursed' ");

    return $query;

  }

  function getApprovedLoans(){
    $office=$_SESSION['office'];
    $query =   $this->db->SelectData("SELECT * FROM m_loan JOIN members on members.c_id=m_loan.member_id where members.office_id='".$office."' AND loan_status ='Approved'");

    return $query;

  }


  function getProvisionDefinitions(){
    $office=$_SESSION['office'];
    $provisioning =$this->db->SelectData("SELECT * FROM m_loan_ageing WHERE office_id = $office");

    return $provisioning;

  }


  function getDetailedProvisioning($id){

    $office=$_SESSION['office'];
    $today=date('Y-m-d');
    $provisioning =$this->db->SelectData("SELECT * FROM m_loan_ageing where id='".$id."'");
    $total_interest=0;
    $total_principal=0;
    $rset=null;
    $query=null;
    if(count($provisioning)>0){
      $accounts=0;
      $loan_balance=0;        
      if($provisioning[0]['days_from']==0){
        $query = $this->db->SelectData("SELECT * FROM m_loan_repayment_schedule s JOIN (m_loan l JOIN members m ON m.c_id=l.member_id)ON s.account_no=l.account_no  WHERE m.office_id='".$office."' AND l.loan_status='Disbursed' AND (DATEDIFF('".$today."',s.duedate)>'".$provisioning[0]['days_from']."' OR DATEDIFF('".$today."',s.duedate)='".$provisioning[0]['days_to']."') GROUP BY l.account_no");

      }else if($provisioning[0]['provision']==100){
        $query = $this->db->SelectData("SELECT * FROM m_loan_repayment_schedule s JOIN (m_loan l JOIN members m ON m.c_id=l.member_id)ON s.account_no=l.account_no  WHERE m.office_id='".$office."' AND l.loan_status='Disbursed' AND (DATEDIFF('".$today."',s.duedate)>='".$provisioning[0]['days_from']."') GROUP BY l.account_no");  
      }else{
        $query = $this->db->SelectData("SELECT * FROM m_loan_repayment_schedule s JOIN (m_loan l JOIN members m ON m.c_id=l.member_id)ON s.account_no=l.account_no  WHERE m.office_id='".$office."' AND l.loan_status='Disbursed' AND (DATEDIFF('".$today."',s.duedate) BETWEEN '".$provisioning[0]['days_from']."' AND '".$provisioning[0]['days_to']."') GROUP BY l.account_no"); 
      }



      foreach($query as $key=>$value){
        $accounts=$accounts+1;
        $loan_balance=$loan_balance+$value['installment'];
        if(empty($value['company_name'])){
          $name=$value['firstname']." ".$value['middlename']." ".$value['lastname'];  
        }else{
          $name=$value['company_name'];   
        }
        $diff=date_diff(date_create($value['duedate']),date_create($today));
        if($diff->format("%R%a")>0){
          $days=$diff->format("%R%a");    
        }else{
          $days=0;    
        }   
        $total_principal=$total_principal+$value['principal_amount'];
        $total_interest=$total_interest+$value['interest_amount'];
        $rset .='<tr class="gradeX">
        <td>'.$name.'</td>
        <td>'.number_format($value['principal_amount']).'</td>
        <td>'.number_format($value['interest_amount']).'</td>   
        <td>'.$days.'</td></tr>';   
      }
      $rset .='<tr class="gradeX"  >
      <td>Totals</td>
      <td>'.number_format($total_principal).'</td>   
      <td>'.number_format($total_interest).'</td>         
      <td></td></tr>';    

    }

//print_r($rset);die();
    return $rset;

  }



  function getLoanProvisioning(){
    $office=$_SESSION['office'];
    $today=date('Y-m-d');
    $provisioning =$this->db->SelectData("SELECT * FROM m_loan_ageing WHERE office_id = $office");
    $total_accounts=0;
    $total_loan_balance=0;
    $total_recovery=0;
    $rset=null;
    $query=null;
    $outstanding=null;
    for($i=0;$i<count($provisioning);$i++){
      $accounts=0;
      $loan_balance=0;        
      $rset .='<tr class="gradeX"  >
      <td>'.$provisioning[$i]['description'].'</td>'; 
      if($i==0&&$provisioning[$i]['days_from']==0){
        $query = $this->db->SelectData("SELECT * FROM m_loan_repayment_schedule s JOIN (m_loan l JOIN members m ON m.c_id=l.member_id)ON s.account_no=l.account_no  WHERE m.office_id='".$office."' AND l.loan_status='Disbursed' AND (DATEDIFF('".$today."',s.duedate)<='".$provisioning[$i]['days_from']."' OR DATEDIFF('".$today."',s.duedate)='".$provisioning[$i]['days_to']."') GROUP BY l.account_no");  
      }else if(count($provisioning)-$i==1){
        $query = $this->db->SelectData("SELECT * FROM m_loan_repayment_schedule s JOIN (m_loan l JOIN members m ON m.c_id=l.member_id)ON s.account_no=l.account_no  WHERE m.office_id='".$office."' AND l.loan_status='Disbursed' AND (DATEDIFF('".$today."',s.duedate)>='".$provisioning[$i]['days_from']."') GROUP BY l.account_no"); 
      }else{
        $query = $this->db->SelectData("SELECT * FROM m_loan_repayment_schedule s JOIN (m_loan l JOIN members m ON m.c_id=l.member_id)ON s.account_no=l.account_no  WHERE m.office_id='".$office."' AND l.loan_status='Disbursed' AND (DATEDIFF('".$today."',s.duedate) BETWEEN '".$provisioning[$i]['days_from']."' AND '".$provisioning[$i]['days_to']."') GROUP BY l.account_no");   
      }
//print_r($query);die();
      foreach($query as $key=>$value){
        $outstanding = $this->db->SelectData("SELECT sum(installment) as installment  FROM m_loan_repayment_schedule  WHERE account_no='".$value['account_no']."' AND principal_completed='' ");    
    //print_r("SELECT sum(installment) as installment  FROM m_loan_repayment_schedule  WHERE account_no='".$value['account_no']."' AND principal_completed='' ");die();
        $accounts=$accounts+1;
    //$loan_balance=$loan_balance+$value['installment']; from query 
        $loan_balance=$loan_balance+$outstanding[0]['installment'];  
      }

      $total_accounts=$total_accounts+$accounts;
      $total_loan_balance=$total_loan_balance+$loan_balance;
      $recovery=($loan_balance*$provisioning[$i]['provision'])/100;
      $total_recovery=$total_recovery+$recovery;
      $rset .='<td>'.number_format($accounts).'</td>
      <td>'.number_format($loan_balance).'</td>   
      <td>'.$provisioning[$i]['provision'].'</td> 
      <td>'.number_format($recovery).'</td></tr>';    

    }
    $rset .='<tr class="gradeX"  >
    <td>Totals</td>
    <td>'.number_format($total_accounts).'</td>    
    <td>'.number_format($total_loan_balance).'</td>    
    <td></td>        
    <td>'.number_format($total_recovery).'</td></tr>';       
//print_r($rset);die();
    return $rset;

  }


  function getLoanAging1(){
    $office=$_SESSION['office'];
    $today=date('Y-m-d');
    $query =   $this->db->SelectData("SELECT * FROM m_loan_repayment_schedule s JOIN (m_loan l JOIN members m ON m.c_id=l.member_id)ON s.account_no=l.account_no  WHERE m.office_id='".$office."' AND DATEDIFF('".$today."',s.duedate)>0 AND DATEDIFF('".$today."',s.duedate)<30 ");
//print_r("SELECT * FROM m_loan_repayment_schedule s JOIN (m_loan l JOIN members m ON m.c_id=l.member_id)ON s.account_no=l.account_no  WHERE m.office_id='".$office."' AND DATEDIFF('".$today."',s.duedate)>0 && DATEDIFF('".$today."',s.duedate)<30 ");
    print_r($query);die();
    return $query;

  }

  function membersList($office) {
      try {
          $result = $this->db->SelectData("SELECT * FROM members m INNER JOIN m_branch b WHERE m.office_id = b.id AND office_id = '$office' ORDER BY m.office_id DESC");

          return $result;
      } catch (Exception $e) {
          throw new Exception("Failed to fetch members' list: " . $e->getMessage());
      }
  }

  function savingslist($office) {
    try {
        $query = $this->db->SelectData("SELECT * FROM m_savings_account s JOIN (members m JOIN m_branch b ON m.office_id=b.id)
            ON s.member_id = m.c_id WHERE m.office_id = '$office'");

        return $query;
    } catch (Exception $e) {
        throw new Exception("Failed to fetch savings list: " . $e->getMessage());
    }
}

  function getSavingsProductAccount($id){
    $query= $this->db->SelectData("SELECT count(s.product_id) as number,sum(running_balance) as balance FROM m_savings_account s  JOIN (members m  JOIN m_branch b ON m.office_id=b.id)
      ON  s.member_id  = m.c_id where m.office_id='".$_SESSION['office']."' and s.product_id='".$id."'");

    return $query;
  }

function getSavingsByProduct($office) {
  try {
      $result = $this->db->SelectData("SELECT * FROM m_savings_account s JOIN (members m JOIN m_branch b ON m.office_id = b.id) ON s.member_id = m.c_id WHERE m.office_id = '$office'");

      $count = count($result);

      if ($count > 0) {
          foreach ($result as $key => $value) {
              $accounts = $this->getSavingsProductAccount($value['id']);
              $rset[$key]['product_type'] = $value['name'];
              $rset[$key]['no_of_accounts'] = $accounts[0]['number'];
              $rset[$key]['balance_of_account'] = $accounts[0]['balance'];
          }
          return $rset;
      }
  } catch (Exception $e) {
      // Handle any exceptions and return an error response
      throw new Exception("Failed to fetch savings by product: " . $e->getMessage());
  }
}

function getSavingsAccountByStatus($status){
  $query= $this->db->SelectData("SELECT count(s.account_status) as number,sum(running_balance) as balance FROM m_savings_account s  JOIN (members m  JOIN m_branch b ON m.office_id=b.id)
    ON  s.member_id  = m.c_id where m.office_id='".$_SESSION['office']."' and s.account_status='".$status."'");

  return $query;
}

function getSavingsByStatus($office) {
  try {
      $array = array(
          '0' =>'Active',
          '1' =>'Domant',
          '2' =>'Closed',
          '3' =>'Opened',
      );

      $count = count($array);

      if ($count > 0) {
          for ($i = 0; $i < $count; $i++) {
              $status = $this->getSavingsAccountByStatus($office, $array[$i]);
              $rset[$i]['status'] = $array[$i];
              $rset[$i]['no_of_accounts'] = $status[0]['number'];
              $rset[$i]['balance_of_account'] = $status[0]['balance'];
          }
          return $rset;
      }
  } catch (Exception $e) {
      throw new Exception("Failed to fetch savings by status: " . $e->getMessage());
  }
}

function fixeddepositList($office) {
  try {
      $query = $this->db->SelectData("SELECT * FROM fixed_deposit_account f JOIN (members m  JOIN m_branch b ON m.office_id=b.id)
          ON  f.member_id  = m.c_id where m.office_id='$office'");
      return $query;
  } catch (Exception $e) {
      throw new Exception("Failed to fetch fixed deposit data: " . $e->getMessage());
  }
}

function getFixedProductAccount($id){
  $query= $this->db->SelectData("SELECT count(f.product_id) as number,sum(running_balance) as balance FROM fixed_deposit_account f  JOIN (members m  JOIN m_branch b ON m.office_id=b.id)
    ON  f.member_id  = m.c_id where m.office_id='".$_SESSION['office']."' and f.product_id='".$id."'");

  return $query;
}

function getFixedByProduct($office) {
  try {
      $result = $this->db->SelectData("SELECT * FROM fixed_deposit_product WHERE office_id = $office");
      $count = count($result);
      $rset = array();

      if ($count > 0) {
          foreach ($result as $key => $value) {
              $accounts = $this->getFixedProductAccount($value['id'], $office);
              $rset[$key]['product_type'] = $value['name'];
              $rset[$key]['no_of_accounts'] = $accounts[0]['number'];
              $rset[$key]['balance_of_account'] = $accounts[0]['balance'];
          }
      }

      return $rset;
  } catch (Exception $e) {
      throw new Exception("Failed to fetch fixed deposit data by product: " . $e->getMessage());
  }
}

function getFixedAccountByStatus($status){
  $query= $this->db->SelectData("SELECT count(f.account_status) as number,sum(running_balance) as balance FROM fixed_deposit_account f  JOIN (members m  JOIN m_branch b ON m.office_id=b.id)
    ON  f.member_id  = m.c_id where m.office_id='".$_SESSION['office']."' and f.account_status='".$status."'");

  return $query;
}

function getFixedByStatus($office) {
  try {
      $array = array(
          '0' =>'Active',
          '1' =>'Closed',
      );

      $count = count($array);
      $rset = array();

      if ($count > 0) {
          for ($i = 0; $i < $count; $i++) {
              $status = $this->getFixedAccountByStatus($array[$i], $office);
              $rset[$i]['status'] = $array[$i];
              $rset[$i]['no_of_accounts'] = $status[0]['number'];
              $rset[$i]['balance_of_account'] = $status[0]['balance'];
          }
      }

      return $rset;
  } catch (Exception $e) {
      throw new Exception("Failed to fetch fixed deposit data by status: " . $e->getMessage());
  }
}

////share_accounts
function ShareHoldersLists(){

  $result= $this->db->SelectData("SELECT * FROM share_account JOIN members on share_account.member_id=members.c_id where members.office_id='".$_SESSION['office']."' ");
  $count=count($result);

  if($count>0){
   foreach ( $result as $key => $value) {
    $rset[$key]['member'] = $value['c_id']; 
    $rset[$key]['account_no'] = $value['share_account_no']; 
    $rset[$key]['office_id'] = $value['office_id']; 
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
  $office=$_SESSION['office'];
  $result= $this->db->SelectData("SELECT * FROM share_products where office_id = $office");
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

//GENEEAL LEGER Reports_model

function getGlaccountName($account){

  return $this->db->selectData("SELECT * FROM acc_ledger_account WHERE id='".$account."' ");
}
function getGlaccounts(){

  return $this->db->selectData("SELECT * FROM acc_ledger_account WHERE account_usage='Account' AND sacco_id = '".$_SESSION['office']."' order by  gl_code ASC,classification");
}

function getBalance_Forward($date,$account){

  $from=date('Y-m-d',strtotime($date));

  $office_id=$_SESSION['office'];
  $balance=0; 

  if ($_SESSION['Isheadoffice'] == 'Yes') {
    $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$account."' AND office_id='".$office_id."' and DATE(created_date) < '".$from."' ");
  } else {
    $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$account."' AND office_id='".$office_id."' AND branch_id = '".$_SESSION['branchid']."' and DATE(created_date) < '".$from."' ");
  }

  if(count($res)>0){ 
    foreach ($res as $key => $value) {
      $side = $res[$key]['trial_balance_side']; 
      $type = $res[$key]['transaction_type']; 
      $amount = $res[$key]['amount']; 
      if($side=='SIDE_A'){
        if($type=='DR'){
          $balance=$balance+$amount;
        }else{
          $balance=$balance-$amount;      
        }                
      }else{
        if($type=='DR'){
          $balance=$balance-$amount;          
        }else{
          $balance=$balance+$amount;                      
        }                       
      }
    }
  //$date=$this->db->selectData("SELECT MAX(DATE(created_date)) as date FROM acc_gl_journal_entry where account_id='".$account."' AND office_id='".$office_id."' and DATE(created_date) < '".$from."' ");
    $prev_date = date('d-m-Y', strtotime($from .' -1 day'));
    $bal_date['balance']=$balance;    
    $bal_date['date']=$prev_date;   
    
    return $bal_date;
  }
}

function getGLreport($data){

  $start=$data['startdon'];
  $end=$data['endon'];

  $from=date('Y-m-d',strtotime($start));
  $to=date('Y-m-d',strtotime($end));
  $acc_id=$data['glaccount'];
  $office_id=$_SESSION['office'];

  if(!empty($start)){
    $branches = $this->getSaccoBranches();
    if ($_SESSION['Isheadoffice'] == 'Yes') {
      if ((isset($data['branch']) && $data['branch'] == 'all') || empty($branches)) {
        $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$acc_id."' AND office_id='".$office_id."' and DATE(created_date) BETWEEN '".$from."' AND '".$to."' ");
      } else {
        $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$acc_id."' AND office_id='".$office_id."' AND branch_id = '".$data['branch']."' and DATE(created_date) BETWEEN '".$from."' AND '".$to."' ");
      }
    } else {
      $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$acc_id."' AND office_id='".$office_id."' AND branch_id = '".$_SESSION['branchid']."' and DATE(created_date) BETWEEN '".$from."' AND '".$to."' ");
    }
  }else{
    if ($_SESSION['Isheadoffice'] == 'Yes') {
      $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$acc_id."' AND office_id='".$office_id."' ");
    } else {
      $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$acc_id."' AND office_id='".$office_id."' AND branch_id = '".$_SESSION['branchid']."' ");
    }
  }
  if(count($res)>0){ 
 //print_r($res);
//  die();
    $balance=0;
    $debit=0;
    $credit=0;
    $prev_bal=$this->getBalance_Forward($start,$acc_id);
    $balance=$prev_bal['balance'];
    foreach ($res as $key => $value) {
                //$officename = $this->officeName($result[$key]['id']);
      $rset[$key]['created_date'] =date('d-m-Y',strtotime($res[$key]['created_date'])); 
      $rset[$key]['account_id'] = $res[$key]['account_id']; 
      $rset[$key]['office_id'] = $res[$key]['office_id']; 
      $rset[$key]['description'] = $res[$key]['description']; 
      $side = $res[$key]['trial_balance_side']; 
      $type = $res[$key]['transaction_type']; 
      $amount = $res[$key]['amount']; 

                /*
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
                
                */
                
                if($type=='DR'){
                  $balance=$balance-$amount;
                  $debit=$amount ;
                  $credit="";
                }else{
                  $balance=$balance+$amount; 
                  $credit=$amount ;           
                  $debit="";          
                }
                
                

                $rset[$key]['debit'] =$debit; 
                $rset[$key]['credit'] =$credit; 
                $rset[$key]['balance'] =$balance; 
              }
              return $rset;
            }
          }


          function getGLreportByPostDate($data){

            $start=$data['startdon'];
            $end=$data['endon'];

            $from=date('Y-m-d',strtotime($start));
            $to=date('Y-m-d',strtotime($end));
            $office_id=$_SESSION['office'];
            $rset=null; 

           

              $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where  office_id='".$office_id."' and DATE(created_date) between '".$from."' AND '".$to."' order by journal_id");    
              if(count($res)>0){ 
 //print_r($res);
//  die();
                $balance=0;
                $prev_bal=$this->getBalance_Forward($from,$acc);
                $balance=$prev_bal['balance'];
               

                foreach ($res as $key => $value) {

                  $rset .='<tr class="gradeX"  > 
                                    <td>'.$value['created_date'].'</td>

                  <td>'.$value['journal_id'].'</td>
                  <td>'.$value['description'].'</td>
                  <td>'.$value['transaction_id'].'</td>
                  <td>'.$value['transaction_type'].'</td>';

                  $side = $res[$key]['trial_balance_side']; 
                  $type = $res[$key]['transaction_type']; 
                  $amount = $value['amount']; 
                  if($side=='SIDE_A'){
                    if($type=='DR'){
                      $balance=$balance+$amount;
                      $rset .='<td>'.number_format($amount).'</td>
                      <td> </td>';              
                    }else{
                      $balance=$balance-$amount; 
                      $rset .='<td></td>
                      <td>'.number_format($amount).'</td>';      
                    }                
                  }else{
                    if($type=='DR'){
                      $balance=$balance-$amount; 
                      $rset .='<td>'.number_format($amount).'</td>
                      <td> </td>';          
                    }else{
                      $balance=$balance+$amount; 
                      $rset .='<td></td>
                      <td>'.number_format($amount).'</td>';                      
                    }       
                    
                  }
                 

                }

              }

    //end of outer foreach
    
    return $rset;
     //end of outer if     



  }
  /* CONTAINS ASSETS, EXPENSES,COST OF GOODS SOLD */

  function getGlaccountsA($data=null){
    $start=$data['startdon'];
    $end=$data['endon'];

    $from=date('Y-m-d',strtotime($start));
    $to=date('Y-m-d',strtotime($end));
    $office_id=$_SESSION['office'];
   
    $glcods=array();
  $topaccounts=array();

  $topaccountsArray = $this->GetSideCodes('A');
  $i=0;
  foreach($topaccountsArray as $key=>$v){
      $topaccounts[$i] = $v['parent'];
      $glcods[$i] = $v['gl_code'];
      $i++;
  }
  
    $iter=1;
    $sideAtotal=0;
    $rset=null;

    //$office_id=$_SESSION['office'];
    // $rset .='<table>';
    for($i=0;$i<count($topaccounts);$i++){  
//print_r($data);die();
      $header_value=$this->getSideAAccountValue($data,$glcods[$i]);

      $rset .='<tr class="gradeX"  > 
      <td style="font-size: 18px;"><b>'.$glcods[$i].'</b></td>
      <td style="font-size: 18px;"><b>'.$topaccounts[$i].'</b></td>';
      if($header_value<0){ 
        $rset .='<td style="font-size: 18px;"><b>'.number_format(abs($header_value)).' CR</b></td><td></td></tr>';
      }else{
        $rset .='<td style="font-size: 18px;"><b>'.number_format($header_value).'</b></td><td></td></tr>';

      }   
      $suheaders=$this->db->selectData("SELECT * FROM acc_ledger_account where classification='".$topaccounts[$i]."' AND account_usage='Heading' AND sacco_id ='".$_SESSION['office']."' order by  gl_code ASC");

      $counter=count($suheaders);
      if($counter>0){
       foreach ($suheaders as $key =>$headers) {  
         $code=$headers['gl_code'];
         $acc_value=$this->getSubAccountA($data,$code);  
         $rset .='<tr class="gradeX ss" >
         <td style="font-size: 14px;"><b>'.$headers['gl_code'].'</b></td>
         <td style="font-size: 14px;"><b>'.$headers['name'].'</b></td>';
         if($acc_value<0){ 
          $rset .='<td style="font-size: 14px;"><b>'.number_format(abs($acc_value)).' CR</b></td><td></td></tr>';
        }else{
          $rset .='<td style="font-size: 14px;"><b>'.number_format($acc_value).'</b></td><td></td></tr>';

        }  
        
        $result=$this->db->selectData("SELECT * FROM acc_ledger_account where classification='".$topaccounts[$i]."' AND gl_code LIKE '".rtrim($code,'0')."%' and account_usage='Account' AND sacco_id ='".$_SESSION['office']."' order by  gl_code ASC");
        $count=count($result);
        if($count>0){
         foreach ($result as $key => $values) {
          $acc=$values['id'];
          if(!empty($start)){
            $branches = $this->getSaccoBranches();
            if ($_SESSION['Isheadoffice'] == 'Yes') {
              if ($data['branch'] == 'all' || empty($branches)) {
                $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$acc."' AND office_id='".$office_id."' and DATE(created_date) between '".$from."' AND '".$to."' ");
              } else {
                $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$acc."' AND office_id='".$office_id."' AND branch_id = '".$data['branch']."' and DATE(created_date) between '".$from."' AND '".$to."' ");
              }  
            } else {
              $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$acc."' AND office_id='".$office_id."' AND branch_id='".$_SESSION['branchid']."' and DATE(created_date) between '".$from."' AND '".$to."' ");
            }  
          }else{
            if ($_SESSION['Isheadoffice'] == 'Yes') {
              $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$acc."' AND office_id='".$office_id."' "); 
            } else {
              $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$acc."' AND office_id='".$office_id."' AND branch_id = '".$_SESSION['branchid']."'"); 
            }
          }
          $counts=count($res);
          if($counts==0){
            $rset .='<tr class="gradeX ss" ><td>'.$values['gl_code'].'</td>
            <td>'.$values['name'].'</td><td>'."  ".'</td>
            <td>'."  ".'</td></tr>';          
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
            $sideAtotal=$sideAtotal+$balance;              
            $rset .='<tr class="gradeX ss" ><td>'.$values['gl_code'].'</td>';
            if($balance<0){ 
              $rset .='<td>'.$values['name'].'</td><td>'.number_format(abs($balance)).' CR</td>';
            }else{
              $rset .='<td>'.$values['name'].'</td><td>'.number_format($balance).'</td>';
            }
            $rset .='<td>'."  ".'</td></tr>';         
          }   
        }

 //print_r($rset);
// die();
        //return $rset;
      }
 } //loop for sub Headers
 }//if for sub Headers

}

return $rset;
}   

/* CONTAINS Liability, EQUITY OR SHARES,INCOMES */

function getGlaccountsB($data=null){

  $start=$data['startdon'];
  $end=$data['endon'];

  $from=date('Y-m-d',strtotime($start));
  $to=date('Y-m-d',strtotime($end));
  $office_id=$_SESSION['office'];
 
   $glcods=array();
  $topaccounts=array();

  $topaccountsArray = $this->GetSideCodes('B');
  $i=0;
  foreach($topaccountsArray as $key=>$v){
      $topaccounts[$i] = $v['parent'];
      $glcods[$i] = $v['gl_code'];
      $i++;
  }
 

  $iter=1;
  $sideBtotal=0;  

  $rset=null;
  for($i=0;$i<count($topaccounts);$i++){  
    $header_value=$this->getSideBAccountValue($data,$glcods[$i]);   
    $rset .='<tr class="gradeX"  > 
    <td style="font-size: 18px;"><b>'.$glcods[$i].'</b></td>
    <td style="font-size: 18px;"><b>'.$topaccounts[$i].'</b></td>
    <td></td>';
    if($header_value<0){ 
      $rset .='<td style="font-size: 18px;"><b>'.number_format(abs($header_value)).' DR</b></td></tr>';
    }else{
      $rset .='<td style="font-size: 18px;"><b>'.number_format($header_value).'</b></td></tr>';

    }                       

    $suheaders=$this->db->selectData("SELECT * FROM acc_ledger_account where classification='".$topaccounts[$i]."' AND account_usage='Heading' AND sacco_id ='".$_SESSION['office']."' order by  gl_code ASC");

    $counter=count($suheaders);
    if($counter>0){
     foreach ($suheaders as $key =>$headers) {  
       $code=$headers['gl_code'];

    // print_r($code);die();
       $acc_value=$this->getSubAccountB($data,$code);  
       $rset .='<tr class="gradeX ss" ><td style="font-size: 16px;"><b>'.$headers['gl_code'].'</b></td>
       <td style="font-size: 16px;"><b>'.$headers['name'].'</b></td><td>'."  ".'</td>';
       if($acc_value<0){ 
        $rset .='<td style="font-size: 14px;"><b>'.number_format(abs($acc_value)).' DR</b></td></tr>';
      }else{
        $rset .='<td style="font-size: 14px;"><b>'.number_format($acc_value).'</b></td></tr>';

      }


      $result=$this->db->selectData("SELECT * FROM acc_ledger_account where classification='".$topaccounts[$i]."' AND gl_code LIKE '".rtrim($code,'0')."%' and account_usage='Account' AND sacco_id ='".$_SESSION['office']."' order by  gl_code ASC");
      $count=count($result);
      if($count>0){
       foreach ($result as $key => $values) {
        $acc=$values['id'];

        if(!empty($start)){
//print_r($end);die(); AND office_id='".$office_id."'
        
          $branches = $this->getSaccoBranches();
          if ($_SESSION['Isheadoffice'] == 'Yes') {
            if ($data['branch'] == 'all' || empty($branches)) { 
              $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$acc."' AND office_id='".$office_id."' and DATE(created_date) between '".$from."' AND '".$to."' "); 
            } else {
              $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$acc."' AND office_id='".$office_id."' AND branch_id = '".$data['branch']."' and DATE(created_date) between '".$from."' AND '".$to."' "); 
            }
          } else {
            $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$acc."' AND office_id='".$office_id."' and branch_id = '".$_SESSION['branchid']."' and DATE(created_date) between '".$from."' AND '".$to."' ");
          }   

        }else{

          if ($_SESSION['Isheadoffice'] == 'Yes') {
            $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$acc."' AND office_id='".$office_id."' "); 
          } else {
            $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$acc."' AND office_id='".$office_id."' AND branch_id='".$_SESSION['branchid']."'");             
          }
        }
        $counts=count($res);
        if($counts==0){    
          $rset .='<tr class="gradeX ss" ><td>'.$values['gl_code'].'</td>
          <td>'.$values['name'].'</td><td>'."  ".'</td>
          <td>'."  ".'</td></tr>';      

        }else{
          $balance=0;
          foreach ($res as $key => $debits) {

            $type = $debits['transaction_type']; 
            $amount = $debits['amount']; 
            if($type=='DR'){
              $balance=$balance-$amount; 
            }else{
              $balance=$balance+$amount; 
            }
          }               

          $sideBtotal=$sideBtotal+$balance;           
          $rset .='<tr class="gradeX ss" ><td>'.$values['gl_code'].'</td>';
          if($balance<0){ 
            $rset .='<td>'.$values['name'].'</td><td>'.number_format(abs($balance)).' CR</td>';
          }else{
            $rset .='<td>'.$values['name'].'</td><td></td>';
          }
          $rset .='<td>'.number_format($balance).'</td></tr>';            
        }
      } 

    }
   //  print_r($rset);
 //die();
 } //loop for sub Headers
 }//if for sub Headers
}


return $rset;   

}   

/* CONTAINS  EQUITY OR SHARES,INCOMES */

function getIncomeAccounts($data=null){
  $start=$data['startdon'];
  $end=$data['endon'];

  $from=date('Y-m-d',strtotime($start));
  $to=date('Y-m-d',strtotime($end));

  $office_id=$_SESSION['office'];
    //AND created_date between '".$from."' AND '".$to."'
  $array=$this->db->selectData("SELECT * FROM acc_ledger_account where sacco_id = $office_id and classification='Incomes' order by gl_code ASC");
  $counter=count($array);
  if($counter>0){           
    for($t=0;$t<$counter;$t++) {
      if(!empty($start)){ 
        $branches = $this->getSaccoBranches();
        if ($_SESSION['Isheadoffice'] == 'Yes') {
          if ((isset($data['branch']) && $data['branch'] == 'all') || empty($branches)) { 
            $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' and DATE(created_date) between '".$from."' AND '".$to."' ");
          } else {
            $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' AND branch_id = '".$data['branch']."' and DATE(created_date) between '".$from."' AND '".$to."' ");
          }
        } else {
          $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' AND branch_id = '".$_SESSION['branchid']."' and DATE(created_date) between '".$from."' AND '".$to."' ");
        }
      }else{
        if ($_SESSION['Isheadoffice'] == 'Yes') { 
          $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' ");
        } else {
          $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' AND branch_id = '".$_SESSION['branchid']."'");
        }
      }
      $count=count($res);

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
/* CONTAINS  OPERATING EXPENSES */

function getExpenseAccounts($data=null){

  $start=$data['startdon'];
  $end=$data['endon'];

  $from=date('Y-m-d',strtotime($start));
  $to=date('Y-m-d',strtotime($end));
  $office_id=$_SESSION['office'];

    //AND created_date between '".$from."' AND '".$to."'
  $array=$this->db->selectData("SELECT * FROM acc_ledger_account where sacco_id = $office_id and classification='Expenses' order by gl_code ASC");
  $counter=count($array);     

  if($counter>0){      
    for($t=0;$t<$counter;$t++) {
      if(!empty($start)){    
        $branches = $this->getSaccoBranches();
        if ($_SESSION['Isheadoffice'] == 'Yes') {
          if ((isset($data['branch']) && $data['branch'] == 'all') || empty($branches)) {  
            $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' and DATE(created_date) between '".$from."' AND '".$to."' ");
          } else {
            $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' AND branch_id = '".$data['branch']."' and DATE(created_date) between '".$from."' AND '".$to."' ");
          }
        } else {
          $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' AND branch_id = '".$_SESSION['branchid']."' and DATE(created_date) between '".$from."' AND '".$to."' ");
        }
      }else{
        if ($_SESSION['Isheadoffice'] == 'Yes') {  
          $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' ");
        } else {
          $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' AND branch_id = '".$_SESSION['branchid']."' ");
        }
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

  $start=$data['startdon'];
  $end=$data['endon'];

  $from=date('Y-m-d',strtotime($start));
  $to=date('Y-m-d',strtotime($end));
  $office_id=$_SESSION['office'];
    //AND created_date between '".$from."' AND '".$to."'
  $array=$this->db->selectData("SELECT * FROM acc_ledger_account where sacco_id = $office_id and classification='Assets' order by gl_code ASC");
  $counter=count($array);
  for($t=0;$t<$counter;$t++) {
    if(!empty($start)){
      if ($_SESSION['Isheadoffice'] == 'Yes') {  
        if ($data['branch'] == 'all') {
          $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' and DATE(created_date) between '".$from."' AND '".$to."' ");
        } else {
          $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' AND branch_id = '".$data['branch']."' and DATE(created_date) between '".$from."' AND '".$to."' ");
        }
      } else {
        $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' AND branch_id = '".$_SESSION['branchid']."' and DATE(created_date) between '".$from."' AND '".$to."' ");
      }
    }else{
      if ($_SESSION['Isheadoffice'] == 'Yes') { 
        $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' ");
      } else {
        $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."'AND branch_id = '" .$_SESSION['branchid']."' ");
      }
    }

    $count=count($res);
    
    if($count==0){
     $rset[$t]['name'] =$array[$t]['name']; 
     $rset[$t]['gl_code'] =$array[$t]['gl_code']; 
     $rset[$t]['balance'] ="-";       
    // print_r($t."   ".$res);//die();    
   }else{
 //print_r("In");//die();         
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

  $start=$data['startdon'];
  $end=$data['endon'];

  $from=date('Y-m-d',strtotime($start));
  $to=date('Y-m-d',strtotime($end));
  $office_id=$_SESSION['office'];
    //AND created_date between '".$from."' AND '".$to."'
  $array=$this->db->selectData("SELECT * FROM acc_ledger_account where  sacco_id = $office_id and classification='Liabilities' order by gl_code ASC");
  $counter=count($array);
  for($t=0;$t<$counter;$t++) {
    if(!empty($start)){
      if ($_SESSION['Isheadoffice'] == 'Yes') { 
        if ($data['branch'] == 'all') {
          $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' and DATE(created_date) between '".$from."' AND '".$to."' ");
        } else {
          $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' AND branch_id = '".$data['branch']."' and DATE(created_date) between '".$from."' AND '".$to."' ");
        }
      } else {
        $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' AND branch_id = '".$_SESSION['branchid']."' and DATE(created_date) between '".$from."' AND '".$to."' ");
      }
    }else{
      if ($_SESSION['Isheadoffice'] == 'Yes') { 
        $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' ");
      } else {
        $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' AND branch_id = '".$_SESSION['branchid']."'' ");
      }
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

  $start=$data['startdon'];
  $end=$data['endon'];

  $from=date('Y-m-d',strtotime($start));
  $to=date('Y-m-d',strtotime($end));
  $office_id=$_SESSION['office'];
    //AND created_date between '".$from."' AND '".$to."'
  $array=$this->db->selectData("SELECT * FROM acc_ledger_account where sacco_id = $office_id and classification='Equity' order by gl_code ASC");
  
  $counter=count($array);
  for($t=0;$t<$counter;$t++) {
    if(!empty($start)){ 
      if ($_SESSION['Isheadoffice'] == 'Yes') {
        if ($data['branch'] == 'all') {
          $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' and DATE(created_date) between '".$from."' AND '".$to."' ");
        } else {          
          $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' AND branch_id = '".$data['branch']."' and DATE(created_date) between '".$from."' AND '".$to."' ");
        }
      } else {
        $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' AND branch_id = '".$_SESSION['branchid']."' and DATE(created_date) between '".$from."' AND '".$to."' ");
      }
    }else{
      if ($_SESSION['Isheadoffice'] == 'Yes') {
        $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' ");
      } else {
        $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' AND branch_id = '".$_SESSION['branchid']."'' ");
      }
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

function getRevenue($data=null){

  $start=$data['startdon'];
  $end=$data['endon'];

  $from=date('Y-m-d',strtotime($start));
  $to=date('Y-m-d',strtotime($end));
  $office_id=$data['office'];
    //AND created_date between '".$from."' AND '".$to."'
  $array=$this->db->selectData("SELECT * FROM acc_ledger_account where sacco_id = $office_id and classification='Incomes' order by gl_code ASC");
  
  $counter=count($array);
  for($t=0;$t<$counter;$t++) {
    if(!empty($start)){
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


function getSubAccountA($data=null,$gl_code){

  $start=$data['startdon'];
  $end=$data['endon'];

  $from=date('Y-m-d',strtotime($start));
  $to=date('Y-m-d',strtotime($end));
  $office_id=$_SESSION['office'];
  $total_balance=0;
 
    $array=$this->db->selectData("SELECT * FROM acc_ledger_account where gl_code LIKE '".rtrim($gl_code,'0')."%'  AND sacco_id='".$office_id."' order by gl_code ASC");

  
  $counter=count($array);
  for($t=0;$t<$counter;$t++) {
    if(!empty($start)){
      if ($_SESSION['Isheadoffice'] == 'Yes') {  
        $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' and created_date between '".$from."' AND '".$to."' ");
      } else {
        $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' AND branch_id = '".$_SESSION['branchid']."' and created_date between '".$from."' AND '".$to."' ");        
      }
    }else{
      if ($_SESSION['Isheadoffice'] == 'Yes') {  
        $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' ");
      } else {
        $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' AND branch_id = '".$_SESSION['branchid']."'");
      }
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

    $total_balance =$total_balance+$balance; 

  }
}

return $total_balance;
}

function getSubAccountB($data=null,$gl_code){

  $start=$data['startdon'];
  $end=$data['endon'];

  $from=date('Y-m-d',strtotime($start));
  $to=date('Y-m-d',strtotime($end));
  $office_id=$_SESSION['office'];
  $total_balance=0;
  $array=$this->db->selectData("SELECT * FROM acc_ledger_account where gl_code LIKE '".rtrim($gl_code,'0')."%'  AND sacco_id='".$office_id."' order by gl_code ASC");
  
  
  $counter=count($array);
  for($t=0;$t<$counter;$t++) {
    if(!empty($start)){
      if ($_SESSION['Isheadoffice'] == 'Yes') { 
        $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' and created_date between '".$from."' AND '".$to."' ");
      } else {
        $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' AND branch_id = '".$_SESSION['branchid']."' and created_date between '".$from."' AND '".$to."' ");
      }
    }else{
      if ($_SESSION['Isheadoffice'] == 'Yes') {
        $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' ");
      } else {
        $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' AND branch_id = '".$_SESSION['branchid']."' ");
      }
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

    $total_balance =$total_balance+$balance; 

  }
}

return $total_balance;
}

function getSideAAccountValue($data=null,$gl_code){

  $start=$data['startdon'];
  $end=$data['endon'];

  $from=date('Y-m-d',strtotime($start));
  $to=date('Y-m-d',strtotime($end));
  $office_id=$_SESSION['office'];

  $total_balance=0;
  $array=$this->db->selectData("SELECT * FROM acc_ledger_account where gl_code LIKE '".rtrim($gl_code,'0')."%' AND sacco_id ='".$_SESSION['office']."' order by gl_code ASC");
    //print_r($array);die();
  $counter=count($array);
  for($t=0;$t<$counter;$t++) {
    if(!empty($start)){
      if ($_SESSION['Isheadoffice'] == 'Yes') {
        $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' and DATE(created_date) between '".$from."' AND '".$to."' ");
      } else {
        $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' and branch_id ='".$_SESSION['branchid']."' and DATE(created_date) between '".$from."' AND '".$to."' ");
      }
    }else{
      if ($_SESSION['Isheadoffice'] == 'Yes') {
        $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' ");
      } else {
        $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' AND branch_id='".$_SESSION['branchid']."'");
      }
    }
    $count=count($res);

 //print_r($gl_code); die();      


    if($count==0){


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

      $total_balance =$total_balance+$balance; 

    }
  }
//print_r($total_balance);die();
  return $total_balance;
}

function getSideBAccountValue($data=null,$gl_code){

  $start=$data['startdon'];
  $end=$data['endon'];

  $from=date('Y-m-d',strtotime($start));
  $to=date('Y-m-d',strtotime($end));
  $office_id=$_SESSION['office'];
  $total_balance=0;
 // $array=$this->db->selectData("SELECT * FROM acc_ledger_account where gl_code LIKE '".rtrim($gl_code,'0')."%' AND sacco_id = '".."' order by gl_code ASC");
  $array=$this->db->selectData("SELECT * FROM acc_ledger_account where gl_code LIKE '".rtrim($gl_code,'0')."%' AND sacco_id ='".$_SESSION['office']."' order by gl_code ASC");

  $counter=count($array);
  for($t=0;$t<$counter;$t++) {
    if(!empty($start)){ 
      if ($_SESSION['Isheadoffice'] == 'Yes') {
        $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' and DATE(created_date) between '".$from."' AND '".$to."' ");
      } else {
        $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' and branch_id='".$_SESSION['branchid']."' and DATE(created_date) between '".$from."' AND '".$to."' ");
      }
    }else{
      if ($_SESSION['Isheadoffice'] == 'Yes') {
        $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' ");
      } else {
        $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' AND branch_id='".$_SESSION['branchid']."'");
      }
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

    $total_balance =$total_balance+$balance; 

  }
}

return $total_balance;
}

function getSideAAccountTotal($data=null){


  $start=$data['startdon'];
  $end=$data['endon'];

  $from=date('Y-m-d',strtotime($start));
  $to=date('Y-m-d',strtotime($end));
  $office_id=$_SESSION['office'];
  $branchID=$_SESSION['branchid'];
  $total_balance=0;

  $array=$this->db->selectData("SELECT * FROM acc_ledger_account where (classification='Assets' || classification='Expenses') and sacco_id = '$office_id' order by gl_code ASC");
  $counter=count($array);

  for($t=0;$t<$counter;$t++) {
    if(!empty($start)){ 
      if ($_SESSION['Isheadoffice'] == 'Yes') {
        if ($data['branch'] == 'all') { 
          $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' and DATE(created_date) between '".$from."' AND '".$to."' ");
        } else {
          $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' AND branch_id = '".$data['branch']."' and DATE(created_date) between '".$from."' AND '".$to."' ");
        }
      } else {        
        $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' and branch_id = '".$_SESSION['branchid']."' and DATE(created_date) between '".$from."' AND '".$to."' ");
      }
    }else{
      if ($_SESSION['Isheadoffice'] == 'Yes') {
        $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' ");
      } else {        
        $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' and branch_id = '".$_SESSION['branchid']."'");
      }
    }
    $count=count($res);
    
    if($count==0){


    }else{
      $balance=0;
      foreach ($res as $key => $value) {
        $type = $value['transaction_type']; 
        $amount = $value['amount']; 
        if($type=='DR'){
          $balance=$balance+$amount; 
        }else{
          $balance=$balance-$amount; 
        }
      }               

      $total_balance =$total_balance+$balance; 

    }
  }

  return $total_balance;
}


function getSideBAccountTotal($data=null){

  $start=$data['startdon'];
  $end=$data['endon'];

  $from=date('Y-m-d',strtotime($start));
  $to=date('Y-m-d',strtotime($end));
  $office_id=$_SESSION['office'];
  $total_balance=0;
  $array=$this->db->selectData("SELECT * FROM acc_ledger_account where (classification='Liabilities' || classification='Equity' || classification='Incomes') and sacco_id ='$office_id' order by gl_code ASC");
  
  $counter=count($array);
  for($t=0;$t<$counter;$t++) {
    if(!empty($start)){
      if ($_SESSION['Isheadoffice'] == 'Yes') {
        if ($data['branch'] == 'all') { 
          $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' and DATE(created_date) between '".$from."' AND '".$to."' ");
        } else {
          $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' AND branch_id = '".$data['branch']."' and DATE(created_date) between '".$from."' AND '".$to."' ");
        }
      } else {
        $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' and branch_id ='".$_SESSION['branchid']."' and DATE(created_date) between '".$from."' AND '".$to."' ");        
      }
    }else{
      if ($_SESSION['Isheadoffice'] == 'Yes') {
        $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' ");
      } else {
        $res=$this->db->selectData("SELECT * FROM acc_gl_journal_entry where account_id='".$array[$t]['id']."' AND office_id='".$office_id."' AND branch_id='".$_SESSION['branchid']."'");
      }
    }
    $count=count($res);
    if($count==0){ 
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

      $total_balance =$total_balance+$balance; 

    }
  }

  return $total_balance;
}

/* CONTAINS ASSETS, EXPENSES,COST OF GOODS SOLD */

function getGlheadersA($data=null){
    
  $start=$data['startdon'];
  $end=$data['endon'];

  $from=date('Y-m-d',strtotime($start));
  $to=date('Y-m-d',strtotime($end));
  $office_id=$_SESSION['office'];
 
  
  
    $glcods=array();
  $topaccounts=array();

  $topaccountsArray = $this->GetSideCodes('A');
  $i=0;
  foreach($topaccountsArray as $key=>$v){
      $topaccounts[$i] = $v['parent'];
      $glcods[$i] = $v['gl_code'];
      $i++;
  }
  
    //print_r($data['startdon']);die();
  $iter=1;
  $sideAtotal=0;

  $rset=null;
    // $rset .='<table>';
  for($i=0;$i<count($topaccounts);$i++){  
    $header_value=$this->getSideAAccountValue($data,$glcods[$i]);
    $rset .='<tr class="gradeX"  > 
    <td style="font-size: 18px;"><b>'.$glcods[$i].'</b></td>
    <td style="font-size: 18px;"><b>'.$topaccounts[$i].'</b></td>';
    if($header_value<0){ 
      $rset .='<td style="font-size: 18px;"><b>'.number_format(abs($header_value)).' CR</b></td><td></td></tr>';
    }else{
      $rset .='<td style="font-size: 18px;"><b>'.number_format($header_value).'</b></td><td></td></tr>';

    }   
    $suheaders=$this->db->selectData("SELECT * FROM acc_ledger_account where classification='".$topaccounts[$i]."' AND account_usage='Heading' order by  gl_code ASC");

    $counter=count($suheaders);
    if($counter>0){
     foreach ($suheaders as $key =>$headers) {  
       $code=$headers['gl_code'];
       $acc_value=$this->getSubAccountA($data,$code);  
       $rset .='<tr class="gradeX ss" >
       <td style="font-size: 14px;"><b>'.$headers['gl_code'].'</b></td>
       <td style="font-size: 14px;"><b>'.$headers['name'].'</b></td>';
       if($acc_value<0){ 
        $rset .='<td style="font-size: 14px;"><b>'.number_format(abs($acc_value)).' CR</b></td><td></td></tr>';
      }else{
        $rset .='<td style="font-size: 14px;"><b>'.number_format($acc_value).'</b></td><td></td></tr>';

      }  



 } //loop for sub Headers
 }//if for sub Headers

}

return $rset;
}   
function GetSideCodes($side){
     return $this->db->selectData("SELECT * FROM acc_ledger_account_main_headers where tb_side='".$side."' ");
}

function getWallets($office) {
  try {
      $results = $this->db->SelectData("SELECT * FROM sm_mobile_wallet AS a JOIN members AS b ON a.member_id = b.c_id WHERE a.bank_no = '$office'");
      return $results;
  } catch (Exception $e) {
      throw new Exception("Failed to fetch wallet data: " . $e->getMessage());
  }
}

/* CONTAINS Liability, EQUITY OR SHARES,INCOMES */

function getGlheadersB($data=null){

  $start=$data['startdon'];
  $end=$data['endon'];

  $from=date('Y-m-d',strtotime($start));
  $to=date('Y-m-d',strtotime($end));
  $office_id=$_SESSION['office'];
   
  $glcods=array();
  $topaccounts=array();

  $topaccountsArray = $this->GetSideCodes('B');
  $i=0;
  foreach($topaccountsArray as $key=>$v){
      $topaccounts[$i] = $v['parent'];
      $glcods[$i] = $v['gl_code'];
      $i++;
  }

  $iter=1;
  $sideBtotal=0;  

  $rset=null;
  for($i=0;$i<count($topaccounts);$i++){  
    $header_value=$this->getSideBAccountValue($data,$glcods[$i]);   
    $rset .='<tr class="gradeX"  > 
    <td style="font-size: 18px;"><b>'.$glcods[$i].'</b></td>
    <td style="font-size: 18px;"><b>'.$topaccounts[$i].'</b></td>
    <td></td>';
    if($header_value<0){ 
      $rset .='<td style="font-size: 18px;"><b>'.number_format(abs($header_value)).' DR</b></td></tr>';
    }else{
      $rset .='<td style="font-size: 18px;"><b>'.number_format($header_value).'</b></td></tr>';

    }                       

    $suheaders=$this->db->selectData("SELECT * FROM acc_ledger_account where classification='".$topaccounts[$i]."' AND account_usage='Heading' order by  gl_code ASC");

    $counter=count($suheaders);
    if($counter>0){
     foreach ($suheaders as $key =>$headers) {  
       $code=$headers['gl_code'];

    // print_r($code);die();
       $acc_value=$this->getSubAccountB($data,$code);  
       $rset .='<tr class="gradeX ss" ><td style="font-size: 14px;"><b>'.$headers['gl_code'].'</b></td>
       <td style="font-size: 14px;"><b>'.$headers['name'].'</b></td><td>'."  ".'</td>';
       if($acc_value<0){ 
        $rset .='<td style="font-size: 14px;"><b>'.number_format(abs($acc_value)).' DR</b></td></tr>';
      }else{
        $rset .='<td style="font-size: 14px;"><b>'.number_format($acc_value).'</b></td></tr>';

      }


 } //loop for sub Headers
 }//if for sub Headers
}


return $rset;   

}   


}

