<?php
//error_reporting(0);

class autorun_model extends Model{
	public function __construct(){
		parent::__construct();
    	$this->logUserActivity(NULL);
		$this->loans_calculations = new LoanCalculations();
	}

	/////////////////////////////////////////// BULK MESSAGING //////////////////////////////////////


	function SendBulkMessages($sacco){

		$today = date('Y-m-d');
		$bulk_messages = $this->db->SelectData("SELECT * FROM messages WHERE status='Active' AND office_id =  '".$sacco."' AND message_date LIKE '$today%'");
		foreach ($bulk_messages as $key => $value) {
			if ($value['email'] == 'Yes') {
				$this->sendEmailToSaccoMembers($sacco, $value);
			}
			if ($value['telephone'] == 'Yes') {
				$this->sendSMSToSaccoMembers($sacco, $value);
			}
			if ($value['advert'] == 'Yes') {
				$this->advertiseToSaccoMembers($sacco, $value);
			}
			if ($value['app'] == 'Yes') {
				$this->pushToSaccoApp($sacco, $value);
			}

		}
	}

	function pushToSaccoApp($sacco, $data){

		$members = $this->getSaccoMembers($sacco);
		
		foreach ($members as $key => $value) {

			$phone = $value['mobile_no'];			
			$variables = json_decode($data['message_variables'], 1);
			$msg = $data['details'];

			$send = TRUE;
			if (!empty($variables)) {
				foreach($variables as $variable_key => $variable_value){
					$new_value = $this->getMessageTemplate($variable_value, $value['c_id']);
					$msg = str_replace($variable_key,$new_value,$msg);
					if (is_numeric($new_value)) {
						if ($new_value <= 0) {
							$send = FALSE;
							break;
						} else{
							$send = TRUE;
						}
					} else{
						$send = TRUE;
					}
				}
			}

			if ($send) {
				$new_msg = $data['title'] .  "</br>\n". $msg;
				echo $new_msg;
				echo "</br></br>";
				
				//$this->pushToApp($phone, $new_msg);
				//$this->updateBulkMessage($data['id'], $data['frequency'], $data['message_date']);
			} else {
				echo "NO; " . "</br>\n Because: ";
				echo $msg;
				echo "</br></br>";
			}

		}
	}

	function advertiseToSaccoMembers($sacco, $data){
		
		//to be done
	}

	function pushToApp($phone_number, $message){
		return true;
	}

	function sendEmailToSaccoMembers($sacco, $data){

		$members = $this->getSaccoMembers($sacco);
		
		foreach ($members as $key => $value) {

			$email = $value['email'];			
			$variables = json_decode($data['message_variables'], 1);
			$msg = $data['details'];

			$send = TRUE;
			if (!empty($variables)) {
				foreach($variables as $variable_key => $variable_value){
					$new_value = $this->getMessageTemplate($variable_value, $value['c_id']);
					$msg = str_replace($variable_key,$new_value,$msg);
					if (is_numeric($new_value)) {
						if ($new_value <= 0) {
							$send = FALSE;
							break;
						} else{
							$send = TRUE;
						}
					} else{
						$send = TRUE;
					}
				}
			}

			if ($send) {
				$new_msg = $data['title'] .  "</br>\n". $msg;
				echo $new_msg;
				echo "</br></br>";
				
				//$this->sendEmail($email, $msg, $data['title']);
				//$this->updateBulkMessage($data['id'], $data['frequency'], $data['message_date']);
			} else {
				echo "NO; " . "</br>\n Because: ";
				echo $msg;
				echo "</br></br>";
			}

		}
	}

	function sendSMSToSaccoMembers($sacco, $data){

		$members = $this->getSaccoMembers($sacco);
		
		foreach ($members as $key => $value) {

			$walletAccount = $value['mobile_no'];			
			$variables = json_decode($data['message_variables'], 1);
			$msg = $data['details'];

			$send = TRUE;
			if (!empty($variables)) {
				foreach($variables as $variable_key => $variable_value){
					$new_value = $this->getMessageTemplate($variable_value, $value['c_id']);
					$msg = str_replace($variable_key,$new_value,$msg);
					if (is_numeric($new_value)) {
						if ($new_value <= 0) {
							$send = FALSE;
							break;
						} else{
							$send = TRUE;
						}
					} else{
						$send = TRUE;
					}
				}
			}

			if ($send) {
				$new_msg = $data['title'] .  "</br>\n". $msg;
				echo $new_msg;
				echo "</br></br>";

				//$this->SendSMS($walletAccount,$new_msg);
				//$this->updateBulkMessage($data['id'], $data['frequency'], $data['message_date']);
			} else {
				echo "NO; " . "</br>\n Because: ";
				echo $msg;
				echo "</br></br>";
			}

		}
	}

	function updateBulkMessage($id, $frequency, $date){

		/*$multiple_warnings = array();
		$current = 0;
		$processed = 0;

		if (!empty($multiple_warnings)) {

			$date = $multiple_warnings[$current+1];
			if ($frequency == 'Once' && $processed >= count($multiple_warnings)) {
				$data['status'] = "Closed";
			} else {
				$data['message_date'] = date('Y-m-d', strtotime($date));
			}
			} else if ($frequency == 'Daily') {
				$data['message_date'] = date('Y-m-d', strtotime($date));
			} else if ($frequency == 'Weekly') {
				$data['message_date'] = date('Y-m-d', strtotime($date));
			} else if ($frequency == 'Monthly') {
				$data['message_date'] = date('Y-m-d', strtotime($date));
			} else if ($frequency == 'Annually') {
				$data['message_date'] = date('Y-m-d', strtotime($date));
			}

		} else { */

			if ($frequency == 'Once') {
				$data['status'] = "Closed";
			} else if ($frequency == 'Daily') {
				$data['message_date'] = date('Y-m-d', strtotime("+1 days", strtotime($date)));
			} else if ($frequency == 'Weekly') {
				$data['message_date'] = date('Y-m-d', strtotime("+7 days", strtotime($date)));
			} else if ($frequency == 'Monthly') {
				$data['message_date'] = date('Y-m-d', strtotime("+1 months", strtotime($date)));
			} else if ($frequency == 'Annually') {
				$data['message_date'] = date('Y-m-d', strtotime("+1 years", strtotime($date)));
			}
		//}

		//$data['processed'] = $processed + 1;

		$this->db->UpdateData('messages', $data,"`id` = '{$id}'");
	}

	function getMessageTemplate($id, $member_id){

		$message_details = $this->db->SelectData("SELECT * FROM message_template_methods WHERE status='Active' AND id = '$id'");

		$ch = curl_init();
		$url = URL.$message_details[0]['link']."/".$member_id;
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$server_output = curl_exec ($ch);
		curl_close ($ch);

		return $server_output;

	}

	function getMemberName($id){

		$results = $this->db->selectData("SELECT * FROM members WHERE c_id = $id AND status = 'Active'");

		if (!empty($results[0]['firstname'])) {
			$name = $results[0]['firstname'] . " " . $results[0]['middlename'] . " " . $results[0]['lastname'];
		} else {
			$name = $results[0]['company_name'];
		}		

		echo $name;
		die();

	}

	function getMemberLoanBalance($id){
		$results = $this->db->selectData("SELECT * FROM m_loan WHERE member_id = $id AND loan_status = 'Disbursed'");

		$balance = 0;
		if (!empty($results)) {
			$balance = $results[0]['total_outstanding'];
		}
		echo $balance;
		die();

	}

	//////////////////////////////////////////////////////////////////////////////////////////////////

	///////////////////////////////////////////  BULK PAYMENT //////////////////////////////////////////

	function ProcessBulkPaymentRequest($sacco){

		$bulk_details=$this->db->SelectData("SELECT * FROM bulk_payments WHERE status='Active' AND payment_status = 'Active' AND office_id =  '".$sacco."'");
		foreach ($bulk_details as $key => $value) {
		
			$wallet = $this->getSaccoWalletAccount($value['telephone']);
			$receiving = $this->getSaccoWalletAccount($value['from_account']);
			$member_details=$this->db->SelectData("SELECT * FROM members WHERE c_id='".trim($value['member_id'])."' AND office_id =  '".$sacco."'");

			if(count($wallet)>0&&count($member_details)>0){

				if(count($receiving)>0){
					$amount = $value['amount'];

					if($amount>0){
						$wallet_balance = $wallet[0]['wallet_balance'];
						$transwallet_balance = $receiving[0]['wallet_balance'];

						$new_w_bal = $wallet_balance - $amount;
						$new_w_receiving_acc_bal = $transwallet_balance + $amount;
 
						$data = array();
						if($new_w_bal>0){

							if (date('Ymd') == date('Ymd', strtotime($value['payment_date']))) {

								$data['amount'] = $amount;
								$data['wallet_account_number'] = $value['from_account'];
								$data['amount_in_words'] = $this->convertNumber($amount);
								$data['wallet_balance'] = $new_w_bal;
								$data['transaction_type'] = 'Transfer';
								$data['description'] = 'To :'.$value['telephone'];
								$data['transaction_id'] = 'BP' . date('YmdHis');
								$data['fee']=0;
								
								$this->logWalletTransaction($data);

								$data['accounttransfer'] = $value['telephone'];
								$data['receiving_balance'] = $new_w_receiving_acc_bal;
								$data['description']='From :'.$value['from_account'];
								$data['fee'] = 0;

								$transaction_id = $this->logWalletTransaction($data);
									echo $transaction_id . "</br>";
								$this->updatePaymentStatus($value['id'], $value['frequency'], $value['payment_date']);
							}

			    		}
					}
				}
			}
		}
	}

	function updatePaymentStatus($id, $frequency, $date){		

		if ($frequency == 'Once') {
			$data['payment_status'] = "Closed";
		} else if ($frequency == 'Daily') {
			$data['payment_date'] = date('Y-m-d', strtotime("+1 days", strtotime($date)));
		} else if ($frequency == 'Weekly') {
			$data['payment_date'] = date('Y-m-d', strtotime("+7 days", strtotime($date)));
		} else if ($frequency == 'Monthly') {
			$data['payment_date'] = date('Y-m-d', strtotime("+1 months", strtotime($date)));
		} else if ($frequency == 'Annually') {
			$data['payment_date'] = date('Y-m-d', strtotime("+1 years", strtotime($date)));
		}

		$data['processed'] = "Yes";
		$this->db->UpdateData('bulk_payments', $data,"`id` = '{$id}'");
	}


	function getSaccoWalletAccount($phone_no){
		return	$this->db->SelectData("SELECT * from sm_mobile_wallet WHERE wallet_account_number = '".$phone_no."'");
	}

	///////////////////////////////////////////////////////////////////////////////////////////////////

	///////////////////////////////////////// //QUICK LOAN ALGORITHM ///////////////////////////////////////

	function CalculateMemberQuickLoans($sacco){

		$members = $this->getSaccoMembers($sacco);

		echo "<table border='1'>
			<tr>
				<th colspan='7'>$sacco</th>
			</tr>
			<tr>
				<th>Sacco Member Name</th>
				<th>Share Holdings</th>
				<th>Locked Savings</th>
				<th>AVG Past Deposits</th>
				<th>Min Credit</th>
				<th>Max Credit</th>
				<th>Qualify</th>
			</tr>";
		foreach ($members as $key => $value) {
			$amounts = array();

			$share_pdt_details = $this->getDefaultProducts(1, $sacco);
			if (!empty($share_pdt_details)) {
				$share_price = $share_pdt_details[0]['amount'];
				$loan_pdt_details = $this->getQuickProducts($sacco);

				if (!empty($loan_pdt_details)) {
					$minimum_credit = $loan_pdt_details['min_principal_amount'];
					$period_in_months = $loan_pdt_details['loan_transaction_months'];
					$share_price_percentage = $loan_pdt_details['share_percentage_for_loan'];

					if ($period_in_months > 0 && $share_price_percentage > 0) {						
						$mem_shares = $this->getMemberShares($sacco, $value['c_id']);
						$locked_savings_amount = $this->getMemberLockedSavings($sacco, $value['c_id']);
						$avg_savs_over_last_given_period = $this->getMemberAvgSavingsOverPeriod($sacco, $value['c_id'], $period_in_months);
						$total_shares = (($mem_shares*$share_price)*($share_price_percentage/100));
						$total_credit_wothiness = (($total_shares) + ($locked_savings_amount) + $avg_savs_over_last_given_period);

						/*if ($minimum_credit >= $total_credit_wothiness) {
							$total_credit_wothiness = $loan_pdt_details['max_principal_amount'];
						} */
						echo "<tr> 
						<td><b>" . $value['firstname'] . "</b></td>
						<td> " . number_format($total_shares) . "</td>
						<td> " . number_format($locked_savings_amount) . "</td>
						<td> " . number_format($avg_savs_over_last_given_period) . "</td>
						<td> " . number_format($minimum_credit) . "</td>
						<td> " . number_format($total_credit_wothiness) . "</td>
						<td> "; ?><?php echo $total_credit_wothiness < $loan_pdt_details['min_principal_amount'] ? "You Don't Qualify" : "You Qualify"; ?> <?php echo "</td>
						</tr>";
					}
				}
			}

			//<td> " . number_format(min($amounts)) . "</td></tr>";
			//<td> " . number_format(max($amounts)) . "</td></tr>";

		}
		echo "</table><br><br>";
	}

	function getMemberAvgSavings($saccoid, $member_id, $period){
		$today = date_format(date_create(), "Y-m-d");
		$previous_date = date('Y-m-d', strtotime("-$period months", strtotime($today)));

		$savings = $this->db->selectData("SELECT account_no FROM m_savings_account WHERE member_id = $member_id AND office_id = $saccoid AND account_status = 'Active'");

		$avg = $total = 0;
		foreach ($savings as $key => $value) {
			$dep_trans_sum = $this->getAccountTransactionsSum($value['account_no']);
			$dep_trans = $this->getAccountTransactionsCount($value['account_no']);
			//$avg += ($dep_trans_sum/$dep_trans);
			$total += $dep_trans_sum;
		}
	
		$avg = ($total/$period);
		return $avg;
	}

	function getMemberAvgSavingsOverPeriod($saccoid, $member_id, $period){
		$today = date_format(date_create(), "Y-m-d H:i:s");
		$previous_date = date('Y-m-d H:i:s', strtotime("-$period months", strtotime($today)));

		$savings = $this->db->selectData("SELECT account_no FROM m_savings_account WHERE member_id = $member_id AND office_id = $saccoid AND account_status = 'Active'");

		$avg = $total = 0;
		foreach ($savings as $key => $value) {
			$total = $this->getAccountTransactionsSum($value['account_no'], $previous_date, $today);
		}
	
		$avg = ($total/$period);
		return $avg;
	}

	function getAccountTransactionsSum($acc, $start, $end){
		$savings = $this->db->selectData("SELECT SUM(amount) AS sum FROM m_savings_account_transaction WHERE savings_account_no = $acc AND transaction_type = 'Deposit' AND transaction_reversed = 'No' AND transaction_date BETWEEN '$start' AND '$end'");
		if (empty($savings)) {
			return 0;
		} else {
			if ($savings[0]['sum'] == "") {
				return 0;
			} else {
				return $savings[0]['sum'];
			}
		}
	}

	function getAccountTransactionsCount($acc){
		$savings = $this->db->selectData("SELECT COUNT(amount) AS sum FROM m_savings_account_transaction WHERE savings_account_no = $acc AND transaction_type = 'Deposit' AND transaction_reversed = 'No'");
		if (empty($savings)) {
			return 0;
		} else {
			if ($savings[0]['sum'] == "") {
				return 0;
			} else {
				return $savings[0]['sum'];
			}
		}
	}

	function getMemberLockedSavingsPercentile($saccoid, $member_id){
		$total = $this->getTotalSaccoLockedSavings($saccoid);
		$member_savings = $this->getMemberLockedSavings($saccoid, $member_id);
		//echo "</br>$member_id LOCKED SAVs: " . $member_savings . " TOTAL LOCKED: " . $total ;//. " %AGE: " . (($member_savings/$total)*100) . "</br>";
		//die();
		if ($total>0) {
			return (($member_savings/$total)*100);
		} else {
			return 0;
		}
	}

	function getMemberLockedSavings($saccoid, $id){
		$savings = $this->db->selectData("SELECT running_balance AS total FROM m_savings_account WHERE member_id = $id AND office_id = $saccoid AND account_status = 'Active' AND withdraw_status = 'Active'");
		if (empty($savings)) {
			return 0;
		} else {
			if ($savings[0]['total'] == "") {
				return 0;
			} else {
				return $savings[0]['total'];
			}
		}
	}

	function getTotalSaccoLockedSavings($id){
		$savings =  $this->db->selectData("SELECT SUM(running_balance) AS total FROM m_savings_account WHERE office_id = $id AND account_status = 'Active' AND withdraw_status = 'Active'");
		if ($savings[0]['total'] == "") {
			return 0;
		} else {
			return $savings[0]['total'];
		}
	}

	function getMemberSharePercentile($saccoid, $member_id){
		$total = $this->getTotalSaccoShares($saccoid);
		$member_shares = $this->getMemberShares($saccoid, $member_id);
		//echo "$member_id SHARES: " . $member_shares . " TOTAL: " . $total . " %AGE: " . (($member_shares/$total)*100) . "</br>";
		//die();
		if ($total>0) {
			return (($member_shares/$total)*100);
		} else {
			return 0;
		}
	}

	function getMemberShares($sacco, $id){
		$shares =  $this->db->selectData("SELECT SUM(total_shares) AS total FROM share_account WHERE member_id = $id AND office_id = $sacco AND account_status = 'Active'");
		if ($shares[0]['total'] == "") {
			return 0;
		} else {
			return $shares[0]['total'];
		}
	}

	function getTotalSaccoShares($id){
		$shares =  $this->db->selectData("SELECT SUM(total_shares) AS total FROM share_account WHERE office_id = $id AND account_status = 'Active'");
		if ($shares[0]['total'] == "") {
			return 0;
		} else {
			return $shares[0]['total'];
		}
	}

	function getSaccoMembers($sacco){

		$results = $this->db->selectData("SELECT * FROM members WHERE office_id = $sacco AND status = 'Active'");

		return $results;
	}

	////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/////////////////////////////////////// GROUP MONEY TRANFER TO MEMBER /////////////////////////////////

	function ComputeNodePayout($sacco){

		$saccoGroups = $this->getSaccoGroups($sacco);

		foreach ($saccoGroups as $key => $value) {

			$member_payments_collected = $this->getMemberPayments($value['account_no'], $value['deposit_frequency'], $value['member_contribution']);
			$expected_member_payments = $this->getTotalExpectedMemberPayments($value['id'], $value['member_contribution']);

			//echo $value['name'] . " Collected -> ". $member_payments_collected . " : Expected -> " . $expected_member_payments . " (" .$value['deposit_frequency'] . ")</br>";
			if ($member_payments_collected >= $expected_member_payments) {
				$this->transferMoney($value['payment_order'], $value['id'], $value['next_person'], $value['account_no'], $value['payout_frequency'], $sacco, $expected_member_payments);
			} else{
				echo ucfirst($value['name']) . " Members Have Not Fully Contributed!! </br>";
			}
		}
	}

	function transferMoney($payment_order, $grp_id, $next_person, $grp_acc, $freq, $sacco_id, $amount){

		$new_next_person = $this->getNextPerson($grp_id, $payment_order, $next_person);

		if ($new_next_person > 0) {
			$account = $this->getMemberAccountNo($new_next_person);
			$transaction_status = $this->makeWalletTransaction($grp_id, $grp_acc, $account, $freq, $sacco_id, $amount, $new_next_person);

			$xmlData = simplexml_load_string(trim($transaction_status));	 
			$responsecode = $xmlData->response->responsecode; 	 
			$responsemsg = $xmlData->response->responsemsg; 

			if ($responsecode == 100) {
				$this->updateNextPerson($grp_id, $new_person, $new_next_person);
				echo "Successfully Transfered Funds";
			} else {
				echo $responsemsg;
			}
		}
	}

	function makeWalletTransaction($grp, $grp_acc, $account, $payout_FREQ, $saccoid, $amount, $member_id){

		$today = date("Y-m-d");
		$last_update = $this->getGroupLastGiveAwayDate($grp);

		$day_date = date("Y-m-d", strtotime('+1 day', strtotime($last_update)));
		$week_date = date("Y-m-d", strtotime('+1 week', strtotime($last_update)));
		$month_date = date("Y-m-d", strtotime('+1 month', strtotime($last_update)));
		$annual_date = date("Y-m-d", strtotime('+1 year', strtotime($last_update)));

		$wallet = $this->getMemberWalletNo($member_id);

		if(($payout_FREQ == "daily") && ($today >= $day_date)){
			$uname =  "Daily Payout";
			$response = $this->makeTransfer($uname, $grp_acc, $wallet, $saccoid, $amount);
			return $response;
		} else if(($payout_FREQ == "weekly") && ($today >= $week_date)){
			$uname =  "Weekly Payout";
			$response = $this->makeTransfer($uname, $grp_acc, $wallet, $saccoid, $amount);
			return $response;
		} else if(($payout_FREQ == "monthly") && ($today >= $month_date)){
			$uname =  "Monthly Payout";
			$response = $this->makeTransfer($uname, $grp_acc, $wallet, $saccoid, $amount);
			return $response;
		} else if(($payout_FREQ == "annually") && ($today >= $annual_date)){
			$uname =  "Annual Payout";
			$response = $this->makeTransfer($uname, $grp_acc, $wallet, $saccoid, $amount);
			return $response;
		} else{
			return FALSE;
		}
	}

	function getMemberWalletNo($id){

		$results = $this->db->selectData("SELECT * FROM members WHERE c_id = $id");

		return $results[0]['mobile_no'];
	}

	function makeTransfer($uname, $acc, $wallet, $sacco, $amount){
		$req_array['requesttype'] = "";
		$req_array['username'] = $uname;
		$req_array['requestid'] = uniqid();
		$req_array['accountno'] = $acc;
		$req_array['wallet_account_number'] = $wallet;
		$req_array['saccoid'] = $sacco;
		$req_array['amount'] = $amount;

		////////////// Alternatively Make An API Call To The Corresponding BankPull Method ////////////////////
		$this->log->ExeLog($req_array, 'ThirdPartyModel::BankPullrequest Function Call With Data Set ' . var_export($req_array, true), 1);
        $req_array['req_filename'] = $this->log->LogXML($req_array['username'],$req_array['requestid'], $req_array['requesttype'],$xml_post);

		$final_response = $this->savings->ProcessBankPullRequest($req_array);
		
		$this->log->ExeLog($req_array, 'ThirdPartyModel::bankpullrequest Sending back response ' . var_export($final_response, true), 3);
			return $final_response;
		/////////////////////////////////////////////////////////////////////////////////////////////////////
	}

	function getGroupLastGiveAwayDate($id){

		$results = $this->db->selectData("SELECT * FROM m_group WHERE id = $id");

		return $results[0]['payout_date'];
	}

	function updateNextPerson($id, $member_id, $next_person){

		$grpMemberDetails = array(
			'rank' => 1
		);

		$this->db->UpdateData('m_group_client', $grpMemberDetails,"`client_id` = '{$member_id}' AND `group_id` = '{$id}'");

		$grpDetails = array(
			'next_person' => $next_person,
			'payout_date' => date("Y-m-d")
		);

		$this->db->UpdateData('m_group', $grpDetails,"`id` = '{$id}'");

		$all = $this->checkIfAllHaveReceived($id);
		$memberNumber = $this->getMemberCount($id);
		if ($all == $memberNumber) {
			$this->rewindCount($id);
		}
	}

	function rewindCount($id){

		$grpMemberDetails = array(
			'rank' => 0
		);
		$this->db->UpdateData('m_group_client', $grpMemberDetails,"`group_id` = '{$id}'");

		$grpMemberDetails = array(
			'round' => ($this->getPreviousRound($id) + 1),
			'next_person' => 0
		);
		$this->db->UpdateData('m_group', $grpMemberDetails,"`id` = '{$id}'");
	}

	function getPreviousRound($id){
		$results = $this->db->selectData("SELECT * FROM m_group WHERE id = $id");
		return $results[0]['round'];
	}

	function checkIfAllHaveReceived($id){

		$results = $this->db->selectData("SELECT COUNT(client_id) AS total FROM m_group_client WHERE group_id = $id AND rank = 1 AND status = 'Active'");

		return $results[0]['total'];
	}

	function getNextPerson($grpID, $payment_order, $previous_person){

		if ($payment_order == 'fifo') {
			$members = $this->getGroupMembers($grpID);
			foreach ($members as $key => $value) {
				if ($value['client_id'] == $previous_person) {
					$new_person = $members[$key+1]['client_id'];
				} else{
					$new_person = $members[0]['client_id'];
				}
			}
		} else if ($payment_order == 'random') {
			$no_of_members = $this->getRemainingMembersCount($grpID);
			$nxt = rand(1, $no_of_members);
			$members = $this->getRemainingGroupMembers($grpID);
			foreach ($members as $key => $value) {
				if ($key == ($nxt-1)) {
					$new_person = $members[($nxt-1)]['client_id'];
				}
			}
		}
		return $new_person;
	}

	function getRemainingGroupMembers($id){

		$results = $this->db->selectData("SELECT * FROM m_group_client WHERE group_id = $id AND rank = 0");

		return $results;
	}

	function getGroupMembers($id){

		$results = $this->db->selectData("SELECT * FROM m_group_client WHERE group_id = $id");

		return $results;
	}

	function getMemberAccountNo($id){
		$results = $this->db->selectData("SELECT account_no FROM m_savings_account WHERE member_id = $id");
		return $results[0]['account_no'];
	}

	function getMemberCount($grp){

		$results = $this->db->selectData("SELECT COUNT(client_id) AS total FROM m_group_client WHERE group_id = $grp AND status = 'Active'");

		return $results[0]['total'];
	}

	function getRemainingMembersCount($grp){

		$results = $this->db->selectData("SELECT COUNT(client_id) AS total FROM m_group_client WHERE group_id = $grp AND rank = 0 AND status = 'Active'");

		return $results[0]['total'];
	}

	function getTotalExpectedMemberPayments($grp, $expected){

		return ($this->getMemberCount($grp) * $expected);
	}

	function getMemberPayments($acc, $dep_freq, $expected_amount){

		if ($dep_freq == 'daily') {
			$date = date("Y-m-d");
		} else if ($dep_freq == 'weekly') {
			$start_date = date("Y-m-d",strtotime('monday this week'));
			$end_date = date("Y-m-d",strtotime("sunday this week"));
		} else if ($dep_freq == 'monthly') {
			$date = date("Y-m");
		} else if ($dep_freq == 'annually') {
			$date = date("Y");
		}

		if ($dep_freq == 'weekly') {
			$results = $this->db->selectData("SELECT * FROM m_savings_account_transaction WHERE savings_account_no = $acc AND transaction_type = 'Deposit' AND transaction_date >= '$start_date%' AND transaction_date <= '$end_date%'");
		} else {
			$results = $this->db->selectData("SELECT * FROM m_savings_account_transaction WHERE savings_account_no = $acc AND transaction_type = 'Deposit' AND transaction_date LIKE '$date%'");
		}

		$total = 0;
		foreach ($results as $key => $value) {
			$total += $value['amount'];
			if ($value['amount'] < $expected_amount) {
				return $total;
				break;
			}
		}

		return $total;
	}

	function getSaccoGroups($sacco){

		$results = $this->db->selectData("SELECT * FROM m_group WHERE office_id = $sacco AND status = 'Active'");

		return $results;
	}

	///////////////////////////////////////////////////////////////////////////////////////////////////////
	

	function ProcessStandingOrder($sacco, $time){

		$results = $this->db->selectData("SELECT * FROM m_account_transfer_details WHERE from_office_id = $sacco AND transfer_type = '$time'");

		foreach ($results as $key => $value) {
			$this->TransferData($value['id'], $value['from_savings_account_id'], $value['to_savings_account_id'], $value['transfer_amount'], $time, $sacco);
		}
	}

	function TransferData($id, $acc_sender, $acc, $transfer_amount, $time, $sacco){

		$result_send = $this->db->selectData("SELECT * FROM m_savings_account JOIN members ON m_savings_account.member_id = members.c_id WHERE m_savings_account.account_no='".$acc_sender."'");

		$sender_amount = str_replace(",","",$transfer_amount);
		$sender_runnning_balance = $result_send[0]['running_balance'];
		$availablewithdraws = $result_send[0]['total_withdrawals'];
		$senderName = $result_send[0]['firstname'] . " " . $result_send[0]['lastname'];
		$new_total_withdraws = $availablewithdraws + $sender_amount ;
		$new_sender_balance = ($sender_runnning_balance - $sender_amount);

		$update_time = date('Y-m-d H:i:s');
		$result = $this->db->selectData("SELECT * FROM m_savings_account JOIN members ON m_savings_account.member_id = members.c_id WHERE m_savings_account.account_no='".$acc."' ");

		$amount = str_replace(",","",$transfer_amount);
		$balance = $result[0]['running_balance'];
		$availabledeposit = $result[0]['total_deposits'];
		$receiverName = $result[0]['firstname'] . " " . $result[0]['lastname'];
		$new_total_deposits = $availabledeposit + $amount ;
		$new_balance = ($amount + $balance);

		$senderData = array(
			'savings_account_no' => $acc_sender,
			'transaction_type' => "Transfer",
			'payment_detail_id' => "Transfer",
			'amount' => $sender_amount,
			'running_balance' => $new_sender_balance,
			'depositor_name' => $senderName,
			'amount_in_words' => $this->convertNumber($sender_amount),
			'telephone_no' => "",
			'branch' => $sacco,
			'user_id' => 0,
		);

		$receiverData = array(
			'savings_account_no' => $acc,
			'transaction_type' => "Transfer",
			'payment_detail_id' =>  "Transfer",
			'amount' => $amount,
			'running_balance' => $new_balance,
			'depositor_name' => $receiverName,
			'amount_in_words' => $this->convertNumber($amount),
			'telephone_no' => "",
			'branch' => $sacco,
			'user_id' => 0,
		);

		$withdraw_trans_id = $this->db->InsertData('m_savings_account_transaction', $senderData);
		$deposit_trans_id = $this->db->InsertData('m_savings_account_transaction', $receiverData);

		$withdrawstatus = array(
			'total_withdrawals' => $new_total_withdraws,
			'running_balance' => $new_sender_balance,
			'last_updated_on' =>$update_time,
		);

		$depositstatus = array(
			'total_deposits' => $new_total_deposits,
			'running_balance' => $new_balance,
			'last_updated_on' =>$update_time,
		);

		$this->db->UpdateData('m_savings_account', $withdrawstatus,"`account_no` = '{$acc_sender}'");
		$this->db->UpdateData('m_savings_account', $depositstatus,"`account_no` = '{$acc}'");

		$postData = array(
			'name' => $senderName,
			'account_transfer_details_id' => $id,
			'priority' => NULL,
			'status' => NULL,
			'instruction_type' => NULL,
			'amount' => $amount,
			'valid_from' => "",
			'valid_till' => "",
			'recurrence_type' => NULL,
			'recurrence_frequency' => 1,
			'recurrence_interval' => $time,
			'recurrence_on_day' => date('d'),
			'recurrence_on_month' => date('m'),
			'last_run_date' => date('Y-m-d H:i:s')
		);

		$tran_id = $this->db->InsertData('m_account_transfer_standing_instructions', $postData);

	}

	function CheckGLAccounts($sacco, $name){

		$admin = $this->getSaccoAdminEmail($sacco);

		$msg = "";
		$GL_wallet = $this->getWalletGL($sacco);
		$C_wallet = $this->getWalletBalance($sacco);

		if ($GL_wallet['amount'] != $C_wallet) {
			$msg .= 'Wallet Account ('.$GL_wallet['gl_code'].') Not Balanced GL Account Balance: ' . number_format($GL_wallet['amount']) . " Customer Balance: " . number_format($C_wallet) . "</br>";
		}

		$GL_savings = $this->getSavingsGL($sacco);	
		$C_savings = $this->getSavingsBalance($sacco);

		if ($GL_savings['amount'] != $C_savings) {
			$msg .= 'Savings Account ('.$GL_savings['gl_code'].') Not Balanced GL Account Balance: ' . number_format($GL_savings['amount']) . " Customer Balance: " . number_format($C_savings) . "</br>";
		}

		$GL_loans = $this->getloansGL($sacco);	
		$C_loans = $this->getloansBalance($sacco);

		if ($GL_loans['amount'] != $C_loans) {
			$msg .= 'Loans Account ('.$GL_loans['gl_code'].') Not Balanced GL Account Balance: ' . number_format($GL_loans['amount']) . " Customer Balance: " . number_format($C_loans) . "</br>";
		}

		$GL_shares = $this->getsharesGL($sacco);	
		$C_shares = $this->getsharesBalance($sacco);

		if ($GL_shares['amount'] != $C_shares) {
			$msg .= 'Shares Account ('.$GL_shares['gl_code'].') Not Balanced GL Account Balance: ' . number_format($GL_shares['amount']) . " Customer Balance: " . number_format($C_shares) . "</br>";
		}

		$GL_timedeposits = $this->gettimedepositsGL($sacco);	
		$C_timedeposits = $this->gettimedepositsBalance($sacco);

		if ($GL_timedeposits['amount'] != $C_timedeposits) {
			$msg .= 'Time Deposits Account ('.$GL_timedeposits['gl_code'].') Not Balanced GL Account Balance: ' . number_format($GL_timedeposits['amount']) . " Customer Balance: " . number_format($C_timedeposits) . "</br>";
		}

		if ($admin != "" && $msg != "") {
			$subject = ucfirst($name) . " General Ledger Accounts Warning";
			//echo "$admin $name </br> $msg </br>";
			$this->sendEmail($admin, $msg, $subject);
		}
	}

	function getSaccoAdminEmail($sacco){

		$results = $this->db->SelectData("SELECT * FROM m_staff WHERE office_id = $sacco AND is_active = 1 AND access_level = 'A' AND status = 'Active' ORDER BY id ASC LIMIT 1");

		return $results[0]['email'];

	}

	function checkDailyTransactions(){

		$today = date('Y-m-d');

		$results = $this->db->SelectData("SELECT * FROM m_savings_account_accrued_interest WHERE accrued_date LIKE '$today%'");

		if(empty($results)){
			return false;
		}else{ 
			return true;
		}

	}

	function checkWeeklyTransactions(){

		$today = date('Y-m-d');

		$results = $this->db->SelectData("SELECT * FROM m_charge_transaction WHERE date LIKE '$today%'");

		if(empty($results)){
			return false;
		}else{ 
			return true;
		}
	}

	function checkMonthlyTransactions(){

		$month = (int)date('m');
		$year = (int)date('Y');
		$days_in_this_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);

		$today = date('Y-m');

		$results = $this->db->SelectData("SELECT COUNT(DISTINCT(accrued_date)) AS total FROM m_savings_account_accrued_interest WHERE accrued_date LIKE '$today%'");
		
		if ($results[0]['total'] >= $days_in_this_month) {
			return false;
		}else{ 
			return true;
		}
	}

	function ComputeCharges($sacco, $time){
		
		$results = $this->db->SelectData("SELECT * FROM m_charge WHERE office_id = $sacco AND charge_time = '$time' AND status = 'Active' AND is_active = 1 AND is_deleted = 0");

		if (count($results)>0) {
			foreach ($results as $key => $value) {
				$pdt = $value['id'];
				$transaction = $value['transaction_type_id'];
				$applies_to = $value['charge_applies_to'];
				$pointers = $this->db->SelectData("SELECT * FROM acc_gl_pointers WHERE sacco_id = $sacco AND product_id = $pdt AND transaction_type_id = $transaction");

				if (count($pointers)>0){
					$wallets = $this->db->SelectData("SELECT * from sm_mobile_wallet WHERE bank_no=$sacco");
					foreach ($wallets as $key1 => $value1) {
						$exemptions = $this->getMemberChargeExemptions($value1['member_id']);
						if (is_null($exemptions)) {
							$this->ChargeMember($value, $pointers[0], $value1, $sacco);
						} else {
							if (!in_array($pdt, $exemptions)) {
								$this->ChargeMember($value, $pointers[0], $value1, $sacco);
							} else {
								//echo "Member " . $value1['member_id'] . " Exepted From " . ucwords($value['name']) . "</br>";
							}
						}
					}
				}
			}
		}
	}

	function ChargeMember($charges, $pointers, $wallet, $sacco){

		///////////////////////////////////// SAVE WALLET TRANSACTION ///////////////////////////////////////

		$transaction_id = "C". uniqid();
		$description = ucfirst($charges['name']) . " Charges On " . $wallet['wallet_account_number'] . " Wallet";

		$amount = $charges['amount'];
		$new_balance = $wallet['wallet_balance'] - $amount;
		$transaction_postData = array(
			'wallet_account_number' => $wallet['wallet_account_number'],
			'transaction_type' =>  'Charge',
			'transaction_ID' => $transaction_id,
			'transaction_status' => 'complete',
			'transaction_date' => date("Y-m-d H:i:s"),
			'amount' => $amount,
			'amount_in_words' => $this->convertNumber($amount),
			'fee' => 0,
			'running_balance' => $new_balance,
			'description' => $description,
			'user_id' => 0,
			'transaction_reversed' => NULL,
			'reversed_by' => NULL
		);

		$charge_transaction_id = $this->db->InsertData('sm_mobile_wallet_transactions', $transaction_postData);

		/////////////////////////////////////// UPDATE WALLET BALANCE //////////////////////////////////////
		$update_details = array(
			'wallet_balance' =>$new_balance
		);
		$id = $wallet['wallet_id'];

		$this->db->UpdateData('sm_mobile_wallet', $update_details,"`wallet_id` = '{$id}'");

		///////////////////////////////////////// MAKE GL ENTRIES /////////////////////////////////////////

		$debt_id = $pointers["debit_account"];
		$credit_id = $pointers["credit_account"];

		$sideA = $this->getAccountSide($debt_id);
		$sideB = $this->getAccountSide($credit_id);

		$this->makeJournalEntry('',$debt_id,$sacco,'0',$charge_transaction_id,$transaction_id,$amount,'DR',$sideA,$description);//DR
		$this->makeJournalEntry('',$credit_id,$sacco,'0',$charge_transaction_id,$transaction_id,$amount,'CR',$sideB,$description);//CR

		////////////////////////////////////// SAVE TRANSACTION CHARGE //////////////////////////////////
		$charge_transaction_data = array(
			'transaction_id' => $transaction_id,
			'charge_id' => $charges['id'],
			'trans_amount' => $amount,
			'date' => date("Y-m-d H:i:s")
		);

		$charge_trans_id = $this->db->InsertData('m_charge_transaction', $charge_transaction_data);
	}

	function createSA($data){

		$ra = trim(substr(md5(uniqid(mt_rand(), true)), 0, 10));
		$password = Hash::create('sha256',$ra, HASH_ENCRIPT_PASS_KEYS);	 
		$postData = array();	

		$response = array();
		$response['code'] = "101";
		$response['error'] = True;

		if (empty($data['fname'])) {
			$response['msg'] = "Missing FirstName";
		} else if (empty($data['lname'])) {
			$response['msg'] = "Missing Last Name";
		} else if (empty($data['username'])) {
			$response['msg'] = "Missing User Name";
		} else if (empty($data['phone'])) {
			$response['msg'] = "Missing Phone Number";
		} else if (empty($data['email'])) {
			$response['msg'] = "Missing Email";
		} else if (empty($data['office_id'])) {
			$response['msg'] = "Missing Office ID";
		} else if (empty($data['gender'])) {
			$response['msg'] = "Missing Gender";
		} else {

			if ($this->checkUsername($data['username'])) {
				$response['msg'] = "Username already Exists";
			} else if ($this->checkPhonenumber($data['phone'])) {
				$response['msg'] = "Phone Number already Exists";
			} else if ($this->checkEmail($data['email'])) {
				$response['msg'] = "Email already Exists";
			} else{

				$response['code'] = "100";
				$response['error'] = False;

				$postData['firstname']=$data['fname'];	 
				$postData['lastname']=$data['lname'];	 
				$postData['username']=$data['username'];	 
				$postData['password']= $password;
				$postData['mobile_no']=$data['phone'];	 
				$postData['email']=$data['email'];	 
				$postData['office_id']=$data['office_id'];
				//$postData['organisational_role_enum']=$data['responsibility'];	 
				$postData['gender']=$data['gender'];
				$postData['access_level']= 'SA';

				$message =  "Hello, your CLIC FMS login details are<br>Username: ".$data['username']."<br>Password: ".$ra;
				//$this->sendEmail($data['email'],$message);	 
				$this->AuthAccessLevels($data);	
				$id = $this->db->InsertData('m_staff',$postData);

				$response['msg'] = 'Successfully created System Admin';

			}
		}
		echo json_encode($response);
		die();
	}

	function checkUsername($uname){

		$results = $this->db->SelectData("SELECT username FROM m_staff WHERE username ='" . $uname . "'");

		if (empty($results)) {
			return false;
		} else {
			return true;
		}
	}

	function checkPhonenumber($mobile_no){

		$results = $this->db->SelectData("SELECT mobile_no FROM m_staff WHERE mobile_no ='" . $mobile_no . "'");

		if (empty($results)) {
			return false;
		} else {
			return true;
		}
	}

	function checkEmail($email){

		$results = $this->db->SelectData("SELECT email FROM m_staff WHERE email ='" . $email . "'");

		if (empty($results)) {
			return false;
		} else {
			return true;
		}
	}

	function AuthAccessLevels($data){
		$postData = array();	
		$postData['access_denotor']= 'SA';	
		$postData['office_id'] = $data['office_id'];	 
		//$postData['user_id'] = $data['user_id'];	 
		//$postData['creator_access']=$_SESSION['user_id'];
		$postData['access_name'] = "System Admin";//$data['access_name'];
		$postData['allowed_access']='1,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,62,63,63,64,65,66,67,68,69,70,71,72,73,74,75,76,77,78,79,80,81,82,83,84,85,86,87,88,89,90,91,92,93,94,95,96,97,98,99,100,101,102,103,104,105,106,107,108,109,110,111,112,113,114,115,116,117,118,119,120';

		$this->db->InsertData('sch_user_levels',$postData);
	}

	function getSaccos(){
		$results = $this->db->SelectData("SELECT * FROM m_branch");
		if(empty($results)){
			return '';
		}else{ 
			return $results;
		}
	}

	function getSaccoCurrency($id){
		$results = $this->db->SelectData("SELECT currency FROM system_settings WHERE status ='Active'");
		return $results[0]['currency'];
	}

	function getSaccoName($id){
		$results = $this->db->SelectData("SELECT name FROM m_branch WHERE id ='" . $id . "'");
		return $results[0]['name'];
	}

	//sacco_id
	function SavingsDailyInterestCalculation($sacco){
		$loans = $this->SavingsList($sacco);
		if(!empty($loans)){
			foreach($loans as $key => $value){
				$account_no = $value['account_no'];
				$principle =  $value['running_balance']; 
			  
				$rate = $value['nominal_interest_rate'];
				$days_in_year = $value['days_in_year'];
		   
				$month = (int)date('m');
				$year = (int)date('Y');
				$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year); // 31
				//$interest = number_format(($principle* (($rate/100)*(1/$days_in_year))),2);
				$interest = ($principle* $rate * (1/$days_in_month )/100);
				//$interest = sprintf('%0.2f', $interest); // 520 -> 520.00
				//$interest =  truncate($interest, 2); 
				$dd = date('Y-m-d');
				$update_time=date('Y-m-d H:i:s');	

				$loan_transaction= array(
					'account_no' => $account_no,
					'accrued_date' =>$update_time,
					'interest_amount' => $interest,
				);
				$this->db->InsertData('m_savings_account_accrued_interest', $loan_transaction);
			}
			return true;
		} else {
			return false;
		}
	}

	//sacco_id
	function FixedDepositList($sacco){
		$results = $this->db->SelectData("SELECT * FROM fixed_deposit_account where account_status='Active' and interest_stopped='No' and office_id = '". $sacco ."'");
		if(empty($results)){
			return '';
		}else{ 
			return $results ;
		}
	}

	//sacco_id
	function SavingsList($sacco){
		$results = $this->db->SelectData("SELECT * FROM m_savings_account a inner join m_savings_product p  where a.product_id =  p.id AND a.account_status='Active' AND a.office_id = '".$sacco."'");
		if(empty($results)){
			return '';
		}else{ 
			return $results ;
		}
	}

	//sacco_id
	function SavingsMonthlyInterestPosting($sacco){

		$loans = $this->SavingsList($sacco);

		if(!empty($loans)){
			foreach($loans as $key => $value){

				$loan_interest_mapping = $this->GetSaccoGLPointers($value['product_id'],3,'Interest on savings', $sacco);

				if (!empty($loan_interest_mapping)) {

					$account_no = $value['account_no'];
					$principle = $value['running_balance'];
					$sum_int = $this->getSumOfDailySavingsInterests($account_no);

					$interest = $sum_int[0]['interest'];

					if ($interest > 0) {

						$new_balance = $principle + $interest;
						$client_details = $this->getClient($value['member_id']);

						$office_id = $client_details[0]['office_id'];	
						$user_id = $client_details[0]['c_id'];

						$data['amount_in_words']=$this->convertNumber($interest);
						$transaction_postData = array(
							'savings_account_no' => $account_no,
							'payment_detail_id' =>  NULL,
							'amount' =>$interest,
							'running_balance' => $new_balance,
							'depositor_name' => $this->getSaccoName($sacco),
							'amount_in_words' => $data['amount_in_words'],
							'telephone_no' => NULL,
							'branch' =>$office_id,
							'transaction_type' =>'Interest',
							'user_id' =>  $user_id
						);

						$transaction_id = $this->db->InsertData('m_savings_account_transaction', $transaction_postData);
						$availabledeposit = $value['total_deposits'];

						$new_total_deposits = $availabledeposit + $interest ;

						$dd = date('Y-m-d');
						$update_time=date('Y-m-d H:i:s');

						$depositstatus = array(
							'total_deposits' => $new_total_deposits,
							'running_balance' => $new_balance,
							'last_updated_on' =>$update_time,
						);

						$this->db->UpdateData('m_savings_account', $depositstatus,"`account_no` = '{$account_no}'");

						$deposit_transaction_id = $transaction_id;
						$transaction_uniqid = uniqid();

						//$this->db->SelectData("SELECT * FROM  acc_gl_pointers where pointer_name = 'Interest on savings'");

						$disburs_debt_id=$loan_interest_mapping[0]["debit_account"];
						$disburs_credit_id=$loan_interest_mapping[0]["credit_account"];

						$disburs_sideA=$this->getAccountSide($disburs_debt_id);
						$disburs_sideB=$this->getAccountSide($disburs_credit_id);

						//disburse to savings
						$description="Interest on savings account no ".$account_no;

						$deposit_transaction_uniqid = $deposit_transaction_id."".$transaction_uniqid;
						$transaction_id = "S".$deposit_transaction_uniqid;

						$new_data['transaction_id'] = $transaction_id;
						$this->db->UpdateData('m_savings_account_transaction', $new_data,"`id` = '{$deposit_transaction_id}'");

						$this->makeJournalEntry('saving',$disburs_debt_id,$office_id,'0',$deposit_transaction_id,$transaction_id,$interest,'DR',$disburs_sideA,$description);//DR
						$this->makeJournalEntry('saving',$disburs_credit_id,$office_id,'0',$deposit_transaction_id,$transaction_id,$interest,'CR',$disburs_sideB,$description);//CR
					}
				}
			}
		}
	}

	function getSumOfDailySavingsInterests($acc){
		$month = date('m');
		$year = date('Y');

		return	$this->db->SelectData("SELECT sum(interest_amount) as interest FROM 	m_savings_account_accrued_interest where account_no='".$acc."' and MONTH(accrued_date) = '".$month."'  and YEAR(accrued_date) = '".$year."' ");
	}

	// Monthly fixed deposit interest posting
	function FixedDepositInterestPosting($sacco){
		$fixed = $this->FixedDepositList($sacco);
		if(!empty($fixed)){
			foreach($fixed as $key => $value){

				$fd_interest_mapping = $this->GetSaccoGLPointers($value['product_id'],4,'TD Interest', $sacco);

				if(!empty($fd_interest_mapping)){
					$account_no = $value['account_no'];
					$principle = $value['running_balance']; 
					$rate = $value['interest_rate']; 

					$interest =  ($principle*($rate/12))/100 ; //monthly interest calculation
					if ($interest > 0) { 

						$new_balance = $principle + $interest;	   

						$client_details = $this->getClient($value['member_id']);

						$office_id = $client_details[0]['office_id'];	
						$user_id = $client_details[0]['c_id'];

						if(empty($client_details[0]['company_name'])){
							$name=$client_details[0]['firstname']." ".$client_details[0]['middlename']." ".$client_details[0]['lastname'];
						}else{
							$name=$client_details[0]['company_name'];	
						}

						$data['amount_in_words']=$this->convertNumber($interest);

						$transaction_postData = array(
							'fixed_account_no' =>$account_no,
							'amount' =>$interest,
							'transaction_type' =>'Interest',
							'depositor_name' =>'system',
							'running_balance' =>$new_balance,
							'amount_in_words' =>$data['amount_in_words'],
							'branch' =>$office_id,
							'user_id' =>$user_id,
							'approved_by' => 'system', 
			        	);
					
						$fixed_trans_id = $this->db->InsertData('fixed_deposit_transactions', $transaction_postData);
						$total_interest_posted = $value['total_interest_posted']; 
						$total_interest_earned = $value['total_interest_earned']; 
				
						$newtotal_interest_posted	 = $total_interest_posted +$interest;
						$newtotal_interest_earned	 = $total_interest_earned +$interest;
						//Interest

				        $InterestData = array(
							'running_balance' => $new_balance,
							'total_interest_earned' => $newtotal_interest_posted,
							'total_interest_posted' => $newtotal_interest_earned,
						);

						$this->db->UpdateData('fixed_deposit_account', $InterestData,"`account_no` = '{$account_no}'");
						
						$transaction_uniqid=uniqid();
						$interest_transaction_uniqid = $fixed_trans_id."".$transaction_uniqid;

						//=$this->db->SelectData("SELECT * FROM  acc_gl_pointers where pointer_name = 'TD Interest' AND sacco_id = '".$sacco."'");

						$transaction_id = "FD".$interest_transaction_uniqid;

						$debt_id =$fd_interest_mapping[0]["debit_account"]; //debit fixed  Control account
						$credit_id =$fd_interest_mapping[0]["credit_account"]; //credit cash fixed reference	
						$sideA=$this->getAccountSide($debt_id);
						$sideB=$this->getAccountSide($credit_id);

						$description="Fixed interest for  ".$name;

						$new_data['transaction_id'] = $transaction_id;
						$this->db->UpdateData('fixed_deposit_transactions', $new_data,"`id` = '{$fixed_trans_id}'");

						$this->makeJournalEntry('fixed',$debt_id,$office_id,$user_id,$fixed_trans_id,$transaction_id,$interest,'DR',$sideA,$description);//DR
						$this->makeJournalEntry('fixed',$credit_id,$office_id,$user_id,$fixed_trans_id,$transaction_id,$interest,'CR',$sideB,$description);//CR
					}
				}
			}
		}
	}

	//sacco_id
	function LoanDailyInterestCalculation($sacco){
		$loans = $this->loansList($sacco);

		if(!empty($loans)){
			foreach($loans as $key => $value){
				$account_no = $value['account_no'];
				$loan_id =  $value['account_no'];

				$ln = $this->getLoanBalance($value['account_no']);
				$id = $ln[0]['id'];
				$principle = $ln[0]['outstanding_loan_balance_derived'];
				$rate = $value['annual_nominal_interest_rate'];
				$days_in_year = $value['days_in_year'];
				$month = (int)date('m');
				$year = (int)date('Y');
				$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year); // 31

				$interest = ($principle* $rate * (1/$days_in_month )/100);

				$interest =  str_replace( ',', '',  $interest);
				//$interest = sprintf('%0.2f', $interest); // 520 -> 520.00
				//	$interest =  truncate($interest, 2); 
				$dd = date('Y-m-d');
				$update_time=date('Y-m-d H:i:s');	

				$loan_transaction= array(			    
					'account_no' => $account_no,
					'accrued_date' =>$update_time,
					'interest_amount' => $interest,			  
				);

				$loan_transaction_id = $this->db->InsertData('m_loan_interest', $loan_transaction);

				//debit and credit the onto the personal account
			}
		}
	}

	//sacco_id
	function LoanMonthlyInterestPosting($sacco){
		
		$loans = $this->loansList($sacco);

		if(!empty($loans)){
			foreach($loans as $key => $value){	

					

				if (!empty($loan_interest_mapping)) {

					$account_no = $value['account_no'];
					$loan_id =  $value['account_no'];

					$ln = $this->getLoanBalance($account_no);

					$id = $ln[0]['id'];
					$principle = $ln[0]['outstanding_loan_balance_derived'];
					$rate = $value['annual_nominal_interest_rate'];
					$days_in_year = $value['days_in_year'];
					$sum_int = $this->getSumOfDailyInterests($account_no);
					$interest = $sum_int[0]['interest'];
			   
			   		if ($interest > 0) {
						$balance = $principle + $interest;
						$client_details = $this->getClient($value['member_id']);
						$office_id = $client_details[0]['office_id'];

						$dd = date('Y-m-d');
						$update_time=date('Y-m-d H:i:s');	

						$loan_transaction= array(
							'office_id' => $office_id,
							'account_no' => $account_no,
							'transaction_date' =>$update_time,
							'amount' => $interest,
							'outstanding_loan_balance_derived' => $balance,
							'appuser_id' => 0,
							'transaction_type' => 'Debit',
							'm_description' => 'Interest',
						);

						$loan_transaction_id = $this->db->InsertData('m_loan_transaction', $loan_transaction);	

						$deposit_transaction_id = $id;
						$transaction_uniqid = uniqid();

						//=$this->db->SelectData("SELECT * FROM  acc_gl_pointers where pointer_name = 'Secure Loan Interest'");

						$disburs_debt_id=$loan_interest_mapping[0]["debit_account"];	
						$disburs_credit_id=$loan_interest_mapping[0]["credit_account"];		

						$disburs_sideA=$this->getAccountSide($disburs_debt_id);
						$disburs_sideB=$this->getAccountSide($disburs_credit_id);	
						//disburse to savings

						$description="Interest on loan ID ".$loan_id;

						$deposit_transaction_uniqid = $deposit_transaction_id."".$transaction_uniqid;
						$transaction_id = "L".$deposit_transaction_uniqid;

						$new_data['transaction_id'] = $transaction_id;
						$this->db->UpdateData('m_loan_transaction', $new_data,"`id` = '{$loan_transaction_id}'");

						$this->makeJournalEntry('loan',$disburs_debt_id,$office_id,'0',$deposit_transaction_id,$transaction_id,$interest,'DR',$disburs_sideA,$description);//DR
						$this->makeJournalEntry('loan',$disburs_credit_id,$office_id,'0',$deposit_transaction_id,$transaction_id,$interest,'CR',$disburs_sideB,$description);//CR
					}
				}
			}
		}
	}

	//sacco_id
	function QuickLoan($sacco){
		$loans = $this->QuickLoansList($sacco);

		$file = "logs/" . date('Y_m_d') . ".txt";

		if (!file_exists($file)) {
			$data = "\nDate: " . date('Y_m_d_h_i_s') . "\n";
			file_put_contents($file, $data);
		}

		$txt = "\n........................Quick Loan Auto run started today at ".date('Y-m-d H:i:s')."........................\n";
		file_put_contents($file, $txt.PHP_EOL , FILE_APPEND | LOCK_EX);

		$txt = "Array of all active loans as of today has been fetched, now Entering a loop for loans one by one";
		file_put_contents($file, $txt.PHP_EOL , FILE_APPEND | LOCK_EX);

		if(!empty($loans)){
			foreach($loans as $key => $value){
				$txt = "\nStarted Execting Loan account number ".$value['account_no']." for member ID ".$value['member_id']. "\n";
				file_put_contents($file, $txt.PHP_EOL , FILE_APPEND | LOCK_EX);

				$current_date = date('Y-m-d');
				$duedate = $value['duedate'];
				$account_no = $value['account_no']; 

				$wallet = $this->getWallet($value['member_id']);		 
				$walletAccount = $wallet[0]['wallet_account_number'];
				$lnBalance = (int)  $value['total_outstanding'];
				$loanBalance = number_format($lnBalance);
				$date = date('Y-m-d');
				$member_id = $value['member_id'];
				$savings_account_no = $wallet[0]['savings_account'];

				$SevenDays = date('Y-m-d', strtotime($current_date. ' + 7 days')); 
				$OnDayBefore = date('Y-m-d', strtotime($current_date. ' + 1 day'));
				$AfterFiveDaysPostingInterest = date('Y-m-d', strtotime($current_date. ' - 5 day'));

				$currency = $this->getSaccoCurrency($sacco);
				$message="";
				if($SevenDays == $duedate ){
					$message = "Hello, Your ".$this->getSaccoName($sacco)." loan of ".$currency." ".$loanBalance." is due in 7 days, please pay to avoid additional charges";
					$rs =    $this->SendSMS($walletAccount,$message);
				} else if($OnDayBefore == $duedate ){
					$message = "Hello, Your ".$this->getSaccoName($sacco)." loan of ".$currency." ".$loanBalance."  is due tomorrow, please pay today to avoid any charges";
					$rs =    $this->SendSMS($walletAccount,$message);
				}else if($current_date == $duedate ){
					$walletAccountBalance = $wallet[0]['wallet_balance'];
					if($walletAccountBalance>0){
						$payAmount =0;
						if($walletAccountBalance>=$lnBalance){
							$payAmount = $lnBalance;
							$message = $currency." ".$payAmount."  has been deducted from your ".$this->getSaccoName($sacco)." wallet towards loan repayment, your loan has been completely cleared. Thank you.";
						}else{
						    $payAmount = $lnBalance-$walletAccountBalance;
						    $Balance = $lnBalance -  $payAmount;
						    $message = $currency." ".$payAmount." been deducted from your ".$this->getSaccoName($sacco)." wallet towards loan repayment, your loan balance is now ".$currency." ".$Balance;
						}

						$AutoSweep = $this->AutoLoanRepayment(API, $member_id, $walletAccount, $savings_account_no, $payAmount);
						$ArrayData = json_decode($AutoSweep , true);
						if($ArrayData['message']==1){
							$rs = $this->SendSMS($walletAccount,$message);
						}
					}
				}else if($duedate == $AfterFiveDaysPostingInterest){
					$message = "Charge has been added to you account";
					$rs = $this->SendSMS($walletAccount,$message);
					//Post Interest on the quick loan
				}
				$txt = "Finishing the exection for loan account ".$account_no." with the following sms sent to the user '".$message."' to  phone number '".$walletAccount."'.\nNow exiting the exection for loan account number ".$account_no. "";
				file_put_contents($file, $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
			}
		}
		$txt = "\n.........................Excetion for for today completed at at ".date('Y-m-d H:i:s')."........................";
		file_put_contents($file, $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
	}

	function SendSMS($phone_number, $message){
		return true;
	}

	function getWallet($member){
		return	$this->db->SelectData("SELECT * from sm_mobile_wallet WHERE member_id= '".$member."' ");
	}

	function getSumOfDailyInterests($acc){
		$month = date('m');
		$year = date('Y');
		return	$this->db->SelectData("SELECT sum(interest_amount) as interest FROM m_loan_interest where account_no='".$acc."' and MONTH(accrued_date) = '".$month."'  and YEAR(accrued_date) = '".$year."' ");
	}

	function getLoanBalance($acc){
		return	$this->db->SelectData("SELECT * FROM m_loan_transaction where account_no='".$acc."' order by id DESC LIMIT 1  ");
	}
 
	//sacco_id
	function loansList($sacco){
		$results = $this->db->SelectData("SELECT * FROM m_loan l INNER JOIN m_product_loan p where l.loan_status='Disbursed' and p.id=l.product_id AND p.installment_option!='One Installment'AND p.interest_method!='Flat' AND l.sacco_id = '".$sacco."'");
		if(empty($results)){
			return '';
		}else{			
			return $results ;
		}
	}

	function QuickLoansList($sacco){
		$results = $this->db->SelectData("SELECT * FROM m_loan l, m_product_loan p  INNER JOIN m_loan_repayment_schedule s where l.loan_status='Disbursed' AND p.id=l.product_id AND p.installment_option='One Installment' AND p.interest_method='Flat' AND l.account_no = s.account_no AND l.sacco_id ='".$sacco."'");
		if(empty($results)){
			return '';			
		}else{			
			return $results ;
		}
	}

	function getClient($id){
		$results = $this->db->SelectData("SELECT * FROM members c INNER JOIN m_branch b where c.office_id  = b.id AND c.c_id='".$id."'  order by c.c_id desc");
		if(empty($results)){
			return '';
		}else{
			return $results ;
		}
	}

	function makeJournalEntry($trans_type,$acc_id,$office,$user,$type_trans_id,$trans_id,$amount,$type,$side,$description){
		$column = '';
		if($trans_type=='loan'){
			$column = 'loan_transaction_id';
		}else if ($trans_type=='saving'){
			$column = 'savings_transaction_id';
		}else if ($trans_type=='share'){
			$column = 'share_capital_transaction_id';
		}else if ($trans_type=='fixed'){
			$column = 'fixed_deposit_transaction_id';
		}else if ($trans_type=='wallet'){
			$column = 'wallet_transaction_id';
		}
		if ($column != "") {
			$postData = array(
				'account_id' =>$acc_id,
				'office_id' => $office,
				'branch_id' => $office,
				'createdby_id' =>$user,
			  	$column=>$type_trans_id,
				'transaction_id' =>$trans_id,
				'amount' => $amount,
	           	'transaction_type' =>$type,
	            'trial_balance_side' =>$side,							
	            'description' =>$description,							
	        ); 
	    } else {
			$postData = array(
				'account_id' =>$acc_id,
				'office_id' => $office,
				'branch_id' => $office,
				'createdby_id' =>$user,
				'transaction_id' =>$trans_id,
				'amount' => $amount,
	           	'transaction_type' =>$type,
	            'trial_balance_side' =>$side,							
	            'description' =>$description,							
	        ); 

	    }

		$this->db->InsertData('acc_gl_journal_entry', $postData);
	}

	function AutoLoanRepayment($url, $id, $wallet, $account, $amount){

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,"member_id_pay_loan=".$id."&wallet=".$wallet."&account=".$account."&amount=".$amount."&auth_user=esacco_app&auth_token=".$id."&auth_pwd=E$gnmd?#");
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$server_output = curl_exec ($ch);

		curl_close ($ch);
		return $server_output;
	}
}

 
