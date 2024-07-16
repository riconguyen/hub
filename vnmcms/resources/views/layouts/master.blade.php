<?php
/**
 * Created by IntelliJ IDEA.
 * User: nnghi
 * Date: 01/11/2018
 * Time: 10:51 AM
 */

?>

<!doctype html>
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

<html ng-app="cms3c">
<title>{{$sitename}}</title>
<link rel="stylesheet" href="<?=asset('css/bootstrap.min.css')?>"/>
<link rel="stylesheet" href="<?=asset('css/ng-table.min.css')?>"/>
<link rel="stylesheet" href="<?=asset('css/font-awesome.min.css')?>"/>
<link rel="stylesheet" href="<?=asset('css/3ccms.css')?>?v=20181129"/>
<link rel="stylesheet" href="<?=asset('css/nya-bs-select.css')?>?v=20181129"/>
<link rel="stylesheet" href="<?=asset('css/jquery.jgrowl.min.css')?>?v=20181129"/>
<link rel="stylesheet" href="<?=asset('css/bootstrap-datetimepicker.min.css')?>?v=20181129"/>
<link rel="stylesheet" href="<?=asset('css/ng-tags-input.min.css')?>?v=20181129"/>
<link rel="stylesheet" href="<?=asset('css/ng-tags-input.bootstrap.min.css')?>?v=20181129"/>

<script>
    localStorage.setItem('lang', '{{$lang}}');
    var SERVER_PROFILE={{config("server.server_profile")}};
    var SERVER_PROFILE_BACKUP={{config("server.server_profile_backup")}};
</script>
<script src="<?= asset('js/jquery-2.2.4.min.js') ?>"></script>
<script src="<?= asset('js/popper.min.js') ?>"></script>
<script src="<?= asset('js/angular.min.js') ?>"></script>
<script src="<?= asset('js/angular-route.min.js') ?>"></script>
<script src="<?= asset('js/nya-bs-select.min.js') ?>"></script>

<script src="<?= asset('js/cms3c.js') ?>?v=20181129"></script>
<script src="<?= asset('js/cms3cRouting.js') ?>?v=20181129"></script>
<script src="<?= asset('js/cms3cReportController.js') ?>?v=20181129"></script>
<script src="<?= asset('js/cms3cServiceController.js') ?>?v=20181129"></script>
<script src="<?= asset('js/cms3cCustomerController.js') ?>?v=20181129"></script>
<script src="<?= asset('js/cms3cLogController.js') ?>?v=20181129"></script>
<script src="<?= asset('js/cms3cUserController.js') ?>?v=20181129"></script>
<script src="<?= asset('js/cms3cSipV2Controller.js') ?>?v=20181129"></script>
<script src="<?= asset('js/cms3cChargeController.js') ?>?v=20181129"></script>
<script src="<?= asset('js/cms3cNd91Controller.js') ?>?v=20181129"></script>
<script src="<?= asset('js/cms3cBlackListController.js') ?>?v=20181129"></script>
<script src="<?= asset('js/cms3c-api-services.js') ?>?v=20181129"></script>
<script src="<?= asset('js/angular-translate.min.js') ?>"></script>
<script src="<?= asset('js/angular-translate-loader-static-files.min.js') ?>"></script>
<script src="<?= asset('js/dist_xlsx.full.min.js') ?>"></script>

<script src="<?= asset('js/bootstrap.min.js') ?>"></script>

<script src="<?= asset('js/jquery.timeago.js') ?>"></script>
<script src="<?= asset('js/jquery.jgrowl.min.js') ?>"></script>

<script src="<?= asset('js/highcharts.js') ?>"></script>
<script src="<?= asset('js/ng-tags-input.min.js') ?>"></script>

<link rel="stylesheet"; href="<?= asset('css/ng-table.min.css') ?>">
<script src="<?= asset('js/ng-table.min02.js') ?>"></script>
<script src="<?= asset('js/moment.js') ?>"></script>
<script src="<?= asset('js/bootstrap-datetimepicker-bs4.js') ?>"></script>

<!-- Update recapcha -->
<!--<script src='https://www.google.com/recaptcha/api.js'></script>-->

<body class="bg-light">
<div id="loading" style="    position: fixed;
    top: 50%;
    left: 50%;">
    <div class="text-center w-100">
        <div ><span  style="" class="fa fa-spin fa-circle-o-notch fa-3x text-primary"></span></div>
    </div>
</div>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary  sticky-top"  ng-controller="loginController" style="border-bottom: 2px solid orange">
<!--    Remove Invisible when finish -->

    <a class="navbar-brand"  ng-click="switchNav('#');" href="#">V-Connect <sup class="text-muted small">{{config("server.server_profile")}}</sup></a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent"    >
        <ul class="navbar-nav mr-auto">

            <li class="nav-item"  ng-repeat="item in lstNav"  ng-if=" entity.VIEW_CUSTOMER" >
                <a class="nav-link text-uppercase "
                   ng-class="nav=='/accounts'?'active':''" data-toggle="collapse" data-target="#navbarSupportedContent"
                   ng-click="switchNav('accounts');" href="">@{{'NAV.CUSTOMER'|translate}} </a>
            </li>

            <li class="nav-item"   ng-if="  entity.VIEW_CUSTOMER" >
                <a class="nav-link text-uppercase "   ng-class="nav=='/accounts'?'active':''" data-toggle="collapse" data-target="#navbarSupportedContent"    ng-click="switchNav('accounts');" href="">@{{'NAV.CUSTOMER'|translate}} </a>
            </li>

              <li class="nav-item" ng-if=" entity.VIEW_SERVICE_CONFIG ">
                <a class="nav-link text-uppercase "  ng-class="nav=='/services'?'active':''"   data-toggle="collapse" data-target="#navbarSupportedContent"    ng-click="switchNav('services');" href="">@{{'NAV.SERVICES'|translate}}</a>
            </li>

            <li class="nav-item" ng-if="entity.VIEW_SIP_TRACKING">
                <a class="nav-link text-uppercase  "     ng-class="nav=='/sip'?'active':''"  data-toggle="collapse" data-target="#navbarSupportedContent"  ng-click="switchNav('sip');" href="">@{{'NAV.SIP_TRUNK'|translate}}</a>
            </li>
            <li class="nav-item" ng-if="entity.VIEW_SIP_TRACKING_V2">
                <a class="nav-link text-uppercase  "     ng-class="nav=='/sipv2'?'active':''"  data-toggle="collapse"
                   data-target="#navbarSupportedContent"  ng-click="switchNav('sipv2');" href="">Call Tracking V2</a>
            </li>
            <li class="nav-item" ng-if="entity.BLACK_LIST">
                <a class="nav-link text-uppercase  "     ng-class="nav=='/blacklist'?'active':''"  data-toggle="collapse" data-target="#navbarSupportedContent"  ng-click="switchNav('blacklist');" href="">@{{'NAV.BLACK_LIST'|translate}}</a>
            </li>
            <li class="nav-item" ng-if="entity.VIEW_REPORT ">
                <a class="nav-link text-uppercase  " ng-class="nav=='/report'?'active':''"   data-toggle="collapse" data-target="#navbarSupportedContent"     ng-click="switchNav('report');" href="">@{{'NAV.REPORT'|translate}}</a>
            </li>
            <li class="nav-item" ng-if="entity.LIST_USERS">
                <a class="nav-link text-uppercase"   ng-class="nav=='/user'?'active':''"   data-toggle="collapse" data-target="#navbarSupportedContent"  ng-click="switchNav('user');" href="">@{{'NAV.USER'|translate}}</a>
            </li>

  <li class="nav-item" ng-if="entity.CHARGING_MANUAL">
                <a class="nav-link text-uppercase"   ng-class="nav=='/charging'?'active':''"   data-toggle="collapse" data-target="#navbarSupportedContent"  ng-click="switchNav('charging');" href="">@{{'NAV.CHARGING'|translate}}</a>
            </li>
  <li class="nav-item" ng-if="entity.ND91_CONFIG">
                <a class="nav-link text-uppercase"   ng-class="nav=='/nd91'?'active':''"   data-toggle="collapse" data-target="#navbarSupportedContent"  ng-click="switchNav('nd91');" href="">ND91</a>
            </li>

<!--  <li class="nav-item" ng-if="userRole=='ADMIN' ">-->
<!--                <a class="nav-link text-uppercase"   ng-class="nav=='/setting'?'active':''"   data-toggle="collapse" data-target="#navbarSupportedContent"  ng-click="switchNav('setting');" href="">@{{'NAV.SETTING'|translate}}</a>-->
<!--            </li>-->

            <li class="nav-item"  ng-class="nav=='/logging'?'active':''" ng-if="entity.VIEW_CHANGE_LOG">
                <a class="nav-link collapsed" href="" ng-click="switchNav('logging');">

                    <span>LỊCH SỬ TÁC ĐỘNG</span>
                </a>

            </li>

        </ul>
        <form class="form-inline my-2 my-lg-0" >

            <button  ng-click="openConfig();"  ng-show="authUser" class="btn btn-outline-light my-2 ml-2 my-sm-0" type="button"> Thoát <i class="fa fa-cogs"></i></button>
        </form>
    </div>
</nav>

<div ng-view></div>

</body>
</html>
<div class="modal fade" id="configLoginUserModal" ng-controller="loginController" tabindex="-1" role="dialog"
     aria-labelledby="exampleModalLabel"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                Thiết lập tài khoản
            </div>
            <div class="modal-body">
                <div class="card text-center">

                    <div class="card-body">
                        <p> Xin chào @{{authUser.name}}</p>
                        <button ng-hide="changePass.btn"
                            ng-click="LogOutService();" class="btn btn-primary"><i class="fa fa-sign-out"></i>
                            @{{'LBL.SIGN_OUT'|translate}}
                        </button>
                        <button ng-class="changePass.btn?'btn-default':'btn-warning'"
                            ng-click="changePass.btn= !changePass.btn" class="btn"><i class="fa fa-sign-out"></i>
                            @{{'LBL.CHANGE_PASS'|translate}}
                        </button>
                    </div>
                    <div ng-if="changePass.btn" class="card-body">
                        <hr>
                        <div class="form-group">
                            <label>Mật khẩu cũ </label>
                            <input type="password" class="form-control text-center"
                                   ng-class="changePassError.oldPassword ?'is-invalid':''"
                                   ng-model="changePass.oldPassword">
                            <div class="invalid-feedback">
                                @{{changePassError.oldPassword[0]}}
                            </div>
                        </div>
                        <div class="form-group form-row">
                            <div class="col">


                            <label>Mật mới (*)</label>
                            <input type="password" class="form-control  text-center"
                                   ng-class="changePassError.newPassword ?'is-invalid':''"
                                   ng-model="changePass.newPassword">
                                <div class="invalid-feedback">
                                    @{{changePassError.newPassword[0]}}
                                </div>
                            </div>
                            <div class="col">


                            <label>Gõ lại mật khẩu mới (*)</label>
                            <input type="password" class="form-control  text-center"
                                   ng-class="changePassError.newPassword_confirmation ?'is-invalid':''"
                                   ng-model="changePass.newPassword_confirmation">
                                <div class="invalid-feedback">
                                    @{{changePassError.newPassword_confirmation[0]}}
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button class="btn btn-warning" ng-click="doChangePassword(changePass);">
                                <i class="fa fa-lock"></i> @{{'LBL.CHANGE_PASS'|translate}}
                            </button>
                        </div>

                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-close"></i> Đóng </button>
            </div>
        </div>
    </div>
</div>





@yield('content')


