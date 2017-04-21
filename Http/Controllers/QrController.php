<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DB;
use App\Http\Requests;

class QrController extends Controller
{
  private $font = "";

  public $distFileDir;

  private $thumb_x = 75;
  private $thumb_y = 901.5;
  private $thumb_size = 180;
  private $thumb_spacing = 22.5;

  private $bg_width = 1125;
  private $bg_height = 2001;

  private $username_fontsize = 35;
  private $username_color = [57, 57, 57];
  private $username_linespacing = 60;
  private $username_x = 201;
  private $username_y = 82.5;

  private $avatar_size = 90;
  private $avatar_x = 75;
  private $avatar_y = 75;

  private $title_fontsize = 43;
  private $title_color = [57, 57, 57];
  private $title_linespacing = 90;
  private $title_x = 75;
  private $title_y = 270;

  private $summary_fontsize = 35;
  private $summary_color = [57, 57, 57];
  private $summary_linespacing = 72;
  private $summary_x = 75;
  private $summary_y = 480;

  public function __construct()
  {
    $this->font = __DIR__.'/msyh.ttf';
    $this->distFileDir = public_path('uploads');
    $this->distFileDir = $this->distFileDir.'/qr';
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
    imagecopyresampled ( $bg, $img, 
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
    $isMock = false;
    //$path = 'pages/index?query=1';
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
    imagecopyresampled ( $img, $srcImg, 
      0, 0,  // dist
      10, 10, // src
      $size, $size, // dist
      $cropSize, $cropSize // src
    );
    return $img;
  }
  public function drawImageByUrl($bg, $url, $thumbSize, $offsetX, $offsetY) {
    /*
    if (preg_match("/\.png$/", $url)) {
      $img = imagecreatefrompng($url);
    } else {
      $img = imagecreatefromjpeg($url);
    }
     */

    $ch = curl_init();
    curl_setopt ($ch, CURLOPT_URL, $url);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT,10);
    $img = curl_exec($ch);
    $img = imagecreatefromstring($img);

    $size = $this->getImageSize($img);
    imagecopyresampled ( $bg, $img, 
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
  public function drawLine($image, $x1, $y1, $x2, $y2, $color = [54, 54, 54]) {
    imagedashedline($image, $x1, $y1, $x2, $y2, $color);
  }
  public function drawText($image, $text, $color, $maxWidth, $fontSize, $offsetX, $offsetY, $lineHeight, $bolder = false) {
    // 分配颜色
    $textcolor = imagecolorallocate($image, $color[0], $color[1], $color[2]);
    $font = $this->font;
    $acceptWidth = $maxWidth - $offsetX * 2;
    $rowText = "";
    $x = $offsetX;
    $y = $offsetY + $lineHeight;
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
        if ($bolder) {
          imagettftext($image, $fontSize, 0, $x - 0.5, $y - 0.5, $textcolor, $font, $rowText);
          imagettftext($image, $fontSize, 0, $x + 0.5, $y + 0.5, $textcolor, $font, $rowText);
        }
        $rowText = "";
        $y += $lineHeight;
      }
    }
    if (mb_strlen($rowText) > 0) {
      imagettftext($image, $fontSize, 0, $x, $y, $textcolor, $font, $rowText);
      if ($bolder) {
        imagettftext($image, $fontSize, 0, $x - 0.5, $y - 0.5, $textcolor, $font, $rowText);
        imagettftext($image, $fontSize, 0, $x + 0.5, $y + 0.5, $textcolor, $font, $rowText);
      }
    }
    $lastBox = imagettfbbox($fontSize, 0, $font, $rowText);
    $lastWidth = $lastBox[2] - $lastBox[0];
    return [
      "x" => $x,
      "y" => $y,
      "width" => $lastWidth
    ];
  }
  public function show (Request $request) 
  {
  }
  public function index (Request $request) 
  {
    $gid = $request->get('gid');
    $appPath = $request->get('path');
    // 强制更新
    $force = $request->get('force');
    $width = $request->get('width');
    $height = $request->get('height');

    $filename = $this->distFileDir.'/'.urlencode($appPath)."_$gid.png";
    if (file_exists($filename) && $force !== "1") {
      $img = imagecreatefrompng($filename);
    } else {
      $img = $this->redraw($gid, $appPath);
      imagepng($img, $filename);
    }
    if ($width && $height) {
      $img = $this->fitSize($img, $width, $height);
    }
    return $this->image($img);
  }
  // 适配尺寸
  public function fitSize ($sourceImage, $targetWidth, $targetHeight)
  {
    $targetRatio = $targetWidth / $targetHeight;
    $defaultRatio = $this->bg_width / $this->bg_height;
    if ($targetRatio > $defaultRatio) {
      // 所需的宽高比大于默认比例
      // 以高为基准绘制
      $drawHeight = $targetHeight;
      $drawWidth = $defaultRatio * $drawHeight;
      $drawX = ($targetWidth - $drawWidth) / 2;
      $drawY = 0;
    } else {
      $drawWidth = $targetWidth;
      $drawHeight = $drawWidth / $defaultRatio;
      $drawX = 0;
      $drawY = ($targetHeight - $drawHeight) / 2;
    }
    $distImage = imagecreatetruecolor($targetWidth, $targetHeight);
    $white = imagecolorallocate($distImage, 255, 255, 255);
    imagefill($distImage, 0, 0, $white);
    imagecopyresampled ( $distImage, $sourceImage, 
      $drawX, $drawY, 
      0, 0, 
      $drawWidth, $drawHeight,
      $this->bg_width, $this->bg_height
    );
    return $distImage;
  }
  // 绘图
  public function redraw ($gid, $appPath) 
  {
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

    $bgImageSize = $this->getImageSize($bg);

    // username
    $drawPosition = $this->drawText($bg, "$group->user_name", $this->username_color, $bgImageSize['width'], $this->username_fontsize, $this->username_x, $this->username_y, $this->username_linespacing, true);
    $this->drawText($bg, "发起的拼团", $this->username_color, $bgImageSize['width'], $this->username_fontsize, $drawPosition['x'] + $drawPosition['width'] + 30, $this->username_y + 1, $this->username_linespacing);
    $this->drawLine($bg, 0, 225, $bgImageSize['width'], 225, 0);
    // avatar
    $avatarUrl = preg_replace("/\/0$/", "/64", $group->user_avatar);
    $this->drawImageByUrl($bg, $avatarUrl, $this->avatar_size, $this->avatar_x, $this->avatar_y);
    // group title
    $drawPosition = $this->drawText($bg, $title, $this->title_color, $bgImageSize['width'], $this->title_fontsize, $this->title_x, $this->title_y, $this->title_linespacing, true);
    // group summary
    $summary_y = $drawPosition["y"] + 30;
    $this->drawText($bg, $summary, $this->summary_color, $bgImageSize['width'], $this->summary_fontsize, $this->summary_x, $summary_y, $this->summary_linespacing);

    // thumb
    $thumbX = $this->thumb_x;
    $thumbSize = $this->thumb_size;
    foreach ($images as $url) {
      $this->drawImageByUrl($bg, $url, $thumbSize, $thumbX, $this->thumb_y);
      $thumbX += $thumbSize + $this->thumb_spacing;
    }
    $codeImage = $this->getQRImage($token, 390, $appPath);
    $codeImageSize = $this->getImageSize($codeImage);
    imagecopy ( $bg, $codeImage, 367.5, 1233, 0, 0, $codeImageSize['width'], $codeImageSize['height']);

    return $bg;
  }
  private function saveFile ($filename, $image) {
    imagepng($image, $filename);
  }
}

