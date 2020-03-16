<?php

use Respect\Validation\Exceptions\ValidationException;

class ResData
{
    static public function success($_JSON){
        $_JSON['code']=200;
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($_JSON);
        exit;
    }
    static public function fail($_JSON,$error_code=500){
        $_JSON['code']=$error_code;
        $_JSON['success']=false;
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($_JSON);
        exit;
    }
    static public function failException(Exception $e){
        $_JSON['code']=$e->getCode();
        $_JSON['msg']=$e->getMessage();
        $_JSON['dev_error_detail']=$e->getTraceAsString();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($_JSON);
        exit;
    }
    //資料驗證錯誤
    static public function failValException(ValidationException $e){
        $_JSON['code']=400;
        $_JSON['msg']=$e->getMessage();
        $_JSON['dev_error_detail']=$e->getTraceAsString();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($_JSON);
        exit;
    }

}