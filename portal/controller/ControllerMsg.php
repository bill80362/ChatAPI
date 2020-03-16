<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2020/2/18
 * Time: 上午 11:00
 */

class ControllerMsg
{
    public function __construct(){
        Middleware::checkClientIP();//可在Config確認是否打開功能
        Middleware::ValBearerToken();//驗證Token，取的UserID
        Middleware::getReqJsonData();//取得JSON參數
    }

    public function send(){
        //Middle Var
        $UserID = Middleware::$UserID;
        $ReqData = Middleware::$ReqData;
        //驗證資料
        Validator::checkRoomID($ReqData["msg_room_id"]);
        Validator::checkMsgContent($ReqData["msg_message"]);
        //檢查房間是否存在，會員是否存在該房間

        //新增訊息
        $oDatetime_NOW = new DateTime();
        $oMsg = new Msg();
        $Data = array();
        $Data["UserID"] = $UserID;
        $Data["RoomID"] = $ReqData["msg_room_id"];
        $Data["MsgContent"] = $ReqData["msg_message"];
        $Data["NewTime"] = $oDatetime_NOW->format("Y-m-d H:i:s");
        if($oMsg->create($Data)){
            $InsertID =  $oMsg->getInsertID();
            /***串流Start，通知房間內所有連線中的用戶***/
            //該房間會員數量
            $oRoom2User = new Room2User();
            $Room2UserData = $oRoom2User->getList("RoomID=".$ReqData["msg_room_id"]." AND Status='Y' ");
            $UserIDList = array_column($Room2UserData,"UserID");
            //房間會員的推播狀態是否開啟
            foreach ($Room2UserData as $value){
                $UserData[$value["UserID"]]["PushNotification"] = $value["PushNotification"];
            }
            //抓會員的裝置跟推播資料
            $oUser = new User();
            $UserDataList = $oUser->getListbyID($UserIDList);
            foreach ($UserDataList as $value){
                $UserData[$value["UserID"]]["Device"] = $value["Device"];
                $UserData[$value["UserID"]]["DeviceToken"] = $value["DeviceToken"];
            }
            //房間名稱
            $oRoom = new Room();
            $Room = $oRoom->find($ReqData["msg_room_id"]);
            if($Room["RoomType"]=="S"){
                //抓出好友ID
                foreach ($UserIDList as $v){
                    if($v!=$UserID){
                        $FriendID = $v;
                        break;
                    }
                }
                //抓好友暱稱
                $oUser = new User();
                $Friend = $oUser->find($FriendID);
                $RoomName = $Friend["Nickname"];
            }else{
                //多人房間直接使用RoomName
                $RoomName = $Room["RoomName"];
            }
            //處理串流資料
            foreach ($UserIDList as $value){
                $oMsgStream = new MsgStream($value);
                if($oMsgStream->isUserStream()){
                    //先抓出需要的資料
                    $oUser = new User();
                    $User = $oUser->find($UserID);
                    $oRoom = new Room();
                    $Room = $oRoom->find($ReqData["msg_room_id"]);
                    //開始給串流
                    $Data = array(
                        'message' => $ReqData["msg_message"],
                        'msg_add_time' => $oDatetime_NOW->getTimestamp(),
                        'msg_id' => $InsertID,
                        'msg_message' => $ReqData["msg_message"],
                        'msg_room_id' => $ReqData["msg_room_id"],
                        'msg_system' => 0,
                        'msg_user_id' => $UserID,
                        'nick' => $User["Nickname"],
                        'room_name' => $Room["RoomName"],
                        'user_id' => $UserID,
                    );
                    //Push一筆
                    $oMsgStream->pushData($Data);
                }else{
                    if($UserData[$value]["Device"]=="I" && Validator::checkTokenIOS($UserData[$value]["DeviceToken"]) && $UserData[$value]["PushNotification"]=="Y" ){
                        //發送iOS推播訊息
                        $oPushNotifications = new PushNotifications("I");
                        $oPushNotifications->pushMsg($UserData[$value]["DeviceToken"],$RoomName,$ReqData["msg_message"],$ReqData["msg_room_id"],Badge::get($value));
                        unset($oPushNotifications);
                    }
                }
            }
            /***串流End***/
            //回應
            $ResData['msg_add_time']=$oDatetime_NOW->getTimestamp();
            $ResData['msg_id']=$InsertID;
            $ResData['msg_message']=$ReqData["msg_message"];
            $ResData['msg_room_id']=$ReqData["msg_room_id"];
            $ResData['msg_user_id']=$UserID;
            ResData::success($ResData);
        }

    }
    public function read(){
        //Middle Var
        $UserID = Middleware::$UserID;
        $ReqData = Middleware::$ReqData;
        //驗證資料
        Validator::checkMsgRead($ReqData);
        foreach ( $ReqData as $value ){
            Validator::checkRoomID($value["msg_room_id"]);
            Validator::checkMsgID($value["msg_id"]);
        }
        //將POST資料整理成SQL_WHERE
        $WHERE_SQL = array();
        $RoomIDList = array();
        foreach ( $ReqData as $value ){
            $RoomIDList[] = (int)$value["msg_room_id"];
            $WHERE_SQL[] = " ( RoomID=".(int)$value["msg_room_id"]." AND MsgID>".(int)$value["msg_id"]." ) ";
        }
        //檢查會員是否存在該房間
        $oRoom2User = new Room2User();
        $UserInRoomID = $oRoom2User->getList("UserID=".$UserID." AND Status='Y' ");//會員有在的房間
        $UserInRoomID = array_column($UserInRoomID,"RoomID");
        //取得新的訊息列表
        $oMsg = new Msg();
        $MsgList = $oMsg->getList(" 1=1 AND ( ".join(" OR ",$WHERE_SQL)." ) ");
        //回應
        $ResData["data"] = array();
        foreach ($MsgList as $value){

            $MsgTypeFlip = array_flip(Msg::$MsgType);
            $msg_system = $MsgTypeFlip[$value["MsgType"]];

            //Data(如果會員本身不再房內，不給資料)
            if( in_array($value["RoomID"],$UserInRoomID) ){
                $ResData["data"][] = array(
                    "msg_id"=> $value["MsgID"],
                    "msg_user_id"=> $value["UserID"],
                    "msg_room_id"=> $value["RoomID"],
                    "msg_system"=> $msg_system,
                    "msg_add_time"=> strtotime($value["NewTime"]),
                    //"msg_message"=> urlencode($value["MsgContent"]),
                    "msg_message"=> $value["MsgContent"],
                );
            }

        }
        ResData::success($ResData);
    }
    public function updateBadge(){
        //Middle Var
        $UserID = Middleware::$UserID;
        $ReqData = Middleware::$ReqData;
        //驗證資料
        Validator::checkInt($ReqData["badge"],"badge");
        //設定
        Badge::set($UserID,$ReqData["badge"]);
        //回應
        ResData::success(array("data"=>$ReqData));
    }
    public function stream(){
        ignore_user_abort(true);
        //ini_set('max_execution_time', 0);
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        //Middle Var
        $UserID = Middleware::$UserID;
        //$UserID = 14; //測試用
        $oMsgStream = new MsgStream($UserID,true,Middleware::$Token);
        //傳連線成功訊息
        echo "id:".$UserID." \n";
        echo "event: message\n";
        echo "data: " . json_encode(array("Link"=>"connected"));
        echo "\n\n";
        // 讓迴圈無限執行
        for($i=0;$i<600;$i++) {
            $PopData = $oMsgStream->getDataNoLock();
            if(count($PopData)>0){
                //將資料內容編碼json傳送
                echo "id:".$UserID." \n";
                echo "event: message\n";
                echo "data: " . json_encode($PopData);
                echo "\n\n";
            }else{
                //持續連線中
                echo "id:".$UserID." \n";
                echo "event: link\n";//改成非message讓client辨識為無用訊息
                echo "data: " . json_encode(array("Link"=>"Linking..."));
                echo "\n\n";
            }
            //輸出暫存
            ob_flush();
            flush();
            //偵測使用者關閉連線(前面一定要有輸出才能偵測)
            if(connection_aborted()){
                exit();
            }
            // 控制睡眠多久再執行（秒）
            sleep(1);
        }
    }

}