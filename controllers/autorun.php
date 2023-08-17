<?php

class autorun extends Controller{

	public function __construct(){
		parent::__construct();	 
	}

	function Bulk(){
		$this->saccos = $this->model->getSaccos();
		if (!empty($this->saccos)) {
			foreach ($this->saccos as $key => $value) {
				$this->model->SendBulkMessages($value['id']);
			}
		}
	}	

	function Bulkdetails($method, $id){
		$this->model->$method($id);
	}

	function quickLoanCalculation(){
		$this->saccos = $this->model->getSaccos();
		if (!empty($this->saccos)) {
			foreach ($this->saccos as $key => $value) {
				$this->model->CalculateMemberQuickLoans($value['id']);
			}
		}
	}

	function esacco_payout(){
		$this->saccos = $this->model->getSaccos();
		if (!empty($this->saccos)) {
			foreach ($this->saccos as $key => $value) {
				$this->model->ComputeNodePayout($value['id']);
			}
		}
	}

	function daily(){
		$this->dailytransactions = $this->model->checkDailyTransactions();
		if (!$this->dailytransactions) {
			$this->saccos = $this->model->getSaccos();
			if (!empty($this->saccos)) {
				foreach ($this->saccos as $key => $value) {
					//$this->model->ProcessBulkPaymentRequest($value['id']);
					//$this->model->SavingsDailyInterestCalculation($value['id']);
					//$this->model->LoanDailyInterestCalculation($value['id']);
					$this->model->QuickLoan($value['id']);
					$this->model->ComputeCharges($value['id'], 'Daily');
					$this->model->ProcessStandingOrder($value['id'], 'Daily');
				}
			}
		} else {
			echo "Daily Transactions Rejected";
		}
	}

	function weekly(){
		$this->weeklytransactions = $this->model->checkWeeklyTransactions();
		if (!$this->weeklytransactions) {
			$this->saccos = $this->model->getSaccos();
			if (!empty($this->saccos)) {
				foreach ($this->saccos as $key => $value) {
					$this->model->ComputeCharges($value['id'], 'Weekly');
					$this->model->ProcessStandingOrder($value['id'], 'Weekly');
				}
			}
		} else {
			echo "Weekly Transactions Rejected";
		}
	}

	function monthly(){
		$this->monthlytransactions = $this->model->checkMonthlyTransactions();
		if (!$this->monthlytransactions) {
			$this->saccos = $this->model->getSaccos();
			if (!empty($this->saccos)) {
				foreach ($this->saccos as $key => $value) {
					$this->model->SavingsMonthlyInterestPosting($value['id']);
					$this->model->LoanMonthlyInterestPosting($value['id']);
					$this->model->FixedDepositInterestPosting($value['id']);
					$this->model->ComputeCharges($value['id'], 'Monthly');
					$this->model->ProcessStandingOrder($value['id'], 'Monthly');
				}
			}
		} else {
			echo "Monthly Transactions Rejected";			
		}
	}

	function annualy(){
		$this->weeklytransactions = $this->model->checkWeeklyTransactions();
		if (!$this->weeklytransactions) {
			$this->saccos = $this->model->getSaccos();
			if (!empty($this->saccos)) {
				foreach ($this->saccos as $key => $value) {
					$this->model->ComputeCharges($value['id'], 'Annually');
					$this->model->ProcessStandingOrder($value['id'], 'Annually');
				}
			}
		} else {
			echo "Annual Transactions Rejected";
		}

	}

	function settings(){
		if (empty($_POST)) {
			echo "Access Denied";
		} else {
			$this->saccos = $this->model->createSA($_POST);
		}
	}

	function checkGLs(){
		$this->saccos = $this->model->getSaccos();
		if (!empty($this->saccos)) {
			foreach ($this->saccos as $key => $value) {
				$this->model->CheckGLAccounts($value['id'], $value['name']);
			}
		}
	}

}