<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SipV2Controller extends Controller
{

    public function postSearchCallLog(Request  $request)
    {
        $user= $request->user;
        $startTime=round(microtime(true) * 1000);


        if (!$this->checkEntity($user->id, "VIEW_SIP_TRACKING")) {
            Log::info($user->email . '  TRY TO GET SipController.postCallLog WITHOUT PERMISSION');
            return response()->json(['status' => false, 'message' => "Permission denied"], 403);
        }

        $validator = Validator::make($validData, [
            'q' => 'nullable|alpha_spaces|max:50',
            'count' => 'sometimes|numeric|max:100',
            'page'=>'sometimes|numeric|max:1000',
            'direction'=>'required|in:"in","out"',
            'start_date'=>'nullable|date',
            'end_date'=>'nullable|date|after:start_date'

        ]);
        // Trả về lỗi nếu sai
        if ($validator->fails()) {
            $logDuration= round(microtime(true) * 1000)-$startTime;
            Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".$request->url()."|".json_encode($validData)."|GET_CUSTOMERS|".$logDuration."|Invalid parameter");

            return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
        }



    }
}
