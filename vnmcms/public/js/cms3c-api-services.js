cms3c.factory('ApiServices', function($http,loginCheck){
    var Token = loginCheck.getSessions();
    //var ApiUrl="http://210.211.99.120";
    var ApiUrl="";
    var headers = {
        'Authorization': "Bearer " + Token,
        'Source':'1'
    };

    return {


        getAccounts:function (data) {
            return $http({method:'get', headers:headers, url:ApiUrl+'/api/accounts/getListCustomers?'+ $.param(data)});
        },
        callDebugPython:function (data, data2) {
            return $http({method:'post', headers:headers, url:ApiUrl+'/api/sip/callDebugPython/'+data, data:data2});
        },
        postCheckNumberRouting:function (data) {
            return $http({method:'post', headers:headers, url:ApiUrl+'/api/sip/postCheckNumberRouting', data:data});
        },
        getAccountsById:function (data) {
            return $http({method:'get',headers:headers, url:ApiUrl+'/api/accounts/getAccountsById/'+data});
        },

        getDashboardInfo:function () {
                return $http({method:'get',headers:headers, url:ApiUrl+'/api/accounts/getDashboardInfo/'});
            },

        postViewDashboardDailyFlow:function (data) {
                return $http({method:'post',headers:headers, url:ApiUrl+'/api/accounts/postViewDashboardDailyFlow',data:data});
            },

        getFeeByEntNumber:function (data) {
            return $http({method:'get',headers:headers, url:ApiUrl+'/api/accounts/getFeeByEntNumber/'+data});
        },

        getServiceConfig:function (data) {
            return $http({method:'get',headers:headers, url:ApiUrl+'/api/accounts/getServiceConfig',params:data});
        },

        getServiceZoneQuantityType:function (data) {
            return $http({method:'get',headers:headers, url:ApiUrl+'/api/services/getServiceZoneQuantityType?'});
        },
        postServicePrefixType:function (data) {
            return $http({method:'post',headers:headers, url:ApiUrl+'/api/services/postServicePrefixType', data:data});
        },
        deleteServicePrefixType:function (data) {
            return $http({method:'post',headers:headers, url:ApiUrl+'/api/services/deleteServicePrefixType', data:data});
        },

        getServiceConfigById:function (data) {
            return $http({method:'get',headers:headers, url:ApiUrl+'/api/accounts/getServiceConfigById/'+ data});
        },

        getConfigByCustomer:function (data) {

            return $http({method:'get',headers:headers, url:ApiUrl+'/api/accounts/getConfigByCustomer/'+ data});
        },

		getListHotLinesByCustomers:function (data) {
			return $http({method:'get',headers:headers, url:ApiUrl+'/api/accounts/getListHotLinesByCustomers',params:data})
		},

        postSearchSip:function (data) {

            return $http({method:'post',headers:headers, url:ApiUrl+'/api/sip/postSearchSip', data:data});

        },

        getActivity:function (data) {
            return $http({method:'post',headers:headers, url:ApiUrl+'/api/activity?'+ $.param(data)});
        },

        //
        getBillingByEntNumber:function(data,id)
        {
            return $http({method:'post',headers:headers, url:ApiUrl+'/api/billing/getBillingByEntNumber/'+id, data:data});
        },
    //
        getBillLog:function(data,id)
        {
            return $http({method:'post',headers:headers, url:ApiUrl+'/api/billing/getBillLog/'+id, data:data});
        },

        getBillLogV2:function(data)
        {
            return $http({method:'post',headers:headers, url:ApiUrl+'/api/billing/getBillLogV2', data:data});
        },



        postCallLog:function (data) {
            return $http({method:'post',headers:headers, url:ApiUrl+'/api/sip/postCallLog/'+data.caller, data:data});
        },

        getSipConfigByCaller:function (id) {
            return $http({method:'get',headers:headers, url:ApiUrl+'/api/sip/getSipConfigByCaller/'+id});
        },
        putDeleteSipByCaller:function (id) {
            return $http({method:'post',headers:headers, url:ApiUrl+'/api/sip/putDeleteSipByCaller',data:id});
        },

        postServiceSubscriber:function (data) {
            return $http({method:'post',headers:headers, url:ApiUrl+'/api/accounts/postServiceSubscriber', data:data});
        } ,
        updateServiceSubscriber:function (id, data) {
            return $http({method:'post',headers:headers, url:ApiUrl+'/api/accounts/postServiceSubscriber/'+id, data:data});
        } ,
        postAccountBlock:function (data) {
            return $http({method:'post',headers:headers, url:ApiUrl+'/api/accounts/postAccountBlock', data:data});
        } ,

        postServiceAdded:function (data) {
            return $http({method:'post',headers:headers, url:ApiUrl+'/api/accounts/serviceAddedCode', data:data});
        } ,
        postServiceConfig:function (data) {
            return $http({method:'post',headers:headers, url:ApiUrl+'/api/accounts/postServiceConfig', data:data});
        },



        postServiceConfigPrice:function (data) {
            return $http({method:'post',headers:headers, url:ApiUrl+'/api/accounts/postServiceConfigPrice', data:data});
        },
        postServiceConfigHotlinePrice:function (data) {
            return $http({method:'post',headers:headers, url:ApiUrl+'/api/accounts/postServiceConfigHotlinePrice', data:data});
        },
        postServiceOptionPrice:function (data) {
            return $http({method:'post',headers:headers, url:ApiUrl+'/api/accounts/postServiceOptionPrice', data:data});
        },
        postServiceCallPrice:function (data) {
            return $http({method:'post',headers:headers, url:ApiUrl+'/api/accounts/postServiceCallPrice', data:data});
        },
        postServiceSmsPrice:function (data) {
            return $http({method:'post',headers:headers, url:ApiUrl+'/api/accounts/postServiceSmsPrice', data:data});
        },
        postServiceQuantityPrice:function (data) {
            return $http({method:'post',headers:headers, url:ApiUrl+'/api/accounts/postServiceQuantityPrice', data:data});
        },

        //  DELETE SERVICE ITEM
        deleteQuantityPrice:function (data) {
            return $http({method:'post', headers:headers, url:ApiUrl+'/api/service/deleteQuantityPrice',data:data})

        },
        deleteOptionPrice:function (data) {
            return $http({method:'post', headers:headers, url:ApiUrl+'/api/service/deleteOptionPrice',data:data})

        },
        deleteSmsPrice:function (data) {
            return $http({method:'post', headers:headers, url:ApiUrl+'/api/service/deleteSmsPrice',data:data})

        },
        deleteCallPrice:function (data) {
            return $http({method:'post', headers:headers, url:ApiUrl+'/api/service/deleteCallPrice',data:data})

        },
        deleteConfigHotlinePrice:function (data) {
            return $http({method:'post', headers:headers, url:ApiUrl+'/api/service/deleteConfigHotlinePrice',data:data})

        },
        deleteConfigPrice:function (data) {
            return $http({method:'post', headers:headers, url:ApiUrl+'/api/service/deleteConfigPrice',data:data})

        },




        postServiceCustomerHotline:function (data) {
            return $http({method:'post',headers:headers, url:ApiUrl+'/api/accounts/postServiceCustomerHotline', data:data});
        },


        updateServiceCustomerHotline:function (id, data) {
            return $http({method:'post',headers:headers, url:ApiUrl+'/api/accounts/postServiceCustomerHotline/'+id, data:data});
        },




        postServiceCustomerQuantity:function (data) {
            return $http({method:'post',headers:headers, url:ApiUrl+'/api/accounts/postServiceCustomerQuantity', data:data});
        },
        postServiceCustomerOption:function (data) {
            return $http({method:'post',headers:headers, url:ApiUrl+'/api/accounts/postServiceCustomerOption', data:data});
        },
        postSipRouting:function (data) {
            return $http({method:'post',headers:headers, url:ApiUrl+'/api/sip/postSipRouting', data:data});
        },

        saveFeeLimit:function (data) {
            return $http({method:'post',headers:headers, url:ApiUrl+'/api/accounts/saveFeeLimit', data:data});
        },



        saveAddFeeLimit:function (data) {
            return $http({method:'post',headers:headers, url:ApiUrl+'/api/accounts/saveAddFeeLimit', data:data});
        },
        getFeeLimitLogs:function (data) {
            return $http({method:'post',headers:headers, url:ApiUrl+'/api/accounts/getFeeLimitLogs', data:data});
        },

        saveRedWarning:function (data) {
            return $http({method:'post',headers:headers, url:ApiUrl+'/api/accounts/saveRedWarning', data:data});
        },

        // Report controller

        postViewReport:function (data, report) {
            return $http({method:'post',headers:headers, url:ApiUrl+'/api/report/'+report, data:data});
            // return $http({method:'post',headers:headers, url:ApiUrl+'/api/report/'+report, data:data});
        },
		postViewMonthlyAudit:function (data) {
            return $http({method:'post',headers:headers, url:ApiUrl+'/api/report/monthly', data:data});
            // return $http({method:'post',headers:headers, url:ApiUrl+'/api/report/'+report, data:data});
        },
		searchCustomer:function (data) {
            return $http({method:'post',headers:headers, url:ApiUrl+'/api/v1/customer/postSearchCustomer', data:data});
            // return $http({method:'post',headers:headers, url:ApiUrl+'/api/report/'+report, data:data});
        },
		searchReportGrowth:function (data) {
            return $http({method:'post',headers:headers, url:ApiUrl+'/api/report/searchReportGrowth', data:data});
            // return $http({method:'post',headers:headers, url:ApiUrl+'/api/report/'+report, data:data});
        },
		searchReportAudit:function (data) {
            return $http({method:'post',headers:headers, url:ApiUrl+'/api/report/searchReportAudit', data:data});
            // return $http({method:'post',headers:headers, url:ApiUrl+'/api/report/'+report, data:data});
        },
        searchReportMonthBilling:function (data) {
            return $http({method:'post',headers:headers, url:ApiUrl+'/api/report/searchReportMonthBilling', data:data});
            // return $http({method:'post',headers:headers, url:ApiUrl+'/api/report/'+report, data:data});
        },

        // Server config

		getListServer: function () {
			return $http({method: 'get', headers: headers, url: ApiUrl + '/api/admin/listServer'});
		},
		saveServer: function (data) {
			return $http({method: 'post', headers: headers, url: ApiUrl + '/api/admin/postServer', data:data});
		},

        saveActiveServer: function (data) {
			return $http({method: 'post', headers: headers, url: ApiUrl + '/api/admin/postActiveServer', data:data});
		},
		getLogs: function (data) {

			return $http({method: 'POST', headers: headers, url: ApiUrl + '/api/logs', data: data});

		},
		getServerResource: function (data) {
			return $http({method: 'post', headers: headers, url: ApiUrl + '/api/admin/server-resource', data:data});
		},
		saveMoveCustomerServer: function (data) {
			return $http({method: 'post', headers: headers, url: ApiUrl + '/api/admin/server-move-customer', data:data});
		},
		setRechargeCustomer: function (data) {
			return $http({method: 'post', headers: headers, url: ApiUrl + '/api/v1/customer/postRechargeCustomer', data:data});
		},

		postRequestCheckCharging: function (data) {
			return $http({method: 'post', headers: headers, url: ApiUrl + '/api/v1/charging/request-check-charging', data:data});
		},

	postRequestCharge: function (data) {
			return $http({method: 'post', headers: headers, url: ApiUrl + '/api/v1/charging/request-charge', data:data});
		},
	postChangeCfu: function (data) {
			return $http({method: 'post', headers: headers, url: ApiUrl + '/api/v1/customer/cfu', data:data});
		},


		getEntity: function (data) {
			return $http({
				method: 'GET',
				url: ApiUrl + '/api/entities?'
			});
		},
		setEntity: function (data) {
			return $http({
				method: 'POST',
				url: ApiUrl + '/api/entities',
				data:data
			});
		},

		setEntityRole: function (data) {
			return $http({
				method: 'POST',
				url: ApiUrl + '/api/entity-role',
				data:data
			});
		},

		removeEntityRole: function (data) {
			return $http({
				method: 'POST',
				url: ApiUrl + '/api/remove-entity-role',
				data:data
			});
		},


		getAllRoles: function (data) {
			return $http({
				method: 'GET',
				url: ApiUrl + '/api/roles',
				params:data
			});
		},

		setRole: function (data) {
			return $http({
				method: 'POST',
				url: ApiUrl + '/api/roles',
				data:data
			});
		},
		setRemoveRole: function (data) {
			return $http({
				method: 'POST',
				url: ApiUrl + '/api/remove-roles',
				data:data
			});
		},
		getInitNd91: function (data) {
			return $http({
				method: 'GET',
				url: ApiUrl + '/api/nd91/init',
				data:data
			});
		},
		getInitNd91TimeRange: function (data) {
			return $http({
				method: 'GET',
				url: ApiUrl + '/api/nd91/init-time-range',
				data:data
			});
		},
		getInitNd91Quota: function (data) {

        	return $http({
				method: 'GET',
				url: ApiUrl + '/api/nd91/init-quota',
				data:data
			});
		},
		setNd91RuleConfig: function (data) {
			return $http({
				method: 'POST',
				url: ApiUrl + '/api/nd91/set-dnc',
				data:data
			});
		},

		setNd91QuotaItem: function (data) {
			return $http({
				method: 'POST',
				url: ApiUrl + '/api/nd91/set-quota-item',
				data:data
			});
		},
		setNd91Quota: function (data) {
			return $http({
				method: 'POST',
				url: ApiUrl + '/api/nd91/set-quota',
				data:data
			});
		},
		setNd91Time: function (data) {
			return $http({
				method: 'POST',
				url: ApiUrl + '/api/nd91/set-time-range',
				data:data
			});
		},

	getNd91Report: function (data) {
			return $http({
				method: 'POST',
				url: ApiUrl + '/api/nd91/report',
				data:data
			});
		},
		getNd91ReportBrandname: function (data) {
			return $http({
				method: 'POST',
				url: ApiUrl + '/api/nd91/report-brandname',
				data: data
			});
		},
		getSyncReport: function (data) {
			return $http({
				method: 'POST',
				url: ApiUrl + '/api/nd91/syncReport',
				data: data
			});
		},

		postDNCBlacklist: function (data) {
			return $http({
				method: 'POST',
				url: ApiUrl + '/api/nd91/dnc-blacklist',
				data: data
			});
		},
	removeDNCBlacklist: function (data) {
			return $http({
				method: 'POST',
				url: ApiUrl + '/api/nd91/deactive-dnc-blacklist',
				data: data
			});
		},

	getDNCBlacklist: function (data) {
			return $http({
				method: 'GET',
				url: ApiUrl + '/api/nd91/dnc-blacklist',
				params: data
			});
		},

	getDNCWhitelist: function (data) {
			return $http({
				method: 'GET',
				url: ApiUrl + '/api/nd91/dnc-whitelist',
				params: data
			});
		},


	postDNCWhitelist: function (data) {
			return $http({
				method: 'POST',
				url: ApiUrl + '/api/nd91/dnc-whitelist',
				data: data
			});
		},


		getOperatorTelco: function (data) {
			return $http({
				method: 'GET',
				url: ApiUrl + '/api/accounts/operator-telco',
				data: data
			});
		},



	};
});

cms3c.factory('ApiUsers', function ($http, loginCheck) {
    var ApiUrl="";
    var Token = loginCheck.getSessions();
    var headers = {
        'Authorization': "Bearer " + Token,
        'token': Token,
		'Source':'1'
    };


    return {
        login:function (data) {
            return $http({method: 'post',  url:ApiUrl+'/api/auth/login', data: data});
        },
        loginWeb:function (data) {
            return $http({method: 'post',  url:ApiUrl+'/api/auth/loginWeb', data: data});
        },
        loginWebSS:function (data) {
            return $http({method: 'post',  url:ApiUrl+'/api2/loginWeb', data: data});
        },
        logOut:function (data) {
            return $http({method: 'post', headers:headers, url:ApiUrl+'/api/auth/logout', data: data});
        },
        addUser: function (data) {
            return $http({method: 'post',headers:headers, url:ApiUrl+'/api/admin/users', data: data});
        },
        changePassword: function (data) {
            return $http({method: 'post',headers:headers, url:ApiUrl+'/api/user/changepassword', data: data});
        },
        updateUser: function (data, id) {
            return $http({method: 'post',headers:headers, url:ApiUrl+'/api/admin/users/'+id , data: data});
        },
        viewUser: function (data) {
            return $http({method: 'get',headers:headers, url:ApiUrl+'/api/admin/users/'+data});
        },
        viewAll:function (data) {

            return $http({method: 'get',headers:headers, url:ApiUrl+'/api/admin/users/', params:data});
        },


        getRoles:function(data)
        {
            return $http({method:'post',headers:headers, url:ApiUrl+'/api/role/getRoles'});
        },

        getUserByRole:function(data)
        {
            return $http({method:'post',headers:headers, url:ApiUrl+'/api/role/getUserByRole'});
        },

    getAms:function(data)
        {
            return $http({method:'get',headers:headers, url:ApiUrl+'/api/admin/am'});
        },


    }
});

cms3c.factory('ApiV1', function ($http, loginCheck) {
    var ApiUrl="";
    var Token = loginCheck.getSessions();
    var headers = {
        'Authorization': "Bearer " + Token,
        'token': Token,
		'Source':'1'
    };

    return {
        addCustomer:function (data) {                        return $http({method:'POST', headers:headers,url: ApiUrl+'api/v1/customer/addCustomer', data:data});       },
        changeCustomerIdentity:function (data) {            return $http({method:'POST', headers:headers, url:ApiUrl+'api/v1/customer/changeCustomerIdentity', data:data});       },
        changeCustomerProductCode:function (data) {            return $http({method:'POST', headers:headers, url:ApiUrl+'api/v1/customer/changeCustomerProductCode', data:data});       },
		postChangePauseState:function (data) {            return $http({method:'POST', headers:headers, url:ApiUrl+'api/v1/customer/changePauseState', data:data});       },
		postChangePauseStateHotline:function (data) {            return $http({method:'POST', headers:headers, url:ApiUrl+'api/v1/hotline/changePauseStateHotline', data:data});       },
        changeCustomersStatus:function (data) {            return $http({method:'POST', headers:headers, url:ApiUrl+'api/v1/customer/changeCustomersStatus', data:data});       },
        editCustomer:function (data) {                       return $http({method:'POST', headers:headers, url:ApiUrl+'api/v1/customer/editCustomer', data:data});       },
        getCustomers:function (data) {                      return $http({method:'GET', headers:headers, url:ApiUrl+'api/v1/customer/getCustomers?'+$.param(data)});       },
        getCustomersV2:function (data) {                      return $http({method:'POST', headers:headers, url:ApiUrl+'api/v1/customer/getCustomersV2', data:data});       },
        removeCustomer:function (data) {                    return $http({method:'POST', headers:headers, url:ApiUrl+'api/v1/customer/removeCustomer', data:data});       },
        upgradeEnterpriseToHotline:function (data) {            return $http({method:'POST', headers:headers, url:ApiUrl+'api/v1/customer/upgradeEnterpriseToHotline', data:data});       },
        addCustomerHotlines:function (data) {            return $http({method:'POST', headers:headers, url:ApiUrl+'api/v1/hotline/addCustomerHotlines', data:data});       },
        changeHotlineConfig:function (data) {            return $http({method:'POST', headers:headers, url:ApiUrl+'api/v1/hotline/changeHotlineConfig', data:data});       },
        changeHotlineStatus:function (data) {            return $http({method:'POST', headers:headers, url:ApiUrl+'api/v1/hotline/changeHotlineStatus', data:data});       },
        getCustomerHotlines:function (data) {            return $http({method:'GET', headers:headers, url:ApiUrl+'api/v1/hotline/getCustomerHotlines', data:data});       },
        getHotlineConfig:function (data) {                 return $http({method:'GET', headers:headers, url:ApiUrl+'api/v1/hotline/getHotlineConfig', data:data});       },
        removeHotline:function (data) {                   return $http({method:'POST', headers:headers, url:ApiUrl+'api/v1/hotline/removeHotline', data:data});       },
        getServiceByCode:function (data) {            return $http({method:'GET', headers:headers, url:ApiUrl+'api/v1/service/getServiceByCode?'+ $.param(data)});       },
        getServices:function (data) {                   return $http({method:'GET', headers:headers, url:ApiUrl+'api/v1/service/getServices?'+ $.param(data)}); }



    }
});



















