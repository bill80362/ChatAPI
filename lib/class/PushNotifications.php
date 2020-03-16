<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2020/2/3
 * Time: 下午 06:00
 */

class PushNotifications
{
    public $oPushNotifications;
    public function __construct($_Device){
        if($_Device=="I") {
            $this->oPushNotifications = new PushNotifications_iOS(iOS_SandBox, iOS_PEM_PATH );
        }elseif($_Device=="A"){
            exit("Error...");
        }else{
            exit("Error...");
        }
    }
    public function pushMsg($_DeviceToken,$_Title,$_Msg,$_RoomID,$_Badge){
        $this->oPushNotifications->pushMsg($_DeviceToken,$_Title,$_Msg,$_RoomID,$_Badge);
    }
}