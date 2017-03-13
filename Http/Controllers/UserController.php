<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use DB;
use Cookie;
use App\Http\Requests;

class UserController extends Controller
{


  public function __construct()
  {
    $this->middleware('crossRequest');
  }

  public function getUser($key, $value)
  {
    $user = DB::table('user')->where($key, $value)->first(); 
    // stcClass -> array
    $user = json_decode( json_encode( $user ),true);
    return $user;
  }
  // post create
  public function store(Request $request)
  {
    $params = $request->all();
    $err = $this->validator($params, [
      'code' => 'required',
      'name' => 'required',
      'avatar' => 'required'
    ]);

    if ($err !== null) {
      return $this->json(-1, $err);
    }

    $code = $params['code'];
    $appId = env('WECHAT_APP_ID');
    $secret = env('WECHAT_SECRET');
    $wechatApiUrl = "https://api.weixin.qq.com/sns/jscode2session?".join('&', [
      "appid=$appId",
      "secret=$secret",
      "js_code=$code",
      "grant_type=authorization_code"
    ]);
    if ($code === "user_code") {
      # mock
      $wechatResult = [
        #"errcode" => 'xx',
        #"errmsg" => 'xx',
        "session_key" => "OiP2i\/vANUo18H4RxluqSA==",
        "openid" => "oNQkY0Vxlhxb0QmbQV9urcjGIhW0--++"
      ];
    } else {
      $wechatResult = json_decode(file_get_contents($wechatApiUrl), true);
    }
    if (isset($wechatResult['errcode'])) {
      return response()->json([
        "error" => $wechatResult['errcode'],
        "reason" => $wechatResult['errmsg']
      ]);
    }
    $openid = $wechatResult['openid'];
    $user = $this->getUser('openid', $openid);
    $now = time() * 1000;
    if ($user) {
      // 注册过，更新
      $user['name'] = $params['name'];
      $user['avatar'] = $params['avatar'];
      $user['updatetime'] = $now;
      $user['session_key'] = $wechatResult['session_key'];
      DB::table('user')
        ->where('id', $user['id'])
        ->update($user);
    } else {
      // 未注册过，插入
      $user = [
        // token 就是 加密后的3rd_session_id
        'token' => md5(md5($openid)),
        'session_key' => $wechatResult['session_key'],
        'openid' => $openid,
        'name' => $params['name'],
        'avatar' => $params['avatar'],
        'createtime' => $now,
        'updatetime' => $now
      ];
      $id = DB::table('user')->insertGetId($user);
      $user['isnew'] = '1';
      $user['id'] = $id;
    }
    unset($user['session_key']);
    unset($user['openid']);
    Cookie::queue("TOKEN", $user['token'], 3600);
    return $this->json(0, $user);
  }

  // 根据token获取用户信息
  public function show(Request $request, $id)
  {
    $user = $this->getUser('token', $id);
    unset($user['session_key']);
    unset($user['openid']);
    return $this->json(0, $user);
  }
  public function destroy(Request $request, $id)
  {
    $token = $request->input('token');
    if ($token !== '2cc4d8f81bfdbdda3193cd57d7ce34fc') {
      // 非法admin
      return $this->json(500);
    }
    $result = DB::table('user')->where('id', $id)->delete();
    if ($result) {
      return $this->json(0, $result);
    } else {
      return $this->json(-1, "用户不存在");
    }
  }
}
