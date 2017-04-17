<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

class QrController extends Controller
{
  public function __construct()
  {

  }
  public function getImageSize($image) {
    $size = [
      "width" => imagesx ( $image ),
      "height" => imagesy ( $image )
    ]; 
    return $size;
  }
  public function getBgImage() {
    $img = imagecreatefromjpeg (dirname(__FILE__).'/../bg.jpg' );
    return $img;
  }
  public function getQRImage() {
    $img = imagecreatefrompng (dirname(__FILE__).'/../qr.png' );
    return $img;
  }
  public function drawTitle($image, $text, $color, $maxWidth, $offsetX, $offsetY) {
    // 分配颜色
    $textcolor = imagecolorallocate($image, $color[0], $color[1], $color[2]);
    $font = __DIR__.'/msyh.ttf';
    $fontSize = 31;
    $len = 0;
    $n = 100;
    $limitX = $maxWidth - $offsetX;
    do {
      $fontSize--; 
      $n--;
      $box = imagettfbbox ( $fontSize, 0, $font, $text);
      $len = $box[2] - $box[0];
    } while ($n > 0 && $len > $limitX);

    $x = ($maxWidth - $len) / 2;
    $y = $box[1] - $box[7] + $offsetY;
    //var_dump($box);

    imagettftext($image, $fontSize, 0, $x, $y, $textcolor, $font, $text);
  }
  public function index (Request $request) 
  {
    $width = 100;
    $height = 300;
    $title = '前端 Code-review 开团xxx';

    $bg = $this->getBgImage();
    $codeImage = $this->getQRImage();

    $bgImageSize = $this->getImageSize($bg);
    $codeImageSize = $this->getImageSize($codeImage);

    //$image = imagecreatetruecolor(200, 100);
    //$bgcolor = imagecolorallocate($image, 0, 0, 0);  
    //$textcolor = imagecolorallocate($bg, 0, 0, 0);
    //imagestring($bg, 20, 15, 10, "Hello world!", $textcolor);
    $this->drawTitle($bg, $title, [0, 0, 0], $bgImageSize['width'], 20, 7);
    // 计算中心点
    // 默认画在y轴下 60% 的位置
    $drawYPercent = 0.6;
    $drawPosition = [
      "x" => ($bgImageSize['width'] - $codeImageSize['width'] ) / 2,
      "y" => $bgImageSize['height'] * $drawYPercent
    ];
    if ($bgImageSize['height'] * (1 - $drawYPercent) < $codeImageSize['height']) {
      // 剩下的部分不够画的, 就反推位置
      $drawPosition['y'] = $bgImageSize['height'] - $codeImageSize['height'] - 20;
    }


    imagecopy ( $bg, $codeImage, $drawPosition['x'], $drawPosition['y'], 0, 0, $codeImageSize['width'], $codeImageSize['height']);
    return $this->image($bg);
  }
}

