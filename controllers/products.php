<?php

class Products extends Controller{

	public function __construct(){
		parent::__construct();
		Auth::handleSignin();
		Auth::CheckSession();
		Auth::CheckAuthorization();
		$_SESSION['timeout'] = time(); 
	}

	/********************  products menu  *******************************/

	function insurance(){
		$this->view->insurance  = $this->model->InsuranceList();	
		$this->view->render('forms/products/insurancelist');
	}

	function index(){
		$this->view->shares = $this->model->getShareProducts();
		$this->view->loans = $this->model->getLoanProducts();
		$this->view->savings = $this->model->getSavingsProducts();
		$this->view->fixed = $this->model->getFixedProducts();
		$this->view->charges = $this->model->getChargeProducts();
		$this->view->defaults = $this->model->getDefaultsCount();
		$this->view->provisioning = $this->model->getProvisioningProducts();
		$this->view->thirdparty = $this->model->getThirdPartyProductCount();
		$this->view->insurance = $this->model->getInsuranceProductsCount();
		$this->view->render('forms/products/products');
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
		$defaultsCount = $this->model->getDefaultsCount();
		$this->view->defaults = $defaultsCount;
	 	$this->view->charges = $this->model->getAllCharges();
		$this->view->savings = $this->model->getAllSavings();
		$this->view->shares = $this->model->getAllShares();
		
		if ($defaultsCount > 0) {
			$this->view->groups = $this->model->getDefaultProducts(-1);
			$this->view->charges = $this->model->getDefaultProducts(6);
			$this->view->savings = $this->model->getDefaultProducts(3);
			$this->view->shares = $this->model->getDefaultProducts(1);
			$this->view->wallets = $this->model->getDefaultWalletDetails(5);
			$this->view->render('forms/products/viewdefaultproducts');
		} else {
			$this->view->render('forms/products/defaultproducts');
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

	function addSavingsGlpointers($id){

		$this->view->hastransactions = $this->model->hastransacted();

		$product_type=3;
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
		$this->view->render('forms/products/savings_pointers_form');

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
	$this->model->createglequity();
		
	}

	function createglinsurance(){
		$this->model->createglinsurance();
	}

	function createglsaving(){	
		$this->model->createglsaving();
		
	}

	function createglother(){	
		$this->model->createglother();
		
	}

	function createglwallet(){	
		$this->model->createglwallet();
	}

	/* ----Loan products  ******************/
	function loanproducts(){
		$this->view->loanproductList = $this->model->loanproductList();
		$this->view->render('forms/products/loanproducts');
	}

	function newquickloanproduct(){
		$this->view->loanproductList = $this->model->loanproductList();
		$this->view->render('forms/products/newquickloanproduct');
	}

	function viewLoanProducts($id){
		$this->view->hastransactions = $this->model->hastransacted();
		$product_type=2;
		$this->view->created= $this->model->getPointers($id,$product_type);		
		$this->view->loanproduct = $this->model->getloanproduct($id);
		$this->view->collateral = $this->model->getcollateral($id);
		$this->view->charges = $this->model->getloanProductcharges($id);
		$this->view->curname = $this->model->currency();
		
		$this->view->render('forms/products/viewloanproduct');

	}

	function newloanProduct(){		
		$this->view->currency  = $this->model->currency();
		$this->view->office = $this->model->officeList();
		$this->view->assets=$this->model->getAssets();
		$this->view->liability=$this->model->getLiability();
		$this->view->equity=$this->model->getEquity();
		$this->view->income=$this->model->getIncome();
		$this->view->expenses=$this->model->getExpenses();
		$this->view->getcharges=$this->model->getCharges(2);
		$this->view->render('forms/products/newloanproduct');
	}

	function createnewloanproduct(){	
		$data = $_POST;	
		$rs = $this->model->createnewloanproduct($data);		
		echo json_encode($rs);
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
		$this->view->savings = $this->model->savingsProductsList();
		$this->view->render('forms/products/savingsproducts');
	}

	function productdetails($id){
		$this->view->hastransactions = $this->model->hastransacted();
		$product_type=3;
	 $this->view->created= $this->model->getPointers($id,$product_type);	
		$this->view->savings = $this->model->productdetails($id);
		$this->view->render('forms/products/savingspdt_details');

	}

	function newSavingsProduct(){
		$this->view->currency = $this->model->currency();
		$this->view->assets=$this->model->getAssets();
		$this->view->liability=$this->model->getLiability();
		$this->view->equity=$this->model->getEquity();
		$this->view->income=$this->model->getIncome();
		$this->view->expenses=$this->model->getExpenses();
		$this->view->getcharges=$this->model->getCharges(3);
		$this->view->render('forms/products/newsavingsproduct');

	}

	function EditSavingProduct($id){
		$this->view->currency  = $this->model->currency();
		$this->view->assets=$this->model->getAssets();
		$this->view->liability=$this->model->getLiability();
		$this->view->equity=$this->model->getEquity();
		$this->view->income=$this->model->getIncome();
		$this->view->expenses=$this->model->getExpenses();
		$this->view->currency  = $this->model->currency();
		$this->view->getcharges=$this->model->getCharges(3);
		$this->view->savings = $this->model->productdetails($id);
		$this->view->render('forms/products/editsavingproduct');

	}

	function UpdateSavingProduct(){
		$data=$_POST;
		$this->model->UpdateSavingProduct($data);
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

	function createSavingsProduct(){
		$this->model->saveProduct();
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
		$this->view->lonageing = $this->model->loanProvision();
		$this->view->render('forms/products/loan_ageing');
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
		$this->view->fixed = $this->model->fixedDepositProducts();
		$this->view->render('forms/products/fixeddepositproducts');
	}

	function newfixedDepositProducts(){
		$this->view->assets=$this->model->getAssets();
		$this->view->liability=$this->model->getLiability();
		$this->view->equity=$this->model->getEquity();
		$this->view->income=$this->model->getIncome();
		$this->view->expenses=$this->model->getExpenses();
		$this->view->getcharges=$this->model->getCharges(4);
		$this->view->currency  = $this->model->currency();
		$this->view->render('forms/products/newfixeddepositproduct');
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
		$this->view->charge = $this->model->chargeproductList();
		$this->view->product_type = 6;
		$this->view->render('forms/products/chargeproducts');
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

	function viewCharge($product_type, $product_id){
		$this->view->hastransactions = $this->model->hastransacted();
		$this->view->charge = $this->model->getChargesDetails($product_id);
	 	$this->view->created= $this->model->getPointers($product_id,$product_type);
		$this->view->render('forms/products/viewcharge');
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
		$this->view->hastransactions = $this->model->hastransacted();
		$product_type=1;
		$this->view->created= $this->model->getPointers($id,$product_type);	
		$this->view->product = $this->model->getshareProduct($id);
		$this->view->render('forms/products/viewshareproduct');
	}

	function getinsuranceproduct($id){
		$this->view->hastransactions = $this->model->hastransacted();
		$product_type=8;
		$this->view->created= $this->model->getPointers($id,$product_type);	
		$this->view->product = $this->model->getInsuranceProduct($id);
		$this->view->render('forms/products/viewinsuranceproduct');

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
		$this->view->shares  = $this->model->SharesList();	
		$this->view->render('forms/products/sharesconfig');
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
		$this->model->saveShares();
	}

	function createinsurance(){
		$this->model->saveInsurance($_POST);
	}

	function createinsurancecategory(){
		$this->model->saveInsuranceCategory($_POST);
	}

	/* ----ThirdParty products  ******************/
	function thirdpartyproducts(){
		if ($_SESSION['access_level'] == 'SA') {
	 		$this->view->thirdpartyProductlist = $this->model->getThirdPartySAProducts();
		} else {
	 		$this->view->thirdpartyProductlist = $this->model->getThirdPartyProducts();
		}
		$this->view->clic = array();//$this->model->getClicThirdPartyProducts();
		$this->view->render('forms/products/thirdpartyproducts');
	}

	function thirdpartyProduct($id){

		$this->view->hastransactions = $this->model->hastransacted();
		$product_type=7;
		$this->view->created= $this->model->getPointers($id,$product_type);		
		$this->view->thirdparty = $this->model->getthirdpartyproduct($id);
		$this->view->collateral = $this->model->getcollateral($id);
		$this->view->charges = $this->model->getloanProductcharges($id);
		$this->view->curname = $this->model->currency();
		$this->view->render('forms/products/viewthirdpartyproduct');

	}

	function thirdpartyTransactions($id){
		$this->view->transactions = $this->model->getThirdpartyTransactions($id);
		$this->view->render('forms/products/thirdpartytransactions');

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