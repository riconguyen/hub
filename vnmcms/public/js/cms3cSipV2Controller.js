cms3c.controller('sipV2Controller', function ($scope, ApiServices,$filter,  $location, dataShare, loginCheck,ngTableParams,$filter) {

    loginCheck.getEntity().then(function (value) {
        $scope.authUser = value;
        $scope.entity = value.entity;
    });
    $scope.lstCustomers=[]

    ApiServices.getRecentCustomers().then(result=>{

        (result.data?.lstData).map(item=>{
            $scope.lstCustomers.push(item.enterprise_number)
        })
    })

    $scope.lstCallTypes= [
        {"id":1,label:"Gọi thành công"},
        {"id":2,label:"Gọi không thành công"},
    ]
    $scope.sipParam={enterprise_number:"", caller:"", start_date:"", end_date:"", type:1}
    let date= new Date();
    $scope.sipParam.start_date= new Date(date.getFullYear(), date.getMonth(), 1);
    $scope.sipParam.end_date= new Date();
    $scope.sipLogCols = [


        {field: "setup_time", title: "Ngày gọi", sortable: "created_at", show: true},
        {field: "direction", title: "#", sortable: "direction", show: true},
        {field: "CLI", title: "Số gọi", sortable: "CLI", show: true},
        {field: "CLD", title: "Số nhận", sortable: "CLD", show: true},
        {field: "duration", title: "Thời lượng", sortable: "duration", show: true},
        {field: "disconnect_cause", title: "Lý do", sortable: "disconnect_cause", show: true},
        {field: "charge_status", title: "Tình trạng tính phí", sortable: "charge_status", show: true},
        {field: "state", title: "Trạng thái", sortable: "state", show: true},
        {field: "reject_cause", title: "Mã lỗi (*)", sortable: "reject_cause", show: true},
        {field: "call_brandname", title: "Brandname", sortable: "call_brandname", show: true},
        // {field: "nbr", title: "Tác vụ", sortable: "nbr", show: true}


    ];




    $scope.onSearch= ()=>{

        console.log($scope.sipParam)
        let errors=[];
        if(!$scope.sipParam.enterprise_number)
        {
            errors.push("<li>Trường Enterprise Number không được để trống</li>")
        }
        if(!$scope.sipParam.caller)
        {
            errors.push("<li>Trường Số cần kiểm tra không được để trống</li>")
        }
        if(!$scope.sipParam.start_date)
        {
            errors.push("<li>Trường ngày bắt đầu không được để trống</li>")
        }
        if(!$scope.sipParam.end_date)
        {
            errors.push("<li>Trường ngày kết thúc không được để trống</li>")
        }

        var startDate = moment($scope.sipParam.start_date);
        var endDate = moment($scope.sipParam.end_date);

        if (startDate.isAfter(endDate)) {
            errors.push("<li>Ngày bắt đầu không được sau ngày kết thúc </li>")
        } else
        {
            var duration = moment.duration(endDate.diff(startDate));
            var days = duration.asDays();
            if(days > 31  )
            {
                errors.push("<li>Khoảng ngày bắt đầu và kết thúc không quá 31 ngày </li>")
            }

        }

        if(errors.length>0)
        {
            $.jGrowl(`<b>Có lỗi xảy ra</b><ul>${errors.join("")}</ul>`)
            return;
        }




        let postData={
            start_date:startDate.format("YYYY-MM-DD 00:00:00"),
            end_date: endDate.format("YYYY-MM-DD 23:59:59"),
            enterprise_number: $scope.sipParam.enterprise_number,
            caller: $scope.sipParam.caller,
            type:$scope.sipParam.type,


        }

        console.log("postData",postData)

        $scope.onSearchLog(postData)
    };

    $scope.viewCallDebugPython = function (data) {
        $scope.currentCall = data;
        if ($scope.userRole == 1) {
            $scope.currentCall.loading = false;
            var data2 = {"cld": data.CLD};
            res = ApiServices.callDebugPython(data.CLI, data2);
            res.then(function (data) {
                $("#sipData").html("<pre>" + data.data + "</pre>");
                $scope.currentCall.loading = true;
            })
        }
        else
        {
            $scope.currentCall.loading = true;
        }
        $("#callDialogDebugV2").modal('show');
    }

    $scope.onSearchLog= function(param)
    {
        console.log(param)
        $("#loading").modal("show");

        // $scope.sipLogParam.start_date = $filter('date')($scope.sipLogParam.start_date, 'yyyy/MM/dd 00:00:00', 'GMT+07');
        // $scope.sipLogParam.end_date = $filter('date')($scope.sipLogParam.end_date, 'yyyy/MM/dd 23:59:59', 'GMT+07');

        $scope.sipLogParam=param;


        if (!$scope.sbcSipLog) {
            $scope.sbcSipLog = new ngTableParams({
                    page: 1, // show first page
                    count:20   // count per page

                }, {
                    counts: [20,50,100],
                    getData: function ($defer, params) {
                        $scope.sipLogParam.page = params.page();
                        $scope.sipLogParam.count = params.count();
                        $scope.sipLogParam.sorting = $scope.sbcSipLog.orderBy().toString();
                        $scope.sipLogParam.tableGroupBy = $scope.sbcSipLog.tableGroupBy;
                        $("#loading").modal("show");
                        ApiServices.searchCallLog($scope.sipLogParam).then(function (response) {
                                $("#loading").modal("hide");
                                $scope.lstLog = response.data.call_history;
                                $scope.sipLogParam.start_date= response.data.start_date;
                                $scope.sipLogParam.end_date= response.data.end_date;

                                $scope.lstLogCount = response.data.count;
                                // $scope.userAgent= response.data.data.user;

                                if (response.data.count <= $scope.sbcSipLog.parameters().count) {
                                    $scope.sbcSipLog.parameters().page = 1;
                                }
                                params.total($scope.lstLogCount);
                                $defer.resolve($scope.lstLog);
                            }, function (response) {
                                $("#loading").modal("hide");
                                $scope.lstLog = [];
                                $scope.lstLogCount = -1;
                            }
                        );
                    }
                }
            )
        }
        else {
            $scope.sbcSipLog.reload();
        }
    }






});
