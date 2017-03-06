<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use DB;
use Cookie;
use App\Http\Requests;

class AuditorController extends Controller
{

  public function __construct()
  {
    $this->middleware('crossRequest');
  }

  //登录
  public function index(Request $request)
  {

    $params = $request->all();
    $err = $this->validator($params, [
      'account' => 'required',
      'password' => 'required'
    ]);

    $user = DB::table('auditor')
      ->select('id', 'nickname', 'account', 'status', 'level')
      ->where('account', $params['account'])
      ->where('password', md5($params['password']))
      ->first(); 
    if ($user) {
      return $this->json(0, $user);
    }
    // root password echo md5('zhuye2015$pintuan');
    return $this->json(700);
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

    return $this->json(0, []);
  }


  // 根据token获取用户信息
  public function show(Request $request, $id)
  {
    return $this->json(0, []);
  }

  public function destroy(Request $request, $id)
  {
    return $this->json(-1, "用户不存在");
  }
}
