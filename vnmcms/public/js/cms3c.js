$('.nav-link').on('click',function() {
    $('.navbar-collapse').collapse('hide');
});


var GOOGLE_RECAPTCHA_SITEKEY = '6LcrUUwUAAAAAIinKmHeyzKrzmm1dbBlvr0tEPjb';
function Option() {
    this.id = "-1";
    this.description = "";
}


function Service() {
    this.id = '-1';tableLogs
    this.service_name = '';
}


function Customer() {
    this.service_id = -1;
    this.blocked = 0;
}


function ngAlias($compile) {
    return {
        restrict: "A",
        link: function (scope, element, attrs) {
            var args = attrs.ngAlias.split('as').map(function (elm) {
                return elm.replace(/ /g, '')
            });
            scope[args[0]] = '';
            var dot = args[1].split('.');
            var object = {};
            dot.forEach(function (value, index) {
                index === 0
                    ? object = scope[value]
                    : object = object[value] === null ? object[value] = {} : object[value];
            });
            scope[args[0]] = object;
        }
    };
}


const cms3c = angular.module("cms3c", ['ngRoute', 'pascalprecht.translate', 'ngTable', 'nya.bootstrap.select', 'ngTagsInput']);

cms3c.directive('focusOn', function() {
	return function(scope, elem, attr) {
		scope.$on('focusOn', function(e, name) {
			if(name === attr.focusOn) {
				elem[0].focus();
			}
		});
	};
});

cms3c.factory('focus', function ($rootScope, $timeout) {
	return function(name) {
		$timeout(function (){
			$rootScope.$broadcast('focusOn', name);
		});
	}
});


cms3c.config(['$translateProvider', function ($translateProvider) {
    $translateProvider.useStaticFilesLoader({
        prefix: '/lang/',
        suffix: '.json?v=20171023'
    });
    $translateProvider.preferredLanguage(sessionStorage.getItem('lang') || 'vi');
    $translateProvider.useSanitizeValueStrategy('escaped');
}]);
cms3c.factory('Excel', function ($window) {
    var uri = 'data:application/vnd.ms-excel;base64,',
        template = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>{worksheet}</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head><body><table>{table}</table></body></html>',
        base64 = function (s) {
            return $window.btoa(unescape(encodeURIComponent(s)));
        },
        format = function (s, c) {
            return s.replace(/{(\w+)}/g, function (m, p) {
                return c[p];
            })
        };
    return {
        tableToExcel: function (tableId, worksheetName) {
            var table = $(tableId),
                ctx = {worksheet: worksheetName, table: table.html()},
                href = uri + base64(format(template, ctx));
            return href;
        }
    };
})
cms3c.factory('TranslateService', function ($translate) {
    return {
        translate: function (lang) {
            return $translate.use(lang);
        }
    }
});



cms3c.factory('AuthInterceptor', function ($q, $location) {
	return {
		request: function (config) {
			config.headers = config.headers || {};
			if (config.headers.Authorization == undefined && BearerToken().get()) {
				config.headers.Authorization = 'Bearer ' + BearerToken().get();

			}
			return config || $q.when(config);
		},
		response: function (response) {
			if (response.status === 401) {


				location.href="/"
			}

			return response || $q.when(response);
		}
	};
});
cms3c.config(['$httpProvider', function ($httpProvider) {
	$httpProvider.defaults.useXDomain = true;
	$httpProvider.interceptors.push('AuthInterceptor');

}]);

var BearerToken = function () {
	return {
		get: function () {
			return localStorage.getItem(TOKEN_KEY) ? localStorage.getItem(TOKEN_KEY) : "";
		},
		set: function (token) {
			return localStorage.setItem(TOKEN_KEY, token);
		},
		remove:function () {
			return localStorage.removeItem(TOKEN_KEY);

		}
	}
}

var TOKEN_KEY="token";


cms3c.directive('ngAlias', ngAlias);
cms3c.directive('stringToNumber', function() {
    return {
        require: 'ngModel',
        link: function(scope, element, attrs, ngModel) {
            ngModel.$parsers.push(function(value) {
                return '' + value;
            });
            ngModel.$formatters.push(function(value) {
                return parseFloat(value, 10);
            });
        }
    };
});
cms3c.factory('dataShare', function () {
    var dataShare = {};
    return {
        data: dataShare
    }
});
cms3c.factory('loginCheck', function ($http, $location) {
    var token =BearerToken().get();
    return {
        getSessions: function () {
            var User = $http.post('/api/check', {token: token});
            User.then(function (data) {
                return data.data
            }, function (error) {
                $location.path("/login");
            })

            return token;
        },
		getRole: function () {
			return $http.post('/api/check?', {token: token});
		},
        getEntity: function () {
			return $http.post('/api/check?', {token: token}).then(function (value) {
			    return value.data;

            }, function (reason) {
				$location.path("/login");

            });



		},


    };
});



cms3c.directive('datetimepicker', ['$timeout', function ($timeout) {
	return {
		require: '?ngModel',
		restrict: 'EA',
		scope: {
			datetimepickerOptions: '@',
			onDateChangeFunction: '&',
			onDateClickFunction: '&'
		},
		link: function ($scope, $element, $attrs, controller) {
			$element.on('dp.change', function () {
				$timeout(function () {
					var dtp = $element.data('DateTimePicker');
					controller.$setViewValue(dtp.date());
					$scope.onDateChangeFunction();
				});
			});

			$element.on('click', function () {
				$scope.onDateClickFunction();
			});

			controller.$render = function () {
				if (!!controller && !!controller.$viewValue) {
					var result = controller.$viewValue;
					$element.data('DateTimePicker').date(result);
				}
			};

			$element.datetimepicker($scope.$eval($attrs.datetimepickerOptions));
		}
	};
}]);


cms3c.controller('dashboardController', function ($scope, ApiServices, loginCheck, $http, $location, dataShare) {

	var Token=loginCheck.getEntity().then(function (value) {

		$scope.entity=value.entity;

		if($scope.entity.VIEW_BILLING_CUSTOMER && value.acc)
		{
			dataShare.data.billing = value.acc;
			$location.path("/billing");
		}

		if($scope.entity.VIEW_DASHBOARD)
		{
			$scope.viewDashboard();
			$scope.flowDailyChart();
		}
		else
		{
			$("#loading").modal("hide");
		}


	});



	// INIT DATA
    Date.prototype.addHours= function(h){
        this.setHours(this.getHours()+h);
        return this;
    }
    $scope.nav= $location.search();


    var timer= new Date().addHours(1);
    document.cookie = "sbc="+ BearerToken().get()+"; expires="+timer+"; path=/";

	// User = $http.post('/api/user', {token: Token});
	// User.then(
	// 	function (user) {
	// 		var LoginUser = (user.data);
	// 		$scope.userRole = LoginUser.user.role;
	// 		if (LoginUser.user.role == 3) {
	// 			// Goto billing
	// 			dataShare.data.billing = LoginUser.enterprise;
	// 			$location.path("/billing");
	//
	// 		}
	//
	// 		if (LoginUser.user.role == 4) {
	// 			// Goto billing
	//
	// 			$location.path("/sip");
	//
	// 		}
	// 		if (LoginUser.user.role == 5 || LoginUser.user.role == 6) {
	// 			// Goto billing
	//
	// 			$location.path("/accounts");
	//
	// 		}

	//
    // }, function (error) {
    //   //  alert("Error Something");
	//
    // })

	$("#loading").modal("show");


    $scope.dashboard = {};
    $scope.viewDashboard = function () {
    	if($scope.entity.VIEW_DASHBOARD)
		{
			res = ApiServices.getDashboardInfo();
			$("#loading").modal("hide");
			res.then(function (data) {
				$scope.dashboard = data.data;
			}, function (error) {
			})
		}

    };

    // render data
    $scope.flowDailyChart = function () {
        // $scope.report.flow = data.data;
		if($scope.entity.VIEW_DASHBOARD)
		{
			 ApiServices.postViewDashboardDailyFlow().then(function (data) {
				$scope.dailyFlow=data.data;


			});
		}

    };





   setInterval(function () {
	   if($scope.entity.VIEW_DASHBOARD) {
		   $scope.flowDailyChart();
	   }


    },60000);



})
/** LOGIN CONTROLLER -------------------
 *
 */

cms3c.controller('billingController', function ($scope, ApiServices, $location, $filter, dataShare, Excel, $timeout, loginCheck, $window, ngTableParams ) {
	var Token=loginCheck.getEntity().then(function (value) {

		$scope.entity= value.entity;
		$scope.acc= value.acc

	});

    $scope.billing = {};
    $scope.billParam = {start_date: new Date(moment().format("YYYY-MM-01 00:00:00")), end_date: new Date(moment().format("YYYY-MM-DD 23:59:59"))};
    $scope.sbcBillParam = {};
    $scope.billParamFinal={};
    $scope.selectedHotline = {};
    $scope.itemTake = [25, 50, 100, 200, 250];

    var path= $location.path();




    function initServices() {

        var res= ApiServices.getServiceZoneQuantityType();
        res.then(function (value) {

            $scope.quantityTypes ={};
            for(var i=0; i< value.data.prefix.length; i++)
            {
                $scope.quantityTypes[value.data.prefix[i].id]= value.data.prefix[i].name;
            }

            console.log($scope.quantityTypes);

        }, function (reason) {
            $.jGrowl("Không tải được dịch vụ")
        })

    }


	if(path=="/billing")
	{
		initServices();

	}


    $scope.sbcBillCols=[

        {field:'event_occur_time',title:'Ngày phát sinh',sortable:'event_occur_time'},
        {field:'event_type',title:'Loại',sortable:'destination_type'},
        {field:'destination_type',title:'Hướng',sortable:'direction_type'},
        {field:'hotline_num',title:'Hotline',sortable:'hotline_num'},
        {field:'display_num',title:'Số hiển thị',sortable:'display_num'},
        {field:'called_num',title:'Số đích',sortable:'called_num'},
        {field:'enterprise_num',title:'Số tính cước',sortable:'enterprise_num'},
        {field:'charge_status',title:'Trạng thái',sortable:'charge_status'},
        // {field:'description',title:'Nội dung',sortable:'description'},
        {field:'count',title:'Số giây',sortable:'count'},
        {field:'amount',title:'Số tiền',sortable:'amount'},
        {field:'total_count',title:'Thời lượng lũy kế',sortable:'total_count'},
        {field:'total_amount',title:'Lũy kế trong tháng',sortable:'total_amount'},

    ];


    if (!dataShare.data.billing) {
        $location.path("/account"); // Xử lý chưa có dữ liệu khahcs hàng thì trả về danh sách khách hàng
    }



    $scope.exportToExcel2= function (data) {

        $window.open('/exportExcel/'+ $scope.billing.customer.enterprise_number+"?take=50000&token="+ Token);


    }
    $scope.billing.customer = dataShare.data.billing;



    $scope.range = function (min, max, step) {
        step = step || 1;
        var input = [];
        for (var i = min; i <= max; i += step) input.push(i);
        return input;
    };

    $scope.closeBillingView= function () {
        $location.path('/accounts');

    }
    $scope.viewLogBilling = function () {
     //

        var startDate=moment($scope.billParam.start_date).format("YYYY-MM-DD HH:mm:ss");
        var endDate= moment($scope.billParam.end_date).format("YYYY-MM-DD HH:mm:ss");
        if (!startDate) {
            $scope.billParamFinal.start_date = null;
        }
        else {
            $scope.billParamFinal.start_date = startDate;
            $scope.sbcBillParam.start_date = startDate;
        }
        if (!endDate) {
            $scope.billParamFinal.end_date = null;
        }
        else {
            $scope.billParamFinal.end_date = endDate;
            $scope.sbcBillParam.end_date = endDate;
        }

        $scope.billParam.hotline_number?$scope.billParamFinal.hotline_number=angular.copy($scope.billParam.hotline_number):$scope.billParamFinal.hotline_number=null;

        $scope.getBillLog();
        var enterprise= $scope.billing.customer.enterprise_number?$scope.billing.customer.enterprise_number:$scope.acc.enterprise_number;


        res = ApiServices.getBillingByEntNumber($scope.billParamFinal, enterprise)
        res.then(function (data) {
            $scope.billing.data = data.data;
            /** Render Highchart
             *
             */
            if ($scope.billParam.hotline_number) {
                var textHotline = "<p style='font-size: 90%'> Số HOTLINE: " + $scope.billParam.hotline_number + '</p><br>';
            }
            else {
                var textHotline = "";
            }
            var billChart = data.data.chart;
            var resDate = data.data.date;
            $scope.billingParamData= resDate;




                if (billChart.length > 0) {
                var datachart = [];
                for (var i = 0; i < billChart.length - 1; i++) {
                    datachart.push({
                        name: $filter('translate')('REPORT.QUANTITY.' + billChart[i].name),
                        y: billChart[i].per,
                        val: billChart[i].sum
                    });
                }
                Highcharts.chart('chart_billing', {
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
                        text: textHotline + $filter('translate')('LBL.FROM_DATE') + ": " + resDate.start_date + " " + $filter('translate')('LBL.TO_DATE') + ": " + resDate.end_date
                    },
                    tooltip: {
                        pointFormat: '{series.name}: <b> {point.val} Đ</b><br> '
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
            /** End render
             *
             */
        },function (e){
            if(e.status==422) {
                $scope.billing.error = e.data.errors;
                $scope.billing.data.logs= null;
            }
            }
            );
        res = ApiServices.getConfigByCustomer($scope.billing.customer.enterprise_number?$scope.billing.customer.enterprise_number:$scope.acc.enterprise_number);
        res.then(function (data) {
            $scope.billing.config = data.data;
            $scope.billing.hotlines=[];
            if($scope.billing.config.hotlines.length>0)
            {
                for(var i=0; i < $scope.billing.config.hotlines.length; i++)
                {
                    if($scope.billing.config.hotlines[i].status==0 || $scope.billing.config.hotlines[i].status==1)
                    {
                        $scope.billing.hotlines.push($scope.billing.config.hotlines[i]);
                    }

                }
            }
        })
    };


	$scope.sbcHotlineTableParam={};
	$scope.getListHotline=function()
	{
if (!$scope.sbcHotlineTable) {
			$scope.sbcHotlineTable = new ngTableParams({
					page: 1, // show first page
					count:10   // count per page

				}, {
					counts: [10,20,50],
					getData: function ($defer, params) {
						$scope.sbcHotlineTableParam.page = params.page();
						$scope.sbcHotlineTableParam.count = params.count();
						$scope.sbcHotlineTableParam.enterprise_number = $scope.billing.customer.enterprise_number;
						$scope.sbcHotlineTableParam.tableGroupBy = $scope.sbcHotlineTable.tableGroupBy;
						var postData=  angular.copy($scope.sbcHotlineTableParam);

						ApiServices.getListHotLinesByCustomers(postData).then(function (response) {


								var dataRes= response.data.data;
								$scope.listHotlines=[];

								for (var i = 0; i < dataRes.length ; i++) {
									dataRes[i].created_at= moment(dataRes[i].created_at).format("DD/MM/YYYY HH:mm:ss")
									$scope.listHotlines.push(dataRes[i]);
								}



								$scope.listHotlinesCount = response.data.count;


								if (response.data.count <= $scope.sbcHotlineTable.parameters().count) {
									$scope.sbcHotlineTable.parameters().page = 1;
								}
								params.total($scope.listHotlinesCount);
								$defer.resolve($scope.listHotlines);
							}, function (response) {
								$scope.listHotlines = [];
								$scope.listHotlinesCount = 0;
								$.jGrowl("Điều kiện tìm kiếm không hợp lệ hoặc có lỗi xảy ra")


							}
						);
					}
				}
			)
		}
		else {
			$scope.sbcHotlineTable.reload();
		}

	}
	$scope.getListHotline();


    // Rewwrite bill log
    $scope.getBillLog= function () {

        if($scope.billing)
        {
            $scope.sbcBillParam.enterprise_number=$scope.billing.customer.enterprise_number;
        }


        if (!$scope.sbcBillTable) {
            $scope.sbcBillTable = new ngTableParams({
                    page: 1, // show first page
                    count:100   // count per page

                }, {
                    counts: [100,200,300,400],
                    getData: function ($defer, params) {
                        $scope.sbcBillParam.page = params.page();
                        $scope.sbcBillParam.count = params.count();
                        $scope.sbcBillParam.sorting = $scope.sbcBillTable.orderBy().toString();
                        $scope.sbcBillParam.tableGroupBy = $scope.sbcBillTable.tableGroupBy;

                        var postData=  angular.copy($scope.sbcBillParam);
                        postData.start_date= moment($scope.billParam.start_date).format("YYYY-MM-DD 00:mm:ss");
                        postData.end_date= moment($scope.billParam.end_date).format("YYYY-MM-DD HH:mm:ss");
						$("#loading").modal("show");

                        ApiServices.getBillLogV2(postData).then(function (response) {

							$("#loading").modal("hide");
                                $scope.lstBillLog = response.data.data.data;
                                $scope.lstBillLogCount = response.data.data.count;
                                $scope.billDateRange = response.data.data.date;
                                $scope.limitItemPerDownload = response.data.data.limit_download_row;
                                $scope.lstLinks= renderLink( $scope.lstBillLogCount,  $scope.limitItemPerDownload, 0);

                                if (response.data.data.count <= $scope.sbcBillTable.parameters().count) {
                                    $scope.sbcBillTable.parameters().page = 1;
                                }
                                params.total($scope.lstBillLogCount);
                                $defer.resolve($scope.lstBillLog);
                            }, function (response) {
							$("#loading").modal("hide");
                                $scope.lstBillLog = [];
                                $scope.lstBillLogCount = -1;

                                if(response.status==422)
								{
									$.jGrowl("Lưu ý có thể thời gian bắt đầu và kết thúc dài quá 30 ngày hoặc thời gian bắt đầu quá 3 tháng từ hiện tại sẽ không hiển thị lịch sử. ")
								}
                            }
                        );
                    }
                }
            )
        }
        else {
            $scope.sbcBillTable.reload();
        }


    }
    if($scope.billing && $scope.billing.customer)
    {
        $scope.getBillLog();
    }

    $scope.downBilling= function (link) {

        // console.log(link);



        // console.log($scope.lstLinks);

        for(var i=0; i< $scope.lstLinks.length; i++)
        {
            if($scope.lstLinks[i].page > link.page-2 && $scope.lstLinks[i].page < link.page+3)
            {
				$scope.lstLinks[i].display=true;
            }
            else
            {
				$scope.lstLinks[i].display= false;
            }

			$scope.lstLinks[i].active= false;

        }
		link.active= true;




		$scope.sbcBillParam.download=1;

		var dataPost= angular.copy($scope.sbcBillParam);

				dataPost.start_date= moment($scope.billParam.start_date).format("YYYY-MM-DD HH:mm:ss");
				dataPost.end_date= moment($scope.billParam.end_date).format("YYYY-MM-DD 23:59:59");



		dataPost.count=  $scope.limitItemPerDownload
		    dataPost.page=  link.page;
		    dataPost.totalPage=  link.totalPage;

        $window.open('/exportBilling?'+ $.param(dataPost));

    }

	function renderLink(total, limit, current) {

		var totalPage = Math.ceil(total / limit);

		var lstItem = [];
		for (var i = 1; i <= totalPage; i++) {
			var toItem = total > (i * limit) ? (i * limit) : total;

			var data = {page: i, from: i * limit- limit+1, to: toItem, display: false, totalPage:totalPage}
			if (current == 0) {

				if (i <= 5) {
					data.display = true
				}
			}

			lstItem.push(data)
		}

		return lstItem;
	}



    $scope.jumpLastList= function()
    {
		for(var i =0; i< $scope.lstLinks.length; i++)
		{
			if(i > $scope.lstLinks.length-5 )
			{
				$scope.lstLinks[i].display= true;
			}
			else
			{
				$scope.lstLinks[i].display= false;
			}
		}

    }
    $scope.jumpFistList= function()
    {
        for(var i =0; i< $scope.lstLinks.length; i++)
        {
            if(i < 5)
            {
				$scope.lstLinks[i].display= true;
            }
            else
            {
				$scope.lstLinks[i].display= false;
            }
        }

    }

	$scope.jumpNextList = function () {
		var position;

		for (var z = 0; z < $scope.lstLinks.length; z++) {
			if ($scope.lstLinks[z].display) {

				position = z + 5;

				break;
			}
		}

		if(position > $scope.lstLinks.length-1)

		{
			$.jGrowl("Bạn đang ở cuối danh sách");
			return;
		}


		for (var z = 0; z < $scope.lstLinks.length; z++) {
			if (z >= position - 5 && z < position) {

				$scope.lstLinks[z].display = false;

			}
			else if (z >= position && z < position + 5) {
				$scope.lstLinks[z].display = true;
			}
		}

	}

	$scope.jumpPreviousList = function () {
		var position=0 ;

		for (var z = 0; z < $scope.lstLinks.length; z++) {
			if ($scope.lstLinks[z].display) {

				position = z ;

				break;
			}
		}
		console.log(position);
		if(position==0)
		{
			$.jGrowl("Bạn đang ở đầu danh sách");
			return;
		}

		for (var z = 0; z < $scope.lstLinks.length; z++) {
			if (z >= position - 5 && z < position) {

				$scope.lstLinks[z].display = true;

			}
			else if (z >= position && z < position + 5) {
				$scope.lstLinks[z].display = false;
			}
		}

	}


    $scope.viewBillLogByHotline = function (data) {
        $scope.billParam.hotline_number = data;
        $scope.sbcBillParam.hotline_number = data;
        $scope.viewLogBilling();
    }
    $scope.dialogDetailBilling = function (data) {
        $scope.currentLogBilling = data;
        $("#logBillingDetail").modal('show');
    }
    // AUTO RENDER LOG
    if ($scope.billing.customer) {

        $scope.viewLogBilling();
    }
})
/** LOGIN CONTROLLER -------------------
 *
 */
cms3c.controller('loginController', function ($scope, ApiUsers, $location, $filter, $http, loginCheck, dataShare, Excel, $timeout) {

    $scope.login = {};



	$("#loading").hide();

    $scope.nav = $location.path();
    $scope.urlParam = $location.search();
    $scope.changePass = {};
    $scope.captchaKey = GOOGLE_RECAPTCHA_SITEKEY;
	var Token=loginCheck.getEntity().then(function (value) {
		$scope.authUser= value;
		$scope.entity=value&& value.entity?value.entity:{};


		$("#loading").modal("hide");

	}, function (reason) {
		$("#loading").modal("hide");
	});




    $scope.switchNav = function (nav) {
        $scope.nav = '/' + nav ;
        $location.path(nav);
    }
    $scope.openConfig = function () {
        $("#configLoginUserModal").modal('show');
    }
    $scope.LogOutService = function () {
        localStorage.removeItem('token');
        res = ApiUsers.logOut();
        res.then(function (data) {
            var timer= new Date();
            document.cookie = "sbc="+ Token+"; expires="+timer+"; path=/";
            location.href = '/';
        }, function (data) {
            var timer= new Date();
            document.cookie = "sbc="+ Token+"; expires="+timer+"; path=/";
            $scope.login = null;
            location.href = '/';
        })
    }
    /**
     * Change user password
     * @param data
     * 2018 10 06  posted
     */
    $scope.doChangePassword = function (data) {

        res= ApiUsers.changePassword(data);
        res.then(function (result) {

    alert("Change pass success");
    window.location.reload();
        }, function (error) {
           $scope.changePassError=error.data.errors;
        })


    }
    /**
     * End chane user password
     */
    $scope.notShowCapcha = function () {
        googleKeyFromLogin = $('#g-Recapcha').children().length;
        if (!googleKeyFromLogin || googleKeyFromLogin == false) {
            grecaptcha.render($('#g-Recapcha')[0], {
                sitekey: GOOGLE_RECAPTCHA_SITEKEY,
                callback: onCheckedCaptcha,
                'expired-callback': recaptchaExpired
            });
        } else {
            grecaptcha.reset();
        }
    }
    $scope.notShowCapcha = function () {
        $("#sbccaptcha").attr("src","/images/captcha?"+ new Date().getTime());
    }


    $scope.loginMe = function () {
        if (!$scope.login.email) {
            $scope.login.message = $filter('translate')("LOGIN.email_empty");
            return false;
        }
        else {
            $scope.login.message = null;
        }
        if (!$scope.login.password) {
            $scope.login.message = $filter('translate')("LOGIN.password_empty");
            return false;
        }
        else {
            $scope.login.message = null;
        }
		$("#loading").modal("show");
        res = ApiUsers.loginWebSS($scope.login);
        res.then(function (data) {
			$("#loading").modal("hide");
            if (data.status == 200) {
                $scope.login = data.data
                if (data.data.response == 'error') {
                    $scope.loginErrorCaptcha=false;
                    $scope.notShowCapcha();
                    $scope.login.message = $filter('translate')('LOGIN.' + data.data.message);
                }
                else if (data.data.response == 'success') {
                    $scope.login.message = $filter('translate')('LOGIN.COMPLETE');
                    localStorage.setItem('token', data.data.result.token)

				//TODO	entity.VIEW_DASHBOARD

                    location.href = '/';
                }
            }
        }, function (error) {
			$("#loading").modal("hide");
            if(error.status==406)
            {
                $scope.notShowCapcha();
                $scope.loginErrorCaptcha=true;
                $location.path("/login");
            }
            else
            {
                alert("Error:   "+ error.status);
                $location.path("/login");
            }

        })
    }
});
/*** =============================================
 *  Service controller
 */


var ServiceConfig = function (data) {
	this.service_name ="";
	this.id= null;
	this.ocs_charge = 0;
	this.type = 0;
	this.status =0;
	this.product_code = "";
	this.is_prepaid=0;
}



cms3c.controller('servicesControllerAdd', function ($scope, ApiServices, $location, dataShare, loginCheck,$filter) {


	var Token=loginCheck.getEntity().then(function (value) {
		$scope.authUser= value;
		$scope.entity=value&& value.entity?value.entity:{};


	});

	$scope.lstOCS=[
		{id:0, title:$filter('translate')('Không ')},
		{id:1, title:$filter('translate')('Tính cước qua OCS')}
	]

	$scope.lstPaymentOptions=[
		{id:0, title:$filter('translate')('Trả sau ')},
		{id:1, title:$filter('translate')('Trả trước')}
	]


	$scope.lstStatus=[
		{id:0, title:$filter('translate')('Hoạt đông ')},
		{id:1, title:$filter('translate')('Tạm ngưng')},
		{id:2, title:$filter('translate')('Hủy')},
	]

	$scope.lstType=[
		{id:1, title:$filter('translate')('SERVICE.TYPE.1')},
		{id:0, title:$filter('translate')('SERVICE.TYPE.0')},

	]


	$scope.addService = new ServiceConfig();

	let sharedInfo= angular.copy(dataShare.data);


	if ('service_name' in sharedInfo) {
		$scope.addService.service_name = sharedInfo.service_name;
	}
	if ('id' in sharedInfo) {
		$scope.addService.id = sharedInfo.id;
	}
	if ('product_code' in sharedInfo) {
		$scope.addService.product_code = sharedInfo.product_code;
	}
	if ('type' in sharedInfo) {
		$scope.addService.type = sharedInfo.type;
	}
	if ('ocs_charge' in sharedInfo) {
		$scope.addService.ocs_charge = sharedInfo.ocs_charge;
	}
	if ('status' in sharedInfo) {
		$scope.addService.status = sharedInfo.status;
	}
	if ('is_prepaid' in sharedInfo) {
		$scope.addService.is_prepaid = sharedInfo.is_prepaid;
	}


    $scope.onAddService = function () {
        var res = ApiServices.postServiceConfig($scope.addService);
        res.then(function (data) {
        	$.jGrowl("Cập nhật thành công")
            $location.path("/services");

        })
    }

	$scope.closeService= function()
	{
		$location.path("/services");
	}
});
/** SIP CONTROLLER ----------------------
 *
 *
 *
 */
cms3c.controller('sipController', function ($scope, dataShare, ApiServices, $location, Excel, $timeout, loginCheck, $filter, $http,ngTableParams, $window) {
    var Token = loginCheck.getSessions();
$scope.sbcSipParam= {};
	$scope.optionsDate = '{format:"YYYY/MM/DD", useCurrent: false,debug:true}';

  var  User = $http.post('/api/user', {token: Token});
    User.then(function (user) {
        var LoginUser= (user.data);
        $scope.userRole=LoginUser.user.role;


        console.log($scope.userRole)

    }, function (error) {
        // alert("Error Something");

    })


    $scope.message = 'sip';
    $scope.sipLogParam = {};
    $scope.sipLogParam.direction = "in";
    $scope.sipLogParam.paramEnable = true;

    $scope.exportToExcel = function (tableId) { // ex: '#my-table'
        var exportHref = Excel.tableToExcel(tableId, 'sheet name');
        $timeout(function () {
            location.href = exportHref;
        }, 100); // trigger download
    }

    $scope.exportExcelSip= function () {

        var excelParam={'hotline':$scope.currentSip.caller,'param':$scope.sipLogParam};
        $window.open('/exportSipCallLog?'+ $.param(excelParam));
        console.log(excelParam);

    }




	$scope.sipCols = [


		{field: "action", title: "Action", sortable: "created_at", show: true},
		{field: "caller", title: "Hotline", sortable: "caller", show: true},
		{field: "brand_name", title: "Brand name", sortable: "brand_name", show: true},
		{field: "companyname", title: "Customer", sortable: "companyname", show: true},
		{field: "phone1", title: "Phone no", sortable: "phone1", show: true},
		{field: "ip_proxy", title: "Ip Proxy", sortable: "ip_proxy", show: true},
		{field: "ip_auth", title: "Ip Auth", sortable: "ip_auth", show: true},
		{field: "destination", title: "Destination", sortable: "destination", show: true},

		// {field: "updated_at", title: "Cập nhật", sortable: "updated_at", show: true}


	];


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
		{field: "nbr", title: "Tác vụ", sortable: "nbr", show: true}


	];



    $scope.onSearchSip= function(){


        $scope.currentSip= null;
		if (!$scope.sbcSip) {
			$scope.sbcSip = new ngTableParams({
					page: 1, // show first page
					count:10   // count per page

				}, {
					counts: [10,20,50,100],
					getData: function ($defer, params) {
						$scope.sbcSipParam.page = params.page();
						$scope.sbcSipParam.count = params.count();
						$scope.sbcSipParam.sorting = $scope.sbcSip.orderBy().toString();
						$scope.sbcSipParam.tableGroupBy = $scope.sbcSip.tableGroupBy;
						$("#loading").modal("show");
						ApiServices.postSearchSip($scope.sbcSipParam).then(function (response) {
							$("#loading").modal("hide");
								$scope.lstCustomer = response.data.sip;
								$scope.lstCustomerCount = response.data.count;
								// $scope.userAgent= response.data.data.user;

								if (response.data.count <= $scope.sbcSip.parameters().count) {
									$scope.sbcSip.parameters().page = 1;
								}
								params.total($scope.lstCustomerCount);
								$defer.resolve($scope.lstCustomer);
							}, function (response) {
							$("#loading").modal("hide");
								$scope.lstCustomer = [];
								$scope.lstCustomerCount = -1;
							}
						);
					}
				}
			)
		}
		else {
			$scope.sbcSip.reload();
		}
    }



    $scope.onSearchSip();
    $scope.filterView = function (data) {
		$scope.sipLogParam.direction=data?data:"in";
		$scope.onSearchSipLog($scope.currentSip);
    }

    $scope.closeSip = function () {
        $scope.currentSip = null;

    }






    $scope.viewSip = function (data) {
        $scope.currentSip = data;







		$scope.onSearchSipLog($scope.currentSip);



    }


    $scope.onSearchSipLog= function(param)
    {
    	$("#loading").modal("show");
		$scope.sipLogParam.caller = param.caller;
		// $scope.sipLogParam.start_date = $filter('date')($scope.sipLogParam.start_date, 'yyyy/MM/dd 00:00:00', 'GMT+07');
		// $scope.sipLogParam.end_date = $filter('date')($scope.sipLogParam.end_date, 'yyyy/MM/dd 23:59:59', 'GMT+07');

		$scope.sipLogParam.start_date = moment($scope.sipLogParam.start_date).format("YYYY/MM/DD 00:00:00");
		$scope.sipLogParam.end_date = moment($scope.sipLogParam.end_date).format("YYYY/MM/DD 23:59:59");


		if (!$scope.sbcSipLog) {
			$scope.sbcSipLog = new ngTableParams({
					page: 1, // show first page
					count:10   // count per page

				}, {
					counts: [10,20,50,100],
					getData: function ($defer, params) {
						$scope.sipLogParam.page = params.page();
						$scope.sipLogParam.count = params.count();
						$scope.sipLogParam.sorting = $scope.sbcSipLog.orderBy().toString();
						$scope.sipLogParam.tableGroupBy = $scope.sbcSipLog.tableGroupBy;
						$("#loading").modal("show");
						ApiServices.postCallLog($scope.sipLogParam).then(function (response) {
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
        $("#callDialogDebug").modal('show');
    }
    // $scope.viewCallDebugPython('0983048222');

    $scope.setupBlockRule = function (data) {
        $scope.sipSetup = {'caller': data};
        $scope.sipSetup.nav = 'info'
        res = ApiServices.getSipConfigByCaller(data)
        res.then(function (data) {
            $scope.sipSetup.sip = data.data;
        }, function (dataerror) {
            $scope.sipSetup = false;
        });
        $("#modal_setup_sip").modal('show');
    }
    $scope.deleteSip = function (data) {
    	var cfm = confirm("Bạn muốn xóa số này");
    	if(cfm)
		{
			var datatodelete = {'number': data};
			res = ApiServices.putDeleteSipByCaller(datatodelete);
			res.then(function (data) {
				$("#modal_setup_sip").modal('hide');
				$scope.onSearchSip();
				$.jGrowl("Xóa số thành công")
			}, function (error) {
				$.jGrowl("Có lỗi xảy ra")
			})
		}

    }
    $scope.selectSipSetupNav = function (data) {
        $scope.sipSetup.nav = data;
    }



    $scope.onCheckNumberRouting= function (call) {

        call.checkedInfo={};
        call.checkedInfo.loading=true;
        call.direction= angular.copy($scope.sipLogParam.direction);


        ApiServices.postCheckNumberRouting(call).then(function (value) {
            call.checkedInfo.loading=false;
			call.checkedInfo.state=true;
			var infoNbr= value.data;
			if(infoNbr.data)
            {
				call.checkedInfo.icon="fa-venus-double text-primary";
				call.checkedInfo.placeHolder=infoNbr.data.STATUS?"Đang sử dụng dịch vụ chuyển mạng của nhà mạng "+infoNbr.data.DESCRIPTION:"Không chuyển mạng giữ số" ;
            }
            else
            {
				call.checkedInfo.placeHolder="Không tìm thấy thông tin chuyển mạng";
				call.checkedInfo.icon="fa-check text-warning";
            }

        }, function (reason) {
            call.checkedInfo.loading=false;
			call.checkedInfo.state=false;
        })







	}
});
cms3c.controller('sipControllerAdd', function ($scope, dataShare, ApiServices, $location, loginCheck) {
    var Token = loginCheck.getSessions();
    $scope.message = "Trang nay se duoc su dung de hien thi mot form de them sinh vien";
    $scope.sip = {};
    $scope.sip.customer_id = dataShare.data.customerID;
    if (!$scope.sip.customer_id) {
        $location.path("/accounts");
        return false;
    }
    res = ApiServices.getAccountsById($scope.sip.customer_id);
    res.then(function (data) {
        $scope.sipCustomer = data.data;
        if ($scope.sipCustomer.sip.acl) {
            $scope.sip.acl = angular.copy($scope.sipCustomer.sip.acl);
        }
        if ($scope.sipCustomer.sip.routing.length > 0) {
            $scope.sip.routing = angular.copy($scope.sipCustomer.sip.routing[0]);
        }
    });
});

cms3c.controller('settingController', function ($scope, ApiServices, loginCheck, $http, $location, dataShare) {

$scope.server= new Server();
$scope.lstServerType=[
    {id:1,name:"Server đồng bộ"},
    {id:2,name:"Server độc lập"}
]

$scope.getListServer= function()
{
    var res= ApiServices.getListServer();
    res.then(function (value) {
        if(value.data.status)
        {
			$scope.listServer= value.data.data;
        }
        else
        {
            alert("Init list server error");
        }

    })
}

$scope.onCheckResource= function(data)
{
    $("#serveResource").modal("show");
    $scope.serverCPU={};
    $scope.serverCPU.loading=true
    ;
	console.log($scope.serverCPU);

	ApiServices.getServerResource().then(function (value) {
		$("#cpuData").html("<pre>"+value.data+"</pre>");
		// console.log(value.data);
	})
    

}

	$scope.onSubmitServer = function (data) {

		if (data.ip && data.server_name && data.port && data.api_url) {

			var res = ApiServices.saveServer(data)
			res.then(function (value) {
				alert("Cập nhật thành công")
				if (value.data.status) {
					data.id = value.data.data.id
					data.edit = true;
				}
				$scope.getListServer()
			}, function (reason) {
				alert("Cập nhật thất bại")
			})
		}
		else {
			alert("Thông tin chưa đầy đủ, vui lòng nhập tất cả các trường")
			return;
		}

	}

	$scope.getListServer();

$scope.editServer=function (server) {
    $scope.server= angular.copy(server);
    $scope.server.edit=true;

}

$scope.newServer= function () {
    $scope.server= new Server();

}

$scope.onActiveServer= function (data) {


	var res=ApiServices.saveActiveServer(data)
	res.then(function (value) {
		alert("Cập nhật thành công")
		if(value.data.status)
		{
			$scope.getListServer()
		}

	}, function (reason) {
		alert("Cập nhật thất bại")
	})
}
});

var Server= function()
{
    this.id= null;
    this.server_name="";
    this.port="";
    this.api_url="";
    this.ip= "";
    this.edit= false;
    this.server_type=1;
}



/** CUSTOMER CONTROLLER ------------
 *
 *
 *
 */


