<?php    
   
include "qrlib.php";        
$filename = 'test.png';
$errorCorrectionLevel = 'L';
$matrixPointSize = 4;
$txt = 'MBONYE EMMANUEL';
QRcode::png($txt, $filename, $errorCorrectionLevel, $matrixPointSize, 2);    
echo '<img src="'.$filename.'" /><hr/>';  
  
    