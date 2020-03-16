<?php


class Msg extends DBModel
{
    static public $MsgType = array("C","S","G");

    public function getLastMsgIDbyRoomID($RoomIDArray){
        if(count($RoomIDArray)==0)
            return false;
        $sql = "SELECT RoomID,MAX(MsgID) as LastMsgID FROM ".$this->TableName." WHERE RoomID IN (".join(',',$RoomIDArray).") GROUP BY RoomID ";
        $this->_DB['S']->query($sql);
        $List = $this->_DB['S'] -> get_total_data();
        return $List;
    }

    //新增房間訊息 + EventSource(長連線)通知房間內所有連線中的用戶
    public function sendRoomMsg($_RoomID,$_UserIDArray,$_Method,$_MsgContent="",$_MsgSystem=1,$showNicknameArray=true){
        if($_Method=="Custom"){
            $_MsgSystem = 1;
        }elseif($_Method=="inRoom"){
            $showNicknameArray = true;
            $_MsgContent = "已进入群组";
            $_MsgSystem = 1;
        }elseif($_Method=="kickRoom"){
            $showNicknameArray = true;
            $_MsgContent = "已离开群组";
            $_MsgSystem = 1;
        }else{
            exit("...");
        }
        /***串流Start，通知房間內所有連線中的用戶***/
        //房型轉碼 取得 room_group
        $oRoom = new Room();
        $Room = $oRoom->find($_RoomID);
        $RoomTypeFlip = array_flip(Room::$RoomType);
        $room_group = $RoomTypeFlip[$Room["RoomType"]];
        //抓會員加入房間暱稱
        $oUser = new User();
        $UserData = $oUser->getListbyID($_UserIDArray);
        $UserNicknameArray = array();
        if($UserData && count($UserData)>0 ){
            foreach ($UserData as $key=>$val)
                $UserNicknameArray[$key] =$val["Nickname"];
        }
        //該房間會員數量
        $oDatetime_NOW = new DateTime();
        $oRoom2User = new Room2User();
//        $UserIDList = $oRoom2User->getListbyID(array($_RoomID),"RoomID","Status='Y'");//該房間的會員列表
        $Room2UserData = $oRoom2User->getList("RoomID=".$_RoomID." AND Status='Y' ");
        $UserIDList = array_column($Room2UserData,"UserID");

        //房間會員的推播狀態是否開啟
        foreach ($Room2UserData as $value){
            $UserData[$value["UserID"]]["PushNotification"] = $value["PushNotification"];
        }
        //抓會員的裝置跟推播資料
        $oUser = new User();
        $UserDataList = $oUser->getListbyID($UserIDList);
        if($UserDataList){
            foreach ($UserDataList as $value){
                $UserData[$value["UserID"]]["Device"] = $value["Device"];
                $UserData[$value["UserID"]]["DeviceToken"] = $value["DeviceToken"];
            }
        }
        //房間名稱
        $oRoom = new Room();
        $Room = $oRoom->find($_RoomID);
        $RoomName = $Room["RoomName"];

        if($UserIDList && count($UserIDList)>0 && $UserNicknameArray && count($UserNicknameArray)>0 ){
            //新增訊息進去DB
            $oMsg = new Msg();
            $Data = array();
            $Data["UserID"] = 0;
            $Data["RoomID"] = $_RoomID;
            $Data["MsgContent"] = $showNicknameArray?join(",",$UserNicknameArray).$_MsgContent:$_MsgContent;
            $Data["NewTime"] = $oDatetime_NOW->format("Y-m-d H:i:s");
            $Data["MsgType"] = "S";
            $oMsg->create($Data);

            //處理串流資料
            foreach ($UserIDList as $value){
                //開始給串流
                $oMsgStream = new MsgStream($value);
                if($oMsgStream->isUserStream()){
                    $Data = array(
                        'msg_add_time' => $oDatetime_NOW->getTimestamp(),
                        'msg_id' => $oMsg->getInsertID(),
                        'msg_message' => $showNicknameArray?join(",",$UserNicknameArray).$_MsgContent:$_MsgContent,
                        'msg_room_id' => (int)$_RoomID,
                        'msg_system' => $_MsgSystem,
                        'msg_user_id' => "0",
                        'room_name' => $Room["RoomName"],
                        'user_id' => "0",
                        'room_game_code'=> $Room["GameCode"],
                    );
//                    print_r($Data);
                    //Push一筆
                    $oMsgStream->pushData($Data);
                }else{
                    if($UserData[$value]["Device"]=="I" && Validator::checkTokenIOS($UserData[$value]["DeviceToken"]) && $UserData[$value]["PushNotification"]=="Y"){
                        //發送iOS推播訊息
                        $oPushNotifications = new PushNotifications("I");
                        $oPushNotifications->pushMsg($UserData[$value]["DeviceToken"],$RoomName,join(",",$UserNicknameArray).$_MsgContent,$_RoomID,Badge::get($value));
                        unset($oPushNotifications);
                    }
                }
            }
            //EventSource 另外通知被刪除房間的人
            if($_Method=="kickRoom"){
                //開始給串流
                $kickUserID = $_UserIDArray[0];
                $oMsgStream = new MsgStream($kickUserID);
                if($oMsgStream->isUserStream()){
                    $Data = array(
                        'msg_add_time' => $oDatetime_NOW->getTimestamp(),
                        'msg_id' => $oMsg->getInsertID(),
                        'msg_message' => $showNicknameArray?join(",",$UserNicknameArray).$_MsgContent:$_MsgContent,
                        'msg_room_id' => $_RoomID,
                        'msg_system' => $_MsgSystem,
                        'msg_user_id' => "0",
                        'room_name' => $Room["RoomName"],
                        'user_id' => "0",
                        'room_game_code'=> $Room["GameCode"],
                        //通知被刪除的ID
                        'room_del_user_id'=> $_UserIDArray[0],
                    );
//                    print_r($Data);
                    //Push一筆
                    $oMsgStream->pushData($Data);
                }else{
                    if($UserData[$kickUserID]["Device"]=="I" && Validator::checkTokenIOS($UserData[$kickUserID]["DeviceToken"]) && $UserData[$kickUserID]["PushNotification"]=="Y"){
                        //發送iOS推播訊息
                        $oPushNotifications = new PushNotifications("I");
                        $oPushNotifications->pushMsg($UserData[$kickUserID]["DeviceToken"],$RoomName,join(",",$UserNicknameArray).$_MsgContent,$_RoomID,Badge::get($kickUserID));
                        unset($oPushNotifications);
                    }
                }
            }


        }
        /***串流End***/
    }

}