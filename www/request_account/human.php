<?php

session_start();

$image_width=180;
$image_height=60;

##

function random_string($length = 6) {

   $charset = str_split('ABCDEFGHKLMNPQRSTVWXYZ@$3456789');
   $randomstr = "";
   for($i = 0; $i < $length; $i++) {
     $randomstr .= $charset[array_rand($charset, 1)];
   }
   return $randomstr;

}

##

$image = imagecreatetruecolor($image_width, $image_height);
imageantialias($image, true);

$cols = [];

$r = rand(100, 200);
$g = rand(100, 200);
$b = rand(100, 200);
 
for($i = 0; $i < 5; $i++) {
  $cols[] = imagecolorallocate($image, $r - 20*$i, $g - 20*$i, $b - 20*$i);
}
 
imagefill($image, 0, 0, $cols[0]);

$thickness = rand(2, 10);

for($i = 0; $i < 10; $i++) {
  imagesetthickness($image, $thickness);
  $line_col = $cols[rand(1,4)];
  imagerectangle($image, rand(-$thickness, ($image_width - $thickness)),
                         rand(-$thickness, $thickness),
                         rand(-$thickness, ($image_width - $thickness)),
                         rand(($image_height - $thickness), ($image_width / 2)),
                 $line_col);
}
 
$black = imagecolorallocate($image, 0, 0, 0);
$white = imagecolorallocate($image, 255, 255, 255);
$textcols = [$black, $white];

$fonts = glob(dirname(__FILE__).'/fonts/*.ttf');
$num_chars = 6;
$human_proof = random_string($num_chars);

$_SESSION['proof_of_humanity'] = $human_proof;
 
for($i = 0; $i < $num_chars; $i++) {
  $gap = ($image_width-15)/$num_chars;
  $size = rand(20,30);
  $angle = rand(-30,30);
  $txt_x = 10 + ($i * $gap);
  $txt_y = rand(30, ($image_height-15));
  $txt_col = $textcols[rand(0,1)];
  $txt_font =  $fonts[array_rand($fonts)];
  $txt = $human_proof[$i];
  imagettftext($image, $size, $angle, $txt_x, $txt_y, $txt_col, $txt_font, $txt);
# print "imagettftext( $size, $angle, $txt_x, $txt_y, $txt_col, $txt_font, $txt);<p>";
}

header('Content-type: image/png');
imagepng($image);
imagedestroy($image);
?>
