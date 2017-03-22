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
    if ($group['status'] == 0 && $group['finishtime'] <= time() * 1000) {
      // 只有正常状态的才能根据时间标记为已过期
      // 如果非正常状态的则显示被封禁的理由之类的
      $group['status'] = 1;
      if (($group['limit_amount'] > 0 && $group['total_amount'] >= $group['limit_amount']) || ($group['limit_users'] > 0 && $group['total_users'] >= $group['limit_users']) ) {
        # 达到截图标准
        $group['status'] = 2;
      }
    }
    $group['total_amount'] = $group['total_amount'] * 1;
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
      'commodities' => 'required',
      'commodities.*.price' => 'required|min:0'
    ]);

    if ($err !== null) {
      return $this->json(-1, $err);
    }
    if (!$request->has('limit_amount') && !$request->has('limit_users')) {
      return $this->json(21);
    }

    $params = $request->all();
    $createtime = time() * 1000;
    if ($params['finishtime'] <= $createtime) {
      return $this->json(22);
    }

    $saveCommodities = [];
    foreach($params['commodities'] as $item) {
      $item['count'] = 0;
      $saveCommodities[] = $item;
    }
    $gid = DB::table('group')->insertGetId([
      'title' => $params['title'],
      'userid' => $request->get('TOKEN_UID'),
      'limit_amount' => isset($params['limit_amount']) ? $params['limit_amount'] : 0,
      'limit_users' => isset($params['limit_users']) ? $params['limit_users'] : 0,
      'total_amount' => 0,
      'total_users' => 0,
      'createtime' => $createtime,
      'finishtime' => $params['finishtime'],
      'summary' => isset($params['summary']) ? $params['summary'] : '',
      'images' => json_encode(isset($params['images']) ? $params['images'] : []),
      'contact' => isset($params['contact']) ? $params['contact'] : '',
      'commodities' => json_encode($saveCommodities),
      'custom_fields' => json_encode(isset($params['custom_fields']) ? $params['custom_fields'] : []),
      'status' => 0,
      'share' => 0
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
  public function getGroupsByParticipant($participant, $pagenumber = 1, $pagesize = null) {
    // 根据参与者获取团信息
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
    $sql = DB::table('group')
      ->leftJoin('user', 'user.id', '=', 'group.userid');

    if (array_key_exists('order', $query) && $query['order']) {
      $sql->orderBy('group.'.$query['order'], array_key_exists('desc', $query) ? 'desc': 'asc');
    } else {
      // default
      $sql->orderBy('group.createtime', 'desc');
    }
    if (array_key_exists('id', $query) && $query['id']) {
      $sql->where('group.id', $query['id']);
    } else if (array_key_exists('title', $query) && $query['title']) {
      $sql->where('group.title', 'like', '%'.$query['title'].'%');
    } else {
      # 上述精准查找均不成立时，才考虑时间因素
      if (array_key_exists('createtime_start',$query) && array_key_exists('createtime_end', $query)) {
        $sql->where('group.createtime', '>=', $query['createtime_start']);
        $sql->where('group.createtime', '<=', $query['createtime_end']);
      }
    }
    
    // 先拿个总数
    $total = $sql->count();

    if ($pagesize !== null) {
      # 有分页
      $sql->skip($pagesize * ($pagenumber - 1));
      $sql->take($pagesize);
    }
    $sql->select('group.*', 'user.name as user_name', 'user.avatar as user_avatar');
    $groups = $sql->get();

    foreach($groups as $index => $group) {
      $groups[$index] = $this->decodeGroup($group);
    }
    return ['groups' => $groups, 'total' => $total];
  }

  // 订单信息和团信息合并
  public function decodeParticipant (& $group, & $p) {
    $p->custom_fields = json_decode($p->custom_fields);
    $p->custom_values = json_decode($p->custom_values);
    $p->commodities = json_decode($p->commodities);
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
      $groups = $this->getGroupsByCustom($request->all(), $pagenumber, $pagesize);
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
      $this->decodeParticipant($group, $p);
    }

    $group['participant'] = $participants;
    // 对于无订单的商品，count补0
    foreach($group['commodities'] as $commodity) {
      if (!array_key_exists('count', $commodity)) {
        $commodity->count = 0; 
      }
    }

    # 获取当前用户在该团的订单
    $uid = $request->get('TOKEN_UID');
    $currentUserParticipant = DB::table('participant')
      ->where('groupid', $id)
      ->where('uid', $uid)
      ->first();

    if ($currentUserParticipant) {
      $this->decodeParticipant($group, $currentUserParticipant);
    }
    $group['current_user_participant'] = $currentUserParticipant;
    return $this->json(0, $group);
  }

  public function update (Request $request, $id)
  {
    $isAdmin = $request->get('IS_ADMIN');
    $targetStatus = $request->input('status');
    $share = $request->has('share');
    if ($isAdmin) {
      # 管理员，不限制status修改
      $result = DB::table('group')
        ->where('id', $id)
        ->update(['status' => $targetStatus]);
      if ($result === 0) {
        return $this->json(11);
      } else {
        return $this->json(0, $result);
      }
    } else if ($request->has('finishtime')) {
      # 非管理员，结束时间更新到现在
      $uid = $request->get('TOKEN_UID');
      $result = DB::table('group')
        ->where('id', $id)
        ->first();
      if (!$result) {
        return $this->json(11);
      }
      if ($result->userid != $uid) {
        return $this->json(19);
      }
      /*
       * 不再接口限制状态
      if ($result->status != 1) {
        return $this->json(20);
      }
      */
      $result = DB::table('group')
        ->where('id', $id)
        ->update(['finishtime' => (time() - 1) * 1000]);
      return $this->json(0, $result);
    } else if ($share) {
      # 分享
      $group = DB::table('group')
        ->where('id', $id)
        ->first();
      if (!$group) {
        return $this->json(11);
      }
      $result = DB::table('group')
        ->where('id', $id)
        ->update(['share' => $group->share + 1]);
      return $this->json(0, ["share" => $group->share + 1]);
    } else {
      return $this->json(500);
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
