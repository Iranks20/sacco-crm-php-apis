<?php

class Staff extends Controller{

	public function __construct(){
		parent::__construct();
		// error_reporting(E_ALL);
        // ini_set('display_errors', 1);
	}

	public function index(){
		try {
			$sessionData = json_decode(file_get_contents('php://input'), true);
	
			$details = $this->model->getStaff($sessionData);
	
			header('Content-Type: application/json');
			echo json_encode(array(
				'status' => 200,
				'data' => $details
			));
		} catch (Exception $e) {
			header('Content-Type: application/json');
			echo json_encode(array(
				'status' => 500,
				'message' => 'An error occurred: ' . $e->getMessage()
			));
		}
	}

	function addstaff(){
		try {
			$jsonData = file_get_contents('php://input');
			$data = json_decode($jsonData, true);
	
			if (isset($data['office'])) {
				$office = $data['office'];
	
				$accounts = $this->model->getGLAccounts($office);
				$teller_id = $this->model->getTellerAccountID();
				$accesslevels = $this->model->getAccesslevels();
	
				$response = array(
					'status' => 200, // Success status code
					'message' => 'Staff details fetched successfully.',
					'data' => array(
						'accounts' => $accounts,
						'teller_id' => $teller_id,
						'accesslevels' => $accesslevels
					)
				);
	
				echo json_encode($response);
			} else {
				$response = array(
					'status' => 400, // Bad request status code
					'message' => 'Office value is missing in JSON input.'
				);
	
				http_response_code(400); // Set HTTP status code
				echo json_encode($response);
			}
		} catch (Exception $e) {
			$response = array(
				'status' => 500, // Internal server error status code
				'message' => 'An error occurred: ' . $e->getMessage()
			);
	
			http_response_code(500); // Set HTTP status code
			echo json_encode($response);
		}
	}	

	function insertstaff(){
        $this->model->InsertStaff($_POST);
	}

	public function viewstaffdetails($id){
		try {
			$jsonData = file_get_contents('php://input');
			$data = json_decode($jsonData, true);
	
			if (isset($data)) {
				$office = $data['office'];
				$Isheadoffice = $data['Isheadoffice'];
				// changed function call name from getStaffDetails to getStaffDetail due to  incompatibility issues
				$this->view->details = $this->model->getStaffDetail($id, $office, $Isheadoffice);
	
				$response = array(
					'status' => 200,
					'data' => $this->view->details
				);
	
				echo json_encode($response);
			} else {
				$response = array(
					'status' => 400,
					'message' => 'Office value missing in JSON input.'
				);
	
				http_response_code(400); // Set HTTP status code
				echo json_encode($response);
			}
		} catch (Exception $e) {
			$response = array(
				'status' => 500, // Internal server error status code
				'message' => 'An error occurred: ' . $e->getMessage()
			);
	
			http_response_code(500); // Set HTTP status code
			echo json_encode($response);
		}
	}	
	
	
	function verifystaff($id){
	    $this->model->verifyStaffAccount($id);
	}

	function editstaff($id){
		$this->view->accesslevels = $this->model->getAccesslevels();
		$this->view->details = $this->model->getStaffDetails($id);
		$this->view->account_details = $this->model->getStaffAccountDetails($id);
		$this->view->render('forms/staff/editstaff');
	}

	function updatestaff($id){
		$this->view->allowed_access = $this->model->UpdateStaff($_POST, $id);
	}

	function deletestaff($id){
		$this->model->DeleteStaff($id);
	}

	function resetstaff($id){
		try {
			$jsonData = file_get_contents('php://input');
			$data = json_decode($jsonData, true);
	
			if (!empty($data)) {
				$office = $data['office'];
				$this->model->uu($id, $office);
	
				$response = array(
					'status' => 200,
					'message' => 'Password reset successful.'
				);
	
				echo json_encode($response);
			} else {
				$response = array(
					'status' => 400,
					'message' => 'No data found in JSON input.'
				);
	
				http_response_code(400);
				echo json_encode($response);
			}
		} catch (Exception $e) {
			$response = array(
				'status' => 500,
				'message' => 'An error occurred: ' . $e->getMessage()
			);
	
			http_response_code(500);
			echo json_encode($response);
		}
	}	
	
	function changePassword($id){
	    if($id == $_SESSION['user_id']){
    		$this->view->details = $this->model->getStaffDetails($id);
    		$this->view->render('forms/staff/changepassword');
	    } else {
	        header('Location: ' . URL);
	    }
	}
	
	function updatePassword($id){
	    $this->model->ChangePassword($id);
	}

}