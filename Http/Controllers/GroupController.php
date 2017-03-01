<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use DB;
use App\Http\Requests;
/*
 * TODO
 * 需要整合参团信息
 * */
class GroupController extends Controller
{
  public function __construct()
  {
    $this->middleware('crossRequest');
  }
  public function decodeGroup (& $group)
  {
    $group = json_decode( json_encode( $group ), true);
    $group['images'] = json_decode($group['images']);
    // 商品
    $group['commodities'] = json_decode($group['commodities']);
    // 自定义字段
    $group['custom_fields'] = json_decode($group['custom_fields']);
    return $group;
  }

  // post 创建
  public function store(Request $request)
  {
    $err = $this->validator($request->all(), [
      'title' => 'required',
      'userid' => 'required',
      'limit_amount' => 'min:0',
      'limit_users' => 'min:0',
      'finishtime' => 'required',
      'summary' => 'required',
      'contact' => 'required',
      'commodities' => 'required',
      'commodities.*.price' => 'required|integer|min:0',
      'custom_fields' => 'required'
    ]);

    if ($err !== null) {
      return $this->json(-1, $err);
    }

    $params = $request->all();

    $uid = DB::table('group')->insertGetId([
      'title' => $params['title'],
      'userid' => $params['userid'],
      'limit_amount' => $params['limit_amount'],
      'limit_users' => $params['limit_users'],
      'createtime' => time() * 1000,
      'finishtime' => $params['finishtime'],
      'summary' => $params['summary'],
      'images' => json_encode(isset($params['images']) ? $params['images'] : []),
      'contact' => $params['contact'],
      'commodities' => json_encode($params['commodities']),
      'custom_fields' => json_encode($params['custom_fields']),
      'status' => 0
    ]);
    if ($uid) {
      return $this->json(0, [ 'uid'=> $uid]);
    } else {
      return $this->json(-1, $result);
    }
  }

  // query
  public function index(Request $request)
  {
    $err = $this->validator($request->all(), [
      'pagenumber' => 'integer|min:0',
      'pagesize' => 'integer|min:0',
    ]);

    if ($err !== null) {
      return $this->json(-1, $err);
    }

    if ($request->has('creator')) {
      // 根据创建者查询
      $pagenumber = $request->has('pagenumber') ? $request->input('pagenumber') : 1;
      $pagesize = $request->has('pagesize') ? $request->input('pagesize') : null;
      $query = DB::table('group')
        ->where('userid', $request->input('creator'))
        ->orderBy('createtime', 'desc');
      if ($pagesize !== null) {
        # 有分页
        $query->skip($pagesize * ($pagenumber - 1));
        $query->take($pagesize);
      }
      $groups = $query->get();

      foreach($groups as $index => $group) {
        $groups[$index] = $this->decodeGroup($group);
      }
      return $this->json(0, $groups);
    }
    return response()->json([]);
  }

  // get with index, get one
  public function show(Request $request, $id)
  {
    $group = DB::table('group')->where('id', $id)->first();
    // stcClass -> array
    if ($group) {
      $this->decodeGroup($group);
      return $this->json(0, $group);
    } else {
      return $this->json(0, null);
    }

  }
}
