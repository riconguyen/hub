<?php

  namespace App\Http\Controllers;

  use App\OperatorTelco;
  use App\ServiceConfig;
  use App\ServicePrefixTypeName;
  use Illuminate\Support\Facades\Log;
  use function response;
  use Validator;
  use Illuminate\Http\Request;
  use Illuminate\Support\Facades\DB;

  class ServiceController extends Controller
  {

    //

    private function Activity($activity, $table, $dataID, $rootid, $action) {
      return null;
    }

    public function getServiceConfig(Request $request) {
      $user = $request->user;
      if (!$this->checkEntity($user->id, "VIEW_SERVICE_CONFIG")) {
        Log::info($user->email . '  TRY TO GET ServiceController.getServiceConfig WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }

      $errors = Validator::make($request->only('query', 'page', 'take'), ['query' => 'nullable|unicode_valid|max:50', 'page' => 'nullable|integer|min:0|max:9999999', 'count' => 'nullable|integer|min:0|max:500',

        ]);
      if ($errors->fails()) {
        return $this->ApiReturn($errors->errors(), false, "The given data was invalid", 422);
      }

      $page = request("page", 1);
      $take = request("count", 20);

      if ($request->query) {
        $query = $request->input('query');
      } else {
        $query = null;
      }
      $skip = ($page - 1) * $take;

      $countServices = DB::table('service_config')->whereRaw('service_name like ? OR product_code like? ', ['%' . $query . '%', '%' . $query . '%'])->count();
      $res = DB::table('service_config')->whereRaw('service_name like ? OR product_code like? ', ['%' . $query . '%', '%' . $query . '%'])->take($take)->skip($skip)->get();

      return response()->json(['status' => true, 'data' => $res, 'count' => $countServices, 'page' => 1, 'totalpage' => 0]);
    }

    //     Lấy thiết lập theo dịch vụ
    public function getServiceConfigById(Request $request, $id) {
      $user = $request->user;

      if (!$this->checkEntity($user->id, "VIEW_SERVICE_CONFIG")) {
        Log::info($user->email . '  TRY TO GET ServiceController.getServiceConfigById WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }

      $data = ['id' => $id];

      $errors = Validator::make($data, ['id' => 'required|numeric|exists:service_config',

      ]);
      if ($errors->fails()) {
        return $this->ApiReturn($errors->errors(), false, "The given data was invalid", 422);
      }

      $service = DB::table('service_config')->where("id", $id)->get();
      $service_config_price = DB::table('service_config_price')->where("service_config_id", $id)->whereIn('status', [0, 1])->get();
//      $service_config_hotline_price = DB::table('service_config_hotline_price')->where('service_config_id', $id)->whereIn('status', [0, 1])->get();



        $service_config_hotline_price =DB::select("
        select service_config_hotline_price.*, operator_telco.description operator_name  from service_config_hotline_price left join operator_telco on operator_telco.id= service_config_hotline_price.operator_telco_id
        where service_config_hotline_price.service_config_id=? and service_config_hotline_price.status in (0,1)
        ",[$id]);

            DB::table('service_config_hotline_price')->where('service_config_id', $id)->whereIn('status', [0, 1])->get();
      $service_option_price = DB::table('service_option_price')->where('service_config_id', $id)->whereIn('status', [0, 1])->get();
      $service_call_price = DB::table('call_fee_config')->where('service_config_id', $id)->whereIn('status', [0, 1])->get();
      $service_sms_price = DB::table('sms_fee_config')->whereIn('status', [0, 1])->where('service_config_id', $id)->get();
      $service_quantity_price = DB::table('quantity_config')->whereIn('status', [0, 1])->where('service_config_id', $id)->get();

      $lstServicePrefix = DB::table('service_prefix_type')->where('service_config_id', $id)->get();
      return response()->json(array('status' => true, 'service' => $service, 'config_price' => $service_config_price, 'hotline_price' => $service_config_hotline_price, 'option_price' => $service_option_price, 'call_price' => $service_call_price, 'sms_price' => $service_sms_price, 'quantity_price' => $service_quantity_price, 'lstServicePrefix' => $lstServicePrefix));
    }

    // Tạo dịch vụ

    //    public function postServiceConfig(Request $request) {
    //      $startTime = round(microtime(true) * 1000);
    //
    //      $user = $request->user;
    //
    //      if (!$this->checkEntity($user->id, "UPDATE_SERVICE_CONFIG")) {
    //        Log::info($user->email . '  TRY TO GET ServiceController.getServiceConfigById WITHOUT PERMISSION');
    //        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
    //      }
    //
    //      $serviceID = $request->input('id');
    //      if (!$serviceID) {
    //        $errors = Validator::make($request->only('service_name', 'type', 'status', 'product_code', 'id'),
    //          ['service_name' => 'required|unicode_valid|max:50', 'type' => 'required|in:0,1', 'status' => 'required|in:0,1,2',
    //            'product_code' => 'required|alpha_dash|unique:service_config,product_code|max:50',
    //
    //
    //          ]);
    //      } else {
    //        $errors = Validator::make($request->only('service_name', 'type', 'status', 'product_code', 'id'), ['service_name' => 'required|max:50', 'type' => 'required|in:0,1', 'status' => 'required|in:0,1,2', 'product_code' => 'required|alpha_dash|max:50']);
    //      }
    //
    //      if ($errors->fails()) {
    //        $logDuration = round(microtime(true) * 1000) - $startTime;
    //        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|ADD_EDIT_SERVICE|" . $logDuration . "|ADD_EDIT_SERVICE_FAIL Invalid input data");
    //
    //        return $this->ApiReturn($errors->errors(), false, "The given data was invalid", 422);
    //      }
    //
    //      $arrService = ['service_name' => $request->input('service_name'), 'type' => $request->input('type'), 'product_code' => $request->input('product_code'), 'status' => $request->input('status'), 'created_at' => date("Y-m-d H:i:s"), 'updated_at' => date("Y-m-d H:i:s")];
    //      if ($serviceID) {
    //        unset($arrService["created_at"]);
    //        $res = DB::table('service_config')->where('id', $serviceID)->update($arrService);
    //        $res = $serviceID;
    //        $this->Activity($arrService, "service_config", $serviceID, $serviceID, "Update");
    //      } else {
    //        $res = DB::table('service_config')->insertGetId($arrService);
    //
    //        $this->setDefaultServicePrefixType($res);
    //        $this->Activity($arrService, "service_config", $res, $res, "Create");
    //      }
    //      $logDuration = round(microtime(true) * 1000) - $startTime;
    //      Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|ADD_EDIT_SERVICE|" . $logDuration . "|ADD_EDIT_SERVICE_SUCCESS");
    //
    //      return response()->json(array('status' => true, 'data' => $res));
    //    }

    public function postServiceConfig(Request $request) {
      $startTime = round(microtime(true) * 1000);

      $user = $request->user;

      if (!$this->checkEntity($user->id, "UPDATE_SERVICE_CONFIG")) {
        Log::info($user->email . '  TRY TO GET ServiceController.getServiceConfigById WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }

      $serviceID = $request->input('id');
      if (!$serviceID) {
        $errors = Validator::make($request->only('service_name', 'type', 'status', 'product_code', 'id', 'ocs_charge', 'is_prepaid'), ['service_name' => 'required|unicode_valid|max:50', 'type' => 'required|in:0,1', 'status' => 'required|in:0,1,2', 'product_code' => 'required|alpha_dash|unique:service_config,product_code|max:50', 'ocs_charge' => 'required|in:0,1', 'is_prepaid' => 'required|in:0,1',

          ]);
      } else {
        $errors = Validator::make($request->only('service_name', 'type', 'status', 'product_code', 'id', 'ocs_charge', 'is_prepaid'), ['service_name' => 'required|max:50', 'type' => 'required|in:0,1', 'status' => 'required|in:0,1,2', 'product_code' => 'required|alpha_dash|max:50', 'ocs_charge' => 'required|in:0,1', 'is_prepaid' => 'required|in:0,1',]);
      }

      if ($errors->fails()) {
        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|ADD_EDIT_SERVICE|" . $logDuration . "|ADD_EDIT_SERVICE_FAIL Invalid input data");

        return $this->ApiReturn($errors->errors(), false, "The given data was invalid", 422);
      }
      $isNew = false;

      if ($serviceID) {
        $service = ServiceConfig::where('id', $serviceID)->first();
      } else {
        $isNew = true;
        $service = new ServiceConfig();
        $service->created_at = date("Y-m-d H:i:s");
        $service->ocs_charge = request('ocs_charge');
      }
      $service->is_prepaid = request('is_prepaid');
      $service->service_name = request('service_name');
      $service->type = request('type');
      $service->product_code = request('product_code');
      $service->status = request('status');

      $service->save();

      if ($isNew) {
        $this->setDefaultServicePrefixType($service->id);
      }

      $logDuration = round(microtime(true) * 1000) - $startTime;
      Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|ADD_EDIT_SERVICE|" . $logDuration . "|ADD_EDIT_SERVICE_SUCCESS");

      return response()->json(array('status' => true, 'data' => $service->id));
    }

    // Tạo bản giá dịch vụ
    public function postServiceConfigPrice(Request $request) {
      $startTime = round(microtime(true) * 1000);

      $user = $request->user;
      if (!$this->checkEntity($user->id, "SET_SERVICE_CONFIG")) {
        Log::info($user->email . '  TRY TO GET ServiceController.postServiceConfigPrice WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }

      $regex = "/^(?=.+)(?:[1-9]\d*|0)?(?:\.\d+)?$/";

      $errors = Validator::make($request->only('from_user', 'to_user', 'price', 'id', 'service_config_id'), ['from_user' => 'required|integer', 'to_user' => 'required|integer', 'price' => 'required', 'service_config_id' => 'required|integer|exists:service_config,id', 'id' => 'bail|sometimes|integer|exists:service_config_price'

      ]);
      if ($errors->fails()) {
        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|ADD_EDIT_SERVICE_CONFIG_PRICE|" . $logDuration . "|ADD_EDIT_SERVICE_PRICE_FAIL Invalid Data");

        return $this->ApiReturn($errors->errors(), false, "The given data was invalid", 422);
      }

      $id = $request->id;

      $arrConfigPrice = $request->only('from_user', 'to_user', 'price', 'id', 'service_config_id');

      if (!$id) {
        $arrConfigPrice['updated_at'] = date("Y-m-d H:i:s");
        $id = DB::table('service_config_price')->insertGetId($arrConfigPrice);
        $this->Activity($arrConfigPrice, "service_config_price", $id, 0, "Create ");
      } else {
        unset($arrConfigPrice['created_at']);
        if ($request->status == 2) {
          $arrConfigPrice['status'] = 0;
        }
        DB::table('service_config_price')->where('id', $id)->update($arrConfigPrice);
        $this->Activity($arrConfigPrice, "service_config_price", $id, 0, "Update");
      }

      $logDuration = round(microtime(true) * 1000) - $startTime;
      Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|ADD_EDIT_SERVICE_CONFIG_PRICE|" . $logDuration . "|ADD_EDIT_SERVICE_PRICE_SUCCESS");

      return response()->json(array('id' => $id), 200);
    }

    public function postServiceConfigHotlinePrice(Request $request) {
      $startTime = round(microtime(true) * 1000);

      $user = $request->user;
      if (!$this->checkEntity($user->id, "SET_SERVICE_CONFIG")) {
        Log::info($user->email . '  TRY TO GET ServiceController.postServiceConfigPrice WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }

      $errors = Validator::make($request->only('from_hotline_num', 'to_hotline_num', 'price', 'id', 'init_price', 'service_config_id','operator_telco_id'),
          ['from_hotline_num' => 'required|integer',
              'to_hotline_num' => 'required|integer',
              'price' => 'required',
              'service_config_id' => 'required|integer|exists:service_config,id',
              'operator_telco_id' => 'required|exists:operator_telco,id',
              'init_price' => 'required',
              'id' => 'bail|sometimes|integer|exists:service_config_hotline_price'

      ]);
      if ($errors->fails()) {
        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|ADD_EDIT_SERVICE_CONFIG_HOTLINE_PRICE|" . $logDuration . "|ADD_EDIT_SERVICE_CONFIG_HOTLINE_PRICE_FAIL Invalid data");

        return $this->ApiReturn($errors->errors(), false, "The given data was invalid", 422);
      }

      $id = $request->id;

      $arrConfigPrice = ['service_config_id' => $request->input('service_config_id'),
          'from_hotline_num' => $request->input('from_hotline_num'),
          'to_hotline_num' => $request->input('to_hotline_num'),
          'price' => $request->input('price'),
          'init_price' => $request->input('init_price'),
          'created_at' => date("Y-m-d H:i:s"),
          'updated_at' => date("Y-m-d H:i:s"),
      'operator_telco_id'=>request('operator_telco_id')
      ];
      // return response()->json($arrConfigPrice);
      if (!$id) {
        $res = DB::table('service_config_hotline_price')->insertGetId($arrConfigPrice);
        $this->Activity($arrConfigPrice, "service_config_hotline_price", $res, $arrConfigPrice['service_config_id'], "Create");
      } else {
        unset($arrConfigPrice['created_at']);
        if ($request->status == 2) {
          $arrConfigPrice["status"] = 0;
        }
        DB::table('service_config_hotline_price')->where('id', $id)->update($arrConfigPrice);
        $res = $id;
        $this->Activity($arrConfigPrice, "service_config_hotline_price", $id, $arrConfigPrice['service_config_id'], "Update");
      }
      $logDuration = round(microtime(true) * 1000) - $startTime;
      Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|ADD_EDIT_SERVICE_CONFIG_HOTLINE_PRICE|" . $logDuration . "|ADD_EDIT_SERVICE_CONFIG_HOTLINE_PRICE_SUCCESS");

      return response()->json(array('id' => intval($res)), 200);
    }

    public function postServiceCallPrice(Request $request) {
      $startTime = round(microtime(true) * 1000);

      $user = $request->user;
      if (!$this->checkEntity($user->id, "SET_SERVICE_CONFIG")) {
        Log::info($user->email . '  TRY TO GET ServiceController.postServiceConfigPrice WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }

      $errors = Validator::make($request->only('from_min', 'to_min', 'call_fees', 'id', 'type', 'call_type', 'service_config_id'), ['from_min' => 'required|integer', 'to_min' => 'required|alpha_dash', 'call_fees' => 'required', 'type' => 'required', 'call_type' => 'required|in:0,1', 'service_config_id' => 'required|integer|exists:service_config,id', 'id' => 'bail|sometimes|integer|exists:call_fee_config'

      ]);
      if ($errors->fails()) {
        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|ADD_EDIT_SERVICE_CONFIG_CALL_PRICE|" . $logDuration . "|ADD_EDIT_SERVICE_CONFIG_CALL_PRICE_FAIL INVALID DATA");

        return $this->ApiReturn($errors->errors(), false, "The given data was invalid", 422);
      }

      $id = $request->input('id');

      $arrConfigPrice = ['service_config_id' => $request->input('service_config_id'), 'from_min' => $request->input('from_min'), 'to_min' => $request->input('to_min'), 'type' => $request->input('type'), 'call_fees' => $request->input('call_fees'), 'call_type' => $request->input('call_type'), 'created_at' => date("Y-m-d H:i:s"), 'updated_at' => date("Y-m-d H:i:s")];
      // return response()->json($arrConfigPrice);
      if (!$id) {
        $res = DB::table('call_fee_config')->insertGetId($arrConfigPrice);
        $this->Activity($arrConfigPrice, "call_fee_config", $res, $arrConfigPrice['service_config_id'], "Create");
      } else {
        unset($arrConfigPrice['created_at']);
        if ($request->status == 2) {
          $arrConfigPrice["status"] = 0;
        }
        DB::table('call_fee_config')->where('id', $id)->update($arrConfigPrice);
        $res = $id;
        $this->Activity($arrConfigPrice, "call_fee_config", $id, $arrConfigPrice['service_config_id'], "Update");
      }
      $logDuration = round(microtime(true) * 1000) - $startTime;
      Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|ADD_EDIT_SERVICE_CONFIG_CALL_PRICE|" . $logDuration . "|ADD_EDIT_SERVICE_CONFIG_CALL_PRICE_SUCCESS");

      return response()->json(array('id' => $res), 200);
    }

    public function postServiceSmsPrice(Request $request) {
      $startTime = round(microtime(true) * 1000);

      $user = $request->user;
      if (!$this->checkEntity($user->id, "SET_SERVICE_CONFIG")) {
        Log::info($user->email . '  TRY TO GET ServiceController.postServiceConfigPrice WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }

      $errors = Validator::make($request->only('from_sms', 'to_sms', 'sms_fees', 'id', 'type', 'sms_type', 'service_config_id'), ['from_sms' => 'required|integer', 'to_sms' => 'required|integer', 'sms_fees' => 'required', 'type' => 'required|in:1,2', 'service_config_id' => 'required|integer|exists:service_config,id', 'id' => 'bail|sometimes|integer|exists:sms_fee_config']);
      if ($errors->fails()) {
        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|ADD_EDIT_SERVICE_SMS_PRICE|" . $logDuration . "|ADD_EDIT_SERVICE_SMS_PRICE_FAIL Invalid input data");

        return $this->ApiReturn($errors->errors(), false, "The given data was invalid", 422);
      }
      $id = $request->input('id');
      $arrConfigPrice = ['service_config_id' => $request->input('service_config_id'), 'from_sms' => $request->input('from_sms'), 'to_sms' => $request->input('to_sms'), 'type' => $request->input('type'), 'sms_fees' => $request->input('sms_fees'), 'sms_type' => 2, 'created_at' => date("Y-m-d H:i:s"), 'updated_at' => date("Y-m-d H:i:s")];
      // return response()->json($arrConfigPrice);
      if (!$id) {
        $res = DB::table('sms_fee_config')->insertGetId($arrConfigPrice);
        $this->Activity($arrConfigPrice, "sms_fee_config", $res, $arrConfigPrice['service_config_id'], "Create");
      } else {
        unset($arrConfigPrice['created_at']);
        if ($request->status == 2) {
          $arrConfigPrice["status"] = 0;
        }
        DB::table('sms_fee_config')->where('id', $id)->update($arrConfigPrice);
        $res = $id;
        $this->Activity($arrConfigPrice, "sms_fee_config", $id, $arrConfigPrice['service_config_id'], "Update");
      }

      $logDuration = round(microtime(true) * 1000) - $startTime;
      Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|ADD_EDIT_SERVICE_SMS_PRICE|" . $logDuration . "|ADD_EDIT_SERVICE_SMS_PRICE_SUCCESS");

      return response()->json(array('id' => $res), 200);
    }

    public function postServiceQuantityPrice(Request $request) {
      $startTime = round(microtime(true) * 1000);

      $user = $request->user;
      if (!$this->checkEntity($user->id, "SET_SERVICE_CONFIG")) {
        Log::info($user->email . '  TRY TO GET ServiceController.postServiceConfigPrice WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }

      $errors = Validator::make($request->only('min', 'description', 'id', 'type', 'price', 'service_config_id'), ['min' => 'required|integer', 'description' => 'required|max:250', 'price' => 'required', 'type' => 'required|in:0,1', 'service_config_id' => 'required|integer|exists:service_config,id', 'id' => 'bail|sometimes|integer|exists:quantity_config']);
      if ($errors->fails()) {
        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|ADD_EDIT_SERVICE_QUANTITY_PRICE|" . $logDuration . "|ADD_EDIT_SERVICE_QUANTITY_PRICE_FAIL Invalid input data");

        return $this->ApiReturn($errors->errors(), false, "The given data was invalid", 422);
      }
      $id = $request->id;
      $arrConfigPrice = ['service_config_id' => $request->input('service_config_id'), 'min' => $request->input('min'), 'description' => $request->input('description'), 'type' => $request->input('type'), 'price' => $request->input('price'),];
      // return response()->json($arrConfigPrice);
      if (!$id) {
        $res = DB::table('quantity_config')->insertGetId($arrConfigPrice);
        //            $this->Activity($arrConfigPrice, "quantity_config", $res, $arrConfigPrice['service_config_id'], "Create");
      } else {
        $arrConfigPrice['updated_at'] = date("Y-m-d H:i:s");
        if ($request->status == 2) {
          $arrConfigPrice["status"] = 0;
        }
        DB::table('quantity_config')->where('id', $id)->update($arrConfigPrice);
        $res = $id;
        //            $this->Activity($arrConfigPrice, "quantity_config", $id, $arrConfigPrice['service_config_id'], "Update");
      }
      $logDuration = round(microtime(true) * 1000) - $startTime;
      Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|ADD_EDIT_SERVICE_QUANTITY_PRICE|" . $logDuration . "|ADD_EDIT_SERVICE_QUANTITY_PRICE_SUCCESS");

      return response()->json(array('id' => $res), 200);
    }

    public function postServiceOptionPrice(Request $request) {
      $startTime = round(microtime(true) * 1000);

      $user = $request->user;
      if (!$this->checkEntity($user->id, "SET_SERVICE_CONFIG")) {
        Log::info($user->email . '  TRY TO GET ServiceController.postServiceConfigPrice WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }

      $errors = Validator::make($request->only('from', 'to', 'id', 'type', 'price', 'service_config_id'), ['from' => 'required|integer', 'to' => 'required|integer', 'price' => 'required', 'type' => 'required|in:1,2,3,4,5', 'service_config_id' => 'required|integer|exists:service_config,id', 'id' => 'bail|sometimes|integer|exists:service_option_price']);
      if ($errors->fails()) {
        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|ADD_EDIT_SERVICE_OPTION_PRICE|" . $logDuration . "|ADD_EDIT_SERVICE_OPTION_PRICE_FAIL Invalid input data ");

        return $this->ApiReturn($errors->errors(), false, "The given data was invalid", 422);
      }
      $id = $request->id;
      $arrConfigPrice = ['service_config_id' => $request->input('service_config_id'), 'from' => $request->input('from'), 'to' => $request->input('to'), 'type' => $request->input('type'), 'description' => $request->input('description'), 'price' => $request->input('price'), 'created_at' => date("Y-m-d H:i:s"), 'updated_at' => date("Y-m-d H:i:s")];
      // return response()->json($arrConfigPrice);
      if (!$id) {
        $res = DB::table('service_option_price')->insertGetId($arrConfigPrice);
        //            $this->Activity($arrConfigPrice, "service_option_price", $res, $arrConfigPrice['service_config_id'], "Create");
      } else {
        unset($arrConfigPrice['created_at']);
        if ($request->status == 2) {
          $arrConfigPrice["status"] = 0;
        }
        DB::table('service_option_price')->where('id', $id)->update($arrConfigPrice);
        $res = $id;
        //            $this->Activity($arrConfigPrice, "service_option_price", $id, $arrConfigPrice['service_config_id'], "Update");
      }

      $logDuration = round(microtime(true) * 1000) - $startTime;
      Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|ADD_EDIT_SERVICE_OPTION_PRICE|" . $logDuration . "|ADD_EDIT_SERVICE_OPTION_PRICE_SUCCESS");

      return response()->json(array('id' => $res), 200);
    }

    //
    public function deleteQuantityPrice(Request $request) {
      $startTime = round(microtime(true) * 1000);

      $user = $request->user;
      if (!$this->checkEntity($user->id, "SET_SERVICE_CONFIG")) {
        Log::info($user->email . '  TRY TO GET ServiceController.postServiceConfigPrice WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }

      $errors = Validator::make($request->only('id'), ['id' => 'required|integer|exists:quantity_config']);
      if ($errors->fails()) {
        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|DELETE_QUANTITY_PRICE|" . $logDuration . "|DELETE_QUANTITY_PRICE_FAIL INVALID Input data");

        return $this->ApiReturn($errors->errors(), false, "The given data was invalid", 422);
      }

      $id = $request->id;
      DB::table('quantity_config')->where("id", $id)->update(['status' => 2, 'updated_at' => date('Y-m-d H:i:s')]);
      //        $this->Activity(['status' => 2, 'updated_at' => date('Y-m-d H:i:s')], "quantity_config", $id, 0, "Soft Delete");
      $logDuration = round(microtime(true) * 1000) - $startTime;
      Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|DELETE_QUANTITY_PRICE|" . $logDuration . "|DELETE_QUANTITY_PRICE_SUCCESS");

      return response()->json(['status' => true], 200);
    }

    public function deleteOptionPrice(Request $request) {
      $startTime = round(microtime(true) * 1000);
      $user = $request->user;
      if ($user->role != ROLE_ADMIN) {
        return ['error' => 'Permission denied'];
      }

      $errors = Validator::make($request->only('id'), ['id' => 'required|integer|exists:service_option_price']);
      if ($errors->fails()) {
        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|DELETE_OPTION_PRICE|" . $logDuration . "|DELETE_OPTION_PRICE_FAIL Invalid input data");

        return $this->ApiReturn($errors->errors(), false, "The given data was invalid", 422);
      }

      $id = $request->id;
      DB::table('service_option_price')->where("id", $id)->update(['status' => 2, 'updated_at' => date('Y-m-d H:i:s')]);
      $this->Activity(['status' => 2, 'updated_at' => date('Y-m-d H:i:s')], "service_option_price", $id, 0, "Soft Delete");
      $logDuration = round(microtime(true) * 1000) - $startTime;
      Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|DELETE_OPTION_PRICE|" . $logDuration . "|DELETE_OPTION_PRICE_SUCCESS");

      return response()->json(['status' => true], 200);
    }

    public function deleteSmsPrice(Request $request) {
      $user = $request->user;
      if ($user->role != ROLE_ADMIN) {
        return ['error' => 'Permission denied'];
      }

      $startTime = round(microtime(true) * 1000);
      $errors = Validator::make($request->only('id'), ['id' => 'required|integer|exists:sms_fee_config']);
      if ($errors->fails()) {
        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|DELETE_SMS_PRICE|" . $logDuration . "|DELETE_SMS_PRICE_FAIL Invalid input data");
        return $this->ApiReturn($errors->errors(), false, "The given data was invalid", 422);
      }
      $id = $request->id;
      DB::table('sms_fee_config')->where("id", $id)->update(['status' => 2, 'updated_at' => date('Y-m-d H:i:s')]);
      //        $this->Activity(['status' => 2, 'updated_at' => date('Y-m-d H:i:s')], "sms_fee_config", $id, 0, "Soft Delete");

      $logDuration = round(microtime(true) * 1000) - $startTime;
      Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|DELETE_SMS_PRICE|" . $logDuration . "|DELETE_SMS_PRICE_SUCCESS");
      return response()->json(['status' => true], 200);
    }

    public function deleteCallPrice(Request $request) {
      $startTime = round(microtime(true) * 1000);

      $user = $request->user;
      if (!$this->checkEntity($user->id, "SET_SERVICE_CONFIG")) {
        Log::info($user->email . '  TRY TO GET ServiceController.postServiceConfigPrice WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }

      $errors = Validator::make($request->only('id'), ['id' => 'required|integer|exists:call_fee_config']);
      if ($errors->fails()) {
        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|DELETE_CALL_PRICE|" . $logDuration . "|DELETE_CALL_PRICE_FAIL Invalid input data");

        return $this->ApiReturn($errors->errors(), false, "The given data was invalid", 422);
      }
      $id = $request->id;
      DB::table('call_fee_config')->where("id", $id)->update(['status' => 2, 'updated_at' => date('Y-m-d H:i:s')]);
      //        $this->Activity(['status' => 2, 'updated_at' => date('Y-m-d H:i:s')], "call_fee_config", $id, 0, "Soft Delete");

      $logDuration = round(microtime(true) * 1000) - $startTime;
      Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|DELETE_CALL_PRICE|" . $logDuration . "|DELETE_CALL_PRICE_SUCCESS");
      return response()->json(['status' => true], 200);
    }

    public function deleteConfigHotlinePrice(Request $request) {
      $startTime = round(microtime(true) * 1000);

      $user = $request->user;
      if (!$this->checkEntity($user->id, "SET_SERVICE_CONFIG")) {
        Log::info($user->email . '  TRY TO GET ServiceController.postServiceConfigPrice WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }

      $errors = Validator::make($request->only('id'), ['id' => 'required|integer|exists:service_config_hotline_price']);
      if ($errors->fails()) {
        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|DELETE_CONFIG_HOTLINE_PRICE|" . $logDuration . "|DELETE_CONFIG_HOTLINE_PRICE_FAIL Invalid input data");

        return $this->ApiReturn($errors->errors(), false, "The given data was invalid", 422);
      }
      $id = $request->id;
      DB::table('service_config_hotline_price')->where("id", $id)->update(['status' => 2, 'updated_at' => date('Y-m-d H:i:s')]);
      //        $this->Activity(['status' => 2, 'updated_at' => date('Y-m-d H:i:s')], "service_config_hotline_price", $id, 0, "Soft Delete");
      $logDuration = round(microtime(true) * 1000) - $startTime;
      Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|DELETE_CONFIG_HOTLINE_PRICE|" . $logDuration . "|DELETE_CONFIG_HOTLINE_PRICE_SUCCESS");

      return response()->json(['status' => true], 200);
    }

    public function deleteConfigPrice(Request $request) {
      $startTime = round(microtime(true) * 1000);
      $user = $request->user;
      if (!$this->checkEntity($user->id, "SET_SERVICE_CONFIG")) {
        Log::info($user->email . '  TRY TO GET ServiceController.postServiceConfigPrice WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }

      $errors = Validator::make($request->only('id'), ['id' => 'required|integer|exists:service_config_price']);
      if ($errors->fails()) {
        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|DELETE_CONFIG_PRICE|" . $logDuration . "|DELETE_CONFIG_PRICE_FAIL Invalid input data");

        return $this->ApiReturn($errors->errors(), false, "The given data was invalid", 422);
      }

      $id = $request->id;
      DB::table('service_config_price')->where("id", $id)->update(['status' => 2, 'updated_at' => date('Y-m-d H:i:s')]);
      $this->Activity(['status' => 2, 'updated_at' => date('Y-m-d H:i:s')], "service_config_price", $id, 0, "Soft Delete");

      $logDuration = round(microtime(true) * 1000) - $startTime;
      Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|DELETE_CONFIG_PRICE|" . $logDuration . "|DELETE_CONFIG_PRICE_SUCCESS");

      return response()->json(['status' => true], 200);
    }

    public function getServiceZoneQuantityType(Request $request) {
      $lst = DB::table("prefix_type_name")->select("prefix_type_id as id", 'name', 'prefix_group')->get();
      $lstq = DB::table("prefix_type_group")->get();

      return response()->json(['prefix' => $lst, 'group' => $lstq]);
    }

    public function postServicePrefixType(Request $request) {
      $startTime = round(microtime(true) * 1000);

      $user = $request->user;
      if (!$this->checkEntity($user->id, "SET_SERVICE_CONFIG")) {
        Log::info($user->email . '  TRY TO GET ServiceController.postServiceConfigPrice WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }

      $data = $request->only("id", "service_config_id", "prefix_type_id",
          "description", "prefix_caller", "prefix_called",
          "prefix_caller_match_switch",
          "prefix_called_match_switch",
          "prefix_match_constraint",
          "charge_block_type",
          "priority",
          "operator_telco_id"
      );

      $errors = Validator::make($data, [

        "id" => "nullable|exists:service_prefix_type,id",
          "service_config_id" => "required|exists:service_config,id",
          "operator_telco_id" => "required|exists:operator_telco,id",
          "prefix_type_id" => "required",
          "description" => "required|max:100",
          "prefix_caller" => "nullable|max:2000",
          "prefix_called" => "nullable|max:2000",
          "prefix_caller_match_switch" => "required|in:0,1",
          "prefix_called_match_switch" => "required|in:0,1",
          "prefix_match_constraint" => "required|in:0,1,2",
          "charge_block_type" => "required|in:0,1,2",
          "priority" => "required"

      ]);
      if ($errors->fails()) {
        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|ADD_EDIT_SERVICE_PREFIX_TYPE|" . $logDuration . "|ADD_EDIT_SERVICE_PREFIX_TYPE_FAIL Invalid input data");

        return $this->ApiReturn($errors->errors(), false, "The given data was invalid", 422);
      }

      // Check exists priority

      $data["prefix_caller"] = str_replace("\n", '', $request->prefix_caller);
      $data["prefix_called"] = str_replace("\n", '', $request->prefix_called);

      // check if existst ID

      if ($request->prefix_type_id == -1) {
        // Thêm mới prefizz

        if (!$request->prefix_type_name) {
          return $this->ApiReturn(['prefix_type_name' => ['Prefix name not empty']], false, "The given data was invalid", 422);
        }

        $lastID = ServicePrefixTypeName::max("prefix_type_id") + 1;

        $prefixName = new  ServicePrefixTypeName();
        $prefixName->prefix_type_id = $lastID;
        $prefixName->prefix_group = $request->prefix_group;
        $prefixName->name = $request->prefix_type_name;
        $prefixName->save();

        $data['prefix_type_id'] = $lastID;
      }

//      if ($request->prefix_group && $request->prefix_type_id && $request->prefix_type_id > -1) {
//        $prefixName = ServicePrefixTypeName::where('prefix_type_id',$request->prefix_type_id)->first();
//        $prefixName->prefix_group = $request->prefix_group;
//        $prefixName->save();
//      }

      if ($request->id) {
        if (DB::table("service_prefix_type")->where('service_config_id', $request->service_config_id)->where('priority', $request->priority)->where('id', '<>', $request->id)->exists()) {
          $logDuration = round(microtime(true) * 1000) - $startTime;
          Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|ADD_EDIT_SERVICE_PREFIX_TYPE|" . $logDuration . "|ADD_EDIT_SERVICE_PREFIX_TYPE_FAIL Duplicate priority when EDIT ");

          return $this->ApiReturn(['priority' => ["Duplicated Priority"]], false, "The given data was invalid", 422);
        }

        unset($data["id"]);

        $res = DB::table("service_prefix_type")->where("id", $request->id)->update($data);

        $returnData = $request->id;

        $this->Activity($data, "service_prefix_type", $returnData, 0, "Update service_prefix_type");
      } else {
        if (DB::table("service_prefix_type")->where('service_config_id', $request->service_config_id)->where('priority', $request->priority)->exists()) {
          $logDuration = round(microtime(true) * 1000) - $startTime;
          Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|ADD_EDIT_SERVICE_PREFIX_TYPE|" . $logDuration . "|ADD_EDIT_SERVICE_PREFIX_TYPE_FAIL Duplicate priority when ADDNEW");
          return $this->ApiReturn(['priority' => ["Duplicated Priority"]], false, "The given data was invalid", 422);
        }

        $returnData = DB::table("service_prefix_type")->insertGetId($data);
        $this->Activity($data, "service_prefix_type", $returnData, 0, "Create service_prefix_type");
      }

      $logDuration = round(microtime(true) * 1000) - $startTime;
      Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|ADD_EDIT_SERVICE_PREFIX_TYPE|" . $logDuration . "|ADD_EDIT_SERVICE_PREFIX_TYPE_SUCCESS");

      return $returnData;
    }

    public function deleteServicePrefixType(Request $request) {
      $startTime = round(microtime(true) * 1000);
      $user = $request->user;
      if (!$this->checkEntity($user->id, "SET_SERVICE_CONFIG")) {
        Log::info($user->email . '  TRY TO GET ServiceController.postServiceConfigPrice WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }

      $errors = Validator::make($request->only("id"), [

        "id" => "required|exists:service_prefix_type,id",

      ]);
      if ($errors->fails()) {
        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|DELETE_SERVICE_PREFIX_TYPE|" . $logDuration . "|DELETE_SERVICE_PREFIX_TYPE_FAIL Invalid input data");

        return $this->ApiReturn($errors->errors(), false, "The given data was invalid", 422);
      }

      // Start delete
      DB::table("service_prefix_type")->where("id", $request->id)->delete();

      $this->Activity(["id" => $request->id, "Date" => date("Y-m-d H:i:s")], "service_prefix_type", $request->id, 0, "Delete service_prefix_type");

      $logDuration = round(microtime(true) * 1000) - $startTime;
      Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|DELETE_SERVICE_PREFIX_TYPE|" . $logDuration . "|DELETE_SERVICE_PREFIX_TYPE_SUCCESS");

      return response()->json(["status" => true, 'message' => "success delete"], 200);
    }

    private function setDefaultServicePrefixType($service_id) {
      $data = ['service_config_id' => $service_id, 'priority' => 999, 'prefix_type_id' => 2, 'description' => 'Default External call', 'prefix_caller' => '', 'prefix_called' => '', 'prefix_caller_match_switch' => 0, 'prefix_called_match_switch' => 0, 'prefix_match_constraint' => 0, 'charge_block_type' => 0];
      $res = DB::table("service_prefix_type")->insertGetId($data);

      $this->Activity($data, "service_prefix_type", $res, 0, "Create default service_prefix_type");
      return $res;
    }

    public function getOperatorTelco()
    {
      $lstOperatorTelCo= DB::select("select description, id from operator_telco");
      return response()->json(['status'=>true, 'data'=>$lstOperatorTelCo],200);
    }
  }
