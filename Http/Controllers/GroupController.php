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
    $this->middleware('crossRequest');
  }

  // post 创建
  public function store(Request $request)
  {
    $name = $request->input('name');
    $body = $request->all();
    $result = $users = DB::insert('
      insert into `group`
        (`title`, limit_amount, limit_users, createtime, finishtime, summary, images, contact, commodities, custom_fields, `status`) 
        values 
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
          $body['title'],
          $body['limit_amount'],
          $body['limit_users'],
          time() * 1000,
          $body['finishtime'],
          $body['summary'],
          json_encode($body['images']),
          $body['contact'],
          json_encode($body['commodities']),
          json_encode($body['custom_fields']),
          0
        ]);
    if ($result === true) {
      return response()->json([
        'error' => 0
      ]);
    } else {
      return response()->json([
        'error' => -1,
        'reason' => $result
      ]);
    }
  }

  // query no index
  public function index(Request $request)
  {
    $content = [
      'api' => 'index',
      'error' => 0
    ];
    return response()->json($content);
  }

  // get with index, get one
  public function show(Request $request, $id)
  {
    $result = $users = DB::select('select * from `group` where id = ?', [$id]);
    if ($result) {
      $group = $result[0];
      $group->{'images'} = json_decode($group->{'images'});
      // 商品
      $group->{'commodities'} = json_decode($group->{'commodities'});
      // 自定义字段
      $group->{'custom_fields'} = json_decode($group->{'custom_fields'});
      return response()->json([
        'error' => 0,
        'result' => $group
      ]);
    } else {
      return response()->json([
        'error' => 0,
        'result' => null
      ]);
    }

  }
}
