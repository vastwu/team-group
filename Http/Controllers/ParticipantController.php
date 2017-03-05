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
class ParticipantController extends Controller
{
  public function __construct()
  {
    $this->middleware('auth');
    $this->middleware('crossRequest');
  }

  // 根据商品价格和购买数量计算总价
  public function sumAmountByCommodity($joinGroupcommodities, $commoditiesCount)
  {
    // 计算总金额
    $total_amount = 0;
    foreach($joinGroupcommodities as $index => $commodity) {
      $total_amount += $commodity['price'] * $commoditiesCount[$index];
    }
    return $total_amount;
  }
  // query
  public function index(Request $request, $groupid)
  {
    // 根据创建者查询
    $pagenumber = $request->has('pagenumber') ? $request->input('pagenumber') : 1;
    $pagesize = $request->has('pagesize') ? $request->input('pagesize') : null;
    $query = DB::table('participant')
      ->where('participant.groupid', $groupid)
      ->leftJoin('user', 'participant.uid', '=', 'user.id')
      ->orderBy('participant.createtime', 'desc');
    if ($pagesize !== null) {
      # 有分页
      $query->skip($pagesize * ($pagenumber - 1));
      $query->take($pagesize);
    }
    $participants = $query->get();
    foreach($participants as $item){
      $item->custom_values = json_decode($item->custom_values, true);
      $item->custom_fields = json_decode($item->custom_fields, true);
      $item->commodities = json_decode($item->commodities, true);
    }
    return $this->json(0, $participants);
  }
  // get with index, get one
  public function show(Request $request, $groupid, $pid)
  {
    return $this->json(0, [
      'action' => 'show',
      'gid' => $groupid
    ]);
  }

  // post 创建
  public function store(Request $request, $groupid)
  {
    #var_dump($request->cookie('aaa'));
    #return response("")->withCookie('aaa', 'bbb');

    // 是否有效
    $groups = DB::table('group')
      ->where('id', $groupid)
      ->get();
    if (!isset($groups[0])) {
      // 不存在
      return $this->json(11);
    }
    $joinGroup = $groups[0];
    // 是否过期
    $finishtime = $joinGroup->finishtime;
    if (time() * 1000 > $finishtime) {
      // 已过期
      return $this->json(12);
    }
    // 状态是否正常
    if ($joinGroup->status != 0) {
      return $this->json(18);
    }
    // 是否已参加过
    $joined = DB::table('participant')
      ->where([
        'groupid' => $groupid,
        'uid' => $request->get('TOKEN_UID')
      ])
      ->get();
    if (isset($joined[0])) {
      // 已经参加过了
      return $this->json(13);
    }

    $customFields = json_decode($joinGroup->custom_fields, true);
    $customValues = [];
    $customFieldsCount = count($customFields);
    if ($customFieldsCount > 0) {
      // 有自定义字段
      $customValues = $request->input('custom_values');
      if ($customFieldsCount !== count($customValues)) {
        return $this->json(14);
      }
    }

    // 检查商品情况
    $joinGroupcommodities = json_decode($joinGroup->commodities, true);
    $commodities = $request->input('commodities');
    if (count($joinGroupcommodities) !== count($commodities)) {
      return $this->json(15);
    }

    $participant = [
      'uid' => $request->get('TOKEN_UID'),
      'createtime' => time() * 1000,
      'groupid' => $groupid,
      'commodities' => json_encode($commodities),
      'custom_fields' => json_encode($customFields),
      'custom_values' => json_encode($customValues)
    ];

    // 更新拼团信息
    // 人数+1
    $joinGroup->total_users++;
    // 计算总金额
    $total_amount = $this->sumAmountByCommodity($joinGroupcommodities, $commodities);
    $joinGroup->total_amount += $total_amount;

    $error = null;
    try{
      DB::beginTransaction();
      $id = DB::table('participant')->insertGetId($participant);
      if ($id) {
        // 拼团参加成功，更新group组的钱数和人数
        DB::table('group')
          ->where('id', $joinGroup->id)
          ->update(json_decode(json_encode($joinGroup), true)); // to array
      } else {
        $error = "创建失败";
        DB::rollBack(); 
      }
      DB::commit();
    } catch (Exception $e) {
      DB::rollBack(); 
      $error = $e->getMessage();
    }

    if ($error) {
      return $this->json(16);
    } else {
      return $this->json(0, ['id' => $id]);
    }
  }

  // 移除参团订单
  public function destroy(Request $request, $groupid, $id)
  {
    if (!$request->get('IS_ADMIN')) {
      return $this->json(500);
    }

    // 拼团是否存在
    $participant = DB::table('participant')
      ->where('id', $id)
      ->where('groupid', $groupid)
      ->first();
    if (!$participant) {
      // 不存在
      return $this->json(17);
    }
    // 是否有效
    $group = DB::table('group')
      ->where('id', $groupid)
      ->first();
    if (!$group) {
      // 不存在
      return $this->json(11);
    }

    // 更新团数据
    $group->total_users--;
    // 更新团金额
    $joinGroupcommodities = json_decode($group->commodities, true);
    $commodities = json_decode($participant->commodities, true);
    $group->total_amount -= $this->sumAmountByCommodity($joinGroupcommodities, $commodities);

    $error = null;
    // 移除订单
    try{
      DB::beginTransaction();
      $result = DB::table('participant')
        ->where('id', $id)
        ->delete();
      DB::table('group')
        ->where('id', $group->id)
        ->update(json_decode(json_encode($group), true));
      DB::commit();
    } catch (Exception $e) {
      DB::rollBack(); 
      $error = $e->getMessage();
    }
    if ($error) {
      return $this->json(-1, $error);
    } else {
      return $this->json(0, $result);
    }
  }
}
