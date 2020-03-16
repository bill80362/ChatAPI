<?php
/**
 * IP檢查
 ***/

class CheckIP
{
    private $redis;
    private $oWBPortal;
    public $_ErrorMsg;
    private $_ExpireTime = 10;//暫存IP狀態秒數，預設10秒，config設定檔會取代這邊

    public function __construct()
    {
        //Redis連線
        $this->redis = new Redis();
        $this->redis->connect(REDIS_HOST, REDIS_PORT,60);
        $this->redis->select(REDIS_IP_CHECK);

        //暫存秒數
        is_int(IP_TEMP_SEC) && $this->_ExpireTime = IP_TEMP_SEC;//使用設定檔秒數

        //
        $this->oWBPortal = new WBPortal();
    }
    public function __destruct()
    {
        unset($this->redis);
    }
    //確認IP狀態
    public function getStatus($_IP){
        $_IPStatus_Temp = $this->redis->get($_IP );
        if($_IPStatus_Temp=="Y")
            return true;
        elseif($_IPStatus_Temp=="N")
            return false;
        else{
            //去WB抓IP可否使用
            $rs = $this->oWBPortal->checkIP($_IP);
            if($rs["success"] && $rs["Msg"]=="OK") {
                //連線成功，IP 允許
                $this->setStatusToTemp($_IP,"Y");
                return true;
            }elseif($rs["success"]){
                //連線成功，IP 拒絕
                $this->setStatusToTemp($_IP,"N");
                $this->_ErrorMsg = "IP Access Deny";
                return false;
            }else{
                //連線失敗
                $this->_ErrorMsg = "WB";
                return false;
            }
        }

    }
    //設定IP狀態
    public function setStatusToTemp($_IP,$Status){
        $this->redis->set( $_IP , $Status ,$this->_ExpireTime ) ;//標記
    }

}