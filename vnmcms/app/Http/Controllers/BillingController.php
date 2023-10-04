<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use JWTAuth;
use JWTAuthException;
use Illuminate\Support\Facades\DB;
use Validator;

class BillingController extends Controller
{



    //
    public function getBillLog($id, Request $request)
    {
        $user= $request->user;

      if (!$this->checkEntity($user->id, "VIEW_BILLING")) {
        Log::info($user->email . '  TRY TO GET BillingController.getBillLog WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission prohibit"], 403);
      }


      date_default_timezone_set('Asia/Ho_Chi_Minh');
        $errors = Validator::make($request->only('start_date', 'end_date', 'hotline_number'), [
                'start_date' => 'nullable|date|before_or_equal:today',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'take' => 'nullable|numeric',
                'page' => 'nullable|numeric',
                'hotline_number' => 'nullable|alpha_dash|exists:hot_line_config,hotline_number'
            ]
        );
        if ($errors->fails()) {
            return $this->ApiReturn($errors->errors(), false, "The given data was invalid", 422);
        }
        $datePeriod = (object)[];
        if (!$request->start_date) {
            $datePeriod->start_date = date('Y-m-d 00:00:00');
        } else {
            $datePeriod->start_date = date("Y-m-d 00:00:00", (strtotime($request->start_date)));
        }
        if (!$request->end_date) {
            $datePeriod->end_date = date('Y-m-d H:i:s');
        } else {
            $datePeriod->end_date = date("Y-m-d 23:59:59", (strtotime($request->end_date)));
        }
        $diff = date_diff(date_create($datePeriod->start_date), date_create($datePeriod->end_date));
        $dateOver = intval($diff->format("%R%a"));
        if ($dateOver > 45) {
            return $this->ApiReturn(['start_date' => 'Start date and end date not longer than 45 days'], false, "The given data was invalid", 422);
        }
        $take = !$request->input('take') ? 100 : $request->take;
        $page = !$request->input('page') ? 1 : $request->input('page');
        $skip = ($page - 1) * $take;
        $next = $page + 1;
        $prev = $page - 1;
        $cus = DB::table('customers')
            ->where('enterprise_number', $id)
            ->first();
        if (!$cus) {
            return response()->json('error', 403);
        }
        $cus_id = $cus->id;
        $queryCount = DB::table('charge_log');
        if ($request->hotline_number) {
            $queryCount->where('hotline_num', $request->hotline_number);
        }
        //$idZero = $this->removeZero($id);
        $queryCount->where('cus_id', $cus_id);
        $queryCount->whereBetween('charge_time', [$datePeriod->start_date, $datePeriod->end_date]);
        $queryCount->take($take);
        $queryCount->skip($skip);
        $queryCount->orderBy('id', 'desc');
        $resBillLog = $queryCount->get();
        $resBillAmount = $queryCount->sum("amount");
        $resBillDuration = $queryCount->sum("count");
        $countEvent = $queryCount->count();
        $totalpage = ceil($countEvent / $take);
        return response()->json(['data' => $resBillLog, 'date' => $datePeriod,
            'sum'=>$resBillAmount, 'duration'=>$resBillDuration,
            'page' => array('totalpage' => $totalpage, 'count' => $countEvent, 'skip' => $skip,
                'prev' => $prev, 'next' => $next, 'take' => $take)

        ]);
    }

    /** Rebuild billing logs  */

    public function getBillLogV2(Request $request)
    {
        $user= $request->user;


      if (!$this->checkEntity($user->id, "VIEW_BILLING")) {
        Log::info($user->email . '  TRY TO GET BillingController.getBillLog WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission prohibit"], 403);
      }


        date_default_timezone_set('Asia/Ho_Chi_Minh');
        // Default date from first day

        $errors = Validator::make($request->only('start_date', 'end_date', 'enterprise_number', 'sorting', 'page','count'), [
                'start_date' => 'nullable|date|before_or_equal:tomorrow',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'enterprise_number' => 'nullable|exists:customers,enterprise_number',
                'sorting'=>'nullable|sql_char|max:50',
                'page'=>'nullable|numeric|max:10000',
                'count'=>'nullable|numeric|max:100000',




            ]
        );
        if ($errors->fails()) {
            return $this->ApiReturn($errors->errors(), false, "The given data was invalid", 422);
        }



      $startDate = $request->start_date ? date("Y-m-d H:i:s", strtotime($request->start_date)) : date("Y-m-01 H:i:s");
      $endDate = $request->end_date ? date("Y-m-d 23:59:59", strtotime($request->end_date)) : date("Y-m-d H:i:s");

      if ($startDate < date("Y-m-d H:i:s", strtotime("-90 day"))) {
        return $this->ApiReturn(["start_date" => "No longer than  90 day from now "], false, 'The given data was invalid', 422);
      }

      if ($endDate < date("Y-m-d H:i:s", strtotime("-90 day"))) {
        return $this->ApiReturn(["end_date" => "No longer than 90 days from now "], false, 'The given data was invalid', 422);
      }


      $start = strtotime($startDate);
      $end = strtotime($endDate);

      $days_between = ceil(abs($end - $start) / 86400);

      if($days_between > 31)
      {
        return $this->ApiReturn(["end_date" => "Report limit 30 days in range "], false, 'The given data was invalid', 422);

      }

      $customerCheck= null;



      if ($user && $this->checkEntity($user->id, "VIEW_BILLING_CUSTOMER")) {
        $customerCheck = DB::table('customers')->where('account_id', $user->id)->first();

        if (!$customerCheck) {
          Log::info($user->email . '  TRY TO GET BillingController.getBillLog WITHOUT ENTERPRISE NUMBER');
          return response()->json(['status' => false, 'message' => "Permission prohibit"], 403);
        }
      }

      $enterpriseNumber = $customerCheck? $customerCheck->enterprise_number:$request->enterprise_number;

      if (!$enterpriseNumber) {
        Log::info($user->email . '  TRY TO GET BillingController.getBillLog WITHOUT ENTERPRISE NUMBER FORM');
        return response()->json(['status' => false, 'message' => "Permission prohibit"], 403);
      }


      $customer = DB::table("customers")
        ->where('enterprise_number', $enterpriseNumber)
        ->select("id")
        ->first();



        $start_date= $request->start_date?date("Y-m-d 00:00:00", (strtotime($request->start_date))):  date('Y-m-d 00:00:00');
        $end_date=$request->end_date? date("Y-m-d 23:59:59", (strtotime($request->end_date))): date('Y-m-d H:i:s');

        $enterprise= $request->enterprise_number;
        $hotline= $request->hotline_number;

        $totalPerPage= $request->count?$request->count:50;
        $page= $request->page?$request->page:1;
        $skip= ($page-1)*$totalPerPage;
        $query = $request->input('q')?$request->input('q'):null;


        $param=[$start_date, $end_date, $customer->id];
        $qsort=$request->sorting?$request->sorting:'-event_occur_time';
        $sort=$qsort[0]=='-'?"DESC":"ASC";
        $sortCol= substr($qsort,1);
        $sql = "select * from charge_log a 
                where a.insert_time between ? and ? 
                and a.cus_id= ? ";

        if($hotline)
        {
            $sql .=" AND hotline_num =?";
            array_push($param, $hotline);
        }


        array_push($param,$totalPerPage, $skip);
        $sql .="   ORDER BY " . $sortCol . "  " . $sort . "  LIMIT ? OFFSET ? ";


        $res = DB::select($sql, $param);
        $rs = DB::table('charge_log')
            ->whereBetween('insert_time', [$start_date, $end_date])
            ->where('cus_id', $customer->id);

        if($hotline)
        {
            $rs->where('hotline_num', $hotline);
        }

        $count= $rs->count();

        return $this->ApiReturn(['data'=>$res, 'count'=>$count, 'limit_download_row'=>config('sbc.row_per_file_download'),
          'date'=>['start'=>$start_date, 'end'=>$end_date]], true, null, 200);

    }
    public  function exportBillog(Request $request)
    {
        $cookie=null;
        $user= null;

        if(isset($_COOKIE["sbc"]))
        {
            $cookie = $_COOKIE["sbc"];
            $user = $this->getUserByCookie($cookie);
        }


        if (!$user) {
            return "Permission denied";
        }


      if (!$this->checkEntity($user->id, "EXPORT_BILLING")) {
        Log::info($user->email . '  TRY TO GET BillingController.exportBillog WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission prohibit"], 403);
      }




      date_default_timezone_set('Asia/Ho_Chi_Minh');
      // Default date from first day

      $errors = Validator::make($request->only('start_date', 'end_date', 'enterprise_number', 'sorting', 'page','count'), [
          'start_date' => 'nullable|date',
          'end_date' => 'nullable|date',
          'enterprise_number' => 'required|exists:customers,enterprise_number',
          'sorting'=>'nullable|sql_char|max:50',
          'page'=>'nullable|numeric|max:10000',
          'count'=>'nullable|numeric|max:100000',




        ]
      );
      if ($errors->fails()) {
        return $this->ApiReturn($errors->errors(), false, "The given data was invalid", 422);
      }






      $customer = DB::table("customers")
        ->where('enterprise_number', $request->enterprise_number)
        ->select("id")
        ->first();

      $start_date= $request->start_date?date("Y-m-d H:i:s", (strtotime($request->start_date))):  date('Y-m-d 00:00:00');
      $end_date=$request->end_date? date("Y-m-d H:i:s", (strtotime($request->end_date))): date('Y-m-d H:i:s');


      $start = strtotime($start_date);
      $end = strtotime($end_date);

      $days_between = ceil(abs($end - $start) / 86400);

      if($days_between > 31)
      {
        return $this->ApiReturn(["end_date" => "Report limit 30 days in range "], false, 'The given data was invalid', 422);

      }




      $enterprise= $request->enterprise_number;
      $hotline= $request->hotline_number;

      $totalPerPage= request('count',config('sbc.limitLog'));
      $page= $request->page?$request->page:1;
      $skip= ($page-1)*$totalPerPage;
      $totalPagge= $request->totalPage;

      if($totalPerPage > config('sbc.limitLog'))
      {
        return $this->ApiReturn(["end_date" => "Report limit 30 days in range "], false, 'The given data was invalid', 422);

      }

      $param=[$start_date, $end_date,$start_date, $end_date, $customer->id];
      $qsort=$request->sorting?$request->sorting:'-event_occur_time';
      $sort=$qsort[0]=='-'?"DESC":"ASC";
      $sortCol= substr($qsort,1);


      $sql = "select * from charge_log a 
                where a.insert_time  between ? and ?  and  a.event_occur_time between ? and ? 
                and a.cus_id= ?";

      $sqlCount= "Select count(*) total, sum(amount) totalAmount, sum(count) duration from  charge_log a 
                where  a.insert_time  between ? and ?  and  a.event_occur_time between ? and ? 
                and a.cus_id= ? ";

      if($hotline)
      {
        $sql .=" AND hotline_num =?";
        $sqlCount .=" AND hotline_num =?";
        array_push($param, $hotline);
      }



      $rescount= DB::select($sqlCount, $param);

      array_push($param,$totalPerPage, $skip);
      $sql .="   ORDER BY " . $sortCol . "  " . $sort . "  LIMIT ? OFFSET ? ";


      $res = DB::select($sql, $param);


      $prefix = DB::table("prefix_type_name")
        ->select("prefix_type_id as id", 'name')
        ->get();





        return view('exportBilling', ["data" => $res,'prefix'=>$prefix, 'total'=>$rescount[0]->total,
          'duration'=>$rescount[0]->duration,
          'sum'=>$rescount[0]->totalAmount,
          'total_page'=>$totalPagge,
          'current_page'=>$page,

          "date"=>['start_date'=>$start_date,
            'end_date'=>$end_date], "enter" => $enterprise, "i" => 1]);
    }


    public function getBillingByEntNumber($id, Request $request)
    {
      $user= $request->user;
        $errors = Validator::make($request->only('start_date', 'end_date', 'hotline_number'), [
                'start_date' => 'nullable|date|before_or_equal:today',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'hotline_number' => 'nullable|alpha_dash'
            ]
        );
        if ($errors->fails()) {
            return $this->ApiReturn($errors->errors(), false, "The given data was invalid", 422);
        }

      $errors2 = Validator::make(['enterprise_number' => $id], [
          'enterprise_number' => 'required|alpha_dash|max:250|exists:customers',
        ]
      );
      if ($errors2->fails()) {
        return $this->ApiReturn($errors2->errors(), false, "The given data was invalid", 422);
      };


      $cus = DB::table('customers')
            ->where('enterprise_number', $id)
            ->first();

      if( $this->checkEntity($user->id, "VIEW_BILLING_CUSTOMER"))
      {
        if($cus->account_id != $user->id)
        {
          // Kiểm tra có phải của account đó không nếu login user là billing account
          return $this->ApiReturn(['enterprise_number'=>['The enterprise number is invalid to your login account']], false, "The given data was invalid", 403);
        }
      }


        $datePeriod = (object)[];
        if (!$request->start_date) {
            $datePeriod->start_date = date('Y-m-01 00:00:00');
        } else {
            $datePeriod->start_date = date('Y-m-d 00:00:00', strtotime($request->start_date));
        }
        if (!$request->end_date) {
            $datePeriod->end_date = date('Y-m-d 23:59:59');
        } else {
            $datePeriod->end_date = date('Y-m-d 23:59:59', strtotime($request->end_date));
        }
//        return json_encode($datePeriod);
       $enterprise = $this->removeZero($id); //
        $sql = "SELECT SUM(amount) TOTAL,
                IFNULL(SUM(CASE WHEN event_type='000002' THEN amount ELSE 0 END), 0) VOICE_CALL,
                IFNULL(SUM(CASE WHEN event_type='000001' THEN amount ELSE 0 END), 0) SUB,
                IFNULL(SUM(CASE WHEN event_type='000003' THEN amount ELSE 0 END), 0) SMS
                from charge_log a
                where  a.cus_id= ?  and insert_time  between ? and ?   and event_occur_time between ? and ?  ";
        $param = [$cus->id, $datePeriod->start_date, $datePeriod->end_date, $datePeriod->start_date, $datePeriod->end_date];
        if ($request->hotline_number) {
            $sql .= " and hotline_num=? ";
            array_push($param, $request->hotline_number);
        }
        $rs = DB::select($sql, $param);
        // $rs= json_encode($rs[0]);
        $sumCall = $rs[0]->VOICE_CALL;
        $sumSms = $rs[0]->SMS;
        $sumSub = $rs[0]->SUB;
        $sumTotal = $rs[0]->TOTAL;


        $quanLog = DB::table('quantity_subcriber_cycle_status')
            ->select('cycle_from as cycleFrom', 'cycle_to as cycleTo', 'reserve_duration','total_reserve','type','activated')
            ->where('enterprise_number', $enterprise)
            ->orderBy('created_at', 'DESC');


        $quanLogs = $quanLog->get();


        $logs = [];
        //  $logs = $this->getBillLog($id, $request)->original;
        // $sumTotal = $sumCall + $sumSms + $sumSub;
        if ($sumTotal > 0) {
            $perCall = ($sumCall * 100) / $sumTotal;
            $perSub = ($sumSub * 100) / $sumTotal;
            $perSms = ($sumSms * 100) / $sumTotal;
        } else {
            $perCall = $perSms = $perSub = 0;
        }
        $chart = array(['name' => "CALL_FEE", 'per' => $perCall, 'sum' => $sumCall],
            ['name' => "SMS_FEE", 'per' => $perSms, 'sum' => $sumSms],
            ['name' => "SUB_FEE", 'per' => $perSub, 'sum' => $sumSub],
            ['name' => "TOTAL_FEE", 'per' => 100, 'sum' => $sumTotal]);
        return response()->json(['chart' => $chart, 'logs' => $logs, 'date' => $datePeriod,'quan'=>$quanLogs],200);

    }

    public function exportExcel($enterprise_number, Request $request)
    {
        $request->start_date = date('Y-m-1', strtotime("-1 month"));
        $request->end_date = date('Y-m-d', strtotime("last day of previous month"));
        $request->take = 50000;
        $log = $this->getBillLog($enterprise_number, $request)->original;
        return view('exportExcel', ["data" => $log, "enter" => $enterprise_number, "i" => 1]
        );
    }
}
