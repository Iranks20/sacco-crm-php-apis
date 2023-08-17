<?php
$domain = $_SERVER['HTTP_HOST'];
$subdomain = join('.', explode('.', $domain, -2));
require './config/config.php';

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

define('URL','https://'.$domain.'/');
define('PRI','https://'.$domain.'/');
define('CLIC_API','https://clicmonkey.xyz/test_cliix_api/v1/register_sacco_details/');
define('THRIDPARTY_PRODUCTS_API','http://clic.world/fedapi/v2/v1/thirdpartyproducts');
define('STAFF_URL','https://clicmonkey.xyz/test_cliix_api/v1/addSacco/');
define('REG_CLIENT', 'https://clic.world/fedapi/v3/users/createdodeaccount/');

define('LIBS','library/');
define('SMS_URL','https://clic.world/fedapi/sms.php');
define('PUSHY_KEY','');

define('STELLAR_WALLET_BALANCE','http://clic.world/fedapi/v3/users/getbalance');
//define('STELLAR_WALLET_STATEMENT','http://clic.world/fedapi/v3/users/txstatement');
define('STELLAR_WALLET_STATEMENT','https://api.clic.world/exchange/v3/exchange/statement');
define('ALL_WALLETS_STATEMENT','https://cliix.co/v2/exchange/statement');
define('SEARCH_TRANSACTIONS','https://cliix.co/v2/admin/search_transactions');

define('HASH_PARTY_KEYS','D15HG5774/\*$%096loveu^7*^2KcH><?UzXh458LmyLI2??#.,@$*B!+XGS?OX!$^*');
define('HASH_ENCRIPT_PASS_KEYS','THU5774//394096loveWEAKcodesALLmyLI2??#.,@$*>!+XGS?OX!$^*');
define('HASH_ENCRIPT_MEMBER_KEYS','THU5774/\*$%096loveu^7*^2KcHGJALLmyLI2??#.,@$*B!+XGS?OX!$^*');

//require 'config/config.php';

define('API_URL','https://clicyourworld/api/v2/');
//define('LIBS','library/');
$auto=function($class) {
   require LIBS.$class.".php";
};
 spl_autoload_register($auto);
$check =new Bootstrap();
$check->init();

?>