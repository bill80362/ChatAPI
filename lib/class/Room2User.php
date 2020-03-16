<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2019/12/5
 * Time: 上午 11:16
 */

class Room2User extends DBModel
{
    //審核狀態
    public static $Status = array("W","Y","N");

    //User共同在的房間id 回傳房間id陣列
    public function getUserInSameRoom($UserArray){
        $UserCount = count($UserArray);//User數量
        if($UserCount<=1){
            return false;//User數量一定要兩人以上
        }
        $DataList = $this->getListbyID($UserArray,"UserID","Status='Y'");
        $RoomIDList = array_column($DataList,"RoomID");
        $SameRoomIDArrayCount = array_count_values($RoomIDList);//相同的值數量統計 RoomID => count
        $SameRoomIDList = array();
        foreach ($SameRoomIDArrayCount as $key => $value) {
            if($value>=$UserCount){
                $SameRoomIDList[] = $key;//全部人都在該房間的ID列表
            }
        }
        return $SameRoomIDList;
    }
}