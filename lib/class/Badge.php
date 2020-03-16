<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2020/2/5
 * Time: 下午 05:39
 */

class Badge
{
    public static function set($UserID,$Num)
    {
        //Redis連線
        $redis = new Redis();
        $redis->connect(REDIS_HOST, REDIS_PORT,60);
        $redis->select(REDIS_DB_INDEX);
        //set
        $redis->hset( 'badge' , $UserID , $Num ) ;

    }
    public static function get($UserID)
    {
        //Redis連線
        $redis = new Redis();
        $redis->connect(REDIS_HOST, REDIS_PORT,60);
        $redis->select(REDIS_DB_INDEX);
        //get
        $BadgeNum = $redis->hget('badge',$UserID );
        if(!$BadgeNum) $BadgeNum = 0;
        //set
        $redis->hset( 'badge' , $UserID , ++$BadgeNum ) ;
        //return
        return $BadgeNum;
    }
}