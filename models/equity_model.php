<?php

class Equity_model extends Model{
	
public function __construct(){
	parent::__construct();
	$this->logUserActivity(NULL); 
	if (!$this->checkTransactionStatus()) {
		header('Location: ' . URL); 
	}
 
}

function preparememberinfo($actno){
$office=$_SESSION['office'];

	$result=  $this->db->selectData("SELECT * FROM members WHERE c_id='".$actno."' and 	office_id='".$office."' and status='Active'");
	if(count($result)>0){

	$rset=array();
		if(!empty($result[0]['company_name'])){
	$displayname=$result[0]['company_name'];	 
	 }else{
	$displayname=$result[0]['firstname']." ".$result[0]['middlename']." ".$result[0]['lastname'];
	 }
	foreach ($result as $key => $value) {
		array_push($rset,array(
		'member_id'=>$result[$key]['c_id'],
		'displayname'=>$displayname,
		'dob'=>$result[0]['date_of_birth'],
		'national_id'=>$result[0]['national_id'],
		'address'=>$result[0]['address'],		
		));
          }

echo json_encode(array("result" =>$rset));
die();
}else{
		$rset=array();
		array_push($rset,array(
		'member_id'=>'0',
		));
echo json_encode(array("result" =>$rset));		
	die();
}
	


}

function getshareProductstoapply($id){
 
  $client_pdt= $this->db->SelectData("SELECT DISTINCT(product_id) AS product_id FROM  share_account where member_id='".$id."' and account_status!='Activate'");

		$no =count($client_pdt);

		$add=null;
		if($no>0){
			
		for($i=0;$i<$no;$i++){
				$add .= " AND id!='".$client_pdt[$i]["product_id"]."'";		
		}

		$office_id = $_SESSION['office'];

		$pdts= $this->db->SelectData("SELECT * FROM share_products where office_id = '".$office_id."' and product_status='Active' $add");
		if(count($pdts)>0){
	$option = '<option value="">---Select Product---</option>';
	  foreach ($pdts as $key => $value) {
      $option .= '<option value="' . $value['id'] . '">' . $value['share_name'] . '</option>';
        }
print_r($option);
die();
		}else{
		
		}

		}else{

		$office_id = $_SESSION['office'];
		$pdts= $this->db->SelectData("SELECT * FROM share_products where product_status='Active' AND office_id = '".$office_id."'");
			
		if(count($pdts)>0){
	$option = '<option value="">---Select Product---</option>';
	  foreach ($pdts as $key => $value) {
      $option .= '<option value="' . $value['id'] . '">' . $value['share_name'] . '</option>';
        }
print_r($option);
die();
		}else{
		
		}		}

}


function getshareproduct($id){

$product= $this->db->selectData("SELECT * FROM share_products WHERE id='".$id."' ");

	if(count($product)>0){
	$rset=array();
	foreach ($product as $key => $value) {
		array_push($rset,array(
		'id'=>$product[0]['id'],
		'name'=>$product[0]['share_name'],
		'share_cost'=>$product[0]['amount_per_share'],
		//'interest_rate'=>$product[0]['dividend_rate'],
        ));		
          }
echo json_encode(array("result" =>$rset));
die();
	}
}



// function submitshareapplication($data){

// 	$name=null;
// 	$office = $_SESSION['office'];
// 	$str=date('isH').rand();		
// 	$acc_no= substr($office.$data['cid'].$data['product_id'].substr($str,0,7),0,11);
// 	$client_details = $this->getMember($data['cid']);
// 	$share_product=$this->getProduct($data['product_id']);
// 	$amount=str_replace(",","",$data['amount']);

// 	$prodType = 1;		 
// 	$mapping = $this->GetGLPointers($data['product_id'],$prodType,'Purchase Shares');

// 	if (empty($mapping)) {
// 		header('Location: ' . URL . 'products/shares?msg=pur'); 
// 		die();
// 	}
    
//     if(count($share_product)>0){
		
// 		$share_cost=$share_product[0]['amount_per_share'];
// 		$total_shares=($amount/$share_cost);

// 		$transaction_postData = array(
// 			'share_account_no' => $acc_no,
// 			'branch' => $office,
// 			'no_of_shares' => $data['total_shares'],			 
// 			'amount' =>$amount,
// 			'amount_in_words' => $data['amount_in_words'],
// 			'running_balance' =>$amount,
// 			'recorded_by' =>$_SESSION['user_id'],
// 		);

// 		$updated_on=date('Y-m-d H:i:s');

// 		$share_transaction_id = $this->db->InsertData('share_account_transaction', $transaction_postData);

// 		if(!empty($share_transaction_id)){
// 			$share_postData = array(
// 				'office_id' => $_SESSION['office'],
// 				'share_account_no' => $acc_no,
// 				'member_id' => $data['cid'],
// 				'submittedon_userid' => $_SESSION['user_id'],
// 				'account_status' =>'Active',
// 				'product_id' => $data['product_id'],
// 				'total_shares' =>$total_shares,
// 				'running_balance' =>$amount,
// 				'last_updated_on' => $updated_on,
// 			);
			
// 			$mapping = $this->GetGLPointers($data['product_id'],$prodType,'Purchase Shares'); 

// 			$this->db->InsertData('share_account', $share_postData);
			
// 			$trans_uniqid=uniqid();
// 			$deposit_transaction_uniqid = $share_transaction_id."".$trans_uniqid;
// 			$transaction_id = "SH".$deposit_transaction_uniqid;

// 			if(!empty($mapping[0]["debit_account"])&&!empty($mapping[0]["credit_account"])){
			    
// 				$debt_id=$mapping[0]["debit_account"];	
// 				$credit_id=$mapping[0]["credit_account"];		
// 				$sideA=$this->getAccountSide($debt_id);
// 				$sideB=$this->getAccountSide($credit_id);
// 				if(empty($client_details[0]['company_name'])){
// 					$name=$client_details[0]['firstname']." ".$client_details[0]['middlename']." ".$client_details[0]['lastname']; 	
// 				}else{
// 					$name=$client_details[0]['company_name'];		
// 				}

// 				$description="Shares Bought by ".$name;	
// 				$new_data['transaction_id'] = $transaction_id;
// 				$this->db->UpdateData('share_account_transaction', $new_data,"`share_trans_id` = '{$share_transaction_id}'");
				
// 				$this->makeJournalEntry($debt_id,$office,$_SESSION['user_id'],$share_transaction_id,$transaction_id,$amount,'DR',$sideA,$description);
// 				$this->makeJournalEntry($credit_id,$office,$_SESSION['user_id'],$share_transaction_id,$transaction_id,$amount,'CR',$sideB,$description);
// 			}

// 			header('Location: ' . URL . 'equity/newshareapplication?acc='.$acc_no);
// 		}
// 	}else{
// 		header('Location: ' . URL . 'equity/newshareapplication?msg=fail');
// 	}
// }

function submitshareapplication($data, $office, $user_id, $branchid) {
    try {
        $name = null;
        $str = date('isH') . rand();
        $acc_no = substr($office . $data['cid'] . $data['product_id'] . substr($str, 0, 7), 0, 11);
        $client_details = $this->getMember($data['cid']);
        $share_product = $this->getProduct($data['product_id']);
        $amount = str_replace(",", "", $data['amount']);

        $prodType = 1;
		$id = $data['product_id'];
		$transtype = 'Purchase Shares';
        $mapping = $this->GetGLPointers($id,$prodType,$transtype, $office);
		

        if (empty($mapping)) {
            throw new Exception("GL Pointers not found.");
        }

        if (count($share_product) > 0) {
            $share_cost = $share_product[0]['amount_per_share'];
            $total_shares = ($amount / $share_cost);

            $transaction_postData = array(
                'share_account_no' => $acc_no,
                'branch' => $office,
                'no_of_shares' => $data['total_shares'],
                'amount' => $amount,
                'amount_in_words' => $data['amount_in_words'],
                'running_balance' => $amount,
                'recorded_by' => $user_id,
            );

            $updated_on = date('Y-m-d H:i:s');
            $share_transaction_id = $this->db->InsertData('share_account_transaction', $transaction_postData);

            if (!empty($share_transaction_id)) {
                $share_postData = array(
                    'office_id' => $office,
                    'share_account_no' => $acc_no,
                    'member_id' => $data['cid'],
                    'submittedon_userid' => $user_id,
                    'account_status' => 'Active',
                    'product_id' => $data['product_id'],
                    'total_shares' => $total_shares,
                    'running_balance' => $amount,
                    'last_updated_on' => $updated_on,
                );

                $this->db->InsertData('share_account', $share_postData);

                $trans_uniqid = uniqid();
                $deposit_transaction_uniqid = $share_transaction_id . "" . $trans_uniqid;
                $transaction_id = "SH" . $deposit_transaction_uniqid;

                if (!empty($mapping[0]["debit_account"]) && !empty($mapping[0]["credit_account"])) {
                    $debt_id = $mapping[0]["debit_account"];
                    $credit_id = $mapping[0]["credit_account"];
                    $sideA = $this->getAccountSide($debt_id);
                    $sideB = $this->getAccountSide($credit_id);
                    if (empty($client_details[0]['company_name'])) {
                        $name = $client_details[0]['firstname'] . " " . $client_details[0]['middlename'] . " " . $client_details[0]['lastname'];
                    } else {
                        $name = $client_details[0]['company_name'];
                    }

                    $description = "Shares Bought by " . $name;
                    $new_data['transaction_id'] = $transaction_id;
                    $this->db->UpdateData('share_account_transaction', $new_data, "`share_trans_id` = '{$share_transaction_id}'");

                    $acc_id = $debt_id;
					$user = $user_id;
					$share_trans_id = $share_transaction_id;
					$trans_id = $transaction_id;
					$type = 'DR';
					$side = $sideA;

                    $this->makeJournalEntry($acc_id, $office, $branchid, $user, $share_trans_id, $trans_id, $amount, $type, $side, $description);

					$acc_id = $credit_id;
					$user = $user_id;
					$share_trans_id = $share_transaction_id;
					$trans_id = $transaction_id;
					$type = 'CR';
					$side = $sideA;
                    $this->makeJournalEntry($acc_id, $office, $branchid, $user, $share_trans_id, $trans_id, $amount, $type, $side, $description);
                }

                return array("transaction_id" => $transaction_id);
            } else {
                throw new Exception("Error submitting share application.");
            }
        } else {
            throw new Exception("Share product not found.");
        }
    } catch (Exception $e) {
        throw new Exception("Error submitting share application: " . $e->getMessage());
    }
}
 
function addshares($data){

	$office = $_SESSION['office'];
	$share_account=$this->db->SelectData("SELECT * FROM share_account WHERE share_account_no='".$data['account_no']."'");

	$share_product=$this->getProduct($share_account[0]['product_id']);
	$client_details = $this->getMember($share_account[0]['member_id']);

	if(count($share_product)>0){
		$share_cost=$share_product[0]['amount_per_share'];
		$updated_on=date('Y-m-d H:i:s');
		$amount=str_replace(",","",$data['amount']);
		$total_shares=($amount/$share_cost);
		$newshares=$share_account[0]['total_shares']+$total_shares;
		$newbalance=$share_account[0]['running_balance']+$amount;
		$acc= $data['account_no'];

		$transaction_postData = array(
			'share_account_no' => $data['account_no'],
			'branch' => $office,
			'no_of_shares' =>$total_shares,			 
			'amount' =>$amount,
			'amount_in_words' => $data['amount_in_words'],
			'running_balance' =>$newbalance,
			'recorded_by' =>$_SESSION['user_id'],
		);

		$prodType=1;
		$mapping = $this->GetGLPointers($share_product[0]['id'],$prodType,'Purchase Shares'); 

		if (empty($mapping)) {
			header('Location: ' . URL . 'products/shares?msg=pur'); 
			die();
		}
	
		$share_transaction_id = $this->db->InsertData('share_account_transaction', $transaction_postData);
		
		if(!empty($share_transaction_id)){

			$share_postData = array(
             'total_shares' =>$newshares,
			 'running_balance' => $newbalance,
			 'last_updated_on' => $updated_on,
			);

			$this->db->UpdateData('share_account',$share_postData, "`share_account_no` = {$acc}");

			$trans_uniqid=uniqid();

			$deposit_transaction_uniqid = $share_transaction_id."".$trans_uniqid;

			$transaction_id = "SH".$deposit_transaction_uniqid;

			if(!empty($mapping[0]["debit_account"])&&!empty($mapping[0]["credit_account"])){
				$debt_id=$mapping[0]["debit_account"];	
				$credit_id=$mapping[0]["credit_account"];	
				$sideA=$this->getAccountSide($debt_id);
				$sideB=$this->getAccountSide($credit_id);	
				
				if(empty($client_details[0]['company_name'])){
					$name=$client_details[0]['firstname']." ".$client_details[0]['middlename']." ".$client_details[0]['lastname']; 	
				}else{
					$name=$client_details[0]['company_name'];		
				}

				$description="Shares Bought by ".$name;	

				$new_data['transaction_id'] = $transaction_id;
				$this->db->UpdateData('share_account_transaction', $new_data,"`share_trans_id` = '{$share_transaction_id}'");

				$this->makeJournalEntry($debt_id,$office,$_SESSION['user_id'],$share_transaction_id,$transaction_id,$amount,'DR',$sideA,$description);//DEBIT
				$this->makeJournalEntry($credit_id,$office,$_SESSION['user_id'],$share_transaction_id,$transaction_id,$amount,'CR',$sideB,$description);//CREDIT
			}

			header('Location: ' . URL . 'equity/buyshares?msg=success');
		}else{
			header('Location: ' . URL . 'equity/buyshares?msg=fail');
		}
	}else{
		header('Location: ' . URL . 'equity/buyshares?msg=fail'); 		
	}
}
function makeJournalEntry($acc_id,$office, $branchid, $user,$share_trans_id,$trans_id,$amount,$type,$side,$description){
		$postData = array(
			'account_id' =>$acc_id,
			'office_id' => $office,
			'branch_id' => $branchid,
			'createdby_id' =>$user,
			'share_capital_transaction_id' => $share_trans_id,
			'transaction_id' =>$trans_id,
			'amount' => $amount,
           	'transaction_type' =>$type,
            'trial_balance_side' =>$side,							
            'description' =>$description,							
        );

 
		$this->db->InsertData('acc_gl_journal_entry', $postData);	
	
}


function sellshares($data){

	$office = $_SESSION['office'];
	$share_account_give=$this->db->SelectData("SELECT * FROM share_account WHERE share_account_no='".$data['account_no']."'");
	$share_account_receive=$this->db->SelectData("SELECT * FROM share_account WHERE share_account_no='".$data['account_rec']."'");

	$share_product_r=$this->getProduct($share_account_give[0]['product_id']);
	$client_details_g = $this->getMember($share_account_give[0]['member_id']);
	$client_details_r = $this->getMember($share_account_receive[0]['member_id']);
	
	if(count($share_product_r)>0&&count($share_account_give)>0&&count($share_account_receive)>0){

		$share_cost=$share_product_r[0]['amount_per_share']; 
		$sold_amt=$share_cost*($data['trans_shares']);

		if($sold_amt<=($share_account_give[0]['running_balance'])){
			$newshares=$share_account_give[0]['total_shares']-$data['trans_shares'];
			$newbalance_left=$share_account_give[0]['running_balance']-$sold_amt;
			$acc= $data['account_no'];
			$updated_on=date('Y-m-d H:i:s');

			$share_postData_s = array(
             'total_shares' =>$newshares,
			 'running_balance' => $newbalance_left,
			 'last_updated_on' => $updated_on,
			);

			$newshares_gain=$share_account_receive[0]['total_shares']+$data['trans_shares'];
			$newbalance_gain=$share_account_receive[0]['running_balance']+$sold_amt;
			$acc_r= $data['account_rec'];
			$share_postData_r = array(
             'total_shares' =>$newshares_gain,
			 'running_balance' => $newbalance_gain,
			 'last_updated_on' => $updated_on,
			);

            $transaction_postData_s = array(
             'share_account_no' => $data['account_no'],
             'branch' => $office,
             'transaction_type' =>'Transfered Shares',
             'tansfer_to' =>$data['account_rec'],
             'no_of_shares' =>$data['trans_shares'],			 
             'amount' =>$sold_amt,
             'running_balance' =>$newbalance_left,
             'recorded_by' =>$_SESSION['user_id'],
         	); 
		
			$transaction_postData_r = array(
             'share_account_no' => $data['account_rec'],
             'branch' => $office,
             'transaction_type' =>'Bought Shares',			 
             'bought_from' => $data['account_no'],
             'no_of_shares' =>$data['trans_shares'],			 
             'amount' =>$sold_amt,
             'running_balance' =>$newbalance_gain,
             'recorded_by' =>$_SESSION['user_id'],
         	);

         	$share_transaction_id_s = $this->db->InsertData('share_account_transaction', $transaction_postData_s);
         	$share_transaction_id_r = $this->db->InsertData('share_account_transaction', $transaction_postData_r);

			if(!empty($share_transaction_id_r)&&!empty($share_transaction_id_s)){

				$this->db->UpdateData('share_account',$share_postData_s, "`share_account_no` = {$acc}");
				$this->db->UpdateData('share_account',$share_postData_r, "`share_account_no` = {$acc_r}");
	
				$trans_uniqid=uniqid();

				$deposit_transaction_s = $share_transaction_id_s."".$trans_uniqid;
				$deposit_transaction_r = $share_transaction_id_r."".$trans_uniqid;
				$prodType=1;
	
				// CURRENTLY NOT POSTING. WAITING FOR JOURNAL CONFIRMATION
				$mapping = $this->GetGLPointers($share_account_give[0]['product_id'],$prodType,'Transfer shares');
				print_r($mapping);die();	
				$transaction_id_s = "SH".$deposit_transaction_s;
				$transaction_id_r = "SH".$deposit_transaction_r;

				$new_data_s['transaction_id'] = $transaction_id_s;
				$new_data_r['transaction_id'] = $transaction_id_r;
				$this->db->UpdateData('share_account_transaction', $new_data_s,"`share_trans_id` = '{$share_transaction_id_s}'");
				$this->db->UpdateData('share_account_transaction', $new_data_r,"`share_trans_id` = '{$share_transaction_id_r}'");

				if(!empty($mapping[0]["debit_account"])&&!empty($mapping[0]["credit_account"])){
					$debt_id=$mapping[0]["debit_account"];	
					$credit_id=$mapping[0]["credit_account"];		
					$sideA=$this->getAccountSide($debt_id);
					$sideB=$this->getAccountSide($credit_id);	
					//selling
					if(empty($client_details_g[0]['company_name'])){
						$name_g=$client_details_g[0]['firstname']." ".$client_details_g[0]['middlename']." ".$client_details_g[0]['lastname']; 	
					}else{
						$name_g=$client_details_g[0]['company_name'];		
					}
		
					if(empty($client_details_r[0]['company_name'])){
						$name_r=$client_details_r[0]['firstname']." ".$client_details_r[0]['middlename']." ".$client_details_r[0]['lastname']; 	
					}else{
						$name_r=$client_details_r[0]['company_name'];		
					}

					$description=$name_g." Sold Shares to ".$name_r;

					$this->makeJournalEntry($debt_id,$office,$_SESSION['user_id'],$share_transaction_id_s,$transaction_id_s,$sold_amt,'DR',$sideA,$description);
					$this->makeJournalEntry($credit_id,$office,$_SESSION['user_id'],$share_transaction_id_s,$transaction_id_s,$sold_amt,'CR',$sideB,$description);

					//buying
					//$this->makeJournalEntry($credit_id,$office,$_SESSION['user_id'],$share_transaction_id_r,$transaction_id_r,$sold_amt,'DR',$sideB);
					//$this->makeJournalEntry($debt_id,$office,$_SESSION['user_id'],$share_transaction_id_r,$transaction_id_r,$sold_amt,'CR',$sideA);
				}
				header('Location: ' . URL . 'equity/transfer?msg=success');
			}else{
				header('Location: ' . URL . 'equity/transfer?msg=fail');
			}
		}else{
			header('Location: ' . URL . 'equity/transfer?msg=fail');
		}
	}else{
		header('Location: ' . URL . 'equity/transfer?msg=fail');
	}
}

function ShareHoldersLists($office) {
    try {
        $result = $this->db->SelectData("SELECT * FROM share_account JOIN members on share_account.member_id=members.c_id where members.office_id='" . $office . "' ");
        $count = count($result);

        if ($count > 0) {
            $shareholders = array();

            foreach ($result as $key => $value) {
                $shareholder = array(
                    'member' => $value['c_id'],
                    'account_no' => $value['share_account_no'],
                    'office_id' => $value['office_id'],
                    'office' => $this->getoffice($value['office_id']),
                    'shares' => $value['total_shares'],
                    'opened' => $value['submittedon_date'],
                    'amount' => $value['running_balance'],
                    'status' => $value['account_status']
                );

                if (empty($value['company_name'])) {
                    $shareholder['name'] = $value['firstname'] . " " . $value['middlename'] . " " . $value['lastname'];
                } else {
                    $shareholder['name'] = $value['company_name'];
                }

                $shareholders[] = $shareholder;
            }

            return $shareholders;
        } else {
            throw new Exception("No shareholders found.");
        }

    } catch (Exception $e) {
        throw new Exception("Error retrieving shareholders: " . $e->getMessage());
    }
}


function getShareHolder($acc){
	
  $result= $this->db->SelectData("SELECT * FROM share_account JOIN members on share_account.member_id=members.c_id where members.office_id='".$_SESSION['office']."' and share_account.share_account_no='".$acc."' ");

 if(count($result)>0){
		  foreach ($result as $key => $value) {  
				if(empty($value['company_name'])){
              $rset[$key]['name'] = $result[$key]['firstname']." ".$result[$key]['middlename']." ".$result[$key]['lastname'];
                }else{
			   $rset[$key]['name'] = $value['company_name'];
			   }		  
                $rset[$key]['member'] = $result[$key]['member_id'];
                $rset[$key]['account'] = $result[$key]['share_account_no'];
				$rset[$key]['office_id'] = $result[$key]['office_id']; 
				$rset[$key]['office'] = $this->getoffice($result[$key]['office_id']); 
                $rset[$key]['amount'] = $result[$key]['running_balance'];
                $rset[$key]['shares'] = $result[$key]['total_shares'];
                $rset[$key]['status'] = $result[$key]['account_status'];
                $rset[$key]['image'] = $result[$key]['image'];
			}
        return $rset;
  }
	
}
function getShareTransactions($acc){
	$office=$_SESSION['office'];
	  $result =  $this->db->SelectData("SELECT * FROM share_account_transaction where share_account_no='".$acc."' ");
            foreach ($result as $key => $value) {  
                $rset[$key]['amount'] = $result[$key]['amount'];
                $rset[$key]['transaction_type']=$result[$key]['transaction_type'];
                $rset[$key]['no_of_shares']=$result[$key]['no_of_shares'];
                $rset[$key]['balance'] = $result[$key]['running_balance'];
                $rset[$key]['tansfer'] = $result[$key]['tansfer_to'];
                $rset[$key]['transaction_date'] = $result[$key]['transaction_date'];
			    }
        return $rset;
}
function searchaccount($data){

$acc=$data['accno'];

$fname=$data['fname'];

$lname=$data['lname'];
 $ch= $this->db->selectData("SELECT * FROM m_savings_account WHERE account_no LIKE '%".$acc."%'");	

	if(count($ch)>0){

	foreach ($ch as $key => $value) {		

	$members=$this->getMember($ch[$key]['member_id']);	
  if($members[0]['status']=='Active'){
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

$search= $this->db->selectData("SELECT * FROM members WHERE firstname LIKE '%".$fname."%' || company_name LIKE '%".$fname."%' || lastname LIKE '%".$lname."%' || middlename LIKE '%".$lname."%' ");	

 if(count($search)>0){

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

function getcurrecy($id){
	
	$result =$this->db->SelectData("SELECT * FROM m_currency where id='".$id."' ");
   return $result[0]['name'];
	
}

function getoffice($id){
	$result =$this->db->SelectData("SELECT * FROM m_branch where id='".$id."'");
	
   return $result[0]['name'];
	
}
	
function DeleteAccount($data){
$acc=$data['account_no'];
$date=date('Y-m-d');
		$postData = array(
			'closedon_date' =>$date,
			'closedon_userid' =>$_SESSION['user_id'],
			'closesure_reason' =>$data['reason'],
			'account_status' =>'Closed',
        );

 $this->db->UpdateData('share_account', $postData,"`share_account_no` = '{$acc}'");
 header('Location: ' . URL . 'equity/sharesaccount?account=closed'); 
	
}	

function getMember($id){

   return $this->db->SelectData("SELECT * FROM members c INNER JOIN m_branch b where c.office_id  = b.id AND c.c_id='".$id."'  order by c.c_id desc");

}


function getProduct($id){

   return $this->db->SelectData("SELECT * FROM share_products  where id='".$id."'");

}


function getshareaccount($acc){
	$result= $this->db->selectData("SELECT * FROM share_account WHERE share_account_no='".$acc."' and account_status='Active'");
	if(count($result)>0){
		$member=$this->getMember($result[0]['member_id']);
		if(empty($member[0]['company_name'])){
			$name = $member[0]['firstname']." ".$member[0]['middlename']." ".$member[0]['lastname'];
		}else{
			$name = $member[0]['company_name'];
		}

		$product=$this->getProduct($result[0]['product_id']);
		$rset=array();
		foreach ($result as $key => $value) {
			array_push($rset,array(
				'id'=>$result[0]['id'],
				'member_id'=>$member[0]['c_id'],
				'dob'=>date('d-m-Y',strtotime($member[0]['date_of_birth'])),
				'address'=>$member[0]['address'],
				'nid'=>$member[0]['national_id'],
				'name'=>$name,
				'accounts'=>$result[0]['share_account_no'],
				'shares'=>$result[0]['total_shares'],
				'share_cost'=>$product[0]['amount_per_share'],
				'dividends'=>$result[0]['total_dividends_earned'],
				'amount'=>$result[0]['running_balance'],
				'acc_update_date'=>date('M j Y g:i A',strtotime($result[0]['last_updated_on'])),
				'acc_status'=>$result[0]['account_status'],
	        ));
		}
		echo json_encode(array("result" =>$rset));
		die();
	}else{
		$rset=array();
		array_push($rset,array(
			'member_id'=>0,
         ));
		echo json_encode(array("result" =>$rset));
		die();		
	}
	
}
function getshareaccountclosed($acc){
	
	 $name=null;
$result= $this->db->selectData("SELECT * FROM share_account WHERE share_account_no='".$acc."' and account_status='Closed' ");
		if(count($result)>0){
$member=$this->getMember($result[0]['member_id']);	
			if(empty($member[0]['company_name'])){
              $name = $member[0]['firstname']." ".$member[0]['middlename']." ".$member[0]['lastname'];
                }else{
			   $name = $member[0]['company_name'];
			   }			
$product=$this->getProduct($result[0]['product_id']);				
	$rset=array();
	foreach ($result as $key => $value) {
		array_push($rset,array(
		'id'=>$result[0]['id'],
		'member_id'=>$member[0]['c_id'],
		'dob'=>$member[0]['date_of_birth'],
		'address'=>$member[0]['address'],
		'nid'=>$member[0]['national_id'],
		'name'=>$name,
		'accounts'=>$result[0]['share_account_no'],
		'shares'=>$result[0]['total_shares'],
		'share_cost'=>$product[0]['amount_per_share'],
		'dividends'=>$result[0]['total_dividends_earned'],
		'amount'=>$result[0]['running_balance'],
		'acc_status'=>$result[0]['account_status'],
        ));		
          }
echo json_encode(array("result" =>$rset));
die();
	}else{
		$rset=array();
	array_push($rset,array(
		'member_id'=>0,
         ));		
echo json_encode(array("result" =>$rset));
die();		
	}
	
}
function getsharestransaction($acc,$transno,$tdate){

	$today=date('Y-m-d');
	if($tdate==null){
	  $result =  $this->db->SelectData("SELECT * FROM share_account_transaction where share_account_no='".$acc."' and transaction_id='".$transno."' ");

	  }else{ 
   $trans_date= date('Y-m-d',strtotime(str_replace('-','/',$tdate)));

	 $result =  $this->db->SelectData("SELECT * FROM share_account_transaction where share_account_no='".trim($acc)."' and transaction_id='".trim($transno)."' and date(transaction_date)='".$trans_date."'");
	
	 
	 }
   if(count($result)>0){  
       print_r($result[0]['amount']);
	   die();
	   }
	
}
function getpendingtransaction($acc,$transno){
//$result=null;


	  $result =  $this->db->SelectData("SELECT * FROM share_account_transaction where share_account_no='".$acc."' and share_trans_id='".$transno."' and transaction_status='Pending' ");


   if(count($result)>0){  
       print_r($result[0]['amount']);
	   die();
	   }
	
}
function reversesharestransaction($data){

	$this->db->beginTransaction();
$new_balance=0;
$tansno=$data['tnumber'];
$tansamount=$data['tamount'];
$accno=$data['account_no'];

	  $result =  $this->db->SelectData("SELECT * FROM share_account_transaction where share_trans_id='".$tansno."' and transaction_reversed='No' ");
	 $account_b =  $this->db->SelectData("SELECT * FROM share_account where share_account_no='".$accno."'");

	 if((count($result)>0)&&(count($account_b)>0)){
	
		$tansaction_type=$result[0]['transaction_type'];
		$bought=$result[0]['bought_from'];
		$transfer=$result[0]['tansfer_to'];
	$trans_shares=$result[0]['no_of_shares'];
		$trans_amount=$result[0]['amount'];
		$trans_running_balance=($result[0]['running_balance'])-($result[0]['amount']);	
	$account_running_balance=($account_b[0]['running_balance'])-($result[0]['amount']);

	try{
		
	if($tansaction_type=='Bought Shares'){
	$trans_running_balance=($result[0]['running_balance'])-($result[0]['amount']);	
	$account_running_balance=($account_b[0]['running_balance'])-($result[0]['amount']);	
	$total_shares=($account_b[0]['total_shares'])-($trans_shares);
$updated_on=date('Y-m-d H:i:s');

		$postData = array();
			$postData['running_balance'] =$account_running_balance;
			$postData['reversed_by'] =$_SESSION['user_id'];
			$postData['transaction_reversed'] ='Yes';
       
	 $this->db->UpdateData('share_account_transaction', $postData,"`share_trans_id` = '{$tansno}'");
		
		$postData_acc = array();
		if($account_running_balance==0){			
		$postData_acc['account_status']='Closed';		
		$postData_acc['closesure_reason']='Account balance has been set back to zero';		
		}
		$postData_acc['running_balance']=$account_running_balance;
		$postData_acc['total_shares'] =$total_shares;
		$postData_acc['last_updated_on'] =$updated_on;
		 
		 $this->db->UpdateData('share_account', $postData_acc,"`share_account_no` = '{$accno}'");

	if(!empty($bought)){//IF SHARES WERE Transfered
	$seller = $this->db->SelectData("SELECT * FROM share_account_transaction where share_account_no='".$bought."' and transaction_type='Transfered Shares' and tansfer_to='".$accno."' and no_of_shares='".$trans_shares."' ");
	$trans_no_sell=$seller[0]['share_trans_id'];
	$acc_no_sell=$seller[0]['share_account_no'];
	$seller_ac = $this->db->SelectData("SELECT * FROM share_account where share_account_no='".$bought."'");
	$tansaction_type_sell=$seller[0]['transaction_type'];
	$sold_to_sell=$seller[0]['tansfer_to'];
	$trans_shares_sell=$seller[0]['no_of_shares'];
	$trans_amount_sell=$seller[0]['amount'];
	$trans_running_balance_sell=($seller[0]['running_balance'])+($result[0]['amount']);	
	$account_running_balance_sell=($seller_ac[0]['running_balance'])+($result[0]['amount']);
	$total_shares_sell=($seller_ac[0]['total_shares'])+($trans_shares_sell);
			$postData_sell = array();
			$postData_sell['running_balance'] =$trans_running_balance_sell;
			$postData_sell['reversed_by'] =$_SESSION['user_id'];
			$postData_sell['transaction_reversed'] ='Yes';
       
	 $this->db->UpdateData('share_account_transaction', $postData_sell,"`share_trans_id` = '{$trans_no_sell}'");
		//end transaction update
		$postData_acc_sell = array();
		if($seller_ac[0]['account_status']=='Closed'){	//IF ACCOUNT IS CLOSED		
		$postData_acc_sell['account_status']='Active';					
		$postData_acc_sell['re_activatedon_date']=date('Y-m-d');					
		$postData_acc_sell['re_activatedon_userid']=$_SESSION['user_id'];					
		}
		$postData_acc_sell['running_balance']=$account_running_balance_sell;
		$postData_acc_sell['total_shares'] =$total_shares;
		$postData_acc_sell['last_updated_on'] =$updated_on;
		 $this->db->UpdateData('share_account', $postData_acc_sell,"`share_account_no` = '{$acc_no_sell}'");

	}		 
		 
		 
		 
	}else if($tansaction_type=='Transfered Shares'){//TRANSFERRING SHARES TRANSACTION
	$trans_running_balance=($result[0]['running_balance'])+($result[0]['amount']);	
	$account_running_balance=($account_b[0]['running_balance'])+($result[0]['amount']);	
	$total_shares=($account_b[0]['total_shares'])+($trans_shares);
	
			$postData = array();
			$postData['running_balance'] =$trans_running_balance;
			$postData['reversed_by'] =$_SESSION['user_id'];
			$postData['transaction_reversed'] ='Yes';
       
	 $this->db->UpdateData('share_account_transaction',$postData,"`share_trans_id` = '{$tansno}'");
		$postData_acc = array();
	   if($account_b[0]['account_status']=='Closed'){	//IF ACCOUNT IS CLOSED		
		$postData_acc['account_status']='Active';					
		$postData_acc['re_activatedon_date']=date('Y-m-d');					
		$postData_acc['re_activatedon_userid']=$_SESSION['user_id'];					
		}
		$postData_acc['running_balance']=$account_running_balance;
		$postData_acc['total_shares'] =$total_shares;
		$postData_acc['last_updated_on'] =$updated_on;
		 $this->db->UpdateData('share_account', $postData_acc,"`share_account_no` = '{$accno}'");
	if(!empty($transfer)){//IF SHARES WERE Transfered
	$buyer = $this->db->SelectData("SELECT * FROM share_account_transaction where share_account_no='".$transfer."' and transaction_type='Bought Shares' and bought_from='".$accno."' and no_of_shares='".$trans_shares."' ");
	$trans_no_buy=$buyer[0]['share_trans_id'];
	$acc_no_buy=$buyer[0]['share_account_no'];
	$buyer_ac = $this->db->SelectData("SELECT * FROM share_account where share_account_no='".$bought."'");
	$tansaction_type_buy=$buyer[0]['transaction_type'];
	$sold_to_sell=$buyer[0]['tansfer_to'];
	$trans_shares_buy=$buyer[0]['no_of_shares'];
	$trans_amount_buy=$buyer[0]['amount'];
	$trans_running_balance_buy=($buyer[0]['running_balance'])+($result[0]['amount']);	
	$account_running_balance_buy=($buyer_ac[0]['running_balance'])+($result[0]['amount']);
	$total_shares_buy=($buyer_ac[0]['total_shares'])+($trans_shares_buy);
			$postData_buy = array();
			$postData_buy['running_balance'] =$trans_running_balance_buy;
			$postData_buy['reversed_by'] =$_SESSION['user_id'];
			$postData_buy['transaction_reversed'] ='Yes';
       
	 $this->db->UpdateData('share_account_transaction', $postData_buy,"`share_trans_id` = '{$trans_no_buy}'");
		//end transaction update
		$postData_acc_buy = array();
		if($buyer_ac[0]['account_status']=='Closed'){	//IF ACCOUNT IS CLOSED		
		$postData_acc_buy['account_status']='Active';					
		$postData_acc_buy['re_activatedon_date']=date('Y-m-d');					
		$postData_acc_buy['re_activatedon_userid']=$_SESSION['user_id'];					
		}
		$postData_acc_buy['running_balance']=$account_running_balance_buy;
		$postData_acc_buy['total_shares'] =$total_shares_buy;
		$postData_acc_buy['last_updated_on'] =$updated_on;
		 $this->db->UpdateData('share_account', $postData_acc_buy,"`share_account_no` = '{$acc_no_buy}'");

	}
    }else if($tansaction_type=='Withdrew Dividend'){ 

    }else{
  if(isset($data['trans_date'])){
 header('Location: ' . URL . 'equity/reversesamedaytransaction/prev?trans=failed'); 
  }else{
 header('Location: ' . URL . 'equity/reversesamedaytransaction?trans=failed'); 	  
	  
  }	
	}
$this->db->commit();	

  }catch(Exception $e){
	  $this->db->rollBack();
$error=$e->getMessage();
	  if(isset($data['trans_date'])){
 header('Location: ' . URL . 'equity/reversesamedaytransaction/prev?trans=failed&error='.$error); 
 exit(); 
 }else{
 header('Location: ' . URL . 'equity/reversesamedaytransaction?trans=failed&error='.$error); 	  
exit(); 	  
  }	


}

  if(isset($data['trans_date'])){
 header('Location: ' . URL . 'equity/reversesamedaytransaction/prev?trans=reversed'); 
  }else{
 header('Location: ' . URL . 'equity/reversesamedaytransaction?trans=reversed'); 
	  
  }
	

	
}else{
	  if(isset($data['trans_date'])){
 header('Location: ' . URL . 'members/reversesavingstransaction/prev?trans=failed'); 
  }else{
 header('Location: ' . URL . 'members/reversesavingstransaction?trans=failed'); 	  
	  
  }	
}

	
}
function OpenclosedShareHolder($acc){

$date=date('Y-m-d');

		$postData = array(
			're_activatedon_date' =>$date,
			're_activatedon_userid' =>$_SESSION['user_id'],
			'account_status' =>'Active',
        );

 $this->db->UpdateData('share_account', $postData,"`share_account_no` = '{$acc}'");
 header('Location: ' . URL . 'equity/reopensharesaccount?activated='.$acc.''); 
	
}
function confirmtransaction($data){

	$tansno=$data['tnumber'];
	$tansamount=$data['tamount'];
	$accno=$data['account_no'];

	$postData = array(
		'approved_by' =>$_SESSION['user_id'],
		'transaction_status' =>'Approved',
	);

	$this->db->UpdateData('share_account_transaction', $postData,"`share_trans_id` = '{$tansno}'");
	header('Location: ' . URL . 'equity/confirmpendingtransaction?trans=approved'); 
	
}


function ImportBulk($mdata){

	$ext = strtolower(pathinfo($mdata['audit_file_temp'],PATHINFO_EXTENSION));		
	$now = date('d_m_Y');
	$file_name = $_SESSION['office'].'_'.$_SESSION['user_id'] . '_' . $now;
	$dest = 'public/systemlog/member_list/' . $file_name . '.csv';
	move_uploaded_file($mdata['audit_file_temp'], $dest);
	$filerec = file_get_contents($dest);
	$string = str_getcsv($filerec, "\r");
	$share_product=$this->getProduct(1);
	$share_cost=$share_product[0]['amount_per_share'];

	foreach ($string as $key => $value) {
		$data = explode(',', $value);
		$cid=trim($data[0]);
		//$data = explode(',', $value);
		$name=null;
		$office = $_SESSION['office'];
		$str=date('isH').rand();		
		$acc_no= substr($office.$cid.'1'.substr($str,0,7),0,11);
		//print_r($cid);echo '<hr>';
		$client_details = $this->getMember($cid);
		//print_r($client_details);echo '<hr>';
		if(empty($client_details[0]['company_name'])){
			$name=$client_details[0]['firstname']." ".$client_details[0]['middlename']." ".$client_details[0]['lastname'];
		}else{
			$name=$client_details[0]['company_name'];		
		}
		//print_r($string);die();
		$amount=str_replace(',','',$data[1]);
		//print_r($data[4]);die();
		$updated_on=date('Y-m-d H:i:s');

		if(count($share_product)>0&&$amount>0){

			$total_shares=($amount/$share_cost);
            $transaction_postData = array(
				'share_account_no' => $acc_no,
				'branch' => $office,
				'no_of_shares' =>$total_shares,			 
				'amount' =>$amount,
				'running_balance' =>$amount,
				'recorded_by' =>$_SESSION['user_id'],
			);

			$share_transaction_id = $this->db->InsertData('share_account_transaction', $transaction_postData);

			if(!empty($share_transaction_id)){

				$share_postData = array(
					'share_account_no' => $acc_no,
					'member_id'=>$cid,
					'submittedon_date' => $updated_on,
					'submittedon_userid' => $_SESSION['user_id'],
					'account_status' =>'Active',
					'product_id' => 1,
					'total_shares' =>$total_shares,
					'running_balance' =>$amount,
					'last_updated_on' => $updated_on,
				);

				$this->db->InsertData('share_account', $share_postData);

				$trans_uniqid=uniqid();

				$deposit_transaction_uniqid = $share_transaction_id."".$trans_uniqid;
				$prodType=1;
				$mapping = $this->GetGLPointers(1,$prodType,'Purchase Shares');

				$transaction_id = "SH".$deposit_transaction_uniqid;

				$new_data['transaction_id'] = $transaction_id;
				$this->db->UpdateData('share_account_transaction', $new_data,"`share_trans_id` = '{$share_transaction_id}'");

				if(!empty($mapping[0]["debit_account"])&&!empty($mapping[0]["credit_account"])){
					$debt_id=$mapping[0]["debit_account"];	
					$credit_id=$mapping[0]["credit_account"];		
					$sideA=$this->getAccountSide($debt_id);
					$sideB=$this->getAccountSide($credit_id);
					$description="Shares Bought by ".$name;	

					$this->makeJournalEntry($debt_id,$office,$_SESSION['user_id'],$share_transaction_id,$transaction_id,$amount,'DR',$sideA,$description);
					$this->makeJournalEntry($credit_id,$office,$_SESSION['user_id'],$share_transaction_id,$transaction_id,$amount,'CR',$sideB,$description);
				}
			}
		}
	}
	header('Location:'.URL.'equity/sharesaccount');
}


}