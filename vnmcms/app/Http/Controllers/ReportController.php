<?php
namespace App\Http\Controllers;

use App\CustomerAms;
use App\Customers;
use App\Hotlines;
use function date_format;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use function array_push;
use function intval;
use function response;
use stdClass;

class ReportController extends Controller
{
//postViewReportConnectRating
    private function filterDateRange($r)
    {
        $range = $r['datePeriod'];
        if (!isset($r['end_date'])) {
            $end_date = date('Y-m-d H:i:s');
        } else {
            $end_date = $r['end_date'];
        }
        switch ($range) {
            case 'day':
                $dateResult = (object)['start_date' => date('Y-m-d 00:00:00'), 'end_date' => date("Y-m-d H:i:s")];
                break;
            case 'week':
                $day = date_create(date('Y-m-d H:i:s'));
                date_modify($day, "-1 week");
                $dateResult = (object)['start_date' => date_format($day, 'Y-m-d H:i:s'), 'end_date' => date("Y-m-d H:i:s")];
                break;
            case 'month':
                $day = date_create(date('Y-m-d H:i:s'));
                date_modify($day, "-1 month");
                $dateResult = (object)['start_date' => date_format($day, 'Y-m-d H:i:s'), 'end_date' => date("Y-m-d H:i:s")];
                break;
            case 'quarter':
                $day = date_create(date('Y-m-d H:i:s'));
                date_modify($day, "-3 months");
                $dateResult = (object)['start_date' => date_format($day, 'Y-m-d H:i:s'), 'end_date' => date("Y-m-d H:i:s")];
                break;
            case 'year':
                $day = date_create(date('Y-m-d'));
                date_modify($day, "-1 year");
                $dateResult = (object)['start_date' => date_format($day, 'Y-m-d H:i:s'), 'end_date' => date("Y-m-d H:i:s")];
                break;
            case 'y2d':
                $dateResult = (object)['start_date' => date('Y-01-01 00:00:00'), 'end_date' => date("Y-m-d H:i:s")];
                break;
            case 'manual':
                $dateResult = (object)['start_date' => $r['start_date'], 'end_date' => $end_date];
                break;
        }
        return $dateResult;
    }

    private function renderDateRange($r)
    {
        $datePeriod = $this->filterDateRange($r);
        $startDate = new \DateTime($datePeriod->start_date);
        $endDate = new \DateTime($datePeriod->end_date);
        for ($i = $startDate; $i <= $endDate; $i->modify('+1 day')) {
            $dateRange[] = date_format($i, "Y-m-d");
        }
        return ($dateRange);
    }

    public function postViewReportQuantity(Request $request)
    {
        $user= $request->user;
      if (!$this->checkEntity($user->id, "VIEW_REPORT_QUANTITY")) {
        Log::info($user->email . '  TRY TO GET ReportController.postViewReportQuantity WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }


        $validatedData = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'report' => 'required',
            'datePeriod' => 'required',
        ]);
        $range = $request->all();
        $datePeriod = $this->filterDateRange($range);
// AM Fixing
      $isAm=$this->checkEntity($user->id, "AM");

      $sql = "SELECT SUM(amount) TOTAL,
                IFNULL(SUM(CASE WHEN event_type='000002' THEN amount ELSE 0 END), 0) VOICE_CALL,
                IFNULL(SUM(CASE WHEN event_type='000002' THEN count ELSE 0 END), 0) VOICE_DURATION,
                IFNULL(SUM(CASE WHEN event_type='000001' THEN amount ELSE 0 END), 0) SUB,
                IFNULL(SUM(CASE WHEN event_type='000003' THEN amount ELSE 0 END), 0) SMS,
                IFNULL(SUM(CASE WHEN event_type='000003' THEN count ELSE 0 END), 0) SMS_DURATION
                from charge_log a
                where   insert_time  between ? and ?    ";
      $params = [$datePeriod->start_date, $datePeriod->end_date];
      if ($isAm) {
        $checkCusAms = CustomerAms::where('user_id', $user->id)->count();

        if ($checkCusAms == 0) {
          return response()->json(['fee' =>[],'message'=>'No Customer found by AM '],200);

        }

        $sql .= " AND cus_id in (select cus_id from customer_ams where user_id=? ) ";
        array_push($params, $user->id);
      }

      $result = DB::select($sql, $params);




        $call_fee = $result[0]->VOICE_CALL;
        $call_duration = ceil($result[0]->VOICE_DURATION / 60);
        $sms_fee = $result[0]->SMS;
        $sms_duration = $result[0]->SMS_DURATION;
        $sub_fee = $result[0]->SUB;
        $total_fee = intval($result[0]->TOTAL);
        if ($total_fee > 0) {
            $sub_fee_per = ($sub_fee * 100) / $total_fee;
            $sms_fee_per = ($sms_fee * 100) / $total_fee;
            $call_fee_per = ($call_fee * 100) / $total_fee;
        } else {
            $sub_fee_per = 0;
            $sms_fee_per = 0;
            $call_fee_per = 0;
        }
        // select sum(total_amount) from call_fee_cycle_status where cycle_from >=@dateStart and cycle_to <=@endDate
        return response()->json(['fee' =>
                [array('name' => 'CALL_FEE', 'amount' => intval($call_fee),
                    'count' => $call_duration,
                    'unit' => 'min',
                    'percent' => $call_fee_per),
                    array('name' => 'SMS_FEE',
                        'amount' => intval($sms_fee),
                        'count' => $sms_duration,
                        'unit' => 'sms', 'percent' => $sms_fee_per),
                    array('name' => 'SUB_FEE',
                        'amount' => intval($sub_fee),
                        'count' => 0,
                        'unit' => "null",
                        'percent' => $sub_fee_per)
                ],
                'date' => [
                    'start_date' => date('d/m/Y', strtotime($datePeriod->start_date)),
                    'end_date' => date('d/m/Y', strtotime($datePeriod->end_date))],
                'total_fee' => $total_fee
            ]
        );
        //postViewReportQuantity
    }



  public function postViewReportFlowBK(Request $request) {
    $user = $request->user;
    if (!$this->checkEntity($user->id, "VIEW_REPORT_FLOW")) {
      Log::info($user->email . '  TRY TO GET ReportController.postViewReportFlow WITHOUT PERMISSION');
      return response()->json(['status' => false, 'message' => "Permission denied"], 403);
    }

    $validatedData = $request->validate(['start_date' => 'nullable|date', 'end_date' => 'nullable|date', 'report' => 'required', 'datePeriod' => 'required',
        ]);
        $range = $request->all();
        $datePeriod = $this->filterDateRange($range);
        // So luong cuoc goi
        $querySuccess = "select DATE(a.full_time) as day, IFNULL(SUM(duration),0) total_minute, 
                COUNT(b.id) as num_of_call  from report_days  a 
                left join sbc.cdr_vendors b on a.full_time= DATE(b.setup_time)   and i_vendor in ( 2,17) 
                where  full_time between ? and ?   group by day ";
        $resCallSuccess = DB::select($querySuccess, [$datePeriod->start_date, $datePeriod->end_date]);
        $queryFailed = "select DATE(a.full_time) as day, COUNT(b.id) as num_of_call  
from report_days  a left join sbc.cdr_vendors_failed b on a.full_time= DATE(b.setup_time)   and i_vendor in ( 2,17) 
where  full_time between ? and ? group by day";
        $resCallFailed = DB::select($queryFailed, [$datePeriod->start_date, $datePeriod->end_date]);
        $callFailed = array();
        $dateAvail = array();
        $callSuccessAmount = array();
        $callSuccessTime = array();
        $total_success_call = 0;
        $total_success_call_time = 0;
        $total_failed_call = 0;
        foreach ($resCallSuccess as $key => $val) {
            $total_success_call += intval($val->num_of_call);
            $total_success_call_time += ceil(intval($val->total_minute) / 60);
            array_push($callSuccessAmount, intval($val->num_of_call)?intval($val->num_of_call):0);
            array_push($callSuccessTime, ceil(intval($val->total_minute) / 60));
        }
        foreach ($resCallFailed as $key => $val) {
            $total_failed_call += intval($val->num_of_call);
            array_push($callFailed, intval($val->num_of_call)?intval($val->num_of_call):0);
            array_push($dateAvail, ($val->day));
            //  array_push($callSuccessTime, intval($val->total_minute));
        }
        return response()->json([
            'total' => ['success' => $total_success_call, 'success_time' => $total_success_call_time, 'failed' => $total_failed_call],
            'call_success' => $callSuccessAmount, 'call_time' => $callSuccessTime, 'call_failed' => $callFailed, 'date' =>
                ['start_date' => date('d/m/Y', strtotime($datePeriod->start_date)), 'end_date' => date('d/m/Y', strtotime($datePeriod->end_date))],
            'date_range' => $dateAvail
        ]);
        //postViewReportFlow
    }
public function postViewReportFlow(Request $request) {
    $user = $request->user;
    if (!$this->checkEntity($user->id, "VIEW_REPORT_FLOW")) {
      Log::info($user->email . '  TRY TO GET ReportController.postViewReportFlow WITHOUT PERMISSION');
      return response()->json(['status' => false, 'message' => "Permission denied"], 403);
    }

    $validatedData = $request->validate(['start_date' => 'nullable|date', 'end_date' => 'nullable|date', 'report' => 'required', 'datePeriod' => 'required',
        ]);
        $range = $request->all();
        $datePeriod = $this->filterDateRange($range);
        // So luong cuoc goi
        $querySuccess = "select DATE(a.full_time) as day,  total_minute, 
                num_of_call  from report_days  a 
					 LEFT JOIN (     
Select count(*) num_of_call, IFNULL(SUM(duration),0) total_minute,  DATE(setup_time) day  from sbc.cdr_vendors where setup_time
 between   ? and  ?  group by DATE(setup_time)) b 
on a.full_time= b.day
    where  full_time between ? and  ?   ";
        $resCallSuccess = DB::select($querySuccess, [$datePeriod->start_date, $datePeriod->end_date,$datePeriod->start_date, $datePeriod->end_date]);
        $queryFailed = "select DATE(a.full_time) as day,  
                num_of_call  from report_days  a 
					 LEFT JOIN (     
Select count(*) num_of_call,    DATE(setup_time) day  from sbc.cdr_vendors_failed where setup_time
 between   ? and  ?  group by DATE(setup_time)) b 
on a.full_time= b.day
    where  full_time between ? and  ? ";
        $resCallFailed = DB::select($queryFailed, [$datePeriod->start_date, $datePeriod->end_date,$datePeriod->start_date, $datePeriod->end_date]);
        $callFailed = array();
        $dateAvail = array();
        $callSuccessAmount = array();
        $callSuccessTime = array();
        $total_success_call = 0;
        $total_success_call_time = 0;
        $total_failed_call = 0;
        foreach ($resCallSuccess as $key => $val) {
            $total_success_call += intval($val->num_of_call);
            $total_success_call_time += ceil(intval($val->total_minute) / 60);
            array_push($callSuccessAmount, intval($val->num_of_call)?intval($val->num_of_call):0);
            array_push($callSuccessTime, ceil(intval($val->total_minute) / 60));
        }
        foreach ($resCallFailed as $key => $val) {
            $total_failed_call += intval($val->num_of_call);
            array_push($callFailed, intval($val->num_of_call)?intval($val->num_of_call):0);
            array_push($dateAvail, ($val->day));
            //  array_push($callSuccessTime, intval($val->total_minute));
        }
        return response()->json([
            'total' => ['success' => $total_success_call, 'success_time' => $total_success_call_time, 'failed' => $total_failed_call],
            'call_success' => $callSuccessAmount, 'call_time' => $callSuccessTime, 'call_failed' => $callFailed, 'date' =>
                ['start_date' => date('d/m/Y', strtotime($datePeriod->start_date)), 'end_date' => date('d/m/Y', strtotime($datePeriod->end_date))],
            'date_range' => $dateAvail
        ]);
        //postViewReportFlow
    }

    public function postViewReportCustomer(Request $request)
    {
        $user= $request->user;
      if (!$this->checkEntity($user->id, "VIEW_REPORT_CUSTOMER")) {
        Log::info($user->email . '  TRY TO GET ReportController.postViewReportCustomer WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }

      $isAm=$this->checkEntity($user->id, "AM");


        $validatedData = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'report' => 'required',
            'datePeriod' => 'required',
        ]);
        //postViewReportConnectRating
        $range = $request->all();
        // Cấu hình dữ liệu báo cáo
        $datePeriod = $this->filterDateRange($range);


      if ($isAm) {
        $checkCusAms = CustomerAms::where('user_id', $user->id)->count();

        if ($checkCusAms == 0) {
          return response()->json(['date' =>
            ['start_date' => date('d/m/Y', strtotime($datePeriod->start_date)),
              'end_date' => date('d/m/Y', strtotime($datePeriod->end_date))],
            'customer' => [],
            'hotline' => [],
            'total' => [
              'customer' => 0,
              'hotline' => 0
            ],
            'date_range' => '',
            'message' => 'No Customer found by AM '], 200);
        }
      }



        $queryTotalCustomer = "select DATE(a.full_time) as day, COUNT(b.id) as number_of_cus  
        from report_days  a left join customers b on a.full_time= DATE(b.created_at)  
        where full_time between ? and ?  ";


        $paramsCustomers=[$datePeriod->start_date, $datePeriod->end_date];


        if($isAm)
        {
          $queryTotalCustomer .=" and b.id in (select cus_id from customer_ams where user_id =?)";
          array_push($paramsCustomers, $user->id) ;
        }

        $queryTotalCustomer .=" group by day";

        $resCustomer = DB::select($queryTotalCustomer,$paramsCustomers );


        $queryHotline = "select DATE(a.full_time) as day, COUNT(b.id) as number_of_hotline 
        from report_days  a left join hot_line_config b on a.full_time= DATE(b.created_at)  
        where full_time between ? and ? ";

      if($isAm)
      {
        $queryHotline .=" and b.cus_id in (select cus_id from customer_ams where user_id =?)";
      }


      $queryHotline .=" group by day";

        $resHotline = DB::select($queryHotline, $paramsCustomers);
        $customer = array();
        $dateAvail = array();
        $totalCustomer = 0;
        $inActiveCustomer = array();
        $totalInActiveCustomer = 0;
        $hotline = array();
        $totalHotline = 0;
        foreach ($resCustomer as $key => $val) {
            $totalCustomer += intval($val->number_of_cus);
            array_push($customer, intval($val->number_of_cus));
            array_push($dateAvail, ($val->day));
            //  array_push($callSuccessTime, intval($val->total_minute));
        }
        foreach ($resHotline as $key => $val) {
            $totalHotline += intval($val->number_of_hotline);
            array_push($hotline, intval($val->number_of_hotline));
        }
        return response()->json(['date' => ['start_date' => date('d/m/Y', strtotime($datePeriod->start_date)), 'end_date' => date('d/m/Y', strtotime($datePeriod->end_date))],
            'customer' => $customer,
            'hotline' => $hotline,
            'total' => [
                'customer' => $totalCustomer,
                'hotline' => $totalHotline
            ],
            'date_range' => $dateAvail
        ]);
    }

  public function postViewReportMonthlyAudit(Request $request) {
    $user = $request->user;

    if (!$this->checkEntity($user->id, "VIEW_REPORT_MONTHLY_DESTINATION")) {
      Log::info($user->email . '  TRY TO GET ReportController.postViewReportMonthlyAudit WITHOUT PERMISSION');
      return response()->json(['status' => false, 'message' => "Permission denied"], 403);
    }

    $validatedData = $request->validate(['start_date' => 'required|date', 'count' => 'sometimes|numeric|max:100', 'page' => 'sometimes|numeric|max:1000'

    ]);

    $start_date = date("Y-m-d 00:00:00", strtotime($request->start_date));
    $end_date = date("Y-m-d 23:59:59", strtotime($request->end_date));

    $start_datex = new DateTime($start_date);
    $end_datex = new DateTime($end_date);

    $interval = $start_datex->diff($end_datex);

    if ($interval->days > 31) {
      return $this->ApiReturn([], true, 'Thời gian bắt đầu và kết thúc không quá 31 ngày', 422);

    }

    $returnReport = new \stdClass();
    $returnReport->charge_logs = [];


    $isAm=$this->checkEntity($user->id, "AM");
    if ($isAm) {
      $checkCusAms = CustomerAms::where('user_id', $user->id)->count();

      if ($checkCusAms == 0) {
        return $this->ApiReturn($returnReport, true, 'No AM found', 200);
      }
    }





    $sqlGroupPrefix = DB::select("select prefix_group, GROUP_CONCAT(prefix_type_id SEPARATOR ',') listId  from prefix_type_name f group by f.prefix_group");

    $lstGroupPrefix=DB::table("prefix_type_group")->get();
    $groupObject=new \stdClass();
    foreach($lstGroupPrefix as $groupPrefix)
    {
      $groupObject->{$groupPrefix->id}= $groupPrefix->group_name;
    }

    $groupObject->{0}="No group";
    $groupObject->sub="Sub";


    $sqlRender = null;

    if (count($sqlGroupPrefix) > 0) {
      foreach ($sqlGroupPrefix as $prefix) {
        $prefixGroup = $prefix->prefix_group ? $prefix->prefix_group : 0;

        $sqlRender .= " when destination_type in ($prefix->listId) then '$prefixGroup'";
      }
    }



    $chargeLogs = " select sum(amount) as Amount, sum(count) as Duration,
                     case 
                     $sqlRender
                     else 'sub'
                     end as Direction
                     from charge_log
                     where   insert_time between ? and ?  ";

    $params=[$start_date, $end_date];

    if($isAm)
    {

       $chargeLogs.= " AND cus_id in (select cus_id from customer_ams where user_id= ?) ";
       array_push($params, $user->id);
    }

    $chargeLogs.='  group by Direction';

    $resChargeLog = DB::select($chargeLogs, $params);


    if(count($resChargeLog)>0)
    {
      foreach ($resChargeLog as $item)
      {
        $item->Direction= $groupObject->{$item->Direction};
      }
    }

//    $returnReport->sqlRender = $sqlRender;
    $returnReport->charge_logs = $resChargeLog;


    return $this->ApiReturn($returnReport, true,[],  200);
  }


  public function postSearchReportGrowth(Request $request)
  {
    $user = $request->user;

    if (!$this->checkEntity($user->id, "VIEW_REPORT_DETAIL")) {
      Log::info($user->email . '  TRY TO GET ReportController.postSearchReportGrowth WITHOUT PERMISSION');
      return response()->json(['status' => false, 'message' => "Permission denied"], 403);
    }

    $isAm = $this->checkEntity($user->id, "AM");
    if ($isAm) {
      $checkCusAms = CustomerAms::where('user_id', $user->id)->count();

      if ($checkCusAms == 0) {
        return $this->ApiReturn([], true, 'No AM found', 400);
      }
    }
    $validatedData = $request->validate([
      'start_date' => 'required|date',
      'end_date' => 'required|date',
      'prefix_group' => 'nullable|int',
      'enterprise_number' => 'required'
    ]);
    $start_date= request('start_date', date('Y-m-01 00:00:00'));
    $end_date= request('end_date', date('Y-m-d 23:59:59'));
    $end_date= date_format(new DateTime($end_date), 'Y-m-d 23:59:59');
    $enterprise= request('enterprise_number',null);
    $prefix_group= request('prefix_group',null);
    $checkCus= false;
    if($enterprise)
    {
       $checkCus= Customers::where('enterprise_number', $enterprise)->whereIn('blocked',[0,1])->first();
       if(!$checkCus)
       {
         return $this->ApiReturn([], true, 'Không tìm thấy khách hàng '.$enterprise, 400);
       }

    }


    $prefix_group_result= null;

    if($prefix_group)
    {
      $resCheck=DB::select("select listId from prefix_type_group  join (select prefix_group, GROUP_CONCAT(prefix_type_id SEPARATOR ',') listId  from prefix_type_name f group by f.prefix_group) pg on pg.prefix_group=prefix_type_group.id where prefix_type_group.id=?",[$prefix_group]);
      $prefix_group_result= count($resCheck)>0?$resCheck[0]->listId:null;
    }



    $start_datex = new DateTime($start_date);
    $end_datex = new DateTime($end_date);

    $interval = $start_datex->diff($end_datex);

    if ($interval->days > 31) {
      return $this->ApiReturn([], true, 'Thời gian bắt đầu và kết thúc không quá 31 ngày', 422);

    }

    $params=[$start_date,$end_date];
    $sql="select sum(amount) total_amount, sum(count) total_duration, count(1) total_call,  date(insert_time) day from charge_log where insert_time between ? and ?  and charge_status= 1  ";
    if($isAm)
    {
      $sql .=" AND cus_id in (select cus_id from customer_ams where user_id=?) ";
      array_push($params, $user->id);
    }

    if($checkCus)
    {
      $sql .= " AND cus_id =? ";
      array_push($params, $checkCus->id);
    }

    if($prefix_group_result)
    {
      $sql .=" AND destination_type in ($prefix_group_result)";
    }



    $sql.=" group by day order by day desc    ";
    $sqlCount= "select count(1) total from ($sql) a ";
    $total= DB::select($sqlCount, $params);
    $sql.=" LIMIT ?,?  ";
    array_push($params, 0,100);
    $res=DB::select($sql, $params);


    $dataSummary=new stdClass();
    $dataSummary->total_amount=0;
    $dataSummary->total_duration=0;
    $dataSummary->total_call=0;

    foreach ($res as $item)
    {
      $dataSummary->total_amount += intval($item->total_amount);
      $dataSummary->total_duration += intval($item->total_duration);
      $dataSummary->total_call += intval($item->total_call);

      $item->total_duration= $this->secondsToTime($item->total_duration);
    }







    return response()->json(['data'=>$res, 'count'=>$total[0]->total, 'summary'=>$dataSummary],200);

  }



  public function postSearchReportAudit(Request $request)
  {
    $user = $request->user;

    if (!$this->checkEntity($user->id, "VIEW_REPORT_DETAIL")) {
      Log::info($user->email . '  TRY TO GET ReportController.postSearchReportGrowth WITHOUT PERMISSION');
      return response()->json(['status' => false, 'message' => "Permission denied"], 403);
    }

    $isAm = $this->checkEntity($user->id, "AM");
    if ($isAm) {
      $checkCusAms = CustomerAms::where('user_id', $user->id)->count();

      if ($checkCusAms == 0) {
        return $this->ApiReturn([], true, 'No AM found', 400);
      }
    }
    $validatedData = $request->validate([
      'start_date' => 'required|date',
      'end_date' => 'required|date',
      'enterprise_number' => 'required',
      'prefix_group' => 'nullable|int'
    ]);
    $start_date= request('start_date', date('Y-m-01 00:00:00'));
    $end_date= request('end_date', date('Y-m-d 23:59:59'));
    $end_date= date_format(new DateTime($end_date), 'Y-m-d 23:59:59');


    $totalPerPage= request('count',500);
    $page= request('page',1);
    $skip= ($page-1)*$totalPerPage;





    $enterprise= request('enterprise_number',null);
    $prefix_group= request('prefix_group',null);
    $checkCus= false;
    if($enterprise)
    {
      $checkCus= Customers::where('enterprise_number', $enterprise)->whereIn('blocked',[0,1])->first();
      if(!$checkCus)
      {
        return $this->ApiReturn([], true, 'Không tìm thấy khách hàng '.$enterprise, 400);
      }

    }


    $prefix_group_result= null;

    if($prefix_group)
    {
      $resCheck=DB::select("select listId from prefix_type_group  join (select prefix_group, GROUP_CONCAT(prefix_type_id SEPARATOR ',') listId  from prefix_type_name f group by f.prefix_group) pg on pg.prefix_group=prefix_type_group.id where prefix_type_group.id=?",[$prefix_group]);
      $prefix_group_result= count($resCheck)>0?$resCheck[0]->listId:null;
    }



    $start_datex = new DateTime($start_date);
    $end_datex = new DateTime($end_date);

    $interval = $start_datex->diff($end_datex);

    if ($interval->days > 31) {
      return $this->ApiReturn([], true, 'Thời gian bắt đầu và kết thúc không quá 31 ngày', 422);

    }
/**

    select sum(amount) total_amount, sum(count) total_duration, count(1) total_call,  date(insert_time) day, ifNULL(prefix_type_name.name,"sub") prefixName from charge_log
left join prefix_type_name on prefix_type_name.prefix_type_id= destination_type
where insert_time between '2022-10-01 00:00:00' and '2022-12-30 14:12:25'   group by day, prefixName    order by insert_time desc    LIMIT 0,100

*/

    $params=[$start_date,$end_date];
    $sql="select sum(amount) total_amount, sum(count) total_duration, count(1) total_call,  date(insert_time) day, ifNULL(prefix_type_name.name,\"sub\") prefixName from charge_log
left join prefix_type_name on prefix_type_name.prefix_type_id= destination_type
where insert_time between ? and ?  and charge_status= 1  ";
    if($isAm)
    {
      $sql .=" AND cus_id in (select cus_id from customer_ams where user_id=?) ";
      array_push($params, $user->id);
    }

    if($checkCus)
    {
      $sql .= " AND cus_id =? ";
      array_push($params, $checkCus->id);
    }
    if($prefix_group_result)
    {
      $sql .=" AND destination_type in ($prefix_group_result)";
    }



    $sql.=" group by day, prefixName order by day desc    ";
    $sqlCount= "select count(1) total from ($sql) a ";
    $total= DB::select($sqlCount, $params);
    $sql.=" LIMIT ?,?  ";
    array_push($params, $skip, $totalPerPage);
    $res=DB::select($sql, $params);


    $dataSummary=new stdClass();
    $dataSummary->total_amount=0;
    $dataSummary->total_duration=0;
    $dataSummary->total_call=0;

    foreach ($res as $item)
    {
      $item->customer_name= $checkCus->cus_name;
      $item->enterprise_number= $checkCus->enterprise_number;
      $dataSummary->total_amount += intval($item->total_amount);
      $dataSummary->total_duration += intval($item->total_duration);
      $dataSummary->total_call += intval($item->total_call);

      $item->total_duration= $this->secondsToTime($item->total_duration);
    }

    return response()->json(['data'=>$res, 'count'=>$total[0]->total, 'summary'=>$dataSummary],200);

  }
  public function searchReportMonthBilling(Request $request)
  {
    $user = $request->user;

    if (!$this->checkEntity($user->id, "VIEW_REPORT_DETAIL")) {
      Log::info($user->email . '  TRY TO GET ReportController.postSearchReportGrowth WITHOUT PERMISSION');
      return response()->json(['status' => false, 'message' => "Permission denied"], 403);
    }

    $isAm = $this->checkEntity($user->id, "AM");
    if ($isAm) {
      $checkCusAms = CustomerAms::where('user_id', $user->id)->count();

      if ($checkCusAms == 0) {
        return $this->ApiReturn([], true, 'No AM found', 400);
      }
    }
    $validatedData = $request->validate([
      'billing_month' => 'required|date',

      'enterprise_number' => 'required',
      'prefix_group' => 'nullable|int'
    ]);
    $start_date= request('billing_month', date('Y-m-01 00:00:00'));
    $end_date = date('Y-m-t 23:59:59', strtotime($start_date));

    $totalPerPage= request('count',500);
    $page= request('page',1);
    $skip= ($page-1)*$totalPerPage;





    $enterprise= request('enterprise_number',null);
    $enterpriseNoZero= $this->removeZero($enterprise);

    $checkCus= false;
    if($enterprise)
    {
      $checkCus= Customers::where('enterprise_number', $enterprise)->whereIn('blocked',[0,1])->first();
      if(!$checkCus)
      {
        return $this->ApiReturn([], true, 'Không tìm thấy khách hàng '.$enterprise, 400);
      }

    }


    $callPriceConfig= DB::table('call_fee_config')->where('type', $checkCus->service_id)->get();


    $start_datex = new DateTime($start_date);
    $end_datex = new DateTime($end_date);

    $interval = $start_datex->diff($end_datex);

    if ($interval->days > 31) {
      return $this->ApiReturn([], true, 'Thời gian bắt đầu và kết thúc không quá 31 ngày', 422);

    }


    $totalHotlineAvail= Hotlines::where('cus_id',$checkCus->id)
        ->whereIn('status',[0,1])->count();

    $callPrice= "select  distinct(call_fee_config.type),  call_fee_config.call_fees  from service_prefix_type 
join  call_fee_config on call_fee_config.`type`= service_prefix_type.prefix_type_id
where call_fee_config.service_config_id= ?  order by updated_at desc

";

    $priceConfig= DB::select($callPrice,[$checkCus->service_id]);
    $priceByPrefix=new stdClass();

    if(count($priceConfig)>0)
    {
        foreach ($priceConfig as $item)
        {
            $priceByPrefix->{$item->type}= $item->call_fees;
        }
    }


    $params=[$enterpriseNoZero, $start_date,$end_date, ];
    $sql="SELECT prefix_type_name.name,   call_fee_cycle_status.* FROM call_fee_cycle_status LEFT JOIN prefix_type_name ON prefix_type_name.prefix_type_id= call_fee_cycle_status.`type` WHERE   enterprise_number =?   and  cycle_from BETWEEN ? AND ?  ";

      $sqlSub= "select total_amount, cycle_from , cycle_to, updated_at from subcharge_fee_cycle_status
 where enterprise_number=?  and cycle_from >=? and cycle_from < ? ";



      $subAmount= DB::select($sqlSub, $params);
      $res = DB::select($sql, $params);

      $total=0;
      $total_call=0;
      $call_fee_amount=0;

      if(count($subAmount)>0)
      {

          $total= $total+ intval($subAmount[0]->total_amount)/1.1;
      }
      if(count($res)>0)
      {
          foreach ($res as $item)
          {
              $item->total_amount= intval($item->total_amount/1.1);
              $item->total_duration= intval($item->total_duration/60);
              $item->price= ((isset($priceByPrefix->{$item->type})?$priceByPrefix->{$item->type}:0)*60)/1.1;
              $total= $total+intval($item->total_amount);
              $total_call= $total_call+intval($item->total_duration);
              $call_fee_amount= $call_fee_amount+intval($item->total_amount);
          }
      }

      $dataSummary = new stdClass();
      $dataSummary->totalbeforetax= $total;
      $dataSummary->total_call= $total_call;
      $dataSummary->call_fee_amount= $call_fee_amount;
      $dataSummary->date_print= date("D-M-Y H:i:s");

      $dataSummary->sub = count($subAmount) > 0 ? $subAmount[0] : new stdClass();
      $dataSummary->sub->total_hotline=$totalHotlineAvail;


    return response()->json(['data'=>$res,    'date'=>['start_date'=>$start_date, 'end_date'=>$end_date], 'sum'=>$dataSummary,  'sub'=>$dataSummary->sub,'customer'=>$checkCus],200);

  }


  function secondsToTime($seconds) {
      return $seconds;
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $seconds = $seconds % 60;

    return sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
  }


}

