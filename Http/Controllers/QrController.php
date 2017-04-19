<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DB;
use App\Http\Requests;

class QrController extends Controller
{
  private $font = "";

  private $thumb_x = 75;
  private $thumb_y = 901.5;
  private $thumb_size = 180;
  private $thumb_spacing = 22.5;

  private $bg_width = 1125;
  private $bg_height = 2001;

  private $username_fontsize = 45;
  private $username_color = [57, 57, 57];
  private $username_linespacing = 60;
  private $username_x = 201;
  private $username_y = 82.5;

  private $title_fontsize = 60;
  private $title_color = [57, 57, 57];
  private $title_linespacing = 90;
  private $title_x = 75;
  private $title_y = 270;

  private $summary_fontsize = 45;
  private $summary_color = [57, 57, 57];
  private $summary_linespacing = 72;
  private $summary_x = 75;
  private $summary_y = 480;

  public function __construct()
  {
    $this->font = __DIR__.'/msyh.ttf';
  }
  public function getImageSize($image) {
    $size = [
      "width" => imagesx($image),
      "height" => imagesy($image)
    ]; 
    return $size;
  }
  public function getBgImage($width, $height) {
    $bg = imagecreatetruecolor($width, $height);
    $img = imagecreatefrompng(dirname(__FILE__).'/../bg.png' );
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
  public function getQRImage($token, $size, $path = 'pages/index', $canReload = true) {
    // 模拟
    $isMock = true;
    $path = 'pages/index?query=1';
    $img = imagecreatetruecolor($size, $size);
    if ($isMock) {
      $srcImg = imagecreatefrompng (dirname(__FILE__).'/../qr.png' );
    } else {
      $url = "https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token=$token";
      $post_data = "{\"path\": \"$path\", \"width\": $size}";
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
      curl_setopt($ch, CURLOPT_HEADER, false);
      $srcImg = curl_exec($ch);
      curl_close($ch);
      try {
        $srcImg = imagecreatefromstring($srcImg);
      } catch (\Exception $e) {
        if ($canReload) {
          // 允许刷新token后尝试一次
          $token = $this->getToken(true);
          return $this->getQRImage($token, $size, $path, false);
        } else {
          return 400; 
        }
      }
    }
    $getSize = $this->getImageSize($srcImg);
    $cropSize = $getSize['width'] - 20;
    imagecopyresized ( $img, $srcImg, 
      0, 0,  // dist
      10, 10, // src
      $size, $size, // dist
      $cropSize, $cropSize // src
    );
    return $img;
  }
  public function drawImage($bg, $url, $thumbSize, $offsetX, $offsetY) {
    if (preg_match("/\.png$/", $url)) {
      $img = imagecreatefrompng($url);
    } else {
      $img = imagecreatefromjpeg($url);
    }
    $size = $this->getImageSize($img);
    imagecopyresized ( $bg, $img, 
      $offsetX, $offsetY, 
      0, 0, 
      $thumbSize, $thumbSize,
      $size['width'], $size['height']
    );
  }
  /*
  public function drawThumb($bg, $thumbs, $thumbSize, $offsetX, $offsetY, $thumb_spacing) {
    foreach ($thumbs as $url) {
      if (preg_match("/\.png$/", $url)) {
        $img = imagecreatefrompng($url);
      } else {
        $img = imagecreatefromjpeg($url);
      }
      $size = $this->getImageSize($img);
      imagecopyresized ( $bg, $img, 
        $offsetX, $offsetY, 
        0, 0, 
        $thumbSize, $thumbSize,
        $size['width'], $size['height']
      );
      $offsetX += $thumbSize + $thumb_spacing;
    }
  }
   */
  public function drawText($image, $text, $color, $maxWidth, $fontSize, $offsetX, $offsetY, $lineHeight) {
    // 分配颜色
    $textcolor = imagecolorallocate($image, $color[0], $color[1], $color[2]);
    $font = $this->font;
    $acceptWidth = $maxWidth - $offsetX * 2;
    $rowText = "";
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
        $y += $lineHeight;
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

    $group = DB::table('group')
      ->leftJoin('user', 'user.id', '=', 'group.userid')
      ->where('group.id', $gid)
      ->select('group.*', 'user.name as user_name', 'user.avatar as user_avatar')
      ->first();

    /*
    $result = DB::table('group')
      ->where('id', $gid)
      ->get();
    $group = $result[0];
     */
    //var_dump($group);
    //return $this->json($group);
    $title = $group->title;
    $summary = urldecode($group->summary);
    $images = json_decode($group->images);
    $contact = $group->contact;

    $bg = $this->getBgImage($this->bg_width, $this->bg_height);
    $token = $this->getToken();
    $codeImage = $this->getQRImage($token, 390, $appPath);
    //return $this->image($codeImage);

    $bgImageSize = $this->getImageSize($bg);
    $codeImageSize = $this->getImageSize($codeImage);

    // username
    $this->drawText($bg, $group->user_name, $this->username_color, $bgImageSize['width'], $this->username_fontsize, $this->username_x, $this->username_y, $this->username_linespacing);
    // group title
    $this->drawText($bg, $title, $this->title_color, $bgImageSize['width'], $this->title_fontsize, $this->title_x, $this->title_y, $this->title_linespacing);
    // group summary
    $this->drawText($bg, $summary, $this->summary_color, $bgImageSize['width'], $this->summary_fontsize, $this->summary_x, $this->summary_y, $this->summary_linespacing);

    // thumb
    $thumbX = $this->thumb_x;
    $thumbSize = $this->thumb_size;
    foreach ($images as $url) {
      $this->drawImage($bg, $url, $thumbSize, $thumbX, $this->thumb_y);
      /*
      if (preg_match("/\.png$/", $url)) {
        $img = imagecreatefrompng($url);
      } else {
        $img = imagecreatefromjpeg($url);
      }
      $size = $this->getImageSize($img);
      imagecopyresized ( $bg, $img, 
        $offsetX, $offsetY, 
        0, 0, 
        $thumbSize, $thumbSize,
        $size['width'], $size['height']
      );
       */
      $thumbX += $thumbSize + $this->thumb_spacing;
    }
    //$this->drawThumb($bg, $images, $this->thumb_size, $this->thumb_x, $this->thumb_y, $this->thumb_spacing);
    /*
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
     */
    imagecopy ( $bg, $codeImage, 367.5, 1233, 0, 0, $codeImageSize['width'], $codeImageSize['height']);
    return $this->image($bg);
  }
}

