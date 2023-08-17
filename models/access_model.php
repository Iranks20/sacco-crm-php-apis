<?php

//error_reporting(0);
   
class Access_model extends Model{
	
	public function __construct(){
		parent::__construct();
    	$this->logUserActivity(NULL);
    	
    	if (!$this->checkTransactionStatus()) {
    		header('Location: ' . URL); 
    	}
	}


	function getAccessRights(){
		 $result =  $this->db->SelectData("SELECT * FROM sch_access_rights WHERE on_menu = 'Yes' AND parent_option = 0");

		 $heads = array();

		 foreach ($result as $key => $value) {
		 	$heads[$key]['id'] = $value['id'];
		 	$heads[$key]['menu_title'] = $value['menu_title'];
		 	$heads[$key]['children'] = $this->getMenuChildren($value['id']);
		 	$heads[$key]['barchildren'] = $this->getPageMenuChildren($value['menu_title']);
		 }

	     return $heads;
	}

	function getMenuChildren($id){
		$result =  $this->db->SelectData("SELECT * FROM sch_access_rights WHERE on_menu = 'Yes' AND parent_option = '".$id."'");

		$new_results = array();
		foreach ($result as $key => $value) {
			if ($value['menu_type'] == 'multilevel') {
		 		$new_results[$key]['grandchildren'] = $this->getMenuGrandChildren($value['id']);
			} else {
				$new_results[$key]['grandchildren'] = '';
			}

		 	$new_results[$key]['barmenuchildren'] = $this->getPageMenuChildren($value['menu_title']);
			$new_results[$key]['id'] = $value['id'];
			$new_results[$key]['menu_title'] = $value['menu_title'];
		}

		return $new_results;
	}

	function getMenuGrandChildren($id){
		$result =  $this->db->SelectData("SELECT id, menu_title FROM sch_access_rights WHERE on_menu = 'Yes' AND parent_option = '".$id."'");

		$new_grands = array();
		foreach ($result as $key => $value) {
			$new_grands[$key]['id'] = $value['id'];
			$new_grands[$key]['menu_title'] = $value['menu_title'];
			$new_grands[$key]['great_grand_children'] = $this->getPageMenuChildren($value['menu_title']);
		}

		return $new_grands;
	}

	function getPageAccessRights(){
		 $result =  $this->db->SelectData("SELECT DISTINCT parent FROM sys_menu_links WHERE status = 'Yes'");

		 $heads = array();

		 foreach ($result as $key => $value) {
		 	$heads[$key]['id'] = 0;
		 	$heads[$key]['menu_name'] = $value['parent'];
		 	$heads[$key]['children'] = $this->getPageMenuChildren($value['parent']);
		 }

	     return $heads;

	}

	function getPageMenuChildren($id){
		 $result =  $this->db->SelectData("SELECT id, menu_name FROM sys_menu_links WHERE status = 'Yes' AND parent = '".$id."'");
	     return $result;
	}

	function getAccessLevels(){
		 $office_id=$_SESSION['office'];
		 $result =  $this->db->SelectData("SELECT * FROM sch_user_levels WHERE status = 'Active' AND office_id = '".$office_id."'");
	     return $result;
	}

	function getAccessLevel($id){
		 $result =  $this->db->SelectData("SELECT * FROM sch_user_levels  where access_denotor='".$id."' ");
	     return $result;
	}

	function getAccessDetails($id){	
		$result =  $this->db->SelectData("SELECT * FROM sch_user_levels INNER JOIN sch_access_rights ON allowed_access = id WHERE level_id='".$id."'");
	     return $result;
	}

	function getName($id){
		$result = $this->db->SelectData("SELECT menu_title FROM sch_access_rights  where id='".$id."' ");
		return $result;
	}

	function InsertPermission($data){
		$this->db->InsertData('sch_user_levels', $data);
  		header('Location: ' . URL . 'access?msg=success'); 
	}

	function AccessLevelsComparison($result,$checker){
		$status = false;
	     	$allowed = explode(',', $result[0]['allowed_access']);
	    	for($i=0;$i<sizeof($allowed);$i++){
				 if( $allowed [$i] == $checker ){
					 return true;
					 die();
				  }  
		}
		 return $status;
	}
	function editpermission($id){
		
		$access_rights =  $this->db->SelectData("SELECT id, menu_title, on_menu FROM sch_access_rights WHERE on_menu = 'Yes'");
		$selected_access_rights =  $this->db->SelectData("SELECT * FROM sch_user_levels  where access_denotor='".$id."'");
		
		$new_data = array();
		foreach ($access_rights as $key => $value1) {
            $comparison = $this->AccessLevelsComparison($selected_access_rights, $value1['id']);
	    	$new_data[$key]['id'] = $value1['id'];
			$new_data[$key]['menu_title'] = $value1['menu_title'];
	
			if ($comparison == true) {
				$new_data[$key]['ticked'] = 'yes';
			}else{
				$new_data[$key]['ticked'] = 'no';
			}
		}
		return $new_data; 
	}

	function UpdatePermission($data, $id){

		$perm = '';
		if (isset($_POST['permission'])) {
			foreach ($_POST['permission'] as $key => $value) {
				$parent = $this->getParent($value);
				if ($parent[0]['parent_option']!=0) {
					array_push($_POST['permission'], $parent[0]['parent_option']);
				}
			}
			array_unique($_POST['permission']);
			sort($_POST['permission']);

			foreach ($_POST['permission'] as $key => $value) {
				$perm .= $value . ",";
			}

		}
		
		$page_perm = '';

		if (isset($_POST['page_permission'])) {
			sort($_POST['page_permission']);

			foreach ($_POST['page_permission'] as $key => $value) {
				$page_perm .= $value . ",";
			}
		}

	    $postData = array(
			'office_id' => $_SESSION['office'],
			'user_id' => $_SESSION['user_id'],
			'access_denotor' => $_POST['level'],
			'access_name' =>$_POST['name'],
            'creator_access' => $_POST['level'],
            'allowed_access' => $perm,
            'allowed_access_menu' => $page_perm,
        );
       
		$this->db->UpdateData('sch_user_levels', $postData,"`level_id` = '".$id."'");
        header('Location: ' . URL . 'access/viewaccessdetail/'.$id.'?msg=updated'); 
	}

	function DeletePermission($id){
	    $postData = array(
            'status' => 'Closed',
        );
       
		$this->db->UpdateData('sch_user_levels', $postData,"`level_id` = '".$id."'");
        header('Location: ' . URL . 'access?msg=deleted'); 
	 } 

	function getParent($id){
		$parent =  $this->db->SelectData("SELECT parent_option FROM sch_access_rights where id='".$id."' ");
		return $parent;
	}



	/************** THIRD PARTY ACOOUNTS ************/



	function getThirdPartyAccounts(){
		 $result =  $this->db->SelectData("SELECT * FROM sm_thirdparty_accounts WHERE `status` = 'Active'");
	     return $result;

	}

	function InsertThirdParty(){

		$data = array(
			'name' => $_POST['name'],
			'user_name' =>$_POST['user_name'],
            'password' => Hash::create('sha256',$_POST['password'],HASH_PARTY_KEYS),
            'registration_date' => date('Y-m-d H:i:s'),
            'status' => $_POST['status'],
        );
		$this->db->InsertData('sm_thirdparty_accounts', $data);
  		header('Location: ' . URL . 'access/viewthirdparties?msg=success');

	}

	function getPartyDetails($id){
		 $result =  $this->db->SelectData("SELECT * FROM sm_thirdparty_accounts WHERE `user_id`='".$id."'");
	     return $result;

	}

	function UpdateThirdParty($data, $id){
		 $data = array(
			'name' => $_POST['name'],
			'user_name' =>$_POST['user_name'],
            'phone_number' => $_POST['phone_number'],
            'status' => $_POST['status'],
            'password' => Hash::create('sha256',$_POST['password'],HASH_PARTY_KEYS),
        );

		$this->db->UpdateData('sm_thirdparty_accounts', $data,"`user_id` = '".$id."'");
  		header('Location: ' . URL . 'access/viewthirdparties?msg=success');

	}

	function DeleteThirdParty($id){
		 $data = array(
			'status' => 'Closed'
        );

		$this->db->UpdateData('sm_thirdparty_accounts', $data,"`user_id` = '".$id."'");
  		header('Location: ' . URL . 'access/viewthirdparties?msg=success');

	}

	
}