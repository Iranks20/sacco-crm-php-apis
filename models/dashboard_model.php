<?php
//error_reporting(0);

class Dashboard_model extends Model{
	
	public function __construct(){
		parent::__construct();
    	$this->logUserActivity(NULL); 
    	if (!$this->checkTransactionStatus()) {
    		header('Location: ' . URL); 
    	}
	}

	function getVersion(){
	   	$results = $this->db->selectData("SELECT version FROM system_settings");		
		return $results[0]['version'];

	}

	function getGlAccountsCount(){
		$id = $_SESSION['office'];
		if (!is_null($id)) {
		   	$data = $this->db->selectData("SELECT count(product_id) FROM acc_ledger_account_mapping where office_id= $id");
		   	if (empty($data)) {
				return 0;
			} else {
				return $data[0][0];
			}
		}
	}

	
	function getMembersCount(){
		$office_id = $_SESSION['office'];
		$result= $this->db->SelectData("SELECT count(c_id) AS num FROM members WHERE office_id='".$office_id."'");
		return  $result;
	}

	function getInstanceActiveMembersCount(){
		$result= $this->db->SelectData("SELECT count(c_id) AS num FROM members WHERE status='Active'");
		return  $result;
	}

	function getInstanceRegisteredMembersCount(){
		$result= $this->db->SelectData("SELECT count(c_id) AS num FROM members");
		return  $result;
	}

	function getSaccosCount(){
		$result= $this->db->SelectData("SELECT count(organisation_name) AS num FROM m_organisation");
		return  $result;
	}

	function getStaffCount(){
		$office_id = $_SESSION['office'];
		$result= $this->db->SelectData("SELECT count(id) AS num FROM m_staff WHERE office_id = '".$office_id."'");
		return  $result;
	}
	
	function getLoansCount(){
		$office_id = $_SESSION['office'];
		$result= $this->db->SelectData("SELECT count(loan_id) AS num FROM m_loan JOIN members ON m_loan.member_id=members.c_id where loan_status='Disbursed' AND members.office_id='".$office_id."'");
		return  $result;
	}
	
	function getSavingsCount(){
		$office_id = $_SESSION['office'];
		$result= $this->db->SelectData("SELECT count(id) AS num FROM m_savings_account JOIN members ON m_savings_account.member_id=members.c_id where account_status='Active' AND members.office_id='".$office_id."'");
		return  $result;
	}
	
	function getSharesCount(){
		$office_id = $_SESSION['office'];
		$result= $this->db->SelectData("SELECT count(share_account_no) AS num FROM share_account JOIN members ON share_account.member_id=members.c_id  where account_status='Active' AND members.office_id='".$office_id."'");
		return  $result;
	}
	
	
	function getMemberAccountsCount(){
		$office_id = $_SESSION['office'];
		$result= $this->db->SelectData("SELECT count(c_id) AS num FROM members WHERE firstname != '' AND lastname != '' AND status='Active' AND office_id='".$office_id."'");
		return  $result;
	}
	
	function getBusinessAccountsCount(){
		$office_id = $_SESSION['office'];
		$result= $this->db->SelectData("SELECT count(c_id) AS num FROM members WHERE company_name != '' AND status='Active' AND office_id='".$office_id."'");
		return  $result;
	}
	
	function getGroupAccountsCount(){
		$office_id = $_SESSION['office'];
		$result= $this->db->SelectData("SELECT count(id) AS num FROM m_savings_account WHERE group_id != 0 AND office_id='".$office_id."'");
		return  $result;
	}
	
	

}