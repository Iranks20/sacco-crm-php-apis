<?php 
//include "phpqrcode-master/qrlib.php";   
class ImageLoader {

function __construct() {

}



function ImageLoad($walletNo,$name, $lname, $dob, $nin, $regno, $profileimage, $accountno){
 $name = strtoupper($name);
 $lname = strtoupper($lname);
$template="public/temp/FrontImage.jpg"; 
$filename = 'public/temp/bjpg.png';
$errorCorrectionLevel = 'L';
$matrixPointSize = 10;
$txt = $walletNo."_".$name;
$valid = "20/22";


QRcode::png($txt, $filename, $errorCorrectionLevel, $matrixPointSize, 2);    
$this->WriteOnImage($template,$name,$filename,$walletNo, $lname, $dob, $nin, $regno, $profileimage, $accountno);
}


function WriteOnImage($template,$yourname,$qrcode,$wallet, $lname, $dob, $nin, $regno, $profileimage, $accountno){
    
$image = imagecreatefromjpeg($template);
imagealphablending($image, true);
$expdate ="12/20";
$white = imagecolorallocate($image,  0, 0, 0);
$black = imagecolorallocate($image,  255,255,255);
$address = $wallet."*clic.world";
$name = $lname." ".$yourname;
imagefttext($image, 18, 0, 800, 325, $black, 'public/temp/font/Rajdhani-Bold.ttf', $accountno);
imagefttext($image, 20, 0, 250, 325, $white, 'public/temp/font/Rajdhani-Bold.ttf', $name);
//imagefttext($image, 14, 0, 400, 330, $white, 'public/temp/font/Rajdhani-Bold.ttf', $yourname);
imagefttext($image, 20, 0, 250, 380, $white, 'public/temp/font/Rajdhani-Bold.ttf', $dob);
imagefttext($image, 20, 0, 250, 430, $white, 'public/temp/font/Rajdhani-Bold.ttf', $nin);
imagefttext($image, 20, 0, 250, 485, $white, 'public/temp/font/Rajdhani-Bold.ttf', $regno);

//imagefttext($image,12, 0, 250,  530, $white, 'public/temp/font/Rajdhani-Bold.ttf', $address);
//imagefttext($image,12, 0, 250, 555, $white, 'public/temp/font/Rajdhani-Bold.ttf', $expdate);




$newimage = "public/temp/".$wallet.".jpg";
//$profileimage ='public/images/avatar/'.$profileimage;
imagejpeg($image,$newimage,90);
imagedestroy($image);

$this->AddQRToImage($newimage,$profileimage);

}


function load_image($filename, $type) {
    if( $type == 3 ) {
        $image = imagecreatefromjpeg($filename);
    }
    elseif( $type == IMAGETYPE_PNG ) {
        $image = imagecreatefrompng($filename);
    }
    elseif( $type == IMAGETYPE_GIF ) {
        $image = imagecreatefromgif($filename);
    }
    return $image;
}

function resize_image($new_width, $new_height, $image, $width, $height) {
    $new_image = imagecreatetruecolor($new_width, $new_height);
    imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    return $new_image;
}

function resize_image_to_width($new_width, $image, $width, $height) {
    $resize_ratio = $new_width / $width;
    $new_height = $height * $resize_ratio;
    $finalimage = $this->resize_image($new_width, $new_height, $image, $width, $height);
    //var_dump($finalimage);
    return $finalimage;
    
}




function crop($source, $path, $size) {

	if (is_file($source)&&$path) {
		$size_x	=$size[0];
		$size_y	=$size[1];
		
		$image_size	=getimagesize($source);
		$width	=$image_size[0];
		$height	=$image_size[1];

		$ratio_1	=($width / $height);
		$ratio_2	=($size_x / $size_y);
		
		if ($ratio_1 > $ratio_2) {
			$modheight	=$height;
			$modwidth	=$modheight * $ratio_2;
		} 
		else {
			$modwidth	=$width;
			$modheight	=$modwidth / $ratio_2;
		}

		$point_x	=0;
		$point_y	=0;
		if ($width>$modwidth) $point_x=($width - $modwidth)/2;
		#if ($height>$modheight) $point_y=($height - $modheight)/2;
		
		$resizeable	=($width!=$size_x||$height!=$size_y);
		if ($resizeable) {#$im2 !== FALSE
			copy($source, $path);
			$ext	=pathinfo(strtolower($source), PATHINFO_EXTENSION);
			$thumb	=imagecreatetruecolor($size_x, $size_y);
			# maintain transparency
			if ($ext=="png"||$ext=="gif") {
				imagealphablending($thumb, false);
				imagesavealpha($thumb, true);
			}
			else {
				# create white bg in transparency
				$color	=imagecolorallocate($thumb, 255, 255, 255);
				imagefill($thumb, 0, 0, $color);
			}


			# imagecreatefrom{$ext}: bmp,xbm,wbmp';
			if ($ext=="png") {
				$image	=imagecreatefrompng($path);
			}
			elseif ($ext=="gif") {
				$image	=imagecreatefromgif($path);
			}
			else {
				$image	=imagecreatefromjpeg($path);
			}	
			# ------ crop
			$image	=imagecrop($image, ['x' => $point_x, 'y' => $point_y, 'width' => $modwidth, 'height' => $modheight]);

			# ------ resize
			imagecopyresampled($thumb, $image, 0, 0, 0, 0, $size_x, $size_y, $modwidth, $modheight);
			
			# -------- 
			if ($ext=="png") {
				imagepng($thumb, $path);
			}
			elseif ($ext=="gif") {
				imagegif ($thumb, $path);
			}
			else {
				imagejpeg($thumb, $path);
			}
			imagedestroy($thumb);

		}
	}
}


function AddQRToImage($template,$photo_to_paste){
$certified = true;
  $filename = 'public/temp/UDSACertified.jpg';
   $old_imagei = imagecreatefromjpeg("$filename");
    
    $im2i = $old_imagei;
    

    $im = imagecreatefromjpeg($template);
  
    
    $condicion = GetImageSize($photo_to_paste); // image format?
    list($width, $height, $type) = getimagesize($photo_to_paste);
 
    if($condicion[2] == 1) //gif
    $old_image = imagecreatefromgif("$photo_to_paste");
    $image_width_fixed = $this->resize_image_to_width(300, $old_image, $width, $height);
   // $path = "cropped/ian.png";
    //$size=[200,235];
    //crop($photo_to_paste, $path, $size);
   
    $im2 = $image_width_fixed;
    
    if($condicion[2] == 2) //jpg
    $old_image = imagecreatefromjpeg("$photo_to_paste");
    $image_width_fixed =  $this->resize_image_to_width(200, $old_image, $width, $height);
    $im2 = $image_width_fixed;
    
    if($condicion[2] == 3) //png
    $old_image = imagecreatefrompng("$photo_to_paste");
    $image_width_fixed =  $this->resize_image_to_width(242, $old_image, $width, $height);
    $im2 = $image_width_fixed;
    
    
    imagecopy($im, $im2, 720, 45, 0, 0, imagesx($im2), imagesy($im2));
    if($certified){
            imagecopy($im, $im2i, 750, 345, 0, 0, imagesx($im2i), imagesy($im2i));

    }
    header('Content-type: image/jpeg');
    
    imagejpeg($im,$template,90);
    imagejpeg($im);
    imagedestroy($im);
    imagedestroy($im2);
    imagedestroy($im2i);

}

}


