<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use DB;
use Cookie;
use App\Http\Requests;

class AuditorController extends Controller
{

  public $rootToken = '2cc4d8f81bfdbdda3193cd57d7ce34fc';

  public function __construct()
  {
    $this->middleware('crossRequest');
    $this->rootToken = '2cc4d8f81bfdbdda3193cd57d7ce34fc';
  }

  //登录
  public function index(Request $request)
  {
    if ($request->cookie('session_key')) {
      // 已经有cookie，校验登录
      $session_key = $request->cookie('session_key');
      $user = DB::table('auditor')
        ->select('id', 'nickname', 'account', 'status', 'password', 'level')
        ->where('session_key', $session_key)
        ->first(); 
      if ($user && $session_key === md5(md5($user->account).md5($user->password)) ) {
        $user->isAdmin = $user->level === 99 ;
        unset($user->password);
        unset($user->level);
        $user->rootToken = $this->rootToken;
        return $this->json(0, $user);
      } else {
        return $this->json(701)->withCookie('session_key', '', -1);
      }
    } else {
      $params = $request->all();
      $err = $this->validator($params, [
        'account' => 'required',
        'password' => 'required'
      ]);
      if ($err) {
        return $this->json(-1, $err);
      }

      $md5pwd = md5($params['password']);
      $user = DB::table('auditor')
        ->select('id', 'nickname', 'account', 'status', 'level')
        ->where('account', $params['account'])
        ->where('password', $md5pwd)
        ->first(); 

      if ($user) {
        $session_key = md5(md5($params['account']).md5($md5pwd));
        DB::table('auditor')
          ->where('id', $user->id)
          ->update(['session_key' => $session_key]);
        // 有效期24小时
        $user->rootToken = $this->rootToken;
        $user->isAdmin = $user->level === 99 ;
        unset($user->level);
        return $this->json(0, $user)->withCookie('session_key', $session_key, 24 * 60);
      }
      return $this->json(700);
    }
  }

  // post create
  public function store(Request $request)
  {
    $params = $request->all();
    $err = $this->validator($params, [
      'account' => 'required',
      'password' => 'required',
      'account' => 'required'
    ]);
    if ($err) {
      return $this->json(-1, $err);
    }
    $exists = DB::table('auditor')
      ->where("account", $params['account'])
      ->orWhere("nickname", $params['nickname'])
      ->count();
    if ($exists > 0) {
      return $this->json(702);
    }
    DB::table('auditor')->insert([
      'account' => $params['account'],
      'nickname' => $params['nickname'],
      'password' => md5($params['password']),
      'status' => 1,
      'createtime' => time() * 1000,
      'level' => 1
    ]);
    return $this->json(0, []);
  }

  // 根据token获取用户信息
  public function show(Request $request, $id)
  {
    $users = DB::table('auditor')
        ->select('id', 'nickname', 'account', 'status')
        ->get();
    return $this->json(0, $users);
  }
  public function update(Request $request, $id)
  {
    $params = $request->all();
    $err = $this->validator($params, [
      'password' => 'required',
    ]);
    if ($err) {
      return $this->json(-1, $err);
    }
    DB::table('auditor')
        ->where('id', $id)
        ->update([
          'password' => md5($request->input('password')),
          'session_key' => ''
        ]);
    return $this->json(0, []);
  }

  public function destroy(Request $request, $id)
  {
    return $this->json(0)->withCookie('session_key', '', -1);
  }
}
