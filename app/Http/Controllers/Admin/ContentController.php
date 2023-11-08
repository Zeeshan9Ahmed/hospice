<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContentController extends Controller
{
    public function index(){
        $contents = Content::all();
        return view('admin.contents.index',['contents'=>$contents]);
    }

    public function edit_content($id)
    {
        $contents = Content::find($id);
        return view('admin.contents.update-content',['content' => $contents]);
    }

    public function update_content(Request $request)
    {
        $controls=$request->all();
        $rules=array(
            "description"=>"required",
            "id"=>"required",
        );
        $validator=Validator::make($controls,$rules);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $content=Content::find($request->id);
        $content->description = $request->description;
        $content->save();
        return redirect()->route('admin.contents')->withSuccess('Content Added Successfully...!');
    }
}
