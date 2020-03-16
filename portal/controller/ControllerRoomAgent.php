<?php

class ControllerRoomAgent
{
    public function __construct(){
        Middleware::checkClientIP();//可在Config確認是否打開功能
        Middleware::ValBearerToken();//驗證Token，取的UserID
        Middleware::getReqJsonData();//取得JSON參數
        Middleware::getUserInfo();//根據UserID取的UserInfo
        Middleware::restrictedToAgents();//只有代理可以操作
    }

    public function getCheckList(){
        //Middle Var
        $ReqData = Middleware::$ReqData;
        $UserInfo = Middleware::$UserInfo;
        //驗證資料
        Validator::checkRoomStatus($ReqData["status"]);
        //審核狀態檢查
        if( !in_array($ReqData["status"],Room2User::$Status) ){
            $ResData['msg']="Status錯誤";
            ResData::fail($ResData);
        }
        //列出代理底下會員
        $oUser = new User();
        $UserList = $oUser->getList("Agent='" . $UserInfo["Account"] . "'");
        $UserIDList = array_column($UserList, "UserID");
        //根據ID找Nickname
        for ($i = 0; $i < count($UserList); $i++) {
            $UserNickname[$UserList[$i]["UserID"]] = $UserList[$i]["Nickname"];//好友id=>暱稱
        }
        //所有會員審核中的房間
        $oRoom2User = new Room2User();
        $UserIDList && $Room2UserList = $oRoom2User->getListbyID($UserIDList,"UserID", "Status='".$ReqData["status"]."'");
        $RoomIDList = array_column($Room2UserList, "RoomID");
        //列出該代理開的RoomID
        $oRoom = new Room();
        $RoomIDList && $RoomList = $oRoom->getListbyID($RoomIDList);
        //檢查$Room2UserList是否有非該代理開的房間
        if ($Room2UserList != "") {
            foreach ($Room2UserList as $key => $value) {
                if (in_array($value["RoomID"],$RoomIDList))
                    unset($Room2UserList[$key]);
            }
        }
        //根據ID找RoomName
        for ($i = 0; $i < count($RoomList); $i++) {
            $RoomListByID[$RoomList[$i]["RoomID"]] = $RoomList[$i];
        }

        //回應
        $ResData = array("data" => array());
        if ($Room2UserList != "") {
            foreach ($Room2UserList as $value) {
                //房間資訊
                $RoomData = $RoomListByID[$value["RoomID"]];
                $RoomTypeFlip = array_flip(Room::$RoomType);//房型轉碼
                $group = $RoomTypeFlip[$RoomData["RoomType"]];
                //申請人暱稱
                $Nickname = $UserNickname[$value["UserID"]];
                $ResData["data"][] = array(
                    "room_id" => $value["RoomID"],
                    "room_group" => (string)$group,
                    "room_name" => $RoomData["RoomName"],
                    "user_id" => $value["UserID"],
                    "user_nick" => $Nickname,
                    "room_game_code" => $RoomData["GameCode"],
                );
            }
        }
        ResData::success($ResData);
    }
    public function updateCheck(){
        //Middle Var
        $ReqData = Middleware::$ReqData;
        //驗證資料
        Validator::checkRoomID($ReqData["map_room_id"]);
        Validator::checkUserID($ReqData["map_user_id"]);
        Validator::checkRoomStatus($ReqData["status"]);
        //審核狀態檢查
        if( !in_array($ReqData["status"],Room2User::$Status) ){
            $ResData['msg']="Status Error";
            ResData::fail($ResData);
        }
        //確認資料存在
        $oRoom2User = new Room2User();
        $Room2User = $oRoom2User->getData("RoomID=".$ReqData["map_room_id"]." AND UserID=".$ReqData["map_user_id"]);
        if(!$Room2User)
            ResData::fail(array("msg"=>"该笔资料不存在"));
        //更新
        $Data = array();
        $Data["Status"] = $ReqData["status"];
        if($Data["Status"]=="Y"){
            $result = $oRoom2User->update($Data,"RoomID=".$ReqData["map_room_id"]." AND UserID=".$ReqData["map_user_id"]);
        }else{
            $result = $oRoom2User->delData("RoomID=".$ReqData["map_room_id"]." AND UserID=".$ReqData["map_user_id"]);
        }
        if($result){
            //新增進入房間訊息，通知房間內所有連線中的用戶
            $oMsg = new Msg();
            $oMsg->sendRoomMsg($ReqData["map_room_id"],array($ReqData["map_user_id"]),"inRoom");

            //回應
            $ResData['map_room_id']= $ReqData["map_room_id"];
            $ResData['map_user_id']= $ReqData["map_user_id"];
            $ResData['status']= $ReqData["status"];
            ResData::success($ResData);
        }else{
            $ResData['msg']="更新失败";
            ResData::fail($ResData);
        }
    }
}