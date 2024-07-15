cms3c.controller('nd91Controller', function ($scope, ApiServices,$filter,loginCheck, ApiUsers, ngTableParams) {

	$scope.entity={};
	loginCheck.getEntity().then(function (value) {
		$scope.entity = value.entity;
		$scope.authUser = value;
		$scope.lstNavNd91 = [
			{id: "dnc", title: "Cấu hình ND91", permission: !!$scope.entity.ND91_CONFIG},
			{id: "quota", title: "Hạn mức", permission: !!$scope.entity.ND91_CONFIG},
			{id: "time_range", title: "Thời gian", permission: !!$scope.entity.ND91_CONFIG},
			{id: "report", title: "Báo cáo", permission: !!$scope.entity.ND91_REPORT},
		]
	});
	$scope.initNd91 = function () {
		ApiServices.getInitNd91().then(function (value) {
			$scope.lstDNCConfig = value.data && value.data.status ? value.data.list : [];
			for (var i = 0; i < $scope.lstDNCConfig.length; i++) {
				$scope.lstDNCConfig[i].active = parseInt($scope.lstDNCConfig[i].active);
			}
		}, reason => {
			$.jGrowl("Không thể tải cấu hình")
		})
	}
	$scope.initNd91();

	$scope.editRuleDnc= function (item) {
		$scope.dncConfig= angular.copy(item);
		$scope.dncConfig.active= parseInt(angular.copy(item.active));
		$scope.dncConfig.apply_rule= angular.copy(item.apply_rule).toString();

	}

	$scope.onSaveConfigDnc= function (item) {
		console.log(item)

		ApiServices.setNd91RuleConfig(item).then(function (value) {
			$.jGrowl("Cập nhật thành công")
			$scope.initNd91();
			$scope.dncConfig= null;

		}, function (reason) {

			$.jGrowl("Không cập nhật được cấu hình, có lỗi xảy ra")
		});

	}

	$scope.initND91QuotaConfig= function()
	{
		ApiServices.getInitNd91Quota().then(function (value) {

			$scope.lstDncQuota= value.data && value.data.status?value.data.list:[]  ;
			if($scope.lstDncQuota.length>0)
			{
				$scope.currentQuota= $scope.lstDncQuota;
			}

		},function (reason) {

			$.jGrowl("Không thể tải cấu hình")
		})

	}
	$scope.initND91TimeRangeConfig = function () {
		ApiServices.getInitNd91TimeRange().then(function (value) {
			$scope.lstDncTimeRange = value.data && value.data.status ? value.data.list : [];
			if ($scope.lstDncTimeRange.length > 0) {
				$scope.currentTimeRange = $scope.lstDncTimeRange[0];
				var timeRange = angular.copy($scope.currentTimeRange.time_allow);
				$scope.currentTimeRange.range1 = {from: "", to: ""};
				$scope.currentTimeRange.range2 = {from: "", to: ""};
				if (timeRange.includes(",")) {
					var timeRanges = timeRange.split(",");
					if (timeRanges[0].includes("-")) {
						var range1 = timeRanges[0].split("-");
						$scope.currentTimeRange.range1.from = range1[0];
						$scope.currentTimeRange.range1.to = range1[1];
					}
					if (timeRanges[1].includes("-")) {
						var range2 = timeRanges[1].split("-");
						$scope.currentTimeRange.range2.from = range2[0];
						$scope.currentTimeRange.range2.to = range2[1];
					}
				}
				else {
					if (timeRange.includes("-")) {
						var range1 = timeRange.split("-");
						$scope.currentTimeRange.range1.from = range1[0];
						$scope.currentTimeRange.range1.to = range1[1];
					}
				}
				console.log($scope.currentTimeRange);
			}
		}, function (reason) {
			$.jGrowl("Không thể tải cấu hình")
		})
	}
	$scope.switchNavNd91 = function (nav) {
		for (var i = 0; i < $scope.lstNavNd91.length; i++) {
			$scope.lstNavNd91[i].active = false;
		}
		nav.active = true;
		$scope.nav91 = nav.id;
		switch (nav.id) {
			case "quota":
				$scope.initND91QuotaConfig();
				break;
			case "time_range":
				$scope.initND91TimeRangeConfig();
				break;
			case "report":
				$scope.reportParam = new ReportParam();
				$scope.onSearchReport($scope.reportParam);
				break;
			default:
				$scope.initNd91();
				break;
		}
	}

	$scope.editQuotaItem= function (item) {
		$scope.quotaItem= angular.copy(item);
		$("#editQuotaItem").modal("show");

	}

	$scope.editTimeItem= function (item) {
		$scope.timeRangeEditItem= angular.copy(item);
		$("#editTimeItem").modal("show");

	}
	$scope.onSaveQuotaItem = function (item) {
		var lstError = "";
		var isErrs = false;
		if (!item.max_call_per_day) {
			isErrs = true;
			lstError += "<li>Bạn chưa nhập số lượng ngày</li>";
		}
		if (!item.max_call_per_month) {
			isErrs = true;
			lstError += "<li>Bạn chưa nhập số lượng tháng</li>";
		}
		if (isErrs) {
			$.jGrowl("Có lỗi xảy ra :<ul>" + lstError + "</ul>");
			return;
		}
		ApiServices.setNd91QuotaItem(item).then(function (value) {
			$.jGrowl("Lưu thiết lập thành công")
			$scope.initND91QuotaConfig();
			$("#editQuotaItem").modal("hide");
		}, function (reason) {
			if (reason.status == 422) {
				var lstError = "";
				if (reason.data.errors.max_call_per_day) {
					lstError += "<li>Số lượng cuộc gọi ngày không hợp lệ hoặc sai định dạng </li>";
				}
				if (reason.data.errors.max_call_per_month) {
					lstError += "<li>Số lượng cuộc gọi tháng không hợp lệ hoặc sai định dạng </li>";
				}
				$.jGrowl("Có lỗi xảy ra :<ul>" + lstError + "</ul>");
				return;
			}
			$.jGrowl("Có lỗi xảy ra")
		})
	}
	$scope.onSaveNd91QuotaConfig = function (item) {
		ApiServices.setNd91Quota(item).then(function (value) {
			$.jGrowl("Lưu thiết lập thành công")
			$scope.initND91QuotaConfig();
			$("#editQuotaItem").modal("hide");
		}, function (reason) {
			$.jGrowl("Có lỗi xảy ra")
		})
	}
	$scope.onSaveNd91TimeConfig = function (item) {
		var postData = {id: item.id, active: item.active};
		ApiServices.setNd91Time(postData).then(function (value) {
			$.jGrowl("Lưu thiết lập thành công")
			$scope.initND91TimeRangeConfig();
			// $("#editQuotaItem").modal("hide");
		}, function (reason) {
			$.jGrowl("Có lỗi xảy ra")
		})
	}
	$scope.resetFormDnc = function () {
		$scope.dncConfig = null;
	}
	$scope.onSavTimeItem = function (item) {
		var TimeAllow = "";
		var isError = false;
		var lstError = "";
		if (!validTimeAllow(item.range1.from)) {
			isError = true;
			lstError += "<li>Khung giờ 1, bắt đầu sai định dạng. Hãy điền định dạng  HHMM (ví dụ 0900)";
		}
		if (!validTimeAllow(item.range1.to)) {
			isError = true;
			lstError += "<li>Khung giờ 1, kết thúc sai định dạng. Hãy điền định dạng  HHMM (ví dụ 1100)";
		}
		if (item.range2.from && !validTimeAllow(item.range2.from)) {
			isError = true;
			lstError += "<li>Khung giờ 2, bắt đầu sai định dạng. Hãy điền định dạng  HHMM (ví dụ 1300)";
		}
		if (item.range2.to && !validTimeAllow(item.range2.to)) {
			isError = true;
			lstError += "<li>Khung giờ 2, kết thúc sai định dạng. Hãy điền định dạng  HHMM (ví dụ 1800)";
		}
		if (isError) {
			$.jGrowl("<ul>" + lstError + "</ul>", {theme: "error"});
			return;
		}
		if (item.range1) {
			TimeAllow += item.range1.from + "-" + item.range1.to;
		}
		if (item.range2 && item.range2.from && item.range2.to) {
			TimeAllow += "," + item.range2.from + "-" + item.range2.to;
		}
		var postData = {
			time_edit: true,
			time_allow: TimeAllow,
			id: item.id,
			name: item.name,
			description: item.description
		};
		ApiServices.setNd91Time(postData).then(function (value) {
			$.jGrowl("Lưu thiết lập thành công")
			$scope.initND91TimeRangeConfig();
			$("#editTimeItem").modal("hide");
		}, function (reason) {
			$.jGrowl("Có lỗi xảy ra")
		})
	}

	$scope.nd91BrandNameTableCol = [
		{cssclass:'text-right','col': 'index', 'title': 'Số thứ tự'},
		{cssclass:'font-weight-bold','col': 'brand_name', 'title': 'Brand Name'},
		{cssclass:'text-right','col': 'time_range', 'title': 'Chặn ngoài khung giờ'},
		{cssclass:'text-right','col': 'dnc', 'title': 'Chặn DNC'},
		{cssclass:'text-right','col': 'c197', 'title': 'Chặn c197'},
		{cssclass:'text-right','col': 'quota', 'title': 'Chặn quota'},
		{cssclass:'text-right','col': 'cm', 'title': 'Chặn cm'},
		{cssclass:'text-right','col': 'qltttb', 'title': 'Chặn qltttb'},
		{cssclass:'text-right font-weight-bold','col': 'total', 'title': 'Tổng'},
		// {'col':'user_reject','title':'Chặn user_reject'},
	]
	$scope.nd91BrandNameTableParam= {start_date:"",end_date:"",hotline:""};

	$scope.onSearchReport = function (reportParam) {
		$scope.nd91Report = {};
		var postData = {};
		postData.start_date = moment(reportParam.start_date).format("YYYY-MM-DD 00:00:00");
		postData.end_date = moment(reportParam.end_date).format("YYYY-MM-DD HH:mm:ss");
		postData.hotline = reportParam.hotline;
		$scope.nd91BrandNameTableParam.start_date= postData.start_date;
		$scope.nd91BrandNameTableParam.end_date= postData.end_date;
		$scope.nd91BrandNameTableParam.hotline= postData.hotline;

		$("#loading").modal("show");


		switch ($scope.nd91ReportNav) {
			case "general":
				ApiServices.getNd91Report(postData).then(function (value) {
					$("#loading").modal("hide");
					var report = value.data ? value.data.report : {}
					$scope.nd91Report.total = parseInt(report.successTotal.total) + parseInt(report.failTotal.total);
					$scope.nd91Report.success = report.successTotal.total;
					$scope.nd91Report.dnc = parseInt(report.failTotal.dnc ? report.failTotal.dnc : 0);
					$scope.nd91Report.c197 = parseInt(report.failTotal.c197 ? report.failTotal.c197 : 0);
					$scope.nd91Report.time_range = parseInt(report.failTotal.time_range ? report.failTotal.time_range : 0);
					$scope.nd91Report.quota = parseInt(report.failTotal.quota ? report.failTotal.quota : 0);
					$scope.nd91Report.cm = parseInt(report.failTotal.cm ? report.failTotal.cm : 0);
					$scope.nd91Report.qltttb = parseInt(report.failTotal.qltttb ? report.failTotal.qltttb : 0);
					// $scope.nd91Report.user_reject = parseInt(report.failTotal.user_reject ? report.failTotal.user_reject : 0);
					$scope.nd91Report.nd91 = $scope.nd91Report.dnc
						+ $scope.nd91Report.c197
						+ $scope.nd91Report.time_range
						+ $scope.nd91Report.quota
						+ $scope.nd91Report.cm
						+ $scope.nd91Report.qltttb
					// + $scope.nd91Report.user_reject
					$scope.nd91Report.other = parseInt(report.failTotal.total) - parseInt($scope.nd91Report.nd91);
					$scope.nd91Report.report_start = moment(reportParam.start_date).format("YYYY-MM-DD");
					$scope.nd91Report.report_end = moment(reportParam.end_date).format("YYYY-MM-DD");
					$scope.nd91Report.range_of_day = value.data.range_of_day;
					$scope.nd91Report.chart = value.data.chart;
					renderChartNd91($scope.nd91Report, 'reportChartNd91');
				}, function (reason) {
					$("#loading").modal("hide");
					if (reason.status == 422) {
						console.log(reason.data.errors);
						var lstError = ""
						if (reason.data.errors.end_date) {
							lstError += "<li>Thời gian kết thúc quá 90 ngày so với ngày bắt đầu hoặc quá 12 tháng so với ngày hiện tại hoặc đang ở tương lai</li>";
						}
						if (reason.data.errors.start_date) {
							lstError += "<li>Thời gian bắt đầu hoặc quá 12 tháng so với ngày hiện tại hoặc đang ở tương lai</li>";
						}
						if (reason.data.errors.hotline) {
							lstError += "<li>Số holine không tồn tại</li>";
						}
						$.jGrowl("Có lỗi xảy ra: <ul>" + lstError + "</ul>");
					}
					if (reason.status == 403) {
						$.jGrowl("Bạn không có quyền truy cập báo cáo")
					}
				});
				break;
			case "brandname":
				$scope.onSearchBrandNameReport();
				break;
		}
	}

	$scope.onSearchBrandNameReport=()=>
	{



		if (!$scope.nd91BrandNameTable) {
			$scope.nd91BrandNameTable = new ngTableParams({
					page: 1, // show first page
					count:10   // count per page

				}, {
					counts: [10,20,50,100],
					getData: function ($defer, params) {
						$scope.nd91BrandNameTableParam.page = params.page();
						$scope.nd91BrandNameTableParam.count = params.count();
						$scope.nd91BrandNameTableParam.sorting = $scope.nd91BrandNameTable.orderBy().toString();
						$scope.nd91BrandNameTableParam.tableGroupBy = $scope.nd91BrandNameTable.tableGroupBy;
						$("#loading").modal("show");
						ApiServices.getNd91ReportBrandname($scope.nd91BrandNameTableParam).then(function (response) {
								$("#loading").modal("hide");
								$scope.lstBrandNameRerport = response.data.data;



								$scope.lstBrandNameRerportCount = response.data.count;
								// $scope.userAgent= response.data.data.user;

								if (response.data.count <= $scope.nd91BrandNameTable.parameters().count) {
									$scope.nd91BrandNameTable.parameters().page = 1;
								}
								params.total(response.data.count?response.data.count:0);
								$defer.resolve(response.data.data?response.data.data:[]);
							}, function (reason) {
								$("#loading").modal("hide");
								$scope.lstBrandNameRerport = [];
								$scope.lstBrandNameRerportCount = -1;
							}
						);
					}
				}
			)
		}
		else {
			$scope.nd91BrandNameTable.reload();
		}


	}

	// $scope.onSearchBrandNameReport=function () {
	// 	if (!$scope.nd91BrandNameTable) {
	// 		$scope.nd91BrandNameTable = new ngTableParams({
	// 				page: 1, // show first page
	// 				count:20   // count per page
	//
	// 			}, {
	// 				counts: [20,50,100,200,300],
	// 				getData: function ($defer, params) {
	// 					console.log("NTABLE",$scope.nd91BrandNameTable);
	//
	// 					$scope.nd91BrandNameTableParams.page = params.page();
	// 					$scope.nd91BrandNameTableParams.count = params.count();
	// 					$scope.nd91BrandNameTableParams.sorting = $scope.nd91BrandNameTable.orderBy().toString();
	// 					$scope.nd91BrandNameTableParams.tableGroupBy = $scope.nd91BrandNameTable.tableGroupBy;
	// 					ApiServices.getNd91ReportBrandname($scope.nd91BrandNameTableParams).then(function (response) {
	// 							$("#loading").modal("hide");
	// 							// console.log(response)
	// 							$scope.lstBrandNameReport = response.data.data;
	// 							$scope.nd91BrandNameTableParams.total = response.data.count;
	//
	// 							if (response.data.count <= $scope.nd91BrandNameTable.parameters().count) {
	// 								$scope.nd91BrandNameTable.parameters().page = 1;
	// 							}
	// 							params.total(response.data.count);
	// 							$defer.resolve(response.data.data);
	// 						}, function (response) {
	// 							$("#loading").modal("hide");
	// 							$scope.lstBrandNameReport = [];
	// 							$scope.nd91BrandNameTableParams = -1;
	// 							if (reason.status == 422) {
	// 								console.log(reason.data.errors);
	// 								var lstError = ""
	// 								if (reason.data.errors.end_date) {
	// 									lstError += "<li>Thời gian kết thúc quá 90 ngày so với ngày bắt đầu hoặc quá 12 tháng so với ngày hiện tại hoặc đang ở tương lai</li>";
	// 								}
	// 								if (reason.data.errors.start_date) {
	// 									lstError += "<li>Thời gian bắt đầu hoặc quá 12 tháng so với ngày hiện tại hoặc đang ở tương lai</li>";
	// 								}
	// 								if (reason.data.errors.hotline) {
	// 									lstError += "<li>Số holine không tồn tại</li>";
	// 								}
	// 								$.jGrowl("Có lỗi xảy ra: <ul>" + lstError + "</ul>");
	// 							}
	// 							if (reason.status == 403) {
	// 								$.jGrowl("Bạn không có quyền truy cập báo cáo")
	// 							}
	// 						}
	// 					);
	// 				}
	// 			}
	// 		)
	// 	}
	// 	else {
	// 		$scope.nd91BrandNameTable.reload();
	// 	}
	// }



	function  renderChartNd91(data, element) {

		var lstTotalNd91=[];
		var lstDnc=[];
		var lst197=[];
		var lstTimeRange=[];
		var lstQuota=[];
		var lstSuccess=[];
		var lstCm=[];
		var lstQltttb=[];
		var lstUserReject=[];

		var fail91=data.chart.fail;
		var success91=data.chart.success;

		for(var i=0; i< fail91.length; i++)
		{
			lstTotalNd91.push(parseInt(fail91[i].total?fail91[i].total:0));
			lst197.push(parseInt(fail91[i].c197?fail91[i].c197:0));
			lstDnc.push(parseInt(fail91[i].dnc?fail91[i].dnc:0));
			lstTimeRange.push(parseInt(fail91[i].time_range?fail91[i].time_range:0));
			lstQuota.push(parseInt(fail91[i].quota?fail91[i].quota:0));
			lstCm.push(parseInt(fail91[i].cm?fail91[i].cm:0));
			lstQltttb.push(parseInt(fail91[i].qltttb?fail91[i].qltttb:0));
			// lstUserReject.push(parseInt(fail91[i].user_reject?fail91[i].user_reject:0));

		}
		for(var i=0; i< success91.length; i++)
		{
			lstSuccess.push(parseInt(success91[i].total));

		}

		var dataSeries=[
			{name:"Nghe máy", data:lstSuccess},
			{name:"Chặn ND91", data:lstTotalNd91},
			{name:"Hạn mức", data:lstQuota},
			{name:"DNC", data:lstDnc},
			{name:"197", data:lst197},
			{name:"Thời gian", data:lstTimeRange},
			{name:"CM", data:lstCm},
			{name:"QLTTTT", data:lstQltttb},
			// {name:"Khách đăng ký DNC", data:lstUserReject},

		]



		console.log(lstTotalNd91);
		console.log(dataSeries	);



		Highcharts.chart(element, {

			title: {
				text: 'Lưu lượng cuộc gọi hàng ngày từ  hotline Brandname'
			},

			subtitle: {
				text: null
			},

			xAxis: {
				categories: data.range_of_day
			},
			yAxis: {
				title: {
					text: 'Số cuộc gọi'
				}
			},


			plotOptions: {
				line: {
					dataLabels: {
						enabled: true
					},
					enableMouseTracking: true
				}
			},
			series: dataSeries
		});
	}


	$scope.nd91ReportNav= "general";


	$scope.switchNd91Report=function (nav) {
		if(!nav)
		{
			$scope.nd91ReportNav= 'general';
		}
		else
		{
			console.log(nav)
			$scope.nd91ReportNav= nav;
			if(nav=='brandname')
			{
				$scope.onSearchBrandNameReport();
			}
		}

	}

});


var ReportParam=function () {
	var date = new Date();

	this.hotline="";
	this.start_date=  new Date(date. getFullYear(), date. getMonth(), 1);;
	this.end_date= new Date();


}

function validTimeAllow(text) {
	if (text.length != 4) {
		return false;
	}

	if (isNaN(text)) {

		return false;
	}

	if(text.substring(0,2)> 24 || text.substring(0,2)< 0)
	{
		return false;
	}
	if(text.substring(2,4)> 60 || text.substring(2,4)< 0)
	{
		return false;
	}

	return true;

}
