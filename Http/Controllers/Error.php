<?php

//$ErrorMessages = ['a' => 123];

namespace App\Http\Controllers;

class Error
{
    public static $rulsMessage = [
      'required' => ':attribute 字段缺失',
      'integer' => ':attribute 必须为整数',
      'in' => ':attribute 值无效'
    ];

    public static $reason = [
      // 参加
      '100' => '文件格式错误',
      '11' => '拼团不存在',
      '12' => '拼团已结束',
      '13' => '已经参加过该团',
      '14' => '卖家填写内容和需求数量不符',
      '15' => '商品数量不匹配',
      '16' => '参与失败',
      '17' => '参与订单不存在',
      '18' => '拼团状态异常',
      // 通用异常
      '400' => '用户信息缺失',
      '401' => '用户信息非法',
      // 特殊异常
      '500' => '无权限操作'
    ];

}
