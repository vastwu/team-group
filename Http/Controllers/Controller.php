<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

use ErrorMessages;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public $errMessage = [
      'required' => ':attribute 字段缺失',
      'integer' => ':attribute 必须为整数'
    ];

    public $errReason = [
      // 参加
      '100' => '文件格式错误',
      '11' => '拼团不存在',
      '12' => '拼团已结束',
      '13' => '已经参加过该团',
      '14' => '卖家填写内容和需求数量不符',
      '15' => '商品数量不匹配',
      '16' => '参与失败',
      '17' => '参与订单不存在'
    ];

    public function validator($inputs, $ruls) {
      $validator = \Validator::make($inputs, $ruls, $this->errMessage);
      if ($validator->fails()) {
        $err = $validator->errors()->all();   
        return $err[0];
      }
      return null;
    }
    public function json($errorCode, $result = "") {
      if ($errorCode !== 0) {
        return response()->json([
          'error' => $errorCode,
          'reason' => isset($this->errReason[$errorCode]) ? $this->errReason[$errorCode] : $result,
        ]);
      } else {
        return response()->json([
          'error' => 0,
          'result' => $result,
        ]);
      }
    }
}
