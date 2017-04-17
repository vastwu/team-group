<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DB;
use App\Http\Requests;

class QrController extends Controller
{
  // 上边距
  private $thumb_top = 300;
  // 左右边距
  private $thumb_offset = 20;
  // 间距
  private $thumb_spacing = 10;

  private $bg_width = 600;
  private $bg_height = 800;

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
  public function getBgImage($width, $height) {
    $bg = imagecreatetruecolor($width, $height);
    $img = imagecreatefromjpeg (dirname(__FILE__).'/../bg.jpg' );
    $size = $this->getImageSize($img);
    imagecopyresized ( $bg, $img, 
      0, 0, 
      0, 0, 
      $width, $height,
      $size['width'], $size['height']
    );
    return $bg;
  }
  public function getQRImage() {
    $codeSize = 330;
    $code = imagecreatetruecolor($codeSize, $codeSize);
    $img = imagecreatefrompng (dirname(__FILE__).'/../qr.png' );
    $size = $this->getImageSize($img);
    imagecopyresized ( $code, $img, 
      0, 0, 
      0, 0, 
      $codeSize, $codeSize,
      $size['width'], $size['height']
    );
    return $code;
  }
  public function drawThumb($bg, $thumbs, $bgSize) {
    $offset = $this->thumb_offset;
    $spacing = $this->thumb_spacing;
    $thumbSize = ($bgSize['width'] - $offset * 2 - $spacing * 4) / 5;
    $x = $offset;
    $y = $this->thumb_top;
    foreach ($thumbs as $url) {
      $img = imagecreatefromjpeg($url);
      $size = $this->getImageSize($img);
      imagecopyresized ( $bg, $img, 
        $x, $y, 
        0, 0, 
        $thumbSize, $thumbSize,
        $size['width'], $size['height']
      );
      $x += $thumbSize  + $spacing;
    }
  }
  public function drawTitle($image, $text, $color, $maxWidth, $offsetX, $offsetY) {
    return
    // 分配颜色
    $textcolor = imagecolorallocate($image, $color[0], $color[1], $color[2]);
    $font = __DIR__.'/msyh.ttf';
    $fontSize = 31;
    // 防死循环
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

    $gid = $request->get('gid');
    $result = DB::table('group')
      ->where('id', $gid)
      ->get();

    $group = $result[0];
    //var_dump($group);
    //return $this->json($group);
    $title = $group->title;
    $summary = urldecode($group->summary);
    $images = json_decode($group->images);
    $contact = $group->contact;

    $bg = $this->getBgImage($this->bg_width, $this->bg_height);
    $codeImage = $this->getQRImage();

    $bgImageSize = $this->getImageSize($bg);
    $codeImageSize = $this->getImageSize($codeImage);

    //$image = imagecreatetruecolor(200, 100);
    //$bgcolor = imagecolorallocate($image, 0, 0, 0);  
    //$textcolor = imagecolorallocate($bg, 0, 0, 0);
    //imagestring($bg, 20, 15, 10, "Hello world!", $textcolor);
    $this->drawTitle($bg, $title, [0, 0, 0], $bgImageSize['width'], 20, 7);
    $this->drawThumb($bg, $images, $bgImageSize);
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

