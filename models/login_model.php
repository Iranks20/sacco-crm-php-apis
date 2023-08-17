<?php

class login_model extends Model{
	
    public function __construct(){
        parent::__construct();
    }

    function checkLoginStatus($username){
        $usernameExixts = $this->checkUsername($username);
        if ($usernameExixts) {
            $ipsth = $this->db->prepare("SELECT login_status FROM m_staff WHERE username = '$username'");
            $a = $ipsth->execute();
            $b = $ipsth->fetch();
            return $b['login_status'];
        } else {
            return false;
        }

    } 

    function checkUsername($username){
        $ipsth = $this->db->prepare("SELECT * FROM m_staff WHERE username = '$username'");
        $a = $ipsth->execute();
        $b = $ipsth->fetch();

        if (empty($b)) {
            return false;
        } else {
            return true;            
        }

    }

    function checkUsernameAndPassword($username, $password){
		try{

        $sth = $this->db->prepare("SELECT * FROM m_staff WHERE  email or username = :username AND password = :password");
        $sth->execute(array(
            ':username' => $username,
            ':password' => $password
        ));

        $data = $sth->fetch();
        $count = $sth->rowCount();
        $sql  = "SELECT * FROM m_staff WHERE username = ".$username;
 
        if ($count > 0){
            
            @session_start();
            $saccoDetails =$this->GetsaccoDetails($data['office_id']);
            
            if($saccoDetails['head_office']=='Yes'){
                $saccoid = $saccoDetails['id'];
            }else{
                $saccoid = $saccoDetails['parent_id'];        
            }
            $branchname = $saccoDetails['name'];
            $_SESSION['user_id']=$data['id'];
            $rs = $this->GetSettings();
			$start = 360000;
            $instance = $rs['instance_id'];
            Session::set('loggedin', true);
            Session::set('email', $data['email']);
            Session::set('username',$data['username']);
            Session::set('office', $saccoid);
            Session::set('branchid', $data['office_id']);
            Session::set('branch', $branchname);
            Session::set('Isheadoffice', $saccoDetails['head_office']);
            Session::set('timeout', time());
            Session::set('instance', $instance);
            Session::set('access_level', $data['access_level']);
            Session::set('name', $data['firstname']." ".$data['lastname']);
            
            $fileName = "login_debug.txt";
            $this->appendData($fileName, "SESSION: ".json_encode($_SESSION));
            
            $this->db->InsertLog($sql,"m_staff","LOGIN",$count);
            return true;
        } else {
            $this->db->InsertLog($sql,"m_staff","LOGIN",$count);
            return false;            
        }
		}catch(Exception $r){
			return $this->MakeJsonResponse(203,"unknown error", "");
		}

    }
    
    function checkIfStaffIsVerified($username, $password){
		try{

            $sth = $this->db->prepare("SELECT * FROM m_staff WHERE  email or username = :username AND password = :password");
            $sth->execute(array(
                ':username' => $username,
                ':password' => $password
            ));
    
            $data = $sth->fetch();
            
            $response = FALSE;
            if($data['verified_by'] == 0){
                $response = TRUE;//FALSE;
            } else {
                $response = TRUE;
            }
            
            return $response;
		}catch(Exception $r){
			return $this->MakeJsonResponse(203,"unknown error", "");
		}
	}
	
    
    function checkUsernameAndPasswordAlreadyLoggedIn($username, $password){
		try{

            $sth = $this->db->prepare("SELECT * FROM m_staff WHERE  email or username = :username AND password = :password");
            $sth->execute(array(
                ':username' => $username,
                ':password' => $password
            ));
    
            $data = $sth->fetch();
            
            return $data['login_session'];
		}catch(Exception $r){
			return $this->MakeJsonResponse(203,"unknown error", "");
		}
	}
	
	function checkUsernameAndPasswordLastActionTimestamp($username, $password){
		try{

            $sth = $this->db->prepare("SELECT * FROM m_staff WHERE  email or username = :username AND password = :password");
            $sth->execute(array(
                ':username' => $username,
                ':password' => $password
            ));
    
            $data = $sth->fetch();
            
            $last_action_timestamp = $data['latest_action_timestamp'];
            $time_now = date('Y-m-d H:i:s');
            
            $start = new DateTime($last_action_timestamp);
            $end = new DateTime($time_now);
            $interval = $start->diff($end);

            $diff = ($interval->format('%Y')*12*30*24*60) + ($interval->format('%m')*30*24*60) + ($interval->format('%d')*24*60) + ($interval->format('%H')*60) + ($interval->format('%i')); 
            //echo $interval->format('%Y years %m months %d days %H hours %i minutes %s seconds');
            
            return $diff;
		}catch(Exception $r){
			return $this->MakeJsonResponse(203,"unknown error", "");
		}
	}

    function unlockAccount($username){

        $staff_statement = $this->db->prepare("SELECT * FROM m_staff WHERE email or username = '$username'");

        $a = $staff_statement->execute();
        $b = $staff_statement->fetch();

        if (!empty($b)) {
            $id = $b['id'];                
            $loginData = array(
                'login_attempts' => 0,
                'login_timestamps' => date('Y-m-d H:i:s'),
                'login_status' => 'Active'
            );

            $this->db->UpdateData('m_staff', $loginData, "`id` = {$id}");
        }
		return true;
    }

    function lockAccount($username){

        $staff_statement = $this->db->prepare("SELECT * FROM m_staff WHERE email or username = '$username'");

        $a = $staff_statement->execute();
        $b = $staff_statement->fetch();

        if (!empty($b)) {
            $id = $b['id'];                
            $loginData = array(
                'login_timestamps' => date('Y-m-d H:i:s'),
                'login_status' => 'Inactive'
            );

            $this->db->UpdateData('m_staff', $loginData, "`id` = {$id}");
        }
    }

    function getUserNameDetails($username){

        $ipsth = $this->db->prepare("SELECT * FROM m_staff WHERE username = '$username'");
        $a = $ipsth->execute();
        $b = $ipsth->fetch();

        return $b;

    }

    function appendData($file, $data){
        file_put_contents($file, $data .PHP_EOL , FILE_APPEND | LOCK_EX);
        return true;
    } 

    function login($username, $password, $lockouttime){
		try{

        $minimumLoginTime = 5;
        $UsernameAndPasswordExixts = $this->checkUsernameAndPassword($username, $password);
        $userAlreadyLoggedIn = $this->checkUsernameAndPasswordAlreadyLoggedIn($username, $password);
        $userLastPerformedAction = $this->checkUsernameAndPasswordLastActionTimestamp($username, $password);
        $isVerified = $this->checkIfStaffIsVerified($username, $password);
        
        $fileName = "login_debug.txt";
        $this->appendData($fileName, "Uname: ".json_encode($username));
        $this->appendData($fileName, "PWD: ".json_encode($password));
        
        if(!$isVerified){
            return $this->MakeJsonResponse(404,"User not yet verified!!!" );
        } else if($userAlreadyLoggedIn == 1 && $userLastPerformedAction < 30){
            return $this->MakeJsonResponse(404,"User with this username already logged in" );
        } else if ($UsernameAndPasswordExixts) {
            
            $this->unlockAccount($username);
           // header('Location: ' . URL . 'otp');
			
			$userDetails = $this->getUserNameDetails($username);
            $id = $userDetails['id'];
            
            $loginData = array(
                'login_count' => $userDetails['login_count'] + 1,
                'login_timestamps' => date('Y-m-d H:i:s')
            );
            $this->db->UpdateData('m_staff', $loginData, "`id` = {$id}");
            
            $time_now = date_create();
            $next_reset = date_create($userDetails['next_reset']);
            if(is_null($userDetails['next_reset']) || $time_now > $next_reset){
                return $this->MakeJsonResponse(100,"success", URL . 'staff/changePassword/' . $id);
            } else {
                return $this->MakeJsonResponse(100,"success", URL . 'otp');
            }
           
        } else {
            
            $userDetails = $this->getUserNameDetails($username);
            $id = $userDetails['id'];

            $lastLogin = date_create($userDetails['login_timestamps']);
            $now = date_create();

            $diff = date_diff($lastLogin,$now);

            $diff_minutes = $diff->format('%i');

            if ($diff_minutes > $minimumLoginTime) {
                $loginData = array(
                    'login_attempts' => 1,
                    'login_timestamps' => date('Y-m-d H:i:s')
                );

                $this->db->UpdateData('m_staff', $loginData, "`id` = {$id}");
                return $this->MakeJsonResponse(404,"Invalid username or password" );
            } else {
                $attempts = $userDetails['login_attempts'];

                if ($attempts < 2) {
                    $loginData = array(
                        'login_attempts' => $attempts + 1,
                        'login_timestamps' => date('Y-m-d H:i:s')
                    );

                    $this->db->UpdateData('m_staff', $loginData, "`id` = {$id}");
                   return $this->MakeJsonResponse(404,"Invalid username or password" );
                } else {
                    $this->lockAccount($username);
                   return $this->MakeJsonResponse(404,"Mulitple retries reached. Account suspended, try again later " );
                    
                }
            }
        }
		}catch(Exception $e){
			return $this->MakeJsonResponse(203,"unknown error" );
		}

    }

    function setLoginDetails($username, $password){
        $sth = $this->db->prepare("SELECT * FROM m_staff WHERE username = '$username' AND password = '$password'");
        $sth->execute();
        $data = $sth->fetch();
        $count = $sth->rowCount();
        $start = time();
		return true;
    }
    
    function getAuthQRUser($string)
    {
        $qr_data = explode("-", $string);
        
        $response = [];
        $email = $qr_data[1];
    	$phone = $qr_data[2];
        
        $sth = $this->db->prepare("SELECT * FROM m_staff WHERE email = '$email' AND mobile_no LIKE '%$phone'");
        $sth->execute();
        $data = $sth->fetch();
        
    	$response['username'] = $data['username'];//"steve";
    	$response['hashed_password'] = $data['password']; //"b18783c082aae84de5434f39546ef5ff467dfcac36f598a72a932037ab927549";
        
		return $response;
    }

    function authUser($data) {
		try{
        $uname = $data['username'];
        
        if(isset($data['hashed_password'])){
            $pwd = $data['hashed_password'];
        } else {
            $pwd = Hash::create('sha256',$data['password'],HASH_ENCRIPT_PASS_KEYS);
        }
        
        //print_r($data);
        
        //echo $pwd;
        
        $loginStatus = $this->checkLoginStatus($uname);

        $lockOutTime = 15; //minutes 
        if ($loginStatus == 'Active') {

            $rs =$this->login($uname, $pwd, $lockOutTime);
			return $rs;

        } else if ($loginStatus == 'Inactive') {
            $userDetails = $this->getUserNameDetails($uname);

            $lastLogin = date_create($userDetails['login_timestamps']);
            $now = date_create();

            $diff = date_diff($lastLogin,$now);

            $diff_minutes = $diff->format('%i');

            if ($diff_minutes >= $lockOutTime) {
                $this->unlockAccount($uname);
                $rs = $this->login($uname, $pwd, $lockOutTime);
				 return $rs;
            } else {
            return $this->MakeJsonResponse(503,"Account is suspended, try again after ".$lockOutTime. " minutes");
            }

        } else {
            return $this->MakeJsonResponse(404,"Invalid username or password" );
        }
        }catch(Exception $e){
			return $this->MakeJsonResponse(203,"unknown error" );
		}
    }

    function getrole($id){
        $result= $this->db->SelectData("SELECT * FROM m_role where role_id='".$id."' and is_disabled='Enabled' ");
        return  $result[0]['name'];
    }
    	
    function  getCoutries(){
        $result= $this->db->SelectData("SELECT distinct country FROM m_branch");
        return  $result;
    }

}

?>