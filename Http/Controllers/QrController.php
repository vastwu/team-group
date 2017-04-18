<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DB;
use App\Http\Requests;

class QrController extends Controller
{
  private $font = "";
  // 上边距
  private $thumb_top = 300;
  // 左右边距
  private $thumb_offset = 20;
  // 间距
  private $thumb_spacing = 10;

  private $bg_width = 600;
  private $bg_height = 800;

  private $summary_fontsize = 20;
  private $summary_linespacing = 15;

  public function __construct()
  {
    $this->font = __DIR__.'/msyh.ttf';
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
  public function getToken($focusReload = false) {

    $wx_token = '';
    if (!$focusReload) {
      # 非强制刷新，先从db里找
      $existConfig = DB::table('config')->where("key", "wx_token")->first(); 
      $wx_token = isset($existConfig->value) ? $existConfig->value : '';
    }
    if (!$wx_token) {
      // reload
      $appId = env('WECHAT_APP_ID');
      $secret = env('WECHAT_SECRET');
      $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appId&secret=$secret";
      $wechatResult = json_decode(file_get_contents($url), true);
      if (!isset($wechatResult['errcode'])) {
        $wx_token = $wechatResult['access_token'];
        $existConfig = DB::table('config')->where("key", "wx_token")->first(); 
        if ($existConfig) {
          DB::table('config')
            ->where('key', "wx_token")
            //->where('id', 6)
            ->update([
              "value" => $wx_token,
              "lastupdatetime" => time()
            ]);
        } else {
          DB::table('config')->insert([
            "key" => "wx_token",
            "value" => $wx_token,
            "lastupdatetime" => time()
          ]);
        }
      }
    }
    return $wx_token;
  }
  public function getQRImage($token, $size = 300, $path = 'pages/index', $canReload = true) {
    // example
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

    //$codeSize = 330;
    //$path = 'pages/index?query=1';

    $url = "https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token=$token";

    $post_data = "{\"path\": \"$path\", \"width\": $size}";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    curl_setopt($ch, CURLOPT_HEADER, false);
    //$img = curl_exec($ch);
    $img = curl_exec($ch);
    curl_close($ch);
    try {
      $img = imagecreatefromstring($img);
    } catch (\Exception $e) {
      if ($canReload) {
        // 允许刷新token后尝试一次
        $token = $this->getToken(true);
        return $this->getQRImage($token, $size, $path, false);
      }
    }
    return $img;
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
    // 分配颜色
    $textcolor = imagecolorallocate($image, $color[0], $color[1], $color[2]);
    $font = $this->font;
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
    imagettftext($image, $fontSize, 0, $x, $y, $textcolor, $font, $text);
  }
  public function drawSummary($image, $text, $color, $maxWidth, $offsetX, $offsetY) {

    //$text = "一\n二三\n四五六七八九十，一二三四五六七八九十；一二三四五六七八九十";

    // 分配颜色
    $textcolor = imagecolorallocate($image, $color[0], $color[1], $color[2]);
    $font = $this->font;
    $fontSize = $this->summary_fontsize;
    $rowText = "";
    $acceptWidth = $maxWidth - $offsetX * 2;
    $x = $offsetX;
    $y = $offsetY;

    for ($i = 0; $i < mb_strlen($text); $i++) {
      $rowBox = imagettfbbox($fontSize, 0, $font, $rowText);
      $_string_length = $rowBox[2] - $rowBox[0];
      // 多加一个字符, 看是否需要换行了
      $addText = mb_substr($text, $i, 1);
      $addBox = imagettfbbox($fontSize, 0, $font, $addText);
      $addLen = $addBox[2] - $addBox[0];
      if ($addText === "\n") {
        // 强制换行
        $addLen = $acceptWidth + 1;
        $addText = '';
      }
      if ($_string_length + $addLen  < $acceptWidth) {
        // 没超过
        $rowText .= $addText;
      } else {
        // 多了，render
        imagettftext($image, $fontSize, 0, $x, $y, $textcolor, $font, $rowText);
        $rowText = "";
        $y += $fontSize + $this->summary_linespacing;
      }
    }
    if (mb_strlen($rowText) > 0) {
      imagettftext($image, $fontSize, 0, $x, $y, $textcolor, $font, $rowText);
    }
  }
  public function show (Request $request) 
  {
    $token = $this->getToken();
    $codeImage = $this->getQRImage($token);
  
    return $this->json(0, ["tk" => $token]);
  }
  public function index (Request $request) 
  {
    $width = 100;
    $height = 300;

    $gid = $request->get('gid');
    $appPath = $request->get('path');

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
    $token = $this->getToken();
    $codeImage = $this->getQRImage($token, 300, $appPath);

    $bgImageSize = $this->getImageSize($bg);
    $codeImageSize = $this->getImageSize($codeImage);

    $this->drawTitle($bg, $title, [0, 0, 0], $bgImageSize['width'], 20, 7);
    $this->drawSummary($bg, $summary, [0, 0, 0], $bgImageSize['width'], 20, 150);
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

