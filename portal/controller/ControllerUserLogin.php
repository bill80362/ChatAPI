<?php

class ControllerUserLogin
{
    //Middle Area
    public function __construct(){
        Middleware::checkClientIP();//可在Config確認是否打開功能
        Middleware::getReqJsonData();//取得JSON參數
    }
    public function login(){
        //Middle Var
        $ReqData = Middleware::$ReqData;
        //資料驗證
        Validator::checkUsername($ReqData["username"]);
        Validator::checkPassword($ReqData["password"]);
        Validator::checkTpl($ReqData["tpl"]);
        Validator::checkLevel($ReqData["level"]);
        Validator::checkArrayValue($ReqData["device"],['I','A'],"device");
        //Validator::checkNotEmpty($ReqData["device_token"],"device_token");//device_token不能檢查，因為通知如果關閉會是空值
        //登入
        $oUserLogin = new UserLogin();
        try{
            if($ReqData["level"]=="agent"){
                $User = $oUserLogin->loginAgent($ReqData["username"],$ReqData["password"],$ReqData["tpl"],$ReqData["device"],$ReqData["device_token"]);
            }else{
                $User = $oUserLogin->login($ReqData["username"],$ReqData["password"],$ReqData["tpl"],$ReqData["device"],$ReqData["device_token"]);
            }
        }catch (Exception $e){
            ResData::failException($e);
        }

        if($User["UserID"]>0){
            //登入成功，回覆Token
            $oUserToken = new UserToken();
            $oUserToken->newToken($User["UserID"]);
            //回應
            $ResData['nick']=$User["Nickname"];
            $ResData['chat_id']=$User["ChatID"];
            $ResData['token']=$oUserToken->_Token;
            $ResData['id']=$User["UserID"];
            $ResData['agent']=$User["Agent"];
            $ResData['expire']=$oUserToken->oDateTime_Expire->format(DateTime::ATOM);
            ResData::success($ResData);
        }else{
            //登入失敗紀錄

            //錯誤訊息
            preg_match_all ('/var\sstr=\'(.*)\';var\sta/', $oUserLogin->_errorMsg, $matches);
            $ErrMsg=$matches[1][0];
            //回應
            $ResData['msg']=$ErrMsg;
            ResData::fail($ResData,400);
        }
    }
    public function register(){
        //Middle Var
        $ReqData = Middleware::$ReqData;
        //資料驗證
        Validator::checkUsername($ReqData["username"]);
        Validator::checkPassword($ReqData["password"]);
        Validator::checkTpl($ReqData["tpl"]);
        Validator::checkChatID($ReqData["recommend"]);
        Validator::checkArrayValue($ReqData["device"],['I','A'],"device");
        //Validator::checkNotEmpty($ReqData["device_token"],"device_token");//device_token不能檢查，因為通知如果關閉會是空值
        //註冊需要推薦人才能註冊
        $oRecommend = new User();
        $RecommendUser = $oRecommend->getData("ChatID='".$ReqData["recommend"]."' ");
        if(count($RecommendUser)==0)
            ResData::fail(array("msg"=>"推荐人资讯错误"));
        //註冊
        $oWBPortal = new WBPortal();
        try{
            $oWBPortal->register($ReqData["username"],$ReqData["password"],$RecommendUser["Agent"]);
            $oWBPortal->getWBToken();
        }catch (Exception $e){
            ResData::failException($e);
        }

        if($oWBPortal->WBToken!=""){
            $oUserLogin = new UserLogin();
            $User = $oUserLogin->login($ReqData["username"],$ReqData["password"],$ReqData["tpl"],$ReqData["device"],$ReqData["device_token"],$RecommendUser["UserID"]);
            if($User["UserID"]>0){
                //登入成功，回覆Token
                $oUserToken = new UserToken();
                $oUserToken->newToken($User["UserID"]);
                //回應
                $ResData['nick']=$User["Nickname"];
                $ResData['chat_id']=$User["ChatID"];
                $ResData['token']=$oUserToken->_Token;
                $ResData['id']=$User["UserID"];
                $ResData['agent']=$User["Agent"];
                $ResData['expire']=$oUserToken->oDateTime_Expire->format(DateTime::ATOM);
                ResData::success($ResData);
            }else{
                //登入失敗紀錄

                //回應
                $ResData['msg']="登入错误";
                ResData::fail($ResData);
            }

        }else{
            ResData::fail(array("msg"=>"错误"));
        }
    }
}