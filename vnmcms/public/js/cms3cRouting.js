
/** ROUTING ------------
 *
 */
cms3c.config(['$routeProvider', '$httpProvider',
    function ($routeProvider, $httpProvider) {
        $routeProvider.when('/login', {
            templateUrl: '/templates/login.html',
            controller: 'loginController'
        }).when('/addCustomer', {
            templateUrl: '/templates/customerAdd.html',
            controller: 'customerControllerAdd'
        })


                .when('/dashboard', {
            templateUrl: '/templates/index.html',
            controller: 'dashboardController'
        }).when('/services', {
            templateUrl: '/templates/services.html',
            controller: 'servicesController'
        }).when('/addService', {
            templateUrl: '/templates/addService.html',
            controller: 'servicesControllerAdd'
        }).when('/sip', {
            templateUrl: '/templates/sip.html',
            controller: 'sipController'
        }).when('/sipv2', {
            templateUrl: '/templates/sipv2.html',
            controller: 'sipV2Controller'
        }).when('/addSip', {
            templateUrl: '/templates/addSip.html',
            controller: 'sipControllerAdd'
        }).when('/accounts', {
            templateUrl: '/templates/accounts.html',
            controller: 'customerController'
        }) .when('/user', {
            templateUrl: '/templates/users.html',
            controller: 'userController'
        }) .when('/report', {
            templateUrl: '/templates/report.html',
            controller: 'reportController'
        }).when('/billing', {
            templateUrl: '/templates/billing.html',
            controller: 'billingController'
        }).when('/setting', {
            templateUrl: '/templates/setting.html',
            controller: 'settingController'
        }).when('/logging', {
			templateUrl: '/templates/log.html',
			controller: 'logController'
		}).when('/charging', {
			templateUrl: '/templates/charge.html',
			controller: 'chargingController'
		}).when('/nd91', {
			templateUrl: '/templates/nd91.html',
			controller: 'nd91Controller'
		}).when('/blacklist', {
			templateUrl: '/templates/blacklist.html',
			controller: 'blackListController'
		}).otherwise({
             redirectTo: 'dashboard'
        });
    }]);


/** Report controller
 *
 */