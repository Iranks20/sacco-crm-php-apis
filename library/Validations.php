<?php 
class Validations {

	function __construct() {
	
	}
	
	function validateNumber($amount){
	$amount = str_replace(',', '', $amount);
	if(is_numeric($amount) && !is_float($amount + 0)){
	   return $amount;
	}else{
	   return null;
	}
	}
	
	
	function getAnnualInterest($period, $rate, $days_in_year=null){
	switch($period){
		case "weekly":
		$int_rate = ($rate*52);
		break;
		case "monthly":
		$int_rate = ($rate*12);
		break;
		case "daily":
		$int_rate = ($rate*365);
		break;
		default:
		return 0;
	}
	return number_format($int_rate, 0);
}
}