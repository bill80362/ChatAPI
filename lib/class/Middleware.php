<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2019/12/4
 * Time: 下午 05:27
 */

class Middleware
{
    static public $Token;
    static public $ReqData;
    static public $UserID;
    static public $UserInfo;

    //驗證Token
    static public function ValBearerToken(){
        $BearerToken = PubFunction::getBearerToken();//取的該次Request的Token
        Middleware::$Token = $BearerToken;
        $oUserToken = new UserToken();
        $UserID = $oUserToken->getUserIDbyToken($BearerToken);
        if($UserID<=0){
            //回應
            $ResData['msg']="Token Error";
            ResData::fail($ResData,401);
        }else{
            Middleware::$UserID = $UserID;
            return $UserID;
        }
    }
    //根據UserID取的UserInfo
    static public function getUserInfo(){
        $oUser = new User();
        Middleware::$UserInfo =  $oUser->find(Middleware::$UserID);
        return Middleware::$UserInfo;
    }
    //使用者IP檢查
    static public function checkClientIP(){
        //確認設定檔 是否檢查IP
        if(!IP_CHECK_STATUS)
            return true;//不檢查直接跳過
        //抓IP
        if ($_SERVER["HTTP_CLIENT_IP"]) {
            $_IP = $_SERVER["HTTP_CLIENT_IP"];
        } elseif ($_SERVER["HTTP_X_FORWARDED_FOR"]) {
            $ip_cfg = $_SERVER["HTTP_X_FORWARDED_FOR"];
            $ip_cfg_ary = explode(',', $ip_cfg, 2);
            $_IP = $ip_cfg_ary[0];
        } else {
            $_IP = $_SERVER["REMOTE_ADDR"];
        }
        //檢查IP
        $oCheckIP = new CheckIP();
        $rs = $oCheckIP->getStatus($_IP);
        if (!$rs && $oCheckIP->_ErrorMsg=="IP Access Deny") {
            //回應
            $ResData['msg'] = $_IP . ", IP Access Deny";
            ResData::fail($ResData, 403);
        }elseif(!$rs){
            $ResData['msg'] = $_IP . ", IP Access Deny";
            ResData::fail($ResData, 403);
        }else{
            //PASS
        }
        return true;
    }
    //抓取JSON參數
    static public function getReqJsonData(){
        Middleware::$ReqData = json_decode(file_get_contents("php://input"),true);
        return Middleware::$ReqData;
    }
    //限制會員存取
    static public function restrictedToMembers(){
        Middleware::$UserInfo["Level"]!="M" && ResData::fail(array("msg"=>"It is restricted to members only "));
    }
    //限制代理存取 //只有代理可以操作
    static public function restrictedToAgents(){
        Middleware::$UserInfo["Level"]!="A" && ResData::fail(array("msg"=>"It is restricted to agents only "));
    }


}