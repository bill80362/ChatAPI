<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2020/2/18
 * Time: 下午 01:20
 */

class ControllerMember{
    public function __construct(){
        Middleware::checkClientIP();//可在Config確認是否打開功能
        Middleware::ValBearerToken();//驗證Token，取的UserID
        Middleware::getUserInfo();//根據UserID取的UserInfo
        Middleware::getReqJsonData();//取得JSON參數
        Middleware::restrictedToMembers();//只限會員
    }
    public function getDeposit(){
        //Middle Var
        $UserID = Middleware::$UserID;
        $ReqData = Middleware::$ReqData;
        //根據UserID拿到WBToken
        $oUser = new User();
        $User = $oUser->find($UserID);
        //連線WB
        $ReqData["GameType"] = "BJ";//使用BJ的P KEY
        $oWBPortal = new WBPortal();
        $oWBPortal->WBToken = $User["WBToken"];
        $oWBPortal->getD3P($ReqData["GameType"]) || ResData::fail(array("msg"=>$oWBPortal->curlLog["getD3P"]),401);
        $oWBPortal->getDepositUrl($ReqData["GameType"]) || ResData::fail(array("msg"=>$oWBPortal->_errorMsg));
        $Data = $oWBPortal->DepositUrl;

        //回應
        $ResData['data'] = $Data;
        $ResData['success'] = true;
        ResData::success($ResData);
    }
    public function getWithdraw(){
        //Middle Var
        $UserID = Middleware::$UserID;
        $ReqData = Middleware::$ReqData;
        //根據UserID拿到WBToken
        $oUser = new User();
        $User = $oUser->find($UserID);
        //連線WB
        $ReqData["GameType"] = "BJ";//使用BJ的P KEY
        $oWBPortal = new WBPortal();
        $oWBPortal->WBToken = $User["WBToken"];
        $oWBPortal->getD3P($ReqData["GameType"]) || ResData::fail(array("msg"=>$oWBPortal->curlLog["getD3P"]),401);
        $oWBPortal->getWithdrawUrl($ReqData["GameType"]) || ResData::fail(array("msg"=>$oWBPortal->_errorMsg));
        $Data = $oWBPortal->WithdrawUrl;

        //回應
        $ResData['data'] = $Data;
        $ResData['success'] = true;
        ResData::success($ResData);
    }
}