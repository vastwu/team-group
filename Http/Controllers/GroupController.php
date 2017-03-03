<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use DB;
use App\Http\Requests;

class GroupController extends Controller
{
  public function __construct()
  {
    $this->middleware('auth');
    $this->middleware('crossRequest');
  }
  public function decodeGroup ($group)
  {
    $group = json_decode( json_encode( $group ), true);
    $group['images'] = json_decode($group['images']);
    // 商品
    $group['commodities'] = json_decode($group['commodities']);
    // 自定义字段
    $group['custom_fields'] = json_decode($group['custom_fields']);
    if ($group['status'] === 0 && $group['finishtime'] <= time() * 1000) {
      // 只有正常状态的才能根据时间标记为已过期
      // 如果非正常状态的则显示被封禁的理由之类的
      $group['status'] = 1;
    }
    return $group;
  }

  // post 创建
  public function store(Request $request)
  {
    $err = $this->validator($request->all(), [
      'title' => 'required',
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

    $gid = DB::table('group')->insertGetId([
      'title' => $params['title'],
      'userid' => $request->get('TOKEN_UID'),
      'limit_amount' => $params['limit_amount'],
      'limit_users' => $params['limit_users'],
      'total_amount' => 0,
      'total_users' => 0,
      'createtime' => time() * 1000,
      'finishtime' => $params['finishtime'],
      'summary' => $params['summary'],
      'images' => json_encode(isset($params['images']) ? $params['images'] : []),
      'contact' => $params['contact'],
      'commodities' => json_encode($params['commodities']),
      'custom_fields' => json_encode($params['custom_fields']),
      'status' => 0
    ]);
    if ($gid) {
      return $this->json(0, [ 'id'=> $gid]);
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
    } else if ($request->has('participant')) {
      // 根据参与者获取团信息
      $participant = $request->input('participant');
      $pagenumber = $request->has('pagenumber') ? $request->input('pagenumber') : 1;
      $pagesize = $request->has('pagesize') ? $request->input('pagesize') : null;

      $sql = "
        select * from
          (select * from `group`) A
          right join 
          (select `groupid`, createtime as jointime, custom_values, commodities as participant_commodities from participant where uid = ?) B
          on A.id = B.groupid
          order by jointime desc
      ";
      if ($pagesize !== null) {
        # 有分页
        $skip = $pagesize * ($pagenumber - 1);
        $take = $pagesize;
        $sql .= " limit $skip, $take";
      }
      $groups = DB::select($sql, [
        $participant
      ]);
      $total_price = 0;
      foreach($groups as $index => $group) {
        $group->custom_values = json_decode($group->custom_values);
        $participant_commodities = json_decode($group->participant_commodities);
        unset($group->participant_commodities);
        $parsed = $this->decodeGroup($group);
        foreach($parsed['commodities'] as $i => $item) {
          $item->count = $participant_commodities[$i];
          $total_price += $item->count * $item->price;
        }
        $parsed['total_price'] = $total_price;
        $groups[$index] = $parsed;
      }
      return $this->json(0, $groups);
    }
    return response()->json([]);
  }

  // get with index, get one
  public function show(Request $request, $id)
  {
    $err = $this->validator($request->all(), [
      'participant_limit' => 'integer|min:0'
    ]);
    if ($err !== null) {
      return $this->json(-1, $err);
    }

    $group = DB::table('group')->where('id', $id)->first();
    // stcClass -> array
    if (!$group) {
      return $this->json(0, null);
    }
    $group = $this->decodeGroup($group);
    // 需要获取参团者信息, 默认获取4个
    $limit = $request->has('participant_limit') ? $request->input('participant_limit') : 4;
    $participants = DB::table('participant')
      ->where('groupid', $id)
      ->orderBy('createtime', 'desc')
      ->take($limit)
      ->get();
    foreach($participants as $p) {
      $p->custom_fields = json_decode($p->custom_fields);
      $p->custom_values = json_decode($p->custom_values);
    }
    $group['participant'] = $participants;
    #$group['xxx'] = $request->get('TOKEN_UID');
    return $this->json(0, $group);

  }
}
