<?php

class ControllerPDF{
    public function getMemberDoc(){
        $name = SITE_PATH.'/portal/www/doc/doc.pdf';
        //file_get_contents is standard function
        $content = file_get_contents($name);
        header('Content-Type: application/pdf');
        header('Content-Length: '.strlen( $content ));
        header('Content-disposition: inline; filename="' . $name . '"');
        header('Cache-Control: public, must-revalidate, max-age=0');
        header('Pragma: public');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
        echo $content;
    }
    public function getAgentDoc(){
        $name = SITE_PATH.'/portal/www/doc/doc_agent.pdf';
        //file_get_contents is standard function
        $content = file_get_contents($name);
        header('Content-Type: application/pdf');
        header('Content-Length: '.strlen( $content ));
        header('Content-disposition: inline; filename="' . $name . '"');
        header('Cache-Control: public, must-revalidate, max-age=0');
        header('Pragma: public');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
        echo $content;
    }
}