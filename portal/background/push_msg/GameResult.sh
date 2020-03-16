#!/bin/sh
while [ true ] ; do
        sleep 3

        result=`ps aux|grep 'background/push_msg/GameResult.php' | grep -v "grep"`
        if [ ${#result} = 0 ];then
           /home/chat/portal/background/push_msg/GameResult.php &
        fi

done
