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

  // 根据创建者查询
  public function getGroupsByCreator($creator, $pagenumber = 1, $pagesize = null) {
    #$pagenumber = $request->has('pagenumber') ? $request->input('pagenumber') : 1;
    #$pagesize = $request->has('pagesize') ? $request->input('pagesize') : null;
    $query = DB::table('group')
      ->where('userid', $creator)
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
    return $groups;
  }
  // 追加某团的参与者信息
  public function appendGroupParticipant ($group, $limit = 5) {
    $participants = DB::table('participant')
      ->where('groupid', $group->id)
      ->orderBy('createtime', 'desc')
      ->take($limit)
      ->get();
    foreach($participants as $p) {
      $p->custom_fields = json_decode($p->custom_fields);
      $p->custom_values = json_decode($p->custom_values);
    }
    $group['participant'] = $participants;
    return $groups;
  }
  public function getGroupsByParticipant($participant, $pagenumber = 1, $pagesize = null) {
    // 根据参与者获取团信息
    #$pagenumber = $request->has('pagenumber') ? $request->input('pagenumber') : 1;
    #$pagesize = $request->has('pagesize') ? $request->input('pagesize') : null;

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
    foreach($groups as $index => $group) {
      $parsed = $this->decodeGroup($group);
      $groups[$index] = $parsed;
    }
    return $groups;
    #return $this->json(0, $groups);
  }
  public function getGroupsByCustom($query, $pagenumber, $pagesize) {
    $query = DB::table('group')
      ->leftJoin('user', 'user.id', '=', 'group.userid')
      ->orderBy('group.createtime', 'desc')
      ->select('group.*', 'user.name as user_name', 'user.avatar as user_avatar');
    
    if ($pagesize !== null) {
      # 有分页
      $query->skip($pagesize * ($pagenumber - 1));
      $query->take($pagesize);
    }
    $groups = $query->get();

    foreach($groups as $index => $group) {
      $groups[$index] = $this->decodeGroup($group);
    }
    return $groups;
  }

  // query
  public function index(Request $request)
  {
    $err = $this->validator($request->all(), [
      'type' => 'required|in:1,2,3',
      'pagenumber' => 'integer|min:0',
      'pagesize' => 'integer|min:0',
    ]);

    if ($err !== null) {
      return $this->json(-1, $err);
    }
    $pagenumber = $request->has('pagenumber') ? $request->input('pagenumber') : $request->input('pagenumber');
    $pagesize = $request->has('pagesize') ? $request->input('pagesize') : null;

    $type = $request->input('type');
    $uid = $request->get('TOKEN_UID');
    if ($type == '1') {
      $groups = $this->getGroupsByCreator($uid, $pagenumber, $pagesize);
    } else if ($type == '2') {
      $groups = $this->getGroupsByParticipant($uid, $pagenumber, $pagesize);
    } else if ($type == '3' && $request->get('IS_ADMIN')) {
      $groups = $this->getGroupsByCustom([], $pagenumber, $pagesize);
    }
    return $this->json(0, $groups);
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

    /*
    $sql = "
      select A.*, B.name user_name, B.`avatar` user_avatar from (
        select * from `group` where id = ?
      ) A
      left join `user` B
      on `A`.userid = `B`.id
    ";

    $group = DB::select($sql, [
      $id
    ]);
     */

    $groups = DB::table('group')
      ->leftJoin('user', 'user.id', '=', 'group.userid')
      ->where('group.id', $id)
      ->select('group.*', 'user.name as user_name', 'user.avatar as user_avatar')
      ->get();


    // stcClass -> array
    if (count($groups) == 0) {
      return $this->json(0, null);
    }
    $group = $this->decodeGroup($groups[0]);
    // 需要获取参团者信息, 默认获取4个
    $limit = $request->has('participant_limit') ? $request->input('participant_limit') : 4;
    $participants = DB::table('participant')
      ->leftJoin('user', 'user.id', '=', 'participant.uid')
      ->where('participant.groupid', $id)
      ->orderBy('participant.createtime', 'desc')
      ->select('participant.*', 'user.name as user_name', 'user.avatar as user_avatar')
      ->take($limit)
      ->get();

    foreach($participants as $p) {
      $p->custom_fields = json_decode($p->custom_fields);
      $p->custom_values = json_decode($p->custom_values);
      $p->commodities = json_decode($p->commodities);

      foreach($p->commodities as $i => $value) {
        $commodity = $group['commodities'][$i];
        if (isset($commodity->count)) {
          $commodity->count += $value;
        } else {
          $commodity->count = $value;
        }
      }
    }

    $group['participant'] = $participants;
    return $this->json(0, $group);
  }

  public function update (Request $request, $id)
  {
    if (!$request->get('IS_ADMIN')) {
      return $this->json(500);
    }
    $result = DB::table('group')
      ->where('id', $id)
      ->update(['status' => $request->input('status')]);
    if ($result === 0) {
      return $this->json(11);
    } else {
      return $this->json(0, $result);
    }
  }
  // 删除拼团
  public function destroy(Request $request, $id)
  {
    if (!$request->get('IS_ADMIN')) {
      return $this->json(500);
    }
    $result = DB::table('group')
      ->where('id', $id)
      ->delete();
    if ($result === 0) {
      return $this->json(11);
    } else {
      return $this->json(0, $result);
    }
  }
}
