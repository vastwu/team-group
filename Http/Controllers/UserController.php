<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use DB;
use App\Http\Requests;

class UserController extends Controller
{
  public function __construct()
  {
    $this->middleware('crossRequest');
  }

  // post 创建
  public function index(Request $request)
  {
  }

  public function getUserByOpenid ($openid)
  {
    $user = DB::table('user')->where('openid', $openid)->first(); 
    // stcClass -> array
    $user = json_decode( json_encode( $user ),true);
    return $user;
  }
  // query no index
  public function store(Request $request)
  {
    $code = $request->input('code');
    $appId = env('WECHAT_APP_ID');
    $secret = env('WECHAT_SECRET');
    $wechatApiUrl = "https://api.weixin.qq.com/sns/jscode2session?".join('&', [
      "appid=$appId",
      "secret=$secret",
      "js_code=$code",
      "grant_type=authorization_code"
    ]);
    # mock
    $wechatResult = [
      #"errcode" => 'xx',
      #"errmsg" => 'xx',
      "session_key" => "OiP2i\/vANUo18H4RxluqSA==",
      "openid" => "oNQkY0Vxlhxb0QmbQV9urcjGIhW0--++"
    ];
    #$wechatResult = json_decode(file_get_contents($wechatApiUrl));
    if (isset($wechatResult['errcode'])) {
      return response()->json([
        "error" => $wechatResult['errcode'],
        "reason" => $wechatResult['errmsg']
      ]);
    }
    $openid = $wechatResult['openid'];
    $user = $this->getUserByOpenid($openid);

    if (!$user) {
      // 未注册过，插入
      $user = [
        'session_id' => md5(md5($openid)),
        'session_key' => $wechatResult['session_key'],
        'openid' => $openid,
        'name' => $request->input('name'),
        'avatar' => $request->input('avatar'),
        'createtime' => time() * 1000,
      ];
      $id = DB::table('user')->insertGetId($user);
      $user['isnew'] = '1';
      $user['id'] = $id;
    }
    unset($user['session_key']);
    unset($user['openid']);
    return response()->json($user);
  }

  // get with index, get one
  public function show(Request $request, $id)
  {
  }
}
