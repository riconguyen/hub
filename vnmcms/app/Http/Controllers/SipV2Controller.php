<?php

namespace App\Http\Controllers;

use App\CustomerAms;
use App\Customers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Validator;

class SipV2Controller extends Controller
{

    public function getRecentCustomer(Request  $request)
    {
        $user= $request->user;
        $isAm=$this->checkEntity($user->id, "AM");
        $lstCus=[];
        if($isAm)
        {
            $checkCusAms= CustomerAms::where('user_id',$user->id)->select('cus_id')->get();

            foreach ($checkCusAms as $am)
            {
                $lstCus[]= $am->cus_id;
            }

        }


        $sql= "select enterprise_number, id from customers where blocked in (0,1) ";
        if(count($lstCus)>0)
        {
            $lstCusTxt= implode(",", $lstCus);

            $sql .= " AND id in ($lstCusTxt)";
        }

        $sql .= " ORDER BY updated_at DESC  LIMIT 0, 40";

        $lstCustomers= DB::select($sql);



        return response()->json(['lstData'=>$lstCustomers],200);


    }
    public function postSearchCallLog(Request  $request)
    {
        $user= $request->user;
        $startTime=round(microtime(true) * 1000);


        if (!$this->checkEntity($user->id, "VIEW_SIP_TRACKING_V2")) {
            Log::info($user->email . '  TRY TO GET SipController.postCallLog WITHOUT PERMISSION');
            return response()->json(['status' => false, 'message' => "Permission denied"], 403);
        }

        $validator = Validator::make(request()->all(), [
            'caller' => 'required|alpha_spaces|max:50',
            'count' => 'sometimes|numeric|max:100',
            'page'=>'sometimes|numeric|max:1000',
            'type'=>'required|in:"1","2"',
            'start_date'=>'nullable|date',
            'end_date'=>'nullable|date|after:start_date',
            'enterprise_number'=>'required|max:50',

        ]);
        // Trả về lỗi nếu sai
        if ($validator->fails()) {
            $logDuration= round(microtime(true) * 1000)-$startTime;
            Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".$request->url()."|".json_encode(request()->all())."|GET_CUSTOMERS|".$logDuration."|Invalid parameter");

            return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
        }


        $enterpriseNumber= request('enterprise_number', null);
        $caller= request('caller', null);
        $startDate= request('start_date', date("Y-m-01 00:00:00"));
        $endDate= request('end_date', date('Y-m-d H:i:s'));
        $type= request('type',1);

        $page= request('page', 1);
        $count= request('count', 20);

        $start= ($page-1)*$count;



        $sqlHotline= "select hlc.hotline_number  from  hot_line_config hlc where cus_id= (select id from customers where enterprise_number =? and blocked in (0,1))  and status in (0,1) ";

        $lstHotline= DB::select($sqlHotline, [$enterpriseNumber]);





        if(count($lstHotline)==0)
        {
            Log::info("Not found customers or any valid hotlines on  $enterpriseNumber ");
            return $this->ApiReturn(['enterprise_number'=>['message'=>'Not found customers or any valid hotlines on '.$enterpriseNumber]], false, 'The given data was invalid', 422);
        }

        $customer= Customers::where('enterprise_number', $enterpriseNumber)->whereIn('blocked',[0,1])->first();

        $lstHotlineTxt= [];
        foreach ($lstHotline as $line)
        {
            $lstHotlineTxt[]= $line->hotline_number;
        }

        $lstHotlineTxt = '"' . implode('","', $lstHotlineTxt) . '"';

        $table= new \stdClass();
        $table->vendor= "sbc.cdr_vendors";
        $table->vendor_ex= "sbc.cdr_vendors_extention";
        $connectField="  a.connect_time,  a.quality_mos,
         a.quality_largest_jb,
         a.quality_jitter_burst_rate,
         a.duration,
         a.charge_status,
         'success' AS state,
         null as  reject_cause
         ";

        if($type==2)
        {
            $table->vendor='sbc.cdr_vendors_failed';
            $table->vendor_ex='sbc.cdr_vendors_failed_extention';
            $connectField=" NULL  as  connect_time,
             NULL  as quality_mos,
             NULL as quality_largest_jb,
             NULL as duration,
             NULL as quality_jitter_burst_rate,
             NULL as charge_status,
             'failed' AS state,
              x.reject_cause
             ";
        }




        $sql="SELECT
         a.CLI, 
         a.CLD, 
         a.setup_time, 
         $connectField,
         a.call_id, 
         a.disconnect_time, 
         a.disconnect_cause, 
         a.from_network_ip, 
         a.des_network_ip,
         x.call_brandname            
         ";

        $sqlCount="select count(*) total ";


        $sqlFrom ="  FROM $table->vendor a LEFT JOIN $table->vendor_ex x on a.call_id= x.call_id
            WHERE 
            a.setup_time between ? AND ? 
           AND a.CLI in ($lstHotlineTxt)
           AND (a.CLD =? or a.CLI=?)  ";

        $param=[$startDate, $endDate, $caller,$caller];

        $total= DB::select($sqlCount. $sqlFrom, $param );


        array_push($param, $start, $count);
        $sqlFrom .=" LIMIT ?, ?";
        $lstData= DB::select($sql. $sqlFrom, $param);

        return response()->json(['call_history' => $lstData, 'count'=>  $total[0]->total, 'client' => $customer, 'hotline'=>$lstHotlineTxt,  'start_date' => $startDate, 'end_date' => $endDate]);
    }



}
