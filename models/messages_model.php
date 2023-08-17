<?php

//error_reporting(0);

class Messages_model extends Model{
	
	public function __construct(){
		parent::__construct();
		$this->logUserActivity(NULL);
		if (!$this->checkTransactionStatus()) {
			header('Location: ' . URL); 
		}
	}

	public function getAllMessageTemplates(){

		/* $result =  $this->db->SelectData("DESCRIBE messages");

		//print_r($result);

		foreach ($result as $key => $value) {
			print_r($value);
			print_r(ucwords(str_replace("_", " ", $value['Field'])));
			echo "</br>";
		}
		die(); */

		$office = $_SESSION['office'];
		$result =  $this->db->SelectData("SELECT * FROM messages AS a JOIN products AS b ON a.applies_to = b.p_id WHERE a.office_id = '$office' AND a.status = 'Active'");

		return $result;
	}

	public function getAllMessageTemplateMethods(){
		return $this->db->SelectData("SELECT * FROM message_template_methods WHERE status = 'Active'");
	}

	public function getMessageDetails($id){
		return $this->db->selectData("SELECT * FROM messages WHERE id=$id");
	}

	public function messagetemplate($data){

		$new_variables = array();
		foreach ($data['variable'] as $key => $value) {
			$new_variables[$value] = $data['method'][$key];
		}
		
		$postData = array(
			'office_id' => $_SESSION['office'],
			'email' => isset($data['email']) ? $data['email'] : "No",
			'telephone' => isset($data['telephone']) ? $data['telephone'] : "No",
			'app' => isset($data['app']) ? $data['app'] : "No",
			'advert' => isset($data['advert']) ? $data['advert'] : "No",
			'title' => $data['title'],
			'details' => $data['details'],
			'record_date' => date("Y-m-d"),
			'message_date' => date_format(date_create($data['date']), "Y-m-d"),
			'frequency' => $data['frequency'],
			/*'applies_to' => isset($data['applies_to']) ? $data['applies_to'] : "All",
			'warnings' => isset($data['warnings']) ? $data['warnings'] : "0",
			'warning_dates' => json_encode($data['warning_dates']), */
			'message_variables' => json_encode($new_variables)
		);

		if($this->db->InsertData('messages', $postData)){
			header('Location: ' . URL . 'messages/?msg=success');
		}else{
			header('Location: ' . URL . 'messages/?msg=failed');
		}
	}

	public function updatemessagetemplate($data, $id){

		$new_variables = array();
		foreach ($data['variable'] as $key => $value) {
			$new_variables[$value] = $data['method'][$key];
		}
		
		$postData = array(
			'office_id' => $_SESSION['office'],
			'email' => isset($data['email']) ? $data['email'] : "No",
			'telephone' => isset($data['telephone']) ? $data['telephone'] : "No",
			'advert' => isset($data['advert']) ? $data['advert'] : "No",
			'title' => $data['title'],
			'details' => $data['details'],
			'record_date' => date("Y-m-d"),
			'message_date' => date_format(date_create($data['date']), "Y-m-d"),
			'frequency' => $data['frequency'],
			'message_variables' => json_encode($new_variables)
		);

		$this->db->UpdateData('messages', $postData,"`id` = '".$id."'");
		header('Location: ' . URL . 'messages/?msg=updated');
	}

}

?>