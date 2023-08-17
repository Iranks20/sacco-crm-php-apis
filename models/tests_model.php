<?php
 
class tests_model extends Model{
	
	public function __construct(){
		parent::__construct();
		$this->tr = new TransactionSubmitter();
	}
	
	function testWithdraw(){
//($account_no,$amount, $product_id,$prodType,$operation,$transtype ,$savings_transaction_type,$description,$depositor)

return $this->tr->SubmitSavingsAccountTransaction("10015014146","3000", 2,2,"DR","Loan Disbursement" ,"Withdraw","test deposit","emma");
	
	}

 

 

	
}