<?php
$content = trim(file_get_contents("php://input"));
$data = json_decode($content, true);

$DB_USER =  $data['dbuser'];
$DB_PASS =  $data['dbpassword'];
$DB_NAME =  $data['dbname'];
$DB_HOST =  $data['Iip'];
$filename = $data['configfile'];


file_put_contents('../../config/'.$filename, "<?php\ndefine('DB_TYPE', 'mysql');\ndefine('DB_HOST', '".$DB_HOST."');\ndefine('DB_NAME', '".$DB_NAME."');\ndefine('DB_USER', '".$DB_USER."');\ndefine('DB_PASS', '".$DB_PASS."');");
echo "success";

