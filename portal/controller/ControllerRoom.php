<?php

class ControllerRoom
{
    public function __construct(){
        Middleware::checkClientIP();//可在Config確認是否打開功能
        Middleware::ValBearerToken();//驗證Token，取的UserID
        Middleware::getReqJsonData();//取得JSON參數
        Middleware::getUserInfo();//根據UserID取的UserInfo
    }

    public function createRoom(){
        //Middle Var
        $UserID = Middleware::$UserID;
        $ReqData = Middleware::$ReqData;
        $UserInfo = Middleware::$UserInfo;
        //驗證資料
        $ReqData["room_group"]==0 && Validator::checkUserID($ReqData["map_user_id"]);//單人房才有user_id
        $ReqData["room_group"]==2 && Validator::checkGameCode($ReqData["game_code"]);
        Validator::checkRoomGroup($ReqData["room_group"]);
        Validator::checkRoomName($ReqData["room_name"]);
        //
        $oRoom = new Room();
        $Data = array();
        //只有代理可以創立
        if($ReqData["room_group"]==2 && $UserInfo["Level"]!="A" ) {
            $ResData['msg'] = "代理权限不足!";
            ResData::fail($ResData);
        }
        //設定新增房間資料
        $Data["RoomType"] = Room::$RoomType[$ReqData["room_group"]];//創建房型轉碼
        $Data["RoomName"] = $ReqData["room_name"];//設定房間名稱
        //創建房型代碼檢查
        if($Data["RoomType"]==""){
            $ResData['msg'] = "创建群组类型代码错误!";
            ResData::fail($ResData);
        }
        //創建房型代碼檢查
        if($Data["RoomName"]==""){
            $ResData['msg'] = "群组名称不能为空";
            ResData::fail($ResData);
        }
        //新增單人房，檢查兩人是否有共同的單人房間，有直接回傳該單人房間RoomID
        $oRoom2User = new Room2User();
        $RoomIDList = $oRoom2User->getUserInSameRoom(array($UserID, $ReqData["map_user_id"]));//兩人同房間的ID
        if( 0 == $ReqData["room_group"] ){
            if (count($RoomIDList) > 0) {
                $oRoom = new Room();
                $RoomList = $oRoom->getListbyID($RoomIDList);
                foreach ($RoomList as $value) {
                    //共同的房間是否為1V1房間
                    if ($value["RoomType"] == "S") {
                        //回應原本就有的房間ID
                        $ResData['room_id'] = $value["RoomID"];
                        $ResData['success'] = true;
                        ResData::success($ResData);
                    }
                }
            }
        }
        $Data["GameCode"] = $ReqData["game_code"];//房間要記錄GameCode
        //新增資料
        $Data["UserID"] = $UserID;
        if ($oRoom->create($Data)) {
            $InsertID = $oRoom->getInsertID();
            $oRoom2User = new Room2User();
            //房間創立成功，將創建人自動拉入房間
            $Data = array();
            $Data["RoomID"] = $InsertID;
            $Data["UserID"] = $UserID;
            $Data["Status"] = "Y";
            $oRoom2User->create($Data);
            //單人房 拉邀請人
            if (0 == $ReqData["room_group"]) {
                $Data = array();
                $Data["RoomID"] = $InsertID;
                $Data["UserID"] = $ReqData["map_user_id"];
                $Data["Status"] = "Y";
                $oRoom2User->create($Data);
            }
            //回應
            $ResData['room_group'] = $ReqData["room_group"];
            $ResData['room_id'] = $InsertID;
            $ResData['room_name'] = $ReqData["room_name"];
            $ResData['success'] = true;
            ResData::success($ResData);
        }
    }
    public function updateRoomName(){
        //Middle Var
        $UserID = Middleware::$UserID;
        $ReqData = Middleware::$ReqData;
        //驗證資料
        Validator::checkRoomName($ReqData["room_name"]);
        Validator::checkRoomID($ReqData["room_id"]);
        //房間只有創立房間人可以修改
        $oRoom = new Room();
        $Room = $oRoom->find($ReqData["room_id"]);
        if($Room["RoomType"]=="G" && $Room["UserID"]==$UserID){
            $ResData['msg'] = "仅创群代理人能够修改";
            ResData::fail($ResData);
        }
        //房間內的成員
        $oRoom2User = new Room2User();
        $Room2UserList = $oRoom2User->getList("RoomID=".$ReqData["room_id"]);
        $UserIDList = array_column($Room2UserList,"UserID");
        //修改房間名稱
        $Data = array();
        $Data["RoomName"] = $ReqData["room_name"];
        if($oRoom->update($Data," RoomID=".$ReqData["room_id"])){
            //群組房名修改，通知房間內所有連線中的用戶
            $oMsg = new Msg();
            $oMsg->sendRoomMsg($ReqData["room_id"],$UserIDList,"Custom","群組房名修改:".$ReqData["room_name"],1,false);
            //回應
            $ResData['room_id'] = $ReqData["room_id"];
            $ResData['room_name'] = $ReqData["room_name"];
            $ResData['success'] = true;
            ResData::success($ResData);
        }else{
            $ResData['msg']="修改错误";
            ResData::fail($ResData);
        }
    }
    public function getRoom(){
        //Middle Var
        $UserID = Middleware::$UserID;
        //
        $oRoom2User = new Room2User();
        $Room2UserList = $oRoom2User->getList("UserID=".$UserID." AND Status='Y'");
        $RoomIDList = array_column($Room2UserList,"RoomID");
        $oRoom = new Room();
        $RoomList = $oRoom->getListbyID($RoomIDList);
        //找出單人房對應的好友id   $SingleRoomFriendID[單人房id] = 好友id
        $SingleRoomID = array();//列出單人房ID
        if($RoomList){
            foreach ($RoomList as $value){
                if($value["RoomType"]=="S" ){
                    $SingleRoomID[] = $value["RoomID"];
                }
            }
        }
        //每個房間的通知狀態
        foreach ($Room2UserList as $value){
            $RoomPushNotificationList[$value["RoomID"]] = $value["PushNotification"] ;
        }
//    $oRoom2User = new Room2User();
        $SingleRoomFriendData = array();
        $SingleRoomFriendID = array();
        count($SingleRoomID)>0 && $SingleRoomFriendData =  $oRoom2User->getList("UserID !=".$UserID." AND RoomID IN(".join(",",$SingleRoomID).") AND Status='Y' ");//
        for($i=0;$i<count($SingleRoomFriendData);$i++){
            $SingleRoomFriendID[$SingleRoomFriendData[$i]["RoomID"]] = $SingleRoomFriendData[$i]["UserID"];//單人房id=>好友id
        }
        //根據ID找出Nickname
        $FriendNickname = array();
        $oUser = new User();
        $FriendList = $oUser->getListbyID($SingleRoomFriendID);
        for($i=0;$i<count($FriendList);$i++){
            $FriendNickname[$FriendList[$i]["UserID"]] = $FriendList[$i]["Nickname"];//好友id=>暱稱
        }

        //Room對應的最後MsgID
        $oMsg = new Msg();
        $Data = $oMsg->getLastMsgIDbyRoomID($RoomIDList);
        $LastMsgID = array();
        if($Data){
            foreach ($Data as $value)
                $LastMsgID[$value["RoomID"]] = $value["LastMsgID"];
        }
        //回應
        $ResData = array("data"=> array());
        if($RoomList!=""){
            foreach ($RoomList as $value){
                $RoomTypeFlip = array_flip(Room::$RoomType);//房型轉碼
                $group = $RoomTypeFlip[$value["RoomType"]];
                $RoomFriend = "";
                $RoomGameCode = "";
                if($value["RoomType"]=="S" ){
                    $RoomFriend = "";
                }elseif ($value["RoomType"]=="G"){
                    $RoomGameCode = $value["GameCode"];
                }
                $ResData["data"][] = array(
                    "room_id"=> $value["RoomID"] ,
                    "room_group"=> $group ,
                    "room_name"=> $value["RoomName"] ,
                    "room_friend"=>$SingleRoomFriendID[$value["RoomID"]],
                    "room_friend_nick"=>$FriendNickname[$SingleRoomFriendID[$value["RoomID"]]],
                    "room_game_code"=>$RoomGameCode,
                    "msg_id"=> $LastMsgID[$value["RoomID"]]==null?"0":$LastMsgID[$value["RoomID"]],//該房間最後一筆訊息id
                    "RoomPushNotification" => $RoomPushNotificationList[$value["RoomID"]],
                );
            }
        }
        ResData::success($ResData);
    }
    public function updatePushNotification(){
        //Middle Var
        $UserID = Middleware::$UserID;
        $ReqData = Middleware::$ReqData;
        //驗證資料
        Validator::checkRoomID($ReqData["room_id"]);
        Validator::checkArrayValue($ReqData["PushNotification"],['Y','N'],"PushNotification");
        //會員是否在房間內
        $oRoom2User = new Room2User();
        $Room2UserData = $oRoom2User->getList("RoomID=".$ReqData["room_id"]." AND UserID=".$UserID);
        //修改通知狀態
        $Data = array();
        $Data["PushNotification"] = $ReqData["PushNotification"];
        if( count($Room2UserData)>0 && $oRoom2User->update($Data,"RoomID=".$ReqData["room_id"]." AND UserID=".$UserID)){
            //回應
            $ResData['room_id'] = $ReqData["room_id"];
            $ResData['PushNotification'] = $ReqData["PushNotification"];
            ResData::success($ResData);
        }else{
            $ResData['msg']="Update Error";
            ResData::fail($ResData);
        }
    }
    public function joinUser(){
        //Middle Var
        $UserID = Middleware::$UserID;
        $ReqData = Middleware::$ReqData;
        $UserInfo = Middleware::$UserInfo;
        //驗證資料
        Validator::checkUserIDArray($ReqData["map_user_id"]);
        Validator::checkRoomID($ReqData["map_room_id"]);
        //確認該房間是否為多人房間
        $oRoom = new Room();
        $Room = $oRoom->find($ReqData["map_room_id"]);
        if($Room["RoomType"]=="S"){
            $ResData['msg']="单人群组无法操作";
            ResData::fail($ResData);
        }
        //房-會員無法操作、但是可以操作自己
        if($Room["RoomType"]=="G" && $UserInfo["Level"]!="A" && ( $UserID!=$ReqData["map_user_id"][0] || count($ReqData["map_user_id"])!=1 ) ){
            $ResData['msg']="群组会员无法操作";
            ResData::fail($ResData);
        }
        //房間-自己申請代理跟房間開房代理要一樣
        if($Room["RoomType"]=="G"){
            $oUser = new User();
            $UserList = $oUser->getListbyID($ReqData["map_user_id"]);
            for($i=0;$i<count($UserList);$i++){
                $createRoomAgent = $Room["UserID"];//創房代理
                if($UserList[$i]["Agent"]!=$createRoomAgent){
                    $ResData['msg']="会员不能申请其他代理的群";
                    ResData::fail($ResData);
                }
            }
        }
        //確認該會員使否在該房間


        //確認被邀請的會員是否在該房間，有的話自動剃除
        $oRoom2User = new Room2User();
        $DataList = $oRoom2User->getListbyID($ReqData["map_user_id"],"UserID","RoomID=".$ReqData["map_room_id"]." AND Status='Y'");
        $UserInRoom = array();
        if(count($DataList)>0)
            $UserInRoom = array_column($DataList,"UserID");//被邀請的會員已經在房間的列表
        $ReqData["map_user_id"] = array_diff($ReqData["map_user_id"], $UserInRoom);//剔除相同的
        //加入邀請會員進去房間
        if( count($ReqData["map_user_id"])>0 ){
            foreach ($ReqData["map_user_id"] as $value){
                $Data = array();
                $Data["RoomID"] = $ReqData["map_room_id"];
                $Data["UserID"] = $value;
                if($Room["RoomType"]=="G" && $UserInfo["Level"]!="A")
                    $Data["Status"] = "W";//會員申請房需要代理審核
                else
                    $Data["Status"] = "Y";
                $oRoom2User->create($Data);
            }
        }
        //如果此次邀請會員屬於房間且不是代理邀請，狀態為等待，所以就不發訊息跟推播
        if($Room["RoomType"]=="G" && $UserInfo["Level"]!="A"){
            //回應
            $ResData['membersNum']=count($ReqData["map_user_id"]);
            $ResData['room_id']=$ReqData["map_room_id"];
            $ResData['success']=true;
            ResData::success($ResData);
        }
        //新增進入房間訊息，通知房間內所有連線中的用戶
        $oMsg = new Msg();
        $oMsg->sendRoomMsg($ReqData["map_room_id"],$ReqData["map_user_id"],"inRoom");
        //回應
        $ResData['membersNum']=count($ReqData["map_user_id"]);
        $ResData['room_id']=$ReqData["map_room_id"];
        $ResData['success']=true;
        ResData::success($ResData);
    }
    public function kickUser(){
        //Middle Var
        $UserID = Middleware::$UserID;
        $ReqData = Middleware::$ReqData;
        $UserInfo = Middleware::$UserInfo;
        //驗證資料
        Validator::checkUserID($ReqData["map_user_id"]);
        Validator::checkRoomID($ReqData["map_room_id"]);
        //確認該房間是否為多人房間
        $oRoom = new Room();
        $Room = $oRoom->find($ReqData["map_room_id"]);
        if($Room["RoomType"]=="S"){
            $ResData['msg']="单人群组无法操作";
            ResData::fail($ResData);
        }
        //房-會員無法操作、但是可以操作自己
        if($Room["RoomType"]=="G" && $UserInfo["Level"]!="A" && $UserID!=$ReqData["map_user_id"]){
            $ResData['msg']="群组会员无法操作";
            ResData::fail($ResData);
        }
        //確認該會員使否在該房間

        //該房間會員數量【要再移除前先抓房間會員名單，這樣被移除的會員才會被通知到】
        $oRoom2User = new Room2User();
        $UserIDList = $oRoom2User->getList("RoomID=".$ReqData["map_room_id"]." AND Status='Y' ");
        //將會員移除房間
        $oRoom2User = new Room2User();
        if($oRoom2User->delData("RoomID=".$ReqData["map_room_id"]." AND UserID=".$ReqData["map_user_id"])){
            //新增離開房間訊息，通知房間內所有連線中的用戶
            $oMsg = new Msg();
            $oMsg->sendRoomMsg($ReqData["map_room_id"],array($ReqData["map_user_id"]),"kickRoom");
            //回應
            $ResData['user_id']=$ReqData["map_user_id"];
            $ResData['msg']="剔除成員";
            $ResData['success']=true;
            ResData::success($ResData);
        }else{
            $ResData['success']=false;
            $ResData['msg']="操作失敗";
            ResData::fail($ResData);
        }
    }
    public function getUser(){
        //Middle Var
        $ReqData = Middleware::$ReqData;
        //驗證資料
        Validator::checkRoomID($ReqData["map_room_id"]);
        //確認該會員使否在該房間

        //房間成員ID列表
        $oRoom2User = new Room2User();
        $UserIDList = $oRoom2User->getList("RoomID=".$ReqData["map_room_id"]." AND Status='Y'");
        $UserIDList = array_column($UserIDList,"UserID");
        //房間資訊
        $oRoom = new Room();
        $RoomData = $oRoom->find($ReqData["map_room_id"]);
        //房間成員資料列表
        $oUser = new User();
        $UserList = $oUser->getListbyID($UserIDList);
        //只要id跟nickname
        $members = array();
        if($UserList!=""){
            foreach ($UserList as $value){
                $members[] = array(
                    "user_id"=> $value["UserID"],
                    "nick"=> $value["Nickname"]
                );
            }
        }
        //房型轉碼
        $RoomTypeFlip = array_flip(Room::$RoomType);
        //回應
        $ResData['members']=$members;
        $ResData['room_id']=$ReqData["map_room_id"];
        $ResData['room_group'] = $RoomTypeFlip[$RoomData["RoomType"]];
        $ResData['room_name'] = $RoomData['RoomName'];
        $ResData['room_game_code'] = $RoomData['GameCode'];
        $ResData['success']=true;
        ResData::success($ResData);
    }
}