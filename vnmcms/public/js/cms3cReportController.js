cms3c.controller('reportController', function ($scope, ApiServices,$filter,loginCheck, ngTableParams) {

	var Token=loginCheck.getEntity().then(function (value) {

		$scope.entity=value.entity;
		$scope.authUser= value;
	});


	$scope.message = "Report controller";
    $scope.report={};
    $scope.reportParam={};

    $scope.selectedReport='quantity';

	$scope.optionsDate = '{format:"YYYY/MM/DD", useCurrent: false,debug:true}';
	$scope.optionsMonth = '{format:"YYYY-MM", useCurrent: false,debug:true}';
$scope.reportMonthlyParam={};
$scope.reportMonthlyParam.start_date=moment().format("YYYY-MM-DD");
$scope.reportMonthlyParam.end_date=moment().format("YYYY-MM-DD");
$scope.monthlyReport={tab:"customer",acumulated:[], count:0,prefix_charge:[]};

	$scope.mraCols = [
		{field: "companyname", title: "Tên công ty", sortable: "companyname", show: true},
		{field: "enterprise_number", title: "Enterprise number", sortable: "enterprise_number", show: true},
		{field: "addr", title: "Địa chỉ", sortable: "addr", show: true},
		{field: "totalAmount", title: "Lũy kế", sortable: "totalAmount", show: true},

	];

$scope.selectDateRange= function (data) {
    $scope.report.dateRange=data;
    $scope.reportParam.datePeriod=data;
    if(!$scope.reportParam.report)
    {
        $scope.selectedReport='quantity'; // mặc định là sản lượng
    }

  //  $scope.reportParam.dateRange={};


    if(data !='manual')
    {
    $scope.viewReport($scope.selectedReport);
    }


}



$scope.selectDateManual=function () {

    $scope.viewReport($scope.selectedReport);

}


$scope.viewReport= function (data) {


        var defaultRange='day';
        $scope.selectedReport=data;
        $scope.reportParam.report=data;

        if(data=='monthly_audit')
        {
            $scope.viewMonthlyAudit('prefix');
            return;
        }

         if(data=='detail_audit')
        {
            $scope.viewDetailAudit('prefix');
            return;
        }



        if (!$scope.report.dateRange) {

            $scope.report.dateRange=defaultRange;
            $scope.reportParam.datePeriod = defaultRange; // mặc định là ngày

        }
        // Render data

	if ($scope.reportParam.datePeriod == 'manual') {
		$scope.reportParam.start_date = $filter('date')($scope.reportParam.start_date, 'yyyy/MM/dd 00:00:00', 'GMT+07');
		$scope.reportParam.end_date = $filter('date')($scope.reportParam.end_date, 'yyyy/MM/dd 23:59:59', 'GMT+07');
	}
	$("#loading").modal("show");
	var res = ApiServices.postViewReport($scope.reportParam, $scope.reportParam.report);
	res.then(function (data) {
		$("#loading").modal("hide");
		/**
		 *Highchart QUANTITY
		 *
		 *
		 */
		switch ($scope.selectedReport) {
			case "quantity":
				$scope.report.quantity = data.data;
				var res = data.data.fee;
				var resDate = data.data.date;
				if (res.length > 0) {
					var datachart = [];
					for (var i = 0; i < res.length; i++) {
						datachart.push({
							name: $filter('translate')('REPORT.QUANTITY.' + res[i].name),
							y: res[i].percent,
							val: res[i].amount,
							unit: res[i].unit,
							count: res[i].count
						});
					}
					Highcharts.chart('chart-quantity', {
						chart: {
							plotBackgroundColor: null,
							plotBorderWidth: null,
							plotShadow: false,
							type: 'pie'
						},
						title: {
							text: $filter('translate')('REPORT.CHART') + " " + $filter('translate')('REPORT.NAV.QUANTITY')
						},
						subtitle: {
							text: $filter('translate')('LBL.FROM_DATE') + ": " + resDate.start_date + " " + $filter('translate')('LBL.TO_DATE') + ": " + resDate.end_date
						},
						tooltip: {
							pointFormat: '{series.name}: <b> {point.val} Đ</b><br> Sản lượng: {point.count} <sup>{point.unit}</sup>'
						},
						plotOptions: {
							pie: {
								allowPointSelect: true,
								cursor: 'pointer',
								dataLabels: {
									enabled: true,
									format: '<b>' + $filter('translate')('') + '{point.name}</b> : {point.percentage:.1f}%',
									style: {
										color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
									}
								}
							}
						},
						series: [{
							name: 'Doanh thu',
							colorByPoint: true,
							data: datachart
						}]
					});
				}
				/// END API
				break;
			case "flow":
				$scope.report.flow = data.data;
				var resDate = data.data.date;
				Highcharts.chart('chart_flow', {
					chart: {
						type: 'line'
					},
					title: {
						text: $filter('translate')('REPORT.CHART') + " " + $filter('translate')('REPORT.NAV.FLOW')
					},
					subtitle: {
						text: $filter('translate')('LBL.FROM_DATE') + ": " + resDate.start_date + " " + $filter('translate')('LBL.TO_DATE') + ": " + resDate.end_date
					},
					xAxis: {
						categories: $scope.report.flow.date_range
					}, tooltip: {
						crosshairs: true,
						shared: true
					},
					yAxis: [{ // Primary yAxis
						labels: {
							format: '{value} phút',
							style: {
								color: Highcharts.getOptions().colors[1]
							}
						},
						title: {
							text: 'Số phút',
							style: {
								color: Highcharts.getOptions().colors[1]
							}
						}
					}, { // Secondary yAxis
						title: {
							text: 'Số cuộc gọi',
							style: {
								color: Highcharts.getOptions().colors[0]
							}
						},
						labels: {
							format: '{value} cuộc gọi',
							style: {
								color: Highcharts.getOptions().colors[0]
							}
						},
						opposite: true
					}],
					plotOptions: {
						line: {
							dataLabels: {
								enabled: true
							},
							enableMouseTracking: true
						}
					},
					series: [{
						name: 'Cuộc gọi thành công',
						data: $scope.report.flow.call_success,
						tooltip: {
							valueSuffix: ' cuộc gọi'
						}
					}, {
						name: 'Cuộc gọi không gặp khách',
						data: $scope.report.flow.call_failed,
						tooltip: {
							valueSuffix: ' cuộc gọi'
						}
					},
						{
							name: 'Số phút gọi',
							type: 'column',
							data: $scope.report.flow.call_time,
							tooltip: {
								valueSuffix: ' phút'
							}
						}
					]
				});
				break;
			case "customer":
				$scope.report.customer = data.data;
				var resDate = data.data.date;
				Highcharts.chart('chart_customer', {
					chart: {
						type: 'line'
					},
					title: {
						text: $filter('translate')('REPORT.CHART') + " " + $filter('translate')('REPORT.NAV.CUSTOMER')
					},
					subtitle: {
						text: $filter('translate')('LBL.FROM_DATE') + ": " + resDate.start_date + " " + $filter('translate')('LBL.TO_DATE') + ": " + resDate.end_date
					},
					xAxis: {
						categories: $scope.report.customer.date_range
					}, tooltip: {
						crosshairs: true,
						shared: true
					},
					yAxis: [{ // Primary yAxis
						labels: {
							format: '{value} hotline',
							style: {
								color: Highcharts.getOptions().colors[1]
							}
						},
						title: {
							text: 'Dịch vụ',
							style: {
								color: Highcharts.getOptions().colors[1]
							}
						}
					}, { // Secondary yAxis
						title: {
							text: 'Khách hàng',
							style: {
								color: Highcharts.getOptions().colors[0]
							}
						},
						labels: {
							format: '{value} khách',
							style: {
								color: Highcharts.getOptions().colors[0]
							}
						},
						opposite: true
					}],
					plotOptions: {
						line: {
							dataLabels: {
								enabled: true
							},
							enableMouseTracking: true
						}
					},
					series: [{
						name: 'Khách hàng',
						data: $scope.report.customer.customer,
						tooltip: {
							valueSuffix: ' khách'
						}
					}, {
						name: 'Hotline',
						type: 'column',
						data: $scope.report.customer.hotline,
						tooltip: {
							valueSuffix: ' dịch vụ'
						}
					}
					]
				});
				break;
			default:
				break;
		}
	}, function (reason) {
		$.jGrowl("Lỗi tải báo cáo")
		$("#loading").modal("hide");
	})
}
	$scope.viewMonthlyAudit = function (data) {
		$scope.monthlyReport.tab = data;
		$scope.onSearchMonthlyAudit($scope.reportMonthlyParam);
	}
	$scope.onSearchMonthlyAudit = function (data) {
		$("#loading").modal("show");
		// var auditParam={};
		$scope.reportMonthlyParam.start_date = (moment(data && data.start_date ? data.start_date : null).format("YYYY-MM-DD 00:00:00"));
		$scope.reportMonthlyParam.end_date = (moment(data && data.end_date ? data.end_date : null).format("YYYY-MM-DD 23:59:59"));
		ApiServices.postViewMonthlyAudit($scope.reportMonthlyParam).then(function (response) {
			// console.log(response);
			// $scope.monthlyReport.acumulated = response.data.data.acumulated;
			// $scope.monthlyReport.count = response.data.data.count;
			$scope.monthlyReport.charge_logs = response.data.data.charge_logs;
			$("#loading").modal("hide");
		}, function (reason) {
			$.jGrowl("Lỗi tải báo cáo")
			$("#loading").modal("hide");
		})
	}
	$scope.selectDateRange('day');
	$scope.viewDetailAudit = () => {
		// init audit all
        $scope.subNav='growth';
		// var date = new Date(), y = date.getFullYear(), m = date.getMonth();
        $scope.queryDetail= {};
        $scope.queryDetail.start_date= new Date();
        $scope.queryDetail.end_date= new Date();
        $scope.queryDetail.enterprise_number= "0968000010";
        $scope.queryDetail.billing_month=moment().subtract(1, 'months').format("YYYY-MM-01");
        $scope.queryDetail.prefix_group=null;
		// $scope.searchReportGrowth($scope.queryDetail);
        ApiServices.getServiceZoneQuantityType().then(result=>{

            $scope.lstPrefixGroup=result.data.group;
            $scope.lstPrefixGroup.unshift({id:"","group_name":" -- Tất cả hướng --"});
        },reason => {

        })

	}
	$scope.subNavDetailReport = (nav) => {
	    $scope.subNav= nav;
	}

	$scope.loadCustomers=(query)=>

    {

        console.log(query)
        return ApiServices.searchCustomer(query).then(response=>{
			var countries = response.data;
			return countries.filter(function(country) {
			    console.log(country)
				return country.enteprise_number;
			});
        })

    }


	$scope.colReportGrowth=[
        {field:'day', name:'Ngày', format:0},
        {field:'total_call', name:'Cuộc gọi',format:1},
        {field:'total_duration', name:'Thời lượng',format:2},
        {field:'total_amount', name:'Doanh thu',format:1}
    ]


	$scope.colReportAudit=[
        {field:'day', name:'Ngày', format:0},
        {field:'customer_name', name:'Tên khách hàng',format:0},
        {field:'prefixName', name:'Loại cuộc gọi',format:0},
		{field:'total_duration', name:'Thời lượng',format:2},
		{field:'total_amount', name:'Thành tiền',format:1},
    ]

	$scope.searchReportGrowth=(query)=>{

		if(!query.enterprise_number)
		{
			$.jGrowl("Số đại diện bắt buộc nhập",{theme:'error'})
			return;
		}
        let postData= new Set();
	    postData.start_date= moment(query.start_date).format("YYYY-MM-DD 00:00:00");
	    postData.end_date= moment(query.end_date).format("YYYY-MM-DD HH:mm:ss");
	    postData.prefix_group= query.prefix_group;
	    postData.enterprise_number= query.enterprise_number;
	    ApiServices.searchReportGrowth(postData).then(res=>{

	        $scope.searchReportGrowthData= res.data;

        },reason => {
	        $.jGrowl(reason.data.message,{theme:'error'})
        })

    }


	$scope.searchReportAudit=(query)=>{


	    if(!query.enterprise_number)
        {
			$.jGrowl("Số đại diện bắt buộc nhập",{theme:'error'})
            return;
        }
        let postData= new Set();
	    postData.start_date= moment(query.start_date).format("YYYY-MM-DD 00:00:00");
	    postData.end_date= moment(query.end_date).format("YYYY-MM-DD HH:mm:ss");
	    postData.prefix_group= query.prefix_group;
	    postData.enterprise_number= query.enterprise_number;
	    ApiServices.searchReportAudit(postData).then(res=>{

	        $scope.searchReportAuditData= res.data;

        },reason => {
	        $.jGrowl(reason.data.message,{theme:'error'})
        })

    }

    $scope.searchReportBilling=(query)=>{
		console.log("Data info ", query);

		let postData={enterprise_number:query.enterprise_number};
		postData.billing_month= moment(query.billing_month).format("YYYY-MM-01 00:00:00");
        console.log("PostData info ", postData);

        if(!postData.enterprise_number)
        {
            $.jGrowl("Số đại diện bắt buộc nhập",{theme:'error'})
            return;
        }if(!postData.billing_month)
        {
            $.jGrowl("Bạn chưa chọn tháng",{theme:'error'})
            return;
        }
        if( (query.billing_month).isAfter(moment()))
		{
            $.jGrowl("Tháng cước đang truy xuất ở tương lai",{theme:'error'})
            return;
		}

        ApiServices.searchReportMonthBilling(postData).then(res=>{

            let renderResult= res.data;



            $scope.searchReportBillingData= renderResult
            $scope.searchReportBillingData.tax= (renderResult.sum.totalbeforetax/100)*10;



        },reason => {
            $.jGrowl(reason.data.message,{theme:'error'})
        })
	}

});


// function htmlTableToExcel(type, obj){
// 	var data = document.getElementById(obj);
//
// 	let tableName='Sheet1';
// 	var excelFile = XLSX.utils.table_to_book(data, {sheet: tableName});
//     const sheet = excelFile.Sheets[tableName];
//     sheet['A1'].s = { font: { sz: 14 } };
//
// 	XLSX.write(excelFile, { bookType: type, bookSST: true, type: 'base64' });
//
// 	XLSX.writeFile(excelFile, obj+'FilExportVconnect.' + type);
// }

function htmlTableToExcel(type, obj){
    var data = document.getElementById(obj);

    let tableName = 'Sheet1';
    var excelFile = XLSX.utils.table_to_book(data, {sheet: tableName});

    var excelBase64 = XLSX.write(excelFile, { bookType: type, bookSST: true, type: 'base64' });

   XLSX.writeFile(excelFile, obj+'FilExportVconnect.' + type);


}

function exportDoc(obj){
    var header = "<html xmlns:o='urn:schemas-microsoft-com:office:office' "+
        "xmlns:w='urn:schemas-microsoft-com:office:word' "+
        "xmlns='http://www.w3.org/TR/REC-html40'>"+
        "<head><meta charset='utf-8'><title>Export HTML to Word Document with JavaScript</title></head><body>";
    var footer = "</body></html>";
    var sourceHTML = header+document.getElementById(obj).innerHTML+footer;

    var source = 'data:application/vnd.ms-word;charset=utf-8,' + encodeURIComponent(sourceHTML);
    var fileDownload = document.createElement("a");
    document.body.appendChild(fileDownload);
    fileDownload.href = source;
    fileDownload.download = 'document.doc';
    fileDownload.click();
    document.body.removeChild(fileDownload);
}