<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Controllers\Error;
#require app_path().'/Http/Controllers/ErrorMessages.php';


class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function validator($inputs, $ruls) {
      $validator = \Validator::make($inputs, $ruls, Error::$rulsMessage);
      if ($validator->fails()) {
        $err = $validator->errors()->all();   
        return $err[0];
      }
      return null;
    }
    public function image($image) {
      //imagejpeg($image);
      ob_start();
      imagepng($image);
      $content = ob_get_contents();
      imagedestroy($image);
      ob_end_clean();
      return response($content)->header('Content-Type', 'image/png'); 
    }
    public function json($errorCode, $result = "") {
      if ($errorCode !== 0) {
        $reason = Error::$reason;
        return response()->json([
          'error' => $errorCode,
          'reason' => array_key_exists($errorCode, $reason) ? $reason[$errorCode] : $result
        ]);
      } else {
        return response()->json([
          'error' => 0,
          'result' => $result,
        ]);
      }
    }
}
