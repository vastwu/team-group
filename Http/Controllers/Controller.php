<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public $errMessage = [
      'required' => ':attribute 字段缺失',
      'integer' => ':attribute 必须为整数'
    ];

    public function validator($inputs, $ruls) {
      $validator = \Validator::make($inputs, $ruls, $this->errMessage);
      if ($validator->fails()) {
        $err = $validator->errors()->all();   
        return $err[0];
      }
      return null;
    }
    public function json($errorCode, $result) {
      if ($errorCode !== 0) {
        return response()->json([
          'error' => $errorCode,
          'reason' => $result
        ]);
      } else {
        return response()->json([
          'error' => 0,
          'result' => $result
        ]);
      }
    }
}
