<?php 
include "phpqrcode-master/qrlib.php";   
class ImageLoaderBack {

function __construct() {

}



function ImageLoader($walletNo,$name){
 $name = strtoupper($name);

$template="public/temp/backImage.jpg"; 

$filename = 'public/temp/bjpg.png';
$errorCorrectionLevel = 'L';
$matrixPointSize = 10;
$txt = $walletNo;
$valid = "20/22";
QRcode::png($txt, $filename, $errorCorrectionLevel, $matrixPointSize, 2);    
$this->WriteOnImage($template,$name,$filename,$walletNo);
}

function WriteOnImage($template,$yourname,$qrcode,$wallet){
$image = imagecreatefromjpeg($template);
imagealphablending($image, true);
$expdate ="12/20";
$white = imagecolorallocate($image,  250, 250, 250);
$address = $wallet."*clic.world";
//imagefttext($image, 32, 0, 460, 277, $white, 'public/temp/font/Rajdhani-Bold.ttf', $yourname);
imagefttext($image,17, 0, 770, 350, $white, 'public/temp/font/Rajdhani-Bold.ttf', $address);
//imagefttext($image,26, 0, 170, 440, $white, 'public/temp/font/Rajdhani-Bold.ttf', $expdate);


$newimage = "public/temp/".$wallet."_back.jpg";

imagejpeg($image,$newimage,90);
imagedestroy($image);
$this->AddQRToImage($newimage,$qrcode);

}

function AddQRToImage($template,$photo_to_paste){



$im = imagecreatefromjpeg($template);
$condicion = GetImageSize($photo_to_paste); // image format?

if($condicion[2] == 1) //gif
$im2 = imagecreatefromgif("$photo_to_paste");
if($condicion[2] == 2) //jpg
$im2 = imagecreatefromjpeg("$photo_to_paste");
if($condicion[2] == 3) //png
$im2 = imagecreatefrompng("$photo_to_paste");

imagecopy($im, $im2, 730, 75, 0, 0, imagesx($im2), imagesy($im2));

header('Content-type: image/jpeg');

imagejpeg($im,$template,100);
imagejpeg($im);
imagedestroy($im);
imagedestroy($im2);

}


}


