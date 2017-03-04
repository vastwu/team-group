<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

class UploadController extends Controller
{
  public $distFileDir;
  public function __construct()
  {
    $this->distFileDir = public_path('uploads');
    $this->middleware('auth');
  }
  public function store (Request $request) 
  {
    $files = $request->file();
    $return = [];
    foreach($files as $file){
      $mimeTye = $file->getMimeType();
      $ext = $file->getClientOriginalExtension();
      if($mimeTye != 'image/jpeg'){
        return $this->json(100);
      }
      $filename = date('Ymdhis').md5(rand(1,500)).'.'.$ext;
      $file->move($this->distFileDir.'/images', $filename);
      $imagePath = '/uploads/images/'.$filename;
      $url = env('APP_URL').$imagePath;
      $return[] = $url;
    }
    if ($request->input('iframe') == 1) {
      return response("<script>parent.postMessage && parent.postMessage(".json_encode($return).", '*');</script>");
      //$this->json(0, $return);
    } else {
      // 常规返回
      return $this->json(0, $return);
    }
  }
}

