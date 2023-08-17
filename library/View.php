<?php
//error_reporting(0);
class View{

    public function __construct(){
        $this->db = new Database();
    }

    public function render_home($dispName){ //render
        $this->display_menus = $this->BuildMenus();
        $this->display_page_menus = $this->getAllowedPageMenus();
        $this->permission = $this->checkAllAcounts();
        $this->transaction_status = $this->checkTransactionStatus();
        $this->canTransact = $this->checkIfCanTransact();
        $this->saccoIsOk = $this->CheckSaccoHealth();

        $this->glcomparison = $this->getGLComparisonHealth();
        $this->cgap = $this->getCGAPHealth();

        //$this->adverts = $this->getAllSaccoAdverts(); 

        require 'views/header_home.php';
        require 'views/'.$dispName.'.php';
        require 'views/footer.php';
    }

    public function render($dispName){ //render
        $this->display_menus = $this->BuildMenus();
        $this->display_page_menus = $this->getAllowedPageMenus();
        $this->permission = $this->checkAllAcounts(); 
        $this->transaction_status = $this->checkTransactionStatus();
        $this->canTransact = $this->checkIfCanTransact();
        $this->saccoIsOk = $this->CheckSaccoHealth();

        $this->glcomparison = $this->getGLComparisonHealth();
        $this->cgap = $this->getCGAPHealth();

        require 'views/header.php';
        require 'views/'.$dispName.'.php';
        require 'views/footer.php';
    }

    public function renders($dispName){ //render
        require 'views/'.$dispName.'.php';    
    }

    public function BuildMenus() {
        $user_id = Session::get('access_level');  
        $office_id = Session::get('office');
        $allowed_rights = $this->db->SelectData("SELECT allowed_access FROM sch_user_levels WHERE access_denotor=:id AND office_id = '$office_id'", array('id' => $user_id));
        if(count($allowed_rights)>0){
            foreach ($allowed_rights as $value) {
                $menu_set = explode(',', $value['allowed_access']);
                $topmenus = $this->TopMenus($menu_set);
            }
            return $topmenus;
        }
    }

    function TopMenus($menu_set) {
        foreach ($menu_set as $key => $value) {
        $topmenus = $this->db->SelectData("SELECT * FROM sch_access_rights WHERE parent_option =0 AND on_menu='Yes' AND id=:id ORDER BY rank ASC", array('id' => $value));          
            foreach ($topmenus as $key => $tmenu) {
                $submenus = $this->SubMenus($tmenu['id'], $menu_set);
                $menulist[$tmenu['id']]['Title'] = $tmenu['menu_title'];
                $menulist[$tmenu['id']]['CSS'] = $tmenu['css'];
                $menulist[$tmenu['id']]['Submenus'] = $submenus;
            }
        }
        return $menulist;
    }

    function SubMenus($id, $menu_set) {
        
        $submenulist = $this->db->SelectData("SELECT * FROM sch_access_rights WHERE parent_option = :parent_option AND on_menu=:yes ORDER BY rank ASC", array('parent_option' => $id, 'yes' =>'Yes'));
        if(count($submenulist)>0){
            foreach ($submenulist as $key => $value) {
                if (in_array($value['id'], $menu_set)) {
                    $submenus[$key]['Submenus'] = $value;
                } else {
                    $submenus[$key]['Submenus'] = NULL;
                }
            }
            return $submenus;
        }
    }

    function AccessRights($u_role) {
        $allowed_rights = $this->db->SelectData("SELECT allowed_access FROM sch_user_levels WHERE access_level=:id", array('id' => $u_role));      
        foreach ($allowed_rights as $value) {
            $menu_set = explode(',', $value['allowed_access']);
            $aclist = $this->ACList($menu_set);
        }

        return $aclist;
    }

    function ACList($ml) {
        foreach ($ml as $key => $value) 
       $aclist = $this->db->SelectData("SELECT * FROM sch_access_rights WHERE id=:id", array('id' => $value));

            foreach ($aclist as $key => $tmenu) {
                $accesslist[$tmenu['id']] = $tmenu['load_page'];
            }
       
        return $accesslist;
    }
    
    function SubSubMenus($id) {
        
      $submenulist = $this->db->SelectData("SELECT * FROM sch_access_rights WHERE parent_option = :parent_option AND on_menu=:yes ORDER BY rank ASC", array('parent_option' => $id, 'yes' =>'Yes'));
       if(count($submenulist)>0){     
          foreach ($submenulist as $key => $value) {
                    $submenus[$key]['Submenus'] = $value;
            }
            return $submenus;
        }
    }
    

    ///////////////////////////////////////////////////STEVEN///////////////////////////////////////////////
    
    function getAllSaccoAdverts(){

        $today = date('Y-m-d');
        $office = $_SESSION['office'];
        $result = $this->db->selectData("SELECT * FROM messages WHERE advert = 'Yes' AND message_date LIKE '$today%' AND office_id = '".$office."' AND status= 'Active'");

        $new_data = array();
        foreach ($result as $key => $value) {
            $new_data[$key]['title'] = ucwords($value['title']);
            $new_data[$key]['details'] = ucfirst($value['details']);
        }

        return $new_data;
    }

    function checkAllAcounts(){

        $level = Session::get('access_level');

        if ($level == "SA") {
            return true;
        } else {
            $accounts = $this->checkAccounts();
            $ledgers = $this->checkLedgerAccounts();
            $pointers = $this->checkPointers();
            $balances = $this->checkBalances();
            $thirdparty = $this->checkThirdpartyProducts();
            $wallets = $this->checkWalletPointers();

            if ($accounts && $ledgers && $pointers && $balances  ) {
                return true;
            } else{
                return false;
            }
        }
    }    

    function checkAccounts(){
        $office=$_SESSION['office'];
        $result=  $this->db->selectData("SELECT * FROM acc_ledger_account where sacco_id='".$office."'");

        if (count($result) <= 0) {
            return false;
        } else {
            return true;
        }
    }

    function checkLedgerAccounts(){
        $office=$_SESSION['office'];
        $result=  $this->db->selectData("SELECT * FROM acc_ledger_account_mapping where office_id='".$office."'");
        if (count($result) >= 5) {
            return true;
        } else {
            return false;
        }
    }

    function checkPointers(){
        $office=$_SESSION['office'];
        $result=  $this->db->selectData("SELECT * FROM acc_accounting_rule where office_id='".$office."' AND status = 1");

        if (count($result) <= 0) {
            //return false;
            return true;
        } else {
            return true;
        }
    }
    
    function checkBalances(){
        $office=$_SESSION['office'];
        $result=  $this->db->selectData("SELECT * FROM acc_gl_journal_entry where transaction_id LIKE 'OP%' AND office_id='".$office."'");

        if (count($result) <= 0) {
            return false;
        } else {
            return true;
        }
    }

    function checkThirdpartyProducts(){
        $office=$_SESSION['office'];
        $result=  $this->db->selectData("SELECT * FROM thirdparty_products WHERE office_id='".$office."'");

        if (count($result) <= 0) {
            return false;
        } else {
            return true;
        }
    }

    function checkWalletPointers(){
        $office=$_SESSION['office'];

        $wallet_transactions = $this->db->selectData("SELECT transaction_type_id FROM transaction_type where product_type=5");

        $ids = array();

        foreach ($wallet_transactions as $key => $value) {
            $result = $this->db->selectData("SELECT * FROM acc_gl_pointers where sacco_id='".$office."' AND transaction_type_id = '".$value['transaction_type_id']."'");
            if (!empty($result)) {
                $ids[$key]['name'] = $result[0]['pointer_id'];
            }
        }

        if ((count($ids) >= count($wallet_transactions)) && count($ids) > 0) {
            return true;
        } else {
            return false;
        }
    }

    function getAllowedPageMenus(){

        $user_id = Session::get('access_level');  
        $office_id = Session::get('office');

        $allowed_menus = $this->db->SelectData("SELECT allowed_access_menu FROM sch_user_levels WHERE access_denotor=:id AND office_id = '$office_id'", array('id' => $user_id));

        if(count($allowed_menus)>0){
            foreach ($allowed_menus as $value) {
                $menu_set = explode(',', $value['allowed_access_menu']);
                $lowermenus = $this->GetMenus($menu_set);
            }
            return $lowermenus;
        } else{
            return '';
        }

    }

    function GetMenus($menuset){
        $menulist = array();
        foreach ($menuset as $key => $value) {     
            $acceptedmenus = $this->db->SelectData("SELECT * FROM sys_menu_links WHERE status='Yes' AND id=:id ORDER BY rank ASC", array('id' => $value));        
            foreach ($acceptedmenus as $key1 => $amenu) {
                $menulist[$key]['id'] = $amenu['id'];
                $menulist[$key]['parent'] = $amenu['parent'];
                $menulist[$key]['title'] = $amenu['menu_name'];
                $menulist[$key]['link'] = $amenu['menu_link'];
                $menulist[$key]['css'] = $amenu['css'];
                $menulist[$key]['rank'] = $amenu['rank'];
            }
        }
        return $menulist;
    }

    function checkTransactionStatus(){

        $id = $_SESSION['user_id'];
        $office =$_SESSION['office'];
        $result = $this->db->selectData("SELECT * FROM m_staff where id='".$id."' AND office_id = '". $office."'");

        if (empty($result)) {
            return true;
        } else {
            if ($result[0]['last_request_date'] != date('Y-m-d') && $result[0]['account_balance'] > 0 && $result[0]['can_transact'] == 'Yes') {
                return true;
            } else if (($result[0]['can_transact'] == 'No' && $result[0]['access_level'] == 'A') || $result[0]['access_level'] == 'SA') {
                return true;
            } else {
                return true;
            }
        }

    }

    function checkIfCanTransact(){

        $id = $_SESSION['user_id'];
        $office =$_SESSION['office'];

        if ($_SESSION['Isheadoffice'] == 'Yes') {
            $result = $this->db->selectData("SELECT * FROM m_staff where can_transact = 'Yes' AND id='".$id."' AND office_id = '". $office."'");
        } else {
            $result = $this->db->selectData("SELECT * FROM m_staff where can_transact = 'Yes' AND id='".$id."' AND office_id = '". $_SESSION['branchid']."'");
        }

        if (count($result) > 0) {
            return true;
        } else {
            return false;
        }

    }

    function CheckSaccoHealth(){

        if ($_SESSION['access_level'] == 'SA') {
            return TRUE;
        } else {            

            $comparionReport = $this->getGLComparisonHealth();
            $cgapReport = $this->getCGAPHealth();

            if ($comparionReport && $cgapReport) {
                //echo "Both True";
                return TRUE;
            } else if (!$comparionReport && $cgapReport) {
                //echo "Comparison";
                return FALSE;
            } else if ($comparionReport && !$cgapReport) {
                //echo "Cgap";
                return FALSE;
            } else {
                //echo "Both False";
                return FALSE;
            }

        }

    }   

    function getGLComparisonHealth(){

        $GL_wallet = $this->getWalletGL();  
        $C_wallet = $this->getWalletBalance();

        $GL_savings = $this->getSavingsGL();    
        $C_savings = $this->getSavingsBalance();

        $GL_loans = $this->getloansGL();    
        $C_loans = $this->getloansBalance();

        $GL_shares = $this->getsharesGL();  
        $C_shares = $this->getsharesBalance();

        $GL_timedeposits = $this->gettimedepositsGL();  
        $C_timedeposits = $this->gettimedepositsBalance();

        if (($GL_wallet['amount'] == $C_wallet) && ($GL_savings['amount'] == $C_savings) && ($GL_loans['amount'] == $C_loans) && ($GL_shares['amount'] == $C_shares) && ($GL_timedeposits['amount'] == $C_timedeposits)) {
                return TRUE;
        } else {
            return FALSE;
        }

    }

    function getCGAPHealth(){

        return TRUE;
    }


    function getWalletGL($sacco = NULL){
        $amount = $code = 0;
        $data = array();
        if ($sacco == NULL) {
            $office=$_SESSION['office']; 
        } else {
            $office=$sacco;
        }

        $acc = $this->db->SelectData("SELECT account_id from acc_ledger_account_mapping where office_id = $office and product_id = 5");
        if (!empty($acc)) {
            $acc_id = $acc[0]['account_id'];
            $query =   $this->db->SelectData("SELECT * FROM acc_gl_journal_entry where account_id = $acc_id");
            $query2 =   $this->db->SelectData("SELECT * FROM acc_ledger_account where id = $acc_id");
            foreach($query as $key => $value){
                if($value['transaction_type'] == 'CR'){
                    $amount = $amount + $value['amount'];
                }else{
                    $amount = $amount - $value['amount'];
                }
            }

            $data['amount'] = $amount;
            $data['gl_code'] = $query2[0]['gl_code'];
        } else {
            $data['amount'] = $amount;
            $data['gl_code'] = $code;
        }
        return $data;     
    }

    function getWalletBalance($sacco = NULL){
        if ($sacco == NULL) {
            $office=$_SESSION['office']; 
        } else {
            $office=$sacco;
        }
        
        $query =   $this->db->SelectData("SELECT sum(wallet_balance) as wallet_balance FROM sm_mobile_wallet where bank_no = $office");

        if (empty($query)) {
            return 0;
        } else {
            return $query[0]['wallet_balance'];
        }
    }

    function getSavingsGL($sacco = NULL){
        $amount = $code =0;
        if ($sacco == NULL) {
            $office=$_SESSION['office']; 
        } else {
            $office=$sacco;
        } 
        $acc = $this->db->SelectData("SELECT account_id from acc_ledger_account_mapping where office_id = $office and product_id = 3");
        if (!empty($acc)) {
            $acc_id = $acc[0]['account_id']; 
            $query =   $this->db->SelectData("SELECT * FROM acc_gl_journal_entry where account_id = $acc_id");
            $query2 =   $this->db->SelectData("SELECT * FROM acc_ledger_account where id = $acc_id");
            foreach($query as $key => $value){
                if($value['transaction_type'] == 'CR'){
                    $amount = $amount + $value['amount'];
                }else{
                    $amount = $amount - $value['amount'];
                }
            }
            $data['amount'] = $amount;
            $data['gl_code'] = $query2[0]['gl_code'];
        } else {
            $data['amount'] = $amount;
            $data['gl_code'] = $code;
        }
        return $data;     
    }

    function getSavingsBalance($sacco = NULL){
        if ($sacco == NULL) {
            $office=$_SESSION['office']; 
        } else {
            $office=$sacco;
        }
        $query =   $this->db->SelectData("SELECT sum(running_balance) as account_balance FROM m_savings_account where office_id = $office");

        return $query[0]['account_balance'];
    }

    function getloansGL($sacco = NULL){
        $amount = $code = 0;
        if ($sacco == NULL) {
            $office=$_SESSION['office']; 
        } else {
            $office=$sacco;
        }  
        $acc = $this->db->SelectData("SELECT account_id from acc_ledger_account_mapping where office_id = $office and product_id = 2");
        if (!empty($acc)) {
            $acc_id = $acc[0]['account_id']; 
            $query =   $this->db->SelectData("SELECT * FROM acc_gl_journal_entry where account_id = $acc_id");
            $query2 =   $this->db->SelectData("SELECT * FROM acc_ledger_account where id = $acc_id");
            foreach($query as $key => $value){
                if($value['transaction_type'] == 'DR'){
                    $amount = $amount + $value['amount'];
                }else{
                    $amount = $amount - $value['amount'];
                }
            }
            $data['amount'] = $amount;
            $data['gl_code'] = $query2[0]['gl_code'];
        } else {
            $data['amount'] = $amount;
            $data['gl_code'] = $code;
        }
        return $data;     
    }

    function getloansBalance($sacco = NULL){
        if ($sacco == NULL) {
            $office=$_SESSION['office']; 
        } else {
            $office=$sacco;
        } 
        $query =   $this->db->SelectData("SELECT sum(total_outstanding) as account_balance FROM m_loan where sacco_id = $office");
        return $query[0]['account_balance'];
    }

    function getsharesGL($sacco = NULL){
        $amount = $code = 0;
        if ($sacco == NULL) {
            $office=$_SESSION['office']; 
        } else {
            $office=$sacco;
        }  
        $acc = $this->db->SelectData("SELECT account_id from acc_ledger_account_mapping where office_id = $office and product_id = 1");
        if (!empty($acc)) {
            $acc_id = $acc[0]['account_id']; 
            $query =   $this->db->SelectData("SELECT * FROM acc_gl_journal_entry where account_id = $acc_id");
            $query2 =   $this->db->SelectData("SELECT * FROM acc_ledger_account where id = $acc_id");
            foreach($query as $key => $value){
                if($value['transaction_type'] == 'CR'){
                    $amount = $amount + $value['amount'];
                }else{
                    $amount = $amount - $value['amount'];
                }
            }
            $data['amount'] =  $amount;
            $data['gl_code'] = $query2[0]['gl_code'];
        } else {
            $data['amount'] =  $amount;
            $data['gl_code'] = $code;           
        }
        return $data;     
    }

    function getsharesBalance($sacco = NULL){
        if ($sacco == NULL) {
            $office=$_SESSION['office']; 
        } else {
            $office=$sacco;
        } 
        $query =   $this->db->SelectData("SELECT sum(running_balance) as account_balance FROM share_account WHERE office_id = $office");

        return $query[0]['account_balance'];
    }

    function gettimedepositsGL($sacco = NULL){
        $amount = $code = 0;
        if ($sacco == NULL) {
            $office=$_SESSION['office']; 
        } else {
            $office=$sacco;
        }  
        $acc = $this->db->SelectData("SELECT account_id from acc_ledger_account_mapping where office_id = $office and product_id = 4");
        if (!empty($acc)) {
            $acc_id = $acc[0]['account_id']; 
            $query =   $this->db->SelectData("SELECT * FROM acc_gl_journal_entry where account_id = $acc_id");
            $query2 =   $this->db->SelectData("SELECT * FROM acc_ledger_account where id = $acc_id");
            foreach($query as $key => $value){
                if($value['transaction_type'] == 'CR'){
                    $amount = $amount + $value['amount'];
                }else{
                    $amount = $amount - $value['amount'];
                }
            }
            $data['amount'] = $amount;
            $data['gl_code'] = $query2[0]['gl_code'];
        } else {
            $data['amount'] = $amount;
            $data['gl_code'] = $code;
        }
        return $data;     
    }

    function gettimedepositsBalance($sacco = NULL){
        if ($sacco == NULL) {
            $office=$_SESSION['office']; 
        } else {
            $office=$sacco;
        } 
        $query =   $this->db->SelectData("SELECT sum(running_balance) as account_balance FROM fixed_deposit_account WHERE office_id = $office");

        return $query[0]['account_balance'];
    }
    
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
}
