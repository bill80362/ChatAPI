<?php

class UserAvatar
{
    //Image轉成Base64
    static public function Image_to_Base64($_Path){
        $type = pathinfo($_Path, PATHINFO_EXTENSION);
        $data = file_get_contents($_Path);
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        return $base64;
    }

    //Base64轉成Image
    static public function Base64_to_Image($_Base64String,$_Path){
        list($type, $data) = explode(';', $_Base64String);
        list(, $data)      = explode(',', $data);
        $data = base64_decode($data);
        file_put_contents($_Path, $data);
        return true;
    }

}