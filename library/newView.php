<?php
class View{

	public function __construct(){
     $this->db = new Database();

	}
	public function render_home($dispName){ //render
    $this->display_menus = $this->BuildMenus();	
	require 'views/header_home.php';
	require 'views/'.$dispName.'.php';
	require 'views/footer.php';
	}
	public function render($dispName){ //render
    $this->display_menus = $this->BuildMenus();
	require 'views/header.php';
	require 'views/'.$dispName.'.php';
	require 'views/footer.php';
	}
	public function renders($dispName){ //render

	require 'views/'.$dispName.'.php';
	
	}
    public function BuildMenus() {
   $user_id = Session::get('user_id');		
      $allowed_rights = $this->db->SelectData("SELECT allowed_access FROM sch_user_levels WHERE user_id=:id", array('id' => $user_id));
  //print_r($allowed_rights);die();  
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
                $submenus = $this->SubMenus($tmenu['id']);
                $menulist[$tmenu['id']]['Title'] = $tmenu['menu_title'];
                $menulist[$tmenu['id']]['CSS'] = $tmenu['css'];
                $menulist[$tmenu['id']]['Submenus'] = $submenus;
            }
        }
        return $menulist;
    }

    function SubMenus($id) {
		
      $submenulist = $this->db->SelectData("SELECT * FROM sch_access_rights WHERE parent_option = :parent_option AND on_menu=:yes ORDER BY rank ASC", array('parent_option' => $id, 'yes' =>'Yes'));
   if(count($submenulist)>0){     
	  foreach ($submenulist as $key => $value) {
            $submenus[$key]['Submenus'] = $value;
        }
        return $submenus;
    }
	}

    function AccessRights($u_role) {
        $allowed_rights = $this->db->SelectData("SELECT allowed_access FROM sch_user_levels WHERE user_id=:id", array('id' => $u_role));		
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
	
	
	
}
