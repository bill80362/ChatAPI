<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2019/12/5
 * Time: 上午 11:15
 */

class Room extends DBModel
{
    static public $RoomType = array("S","M","G");//單人房,多人房
    static public $GameRoomStatus = array("W","Y");//等待審核中、審核通過
}