<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2019/12/4
 * Time: 下午 03:02
 */

class UserLogin extends DBModel
{
    public $_errorMsg;

    public function login($_Account,$_Passwd,$_Tpl,$_Device="",$_DeviceToken="",$_RecommendUserID=0){
        $oWBPortal = new WBPortal();
        if($oWBPortal->login($_Account,$_Passwd,$_Tpl)){
            $_Agent = $oWBPortal->AgentAccount;//取得代理帳號
            $oWBPortal->getWBToken() || ResData::fail(array("msg"=>$oWBPortal->_errorMsg));
            //WB登入成功，紀錄token
            $oUser = new User();
            $User = $oUser->getData("Account='".$_Account."'");
            if(count($User)==0){
                //建立資料
                $Data = array();
                $Data["Account"] = $_Account;
                $Data["Agent"] = $_Agent;
                $Data["WBToken"] = $oWBPortal->WBToken;
                $Data["Tpl"] = $_Tpl;
                $Data["Level"] = "M";
                $Data["Device"] = $_Device;
                $Data["DeviceToken"] = $_DeviceToken;
                $Data["Recommend"] = $_RecommendUserID;
                $Data["ChatID"] = PubFunction::create_uuid();
                if($oUser->create($Data)){
                    //取得會員資料
                    $User = $Data;
                    $User["UserID"] = $oUser->getInsertID();
                    //自動與推薦人互加好友-會員加推薦人
                    $Data = array();
                    $Data["UserID"] = $User["UserID"];
                    $Data["FriendID"] = $_RecommendUserID;
                    $oUser2User = new User2User();
                    $UserAddFriendData = $oUser2User->getData(" UserID=".$Data["UserID"]." AND FriendID=".$Data["FriendID"]);
                    count($UserAddFriendData)==0 && $oUser2User->create($Data);
                    //自動與推薦人互加好友-推薦人加會員
                    $Data = array();
                    $Data["UserID"] = $_RecommendUserID;
                    $Data["FriendID"] = $User["UserID"];
                    $oUser2User = new User2User();
                    $UserAddFriendData = $oUser2User->getData(" UserID=".$Data["UserID"]." AND FriendID=".$Data["FriendID"]);
                    count($UserAddFriendData)==0 && $oUser2User->create($Data);
                    //自動與代理互加好友-會員加代理
                    $AgentUser = $oUser->getData("Account='".$_Agent."'");
                    $Data = array();
                    $Data["UserID"] = $User["UserID"];
                    $Data["FriendID"] = $AgentUser["UserID"];
                    $oUser2User = new User2User();
                    $UserAddFriendData = $oUser2User->getData(" UserID=".$Data["UserID"]." AND FriendID=".$Data["FriendID"]);
                    count($UserAddFriendData)==0 && $oUser2User->create($Data);
                    //自動與代理互加好友-代理加會員
                    $AgentUser = $oUser->getData("Account='".$_Agent."'");
                    $Data = array();
                    $Data["UserID"] = $AgentUser["UserID"];
                    $Data["FriendID"] = $User["UserID"];
                    $oUser2User = new User2User();
                    $UserAddFriendData = $oUser2User->getData(" UserID=".$Data["UserID"]." AND FriendID=".$Data["FriendID"]);
                    count($UserAddFriendData)==0 && $oUser2User->create($Data);
                }else{
                    ResData::fail(array("msg"=>"資料庫新增錯誤!"));
                }
            }else{
                //更新資料
                $Data = array();
                $Data["WBToken"] = $oWBPortal->WBToken;
                $Data["Device"] = $_Device;
                $Data["DeviceToken"] = $_DeviceToken;
                if($oUser->update($Data," UserID=".$User["UserID"])){
                    $User["WBToken"] = $Data["WBToken"];
                }
            }
            return $User;
        }else{
            $this->_errorMsg = $oWBPortal->curlLog["login"];
            return false;
        }
    }
    public function loginAgent($_Account,$_Passwd,$_Tpl,$_Device="",$_DeviceToken=""){
        $oWBPortal = new WBPortal();
        if($oWBPortal->loginAgent($_Account,$_Passwd)){
            $oUser = new User();
            $User = $oUser->getData("Account='".$_Account."'");
            if(count($User)==0){
                //建立資料
                $Data = array();
                $Data["Account"] = $_Account;
                $Data["Agent"] = $_Account;
                $Data["Tpl"] = $_Tpl;
                $Data["Level"] = "A";
                $Data["Device"] = $_Device;
                $Data["DeviceToken"] = $_DeviceToken;
                $Data["ChatID"] = PubFunction::create_uuid();
                if($oUser->create($Data)){
                    //取得會員資料
                    $User = $Data;
                    $User["UserID"] = $oUser->getInsertID();
                }else{
                    ResData::fail(array("msg"=>"资料库新增错误!"));
                }
            }else{
                //更新登入裝置
                $Data = array();
                $Data["Device"] = $_Device;
                $Data["DeviceToken"] = $_DeviceToken;
                $oUser->update($Data," UserID=".$User["UserID"]);
            }
            return $User;
        }else{
            return false;
        }
    }
}