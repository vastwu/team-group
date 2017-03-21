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
    $participant = DB::table('participant')
      ->where('id', $pid)
      ->where('groupid', $groupid)
      ->first();

    if (!$participant) {
      return $this->json(17);
    }
    $participant->custom_values = json_decode($participant->custom_values, true);
    $participant->custom_fields = json_decode($participant->custom_fields, true);
    $participant->commodities = json_decode($participant->commodities, true);
    return $this->json(0, $participant);
  }

  // post 创建
  public function store(Request $request, $groupid)
  {
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

    $totalPrice = 0;
    foreach ($commodities as $index => $count) {
      $saveItem = [
        'name' => $joinGroupcommodities[$index]['name'],
        'price' => $joinGroupcommodities[$index]['price'],
        'count' => $count
      ];
      $totalPrice += $saveItem['price'] * $count;
      $commodities[$index] = $saveItem;
      // 团自身商品计数增加
      $joinGroupcommodities[$index]['count'] += $count;
    }
    $joinGroup->commodities = json_encode($joinGroupcommodities);

    $participant = [
      'uid' => $request->get('TOKEN_UID'),
      'createtime' => time() * 1000,
      'groupid' => $groupid,
      'commodities' => json_encode($commodities),
      'custom_fields' => json_encode($customFields),
      'custom_values' => json_encode($customValues),
      'total_price' => $totalPrice
    ];

    // 更新拼团信息
    // 人数+1
    $joinGroup->total_users++;
    // 总金额增加
    $joinGroup->total_amount += $totalPrice;

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
    $group->total_amount -= $participant->total_price;
    // 更新团金额
    $joinGroupcommodities = json_decode($group->commodities, true);
    $commodities = json_decode($participant->commodities, true);

    foreach ($commodities as $index => $item) {
      $joinGroupcommodities[$index]['count'] -= $item['count'];
    }
    $group->commodities = json_encode($joinGroupcommodities);


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
