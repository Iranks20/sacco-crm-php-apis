<?php

class Auth  {

	public function __construct(){
	 // correct
    //$foobar->foobarfunc();	
		$this->db = new Database();
		$this->view = new View();
	}
	
	public static function handleSignin()
	{
	    $db = new  Database();
		@session_start();
		$id = $_SESSION['user_id'];
		
		if (empty($id)){
		    
			session_destroy();
			header('Location: '. URL . 'login');
			exit;
			
		} else {

    	    $session_data = array(
                'login_session' => 1,
                'latest_action_timestamp' => date('Y-m-d H:i:s'),
            );
            $db->UpdateData('m_staff', $session_data, "`id` = {$id}");
		}
	} 
	public static function handlesession()
	{
		@session_start();
		$user_id = Session::get('user_id');
		if (!empty($user_id)){
			header('Location: '. URL);
		}
	}
	

	public static function CheckSession() {

        $db = new  Database();
        $id = $_SESSION['user_id'];
        
        if(!isset($_SESSION['verified_otp'])){
            
            header('Location: '. URL . 'otp');
			exit;
		}
        
        //If you are logged in
		$inactive = 900;//1440;
		if(isset($_SESSION['timeout'])) {

			$session_life = time()-($_SESSION['timeout']);
			$loggedin = Session::get('loggedin');
			
			if(($loggedin ==false) || ($session_life > $inactive)){ 

				session_start();

				$path = "public/images/avatar/". $_SESSION['username'] . ".txt" ;

				if (file_exists($path)) {
					$text = file_get_contents($path);
					$todelete = explode(':', $text);
					for ($i=0; $i < sizeof($todelete); $i++) {
						if (file_exists($todelete[$i])) {
							unlink($todelete[$i]);
						}
					}
					unlink($path);
				}
				
				$session_data = array(
                    'login_session' => NULL,
                    //'latest_action_timestamp' => "0000-00-00 00:00:00",
                    'latest_action_timestamp' => date('Y-m-d H:i:s')
                );
                $db->UpdateData('m_staff', $session_data, "`id` = {$id}");
				
				session_destroy();
				header('Location: ' . URL . 'login');
				exit;
				
		    } else{
		        $session_data = array(
                    'login_session' => 1,
                    'latest_action_timestamp' => date('Y-m-d H:i:s'),
                );
                $db->UpdateData('m_staff', $session_data, "`id` = {$id}");
		    }
		}
		
	}
	
	public static function CheckAuthorization() {

		$url = isset($_GET['url']) ? $_GET['url'] : null;
		$url = rtrim($url, '/');
		$url = filter_var($url, FILTER_SANITIZE_URL);
		$_url=explode('/', $url);
		
		if(count($_url)==1){
			$method=$_url[0].'/';
		}else{
			$method=$_url[0].'/'.$_url[1].'/';		
		}
		$user=$_SESSION['access_level'];
		$access=Auth::AccessRights($user);

		/*

		print_r($user);
		echo "</br>";
		print_r($method);
		echo "</br>";
		print_r($access);
		die();

		if (in_array($method,$access)){
            return true;
        }else{
			//$access= $this->view->AccessRights($user);
      		header('Location: '. URL.'?access=denied');
      		die();	
		}

		 */

		
	}
	public static function AccessRights($u_role) {
	
		$db = new  Database(); 
		$id = $_SESSION['office'];
		
		$allowed_rights = $db->SelectData("SELECT allowed_access FROM sch_user_levels WHERE office_id = '$id' AND access_denotor=:id", array('id' => $u_role));		
		foreach ($allowed_rights as $value) {
			$menu_set = explode(',', $value['allowed_access']);
			$aclist =Auth::ACList($menu_set);
		}

		return $aclist;
	}

	public static function ACList($ml) {
		$db = new  Database(); 
		foreach ($ml as $key => $value){ 
			$aclist = $db->SelectData("SELECT * FROM sch_access_rights WHERE id=:id ORDER BY rank ASC", array('id' => $value));

			foreach ($aclist as $key => $tmenu) {
				$accesslist[$tmenu['id']] = $tmenu['load_page'];
			}
		}	
		
		return $accesslist;
	}
	
}