<?php
//載入Composer套件
include "/home/chat/config/config_inc.php";

//路由套件:https://github.com/nikic/FastRoute
$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {

    //使用者 user
    $r->addGroup('/user', function (FastRoute\RouteCollector $r) {
        //不需驗證Token
        $r->addRoute('POST', '/login', 'ControllerUserLogin@login');//登入
        $r->addRoute('POST', '/register', 'ControllerUserLogin@register');//註冊
        //我的設定
        $r->addRoute('GET', '/refresh_token', 'ControllerUser@refreshToken');//更新Token
        $r->addRoute('POST', '/nick', 'ControllerUser@updateNick');//修改暱稱
        //$r->addRoute('POST', '/chatid', 'ControllerUser@updateChatID');//修改chat_id
        //好友
        $r->addRoute('POST', '/friend', 'ControllerUser@addFriend');//加入好友
        $r->addRoute('GET', '/friend', 'ControllerUser@getFriend');//好友列表
        $r->addRoute('DEL', '/friend', 'ControllerUser@delFriend');//刪除好友
        $r->addRoute('GET', '/addmefriend', 'ControllerUser@addmefriend');//加我好友的列表
        //頭像
        $r->addRoute('POST', '/avatar', 'ControllerUser@getAvatar');//抓取頭像
        $r->addRoute('PUT', '/avatar', 'ControllerUser@updateAvatar');//更新頭像
        //會員中心-限制只有會員能存取
        $r->addRoute('GET', '/deposit', 'ControllerMember@getDeposit');//會員充值網址
        $r->addRoute('GET', '/withdraw', 'ControllerMember@getWithdraw');//會員提款網址
    });
    //房間群組
    $r->addGroup('/room', function (FastRoute\RouteCollector $r) {
        $r->addRoute('POST', '/room', 'ControllerRoom@createRoom');//創立房間
        $r->addRoute('PUT', '/room', 'ControllerRoom@updateRoomName');//修改房間名稱
        $r->addRoute('GET', '/room', 'ControllerRoom@getRoom');//房間清單
        $r->addRoute('PUT', '/room_push', 'ControllerRoom@updatePushNotification');//房間通知狀態
        $r->addRoute('POST', '/room_user', 'ControllerRoom@joinUser');//邀請會員入房間
        $r->addRoute('DEL', '/room_user', 'ControllerRoom@kickUser');//將會員移除房間
        $r->addRoute('POST', '/room_list', 'ControllerRoom@getUser');//房間成員清單
        //【代理】
        $r->addRoute('POST', '/room_check', 'ControllerRoomAgent@getCheckList');//【代理】審核進入下注房清單
        $r->addRoute('PUT', '/room_check', 'ControllerRoomAgent@updateCheck');//【代理】審核回覆
    });
    //訊息
    $r->addGroup('/msg', function (FastRoute\RouteCollector $r) {
        $r->addRoute('POST', '/send', 'ControllerMsg@send');//傳送訊息
        $r->addRoute('POST', '/read', 'ControllerMsg@read');//讀取歷史訊息
        $r->addRoute('POST', '/badge', 'ControllerMsg@updateBadge');//更新"未讀"數量
        $r->addRoute('GET', '/stream', 'ControllerMsg@stream');//讀取歷史訊息
    });

    //DOC pdf說明文件
    $r->addRoute('GET', '/doc.pdf', 'ControllerPDF@getMemberDoc');//GameCode列表
    $r->addRoute('GET', '/doc_agent.pdf', 'ControllerPDF@getAgentDoc');//可用盤口

});



//路由套件設定區 START
if(true){
    // 從$_SERVER取路徑(URI)和方法
    $httpMethod = $_SERVER['REQUEST_METHOD'];
    $uri = $_SERVER['REQUEST_URI'];
    // 去除query string(?foo=bar) and decode URI
    if (false !== $pos = strpos($uri, '?')) {
        $uri = substr($uri, 0, $pos);
    }
    $uri = rawurldecode($uri);
    $routeInfo = $dispatcher->dispatch($httpMethod, $uri);
    //分配路由狀態
    switch ($routeInfo[0]) {
        case FastRoute\Dispatcher::NOT_FOUND:
            //當uri路徑找不到
            header('Content-Type: application/json; charset=utf-8');
            header('HTTP/1.1 404 Not Found');
            $_JSON['code']=404;
            $_JSON['msg']="404 Not Found";
            echo json_encode($_JSON);
            break;
        case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
            $allowedMethods = $routeInfo[1];
            // 當uri路徑找到，方法不對(GET POST PUT.....)
            header('Content-Type: application/json; charset=utf-8');
            header('HTTP/1.0 405 Method Not Allowed');
            $_JSON['code']=405;
            $_JSON['msg']="405 Method Not Allowed";
            echo json_encode($_JSON);
            break;
        case FastRoute\Dispatcher::FOUND:
            //路徑、方法都對了，執行Controller
            $handler = $routeInfo[1];
            $vars = $routeInfo[2];
            //自定義$handler 第一個參數是 string class@method 第二個之後是$vars
            list($class, $method) = explode('@',$handler,2);
            $obj = new $class();//類別進行物件化
            $obj->{$method}($vars);//傳入參數
            break;
    }
}
//路由套件設定區 END





