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
    $this->middleware('crossRequest');
  }

  // post 创建
  public function store(Request $request, $groupid)
  {
    #var_dump($request->cookie('aaa'));
    #return response("")->withCookie('aaa', 'bbb');

    $err = $this->validator($request->all(), [
      'uid' => 'required'
    ]);
    if ($err !== null) {
      return $this->json(-1, $err);
    }
    // 是否有效
    $groups = DB::table('group')
      ->where('id', $groupid)
      ->get();
    if (!isset($groups[0])) {
      return $this->json(-1, "拼团不存在");
    }

    // 是否过期
    $finishtime = $groups[0]->finishtime;
    if (time() * 1000 > $finishtime) {
      return $this->json(-1, "拼团已结束");
    }

    // 是否已参加过
    $joined = DB::table('participant')
      ->where([
        'groupid' => $groupid,
        'uid' => $request->input('uid')
      ])
      ->get();
    if (isset($joined[0])) {
      return $this->json(-1, "已经参加过该团");
    }

    $joinGroup = $groups[0];
    $customFields = json_decode($joinGroup->custom_fields);
    $customValues = [];
    $customFieldsCount = count($customFields);
    if ($customFieldsCount > 0) {
      // 有自定义字段
      $customValues = $request->input('custom_values');
      if ($customFieldsCount !== count($customValues)) {
        return $this->json(-1, "卖家填写内容和需求数量不符");
      }
    }
    $participant = [
      'uid' => $request->input('uid'),
      'createtime' => time() * 1000,
      'groupid' => $groupid,
      'custom_fields' => json_encode($customFields),
      'custom_values' => json_encode($customValues)
    ];
    $id = DB::table('participant')->insertGetId($participant);
    if ($id) {
      return $this->json(0, ['id' => $id]);
    } else {
      return $this->json(-1, "参与失败");
    }

  }

  // query
  public function index(Request $request, $groupid)
  {
    // 根据创建者查询
    $pagenumber = $request->has('pagenumber') ? $request->input('pagenumber') : 1;
    $pagesize = $request->has('pagesize') ? $request->input('pagesize') : null;
    $query = DB::table('participant')
      ->where('groupid', $groupid)
      ->orderBy('createtime', 'desc');
    if ($pagesize !== null) {
      # 有分页
      $query->skip($pagesize * ($pagenumber - 1));
      $query->take($pagesize);
    }
    $participants = $query->get();

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
}
