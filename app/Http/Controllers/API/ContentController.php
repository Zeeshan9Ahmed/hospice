<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContentController extends Controller
{
    public function content(Request $request){

        $type =  $request->type;

        $content = DB::table('contents')->where("type","=",$type)->first();

        if(!empty($content)){
            return response()->json([
                'status' => 1,
                "message" =>"content",
                'data' => $content,
            ]);
        }

        return response()->json([
            'status' => 1,
            "message" =>"Content text not found...!",

            'data' => $content,
        ]);
    }
}
