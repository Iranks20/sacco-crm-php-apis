<?php

class Products extends Controller{

	public function __construct(){
		parent::__construct();
		// error_reporting(E_ALL); 
        // ini_set('display_errors', 1);
	}

	/********************  products menu  *******************************/

	function insurance(){
		try {
			$office = $_SERVER['HTTP_OFFICE'];
	
			if (!empty($office)) {	
				$insuranceList = $this->model->InsuranceList($office);
	
				$response = array(
					'status' => 200,
					'message' => 'Insurance list fetched successfully.',
					'data' => $insuranceList
				);
	
				echo json_encode($response);
			} else {
				$response = array(
					'status' => 400,
					'message' => 'Office value is missing in JSON input.'
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

	function index(){
		try {
			$office = null;
	
			// Check if 'office' header is set
			if (isset($_SERVER['HTTP_OFFICE'])) {
				$office = $_SERVER['HTTP_OFFICE'];
			}
	
			if (!empty($office)) {
				$shares = $this->model->getShareProducts($office);
				$loans = $this->model->getLoanProducts($office);
				$savings = $this->model->getSavingsProducts($office);
				$fixed = $this->model->getFixedProducts($office);
				$charges = $this->model->getChargeProducts($office);
				$defaults = $this->model->getDefaultsCount($office);
				$provisioning = $this->model->getProvisioningProducts($office);
				$thirdparty = $this->model->getThirdPartyProductCount($office);
				$insurance = $this->model->getInsuranceProductsCount($office);
	
				$response = array(
					'status' => 200, // Success status code
					'message' => 'Product data fetched successfully.',
					'data' => array(
						'shares' => $shares,
						'loans' => $loans,
						'savings' => $savings,
						'fixed' => $fixed,
						'charges' => $charges,
						'defaults' => $defaults,
						'provisioning' => $provisioning,
						'thirdparty' => $thirdparty,
						'insurance' => $insurance
					)
				);
	
				echo json_encode($response);
			} else {
				$response = array(
					'status' => 400, // Bad request status code
					'message' => 'Office value is missing in request header.'
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
	
	function chargeexemption($id=null){

		$this->view->members = $this->model->getAllMembers();
		
		if (is_null($id)) {
			$this->view->render('forms/products/member_charge_exemptions');
		} else{
			$this->view->id = $id;
			$this->view->charge = $this->model->getChargesDetails($id);
			$this->view->exempted_members = $this->model->getProductExceptions($id);
			$this->view->render('forms/products/charge_exemptions');
		}

	}

	function editexemption($id){
		$this->view->id = $id;
		$member_details = $this->model->getMemberDetails($id);
		$this->view->member = $member_details['firstname'] . " " . $member_details['middlename'] . " " . $member_details['lastname'];
		$this->view->charges = $this->model->getSelectedCharges($id);
		$this->view->allcharges = $this->model->getAllSaccoCharges();
		$this->view->render('forms/products/edit_exemptions');		
	}

	function updateexemptionmembers($id){
		$this->model->updateExemptedMembers($id);
	}

	function updateexemption($id){
		$this->model->editChargeExemption($id);
	}

	function resetexemption($id){
		$this->model->resetChargeExemption($id);
	}

	function defaultproducts(){
		try {
			$office = $_SERVER['HTTP_OFFICE'];
			// $jsonData = json_decode(file_get_contents('php://input'), true);
	
			if (!empty($office)) {
				// $office = $jsonData['office'];
	
				$defaultsCount = $this->model->getDefaultsCount($office);
				$charges = $this->model->getAllCharges($office);
				$savings = $this->model->getAllSavings($office);
				$shares = $this->model->getAllShares($office);
	
				$response = array(
					'status' => 200, // Success status code
					'message' => 'Default products data fetched successfully.',
					'defaults' => $defaultsCount,
					'charges' => $charges,
					'savings' => $savings,
					'shares' => $shares
				);
	
				if ($defaultsCount > 0) {
					$groups = $this->model->getDefaultProducts(-1);
					$chargeGroups = $this->model->getDefaultProducts(6);
					$savingsGroups = $this->model->getDefaultProducts(3);
					$sharesGroups = $this->model->getDefaultProducts(1);
					$wallets = $this->model->getDefaultWalletDetails(5);
	
					$response['groups'] = $groups;
					$response['chargeGroups'] = $chargeGroups;
					$response['savingsGroups'] = $savingsGroups;
					$response['sharesGroups'] = $sharesGroups;
					$response['wallets'] = $wallets;
				}
	
				echo json_encode($response);
			} else {
				$response = array(
					'status' => 400, // Bad request status code
					'message' => 'Missing or invalid input data.'
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

	function editdefaults(){

	 	$this->view->allcharges = $this->model->getAllCharges();
		$this->view->allsavings = $this->model->getAllSavings();
		$this->view->allshares = $this->model->getAllShares();

		$selected_charges = $this->model->getDefaultProducts(6);

		$charges = array();
		$charges_selected = array();
		foreach ($selected_charges as $key => $value) {
			array_push($charges, $value['p_id']);
		}

		foreach ($selected_charges as $key => $value) {
			array_push($charges_selected, $value[0]);
		}
		
		$this->view->charges = $charges;
		$this->view->charges_selected = $charges_selected;
		$this->view->wallets = $this->model->getDefaultWalletDetails(5);
		$this->view->savings = $this->model->getDefaultProducts(3);
		$this->view->shares = $this->model->getDefaultProducts(1);
		$this->view->groups = $this->model->getDefaultProducts(-1);

		$this->view->render('forms/products/editdefaults');
	}

	function updatedefaults(){
		$this->model->updateDefaultProducts($_POST);
	}

	function getSavingsProductDetails($id){
		$this->model->getSavingsProductDetails($id);
	}

	function getSharesProductDetails($id){
		$this->model->getSharesProductDetails($id);
	}

	function createdefaults(){
		$this->model->createdefaults($_POST);
	}

	function importthdp(){

		$this->model->importThirdpartyProducts(TRUE);
	}

	function addglpointersloan($id){

		$this->view->hastransactions = $this->model->hastransacted();
		$product_type=2;
		
		$this->view->product=$id;	
	 	$this->view->transaction_types = $this->model->getMissingTransactionTypes($id, $product_type);
	 	$this->view->created= $this->model->getPointers($id,$product_type);
	 	$this->view->payment_modes= $this->model->getPaymentModes();
	 	$this->view->glaccounts = $this->model->getGlaccounts();

		$types = $this->model->getTransactionTypes($product_type);
		$created_pointers = $this->model->getPointers($id,$product_type);

		$joint1 = array();
		if ($types > 0) {
			foreach ($types as $key => $value) {
				$joint1[$key] = $value['transaction_type_name'];
			}
		}

		$joint2 = array();
		if ($created_pointers > 0) {
			foreach ($created_pointers as $key => $value) {
				$joint2[$key] = $value['transaction_type'];
			}
		}

		$missing = array_diff($joint1, $joint2);
		$this->view->missing = $missing;
		$this->view->product_type = $product_type;
		$imported_accounts = $this->model->checkIfSaccoImportedAccounts($id);
		$this->view->imported_accounts = $imported_accounts;

		if ($imported_accounts) {
			$this->view->missing_pointers = $this->model->getMissingPointers($missing);
		}
		$this->view->render('forms/products/loan_pointers_form');
	}

	function editglpointersloan($product_id, $pointer_id){

		$this->view->hastransactions = $this->model->hastransacted();

		$product_type=2;
		$this->view->product=$product_id;	
	 	$this->view->transaction_types = $this->model->getTransactionTypes($product_type);
	 	$this->view->payment_modes= $this->model->getPaymentModes();
	 	$this->view->created= $this->model->getPointers($product_id,$product_type);
	 	$this->view->glaccounts = $this->model->getGlaccounts();

		$types = $this->model->getTransactionTypes($product_type);
		$created_pointers = $this->model->getPointers($product_id,$product_type);

		$joint1 = array();
		if ($types > 0) {
			foreach ($types as $key => $value) {
				$joint1[$key] = $value['transaction_type_name'];
			}
		}

		$joint2 = array();
		if ($created_pointers > 0) {
			foreach ($created_pointers as $key => $value) {
				$joint2[$key] = $value['transaction_type'];
			}
		}

		$this->view->missing = array_diff($joint1, $joint2);
	 	$this->view->id = $product_id;	
		$this->view->pointer_details = $this->model->getPointerDetails($pointer_id, $product_id, $product_type);

		$this->view->render('forms/products/editglpointersloan');
	}

	function updateglloan($id){
		$this->model->UpdateGlAccountLoan($_POST, $id);
	}

	function addglpointersequity($id){

		$this->view->hastransactions = $this->model->hastransacted();

		$product_type=1;
		$this->view->product=$id;	
	 	$this->view->transaction_types = $this->model->getMissingTransactionTypes($id, $product_type);
	 	$this->view->payment_modes= $this->model->getPaymentModes();
	 	$this->view->created= $this->model->getPointers($id,$product_type);
	 	$this->view->glaccounts = $this->model->getGlaccounts();

		$types = $this->model->getTransactionTypes($product_type);
		$created_pointers = $this->model->getPointers($id,$product_type);

		$joint1 = array();
		if ($types > 0) {
			foreach ($types as $key => $value) {
				$joint1[$key] = $value['transaction_type_name'];
			}
		}

		$joint2 = array();
		if ($created_pointers > 0) {
			foreach ($created_pointers as $key => $value) {
				$joint2[$key] = $value['transaction_type'];
			}
		}

		$missing = array_diff($joint1, $joint2);
		$this->view->missing = $missing;
		$this->view->product_type = $product_type;
		$imported_accounts = $this->model->checkIfSaccoImportedAccounts($id);
		$this->view->imported_accounts = $imported_accounts;

		if ($imported_accounts) {
			$this->view->missing_pointers = $this->model->getMissingPointers($missing);
		}
		$this->view->render('forms/products/equity_pointers_form');

	}

	function importall($product_type, $id){

		$missing_transaction_types = array();
		
		foreach ($_GET as $key => $value) {
			if (is_int($key)) {
				array_push($missing_transaction_types, $value);
			}
		}
		
		foreach ($missing_transaction_types as $key => $value) {
			$this->model->addMissingTransTypesPointers($value, $id, $product_type);
		}

		header('Location: ' . $_SERVER['HTTP_REFERER'] . '?msg=imported');
	}

	function editglpointersequity($product_id, $pointer_id){

		$this->view->hastransactions = $this->model->hastransacted();

		$product_type=1;
		$this->view->product=$product_id;	
	 	$this->view->transaction_types = $this->model->getTransactionTypes($product_type);
	 	$this->view->payment_modes= $this->model->getPaymentModes();
	 	$this->view->created= $this->model->getPointers($product_id,$product_type);
	 	$this->view->glaccounts = $this->model->getGlaccounts();

		$types = $this->model->getTransactionTypes($product_type);
		$created_pointers = $this->model->getPointers($product_id,$product_type);

		$joint1 = array();
		if ($types > 0) {
			foreach ($types as $key => $value) {
				$joint1[$key] = $value['transaction_type_name'];
			}
		}

		$joint2 = array();
		if ($created_pointers > 0) {
			foreach ($created_pointers as $key => $value) {
				$joint2[$key] = $value['transaction_type'];
			}
		}

		$this->view->missing = array_diff($joint1, $joint2);
	 	$this->view->id = $product_id;	
		$this->view->pointer_details = $this->model->getPointerDetails($pointer_id, $product_id, $product_type);

		$this->view->render('forms/products/editglpointersequity');
	}

	function updateglequity($id){
		$this->model->UpdateGlAccountEquity($_POST, $id);
	}


	//////////////////////insurance//////////////////////
	function addglpointersinsurance($id){

		$this->view->hastransactions = $this->model->hastransacted();

		$product_type=8;
		$this->view->product=$id;	
	 	$this->view->transaction_types = $this->model->getMissingTransactionTypes($id, $product_type);
	 	$this->view->payment_modes= $this->model->getPaymentModes();
	 	$this->view->created= $this->model->getPointers($id,$product_type);
	 	$this->view->glaccounts = $this->model->getGlaccounts();

		$types = $this->model->getTransactionTypes($product_type);
		$created_pointers = $this->model->getPointers($id,$product_type);

		$joint1 = array();
		if ($types > 0) {
			foreach ($types as $key => $value) {
				$joint1[$key] = $value['transaction_type_name'];
			}
		}

		$joint2 = array();
		if ($created_pointers > 0) {
			foreach ($created_pointers as $key => $value) {
				$joint2[$key] = $value['transaction_type'];
			}
		}

		$missing = array_diff($joint1, $joint2);
		$this->view->missing = $missing;
		$this->view->product_type = $product_type;
		$imported_accounts = $this->model->checkIfSaccoImportedAccounts($id);
		$this->view->imported_accounts = $imported_accounts;

		if ($imported_accounts) {
			$this->view->missing_pointers = $this->model->getMissingPointers($missing);
		}
		$this->view->render('forms/products/insurance_pointers_form');

	}

	function editglpointersinsurance($product_id, $pointer_id){

		$this->view->hastransactions = $this->model->hastransacted();

		$product_type=8;
		$this->view->product=$product_id;	
	 	$this->view->transaction_types = $this->model->getTransactionTypes($product_type);
	 	$this->view->payment_modes= $this->model->getPaymentModes();
	 	$this->view->created= $this->model->getPointers($product_id,$product_type);
	 	$this->view->glaccounts = $this->model->getGlaccounts();

		$types = $this->model->getTransactionTypes($product_type);
		$created_pointers = $this->model->getPointers($product_id,$product_type);

		$joint1 = array();
		if ($types > 0) {
			foreach ($types as $key => $value) {
				$joint1[$key] = $value['transaction_type_name'];
			}
		}

		$joint2 = array();
		if ($created_pointers > 0) {
			foreach ($created_pointers as $key => $value) {
				$joint2[$key] = $value['transaction_type'];
			}
		}

		$this->view->missing = array_diff($joint1, $joint2);
	 	$this->view->id = $product_id;	
		$this->view->pointer_details = $this->model->getPointerDetails($pointer_id, $product_id, $product_type);

		$this->view->render('forms/products/editglpointersequity');
	}

	function updateglinsurance($id){
		$this->model->UpdateGlAccountInsurance($_POST, $id);
	}
	/////////////////////////////////////////////////////

	// function addSavingsGlpointers($id){

	// 	$this->view->hastransactions = $this->model->hastransacted();

	// 	$product_type=3;
	// 	$this->view->product=$id;
	// 	$this->view->transaction_types = $this->model->getMissingTransactionTypes($id, $product_type);
	// 	$this->view->payment_modes= $this->model->getPaymentModes();
	// 	$this->view->created= $this->model->getPointers($id,$product_type);
	// 	$this->view->glaccounts = $this->model->getGlaccounts();

	// 	$types = $this->model->getTransactionTypes($product_type);
	// 	$created_pointers = $this->model->getPointers($id,$product_type);

	// 	$joint1 = array();
	// 	if ($types > 0) {
	// 		foreach ($types as $key => $value) {
	// 			$joint1[$key] = $value['transaction_type_name'];
	// 		}
	// 	}

	// 	$joint2 = array();
	// 	if ($created_pointers > 0) {
	// 		foreach ($created_pointers as $key => $value) {
	// 			$joint2[$key] = $value['transaction_type'];
	// 		}
	// 	}

	// 	$missing = array_diff($joint1, $joint2);
	// 	$this->view->missing = $missing;
	// 	$this->view->prodproduct_typeuct_type = $;
	// 	$imported_accounts = $this->model->checkIfSaccoImportedAccounts($id);
	// 	$this->view->imported_accounts = $imported_accounts;

	// 	if ($imported_accounts) {
	// 		$this->view->missing_pointers = $this->model->getMissingPointers($missing);
	// 	}
	// 	$this->view->render('forms/products/savings_pointers_form');

	// }

	function addSavingsGlpointers($id) {
		try {
			$headers = getallheaders();
			$office = isset($headers['office']) ? $headers['office'] : null;
	
			if ($office === null) {
				$response = array(
					'status' => 400,
					'message' => 'Required header (office_id) is missing.'
				);
			} else {
				// Proceed with the function logic
				$hastransactions = $this->model->hastransacted($office);
	
				$product_type = 3;
				$transaction_types = $this->model->getMissingTransactionTypes($id, $product_type, $office);
				$payment_modes = $this->model->getPaymentModes();
				$created = $this->model->getPointers($id, $product_type, $office);
				$glaccounts = $this->model->getGlaccounts();
	
				$types = $this->model->getTransactionTypes($product_type);
				$created_pointers = $this->model->getPointers($id, $product_type, $office);
	
				$joint1 = array();
				if ($types > 0) {
					foreach ($types as $key => $value) {
						$joint1[$key] = $value['transaction_type_name'];
					}
				}
	
				$joint2 = array();
				if ($created_pointers > 0) {
					foreach ($created_pointers as $key => $value) {
						$joint2[$key] = $value['transaction_type'];
					}
				}
	
				$missing = array_diff($joint1, $joint2);
	
				$product_type = $product_type;
				$imported_accounts = $this->model->checkIfSaccoImportedAccounts($id, $office);
	
				$response = array(
					'status' => 200,
					'message' => 'Success',
					'data' => array(
						'hastransactions' => $hastransactions,
						'product' => $id,
						'transaction_types' => $transaction_types,
						'payment_modes' => $payment_modes,
						'created' => $created,
						'glaccounts' => $glaccounts,
						'missing' => $missing,
						'product_type' => $product_type,
						'imported_accounts' => $imported_accounts,
						'missing_pointers' => $imported_accounts ? $this->model->getMissingPointers($missing) : null
					)
				);
			}
	
			http_response_code($response['status']);
			echo json_encode($response);
		} catch (Exception $e) {
			$response = array(
				'status' => 500,
				'message' => 'An error occurred: ' . $e->getMessage()
			);
	
			http_response_code($response['status']);
			echo json_encode($response);
		}
	}
	

	function editglpointerssavings($product_id, $pointer_id){

		$this->view->hastransactions = $this->model->hastransacted();

		$product_type=3;
		$this->view->product=$product_id;	
	 	$this->view->transaction_types = $this->model->getTransactionTypes($product_type);
	 	$this->view->payment_modes= $this->model->getPaymentModes();
	 	$this->view->created= $this->model->getPointers($product_id,$product_type);
	 	$this->view->glaccounts = $this->model->getGlaccounts();

		$types = $this->model->getTransactionTypes($product_type);
		$created_pointers = $this->model->getPointers($product_id,$product_type);

		$joint1 = array();
		if ($types > 0) {
			foreach ($types as $key => $value) {
				$joint1[$key] = $value['transaction_type_name'];
			}
		}

		$joint2 = array();
		if ($created_pointers > 0) {
			foreach ($created_pointers as $key => $value) {
				$joint2[$key] = $value['transaction_type'];
			}
		}

		$this->view->missing = array_diff($joint1, $joint2);
	 	$this->view->id = $product_id;	
		$this->view->pointer_details = $this->model->getPointerDetails($pointer_id, $product_id, $product_type);

		$this->view->render('forms/products/editglpointerssavings');
	}

	function updateglsavings($id){
		$this->model->UpdateGlAccountSavings($_POST, $id);
	}

	function addglpointersTime($id){

		$this->view->hastransactions = $this->model->hastransacted();

		$product_type=4;
		$this->view->product=$id;	
		$this->view->transaction_types = $this->model->getMissingTransactionTypes($id, $product_type);
		$this->view->payment_modes= $this->model->getPaymentModes();
		$this->view->created= $this->model->getPointers($id,$product_type);
		$this->view->glaccounts = $this->model->getGlaccounts();

		$types = $this->model->getTransactionTypes($product_type);
		$created_pointers = $this->model->getPointers($id,$product_type);

		$joint1 = array();
		if ($types > 0) {
			foreach ($types as $key => $value) {
				$joint1[$key] = $value['transaction_type_name'];
			}
		}

		$joint2 = array();
		if ($created_pointers > 0) {
			foreach ($created_pointers as $key => $value) {
				$joint2[$key] = $value['transaction_type'];
			}
		}

		$missing = array_diff($joint1, $joint2);
		$this->view->missing = $missing;
		$this->view->product_type = $product_type;
		$imported_accounts = $this->model->checkIfSaccoImportedAccounts($id);
		$this->view->imported_accounts = $imported_accounts;

		if ($imported_accounts) {
			$this->view->missing_pointers = $this->model->getMissingPointers($missing);
		}
		$this->view->render('forms/products/fixed_pointers_form');

	}

	function editglpointersfixed($product_id, $pointer_id){

		$this->view->hastransactions = $this->model->hastransacted();

		$product_type=4;
		$this->view->product=$product_id;	
	 	$this->view->transaction_types = $this->model->getTransactionTypes($product_type);
	 	$this->view->payment_modes= $this->model->getPaymentModes();
	 	$this->view->created= $this->model->getPointers($product_id,$product_type);
	 	$this->view->glaccounts = $this->model->getGlaccounts();

		$types = $this->model->getTransactionTypes($product_type);
		$created_pointers = $this->model->getPointers($product_id,$product_type);

		$joint1 = array();
		if ($types > 0) {
			foreach ($types as $key => $value) {
				$joint1[$key] = $value['transaction_type_name'];
			}
		}

		$joint2 = array();
		if ($created_pointers > 0) {
			foreach ($created_pointers as $key => $value) {
				$joint2[$key] = $value['transaction_type'];
			}
		}

		$this->view->missing = array_diff($joint1, $joint2);
	 	$this->view->id = $product_id;	
		$this->view->pointer_details = $this->model->getPointerDetails($pointer_id, $product_id, $product_type);

		$this->view->render('forms/products/editglpointersfixed');
	}

	function updateglfixed($id){
		$this->model->UpdateGlAccountFixed($_POST, $id);
	}

	function addglpointersOther($id){
		$this->view->hastransactions = $this->model->hastransacted();
		$product_type = 6;
		$this->view->product = $id;	
		$charge = $this->model->getCharge($id);
		//$product_type = $charge[0]['charge_applies_to'];
		

		$this->view->transaction_types = $this->model->getChargeTransactionTypes($id, $product_type);
		$this->view->payment_modes = $this->model->getPaymentModes();
		$this->view->created = $this->model->getPointers($id, $product_type, $charge[0]['transaction_type_id']);
		$this->view->glaccounts = $this->model->getGlaccounts();

		$types = $this->model->getChargeTransactionTypes($id, $product_type, $charge[0]['transaction_type_id']);
		$created_pointers = $this->model->getPointers($id,$product_type);

		$joint1 = array();
		if ($types > 0) {
			foreach ($types as $key => $value) {
				$joint1[$key] = $value['transaction_type_name'];
			}
		}

		$joint2 = array();
		if ($created_pointers > 0) {
			foreach ($created_pointers as $key => $value) {
				$joint2[$key] = $value['transaction_type'];
			}
		}

		$missing = array_diff($joint1, $joint2);
		$this->view->missing = $missing;
		$this->view->product_type = $product_type;
		$imported_accounts = $this->model->checkIfSaccoImportedAccounts($id);
		$this->view->imported_accounts = $imported_accounts;

		if ($imported_accounts) {
			$this->view->missing_pointers = $this->model->getMissingPointers($missing);
		}
		$this->view->render('forms/products/charge_pointers_form');

	}

	function addglpointersWallet($id){
		
		$this->view->hastransactions = $this->model->hastransacted();
		
		$product_type = 5;
		$this->view->product=$id;	
		$this->view->transaction_types = $this->model->getMissingTransactionTypes($id, $product_type);
		$this->view->payment_modes= $this->model->getPaymentModes();
		$this->view->created= $this->model->getPointers($id,$product_type);
		$this->view->glaccounts = $this->model->getGlaccounts();

		$types = $this->model->getTransactionTypes($product_type);
		$created_pointers = $this->model->getPointers($id,$product_type);

		$joint1 = array();
		foreach ($types as $key => $value) {
			$joint1[$key] = $value['transaction_type_name'];
		}

		$joint2 = array();
		if(!empty($created_pointers)){
    		foreach ($created_pointers as $key => $value) {
    			$joint2[$key] = $value['transaction_type'];
    		}
        }

		$missing = array_diff($joint1, $joint2);
		$this->view->missing = $missing;
		$this->view->product_type = $product_type;
		$imported_accounts = $this->model->checkIfSaccoImportedAccounts($id);
		$this->view->imported_accounts = $imported_accounts;

		if ($imported_accounts) {
			$this->view->missing_pointers = $this->model->getMissingPointers($missing);
		}
		$this->view->render('forms/products/wallet_pointers_form');

	}

	function getdestination($acc){	
		$this->model->getdestination($acc);
		
	}

	function creategloangls(){	
		$this->model->creategloangls();
		
	}

	function createtimegl(){	
		$this->model->createtimegl();
		
	}

	function createglequity(){
		try {
			$jsonData = file_get_contents('php://input');
			$data = json_decode($jsonData, true);
	
			if ($data === null) {
				$response = array(
					'status' => 400,
					'message' => 'Invalid JSON input.'
				);
	
				http_response_code(400);
				echo json_encode($response);
				return;
			}
	
			$result = $this->model->createglequity($data);
	
			if ($result) {
				$response = array(
					'status' => 200,
					'message' => 'GLEquity created successfully.'
				);
			} else {
				$response = array(
					'status' => 500,
					'message' => 'GLEquity creation failed.'
				);
			}
	
			echo json_encode($response);
		} catch (Exception $e) {
			$response = array(
				'status' => 500,
				'message' => 'An error occurred: ' . $e->getMessage()
			);
	
			http_response_code(500);
			echo json_encode($response);
		}
	}	

	function createglinsurance(){
		$this->model->createglinsurance();
	}

	// function createglsaving(){	
	// 	$this->model->createglsaving();
		
	// }

	function createglsaving() {
		try {
			$headers = getallheaders();
			$office = isset($headers['office']) ? $headers['office'] : null;
	
			if ($office === null) {
				$response = array(
					'status' => 400,
					'message' => 'Required header (office_id) is missing.'
				);
			} else {
				// Proceed with the function logic
				$json_input = file_get_contents('php://input');
				$data = json_decode($json_input, true);
	
				$data['office'] = $office; // Add the office ID from headers to your data
	
				$this->model->createglsaving($data);
	
				$response = array(
					'status' => 200,
					'message' => 'Success'
				);
			}
	
			http_response_code($response['status']);
			echo json_encode($response);
		} catch (Exception $e) {
			$response = array(
				'status' => 500,
				'message' => 'An error occurred: ' . $e->getMessage()
			);
	
			http_response_code($response['status']);
			echo json_encode($response);
		}
	}	

	function createglother(){	
		$this->model->createglother();
		
	}

	function createglwallet(){	
		$this->model->createglwallet();
	}

	/* ----Loan products  ******************/
	function loanproducts(){
		try {
			$office = $_SERVER['HTTP_OFFICE'];	
			if (!empty($office)) {	
				$loanProductList = $this->model->loanproductList($office);
	
				$response = array(
					'status' => 200,
					'message' => 'Loan products list fetched successfully.',
					'data' => $loanProductList
				);
	
				echo json_encode($response);
			} else {
				$response = array(
					'status' => 400,
					'message' => 'Office value is missing in headers.'
				);
	
				http_response_code(400);
				echo json_encode($response);
			}
		} catch (Exception $e) {
			$response = array(
				'status' => 500,
				'message' => 'An error occurred: ' . $e->getMessage()
			);
	
			http_response_code(500); // Set HTTP status code
			echo json_encode($response);
		}
	}	

	// function newquickloanproduct(){
	// 	$this->view->loanproductList = $this->model->loanproductList();
	// 	$this->view->render('forms/products/newquickloanproduct');
	// }

	function newquickloanproduct(){
		try {
			if (isset($_SERVER['HTTP_OFFICE'])) {
				$office = $_SERVER['HTTP_OFFICE'];
	
				$loanProductList = $this->model->loanproductList($office);
	
				$response = array(
					'status' => 200,
					'message' => 'Loan product list fetched successfully.',
					'data' => $loanProductList
				);
	
				echo json_encode($response);
			} else {
				$response = array(
					'status' => 400,
					'message' => 'Office value is missing in headers.'
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
	

	function viewLoanProducts($id){
		try {
			$office = $_SERVER['HTTP_OFFICE'];
	
			if (!empty($office)) {
	
				$hastransactions = $this->model->hastransacted($office);
				$product_type = 2;
				$created = $this->model->getPointers($id, $office, $product_type);
				$loanproduct = $this->model->getloanproduct($id);
				$collateral = $this->model->getcollateral($id);
				$charges = $this->model->getloanProductcharges($id);
				$curname = $this->model->currency();
	
				$response = array(
					'status' => 200,
					'message' => 'Loan product details fetched successfully.',
					'data' => array(
						'hastransactions' => $hastransactions,
						'created' => $created,
						'loanproduct' => $loanproduct,
						'collateral' => $collateral,
						'charges' => $charges,
						'curname' => $curname
					)
				);
	
				echo json_encode($response);
			} else {
				$response = array(
					'status' => 400,
					'message' => 'Office value is missing in JSON input.'
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

	function newloanProduct(){
		try {
			$office = $_SERVER['HTTP_OFFICE'];
	
			if (!empty($office)) {	
				$currency = $this->model->currency();
				$officeList = $this->model->officeList();
				$assets = $this->model->getAssets();
				$liability = $this->model->getLiability();
				$equity = $this->model->getEquity();
				$income = $this->model->getIncome();
				$expenses = $this->model->getExpenses();
				$id = 2;
				$charges = $this->model->getCharges($id, $office);
	
				$response = array(
					'status' => 200,
					'message' => 'Loan product details fetched successfully.',
					'data' => array(
						'currency' => $currency,
						'officeList' => $officeList,
						'assets' => $assets,
						'liability' => $liability,
						'equity' => $equity,
						'income' => $income,
						'expenses' => $expenses,
						'charges' => $charges
					)
				);
	
				echo json_encode($response);
			} else {
				$response = array(
					'status' => 400,
					'message' => 'Office value is missing in JSON input.'
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

	// function createnewloanproduct(){	
	// 	$data = $_POST;	
	// 	$rs = $this->model->createnewloanproduct($data);		
	// 	echo json_encode($rs);
	// }
	function createnewloanproduct(){
		try {
			$jsonData = file_get_contents('php://input');
			$data = json_decode($jsonData, true);
	
			if (empty($data)) {
				$response = array(
					'status' => 400,
					'message' => 'Invalid JSON input.'
				);
	
				http_response_code(400);
				echo json_encode($response);
				return;
			}
			$rs = $this->model->createnewloanproduct($data);
			echo json_encode($rs);
		} catch (Exception $e) {
			$response = array(
				'status' => 500,
				'message' => 'An error occurred: ' . $e->getMessage()
			);
	
			http_response_code(500);
			echo json_encode($response);
		}
	}	

	function editloanproduct($id){
		$this->view->currency  = $this->model->currency();
		$this->view->assets=$this->model->getAssets();
		$this->view->liability=$this->model->getLiability();
		$this->view->equity=$this->model->getEquity();
		$this->view->income=$this->model->getIncome();
		$this->view->expenses=$this->model->getExpenses();
		$this->view->getcharges=$this->model->getCharges(2);
		$this->view->loanproduct = $this->model->getloanproduct($id);
		$this->view->check_if_used = $this->model->checkForLoan($id);
		if(count($this->view->check_if_used)>0){
			header('Location: ' . URL . 'products/viewloanproducts/'.$id.'?msg=ProductUsed');  		
		}else{
			$this->view->render('forms/products/editenewloanproduct');
		}
	}
	
	function editshareproduct($id){
	    
		$this->view->assets=$this->model->getAssets();
		$this->view->liability=$this->model->getLiability();
		$this->view->equity=$this->model->getEquity();
		$this->view->income=$this->model->getIncome();
		$this->view->check_if_used = $this->model->checkForLoan($id);
		
		$this->view->details = $this->model->getshareProduct($id);
		
		$this->view->render('forms/products/editenewshareproduct');
	}

    function updateshare($id){
		$this->model->Updatenewshareproduct($id);	
    }
    
	function Updatenewloanproduct(){		
		$data=$_POST;
		$this->model->Updatenewloanproduct($data);			   
	}
		   
	function Deleteloanproduct($id){		
		$this->model->Deleteloanproduct($id);			   
	}


	/********************  Savings products  *******************************/
	function savingsProducts(){
		try {
			$office = $_SERVER['HTTP_OFFICE'];	
			if (!empty($office)) {

				$savingsProductsList = $this->model->savingsProductsList($office);
	
				$response = array(
					'status' => 200,
					'message' => 'Savings products list fetched successfully.',
					'data' => $savingsProductsList
				);
	
				echo json_encode($response);
			} else {
				$response = array(
					'status' => 400,
					'message' => 'Office value is missing in headers.'
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

	function productdetails($id){
		try {
			$office = $_SERVER['HTTP_OFFICE'];
	
			if (!empty($office)) {
	
				$hastransactions = $this->model->hastransacted($office);
				$product_type = 3;
				$created = $this->model->getPointers($id, $office, $product_type);
				$savings = $this->model->productdetails($id);
	
				$response = array(
					'status' => 200,
					'message' => 'Product details fetched successfully.',
					'data' => array(
						'hastransactions' => $hastransactions,
						'created' => $created,
						'savings' => $savings
					)
				);
	
				echo json_encode($response);
			} else {
				$response = array(
					'status' => 400,
					'message' => 'Office value is missing in JSON input.'
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
	function newSavingsProduct(){
		try {
			$headers = getallheaders();
	
			if (isset($headers['Office'])) {
				$office = $headers['Office'];
	
				$currency = $this->model->currency();
				$assets = $this->model->getAssets();
				$liability = $this->model->getLiability();
				$equity = $this->model->getEquity();
				$income = $this->model->getIncome();
				$expenses = $this->model->getExpenses();
				$id = 3;
				$charges = $this->model->getCharges($id, $office);
	
				$response = array(
					'status' => 200,
					'currency' => $currency,
					'assets' => $assets,
					'liability' => $liability,
					'equity' => $equity,
					'income' => $income,
					'expenses' => $expenses,
					'charges' => $charges
				);
	
				echo json_encode($response);
			} else {
				$response = array(
					'status' => 400,
					'message' => 'Office header is missing in the HTTP request.'
				);
	
				http_response_code(400);
				echo json_encode($response);
			}
		} catch (Exception $e) {
			$response = array(
				'status' => 500,
				'message' => 'An error occurred: ' . $e->getMessage()
			);
	
			http_response_code(500); // Set HTTP status code
			echo json_encode($response);
		}
	}

	function EditSavingProduct($id) {
		try {
			$headers = getallheaders();
			$office = isset($headers['office']) ? $headers['office'] : null;
	
			if ($office === null) {
				$response = array(
					'status' => 400,
					'message' => 'Required header (office_id) is missing.'
				);
			} else {
				$currency = $this->model->currency();
				$assets = $this->model->getAssets();
				$liability = $this->model->getLiability();
				$equity = $this->model->getEquity();
				$income = $this->model->getIncome();
				$expenses = $this->model->getExpenses();
				$id = 3;
				$charges = $this->model->getCharges($id, $office);
	
				$savingsProductDetails = $this->model->productdetails($id);
	
				if ($savingsProductDetails) {
					$response = array(
						'status' => 200,
						'message' => 'Savings product details retrieved successfully.',
						'data' => array(
							'currency' => $currency,
							'assets' => $assets,
							'liability' => $liability,
							'equity' => $equity,
							'income' => $income,
							'expenses' => $expenses,
							'charges' => $charges,
							'savings_product_details' => $savingsProductDetails
						)
					);
				} else {
					$response = array(
						'status' => 404,
						'message' => 'Savings product not found.'
					);
				}
			}
	
			http_response_code($response['status']);
			echo json_encode($response);
		} catch (Exception $e) {
			$response = array(
				'status' => 500,
				'message' => 'An error occurred: ' . $e->getMessage()
			);
	
			http_response_code($response['status']);
			echo json_encode($response);
		}
	}	

	function UpdateSavingProduct($id) {
		try {
			$data = json_decode(file_get_contents("php://input"), true);
			if (!$data) {
				$response = array(
					'status' => 400,
					'message' => 'Invalid JSON input.'
				);
			} else {
				$result = $this->model->UpdateSavingProduct($id, $data);
				if ($result) {
					$response = array(
						'status' => 200,
						'message' => 'Savings product updated successfully.'
					);
				} else {
					$response = array(
						'status' => 500,
						'message' => 'An error occurred while updating the savings product.'
					);
				}
			}
		} catch (Exception $e) {
			$response = array(
				'status' => 500,
				'message' => 'An error occurred: ' . $e->getMessage()
			);
		}
	
		header('Content-Type: application/json');
		http_response_code($response['status']);
		echo json_encode($response);
	}	

	function loandetails($id){
		$this->view->loan = $this->model->loandetails($id);
		$this->view->currency = $this->model->currency();
		$this->view->staff = $this->model->getstaffList($this->view->loan[0]['loan_officer_id']);
		$this->view->members = $this->model->getclient($this->view->loan[0]["member_id"]);
		
		if($this->view->loan[0]["loan_status"]=='Disbursed'){
			$this->view->schedule = $this->model->scheduledetails($id);
		}else{
			$this->view->schedule = $this->model->repaymentCalculations($id);	
		}	
		$this->view->render('forms/products/loan_details');
	}

	function loanProductCollateralview($id){
		$this->model->loanProductCollateralview($id);
	}

	function customersupportshedule($id,$p,$np,$d1){
		$this->model->customersupportshedule($id,$p,$np,$d1);	
	}

	function loancustomersupport(){
		$this->view->product = $this->model->loanproductList();	
		$this->view->render('forms/products/loancustomersupport');
	}

	function specifiedRepayment($id){
		$this->view->loan = $this->model->loandetails($id);
		$this->view->currency = $this->model->currency();
		$this->view->staff = $this->model->getstaffList($this->view->loan[0]['loan_officer_id']);
		$this->view->members = $this->model->getclient($this->view->loan[0]["member_id"]);
		
		if($this->view->loan[0]["loan_status"]=='Disbursed'){
			$this->view->schedule = $this->model->scheduledetails($id);	
		}else{		
			$this->view->schedule = $this->model->repaymentCalculations($id);	
		}
		$this->view->render('forms/products/specified_repayment');
	}

	function loandetailsreports($id){
		
		$this->view->loan = $this->model->loandetails($id);
		$this->view->currency = $this->model->currency();
		$this->view->staff = $this->model->getstaffList($this->view->loan[0]['loan_officer_id']);
		$this->view->members = $this->model->getclient($this->view->loan[0]["member_id"]);
		$this->view->membername = $this->model->getMemberName($this->view->loan[0]["member_id"]);
		$this->view->loanproduct = $this->model->getclient($this->view->loan[0]["member_id"]);
		//$this->view->members = $this->model->getClientDetails($id);	
	     //$this->view->age = $this->model->getClientAge($id);	
		$this->view->loanproduct = $this->model->getloanproduct($this->view->loan[0]["product_id"]);
		$this->view->curname = $this->model->currency();
		$this->view->schedule = $this->model->scheduledetails_due($id);	
		$this->view->loantransaction = $this->model->loantransaction($id);	
		$this->view->m_loan_collateral = $this->model->m_loan_collateral($id);
		$this->view->m_loan_charge = $this->model->m_loan_charge($id);
		$this->view->insurance_stampduty = $this->model->insurance_stampduty($id);	
		
		$this->view->render('forms/products/loan_details_reports');

	}

	function loanAccountEquiry($id){
		
		$this->view->loan = $this->model->loandetails($id);
		$this->view->currency = $this->model->currency();	
		$this->view->schedule = $this->model->scheduledetails_due($id);	
		$this->view->schedule_details = $this->model->scheduledetails($id);	
		$this->view->paid = $this->model->scheduledetailspaid($id);	
		$this->view->loantransaction = $this->model->loantransaction($id);	
		$this->view->m_loan_collateral = $this->model->m_loan_collateral($id);
		$this->view->m_loan_charge = $this->model->m_loan_charge($id);
		$this->view->insurance_stampduty = $this->model->insurance_stampduty($id);
		$this->view->staff = $this->model->getstaffList($this->view->loan[0]['loan_officer_id']);
		$this->view->members = $this->model->getclient($this->view->loan[0]["member_id"]);
		//$this->view->loanproduct = $this->model->getclient($this->view->loan[0]["member_id"]);
		//$this->view->members = $this->model->getClientDetails($id);	
	    //$this->view->age = $this->model->getClientAge($id);	
		$this->view->loanproduct = $this->model->getloanproduct($this->view->loan[0]["product_id"]);
		$this->view->curname = $this->model->currency();
			
		$this->view->render('forms/products/loan_account_equiry');

	}

	function viewloanstatement($id){
		
		$this->view->loans = $this->model->loandetails($id);
		$this->view->clientsavings = $this->model->savingsaccountdetails($this->view->loans[0]['member_id']);
		$this->view->clientsaving = $this->model->getClientSavingsdDetails($this->view->loans[0]['member_id']);
		$this->view->currency = $this->model->currency();
		$this->view->members = $this->model->getclient($this->view->loans[0]["member_id"]);
		$this->view->schedule = $this->model->scheduledetails_due($id);
		$this->view->loanproduct = $this->model->getloanproduct($this->view->loans[0]["product_id"]);
		$this->view->loan = $this->model->getClientLoans($this->view->loans[0]["member_id"]);
		$this->view->schedule_details = $this->model->scheduledetails($id);	
		$this->view->paid = $this->model->scheduledetailspaid($id);	
		$this->view->loantransaction = $this->model->loantransaction($id);	
		$this->view->m_loan_collateral = $this->model->m_loan_collateral($id);
		$this->view->m_loan_charge = $this->model->m_loan_charge($id);
		$this->view->insurance_stampduty = $this->model->insurance_stampduty($id);
		
		$this->view->render('forms/products/view_loan_statement');

	}

	function loanStatementOption($id){
		
		$this->view->loans = $this->model->loandetails($id);
		$this->view->render('forms/products/loanstatementoption');

	}

	function getLoanStatement(){
		
		$data = $_POST;
		$loan_id =$data['loan_id'];
		$this->view->transaction = $this->model->getLoanStatement();
		$this->view->loans = $this->model->loandetails($loan_id);
		$this->view->members = $this->model->getclient($this->view->loans[0]["member_id"]);
		$this->view->membername = $this->model->getMemberName($this->view->loans[0]["member_id"]);
		$this->view->loanproduct = $this->model->getloanproduct($this->view->loans[0]["product_id"]);
		$this->view->render('forms/products/view_loan_statement');

	}
		
	function savingsAccount($acc){

		$this->view->savingsdetails = $this->model->savingsdetails($acc);
		$this->view->member_id = $this->view->savingsdetails[0]["member_id"];
		$this->view->product = $this->model->productdetails($this->view->savingsdetails[0]["product_id"]);
		$this->view->members = $this->model->getclient($this->view->member_id);
		$this->view->savings = $this->model->transactiondetails($acc);
		$this->view->render('forms/products/savingpdtapplication_details');	
		
	}

	function ApproveLoan($id){
		$this->view->loan = $this->model->loandetails($id);
		$this->view->members = $this->model->getclient($this->view->loan[0]["member_id"]);	
		$this->view->render('forms/Members/approveloan');
	}

	function Approvesavingsproduct($id){
		//$this->view->loan = $this->model->loandetails($id);
		$this->view->savingsdetails = $this->model->savingsdetails($id);
		$this->view->members = $this->model->getclient($this->view->savingsdetails[0]["member_id"]);
		$this->view->render('forms/Members/approvefixeddeposit');	
	}

	function Approvefixeddeposit($id){
		$this->view->savingsdetails = $this->model->savingsdetails($id);
		$this->view->render('forms/Members/approvefixeddeposit');
	}

	// function createSavingsProduct(){
	// 	$this->model->saveProduct();
	// }

	function createSavingsProduct() {
		try {
			$headers = getallheaders();
			$office = isset($headers['office']) ? $headers['office'] : null;
			$user_id = isset($headers['user_id']) ? $headers['user_id'] : null;
	
			if ($office === null || $user_id === null) {
				$response = array(
					'status' => 400,
					'message' => 'Required headers (office and user_id) are missing.'
				);
			} else {
				$data = json_decode(file_get_contents("php://input"), true);
	
				if (empty($data)) {
					$response = array(
						'status' => 400,
						'message' => 'Invalid JSON data provided.'
					);
				} else {
					// Add the office and created_by to the $data array
					$data['office'] = $office;
					$data['user_id'] = $user_id;
	
					$result = $this->model->saveProduct($data);
	
					if ($result) {
						$response = array(
							'status' => 200,
							'message' => 'Savings product created successfully.'
						);
					} else {
						$response = array(
							'status' => 500,
							'message' => 'Failed to create savings product.'
						);
					}
				}
			}
	
			http_response_code($response['status']);
			echo json_encode($response);
		} catch (Exception $e) {
			$response = array(
				'status' => 500,
				'message' => 'An error occurred: ' . $e->getMessage()
			);
	
			http_response_code($response['status']);
			echo json_encode($response);
		}
	}
	
	

	function approvedloan(){
		$this->model->approvedloan(); 	
	}

	function undoApprovedLoan($id){
		$this->model->undoApprovedLoan($id);
	 	
	}

	function accountApproved(){
		$this->model->accountApproved();
	}

	function modifyLoanAplication($id){
		$this->view->render('forms/Members/editnewclientloanproduct');
	}

	//loan provisioning definition
	function newLoanAgeing(){
		$this->view->render('forms/products/newloanageing');
	}

	function createNewLoanAgeing(){
		$this->model->createNewLoanAgeing(); 	
	}

	function loanProvision(){
		try {
			$office = $_SERVER['HTTP_OFFICE'];
	
			if (!empty($office)) {
	
				$loanAgeingList = $this->model->loanProvision($office);
	
				$response = array(
					'status' => 200,
					'message' => 'Loan provisioning data fetched successfully.',
					'data' => $loanAgeingList
				);
	
				echo json_encode($response);
			} else {
				$response = array(
					'status' => 400,
					'message' => 'Office value is missing in JSON input.'
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

	function updateloanageing(){
		$this->model->updateloanageing();
	}

	function editloanageing($id){
		$this->view->lonageing = $this->model->getLonAgeingDetails($id);	
		$this->view->render('forms/products/editloanageing');
	}

	function Activatesavingsproduct($id){
		$this->view->savingsdetails = $this->model->savingsdetails($id);
		$this->view->members = $this->model->getclient($this->view->savingsdetails[0]["member_id"]);
		$this->view->render('forms/Members/activatesavingsproduct');
	}

	function savingapplicationactivated($id){
			$this->model->savingapplicationactivated(); 	
	}

	function disbursetosavings($id){
		$this->view->loan = $this->model->loandetails($id);
		$this->view->members = $this->model->getclient($this->view->loan[0]["member_id"]);
		$this->view->disburs = $this->model->disburs($id);
		$this->view->m_loan_charge = $this->model->m_loan_charge($id);
		$this->view->insurance_stampduty = $this->model->insurance_stampduty($id);	 
		$this->view->savingsaccountdetails = $this->model->savingsaccountdetails($this->view->loan[0]["member_id"]);

		$this->view->render('forms/Members/disbursetosavings');	
	}

	function disburseloan($id){
		$this->view->member_id=$id;
		$this->view->paymenttype = $this->model->paymentType();
		$this->view->loan = $this->model->loandetails($id);
		$this->view->render('forms/Members/disburseloan');
	}

	function makePayment($id){
		$this->view->loan = $this->model->loandetails($id);
		$this->view->paymentdetails = $this->model->getPaymentNo($id);
		$this->view->render('forms/Members/repayment');
	}

	function fullPayment($id){
		$this->view->loan = $this->model->loandetails($id);
		$this->view->paymentdetails = $this->model->getlastPaymentNo($id);
		$this->view->render('forms/Members/fullrepayment');
	}

	function loandisbursal(){
		$this->model->loandisbursal(); 	
	}

	function DeleteSavingsProduct(){
			
	}	

	/********************  Fixed Deposit Products *******************************/
	function fixedDepositProducts(){
		try {
			$office = $_SERVER['HTTP_OFFICE'];
			if (!empty($office)) {
	
				$fixedDepositProducts = $this->model->fixedDepositProducts($office);
	
				$response = array(
					'status' => 200,
					'message' => 'Fixed deposit products fetched successfully.',
					'data' => $fixedDepositProducts
				);
	
				echo json_encode($response);
			} else {
				$response = array(
					'status' => 400,
					'message' => 'Office value is missing in headers.'
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

	function newfixedDepositProducts() {
		try {
			$headers = getallheaders();
			$office = isset($headers['office']) ? $headers['office'] : null;
	
			if ($office === null) {
				$response = array(
					'status' => 400,
					'message' => 'Required header (office) is missing.'
				);
			} else {
				// Fetch required data as needed
				$assets = $this->model->getAssets();
				$liability = $this->model->getLiability();
				$equity = $this->model->getEquity();
				$income = $this->model->getIncome();
				$expenses = $this->model->getExpenses();
				$id = 4;
				$charges = $this->model->getCharges($id, $office);
				$currency = $this->model->currency();
	
				// Create a response with the fetched data
				$response = array(
					'status' => 200,
					'message' => 'Data retrieved successfully.',
					'data' => array(
						'assets' => $assets,
						'liability' => $liability,
						'equity' => $equity,
						'income' => $income,
						'expenses' => $expenses,
						'charges' => $charges,
						'currency' => $currency
					)
				);
			}
	
			http_response_code($response['status']);
			echo json_encode($response);
		} catch (Exception $e) {
			$response = array(
				'status' => 500,
				'message' => 'An error occurred: ' . $e->getMessage()
			);
	
			http_response_code($response['status']);
			echo json_encode($response);
		}
	}	

	function createfixedDepositProducts(){
		$this->model->createfixedDepositProducts();	
	}

	function UpdatefixedDepositProducts(){
		$data=$_POST;
		$this->model->UpdatefixedDepositProducts($data);	
	}

	function DeletefixedDepositProducts(){
		
	}

	function editFixedDepositProduct($id){
		$this->view->assets=$this->model->getAssets();
		$this->view->liability=$this->model->getLiability();
		$this->view->equity=$this->model->getEquity();
		$this->view->income=$this->model->getIncome();
		$this->view->expenses=$this->model->getExpenses();
		$this->view->getcharges=$this->model->getCharges(4);
		$this->view->currency  = $this->model->currency();
		$this->view->fixedDeposit = $this->model->fixedDepositProductsdetails($id);
		$this->view->render('forms/products/editfixeddepositproduct');
	}

	function viewFixedDepositProduct($id){
		$this->view->hastransactions = $this->model->hastransacted();
		$product_type=4;
		$this->view->product=$id;	
		$this->view->created= $this->model->getPointers($id,$product_type);	
		$this->view->fixedDeposit = $this->model->fixedDepositProductsdetails($id);
		$this->view->render('forms/products/viewfixeddepositproduct');
	}

	/********************Charges Products *******************************/
	function chargeProducts(){
		try {
			$office = $_SERVER['HTTP_OFFICE'];	
			if (!empty($office)) {
	
				$chargeProducts = $this->model->chargeproductList($office);
	
				$response = array(
					'status' => 200,
					'message' => 'Charge products fetched successfully.',
					'data' => $chargeProducts
				);
	
				echo json_encode($response);
			} else {
				$response = array(
					'status' => 400,
					'message' => 'Office value is missing in JSON input.'
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

	function newChargesProduct(){
		$this->view->currency  = $this->model->currency();
		$this->view->products = $this->model->getProducts();
		$this->view->glaccounts = $this->model->getGlaccounts();
		$this->view->render('forms/products/newchargeproduct');
	}

	function getchargeapplicity($id){
		$this->view->apply  = $this->model->getchargeapplicity($id);
	}


	function createcharge(){
		$this->model->createcharge();
	}

	function editChargeProduct($id){
		$this->view->charge = $this->model->getChargesDetails($id);
		$this->view->products = $this->model->getProducts();
		$this->view->render('forms/products/editchargesproduct');	
	}

	// function viewCharge($product_type, $product_id){
	// 	$this->view->hastransactions = $this->model->hastransacted();
	// 	$this->view->charge = $this->model->getChargesDetails($product_id);
	//  	$this->view->created= $this->model->getPointers($product_id,$product_type);
	// 	$this->view->render('forms/products/viewcharge');
	// }
	function viewCharge($product_type, $product_id){
		try {
			$jsonData = file_get_contents('php://input');
			$data = json_decode($jsonData, true);
	
			if (isset($data['office'])) {
				$office = $data['office'];
	
				// Fetch charge details using $product_type, $product_id, and $office
				$hastransactions = $this->model->hastransacted($office);
				$id = $product_id;
				$charge = $this->model->getChargesDetails($id, $office);
				$created = $this->model->getPointers($id, $office, $product_type);
	
				$response = array(
					'status' => 200,
					'message' => 'Charge details fetched successfully.',
					'data' => array(
						'hastransactions' => $hastransactions,
						'charge' => $charge,
						'created' => $created
					)
				);
	
				echo json_encode($response);
			} else {
				$response = array(
					'status' => 400,
					'message' => 'Office value is missing in JSON input.'
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

	function editglpointerscharge($product_id, $pointer_id){

		$this->view->hastransactions = $this->model->hastransacted();

		$product_type=6;
		$this->view->product=$product_id;	
	 	$this->view->transaction_types = $this->model->getTransactionTypes($product_type);
	 	$this->view->payment_modes= $this->model->getPaymentModes();
	 	$this->view->created= $this->model->getPointers($product_id,$product_type);
	 	$this->view->glaccounts = $this->model->getGlaccounts();

		$types = $this->model->getTransactionTypes($product_type);
		$created_pointers = $this->model->getPointers($product_id,$product_type);

		$joint1 = array();
		foreach ($types as $key => $value) {
			$joint1[$key] = $value['transaction_type_name'];
		}

		$joint2 = array();
		foreach ($created_pointers as $key => $value) {
			$joint2[$key] = $value['transaction_type'];
		}

		$this->view->missing = array_diff($joint1, $joint2);
	 	$this->view->id = $product_id;	
		$this->view->pointer_details = $this->model->getPointerDetails($pointer_id, $product_id, $product_type);

		$this->view->render('forms/products/editglpointerscharge');
	}

	function updateglcharge($id){
		$this->model->UpdateGlAccountCharge($_POST, $id);
	}

	function getshareProduct($id){
		try {
			$office = $_SERVER['HTTP_OFFICE'];
	
			if (!empty($office)) {
	
				$hastransactions = $this->model->hastransacted($office);
				$product_type = 1;
				$created = $this->model->getPointers($id, $office, $product_type);
				$product = $this->model->getshareProduct($id);
	
				$response = array(
					'status' => 200,
					'message' => 'Share product details fetched successfully.',
					'data' => array(
						'hastransactions' => $hastransactions,
						'created' => $created,
						'product' => $product
					)
				);
	
				echo json_encode($response);
			} else {
				$response = array(
					'status' => 400,
					'message' => 'Office value is missing in JSON input.'
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


	function getinsuranceproduct($id){
		try {
			$office = $_SERVER['HTTP_OFFICE'];
	
			if (!empty($office)) {
	
				$hastransactions = $this->model->hastransacted($office);
				$product_type = 8;
				$created = $this->model->getPointers($id, $office, $product_type);
				$product = $this->model->getInsuranceProduct($id);
	
				$response = array(
					'status' => 200,
					'message' => 'Insurance product details fetched successfully.',
					'data' => array(
						'hastransactions' => $hastransactions,
						'created' => $created,
						'product' => $product
					)
				);
	
				echo json_encode($response);
			} else {
				$response = array(
					'status' => 400,
					'message' => 'Office value is missing in JSON input.'
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

	function UpdateChargeProduct(){
		$data=$_POST;
		$this->model->UpdateChargeProduct($data);	
	}

	function DeleteChargeProduct($id){
		$this->model->DeleteChargeProduct($id);	
	}

	function amortization_Calculation(){
		$this->model->amortization_Calculation();
	}

	/* Shares  */
	function shares(){
		try {
			$office = $_SERVER['HTTP_OFFICE'];	
			if (!empty($office)) {
	
				$sharesList = $this->model->SharesList($office);
	
				$response = array(
					'status' => 200,
					'message' => 'Shares list fetched successfully.',
					'data' => $sharesList
				);
	
				echo json_encode($response);
			} else {
				$response = array(
					'status' => 400,
					'message' => 'Office value is missing in Headers.'
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
		
	function newshareproduct(){
		$this->view->assets=$this->model->getAssets();
		$this->view->liability=$this->model->getLiability();
		$this->view->equity=$this->model->getEquity();
		$this->view->income=$this->model->getIncome();
		$this->view->render('forms/products/addshares');
	}

	function newinsuranceproduct(){
		$this->view->categories = $this->model->getInsuranceCategories();
		$this->view->render('forms/products/addinsurance');

	}

	function insurancecategories(){
		$this->view->insurance = $this->model->getInsuranceCategories();
		$this->view->render('forms/products/insurancecategories');
	}

	function newinsurancecategory(){
		$this->view->render('forms/products/newinsurancecategory');
	}
	
	function editinsurancecategory($id){
		$this->view->insurance = $this->model->getInsuranceCategory($id);
		$this->view->render('forms/products/editinsurancecategory');
	}
	
	function updateinsurancecategory($id){
		$this->model->updateInsuranceCategory($id);
	}
	
	function deleteinsurancecategory($id){
		$this->model->deleteInsuranceCategory($id);
	}


	function createShare(){
		$user = $_SERVER['HTTP_USER'];
		$office = $_SERVER['HTTP_OFFICE'];
	
		$jsonData = file_get_contents("php://input");
		$data = json_decode($jsonData, true);
	
		if ($data && isset($user) && isset($office)) {
			$data['user'] = $user;
			$data['office'] = $office;
			$this->model->saveShares($data);
		} else {
			$response = array(
				'status' => 400,
				'message' => 'Invalid or missing JSON input.'
			);
	
			http_response_code(400);
			echo json_encode($response);
		}
	}		

	function createinsurance(){
		$this->model->saveInsurance($_POST);
	}

	function createinsurancecategory(){
		$this->model->saveInsuranceCategory($_POST);
	}

	/* ----ThirdParty products  ******************/
	function thirdpartyproducts(){
		try {
			$jsonData = file_get_contents('php://input');
			$data = json_decode($jsonData, true);
	
			if (isset($data['access_level'])) {
				$accessLevel = $data['access_level'];
	
				if ($accessLevel == 'SA') {
					$thirdPartyProducts = $this->model->getThirdPartySAProducts();
				} else {
					$thirdPartyProducts = $this->model->getThirdPartyProducts();
				}
	
				$response = array(
					'status' => 200,
					'message' => 'Third-party products fetched successfully.',
					'data' => $thirdPartyProducts
				);
	
				echo json_encode($response);
			} else {
				$response = array(
					'status' => 400,
					'message' => 'Access level value is missing in JSON input.'
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

	function thirdpartyProduct($id){
		try {
			$office = $_SERVER['HTTP_OFFICE'];
	
			if (!empty($office)) {
	
				$hastransactions = $this->model->hastransacted($office);
				$product_type = 7;
				$created = $this->model->getPointers($id, $product_type, $office);
				$thirdparty = $this->model->getthirdpartyproduct($id);
				$collateral = $this->model->getcollateral($id);
				$charges = $this->model->getloanProductcharges($id);
				$curname = $this->model->currency();
	
				$response = array(
					'status' => 200,
					'message' => 'Third-party product details fetched successfully.',
					'data' => array(
						'hastransactions' => $hastransactions,
						'created' => $created,
						'thirdparty' => $thirdparty,
						'collateral' => $collateral,
						'charges' => $charges,
						'curname' => $curname
					)
				);
	
				echo json_encode($response);
			} else {
				$response = array(
					'status' => 400,
					'message' => 'Office value is missing in  Headers.'
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

	function thirdpartyTransactions($id){
		try {
			$office = $_SERVER['HTTP_OFFICE'];
	
			if (!empty($office)) {
	
				$transactions = $this->model->getThirdpartyTransactions($id, $office);
	
				$response = array(
					'status' => 200,
					'message' => 'Third-party transactions fetched successfully.',
					'data' => array(
						'transactions' => $transactions
					)
				);
	
				echo json_encode($response);
			} else {
				$response = array(
					'status' => 400,
					'message' => 'Office value is missing in JSON input.'
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

	function NewthirdPartyProduct(){
		
		$this->view->currency  = $this->model->currency();
		$this->view->office = $this->model->officeList();
		$this->view->assets=$this->model->getAssets();
		$this->view->liability=$this->model->getLiability();
		$this->view->equity=$this->model->getEquity();
		$this->view->income=$this->model->getIncome();
		$this->view->expenses=$this->model->getExpenses();
		$this->view->getcharges=$this->model->getCharges(2);
		$this->view->render('forms/products/newthirdpartyproduct');
	}

	function createNewthirdPartyProduct(){
		$this->model->createNewthirdPartyProduct();
	}

	function editthirdPartyProduct($id){
		$this->view->currency = $this->model->currency();
		$this->view->assets=$this->model->getAssets();
		$this->view->liability=$this->model->getLiability();
		$this->view->equity=$this->model->getEquity();
		$this->view->income=$this->model->getIncome();
		$this->view->expenses=$this->model->getExpenses();
		$this->view->getcharges=$this->model->getCharges(2);
		$this->view->loanproduct = $this->model->getthirdpartyproduct($id);
		$this->view->check_if_used = $this->model->checkForLoan($id);

		$this->view->render('forms/products/editenewloanproduct');

	}

	function UpdatethirdPartyProduct(){	
		$data=$_POST;
		$this->model->UpdatethirdPartyProduct($data);		   
	}
		   
	function DeletethirdPartyProduct($id){	
		$this->model->DeletethirdPartyProduct($id);		   
	}

	function addthirdpartyglpointer($id){

		$this->view->hastransactions = $this->model->hastransacted();

		$product_type=7;
		$details = $this->model->getPartyProductdetails($id);
		$subtype = $details[0]['product_type'];
		
		$this->view->product=$id;	
	 	$this->view->transaction_types = $this->model->getMissingThirdPartyTransactionTypes($id, $product_type, $subtype);
	 	$this->view->created= $this->model->getPointers($id,$product_type);
	 	$this->view->payment_modes= $this->model->getPaymentModes();
	 	$this->view->glaccounts = $this->model->getGlaccounts();

		$types = $this->model->getThirdPartyTransactionTypes($product_type, $subtype);
		$created_pointers = $this->model->getPointers($id,$product_type);

		$joint1 = array();
		foreach ($types as $key => $value) {
			$joint1[$key] = $value['transaction_type_name'];
		}

		$joint2 = array();
		if (!empty($created_pointers)) {
			foreach ($created_pointers as $key => $value) {
				$joint2[$key] = $value['transaction_type'];
			}
		}

		$missing = array_diff($joint1, $joint2);
		$this->view->missing = $missing;
		$this->view->product_type = $product_type;
		$imported_accounts = $this->model->checkIfSaccoImportedAccounts($id);
		$this->view->imported_accounts = $imported_accounts;

		if ($imported_accounts) {
			$this->view->missing_pointers = $this->model->getMissingPointers($missing);
		}
		$this->view->render('forms/products/thirdparty_pointers_form');

	}

	function editglpointersthird($product_id, $pointer_id){

		$this->view->hastransactions = $this->model->hastransacted();

		$product_type=7;
		$this->view->product=$product_id;	
	 	$this->view->transaction_types = $this->model->getTransactionTypes($product_type);
	 	$this->view->payment_modes= $this->model->getPaymentModes();
	 	$this->view->created= $this->model->getPointers($product_id,$product_type);
	 	$this->view->glaccounts = $this->model->getGlaccounts();

		$types = $this->model->getTransactionTypes($product_type);
		$created_pointers = $this->model->getPointers($product_id,$product_type);

		$joint1 = array();
		foreach ($types as $key => $value) {
			$joint1[$key] = $value['transaction_type_name'];
		}

		$joint2 = array();
		if (!empty($created_pointers)) {
			foreach ($created_pointers as $key => $value) {
				$joint2[$key] = $value['transaction_type'];
			}
		}

		$this->view->missing = array_diff($joint1, $joint2);
	 	$this->view->id = $product_id;	
		$this->view->pointer_details = $this->model->getPointerDetails($pointer_id, $product_id, $product_type);

		$this->view->render('forms/products/editglpointersthird');
	}

	function updateglthird($id){
		$this->model->UpdateGlAccountThird($_POST, $id);
	}

	function createglthirdparty(){	
		$this->model->createglthirdparty();
	}	

	function thirdpartydeposit(){
		$this->view->render('forms/products/deposit');
	}

	function getproductname($id){
		if(!empty($id)){		
			$this->model->getAccountName($id); 
		}else{
			
		}
	}

	function partydeposit(){
		$data = $_POST;
		if(!empty($data)){ 
			$this->model->thirdpartydeposit($data);	
		}
	}

}