<?php

class dashboard extends Controller{

    function index() {
		$rs = $this->model->getDashboardData();
		echo json_encode($rs);
		exit;
	}	
}
