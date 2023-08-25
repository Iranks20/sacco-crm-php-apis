<?php

class Products_model extends Model {

  public function __construct() {
    parent::__construct();
      //Auth::handleSignin();
      $this->logUserActivity(NULL);
      if (!$this->checkTransactionStatus()) {
        header('Location: ' . URL); 
      }
  }

  function checkIfSaccoImportedAccounts(){

    $office = $_SESSION['office'];
    $template_accounts = $this->db->SelectData("SELECT name FROM acc_ledger_account_template");
    $sacco_accounts = $this->db->SelectData("SELECT name FROM acc_ledger_account WHERE sacco_id='".$office."'");
    
    if (count($template_accounts) >= count($sacco_accounts)) {

        $template = array();
        foreach ($template_accounts as $key => $value) {
          array_push($template, $value);
        }

        $sacco_S = array();
        foreach ($template_accounts as $key => $value) {
          array_push($sacco_S, $value);
        }

        $count = 0;
        foreach ($sacco_S as $key => $value) {
          if (in_array($value, $template)) {
            $count += 1;
          }
        }

        if ($count >= count($sacco_S)) {
          return TRUE;
        } else {
          return FALSE;
        }
    } else {
      return FALSE;
    }
  }

  function addMissingTransTypesPointers($transaction_type, $product_id, $type){

    $pointer_names = $this->getTransTypeMissingPointers(trim($transaction_type));

    $debit_account = $this->getSaccoAccountId($pointer_names['debit']);
    $credit_account = $this->getSaccoAccountId($pointer_names['credit']);

    if ((!is_null($debit_account)) && (!is_null($credit_account)))  {

      $pointerData = array(
       'sacco_id' =>$_SESSION['office'],
       'pointer_name' => $pointer_names['transaction_type_name'],
       'description' => $pointer_names['transaction_type_name'],
       'product_id' => $product_id,
       'transaction_type_id' => $pointer_names['transaction_type_id'],
       'debit_account' => $debit_account,
       'credit_account' =>$credit_account
      );
     
      $this->db->InsertData('acc_gl_pointers', $pointerData);

      $table = "";
      if ($type == 1) {
        $table = 'share_products';
      } else if ($type == 2) {
        $table = 'm_product_loan';
      } else if ($type == 3) {
        $table = 'm_savings_product';
      } else if ($type == 4) {
        $table = 'fixed_deposit_product';
      } else if ($type == 8) {
        $table = 'insurance_products';
      } else if ($type == 6) {
        $table = 'm_charge';
      } else if ($type == 7) {
        $table = 'thirdparty_products';
      }
      if ($table != '') {
        $product_details = $this->getProductDetails($product_id, $table);
        if ($product_details['product_status'] != 'Active' || $product_details['status'] != 'Active') {
          if ($table == "insurance_products" || $table == "m_charge") {            
            $productData = array(
              'status' => "Active"
            );
          } elseif ($table == "m_product_loan") {
            $productData = array(
              'status' => "open"
            );
          } else {
            $productData = array(
              'product_status' => "Active"
            );
          }
          $this->db->UpdateData($table, $productData, "`id` = '{$product_id}'");
        }
      }
    }
  }

  function getProductDetails($id, $table){
    $details = $this->db->SelectData("SELECT * FROM $table WHERE id='".$id."'");
    return $details[0];

  }

  function getSaccoAccountId($account_name){

    $office = $_SESSION['office'];
    $results = $this->db->SelectData("SELECT id FROM acc_ledger_account WHERE name='".$account_name."' AND sacco_id = '" . $office . "'");
    if (empty($results)) {
      return NULL;
    } else {
      return $results[0]['id'];
    }
  }

  function getTransTypeMissingPointers($t_type){
    $accounts = $this->db->SelectData("SELECT * FROM transaction_type WHERE transaction_type_name='".$t_type."'");
    return $accounts[0];
  }

  function getMissingPointers($tran_types){

    $pointers = array();
    foreach ($tran_types as $key => $value) {
      $accs = $this->db->SelectData("SELECT * FROM transaction_type WHERE transaction_type_name='".$value."'");
      $pointers[$value]['debit'] = $accs[0]['debit'];
      $pointers[$value]['credit'] = $accs[0]['credit'];
    }

    return $pointers;
  }

  function updateExemptedMembers($id){

    foreach ($_POST['charges'] as $key => $value) {
      $this->updateMemberExemption($id, $value);
    }

    header('Location: ' . URL . 'products/chargeexemption/' . $id . '?msg=success');
  }

  function updateMemberExemption($id, $member_id){

    $selected_charges = $this->getMemberDetails($member_id);

    str_replace(" ,", "", $selected_charges['charge_exemptions']);
    str_replace(" ", "", $selected_charges['charge_exemptions']);

    $exemptions = explode(",", $selected_charges['charge_exemptions']);      
    array_push($exemptions, $id);
    sort($exemptions);
    $new_exemptions = array_unique($exemptions);

    $exp = "";
    foreach ($new_exemptions as $key => $value) {
      $exp .= $value . ",";
    }
    $exp .= " ";

    $exp1 = str_replace(", ", "", $exp);
    $exp2 = str_replace(" ,", "", $exp1);
    $new_exp = str_replace(" ", "", $exp2);

    $postData = array(
      'charge_exemptions' => $new_exp
    );

    $this->db->UpdateData('members', $postData, "`c_id` = '{$member_id}'");
  }

  function getProductExceptions($id){

    $selected_charges = $this->getSelectedSaccoCharges();

    $members = array();
    foreach ($selected_charges as $key => $value) {
      $exp = explode(",", $value['charge_exemptions']);
      if (in_array($id, $exp)) {
        array_push($members, $value['c_id']);
      }
    }

    return $members;
  }

  function getSelectedSaccoCharges(){

    $office = $_SESSION['office'];
    $query = $this->db->SelectData("SELECT * FROM members WHERE office_id='".$office."' AND charge_exemptions != ''");
    return $query;

  }

  function getAllMembers(){

    $office=$_SESSION['office'];
    $query= $this->db->SelectData("SELECT * FROM members WHERE office_id='".$office."'");

    return $query;
  }

  function getMemberDetails($id){

    $office=$_SESSION['office'];
    $query= $this->db->SelectData("SELECT * FROM members WHERE office_id='".$office."' AND c_id = '$id'");

    return $query[0];

  }

  function resetChargeExemption($id){
    
      $postData = array(
        'charge_exemptions' => ''
      );

      $this->db->UpdateData('members', $postData, "`c_id` = '{$id}'");
      header('Location: ' . URL . 'products/chargeexemption?msg=reset');
  }

  function getSelectedCharges($id){

    $office=$_SESSION['office'];
    $query= $this->db->SelectData("SELECT charge_exemptions FROM members WHERE office_id='".$office."' AND c_id = '$id'");

    foreach ($query as $key => $value) {
      $exp = explode(",", $value['charge_exemptions']);
    }
    return $exp;

  }

  function editChargeExemption($id){

    $exp = "";
    foreach ($_POST['charges'] as $key => $value) {
      $exp .= $value . ",";
    }
    $exp .= " ";

    $exp1 = str_replace(", ", "", $exp);
    $exp2 = str_replace(" ,", "", $exp1);
    $new_exp = str_replace(" ", "", $exp2);

    $postData = array(
      'charge_exemptions' => $new_exp
    );

    $this->db->UpdateData('members', $postData, "`c_id` = '{$id}'");
    header('Location: ' . URL . 'products/chargeexemption?msg=success');
  }

  function getThirdpartyTransactions($id){

    $office = $_SESSION['office'];
    $results =  $this->db->SelectData("SELECT * FROM thirdparty_account_transactions AS a JOIN thirdparty_products AS b ON a.thirdparty_account_no = b.thirdparty_accountno WHERE b.id = '$id' AND b.product_status ='Active' AND b.office_id = '".$office."'");

    return $results;
  }

  function getShareProducts($office){
    $results =  $this->db->SelectData("SELECT count(id) AS idz FROM share_products WHERE product_status ='Active' AND office_id = '".$office."'");
    return $results[0]['idz'];
  }

  function getLoanProducts($office){
    $results =  $this->db->SelectData("SELECT count(id) AS idz FROM m_product_loan WHERE status ='open' AND office_id = '".$office."'");

    return $results[0]['idz'];
  }

  function getSavingsProducts($office){
    $results =  $this->db->SelectData("SELECT count(id) AS idz FROM m_savings_product WHERE product_status ='Active' AND office_id = '".$office."'");

    return $results[0]['idz'];
  }

  function getSavingsProductDetails($id){
    $results =  $this->db->SelectData("SELECT * FROM m_savings_product WHERE id='$id'");
    echo json_encode($results[0]);
    die();
  }

  function getSharesProductDetails($id){
    $results =  $this->db->SelectData("SELECT * FROM share_products WHERE id='$id'");
    echo json_encode($results[0]);
    die();
  }

  function getFixedProducts($office){
    $results =  $this->db->SelectData("SELECT count(id) AS idz FROM fixed_deposit_product WHERE product_status ='Active' AND office_id = '".$office."'");

    return $results[0]['idz'];
  }

  function getChargeProducts($office){
    $results =  $this->db->SelectData("SELECT count(id) AS idz FROM m_charge WHERE status ='Active' AND office_id = '".$office."'");

    return $results[0]['idz'];
  }

  function getAllCharges($office){
    $results =  $this->db->SelectData("SELECT * FROM m_charge WHERE status ='Active' AND is_active = 1 AND is_deleted = 0  AND office_id = '".$office."' AND transaction_type_id = 29 OR transaction_type_id = 39");
    return $results;
  }

  function getAllSaccoCharges(){
    $office = $_SESSION['office'];
    $results =  $this->db->SelectData("SELECT * FROM m_charge WHERE status ='Active' AND is_active = 1 AND is_deleted = 0  AND office_id = '".$office."'");
    return $results;
  }

  function getAllSavings($office){
    $results =  $this->db->SelectData("SELECT * FROM m_savings_product WHERE product_status ='Active' AND office_id = '".$office."'");
    return $results;
  }

  function getAllShares($office){
    $results =  $this->db->SelectData("SELECT * FROM share_products WHERE product_status ='Active' AND office_id = '".$office."'");
    return $results;
  }

  function createdefaults($data){
    
    $this->adddefaults($data);

    header('Location: ' . URL . 'products/defaultproducts?msg=success');
  }

  function updateDefaultProducts($data){

    $office = $_SESSION['office'];

    /*$defaults =  $this->db->SelectData("SELECT * FROM m_reg_settings WHERE status ='Active' AND sacco_id = '".$office."'");

    foreach ($defaults as $key => $value) {
    
      $postData = array(
        'status' => 'Closed'
      );

      $id = $value['id'];

      $this->db->UpdateData('m_reg_settings', $postData, "`id` = '{$id}'");

    }*/

    $sth = $this->db->prepare("DELETE FROM `m_reg_settings` WHERE `sacco_id` = '$office'");
    $success = $sth->execute();

    $this->adddefaults($data);

    header('Location: ' . URL . 'products/defaultproducts?msg=updated');
  }

  function adddefaults($data){

    $charge = 6;
    $wallet = 5;
    $shares = 1;
    $savings = 3;
    $group = -1;

    if ($data['group_savings_product'] != "" ) {
      $this->insertdefaults($group, $data['group_savings_product'], str_replace(",","",$data['group_savings_amount']));
    }
    
    foreach ($data['charges'] as $key => $value) {
      if ($value != "") {
        $this->insertdefaults($charge, $value, str_replace(",","",$data['charge_amounts'][$key]));
      }
    }

    if ($data['shares'] != "") {
      if ($data['share_product'] == "" && $data['shares_amount'] == "") {
        $this->insertdefaults($shares, 0, 0);
      } else if ($data['share_product'] != "" && $data['shares_amount'] != "") {
        $this->insertdefaults($shares, $data['share_product'], str_replace(",","",$data['shares_amount']));
      }
    }

    if ($data['savings'] != "") {
      if ($data['savings_product'] == "" && $data['savings_amount'] == "") {
        $this->insertdefaults($savings, 0, 0);
      } else if ($data['savings_product'] != "" && $data['savings_amount'] != "") {
        $this->insertdefaults($savings, $data['savings_product'], str_replace(",","",$data['savings_amount']));
      }

      if (isset($data['wallets'])) {
        $this->insertdefaults($wallet, 0, 0);
      }
    }

  }

  function insertdefaults($product_type, $product_id, $amount){
    $postData = array(
     'sacco_id' =>$_SESSION['office'],
     'product_type' => $product_type,
     'p_id' => $product_id,
     'amount' => $amount,
     'created_by' => $_SESSION['user_id']
    );

    $result = $this->db->InsertData('m_reg_settings', $postData);
  }

  function getDefaultsCount($office){

    $results =  $this->db->SelectData("SELECT count(id) AS idz FROM m_reg_settings WHERE sacco_id = '".$office."'");

    if ($results[0]['idz'] > 0) {
      return $results[0]['idz'];
    } else {
      return 0;
    }
  }

  function getProvisioningProducts($office){
    $results =  $this->db->SelectData("SELECT count(id) AS idz FROM m_loan_ageing WHERE office_id = '".$office."'");

    return $results[0]['idz'];
  }

  function getThirdPartyProductCount($office){
    $results =  $this->db->SelectData("SELECT count(id) AS idz FROM thirdparty_products WHERE product_status ='Active' AND office_id = '".$office."'");

    return $results[0]['idz'];
  }

  function getInsuranceProductsCount($office){
    $results =  $this->db->SelectData("SELECT count(id) AS idz FROM insurance_products WHERE product_status ='Active' AND office_id = '".$office."'");

    return $results[0]['idz'];

  }

  function SendThidpartyRequest($instance){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, THRIDPARTY_PRODUCTS_API);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS,"instance=".$instance);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $server_output = curl_exec($ch);
    curl_close ($ch);

    return  $server_output;

  }
  function importThirdpartyProducts($redirect){

    $results =  $this->db->SelectData("SELECT * FROM system_settings WHERE status ='Active'");

    $response = $this->SendThidpartyRequest($results[0]['instance_id']);
 
    $response_data = json_decode($response, true);

    foreach ($response_data as $key => $value) {

     $rs = $this->GetThirdPartyProductID($value['product_id']);
     if(count($rs)==0){
       $account_no=$this->getThirdPartyNo($_SESSION['office']);
       $office=$_SESSION['office'];
       $postData = array(
         'office_id' =>$office,
         'thirdparty_accountno' =>$account_no,
         'name' => $value['product_name'],
         'product_id' => $value['product_id'],
         'description' => $value['product_description'],
         'product_type' => $value['product_type'],
         'created_by' => $_SESSION['user_id']
       );
       $result = $this->db->InsertData('thirdparty_products', $postData);
     }
    }    
    if($redirect){
      header('Location: ' . URL . 'accounting?msg=imported');
    }else{
      return TRUE;
    }
  }

function UpdateGlAccountEquity($data, $id){

  $postData = array(
   'sacco_id' => $_SESSION['office'],
   'pointer_name' => $data['pname'],
   'description' => $data['description'],
   'product_id' => $data['pnumber'],
   'transaction_type_id' => $data['transaction_type'],
   'transaction_mode' => $data['transaction_mode'],
   'debit_account' => $data['source'],
   'credit_account' => $data['destination'],
 );

  $this->db->UpdateData('acc_gl_pointers', $postData, "`pointer_id` = '{$id}'");
  header('Location: ' . URL . 'products/editglpointersequity/'.$data["pnumber"].'/'.$id.'?msg=editsuccess');

} 

function UpdateGlAccountInsurance($data, $id){

  $postData = array(
   'sacco_id' => $_SESSION['office'],
   'pointer_name' => $data['pname'],
   'description' => $data['description'],
   'product_id' => $data['pnumber'],
   'transaction_type_id' => $data['transaction_type'],
   'transaction_mode' => $data['transaction_mode'],
   'debit_account' => $data['source'],
   'credit_account' => $data['destination'],
 );

  $this->db->UpdateData('acc_gl_pointers', $postData, "`pointer_id` = '{$id}'");
  header('Location: ' . URL . 'products/editglpointersinsurance/'.$data["pnumber"].'/'.$id.'?msg=editsuccess');

}    

function UpdateGlAccountLoan($data, $id){

  $postData = array(
   'sacco_id' => $_SESSION['office'],
   'pointer_name' => $data['pname'],
   'description' => $data['description'],
   'product_id' => $data['pnumber'],
   'transaction_type_id' => $data['transaction_type'],
   'transaction_mode' => $data['transaction_mode'],
   'debit_account' => $data['source'],
   'credit_account' => $data['destination'],
 );

  $this->db->UpdateData('acc_gl_pointers', $postData, "`pointer_id` = '{$id}'");
  header('Location: ' . URL . 'products/editglpointersloan/'.$data["pnumber"].'/'.$id.'?msg=editsuccess');

}

function UpdateGlAccountFixed($data, $id){

  $postData = array(
   'sacco_id' => $_SESSION['office'],
   'pointer_name' => $data['pname'],
   'description' => $data['description'],
   'product_id' => $data['pnumber'],
   'transaction_type_id' => $data['transaction_type'],
   'transaction_mode' => $data['transaction_mode'],
   'debit_account' => $data['source'],
   'credit_account' => $data['destination'],
  );

  $this->db->UpdateData('acc_gl_pointers', $postData, "`pointer_id` = '{$id}'");
  header('Location: ' . URL . 'products/editglpointersfixed/'.$data["pnumber"].'/'.$id.'?msg=editsuccess');

}

function UpdateGlAccountSavings($data, $id){

  $postData = array(
   'sacco_id' => $_SESSION['office'],
   'pointer_name' => $data['pname'],
   'description' => $data['description'],
   'product_id' => $data['pnumber'],
   'transaction_type_id' => $data['transaction_type'],
   'transaction_mode' => $data['transaction_mode'],
   'debit_account' => $data['source'],
   'credit_account' => $data['destination'],
 );

  $this->db->UpdateData('acc_gl_pointers', $postData, "`pointer_id` = '{$id}'");
  header('Location: ' . URL . 'products/editglpointerssavings/'.$data["pnumber"].'/'.$id.'?msg=editsuccess');

}

function UpdateGlAccountCharge($data, $id){

  $postData = array(
   'sacco_id' => $_SESSION['office'],
   'pointer_name' => $data['pname'],
   'description' => $data['description'],
   'product_id' => $data['pnumber'],
   'transaction_type_id' => $data['transaction_type'],
   'transaction_mode' => $data['transaction_mode'],
   'debit_account' => $data['source'],
   'credit_account' => $data['destination'],
 );

  $this->db->UpdateData('acc_gl_pointers', $postData, "`pointer_id` = '{$id}'");
  header('Location: ' . URL . 'products/editglpointerscharge/'.$data["pnumber"].'/'.$id.'?msg=editsuccess');

} 


function UpdateGlAccountThird($data, $id){

  $postData = array(
   'sacco_id' => $_SESSION['office'],
   'pointer_name' => $data['pname'],
   'description' => $data['description'],
   'product_id' => $data['pnumber'],
   'transaction_type_id' => $data['transaction_type'],
   'transaction_mode' => $data['transaction_mode'],
   'debit_account' => $data['source'],
   'credit_account' => $data['destination'],
 );

  $this->db->UpdateData('acc_gl_pointers', $postData, "`pointer_id` = '{$id}'");
  header('Location: ' . URL . 'products/editglpointersthird/'.$data["pnumber"].'/'.$id.'?msg=editsuccess');

}
function currency() {
  return $this->db->SelectData("SELECT * FROM m_currency ");
}

function getPaymentModes() {
  return $this->db->SelectData("SELECT * FROM payment_mode ");
}

function getTransactionTypes($product_type) {
  return  $this->db->SelectData("SELECT * FROM transaction_type WHERE product_type='".$product_type."'");

}

function getThirdPartyTransactionTypes($product_type, $sub_type) {
  return  $this->db->SelectData("SELECT * FROM transaction_type WHERE product_type='".$product_type."' AND sub_product_type='".$sub_type."'");

}

function getMissingThirdPartyTransactionTypes($id, $product_type, $sub_type) {
  $office=$_SESSION['office'];
  $pointers =  $this->db->SelectData("SELECT * FROM acc_gl_pointers WHERE sacco_id='".$office."' AND product_id = '".$id."'");

  $idz = '';
  if(!empty($pointers)){
    foreach ($pointers as $key => $value) {
      $idz .= "AND transaction_type_id != " . $value['transaction_type_id'] . " ";
    }
  }
  return  $this->db->SelectData("SELECT * FROM transaction_type WHERE product_type='".$product_type."' AND sub_product_type='".$sub_type."' $idz");

}

function getPartyProductdetails($id){
  //$office=$_SESSION['office'];
  return $this->db->SelectData("SELECT * FROM thirdparty_products WHERE id = '".$id."'");
}

function getMissingTransactionTypes($id, $product_type) {
  $office=$_SESSION['office'];
  $pointers =  $this->getPointers($id, $product_type);
  

    $idz = '';
  if(!empty($pointers)){
    foreach ($pointers as $key => $value) {
      $idz .= "AND transaction_type_id != " . $value['transaction_type_id'] . " ";
    }
  }
  return  $this->db->SelectData("SELECT * FROM transaction_type where product_type='".$product_type."' $idz");

}

function getChargeTransactionTypes($id, $pdtType) {
  $office=$_SESSION['office'];

  $charge =  $this->db->SelectData("SELECT * FROM m_charge WHERE id ='$id' AND office_id='".$office."'");

  $pointers =  $this->db->SelectData("SELECT * FROM acc_gl_pointers JOIN transaction_type ON transaction_type.transaction_type_id=acc_gl_pointers.transaction_type_id WHERE transaction_type.sub_product_type='0' AND transaction_type.product_type='".$charge[0]['charge_applies_to']."' AND acc_gl_pointers.sacco_id = '".$office."' AND acc_gl_pointers.product_id = '".$id."'");

  if(empty($pointers)){
    $tt_id = $charge[0]['transaction_type_id'];
    return $this->db->SelectData("SELECT * FROM transaction_type where transaction_type_id='".$tt_id."'");
  } else {
    return NULL;
  }
}


function getChargeTransactionTypes2($id, $pdtType) {
	
	 return $this->db->SelectData("SELECT * FROM transaction_type ");
	 
	 
  $office=$_SESSION['office'];

  $charge =  $this->db->SelectData("SELECT * FROM m_charge WHERE id ='$id' AND office_id='".$office."'");

  $pointers =  $this->db->SelectData("SELECT * FROM acc_gl_pointers JOIN transaction_type ON transaction_type.transaction_type_id=acc_gl_pointers.transaction_type_id WHERE transaction_type.sub_product_type='0' AND transaction_type.product_type='".$charge[0]['charge_applies_to']."' AND acc_gl_pointers.sacco_id = '".$office."' AND acc_gl_pointers.product_id = '".$id."'");

  if(empty($pointers)){
    $tt_id = $charge[0]['transaction_type_id'];
    return $this->db->SelectData("SELECT * FROM transaction_type where transaction_type_id='".$tt_id."'");
  } else {
    return NULL;
  }
}


function getGlaccounts() {
  $office=$_SESSION['office'];
  return  $this->db->SelectData("SELECT * FROM acc_ledger_account where disabled ='No' AND account_usage='Account' AND sacco_id = '".$office."' order by name");      
}
function getGlaccountdetails($id){
  return $this->db->selectData("SELECT * FROM acc_ledger_account where id='".$id."'");
}

function getModeofPayment($id){
  return $this->db->selectData("SELECT * FROM payment_mode where id='".$id."'");
}


function getPointers($id,$prodType, $charge=null) {
  $parent_office =$_SESSION['office'];

  if ($charge != NULL) {
    $result=$this->db->SelectData("SELECT * FROM acc_gl_pointers JOIN transaction_type ON transaction_type.transaction_type_id=acc_gl_pointers.transaction_type_id WHERE acc_gl_pointers.transaction_type_id = '$charge' AND acc_gl_pointers.sacco_id = '".$parent_office."' AND acc_gl_pointers.product_id = '".$id."'");
  } else {
    $result=$this->db->SelectData("SELECT * FROM acc_gl_pointers JOIN transaction_type ON transaction_type.transaction_type_id=acc_gl_pointers.transaction_type_id where acc_gl_pointers.product_type_id='".$prodType."' AND sacco_id = '".$parent_office."' and product_id = '".$id."' ");  
  }


  $count=count($result);  
  if($count>0){
    foreach ($result as $key => $value) {
      $gldebit=$this->getGlaccountdetails($value['debit_account']); 
      $glcredit=$this->getGlaccountdetails($value['credit_account']); 
      $paymode=$this->getModeofPayment($value['transaction_mode']); 
      $rset[$key]['pointer_id'] =$value['pointer_id']; 
      $rset[$key]['pointer_name'] =$value['pointer_name']; 
      $rset[$key]['transaction_type'] =$value['transaction_type_name'];
      $rset[$key]['transaction_type_id'] =$value['transaction_type_id'];
      //$rset[$key]['payment_by'] =$paymode[0]['value'];
      $rset[$key]['debit_account'] =$gldebit[0]['name'];
      $rset[$key]['credit_account'] =$glcredit[0]['name'];
    }
    return $rset;
  }
}

function hastransacted(){

  $office =$_SESSION['office'];

  $result=$this->db->SelectData("SELECT * FROM acc_gl_journal_entry WHERE office_id = ".$office. " AND transaction_id NOT LIKE 'OP%'");

  $count=count($result);         
  if($count>0){
    return FALSE;
  } else {
    return TRUE;
  }

}

function getPointerDetails($id, $product_id, $prodType) {
  $parent_office =$_SESSION['office'];
  $result=$this->db->SelectData("SELECT * FROM  acc_gl_pointers JOIN transaction_type ON transaction_type.transaction_type_id=acc_gl_pointers.transaction_type_id where transaction_type.product_type='".$prodType."' AND sacco_id = '".$parent_office."' and pointer_id = '". $id ."' and product_id = '".$product_id."' ");
  $count=count($result);         
  if($count>0){
    foreach ($result as $key => $value) {
      $gldebit=$this->getGlaccountdetails($value['debit_account']); 
      $glcredit=$this->getGlaccountdetails($value['credit_account']); 
      $paymode=$this->getModeofPayment($value['transaction_mode']); 
      $rset[$key]['pointer_id'] =$value['pointer_id']; 
      $rset[$key]['pointer_name'] =$value['pointer_name'];
      $rset[$key]['description'] =$value['description']; 
      $rset[$key]['transaction_type'] =$value['transaction_type_name'];
      //$rset[$key]['payment_by'] =$paymode[0]['value'];
      $rset[$key]['debit_account'] =$gldebit[0]['name'];
      $rset[$key]['credit_account'] =$glcredit[0]['name'];
    }

    return $rset;  

  }
}
function getdestination($id){
  $office=$_SESSION['office'];
  $query= $this->db->SelectData("SELECT * FROM acc_ledger_account where disabled ='No' AND account_usage='Account' AND id!='".$id."' AND  sacco_id = '".$office."' order by name ");
  print_r(json_encode($query));
  die();
}

function getstaffList($id) {

  return $this->db->SelectData("SELECT * FROM m_staff where id='" . $id . "' ");
}

function loanproductList($office) {
  return $this->db->SelectData("SELECT * FROM m_product_loan where status!='closed' AND office_id = '".$office."' ");
}

function loandetails($id) {
  return $this->db->SelectData("SELECT * FROM m_loan where loan_id='" . $id . "' ");
}

function savingsdetails($acc) {
  return $this->db->SelectData("SELECT * FROM m_savings_account where account_no='".$acc."' ");
}

function getMemberName($id){

  $result= $this->db->selectData("SELECT * FROM members WHERE c_id='".$id."' ");
  if(!empty($result[0]['company_name'])){
    $name = $result[0]['company_name'];
  }else{
    $name = $result[0]['firstname']." ".$result[0]['middlename']." ".$result[0]['lastname'] ;
  }
  return $name;

}

function savingsaccountdetails($id) {

  $result = $this->db->SelectData("SELECT * FROM m_savings_account where member_id='" . $id . "' ");
  foreach ($result as $key => $value) {


    return $this->db->SelectData("SELECT * FROM m_savings_account where member_id='" . $id . "' ");

    return $result;

  }
}
function getClientSavingsdDetails($id){

  return $this->db->SelectData("SELECT p.name,member_id, minimum_balance as amount, s.id ,account_no FROM m_savings_product p INNER JOIN m_savings_account s

    where s.product_id = p.id and  s.member_id='".$id."' order by s.id DESC");

}

function m_loan_charge($id){


  $chargedetails = $this->db->SelectData("SELECT * FROM m_loan_charge where loan_id ='" . $id . "' "); 
  return $chargedetails;
}

function insurance_stampduty($id){


  $loandetails = $this->loandetails($id);

  $chargedetails = $this->db->SelectData("SELECT * FROM m_loan_charge where loan_id ='" . $id . "' "); 

  $charge = 0;
  $insurance_charge = 0;
  $Stampduty_charge = 0;

  $postData3 = array();


    //insurance_charge insurance
  $amount = $loandetails[0]['approved_principal'];
  $insurance_charge = $amount*$loandetails[0]['insurance'];
        //Stampduty_charge
  $Stampduty_charge = $amount*$loandetails[0]['stamp_duty'];
  $balanceAfterCharge =  $amount - ($charge + $insurance_charge + $Stampduty_charge);
  $postData3['total_charge'] = $charge;

  $postData3['original_disbursement_amount'] = $amount;
  $postData3['insurance_charge'] = $insurance_charge;
  $postData3['stamp_duty_charge'] = $Stampduty_charge;
  $postData3['amount_to_be_disbursed_after_charges'] = $balanceAfterCharge;


  return  $postData3;

}


function m_loan_collateral($id){
 $results = $this->db->SelectData("SELECT * FROM m_loan_collateral where loan_id='".$id."' ");


 foreach ($results as $key => $value) { 
  $colleteral_id = $value['name']; 
  $collt = $this->db->SelectData("SELECT * FROM loan_product_collateral where collateral_id='".$colleteral_id."' ");



  $rset[$key]['id'] =$value['id']; 
  $rset[$key]['name'] =$collt[0]['collateral_name']; 
}

return $rset;

}


function getClient($id) {

  return $this->db->SelectData("SELECT * FROM members c INNER JOIN m_branch b where c.office_id  = b.id AND c.c_id='" . $id . "'  order by c.c_id desc");
}

/* -------charge products  ---- */

function getCharge($id) {

  $result = $this->db->SelectData("SELECT * FROM m_charge where id=".$id." ");

  return $result;
}

function createcharge() {
  $data = $_POST;

  $commision_amount = 0;
  if ($data['flatamount'] != "" && $data['peramount'] == "") {
    $commision_amount = $data['flatamount'];
  } else if ($data['peramount'] != "" && $data['flatamount'] == "") {
    $commision_amount = (($data['peramount']/100)*$data['amount']);
  }
        //$acc_no= $this->AccountNo();
  if (!empty($data['ispenalty'])) {
    $ispenalty = 1;
  } else {
    $ispenalty = 0;
  }
  $office = $_SESSION['office'];
  $postData = array(
    'office_id' => $_SESSION['office'],
    'charge_applies_to' => $data['chargeappliesto'],
    'name' => $data['fname'],
    'charge_time' => $data['periodic_charge'],
    'transaction_type_id' => $data['chargetype'],
    'charge_calculation_enum' => $data['chargecalculation'],
    'amount' => str_replace( ',', '', $data['amount']),
    'is_penalty' => $ispenalty,
    'commission' => $data['commission'],
    'commission_type' => $data['commission_type'],
    'percentage' => $data['peramount'],
    'commision_amount' => $commision_amount,
  );

  $result = $this->db->InsertData('m_charge', $postData);
  
  $pData = array(
           'sacco_id' =>$office,
         'pointer_name' => $data['fname'],
         'description' => $data['fname'],
         'product_id' => $result,
         'transaction_type_id' => $data['chargetype'],
         'transaction_mode' => 0,
         'product_type_id' => 6,
         'debit_account' => $data['source'],
         'credit_account' => $data['destination'],
        );
		
  $this->db->InsertData('acc_gl_pointers', $pData);
        $NewData = array(         
          'status' =>'Active'
        );
        $this->db->UpdateData('m_charge', $NewData,"`id` = '{$result}'");

  header('Location:'.URL.'products/chargeproducts?msg=success');
}

function UpdateChargeProduct($data) {
  $id = $data['id'];

  $postData = array(
        //'office_id' => $_SESSION['office'],
    'name' => $data['fname'],
    'charge_applies_to' => $data['chargeappliesto'],
    'transaction_type_id' => $data['chargetype'],
    'charge_calculation_enum' => $data['chargecalculation'],
    'amount' => str_replace( ',', '',$data['amount']),
        //'is_active' => $data['activecheck'],
    'is_penalty' => $data['ispenalty'],
           // 'income_or_liability_account_id' => $data['incomefcharge']
  );

  $this->db->UpdateData('m_charge', $postData, "`id` = '{$id}'");
  header('Location: ' . URL . 'products/chargeproducts?msg=success');
}

function DeleteChargeProduct($id) {

  $postData = array(
    'is_deleted' => '1',
  );

  $this->db->UpdateData('m_charge', $postData, "`id` = '{$id}'");
  header('Location: ' . URL . 'products/chargeproducts?msg=success');
}


function transactiondetails($acc) {

  return $this->db->SelectData("SELECT * FROM m_savings_account_transaction where 

    savings_account_no='" . $acc . "' ");
}


function createnewloanproduct($data) {
    
	try{
	    
        $office = $_SESSION['office'];
        $user_id = $_SESSION['user_id'];
        
        $product_name = $data['pname'];
        
        if($this->checkProductLoanExists($product_name))return $this->MakeJsonResponse(101,"product name exists", "");


        foreach(array_keys($data) as $key=>$v){
            if($data[$v]==""){
                return $this->MakeJsonResponse(102,$v." is required", $v);
            }
        }

        $rate_per_period = $data['defaultnominalint'];
        $int_period = $data['period'];
        $annual_interest = $this->val->getAnnualInterest($int_period,$rate_per_period);


        if(isset($data['principledefailt']) && $data['principledefailt'] != ""  && $data['principledefailt'] != " "){
            $min_principal_amount = $this->val->validateNumber($data['principledefailt']);
        } else {
            $min_principal_amount = $this->val->validateNumber($data['defaultprinciple']);
        }
        $max_principal_amount = $this->val->validateNumber($data['principlemax']);

        if(empty($min_principal_amount) || empty($max_principal_amount)){
        	return $this->MakeJsonResponse(504,"invalid input amount", "");
        }

        if(isset($data['share_percentage_for_loan'])){
        	$percentage = $data['share_percentage_for_loan'];
        }else{
        	$percentage = 0;
        }
        if(isset($data['loan_transaction_months'])){
        	$months = $data['loan_transaction_months'];
        }else{
        	$months = 0;
        }

        $postData = array(
            'name' => $data['pname'],
            'office_id' => $office,
            'description' => $data['description'],
            'min_principal_amount' =>   $min_principal_amount,        
            'max_principal_amount' => $max_principal_amount,
            'nominal_interest_rate_per_period' => str_replace(',', '', $rate_per_period),
            'days_in_year' => $data['days_in_year'],    
            'installment_option' => $data['installment_option'],
            'interest_method' => $data['interest_method'],          
            'interest_period' => $int_period,          
            'annual_nominal_interest_rate' => $annual_interest,         
            'duration' => $data['duration'],           
            'min_duration_value' => $data['min_duration_value'], 
            'max_duration_value' => $data['max_duration_value'],           
            'grace_period' => $data['grace_period'],           
            'grace_period_value' => $data['grace_period_value'],           
            'insurance' => $data['insurance'],
            'stamp_duty' => $data['stamp_duty'],
            'share_percentage_for_loan' => $percentage,
            'loan_transaction_months' => $months,
            'created_by' => $user_id,
            
            'group_id' => $data['group_id'],
            'number_of_guarantors' => $data['number_of_guarantors'],
            'fraction_of_savings' => $data['fraction_of_savings'],
            'guarantor_minimum' => $data['guarantor_minimum'],
        
        );
        $result = $this->db->InsertData('m_product_loan', $postData);


        if(!empty($result)){
            $num_charge = count($_POST['s_charges']);
            $num_charges = $_POST['s_charges'];
        
            for ($i = 0; $i <$num_charge; $i++) {
              if (!empty($num_charges[$i])) {
                $postData1 = array(
                  'product_loan_id' => $result,
                  'charge_id' => $num_charges[$i],
                );
                $m_product_loan_charge = $this->db->InsertData('m_product_loan_charge', $postData1);
              }
            }
        
            $collateral = count($_POST['collateral']);
            $num_collateral = $_POST['collateral'];
        
            for ($i = 0; $i <$collateral; $i++) {
              if (!empty($num_collateral[$i])) {
                $postcollateral = array(
                  'loan_product_id' => $result,
                  'collateral_name' => $num_collateral[$i],
                );
                $loan_product_collateral = $this->db->InsertData('loan_product_collateral', $postcollateral);
              }
            }
            return $this->MakeJsonResponse(100,"success", URL."products/viewloanproducts/".$result."msg=success");
        
        }else{
            return $this->MakeJsonResponse(103,$result, "");
        }

	}catch(Exception $e){
		return $this->MakeJsonResponse(203,"unknown error".$e->getMessage(), "");
	}

}

function getGlaccountname($id){
  if ($id != '') {
    $results = $this->db->SelectData("SELECT * FROM acc_ledger_account where id='".$id."' ");
    $ret=$results[0]['name'];
            //print_r($ret);
            //die();
    return $ret;
  } else {
    return '';
  }
}

function getloanproduct($id) {
  $result = $this->db->SelectData("SELECT * FROM m_product_loan where id='" . $id . "' order by id ");
  return $result;
} 

function checkForLoan($id) {
  $result = $this->db->SelectData("SELECT count(product_id) FROM m_loan where product_id='".$id ."'");

  return $result;
} 
function getcollateral($id) {
  $result = $this->db->SelectData("SELECT * FROM loan_product_collateral where loan_product_id='" . $id . "' order by collateral_id");



  return $result;
}
function getloanProductcharges($id) {
  $result = $this->db->SelectData("SELECT * FROM  m_product_loan_charge where product_loan_id='" . $id . "' order by charge_id");
  if(count($result)>0){
   foreach($result as $key =>$value){
    $recorded_by=$this->getCharge($result[$key]['charge_id']);   
    $payment[$key]['charge_name']=$recorded_by[0]['name'];   
    $payment[$key]['charge_amount']=$recorded_by[0]['amount'];   
  } 

  return $payment;  

}


return $result;
}

function Updatenewloanproduct($data) {

  $id = $data['id'];

  $resultcheckbox = $this->db->SelectData("SELECT * FROM m_product_loan where id='".$id."' order by id ");
  $accounting_type = $resultcheckbox[0]['accounting_type'];
  if (!empty($data['cash'])) {

    $accounting_type = $data['cash'];
  } else if (!empty($data['accrual'])) {

    $accounting_type = $data['accrual'];
  }
  if (empty($data['varinstalment'])) {

    $data['varinstalment'] = $resultcheckbox[0]['variable_instalment'];
  }



  $postData = array(
    'name' => $data['pname'],
    'description' => $data['description'],
    'min_principal_amount' => str_replace(',', '', $data['principledefailt']),          
    'max_principal_amount' => str_replace(',', '', $data['principlemax']),
    'nominal_interest_rate_per_period' => str_replace(',', '', $data['defaultnominalint']),
    'days_in_year' => $data['days_in_year'],    
    'installment_option' => $data['installment_option'],
    'interest_method' => $data['interest_method'],          
    'duration' => $data['duration'],           
    'min_duration_value' => $data['min_duration_value'], 
    'max_duration_value' => $data['max_duration_value'],           
    'grace_period' => $data['grace_period'],           
    'grace_period_value' => $data['grace_period_value'],           
    'insurance' => $data['insurance'],
    'stamp_duty' => $data['stamp_duty'],
    'accounting_type' => $accounting_type,
    'created_by' => $_SESSION['user_id'],

  );
  $this->db->UpdateData('m_product_loan', $postData, "`id` = '{$id}'");

  if(!empty($_POST['s_charges'])){
    $num_charge = count($_POST['s_charges']);
    $num_charges = $_POST['s_charges'];

    for ($i = 0; $i < $num_charge; $i++) {
      if (!empty($num_charges[$i])) {

        $m_product_loan_charge = $this->db->SelectData("SELECT * FROM m_product_loan_charge where product_loan_id='" . $id . "' ");
        $postData1 = array(
          'product_loan_id' => $id,
          'charge_id' => $num_charges[$i],
        );
        if (!empty($m_product_loan_charge)) {

                    //$this->db->UpdateData('m_product_loan_charge', $postData3,"  `product_loan_id` = '{$id}' ");
        } else {
          $this->db->InsertData('m_product_loan_charge', $postData1);
        }
      }
    }
  }



  header('Location: ' . URL . 'products/editloanproduct/'.$id.'?msg= Updated');  



}

function getAssets() {


  return $this->db->selectData("SELECT * FROM acc_ledger_account where classification='Assets' AND account_usage='Account' ");
}

function getLiability() {


  return $this->db->selectData("SELECT * FROM acc_ledger_account where classification='Liabilities' AND account_usage='Account' ");
}

function getEquity() {


  return $this->db->selectData("SELECT * FROM acc_ledger_account where classification='Equity' AND account_usage='Account' ");
}

function getIncome() {


  return $this->db->selectData("SELECT * FROM acc_ledger_account where classification='Incomes' AND account_usage='Account' ");
}

function getExpenses() {


  return $this->db->selectData("SELECT * FROM acc_ledger_account where classification='Expenses' AND account_usage='Account' ");
}

function getChargesDetails($id) {

  $office = $_SESSION['office'];

  $result = $this->db->selectData("SELECT * FROM m_charge INNER JOIN products ON m_charge.charge_applies_to = products.p_id WHERE m_charge.office_id = " . $office . " AND id= '" . $id . "' ORDER BY m_charge.id ");

  foreach ($result as $key => $value) {
    $rset[$key]['id'] = $result[$key]['id'];
    $rset[$key]['name'] = $result[$key]['name'];
    if ($result[$key]['is_penalty'] == 1) {
      $rset[$key]['type'] = "Penalty";
    } else {
      $rset[$key]['type'] = "Charge";
    }
    $rset[$key]['amount'] = $result[$key]['amount'];
    $rset[$key]['transaction_type_id'] = $result[$key]['transaction_type_id'];
    $rset[$key]['charge_time'] = $result[$key]['charge_time'];
        //$rset[$key]['chargetime'] = $this->getChargeTime($result[$key]['charge_time']);
    $rset[$key]['chargetime'] = $this->getChargeTime($result[$key]['transaction_type_id']);
    $rset[$key]['chargeappliesto'] =  $result[$key]['charge_applies_to'];
    $rset[$key]['commission'] =  $result[$key]['commission'];
    $rset[$key]['commision_amount'] =  $result[$key]['commision_amount'];
          //  $rset[$key]['chargeappliesto'] = $this->getChargePurpose($result[$key]['charge_applies_to']);
    if ($result[$key]['is_active'] == 1) {
      $rset[$key]['is_active'] = "Yes";
    } else {
      $rset[$key]['is_active'] = "NO";
    }
    if ($result[$key]['charge_calculation_enum'] == 1) {
      $rset[$key]['charge_calculation_enum'] = "Flat";
    } else {
      $rset[$key]['charge_calculation_enum'] = "% Amount";
    }
  }
  return $rset;
}

function getChargePurpose($id) {
  if ($id == 1) {
    $purpose = "Loan";
  } else if ($id == 2) {
    $purpose = "Saving and Deposits";
  } else if ($id == 3) {
    $purpose = "Client";
  } else {
    $purpose = "Not specified";
  }
  return $purpose;
}

function getChargeTime($id) {

  $result =  $this->db->selectData("SELECT * FROM transaction_type where transaction_type_id = '" . $id . "' ");
  $time = $result[0]['transaction_type_name'];

  return $time;
}


function getchargeapplicity($id) {

  $result =  $this->db->selectData("SELECT * FROM transaction_type where product_type = '" . $id . "' ");
  foreach($result as $key => $value){
    echo  '<option value="'.$value['transaction_type_id'].'"> '.$value['transaction_type_name'].'</option>';    
  }

}

function m_savings_product_charge($savings_product_id, $charge_applies_to) {



  $query = $this->db->SelectData("SELECT * FROM m_savings_product_charge sc JOIN m_charge mc 

    where sc.charge_id =mc.id  and sc.savings_product_id ='" . $savings_product_id . "'  and mc.charge_applies_to ='" . $charge_applies_to . "' order by sc.charge_id desc");

  return $query;
}

function getCharges($id) {
  $office=$_SESSION['office'];
  return $this->db->selectData("SELECT * FROM m_charge WHERE charge_applies_to = '$id' AND status = 'Active' AND office_id = '$office' ORDER BY id ");
}

function getLoanChargeByCurrency($id) {


  return $this->db->selectData("SELECT * FROM m_charge where charge_applies_to = '" . $id . "' order by id ");
}

function Deleteloanproduct($id) {

  $postData = array('status' => 'closed');
  $this->db->UpdateData('m_product_loan', $postData, "`id` = '{$id}'");
  header('Location: ' . URL . 'products/loanproducts?msg=success');
}

function Updatenewshareproduct($id){
    
    $postData = array(
        'share_name' => $_POST['share_name'], 
        'description' => $_POST['description'], 
        'amount_per_share' => $_POST['amount_per_share']
    );
    
    $this->db->UpdateData('share_products', $postData, "`id` = '{$id}'");
    header('Location: ' . URL . 'products/getshareproduct/'.$id.'?msg=success');
    
}

function saveProduct() {
  $data = $_POST;
  $office=$_SESSION['office'];
  $postData = array(
   'office_id' =>  $office,
   'name' => $data['product_name'],
   'created_by' => $_SESSION['user_id'],
   'description' => $data['savings_description'],
   'nominal_interest_rate' => str_replace( ',', '',$data['nominal_interest']),
   'interest_posting_period' => $data['i_postingperiod'],
   'interest_calculation_method' => $data['s_interestCalculationTypeMethod'],
   'days_in_year' => $data['days_in_year'],
   'min_required_opening_balance' => str_replace( ',', '',$data['min_open_balance']),
   'min_required_balance' => str_replace( ',', '',$data['min_required_balance']),
   'minimum_balance_for_interest_calculation' => str_replace( ',', '',$data['min_balance_interst_cal']),
 );

  $result = $this->db->InsertData('m_savings_product', $postData);

  $num_charge = count($_POST['s_charges']);
  $num_charges = $_POST['s_charges'];

  for ($i = 0; $i < $num_charge; $i++) {

    if (!empty($num_charges[$i])) {
      $postData1 = array(
        'savings_product_id' => $result,
        'charge_id' => $num_charges[$i],
      );
      $this->db->InsertData('m_savings_product_charge', $postData1);
    }
  }

  header('Location:'.URL.'products/addsavingsglpointers/'.$result.'?msg=success');
      //  header('Location:' . URL . 'products/newsavingsproduct?msg=added');
}

function UpdateSavingProduct($data) {
try{
  $id = $data['id'];
  $resultcheckbox = $this->db->SelectData("SELECT * FROM m_savings_product where id='" . $id . "' order by id ");

  $postData = array(
    'name' => $data['product_name'],
    'description' => $data['savings_description'],
    'nominal_interest_rate' => $data['nominal_interest'],
    'interest_posting_period' => $data['i_postingperiod'],
    'interest_calculation_method' => $data['s_interestCalculationTypeMethod'],
    'days_in_year' => $data['days_in_year'],
    'min_required_opening_balance' => str_replace( ',', '',$data['min_balance']),
    'min_required_balance' => str_replace( ',', '',$data['min_required_balance']),
    'minimum_balance_for_interest_calculation' => str_replace( ',', '',$data['min_balance_interst_cal']),           
  );
  $this->db->UpdateData('m_savings_product', $postData, " `id` = '{$id}'");

  $num_charge = count($_POST['s_charges']);
  $num_charges = $_POST['s_charges'];


  header('Location:' . URL . 'products/savingsproducts?add=true');
}catch(Exception $e){
    header('Location:' . URL . 'products/savingsproducts?add=false&message='.$e->getMessage());
}
}

function chargeproductList($office) {
  $res = $this->db->SelectData("SELECT * FROM m_charge INNER JOIN products ON m_charge.charge_applies_to = products.p_id WHERE is_deleted != 1  AND m_charge.office_id = $office ORDER BY m_charge.id ");


  $rset = array();
  foreach ($res as $key => $value) {
    $rset[$key]['id'] = $res[$key]['id'];
    $rset[$key]['name'] = $res[$key]['name'];

    $rset[$key]['charge_applies_to'] =  $value['p_name'];
    $rset[$key]['status'] =  $value['status'];

    if ($res[$key]['is_penalty'] == 1) {
      $rset[$key]['is_penalty'] = "yes";
    } else {
      $rset[$key]['is_penalty'] = "NO";
    }
    if ($res[$key]['is_active'] == 1) {
      $rset[$key]['is_active'] = "yes";
    } else {
      $rset[$key]['is_active'] = "NO";
    }
  }
  return $rset;
}

function ApproveLoanProduct() {

}

/* -------Savings products  ---- */

function savingsProductsList($office) {
  return $this->db->SelectData("SELECT * FROM m_savings_product  where office_id = '".$office."'");
}

function productdetails($id) {
  return $this->db->SelectData("SELECT * FROM m_savings_product where id='" . $id . "'");
}

function scheduledetails_due($id) {

  $results = $this->db->SelectData("SELECT * FROM m_loan_repayment_schedule where completed = 'false' AND loan_id='" . $id . "' order by id");
  return $results;
}
function scheduledetailspaid($id) {

  $results = $this->db->SelectData("SELECT * FROM m_loan_repayment_schedule where completed = 'true' AND loan_id='" . $id . "' order by id");
  return $results;
}

function loantransaction($id) {

  $results = $this->db->SelectData("SELECT * FROM m_loan_transaction where  loan_id='" . $id . "'");
  return $results;
}

function scheduledetails($id) {

  $results = $this->db->SelectData("SELECT * FROM m_loan_repayment_schedule where loan_id='" . $id . "'");
  if ($results) {

    return $results;
  } else {

    return false;
  }
}

function getLoanStatement(){


  $date = date('Y-m-d');
  $first_day = date('Y-m-01');
  $data = $_POST;


  $loan_id =$data['loan_id'];
  $option =$data['loanoption'];
  $start =date('Y-m-d',strtotime($data['date1']));
  $end =date('Y-m-d',strtotime($data['date2']));

  if($option=='current month'){
   $results = $this->db->SelectData("SELECT * FROM m_loan_transaction where  loan_id='" . $loan_id . "' AND transaction_date BETWEEN '" . $first_day . "' AND '" . $date . "' ");


 } else if($option=='specified period'){

   $results = $this->db->SelectData("SELECT * FROM m_loan_transaction where  loan_id='" . $loan_id . "' AND transaction_date BETWEEN '" . $start . "' AND '" . $end . "' ");



 }else{


   $results = $this->db->SelectData("SELECT * FROM m_loan_transaction where  loan_id='" . $loan_id . "' AND transaction_date = '" . $date . "' ");


 }
 return $results;
}

/* -------fixed deposit products  ---- */

function fixedDepositProducts($office) {
 return $this->db->SelectData("SELECT * FROM fixed_deposit_product where office_id = '".$office."' ");
}

function fixedDepositProductsdetails($id) {

  return $this->db->SelectData("SELECT * FROM fixed_deposit_product where id='" . $id . "' ");
}



function createfixedDepositProducts() {

  $data = $_POST;
  $office=$_SESSION['office'];
  $postData = array(
   'office_id' =>   $office,
   'name' => $data['product_name'],
   'created_by' => $_SESSION['user_id'],
   'description' => $data['fixeddeposite_description'],
   'minimum_deposit_amount' => $data['minDepositAmount'],
   'maximum_deposit_amount' => $data['maxDepositAmount'],
   'interest_posting_period' => $data['i_postingperiod'],
   'interest_calculation_method' => $data['s_interestCalculationTypeMethod'],
   'days_in_year' => $data['days_in_year'], 
   'minimum_deposit_term' => $data['minDepositTerm'],
   'minimum_term_value' => $data['minDepositTermTypeId'],
   'maximum_deposit_term' => $data['maximumDepositTerm'],
   'maximum_term_value' => $data['maxDepositTermTypeId'],
 );

  $result = $this->db->InsertData('fixed_deposit_product', $postData);
  if(!empty($_POST['s_charges'])){
    $num_charge = count($_POST['s_charges']);
    $num_charges = $_POST['s_charges'];

    for ($i = 0; $i < $num_charge; $i++) {
      if (!empty($num_charges[$i])) {
        $postData1 = array(
          'savings_product_id' => $result,
          'charge_id' => $num_charges[$i],
        );
        $this->db->InsertData('m_savings_product_charge', $postData1);
      }
    }
  }


   // header('Location:' . URL . 'products/fixeddepositproducts?update=true');
  header('Location:' . URL . 'products/addglpointerstime/'.$result.'?msg=true');
}

function UpdatefixedDepositProducts() {


  $data = $_POST;
  $id = $_POST['id'];

  $postData = array(
    'name' => $data['product_name'],
    'created_by' => $_SESSION['user_id'],
    'description' => $data['fixeddeposite_description'],
    'minimum_deposit_amount' => $data['minDepositAmount'],
    'maximum_deposit_amount' => $data['maxDepositAmount'],
    'interest_posting_period' => $data['i_postingperiod'],
    'interest_calculation_method' => $data['s_interestCalculationTypeMethod'],
    'days_in_year' => $data['days_in_year'], 
    'minimum_deposit_term' => $data['minDepositTerm'],
    'minimum_term_value' => $data['minDepositTermTypeId'],
    'maximum_deposit_term' => $data['maximumDepositTerm'],
    'maximum_term_value' => $data['maxDepositTermTypeId'],
  );

  $this->db->UpdateData('fixed_deposit_product', $postData, "`id` = '{$id}'");
    //print_r($postData);echo '<br/>';
    //die();

  for ($i = 0; $i < $num; $i++) {
            //naming the select in accounting by assigning them nos
            // eg Saving reference financial_account_type is 1
    $financial_account_type = $i + 1;
    if (!empty($postData2[$i])) {
      $result_gl_account_id = $this->db->SelectData("SELECT * FROM acc_gl_pointers where product_id='" . $id . "' and product_type ='2' and financial_account_type ='" . $financial_account_type . "' order by id ");

      $postData3 = array(
        'product_id' => $id,
        'product_type' => '2',
        'financial_account_type' => $financial_account_type,
        'gl_account_id' => $postData2[$i],
      );
      if (!empty($result_gl_account_id)) {
        $acc_product_id = $result_gl_account_id[0]["id"];

        $this->db->UpdateData('acc_gl_pointers', $postData3, "  `id` = '{$acc_product_id}' ");
      } else {

        $this->db->InsertData('acc_gl_pointers', $postData3);
      }
    }
  }





  header('Location:' . URL . 'products/fixeddepositproducts?add=true');
}

function paymentType() {
  return $this->db->SelectData("SELECT * FROM payment_mode order by id ");
}

function officeList($id = null) {
  if ($id == null) {
    $result = $this->db->SelectData("SELECT * FROM m_branch where b_status='Active' order by id ");
  } else {
    $result = $this->db->SelectData("SELECT * FROM m_branch where b_status='Active' and id !='" . $id . "' order by id ");
  }
  foreach ($result as $key => $value) {
    $officename = $this->officeName($result[$key]['id']);
    $parent_name = $this->officeName($result[$key]['parent_id']);
    $rset[$key]['office_id'] = $result[$key]['id'];
    $rset[$key]['parent_name'] = $parent_name;
    $rset[$key]['name'] = $officename;
    $rset[$key]['opening_date'] = $value['opening_date'];
  }
  return $rset;
}

function officeName($id) {
  if ($id != '') {
    $results = $this->db->SelectData("SELECT * FROM m_branch where id='" . $id . "'");

    return $results[0]['name'];
  } else {
    return '';
  }
}

function currencyName($id) {
  if ($id != '') {
    $results = $this->db->SelectData("SELECT * FROM m_currency where id='" . $id . "'");

    return $results[0]['name'] . "  " . $results[0]['code'];
  } else {
    return '';
  }
}



function get_interest_factor($year_term, $monthly_interest_rate) {

  $factor = 0;

  $base_rate = 1 + $monthly_interest_rate;

  $denominator = $base_rate;

  for ($i = 0; $i < ($year_term * 12); $i++) {

    $factor += (1 / $denominator);

    $denominator *= $base_rate;
  }

  return $factor;
}

function amortization_Calculation() {


        /*
          EMI (Equated Monthly Installment)
          EMI = i*P / [1- (1+i)^-n]
          P = Loan amount
          r = Rate of interest per year
          n = Term of the loan in periods
          l = Length of a period (fraction of a year, i.e., 1/12 = 1 month, 14/360 = bi-weekly.)
          i = Interest rate per period (r*l)

          A = payment Amount per period
          P = initial Principal (loan amount)
          r = interest rate per period
          n = total number of payments or periods


         */ $ir = 10;
          $n = .4;
          $original_principle = 100000;
          $P = $original_principle;
          $Prin_amt = $original_principle;
          $r = ($ir / 100);
          $l = (1 / 12);
          $interest = $r * $l;
          $n = $n * 12;

          $pow = pow((1 + ( $r * $l)), (-($n)));
          $formula = ( $interest * $P / (1 - $pow ));
          $EMI = number_format((float) ($formula), 2, '.', '');
          $remaining_balance = $P;
        //echo $pow;

          echo '  <div class="col-sm-6 col-md-6">
          <table class="table table-striped table-bordered">
          <tbody><tr>
          <th class="table-bold-loan ng-binding">Monthly Installment</th>
          <th class="table-bold-loan ng-binding">Beginning Balance</th>
          <th class="table-bold-loan ng-binding">Interest</th>
          <th class="table-bold-loan ng-binding">Principal</th>
          <th class="table-bold-loan ng-binding">Ending Balance</th>

          </tr>
          <tr>';


          for ($i = 0; $i < $n; $i++) {
            //print_r($P );number_format("1000000",2). number_format((float)$number, 2, '.', '');

            $P = $remaining_balance;
            $ia = number_format((float) ($interest * $P), 2, '.', '');
            $interestamt = number_format((float) ($interest * $Prin_amt), 2, '.', '');

            $principle = $EMI - $ia;

            $interestForMonth = $P * $interest;
            $principalForMonth = number_format((float) ($EMI - $interestForMonth), 2, '.', '');
            $remaining_balance = $P - $principalForMonth;


            echo '  <tr>
            <th class="table-bold-loan n g-binding">' . $P . '</th>
            <th class="table-bold-loan ng-binding">' . $interestForMonth . '</th>
            <th class="table-bold-loan ng-binding">' . $principalForMonth . '</th>
            <th class="table-bold-loan ng-binding">' . $remaining_balance . '</th>

            </tr>';
            $P -= $EMI;
          }

          echo ' </tbody></table>';
        }

        function getPaymentNo($id) {

          return $loan_details = $this->db->SelectData("SELECT * FROM m_loan_repayment_schedule  where loan_id='" . $id . "' and completed=0 order by id ");
        }
        
        function getlastPaymentNo($id) {

         $last_id = $this->db->SelectData("SELECT MAX(id) as last_id FROM m_loan_repayment_schedule  where loan_id='" . $id . "' ");
         $id2 = $last_id[0]['last_id'];
         $repayment = $this->db->SelectData("SELECT * FROM m_loan_repayment_schedule  where id='" . $id2 . "' ");

         return $repayment;

       }


///shares processing

       function saveShares(){
         $data =  $_POST;
         $user=$_SESSION['user_id'];
         $office=$_SESSION['office'];
         $postData = array(
          'office_id' =>  $office,
          'share_name' => $data['sname'],
          'description' => $data['description'],
          'amount_per_share' => $data['samount'],
          'created_by' =>$user,

        );

         $result = $this->db->InsertData('share_products', $postData);


    //    addglpointersequity/4
//header('Location:'.URL.'products/shares??msg=success');
         header('Location:'.URL.'products/addglpointersequity/'.$result.'?msg=success');

       }

       function saveInsurance($data){
        $postData = array(
          'office_id' =>  $_SESSION['office'],
          'name' => $data['name'],
          'description' => $data['description'],
          'min_amount' => $data['min_amount'],
          'max_amount' => $data['max_amount'],
          'cover' => $data['cover'],
          'reward' => $data['reward'],
          'claim_penalty' => $data['claim_penalty'],
          'claim_type' => $data['claim_type'],
          'recovery_time' => $data['recovery_time'],
          'recovery_time_type' => $data['recovery_time_type'],
          'category' => $data['category'],
          'product_type' => $data['product_type'],
          'payment_freq' => $data['payment_freq'],
          'payout_freq' => $data['payout_freq'],
          'created_by' => $_SESSION['user_id']
        );

         $result = $this->db->InsertData('insurance_products', $postData);
         header('Location:'.URL.'products/addglpointersinsurance/'.$result.'?msg=success');

       }

       function saveInsuranceCategory($data){
         $postData = array(
          'office_id' =>  $_SESSION['office'],
          'name' => $data['name']
        );

         $result = $this->db->InsertData('insurance_categories', $postData);
         header('Location:'.URL.'products/insurancecategories/?msg=success');

       }

        function getInsuranceCategories(){
            $office=$_SESSION['office'];
            return $this->db->SelectData("SELECT * FROM insurance_categories WHERE office_id = '".$office."' AND status = 'Active'");
        }
	
    	function getInsuranceCategory($id){
    		$office = $_SESSION['office'];
    		$result =  $this->db->SelectData("SELECT * FROM insurance_categories WHERE office_id = '".$office."' AND id = '" . $id ."'");
    	    return $result;	
    	}
    	
    	function updateInsuranceCategory($id){
    	    
    	    $postData = array(
                'name' => $_POST['name']
            );

            $this->db->UpdateData('insurance_categories', $postData, "`id` = '{$id}'");
            header('Location: ' . URL . 'products/insurancecategories?msg=updated');
    	}
    	
    	function deleteInsuranceCategory($id){
    	    
    	    $postData = array(
                'status' => 'Closed'
              );

            $this->db->UpdateData('insurance_categories', $postData, "`id` = '{$id}'");
            header('Location: ' . URL . 'products/insurancecategories?msg=deleted');
    	}
        


       function SharesList($office){
         return $this->db->SelectData("SELECT * FROM share_products  WHERE office_id = '".$office."'");

       }

       function InsuranceList($office){
         return $this->db->SelectData("SELECT * FROM insurance_products  WHERE office_id = '".$office."'");
       }


       function getshareProduct($id){

        return $this->db->SelectData("SELECT * FROM share_products where id='".$id."'");
        
      }

      function getInsuranceProduct($id){

        return $this->db->SelectData("SELECT * FROM insurance_products where id='".$id."'");
      }


      function repaymentdate($repayment_period,$date,$grace_period_value){


       if ($repayment_period == "days") {


        $date = date("Y-m-d", strtotime($date . " +" . number_format ($grace_period_value) . " day"));
        
      } else if ($repayment_period == "weeks") {

       $date = date("Y-m-d", strtotime($date . " +" . $grace_period_value . " week"));

     } else if ($repayment_period == "months") {
       $date = date("Y-m-d", strtotime($date . " +" . $grace_period_value . " month"));

     } else if ($repayment_period == "years") {

      $date = date("Y-m-d", strtotime($date . " +" . $grace_period_value*12 . " month"));

    }
    return $date ;
  }
  function loanProductCollateralview($id) {


   $query= $this->db->SelectData("SELECT * FROM loan_product_collateral where loan_product_id='".$id."'");



   if(count($query)>0){
    $rset=array();
    foreach ($query as $key => $value) {
      array_push($rset,array(
        'id'=>$query[$key ]['collateral_id'],

        'name'=>$query[$key]['collateral_name'],

      ));

    }
    
  }

  print_r(json_encode(array("result" =>$rset)));
  die();


}


function customersupportshedule($id,$p,$np,$d1) {

     //$dt = $d1.'/'.$d2.'/'.$d3; 06/14/2016 2016-06-14
     //$date = "1998-08-14";
    //$date = date ( 'Y-m-j' , strtotime($date));
 $dt = $d1;
 $date = date("Y-m-d", strtotime($dt));

 $office=$_SESSION['office'];
       //$date = date('Y-m-d');
 $principle  = 0;

        //$id = $data['product_id'];
 $product_loan_details = $this->db->SelectData("SELECT * FROM m_product_loan  where id='" . $id . "'  
  AND office_id = '".$office."' order by id ");
         //$principal  = $data['principal'];
        // $number_of_repayments  = $data['number_of_installments'];
 $principal  = $p;
 $number_of_repayments  = $np;
 $annual_interest_percent = $product_loan_details[0]['nominal_interest_rate_per_period'];
 $days_in_year = $product_loan_details[0]['days_in_year'];
 $installment_option = $product_loan_details[0]['installment_option'];
 $interest_method = $product_loan_details[0]['interest_method'];
 $min_duration = $product_loan_details[0]['min_duration'];
 $min_duration_value = $product_loan_details[0]['min_duration_value'];
 $max_duration = $product_loan_details[0]['max_duration'];
 $max_duration_value = $product_loan_details[0]['max_duration_value'];
 $grace_period = $product_loan_details[0]['grace_period'];
 $grace_period_value = $product_loan_details[0]['grace_period_value'];

         //$date = date("Y-m-d", strtotime($data['repaymentsStartingFromDate']));
 $date = date("Y-m-d", strtotime($dt));




        if ($installment_option == "One Installment")  {// Equal installment declining balance 




        }else{


          if($interest_method == "Declining Balance"){



            $no_date = 1;

            $year_term = 0;
            $down_percent = 0;
            $this_year_interest_paid = 0;
            $this_year_principal_paid = 0;
            $year_term = 1;






            $loanTermFrequencyType = $duration;




            $period = $this->period($loanTermFrequencyType);





            $step = 1;
            $month_term = $number_of_repayments;

            $annual_interest_rate = $annual_interest_percent / 100;
            $monthly_interest_rate = $annual_interest_rate * $period ;


            $loan_duration_value = $duration_value;
            $no_duration  =  $loan_duration_value/$number_of_repayments;


            $current_month = 1;
            $current_no = 0;

            $current_year = 1;


            $power = -($number_of_repayments);

            $denom = pow((1 + $monthly_interest_rate), $power);

            $monthly_payment = $principal * ($monthly_interest_rate / (1 - $denom));

            $monthly_payment_installment = number_format($monthly_payment, 2, ".", "");



            $total_principle = 0;

            $total_interest = 0;
            $total_charge = 0;






            $date = $this-> repaymentdate($grace_period,$date,$grace_period_value);



            while ($current_month <= $month_term) {

              $interest_paid = $principal * $monthly_interest_rate;

              $principal_paid = $monthly_payment - $interest_paid;

              $remaining_balance = $principal - $principal_paid;

                /*


                  $newdate = strtotime ( '+'.$no_date.'  year' , strtotime ( $newdate ) ) ;


                } */


                $from = $date;

                if ($loanTermFrequencyType == "days") {

                  $date = date("Y-m-d", strtotime($date . " +".number_format ($no_duration)." day"));
                } else if ($loanTermFrequencyType == "weeks") {

                  if(is_float($no_duration)){
                    $repay_every = number_format (7*$no_duration);
                    $date = date("Y-m-d", strtotime($date. " +".$repay_every." day"));
                  }else{
                    $date = date("Y-m-d", strtotime($date . " +".number_format ($no_duration)." week"));    
                    
                  }


                } else if ($loanTermFrequencyType =="months") {


                  if(is_float($no_duration)){
                    $repay_every = number_format (($days_in_year/12)*$no_duration);
                    $date = date("Y-m-d", strtotime($date. " +".$repay_every." day"));
                  }else{
                    $date = date("Y-m-d", strtotime($date . " +".number_format ($no_duration)." month"));   
                    
                  }
                } else if ($loanTermFrequencyType == "years") {

                  if(is_float($no_duration)){
                    $repay_every = number_format ($days_in_year*$no_duration);
                    $date = date("Y-m-d", strtotime($date. " +".$repay_every." day"));
                  }else{
                    $date = date("Y-m-d", strtotime($date . " +".number_format ($no_duration*12)." month"));    
                    
                  }
                }

                $date = date("Y-m-d", strtotime($date));

                $to = $date;

                $this_year_interest_paid = $this_year_interest_paid + $interest_paid;

                $this_year_principal_paid = $this_year_principal_paid + $principal_paid;

                $princial_per = number_format($principal_paid, 2, ".", "");

                $interest_per = number_format($interest_paid, 2, ".", "");

                $total_principle = $total_principle + $princial_per;

                $total_interest = $total_interest + $interest_per;

                $total_charge = $total_charge + 0;



                $rset[$current_no]['loan_id'] = $loan_id;
                $rset[$current_no]['installment'] = $monthly_payment_installment;
                $rset[$current_no]['fromdate'] = $from;
                $rset[$current_no]['duedate'] = $to;
                $rset[$current_no]['principal_amount'] = $princial_per;
                $rset[$current_no]['interest_amount'] = $interest_per;
                $rset[$current_no]['fee_charges_amount'] = 0;
                $rset[$current_no]['principal_completed'] = $total_principle;
                $rset[$current_no]['interest_completed'] = $total_interest;
                $rset[$current_no]['fee_charges_completed'] = $total_charge;
                $rset[$current_no]['createdby_id'] = $loan_details[0]['created_by'];
                $rset[$current_no]['created_date'] = $loan_details[0]['submittedon_date'];

                ($current_month % 12) ? $show_legend = FALSE : $show_legend = TRUE;

                if ($show_legend) {

                  $current_year++;

                  $this_year_interest_paid = 0;

                  $this_year_principal_paid = 0;

                  if (($current_month + 6) < $month_term) {

                       // echo $legend;
                  }
                }

                $principal = $remaining_balance;

                $current_month++;
                $current_no++;
              }

              print_r(json_encode($rset));
              die();

            }   else {









              $loanTermFrequencyType =  $duration;

              $period = $this->period($loanTermFrequencyType);


              $no_duration  =  $duration_value/$number_of_repayments;


              $interest = $principal * $number_of_repayments * $period * ($annual_interest_percent / 100);

              $Principal_amount = ($interest + $principal);

              $monthly_pay = $Principal_amount / $number_of_repayments;

              $monthly_interest = $interest / $number_of_repayments;

              $monthly_Principal = $principal / $number_of_repayments;
              $daycount = 0;





        //print_r(date("Y-m-d", strtotime($date)));
              $date = $this->repaymentdate($grace_period,$date,$grace_period_value);



              $total_principle = $monthly_Principal;

              $total_interest = $monthly_interest;

              $total_charge = 0;

              for ($i = 0; $i < $number_of_repayments; $i++) {

                $from = $date;

                if ($loanTermFrequencyType == "days") {




                  $date = date("Y-m-d", strtotime($date. " +".number_format ($no_duration)." day"));
                } else if ($loanTermFrequencyType == "weeks") {


                  if(is_float($no_duration)){
                    $repay_every = number_format (7*$no_duration);
                    $date = date("Y-m-d", strtotime($date. " +".$repay_every." day"));
                  }else{
                    $date = date("Y-m-d", strtotime($date . " +".number_format ($no_duration)." week"));    
                    
                  }


                } else if ($loanTermFrequencyType =="months") {

                 if(is_float($no_duration)){
                  $repay_every = number_format (($days_in_year/12)*$no_duration);
                  $date = date("Y-m-d", strtotime($date. " +".$repay_every." day"));
                }else{
                  $date = date("Y-m-d", strtotime($date . " +".number_format ($no_duration)." month"));   

                }


              } else if ($loanTermFrequencyType == "years") {


                if(is_float($no_duration)){
                  $repay_every = number_format ($days_in_year*$no_duration);
                  $date = date("Y-m-d", strtotime($date. " +".$repay_every." day"));
                }else{
                  $date = date("Y-m-d", strtotime($date . " +".number_format ($no_duration*12)." month"));    

                }
              }

              $date = date("Y-m-d", strtotime($date));

              $to = $date;






              $rset[$i]['loan_id'] = null;
              $rset[$i]['installment'] = $monthly_pay;
              $rset[$i]['fromdate'] = $from;
              $rset[$i]['duedate'] = $to;
              $rset[$i]['principal_amount'] = $monthly_Principal;
              $rset[$i]['interest_amount'] = $monthly_interest;
              $rset[$i]['fee_charges_amount'] = 0;
              $rset[$i]['principal_completed'] = $total_principle;
              $rset[$i]['interest_completed'] = $total_interest;
              $rset[$i]['fee_charges_completed'] = $total_charge;


              $total_principle = $total_principle + $monthly_Principal;

              $total_interest = $total_interest + $monthly_interest;


            }

            print_r(json_encode($rset));
            die();

          }

        }
      }

      function creategloangls() {
        $data = $_POST;
        $office =$_SESSION['office'];
        $postData = array(
         'sacco_id' =>$office,
         'pointer_name' => $data['pname'],
         'description' => $data['description'],
         'product_id' => $data['pnumber'],
		 'product_type_id' => 2,
         'transaction_name' => $data['chargecalculation'],
         'transaction_type_id' => $data['transaction_type'],
         'transaction_mode' => $data['transaction_mode'],
         'debit_account' => $data['source'],
         'credit_account' => $data['destination'],
       );

        $this->db->InsertData('acc_gl_pointers', $postData);
        $dataPost = array(         
          'status' =>'open'
        );
        $this->db->UpdateData('m_product_loan', $dataPost,"`id` = '{$data['pnumber']}'");
        header('Location: ' . URL . 'products/addglpointersloan/'.$data['pnumber']);
      }

      function createglequity() {
        $data = $_POST;
        $office =$_SESSION['office'];
        $postData = array(
         'sacco_id' =>$office,
         'pointer_name' => $data['pname'],
         'description' => $data['description'],
         'product_type_id' => 1,
		 'product_id' => $data['pnumber'],
         'transaction_type_id' => $data['transaction_type'],
         'transaction_mode' => $data['transaction_mode'],
         'debit_account' => $data['source'],
         'credit_account' => $data['destination'],
       );

        $this->db->InsertData('acc_gl_pointers', $postData);

        $postData = array(         
          'product_status' =>'Active'
        );
        $this->db->UpdateData('share_products', $postData,"`id` = '{$data['pnumber']}'");

        header('Location: ' . URL . 'products/addglpointersequity/'.$data['pnumber']);
      }

      function createglinsurance() {
        $data = $_POST;
        $office =$_SESSION['office'];
        
        $postData = array(
            'sacco_id' =>$office,
            'pointer_name' => $data['pname'],
            'description' => $data['description'],
            'product_type_id' => 8,
            'product_id' => $data['pnumber'],
            'transaction_type_id' => $data['transaction_type'],
            'transaction_mode' => $data['transaction_mode'],
            'debit_account' => $data['source'],
            'credit_account' => $data['destination'],
        );

        $this->db->InsertData('acc_gl_pointers', $postData);

        $postData = array(         
          'product_status' =>'Active'
        );
        $this->db->UpdateData('insurance_products', $postData,"`id` = '{$data['pnumber']}'");

        header('Location: ' . URL . 'products/addglpointersinsurance/'.$data['pnumber']);
      }

      function createglsaving() {
        $data = $_POST;
        $office =$_SESSION['office'];
        $postData = array(
         'sacco_id' =>$office,
         'pointer_name' => $data['pname'],
         'description' => $data['description'],
         'product_id' => $data['pnumber'],
		'product_type_id' => 3,
		'transaction_type_id' => $data['transaction_type'],
         'transaction_mode' => $data['transaction_mode'],
         'debit_account' => $data['source'],
         'credit_account' => $data['destination'],
       );

        $this->db->InsertData('acc_gl_pointers', $postData);
        $NewData = array(         
          'product_status' =>'Active'
        );
        $this->db->UpdateData('m_savings_product', $NewData,"`id` = '{$data['pnumber']}'");
        header('Location: ' . URL . 'products/addsavingsglpointers/'.$data['pnumber']);
      }


      function createglother() {    
         $data = $_POST;

        $office =$_SESSION['office'];
        $postData = array(
         'sacco_id' =>$office,
         'pointer_name' => $data['pname'],
         'description' => $data['description'],
         'product_id' => $data['pnumber'],
         'transaction_type_id' => $data['transaction_type'],
         'transaction_mode' => 0,
         'product_type_id' => $data['product_type'],
         'debit_account' => $data['source'],
         'credit_account' => $data['destination'],
        );

        $this->db->InsertData('acc_gl_pointers', $postData);
        $NewData = array(         
          'status' =>'Active'
        );
        $this->db->UpdateData('m_charge', $NewData,"`id` = '{$data['pnumber']}'");
        return true;
        header('Location: ' . URL . 'products/addglpointerswallet/'.$data['pnumber']);
      }

      function createglwallet() {
        $data = $_POST;
        $office =$_SESSION['office'];
        $postData = array(
         'sacco_id' =>$office,
         'pointer_name' => $data['pname'],
         'description' => $data['description'],
         'product_type_id' => 5,
		 'product_id' => $data['pnumber'],
         'transaction_type_id' => $data['transaction_type'],
         'transaction_mode' => $data['transaction_mode'],
         'debit_account' => $data['source'],
         'credit_account' => $data['destination'],
       );

        $this->db->InsertData('acc_gl_pointers', $postData);
        header('Location: ' . URL . 'products/addglpointerswallet/'.$data['pnumber']);
      }


      function createTimeGl() {
        $data = $_POST;
        $office =$_SESSION['office'];
        $postData = array(
         'sacco_id' =>$office,
         'pointer_name' => $data['pname'],
         'description' => $data['description'],
         'product_id' => $data['pnumber'],
          'product_type_id' => 4,
         'transaction_type_id' => $data['transaction_type'],
         'transaction_mode' => $data['transaction_mode'],
         'debit_account' => $data['source'],
         'credit_account' => $data['destination'],
       );

        $this->db->InsertData('acc_gl_pointers', $postData);
        $NewData = array(         
          'product_status' =>'Active'
        );
        $this->db->UpdateData('fixed_deposit_product', $NewData,"`id` = '{$data['pnumber']}'");
        header('Location: ' . URL . 'products/addglpointerstime/'.$data['pnumber']);
      }

      function loanProvision($office){
       return $this->db->SelectData("SELECT * FROM m_loan_ageing WHERE office_id = $office order by id");
     }   

     function createNewLoanAgeing(){
       $data =  $_POST;

       $postData = array(
        'office_id' => $_SESSION['office'],
        'description' => $data['description'],
        'days_from' => $data['days_from'],
        'days_to' => $data['days_to'],
        'provision' => $data['provision'],
      );

       $results =$this->db->InsertData('m_loan_ageing', $postData);
       if(!empty($results)){
        $status = 'Successfully Created'; 

      }else{
        $status = 'Not Successfully Created'; 
      }

      header('Location: ' . URL . 'products/loanprovision?msg='.$status.''); 

    }


    function updateloanageing(){
     $data =  $_POST;
     $id = $data['id'];     
     $postData = array(         
      'description' =>$data['description'],
      'days_from' =>$data['days_from'],
      'days_to' =>$data['days_to'],
      'provision' =>$data['provision'],
    );

     $this->db->UpdateData('m_loan_ageing', $postData,"`id` = '{$id}'");
     $status = 'update successfully';
     header('Location: ' . URL . 'products/loanProvision?msg='.$status.''); 

   }

   function getLonAgeingDetails($id){
    return $this->db->SelectData("SELECT * FROM m_loan_ageing where id ='".$id."'  order by id  ");

  }

  function createNewthirdPartyProduct() {


    $data = $_POST;
    $accounting_type =null;
    $result =null;
    $account_no=$this->getThirdPartyNo($_SESSION['office']);
    $office=$_SESSION['office'];
    $postData = array(
     'office_id' =>$office,
     'thirdparty_accountno' =>$account_no,
     'name' => $data['pname'],
     'description' => $data['description'],
     'product_type' =>7,
     'incoming_charge' => $data['incoming'],    
     'outgoing_charge' => $data['outgoing'],
     'created_by' => $_SESSION['user_id'],

   );
    $result = $this->db->InsertData('thirdparty_products', $postData);

    //header('Location: ' . URL . 'products/thirdpartyProduct/'.$result);
    header('Location: ' . URL . 'products/addthirdpartyglpointer/'.$result."?msg=success");
    
  }

  function getThirdPartyProducts() {
$office=$_SESSION['office'];
$this->importThirdpartyProducts(FALSE);
return $this->db->SelectData("SELECT * FROM thirdparty_products where office_id = '". $office."'  ");
  }

  function GetThirdPartyProductID($id) {
   $office=$_SESSION['office'];
   return $this->db->SelectData("SELECT * FROM thirdparty_products where office_id = '". $office."' AND product_id='".$id."' ");
 }


 function getThirdPartySAProducts() {
   return $this->db->SelectData("SELECT * FROM thirdparty_products AS a JOIN m_branch AS b ON a.office_id = b.id");
 }



 function getClicThirdPartyProducts() {

  $tpp = $this->getThirdPartyProducts();

  $available = array();
  $nonAvailable = array();


  foreach ($tpp as $key => $value) {
    $available[$key]['product_id'] = $value['product_id'];
    $available[$key]['product_name'] = $value['name'];
  }

  $results =  $this->db->SelectData("SELECT * FROM system_settings WHERE status ='Active'");

  $response = $this->SendThidpartyRequest($results[0]['instance_id']);

  $res = json_decode($response, 1);

  foreach ($res as $key => $value) {
    $nonAvailable[$key]['product_id'] = $value['product_id'];
    $nonAvailable[$key]['product_name'] = $value['product_name'];
  }

  print_r($available);
  echo "</br>";
  print_r($nonAvailable);
  echo "</br>";
  //$diff = array_diff($available, $nonAvailable);

  //print_r($diff);
  die();

  return ;
}


function getthirdpartyproduct($id) {
  $result = $this->db->SelectData("SELECT * FROM thirdparty_products where id='" . $id . "'  ");

  return $result;
} 

function createglthirdparty() {
  
  $data = $_POST;
  $office=$_SESSION['office'];
  $postData = array(
   'sacco_id' => $office,
   'pointer_name' => $data['pname'],
   'description' => $data['description'],
   'product_id' => $data['pnumber'],
   'transaction_name' => $data['pname'],
   'transaction_type_id' => $data['transaction_type'],
   'transaction_mode' => $data['transaction_mode'],
   'debit_account' => $data['source'],
   'credit_account' => $data['destination'],
 );

  $pid = $data['pnumber'];
  $this->db->InsertData('acc_gl_pointers', $postData);
  $data = array(         
    'product_status' =>'Active'
  );
  $this->db->UpdateData('thirdparty_products', $data,"`id` = '{$pid}'");
  header('Location: ' . URL . 'products/addthirdpartyglpointer/'.$pid.'?msg=added');
}


function getAccountName($id){

  $result= $this->db->selectData("SELECT name FROM thirdparty_products WHERE thirdparty_accountno='".$id."' and product_status='Active'");

  if(count($result)>0){
    echo $result[0]['name'];
    die();

  }
}

function getAccountBalance($id){

  $result= $this->db->selectData("SELECT running_balance FROM thirdparty_products WHERE thirdparty_accountno='".$id."' and product_status='Active'");

  if(count($result)>0){
    return $result[0]['running_balance'];        
  } else {
    return 0;         
  }
}

function getAccountDetails($id){

  $result= $this->db->selectData("SELECT * FROM thirdparty_products WHERE thirdparty_accountno='".$id."'");

  if(count($result)>0){
    return $result[0];        
  } else {
    return "";         
  }
}

function getAccountId($id){

  $result= $this->db->selectData("SELECT id FROM thirdparty_products WHERE thirdparty_accountno='".$id."' and product_status='Active'");

  if(count($result)>0){
    return $result[0]['id'];        
  } else {
    return 0;         
  }
}

function thirdpartydeposit($data){

  $acc_details = $this->getAccountDetails($data['accountno']);
  $prodType = 7;
  $thirdparty_mapping = $this->GetGLSAPointers($acc_details['id'],$prodType,'Deposit To Thirdparty Account');

  if (empty($thirdparty_mapping)) {
    header('Location: ' . URL . 'products/thirdpartydeposit?msg=thpmap'); 
    die();
  }
  $this->db->beginTransaction();

  $running_balance = $this->getAccountBalance($data['accountno']);

  try{
    $amount = str_replace(",","",$data['amount']);
    $new_runnning_balance = $running_balance + $amount;
    $acc = $data['accountno'];
    $transaction_postData = array(
      'thirdparty_account_no' => $acc,
      'transaction_type' =>  'Deposit',
      'transaction_date' =>  date('Y-m-d H:i:s'),
      'amount' => $amount,
      'amount_in_words' => $data['amount_in_words'],
      'depositor_name' => $data['depositor'],
            //'member_account_no' => $data['tel'],
      'running_balance' => $new_runnning_balance,
      'branch' =>$_SESSION['office'],
      'transaction_status' => 'Pending',
      'approved_by' => $_SESSION['user_id'],
      'reference_no' => $data['reference_no'],
      'transaction_reversed' => 'No',
      'reversed_by' => NULL,

    );

    $deposit_transaction_id = $this->db->InsertData('thirdparty_account_transactions', $transaction_postData);

    $depositstatus = array(
      'running_balance' => $new_runnning_balance,
    );

    $id = $this->getAccountId($acc);
    $this->db->UpdateData('thirdparty_products', $depositstatus,"`id` = '{$id}'");

        ///make Journal Entry

    $thirdparty_mapping = $this->GetGLSAPointers($acc_details['id'],$prodType,'Deposit To Thirdparty Account');
    $debit_id = $thirdparty_mapping[0]["debit_account"];  
    $credit_id = $thirdparty_mapping[0]["credit_account"];   

    $transaction_id = "TP". $deposit_transaction_id . uniqid();

    $dataDR['account_id'] = $debit_id;
    $dataDR['office_id'] = $acc_details['office_id'];
    $dataDR['branch_id'] = $_SESSION['branchid'];
    $dataDR['transaction_id'] = $transaction_id;
    $dataDR['manual_entry'] = 'No';   
    $dataDR['amount'] = $amount;
    $dataDR['transaction_type'] = "DR";
    $dataDR['description'] = "Deposit";
    $dataDR['trial_balance_side'] = $this->getAccountSide($debit_id);
    $dataDR['createdby_id'] = $_SESSION['user_id'];

    $this->db->InsertData('acc_gl_journal_entry', $dataDR);

    $dataCR['account_id'] = $credit_id;
    $dataCR['office_id'] = $acc_details['office_id'];
    $dataCR['branch_id'] = $_SESSION['branchid'];
    $dataCR['transaction_id'] = $transaction_id;
    $dataCR['manual_entry'] = 'No';   
    $dataCR['amount'] = $amount;
    $dataCR['transaction_type'] = "CR";
    $dataCR['description'] = "Deposit";
    $dataCR['trial_balance_side'] = $this->getAccountSide($credit_id);
    $dataCR['createdby_id'] = $_SESSION['user_id'];

    $this->db->InsertData('acc_gl_journal_entry', $dataCR);

    $this->db->commit();

    header('Location:'.URL.'products/thirdpartyproducts?msg=success');

  }catch(Exception $e){
    $this->db->rollBack();
    $error=$e->getMessage();
    header('Location:'.URL.'products/deposit?msg=fail&error='.$error);
    exit();       
  }  

}


}