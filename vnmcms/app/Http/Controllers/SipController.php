<?php
namespace App\Http\Controllers;

use App\Customers;
use App\CustomersBackup;
use App\Hotlines;
use App\HotlinesBackup;
use App\NumberRouting;
use App\SBCAcl;
use App\SBCAclBackup;
use App\SBCCallGroup;
use App\SBCCallGroupBackup;
use App\SBCRouting;
use App\SBCRoutingBackup;
use App\SBCVendor;
use App\SBCVendorBackup;
use DeepCopy\f008\A;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\ActivityController;
use Illuminate\Support\Facades\Log;
use Mockery\Exception;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

use Validator;




class SipController extends Controller
{
    public function callDebugPython($id, Request $request)
    {

      return [];
        $startTime=round(microtime(true) * 1000);

        $user=Auth::user();

            $cld= $request->cld?$request->cld:"";


      $validData = $request->only('cld');
      $validData['cli']= $id;
      $validator = Validator::make($validData, [
        'cli' => 'required|numeric',
        'cld' => 'sometimes|numeric'
      ]);
      // Trả về lỗi nếu sai
      if ($validator->fails()) {

        $logDuration=round(microtime(true) * 1000)-$startTime;
        Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".$request->url()."|".json_encode($validData)."|PYTHON_TRACE|".$logDuration."|Invalid parameter");

        return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
      }



        if($user->role==1 || $user->role==3 ) {
            try {
                $process = new Process('python /u01/app/crontab_script/py_tracelog/calltrace.py ' . $id . ' '.$cld.' ');
                $process->run();
                // executes after the command finishes
                if (!$process->isSuccessful()) {
                    throw new ProcessFailedException($process);
                }

              $logDuration=round(microtime(true) * 1000)-$startTime;
              Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".$request->url()."|".json_encode($validData)."|PYTHON_TRACE|".$logDuration."|Python tracking success");


              return $process->getOutput();
                //  return $id;
            }
            catch (Exception $exception)
            {
                return $exception;
            }
        }

      $logDuration=round(microtime(true) * 1000)-$startTime;
      Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".$request->url()."|".json_encode($validData)."|PYTHON_TRACE|".$logDuration."|Permission prohibit");



      return "Only administrator or technical can get call logs";
    }


    public function postSearchSip(Request $request)    {

      $startTime=round(microtime(true) * 1000);
        $user= $request->user;
//        if($user->role != ROLE_ADMIN && $user->role !=ROLE_USER
//          && $user->role !=ROLE_BILLING
//          && $user->role !=ROLE_TRACKING&& $user->role !=ROLE_SUPER_VISOR&& $user->role !=ROLE_CS)
//        {
//            return ['error'=>'Permission denied'];
//        }


      if (!$this->checkEntity($user->id, "VIEW_SIP_TRACKING") && !$this->checkEntity($user->id, "VIEW_SIP_TRACKING_CUSTOMER")) {
        Log::info($user->email . '  TRY TO GET SipController.postSearchSip WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }




      $page = 0;
      $take = 200;
      // Valid data
      $validData = $request->only('q', 'count');
      $validator = Validator::make($validData, [
        'q' => 'nullable|max:50',
        'count' => 'sometimes|numeric|max:100',
        'page'=>'sometimes|numeric|max:1000'
      ]);
      // Trả về lỗi nếu sai
      if ($validator->fails()) {

        $logDuration= round(microtime(true) * 1000)-$startTime;
        Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".$request->url()."|".json_encode($validData)."|GET_CUSTOMERS|".$logDuration."|Invalid parameter");

        return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
      }
      // Phân trang

      if($this->checkEntity($user->id, "VIEW_SIP_TRACKING_CUSTOMER"))
      {
        $customer= Customers::where("account_id",$user->id)->whereIn("blocked",[0,1])->first();

        if(!$customer)
        {
          return response()->json(['status'=>false, 'message'=>"Tài khoản đã khóa dịch vụ"],403);
        }
      }




      $totalPerPage= $request->count?$request->count:10;
      $query= null;
      $page= $request->page?$request->page:1;
      $skip= ($page-1)*$totalPerPage;
      if ($request->query) {
        $query = $request->input('q');
      }



        $sql="select a.*, b.companyname, b.phone1, c.ip_auth, c.ip_proxy, hlc.brand_name, hlc.brand_call_type  from sbc.routing a join customers b on a.i_customer= b.id   join sbc.acl  c on c.i_acl= a.i_acl    join hot_line_config hlc on hlc.hotline_number= a.caller  where a.direction= ? ";
      $sqlCount="select count(1) total from sbc.routing a join customers b on a.i_customer= b.id 
join sbc.acl  c on c.i_acl= a.i_acl
  join hot_line_config hlc on hlc.hotline_number= a.caller
 
where a.direction= ? ";
      $param=[1];

      if($this->checkEntity($user->id, "VIEW_SIP_TRACKING_CUSTOMER") && $customer)
      {
        $sql .= " AND a.i_customer= ?";
        $sqlCount .= " AND a.i_customer= ?";
        array_push($param, $customer->id);
      }

      if($query)
      {
        $sql .=" AND (hlc.brand_name like ? OR  a.caller like ? or b.companyname like ?   or c.ip_auth like ? or c.ip_proxy like ?)";
        $sqlCount .=" AND (hlc.brand_name like  ? OR   a.caller like ? or b.companyname like ?   or c.ip_auth like ? or c.ip_proxy like ?)";
        array_push($param, "%$query%","%$query%", "%$query%", "%$query%","%$query%");
      }


      $count= DB::select($sqlCount,$param);
      $sql .=" order by a.i_customer desc  limit ? , ? ";
      array_push($param, $skip, $totalPerPage);

      $data= DB::select($sql, $param);

        return response()->json(['sip'=>$data,'count'=>$count[0]->total],200);


    }


    public function getSipConfigByCaller($id)
    {
        if(!$id)
        {
            return response()->json(['response'=>"error",'message'=>'You not provide callee'],400);
        }
        elseif(strlen($id)> 16)
        {
            return response()->json(['response'=>"error",'message'=>'Parametter do note exist'],400);
        }

      $resCaller = DB::table('sbc.routing')->where('caller', $id)->first();
      $resCallee = DB::table('sbc.routing')->where('callee', $id)->first();
      $resGroup=DB::table('sbc.caller_group')
        ->where('caller',$id)
          ->where('status',0)
        ->select('status','enterprise as caller_master_group','callee_regex')
        ->first();
      $resVendor=DB::table('sbc.vendors')
        ->select('i_vendor', 'name')
        ->get();

      $resSelectedVendor=DB::table('hot_line_config')
        ->where('hotline_number',$id)
        ->whereIn('status',[0,1])
        ->select('vendor_id','operator_telco_id')
        ->first();


      // Get ACL
        if($resCaller)
        {
          $resAcl = DB::table('sbc.acl')->where('i_acl', $resCaller->i_acl)->first();

          if ($resCaller->i_acl_backup == $resCaller->i_acl) {
            $resAclBackup = null;
          } else {
            $resAclBackup = DB::table('sbc.acl')->where('i_acl', $resCaller->i_acl_backup)->first();
          }
         }
         else
         {
             $resAcl=null;
             return response()->json(['response'=>"error",'message'=>'Not found routing data'],400);

         }




      return response()->json(['routing'=>[$resCaller,$resCallee],'acl'=>$resAcl,'group'=>$resGroup,'acl_backup'=>$resAclBackup,
        'vendor'=>$resVendor,'current_vendor'=>$resSelectedVendor],200);
    }
    public function postSipRouting(Request $request)
    {
      $BACKUPSTATE = config("server.backup_site");
      $user = $request->user;

      if (!$this->checkEntity($user->id, "CONFIG_HOTLINE")) {
        Log::info($user->email . '  TRY TO GET SipController.postSipRouting WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }



      $sip = (object)[];

        if(!$request->hotline_number)
        {
            $validate = $request->validate([
                'enterprise_number' => 'required|exists:customers,enterprise_number',
                'ip_auth' => 'required|max:100',
                'ip_proxy' => 'sometimes|max:100',
                'ip_auth_backup' => 'sometimes|max:100',
                'ip_proxy_backup' => 'sometimes|max:100',
                'description' => 'max:255',
                'destination' => 'required|max:100',
                'callee_regex' => 'nullable|max:5000',
                'operator_telco_id' => 'required|max:40|exists:operator_telco,id',
                'auto_detect_blocking' => 'nullable|in:0,1',
                'hotline' => 'required|max:200',


            ]);
            $sip->hotline = $request->input('hotline');

        }
        else
        {
            $validate = $request->validate([
                'enterprise_number' => 'required|exists:customers,enterprise_number',
              'ip_auth' => 'required|max:100',
              'ip_proxy' => 'sometimes|max:100',
              'ip_auth_backup' => 'sometimes|max:100',
              'ip_proxy_backup' => 'sometimes|max:100',
                'description' => 'max:255',
                'destination' => 'required|max:100',
                'hotline_number' => 'required|max:200',
                'caller_group_master' => 'nullable|max:200',
                'operator_telco_id' => 'required|max:40|exists:operator_telco,id',
                'auto_detect_blocking' => 'nullable|in:0,1',
                'callee_regex' => 'nullable|max:5000',


            ]);
            $sip->hotline = $request->input('hotline_number');
        }


      $vendorData = SBCVendor::where('i_vendor', $request->vendor_id?$request->vendor_id:1)->first();
      $customer=Customers::where("enterprise_number",$request->enterprise_number)->whereIn("blocked",[0,1])->first();


      $isRunningOnbackup= $customer->server_profile== config("server.server_profile")?false:true;

      $caller_group_master= request("caller_group_master",null);

      if ($request->caller_group) {
        if (!$caller_group_master && $request->caller_group == 1) {
          return $this->ApiReturn(["caller_group_master" => ["Bạn chưa chọn số chủ nhóm"]], false, "Có lỗi xảy ra. Bạn chưa chọn số chủ nhóm", 422);
        }

        if ($caller_group_master && $request->caller_group == 1) {
          $resValidCallerGroup = Hotlines::where('cus_id', $customer->id)->whereIn('status', [0, 1])->where('hotline_number', $request->caller_group_master)->first();

          if (!$resValidCallerGroup) {
            return $this->ApiReturn(["caller_group_master" => ["Số chủ không hợp lệ"]], false, "Có lỗi xảy ra. Số chủ không hợp lệ", 422);
          }

          if ($caller_group_master == $request->hotline && $request->caller_group == 1) {
            return $this->ApiReturn(["caller_group_master" => ["Số chủ không được trùng số nhánh"]], false, "Có lỗi xảy ra. Số chủ không được trùng số nhánh", 422);
          }
        }
      }





      if (strpos($sip->hotline, ',') !== false) {
        $hotlines=explode(",", $sip->hotline);
      }
      else
      {
        $hotlines=[];
      }
        $callee_regex= request("callee_regex", $vendorData->hotline_prefix);
    DB::beginTransaction();

      try{
        if ($request->caller_group) {

            Log::info("Request Caller group V2");
          $callerGroup = $request->caller_group;
          if ($callerGroup == 1) {



              $resGroupMaster= SBCCallGroup::where('caller', $sip->hotline)->first();

            if (!$resGroupMaster) {
                $resGroupMaster= new SBCCallGroup();

            }
              Log::info("create group 1 ".$sip->hotline);
              $resGroupMaster->enterprise= $request->caller_group_master;
              $resGroupMaster->caller= $sip->hotline;
              $resGroupMaster->status= 0;
              $resGroupMaster->callee_regex= $callee_regex;
              $resGroupMaster->algorithm= 1;
              $resGroupMaster->cus_id= $customer->id;
              $resGroupMaster->save();



          } else {

            $resCallGrup= SBCCallGroup::where('cus_id', $customer->id)
              ->where('caller', $sip->hotline)->first();
            if ($resCallGrup) {
              $resCallGrup->delete();
            }

          }
        }

        if(!$hotlines)
        {

          $sip->ip_auth = $request->input('ip_auth');
          $sip->enterprise_number = $request->input('enterprise_number');
          $sip->ip_proxy = $request->input('ip_proxy');
          $sip->ip_proxy_backup = $request->input('ip_proxy_backup')?$request->input('ip_proxy_backup'):null;
          $sip->ip_auth_backup = $request->input('ip_auth_backup')?$request->input('ip_auth_backup'):null;

          $sip->description = $request->input('description');
          $sip->destination = $request->input('destination');
          $sip->telco_destination = request('telco_destination',null);
          $sip->auto_detect_blocking = request('auto_detect_blocking',1);
          $sip->allow_regex_callee = $request->allow_regex_callee;
          $sip->block_regex_callee = $request->block_regex_callee;
          $sip->profile_id_backup = $request->profile_id_backup?$request->profile_id_backup:2 ;
          $sip->groupHotLine= $request->caller_group;
          $sip->vendor= $vendorData;
          $sip->isRunningOnbackup= $isRunningOnbackup;
            $sip->operator_telco_id = $request->input('operator_telco_id');
          $sipOk= $this->setupSipRouting($sip, false);


        }
        else {
          foreach ($hotlines as $line) {
            if (strlen($line) > 6 && strlen($line) < 16) {
              $sip->hotline = $line;
              $sip->ip_auth = $request->input('ip_auth');
              $sip->enterprise_number = $request->input('enterprise_number');
              $sip->ip_proxy = $request->input('ip_proxy');

              $sip->ip_proxy_backup = $request->input('ip_proxy_backup');
              $sip->ip_auth_backup = $request->input('ip_auth_backup');


              $sip->description = $request->input('description');
              $sip->destination = $request->input('destination');
                $sip->telco_destination = request('telco_destination',null);
                $sip->auto_detect_blocking = request('auto_detect_blocking',1);
              $sip->allow_regex_callee = $request->allow_regex_callee;
              $sip->block_regex_callee = $request->block_regex_callee;
              $sip->groupHotLine= $request->caller_group;
              $sip->vendor= $vendorData;
                $sip->operator_telco_id = $request->input('operator_telco_id');
              $sip->isRunningOnbackup= $isRunningOnbackup;
              $sipOk = $this->setupSipRouting($sip, false);


            } else {
              return response()->json(['response'=>'error', 'message'=>"Invalid hotline number "],422);
            }
          }
        }

        DB::commit();

      }
      catch (\Exception $exception)
      {

        DB::rollback();


        Log::info($exception->getTraceAsString());
        return response()->json(['response'=>'error', 'message'=>"Error update sip",'e'=>$exception->getTraceAsString()],500);
      }

      return response()->json($sipOk)->original;

    }



  public function setupSipRouting($sip, $BACKUPSTATE)
  {


    $arrAcl = ['ip_auth' => $sip->ip_auth,
      'ip_proxy' => $sip->ip_proxy? $sip->ip_proxy:null,
      'description' => $sip->description?$sip->description:null,
      'block_regex_caller'=>'',
      'block_regex_callee'=>empty($sip->block_regex_callee)?'^(00|\\\\+84|1900|1800).*': $sip->block_regex_callee ,
      'allow_regex_caller'=>'',

      // 'allow_regex_callee'=>empty($sip->allow_regex_callee)?'^[0-9]{8,16}.*': $sip->allow_regex_callee
      'allow_regex_callee'=>empty($sip->allow_regex_callee)?'^0[0-9]{8,11}$': $sip->allow_regex_callee

    ];

    $arrAclBackup = ['ip_auth' => $sip->ip_auth_backup? $sip->ip_auth_backup:null,
      'ip_proxy' => $sip->ip_proxy_backup?$sip->ip_proxy_backup:null,
      'description' => $sip->description?$sip->description:"",
      'block_regex_caller' => '',
      'block_regex_callee'=>empty($sip->block_regex_callee)?'^(00|\\\\+84|1900|1800).*': $sip->block_regex_callee ,
      'allow_regex_caller' => '',
      'allow_regex_callee'=>empty($sip->allow_regex_callee)?'^0[0-9]{8,11}$': $sip->allow_regex_callee
    ];



      // TODO CURRENTLY STATE ON +**********************************************************TODO******************************************************************************************************************


      $hotlineInfo = Hotlines::where('hotline_number', $sip->hotline)
        ->where('enterprise_number', $sip->enterprise_number)
        ->whereIn('status',[0,1])
        ->select('id', 'cus_id')
        ->first();

      if (!$hotlineInfo) {
        return response()->json(['response' => 'error', 'message' => 'Hotline [' . $sip->hotline . '] and enterprise id [' . $sip->enterprise_number . '] do not match'], 422);
      }
      // Kiểm tra có SIP chưa
      $acl = SBCRouting::whereRaw('caller=? and i_customer=?', [$sip->hotline,$hotlineInfo->cus_id])
        ->select('i_acl as ACL','i_acl_backup')
        ->first();

      if ($acl) {
        $aclid = $acl->ACL;
        array_filter($arrAcl);

        // Cập nhật ACL chính
        SBCAcl::where('i_acl', $aclid)->update($arrAcl);


        // Kiểm tra tạo ACL Backup

        $aclidBackup = $acl->i_acl_backup;

        if($aclid==$aclidBackup &&  $sip->ip_auth_backup)
        {
          // Tạo mới ACL Backup
          Log::info("Tạo mới acl Backup");
          $aclidBackup = DB::table('sbc.acl')->insertGetId($arrAclBackup);
        }

        // Nếu có Iaclbackup và khác cái chính và có auth thì


        if ( $aclid != $aclidBackup &&  $sip->ip_auth_backup) {
          Log::info("Cập nhật  mới acl Backup".$aclidBackup);
          SBCAcl::where('i_acl', $aclidBackup)->update($arrAclBackup);


        }
        else if(!$sip->ip_auth_backup&&  $aclid != $aclidBackup )
        {

          // Xóa ACL đi
          SBCAcl::where('i_acl', $aclidBackup)->delete();
          $aclidBackup= $aclid;
        }

        SBCRouting::whereRaw('caller=? and i_customer=?', [$sip->hotline,$hotlineInfo->cus_id])
          ->update(['i_acl_backup'=> $aclidBackup]);


        $editMode = true;
      } else {
        $editMode = false;
        $aclid = SBCAcl::insertGetId($arrAcl);

        Log::info("SBCBACKUP ACL STRING".json_encode($arrAclBackup));
        $aclidBackup=  SBCAcl::insertGetId($arrAclBackup);
        //        $this->SetActivity($arrAcl, "sbc.acl", $aclid, $resAvai->cus_id, "CREATE_SIP","Tạo mới Sip cho hotline: ".$sip->hotline);
        // Insert new
      }

      $arrRoutingPrimary = ["direction" => 1,
        "caller" => $sip->hotline,
        "callee" => null,
        "i_acl" => $aclid,
        "i_acl_backup" => $aclidBackup,
        "destination" =>isset($sip->telco_destination)&& $sip->telco_destination?$sip->telco_destination: config('sip.RoutingDestination'),  /// Primary Server  ....120// change to 10.50.245.96:5060// secondary server 121
        "priority" => 10,
        "i_customer" => $hotlineInfo->cus_id,
        "auto_detect_blocking" =>$sip->auto_detect_blocking,
        "i_vendor" => 2,
        "network"=>1,
        "description" => $sip->description,
        "i_sip_profile" => 1];

      $arrRoutingSecondary = ["direction" => 2,
        "caller" => null,
        "callee" => $sip->hotline,
        "i_acl" => 1,
        "i_acl_backup" => 2,
        "destination" => $sip->destination,
        "priority" => 10,
        "i_customer" =>$hotlineInfo->cus_id,
        "i_vendor" => 0,
        "network"=>2,
        "description" => $sip->description,
        "i_sip_profile" => $sip->profile_id_backup];
      // Setup Routing
      if ($editMode == false) {
        //Inssert //
        $iCallerRouting=  $routingPrimary =SBCRouting::insertGetId($arrRoutingPrimary);
        $routingSecondary = SBCRouting::insertGetId($arrRoutingSecondary);

      } else {

        SBCRouting::where('direction', 1)
          ->where('i_customer', $hotlineInfo->cus_id)
          ->where('caller', $sip->hotline)
          ->update($arrRoutingPrimary);
        SBCRouting::where('direction', 2)
          ->where('i_customer', $hotlineInfo->cus_id)
          ->where('callee', $sip->hotline)
          ->update($arrRoutingSecondary);

      }

       $this->SetActivity($sip, "hot_line_config", $hotlineInfo->id, 0, config("sbc.action.update_sip_config"),"Cập nhật cấu hinh sip cho hotline  " .$sip->hotline, $sip->enterprise_number, $sip->hotline);


      Hotlines::where('id', $hotlineInfo->id)
        ->update(['sip_config' => date("Y-m-d H:i:s"),
            'operator_telco_id'=>$sip->operator_telco_id,
            'auto_detect_blocking'=>$sip->auto_detect_blocking,
            'vendor_id'=>$sip->vendor->i_vendor]);

      return response()->json(['status' => true], 200);


  }



    public function postCallLog($id, Request $request)
    {
      // TODO UPGRADE PAGE NUMBER DESTINATION
      $startTime=round(microtime(true) * 1000);
        $user= $request->user;

      if (!$this->checkEntity($user->id, "VIEW_SIP_TRACKING")) {
        Log::info($user->email . '  TRY TO GET SipController.postCallLog WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }

      if ($this->checkEntity($user->id, "VIEW_SIP_TRACKING_CUSTOMER")) {
        $res = DB::select("	select * from hot_line_config h join customers c on h.cus_id= c.id where c.account_id= ? and h.hotline_number =?", [$user->id, $id]);
        if (count($res) == 0) {
          Log::info($user->email . '  TRY TO GET SipController.postCallLog WITHOUT PERMISSION');
          return response()->json(['status' => false, 'message' => "Permission denied"], 403);
        }
      }


      // Valid data
      $validData = $request->only('q', 'count', 'direction','start_date','end_date','page');


        if($request->param)
        {
          if(isset($request->param['direction']))
          {

            $validData['direction']=$request->param['direction'];
          }
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
      // Phân trang

      $totalPerPage= $request->count?$request->count:10;
      $query= null;
      $page= $request->page?$request->page:1;
      $skip= ($page-1)*$totalPerPage;
      if ($request->query) {
        $query = $request->input('q');
      }
      $limitRow= 1000;

        if($request->download)
        {

            $limitRow < config('sb.limitLog')?$limitRow:config('sb.limitLog');
        }



        $caller = $id;
//        Check hot line belong to customer
        $rsCus = DB::table('hot_line_config')
            ->where('hotline_number', $id);
        if ($user->role == ROLE_BILLING) {
            $customerID = DB::table('customers')->where("account_id", $user->id)->first();
            if ($customerID) {
                $rsCus->where("cus_id", ($customerID->id));
            } else {
                return "Permission deniced";
            }
        }
        $customer = $rsCus->first();

        $q = $request->q;
        $start_date = $request->start_date;
        $direction = $request->direction;

        $end_date = $request->end_date;

        if (!$start_date) {
            $start_date = (date("Y-m-d 00:00:00"));

            //$start_date=($start_date->getDate());
        }
        if (!$end_date) {
            $end_date = date("Y-m-d H:i:s");
        }

        if($direction=="out")
        {
            $additionDirSuccess="  a.CLI= '".$caller ."'  ";
            $additionDirFailed="  a.CLI= '".$caller ."'  ";
        }
        else
        {
            $additionDirSuccess="  a.CLD= '".$caller ."' ";
            $additionDirFailed="  a.CLD= '".$caller ."' ";
        }



        if ($caller && $customer) {


                  $sql=" 
               SELECT *
        FROM (
        SELECT
         a.CLI, 
         a.CLD, 
         a.setup_time, 
         a.connect_time,
         a.call_id, 
         a.disconnect_time, 
         a.disconnect_cause, 
         a.from_network_ip, 
         a.des_network_ip,
         a.quality_mos,
         a.quality_largest_jb,
         a.quality_jitter_burst_rate,
         a.duration,
         a.charge_status,
         'success' AS state,
        
          x.call_brandname,
        null as reject_cause
        FROM sbc.cdr_vendors a LEFT JOIN sbc.cdr_vendors_extention x on a.call_id= x.call_id
        WHERE 
         $additionDirSuccess
          AND a.setup_time >=? AND a.setup_time <= ?
          UNION ALL
        SELECT
         a.CLI, 
         a.CLD, setup_time, NULL connect_time,
         a.call_id, 
         a.disconnect_time, 
         a.disconnect_cause, 
         a.from_network_ip, 
         a.des_network_ip, 
         NULL AS quality_mos,
         NULL AS quality_largest_jb,
          NULL AS quality_jitter_burst_rate, 
          NULL AS duration,
           NULL AS charge_status,
         'failed' AS state,
         
          x.call_brandname,
        x.reject_cause
        FROM sbc.cdr_vendors_failed a LEFT JOIN sbc.cdr_vendors_failed_extention x on a.call_id= x.call_id
        WHERE 
        $additionDirFailed
         AND a.setup_time >=? AND a.setup_time <= ?
        )
         callLog
         
         where  (callLog.CLD LIKE ? OR callLog.CLI like ?)  
      
        
        
        ";

                  $sqlCount=" 
               SELECT count(*) total
        FROM (
        SELECT
         a.CLI, 
         a.CLD, 
         a.setup_time, 
         a.connect_time,
         a.call_id, 
         a.disconnect_time, 
         a.disconnect_cause, 
         a.from_network_ip, 
         a.des_network_ip,
         a.quality_mos,
         a.quality_largest_jb,
         a.quality_jitter_burst_rate,
         a.duration,
         a.charge_status,
         'success' AS state,
         x.call_brandname,
          null as reject_cause
        FROM sbc.cdr_vendors a LEFT JOIN sbc.cdr_vendors_extention x on a.call_id= x.call_id
        WHERE 
         $additionDirSuccess
          AND a.setup_time >=? AND a.setup_time <= ?
          UNION ALL
        SELECT
         a.CLI, 
         a.CLD, setup_time, NULL connect_time,
         a.call_id, 
         a.disconnect_time, 
         a.disconnect_cause, 
         a.from_network_ip, 
         a.des_network_ip, NULL AS quality_mos, NULL AS quality_largest_jb, NULL AS quality_jitter_burst_rate, NULL AS duration, NULL AS charge_status,
         'failed' AS state,
          x.call_brandname,
        x.reject_cause
        FROM sbc.cdr_vendors_failed a LEFT JOIN sbc.cdr_vendors_failed_extention x on a.call_id= x.call_id
        WHERE 
        $additionDirFailed
         AND a.setup_time >=? AND a.setup_time <= ?
        )
         callLog
         
         where  (callLog.CLD LIKE ? OR callLog.CLI like ?)  
      
        
        
        ";







          $param =[ $start_date, $end_date, $start_date, $end_date,"%$query%","%$query%"];

          $resCount=DB::select($sqlCount, $param);
          $sql .="  LIMIT ?,?";
          array_push($param, $skip, $totalPerPage);

          $result=DB::select($sql,$param);

        } else {
            $result=[];

          $resCount=[];
        }

        return response()->json(['call_history' => $result, 'count'=>count($resCount)>0? $resCount[0]->total:0, 'client' => $customer, 'start_date' => $start_date, 'end_date' => $end_date]);
    }




    // Delete Sip
    public  function putDeleteSipByCaller(Request $request, ActivityController $activityController)
    {
      $startTime = round(microtime(true) * 1000);

      $user = $request->user;
      if (!$this->checkEntity($user->id, "REMOVE_HOTLINE")) {
        Log::info($user->email . '  TRY TO GET SipController.putDeleteSipByCaller WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }



      $validate = $request->validate(['number' => 'required|alpha_dash|exists:hot_line_config,hotline_number']);


        $hot_line_config= DB::table('hot_line_config')
            ->where('hotline_number', $request->number)
            ->whereIn('status',[0,1])
            ->select("id", 'cus_id', 'sip_config')
            ->first();

        if(!$hot_line_config)
        {
            return response()->json(['response'=>'error','message'=>'Not found hotline in this customer '],404);
        }

        if(!$hot_line_config->sip_config)
        {
            DB::table('hot_line_config')
                ->where('hotline_number', $request->number)
                -> update(['status'=>2, 'updated_at'=>date('Y-m-d'), 'sip_config'=>null]);

            $activityController->AddActivity( ['status'=>2, 'updated_at'=>date('Y-m-d'),'cus_id'=>$hot_line_config->cus_id, 'sip_config'=>null],
                "hotline_number", $hot_line_config->id, $hot_line_config->cus_id, "Update Delete Sip config");



        }


        $updateConfig= DB::table('hot_line_config')
            ->where('hotline_number', $request->number)
            ->where('cus_id', $hot_line_config->cus_id)
            ->update(['status'=>2, 'updated_at'=>date('Y-m-d'), 'sip_config'=>null]);

        $activityController->AddActivity( ['status'=>2, 'updated_at'=>date('Y-m-d'),'cus_id'=>$hot_line_config->cus_id, 'sip_config'=>null],
            "hotline_number", $hot_line_config->id, $hot_line_config->cus_id, "Update Delete Sip config");



        DB::table("sbc.routing")
            ->where('i_customer',$hot_line_config->cus_id)
            ->where('caller',$request->number)
            ->orWhere('callee',$request->number)
            ->delete();
        $activityController->AddActivity( ['deleted_at'=>date('Y-m-d'),
            'hot_line'=>$request->number, 'cus_id'=>$hot_line_config->cus_id],
            "sbc.routing", 0, $hot_line_config->cus_id, "Delete Routing");


      $logDuration=round(microtime(true) * 1000)-$startTime;
      Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".$request->url()."|"
        .json_encode($request->all())."|DELETE_HOTLINE_SIP|".$logDuration."|DELETE_HOTLINE_SIP_SUCCESS");



      return response()->json(['status'=>true],200);

    }

    public  function exportSipCallLog(Request $request)
    {

        $cookie=null;
        $user= null;
        if (isset($_COOKIE["sbc"])) {
            $cookie = $_COOKIE["sbc"];
            $user = $this->getUserByCookie($cookie);
        }
        if (!$user) {

            return "Permission denied.";
        }

      if (!$this->checkEntity($user->id, "EXPORT_SIP_TRACKING")) {
        Log::info($user->email . '  TRY TO GET SipController.exportSipCallLog WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }


        $request->user= $user;
        $request->count= config('sbc.limitCustomerDownload');
        $request->download= true;
        if($request->param)
        {
            if(isset($request->param['start_date']))
            {
                $request->start_date= $request->param['start_date'];
            }
            if(isset($request->param['end_date']))
            {
                $request->end_date= $request->param['end_date'];
            }

            if(isset($request->param['q']))
            {
                $request->q = $request->param['q'];
            }

            if(isset($request->param['direction']))
            {
                $request->direction = $request->param['direction'];
            }
        }
      $request->direction = $request->param['direction'];

//        return $this->postCallLog($request->hotline, $request);



      $data= $this->postCallLog($request->hotline, $request)->original;
    return view('exportSiplog',['data'=>$data]);

//        $res= $this->getCustomersV2($request)->original->data;
//
//        return view('exportCustomer', ['data'=>$res,'i'=>1]);

    }


  public function postCheckNumberRouting(Request $request) {
    $startTime = round(microtime(true) * 1000);

    $user = $request->user;

    if (!$this->checkEntity($user->id, "VIEW_SIP_TRACKING")) {
      Log::info($user->email . '  TRY TO GET SipController.postCheckNumberRouting WITHOUT PERMISSION');
      return response()->json(['status' => false, 'message' => "Permission denied"], 403);
    }



    //    $validate = $request->validate(['number' => 'required|alpha_dash|exists:hot_line_config,hotline_number']);
    $direction = $request->direction;
    $numberToCheck = $direction == "out" ? $request->CLD : $request->CLI;
    $resCheck = NumberRouting::where("ISDN", $numberToCheck)->first();
    return $this->ApiReturn($resCheck, true, null, 200);
  }
}


