<?php

class PushNotifications_iOS
{
    public $ctx;
    public $fp;

    public function __construct($_Sandbox = true,$_SSL_KeyFile = "QChat_Push.pem"){
        //Config
        if($_Sandbox){//Test
            $passphrase = '0127';
            $ios_uri = "ssl://gateway.sandbox.push.apple.com:2195";
        }else{//正式
            $passphrase = '0127';
            $ios_uri = "ssl://gateway.push.apple.com:2195";
        }
        //Link
        $this->ctx = stream_context_create();
        stream_context_set_option($this->ctx, 'ssl', 'local_cert', $_SSL_KeyFile);//記得把生成的push.pem放在和這個php文件同一個目錄
        stream_context_set_option($this->ctx, 'ssl', 'passphrase', $passphrase);
        //這裡需要特別注意，一個是開發推送的沙箱環境，一個是發布推送的正式環境，deviceToken是不通用的
        $this->fp = stream_socket_client($ios_uri, $err, $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $this->ctx);
        if (!$this->fp)
            throw new Exception ("ios连线有误",500);
    }
    public function __destruct(){
        fclose($this->fp);
    }
    public function pushMsg($_DeviceToken,$_Title,$_Msg,$_RoomID,$_Badge){
        // 定制推送內容，有一點的格式要求，詳情Apple文檔
        $message = array(
            'title'=> $_Title,//顯示主題
            'body'=>$_Msg , //顯示內容
        );
        $body['aps'] = array(
            'alert' => $message,
            'sound' => 'default',
            'badge' => $_Badge,
        );
//        $body['type']=3;
//        $body['msg_type']=4;
//        $body['title']='新訊息提醒';
//        $body['msg']='收到一則新訊息';
        $body["roomID"] = $_RoomID;

        //開始傳送
        $payload = json_encode($body);
        $msg = chr(0) . pack('n', 32) . pack('H*', $_DeviceToken) . pack('n', strlen($payload)) . $payload;
        $result = fwrite($this->fp, $msg, strlen($msg));
//        if (!$result)
//            echo 'Message not delivered' . PHP_EOL;
//        else
//            echo 'Message successfully delivered' . PHP_EOL;
    }
}