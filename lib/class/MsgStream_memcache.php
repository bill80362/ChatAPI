<?php
/**
 * Chat_User_1 => Status => online/lock
 *             => Data   => array({Json訊息資料},{Json訊息資料},{Json訊息資料})
 ***/

class MsgStream_memcache
{
    static public $PreKey = "Chat_User_";//KEY的前置
    public $Key;
    public $UserID;
    public $isILock=1;//1開放 2是鎖上(我鎖上設定2)
    public $isStream = false;

    public function __construct($UserID,$isStream=false)
    {
        $this->UserID = $UserID;
        $this->Key = $this->PreKey.$UserID;
        $this->isStream = $isStream;//是否為串流使用
        $this->memcache = new Memcache; //Star memcache
        $this->memcache->connect(MEMCACHE_HOST, MEMCACHE_PORT) or die ("Could not connect"); //Connect Memcache
        //建立用戶連線中
        if($this->isStream)
            $this->memcache->set($this->Key, json_encode(array("Status"=>"online")),MEMCACHE_COMPRESSED);
    }
    //SSE用戶關閉視窗會觸發
    public function __destruct()
    {
        //用戶離線-清空
        if($this->isStream)
            $this->memcache->delete($this->Key);
        //測試用
        $oTestSSE = new TestSSE();
        $oTestSSE->create(array("title"=>"用戶:，離線","content"=>MEMCACHE_TEST));
    }
    //確認該用戶是否為連線中
    public function isUserStream(){
        $JsonData = $this->memcache->get($this->Key);
        $getValue = json_decode($JsonData,true);
        if($getValue["Status"]!="" )
            return true;
        else
            return false;
    }
    //拿資料不上鎖
    public function getDataNoLock(){
        $JsonData = $this->memcache->get($this->Key);
        return json_decode($JsonData,true);
    }
    //新增資料，看之後是否需要做上鎖
    public function pushData($_Data){
        $Timestamp_15SecBefore = time()-15;
        $JsonData = $this->memcache->get($this->Key);
        $getValue = json_decode($JsonData,true);
        $getValue["Data"][] = $_Data;//push一筆
        //刪除超過10秒以上的訊息
        foreach( $getValue["Data"] as $key=>$value ){
            if($value["msg_add_time"] < $Timestamp_15SecBefore ) {
                unset($getValue["Data"][$key]);
            }
        }
        $getValue["Data"] = array_values($getValue["Data"]);//unset後要重新排序
        $this->memcache->set($this->Key, json_encode($getValue),MEMCACHE_COMPRESSED);
    }
}