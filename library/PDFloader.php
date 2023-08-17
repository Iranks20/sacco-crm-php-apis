<?php 
//include "setasign/fpdf/src/autoload.php";
require_once(__DIR__ . '/../vendor/setasign/fpdf/fpdf.php');

class PdfLoader {
function __construct() {

}

    function LoadPdf($tel,$name, $lname, $dob, $nin, $regno, $pp, $accountno){
        $source = 'public/images/avatar/'.$pp;
        $path = 'public/images/avatar/cropped/'.$pp;
        $size=[200,235];
        $this->crop($source, $path, $size);
        // initiate FPDI
        $pdf = new FPDF();
        // add a page
        $pdf->AddPage();
        // set the source file
        //$pdf->setSourceFile('public/cards/Template.pdf');
        // import page 1
      //  $tplId = $pdf->importPage(1);
        // use the imported page and place it at point 10,10 with a width of 100 mm
        //$pdf->useTemplate($tplId,0, 0, null, null, false);
        // now write some text above the imported page
 
                
        $pdf->Image('public/temp/'.$tel.'.jpg', 15,50,180,0,'JPG','');
        //$pdf->Image('public/temp/'.$tel.'.jpg', 20,120,120,80,'JPG','www.plus2net.com');
        $pdf->AddPage();
        $pdf->Image('public/temp/'.$tel.'_back.jpg', 15,50,180,0,'JPG','');

$pdf->Output();

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

}


