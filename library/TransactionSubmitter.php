<?php  
class TransactionSubmitter {

	function __construct() {
		$this->db = new Database();
		$this->model = new Model();
	
	}
	
function SubmitSavingsAccountTransaction($account_no,$amount, $product_id,$prodType,$operation,$transtype ,$savings_transaction_type,$description,$depositor){
 	$transaction_type = $savings_transaction_type;
	$amount = str_replace(",", "", $amount);
 
	$update_time = date('Y-m-d H:i:s');
	$office = $_SESSION['office'];
	$user_id = $_SESSION['user_id'];



        $accountDetails = $this->model->GetSavingsAccount($account_no);
		if(sizeof($accountDetails)==0){
			return $this->model->MakeJsonResponse(404, "account not found");
		}
		$mapping = $this->model->GetGLPointers($product_id, $prodType, $transtype);
        if (empty($mapping)) {
            return $this->model->MakeJsonResponse(200, "dr cr accounts not set");
        }
		
		$balance = $accountDetails[0]['running_balance'];
        $availabledeposit = $accountDetails[0]['total_deposits'];
		
		if($operation == "DR"){
		$new_total_deposits = $availabledeposit - $amount;
		$new_balance = $balance - $amount;
		}else if($operation == "CR"){
		$new_total_deposits = $availabledeposit + $amount;
		$new_balance = $balance+$amount;
		}else{
			return $this->model->MakeJsonResponse(204, "operation not supported");
		}
		
		if($new_balance<0){
			return $this->model->MakeJsonResponse(101, "insuficient funds on the account");
		}
 
       

        if (!empty($mapping[0]["debit_account"]) && !empty($mapping[0]["credit_account"])) {
            try {
                $transaction_postData = [
                    'savings_account_no' => $account_no,
                    'transaction_type' => $transaction_type,
                    'payment_detail_id' => $description,
                    'amount' => $amount,
                    'op_type' => $operation,
                    'running_balance' => $new_balance,
                    'depositor_name' => $depositor,
                    'amount_in_words' => $this->model->convertNumber($new_balance),
                    'telephone_no' => 0,
                    'branch' => $office,
                    'user_id' => $user_id,
                ];

                $deposit_transaction_id = $this->db->InsertData('m_savings_account_transaction', $transaction_postData);

                $depositstatus = [
                    'total_deposits' => $new_total_deposits,
                    'running_balance' => $new_balance,
                    'last_updated_on' => $update_time,
                ];

                $this->db->UpdateData('m_savings_account', $depositstatus, "`account_no` = '{$account_no}'");

                $transaction_uniqid = uniqid();
                $deposit_transaction_uniqid = $deposit_transaction_id . "" . $transaction_uniqid;
                $transaction_id = "S" . $deposit_transaction_uniqid;
				
                 $debt_id = $mapping[0]["debit_account"];
                $credit_id = $mapping[0]["credit_account"];
                $sideA = $this->model->getAccountSide($debt_id);
                $sideB = $this->model->getAccountSide($credit_id);

              
			$description = $account_no;

			$new_data['transaction_id'] = $transaction_id;
			$this->db->UpdateData('m_savings_account_transaction', $new_data, "`id` = '{$deposit_transaction_id}'");

			$this->MakeJournalEntry($debt_id, $office, $user_id, $deposit_transaction_id, $transaction_id, $amount, 'DR', $sideA,$description,3);
			$this->MakeJournalEntry($credit_id, $office, $user_id, $deposit_transaction_id, $transaction_id, $amount, 'CR', $sideB,$description,3);
	

                return $this->model->MakeJsonResponse(100, "success", $transaction_id);
            } catch (Exception $e) {
                $error = $e->getMessage();
               return $this->model->MakeJsonResponse(203, $err);
            }
        } else {
            return $this->model->MakeJsonResponse(200, "dr cr accounts not available");
        }
    }


function MakeJournalEntry($acc_id, $office, $user, $sub_ledger_id, $trans_id, $amount, $type, $side,$description,$pdid){

	switch ($pdid){
		case 1;
		$pdata = array('share_capital_transaction_id' => $sub_ledger_id);
		break;
		case 2;
		$pdata = array('savings_transaction_id' => $sub_ledger_id);
		break;
		case 3;
		$pdata = array('share_capital_transaction_id' => $sub_ledger_id);
		break;
		case 4;
		$pdata = array('fixed_deposit_transaction_id' => $sub_ledger_id);
		break;
		case 5;
		$pdata = array('wallet_transaction_id' => $sub_ledger_id);
		break;
		default;
		$pdata = array('client_transaction_id' => $sub_ledger_id);
		break;
	}
	
	 
	 
	    $postData = array(
		'account_id' => $acc_id,
		'office_id' => $office,
		'transaction_id' => $trans_id,
		'amount' => $amount,
		'transaction_type' => $type,
		'description' =>$description,
		'trial_balance_side' => $side,
		'createdby_id' => $user,
		'branch_id' => $_SESSION['branchid'],
		'payment_details_id' => NULL);
			
			$postArray = array_merge($pdata,$postData);
		
	$id =  $this->db->InsertData('acc_gl_journal_entry', $postArray);
	return $id;

}
 
}