<?php

class ControllerUser{
    public function __construct(){
        Middleware::checkClientIP();//可在Config確認是否打開功能
        Middleware::ValBearerToken();//驗證Token，取的UserID
        Middleware::getUserInfo();//根據UserID取的UserInfo
        Middleware::getReqJsonData();//取得JSON參數
    }
    public function refreshToken(){
        //Middle Var
        $UserID = Middleware::$UserID;
        //給新Token
        $oUserToken = new UserToken();
        $oUserToken->newToken($UserID);
        //回應
        $ResData['token']=$oUserToken->_Token;
        $ResData['id']=$UserID;
        $ResData['expire']=$oUserToken->oDateTime_Expire->format(DateTime::ATOM);
        ResData::success($ResData);
    }
    public function updateNick(){
        //Middle Var
        $UserID = Middleware::$UserID;
        $ReqData = Middleware::$ReqData;
        //資料驗證
        Validator::checkNickname($ReqData["nick"]);
        //修改暱稱
        $oUser = new User();
        $Data = array();
        $Data["Nickname"] = $ReqData["nick"];
        $oUser->update($Data,"UserID=".$UserID);
        //回應
        $ResData['nick']=$ReqData["nick"];
        $ResData['success']=true;
        ResData::success($ResData);
    }
    public function addFriend(){
        //Middle Var
        $UserID = Middleware::$UserID;
        $ReqData = Middleware::$ReqData;
        $UserInfo = Middleware::$UserInfo;
        //資料驗證
        Validator::checkUserID($ReqData["fd_map_user_id"]);
        //好友是否存在
        $oUser = new User();
        $FriendUser = $oUser->find((int)$ReqData["fd_map_user_id"]);
        if($FriendUser==""){$ResData['msg']="好友不存在";ResData::fail($ResData); }
        if($FriendUser["Agent"]!=$UserInfo["Agent"]){$ResData['msg']="好友不存在";ResData::fail($ResData); }//不同代理無法加入好友
        //是否已有加入好友
        $oUser2User = new User2User();
        $Data = $oUser2User->getData(" UserID=".$UserID." AND FriendID=".$ReqData["fd_map_user_id"]);
        if(count($Data)>0){
            $ResData['success']=false;
            $ResData['msg']="已加入好友";
            ResData::fail($ResData);
        }
        //根據id加入好友
        $Data = array();
        $Data["UserID"] = $UserID;
        $Data["FriendID"] = $ReqData["fd_map_user_id"];
        //好友資料
        $oUser = new User();
        $User = $oUser->find($ReqData["fd_map_user_id"]);
        //回應
        if($oUser2User->create($Data)){
            $ResData['nick']=$User["Nickname"];
            $ResData['user_id']=$User["UserID"];
            $ResData['success']=true;
            ResData::success($ResData);
        }else{
            $ResData['success']=false;
            $ResData['msg']="無此用戶";
            ResData::fail($ResData);
        }
    }
    public function getFriend(){
        //Middle Var
        $UserID = Middleware::$UserID;
        //抓出好友的ID列表
        $oUser2User = new User2User();
        $DataList = $oUser2User->getList(" UserID=".$UserID);
        $FriendIDArray = array_column($DataList,"FriendID");
        $oUser = new User();
        $FriendList = $oUser->getListbyID($FriendIDArray);
        //回應
        $ResData = array("data"=> array());
        if($FriendList)
            foreach ($FriendList as $value)
                $ResData["data"][] = array(
                    "user_id"=> $value["UserID"] ,
                    "nick"=> $value["Nickname"],
                    "level"=> $value["Level"],
                );
        ResData::success($ResData);
    }
    public function delFriend(){
        //Middle Var
        $UserID = Middleware::$UserID;
        $ReqData = Middleware::$ReqData;
        //資料驗證
        Validator::checkUserID($ReqData["fd_map_user_id"]);
        //好友是否存在
        $oUser = new User();
        $FriendUser = $oUser->find((int)$ReqData["fd_map_user_id"]);
        if($FriendUser==""){$ResData['msg']="好友不存在";ResData::fail($ResData); }
        //是否已有加入好友
        $oUser2User = new User2User();
        $Data = $oUser2User->getData(" UserID=".$UserID." AND FriendID=".$ReqData["fd_map_user_id"]);
        if(count($Data)==0){
            $ResData['success']=false;
            $ResData['msg']="無加入好友";
            ResData::fail($ResData);
        }
        //刪除好友
        if($oUser2User->delData(" UserID=".$UserID." AND FriendID=".$ReqData["fd_map_user_id"])){
            $ResData['fd_map_user_id']=$ReqData["fd_map_user_id"];
            $ResData['success']=true;
            $ResData['msg']="已刪除好友";
            ResData::success($ResData);
        }else{
            $ResData['success']=false;
            $ResData['msg']="無此用戶";
            ResData::fail($ResData);
        }
    }
    public function addmefriend(){
        //Middle Var
        $UserID = Middleware::$UserID;
        //抓出好友的ID列表
        $oUser2User = new User2User();
        $DataList = $oUser2User->getList("FriendID=".$UserID);
        $addMeFriendIDArray = array_column($DataList,"UserID");

        $DataList = $oUser2User->getListbyID($addMeFriendIDArray,"FriendID","UserID=".$UserID);
        $DataList && $myFriendIDArray = array_column($DataList,"FriendID");
        $whoAddMe = $myFriendIDArray ? array_diff($addMeFriendIDArray, $myFriendIDArray):[];//加我的好友 - 我已加入的好友 = 加我但我沒加他的好友
        $oUser = new User();
        $whoAddMe && $whoAddMeUserData = $oUser->getListbyID($whoAddMe);
        //回應
        $ResData = array("data"=>array());
        if($whoAddMeUserData)
            foreach ($whoAddMeUserData as $value)
                $ResData["data"][] = array(
                    "user_id"=>$value["UserID"],
                    "nick"=>$value["Nickname"],
                );
        ResData::success($ResData);
    }
    public function getAvatar(){
        //Middle Var
        $ReqData = Middleware::$ReqData;
        //資料驗證
        Validator::checkUserIDArray($ReqData["user_id"]);
        //
        $ResData = array("data"=>array());
        if($ReqData["user_id"] && is_array($ReqData["user_id"]))
            foreach ($ReqData["user_id"] as $key => $value){
                //載入圖片
                $Base64_Image = "";
                $UserAvatarImgPath = SITE_PATH."/avatar_img/".$value.".jpg";//圖片路徑
                file_exists($UserAvatarImgPath) && $Base64_Image = UserAvatar::Image_to_Base64($UserAvatarImgPath);
                list($PreCode,$Base64_Image) = explode(",",$Base64_Image);//拿掉 data:image/jpeg;base64
                //回應資料
                $ResData["data"][] = array(
                    "user_id"=>$value,
                    "avatar"=>$Base64_Image,
                    "up_time"=> file_exists($UserAvatarImgPath)?filemtime($UserAvatarImgPath):0,
                    "file_size_kb"=>ceil(strlen($Base64_Image)/1000),//頭像大小
//                "file_path"=>"/img/avatar_img.php?img=".$value.".jpg",
                );
            }
        //回應
        ResData::success($ResData);
    }
    public function updateAvatar(){
        //Middle Var
        $UserID = Middleware::$UserID;
        $ReqData = Middleware::$ReqData;
        //資料驗證
        Validator::checkNotEmpty($ReqData["avatar"],"Avatar can not be null");
        //儲存圖片(存在會覆蓋)
        $UserAvatarImgPath = SITE_PATH."/avatar_img/".$UserID.".jpg";
        $rs = UserAvatar::Base64_to_Image("data:image/jpeg;base64,".$ReqData["avatar"],$UserAvatarImgPath);
        //回應
        if($rs){
            $ResData = array();
            ResData::success($ResData);
        }else{
            $ResData['msg'] = "更新失败";
            ResData::fail($ResData);
        }
    }
}