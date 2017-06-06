<?php
/**
 * Created by PhpStorm.
 * User: flyer
 * Date: 16/12/22
 * Time: 下午2:36
 */

namespace App\Business;
use Illuminate\Http\Request;


class LogMessage
{
    public $msg = array();
    public $keys = "LogMessage";
    public function __construct($method = "",$message = array())
    {
        $res = array();
        $headerkeys = array('HTTP_USER_AGENT','REMOTE_ADDR','REMOTE_PORT','REQUEST_URI','REQUEST_TIME','POST','GET');
        foreach($_SERVER as $k=>$v){
            if(in_array($k,$headerkeys)){
                $res[$k] = $v;
            }
        }
//        $res = array(
//            'REMOTE_ADDR'=>$_SERVER['REMOTE_ADDR'],
//            'REMOTE_PORT'=> $_SERVER['REMOTE_PORT'],
//            'REQUEST_URI'=> $_SERVER['REQUEST_URI'],
//            'HTTP_USER_AGENT'=> $_SERVER['HTTP_USER_AGENT'],
//            'REQUEST_TIME'=>$_SERVER['REQUEST_TIME'],
//            'POST'=>$_POST,
//            'GET'=>$_GET,
//        );
        $this->msg = $message;
        $this->msg = array_merge($res,$this->msg);
        $this->msg = array("{LOGMESSAGE}"=>json_encode($this->msg));
//        var_dump($this->msg);
//        exit();

        $this->loginfoformat = $method." | {LOGMESSAGE}";
    }
    //[2016-12-22 06:58:56][local][INFO][App\Http\Controllers\FeiMiController::getFeiMiByUnionid][{"DOCUMENT_ROOT":"/Users/flyer/Work/feimi/public","REMOTE_ADDR":"::1","REMOTE_PORT":"55311","SERVER_SOFTWARE":"PHP 7.0.14 Development Server","SERVER_PROTOCOL":"HTTP/1.1","SERVER_NAME":"localhost","SERVER_PORT":"8000","REQUEST_URI":"/api/feimi/545?appkey=373320adea80df01&token=MHwxNDgyMjUxNDM5fDk5MDMxNzNiOTU3NjE5MDg3N2U4ZGM0YWM1NjBmYTYxZTBlYTk5MzgwZjVlM2QyM2IzYTUwN2M2M2VhMzQ4YzA=","REQUEST_METHOD":"GET","SCRIPT_NAME":"/index.php","SCRIPT_FILENAME":"/Users/flyer/Work/feimi/public/index.php","PATH_INFO":"/api/feimi/545","PHP_SELF":"/index.php/api/feimi/545","QUERY_STRING":"appkey=373320adea80df01&token=MHwxNDgyMjUxNDM5fDk5MDMxNzNiOTU3NjE5MDg3N2U4ZGM0YWM1NjBmYTYxZTBlYTk5MzgwZjVlM2QyM2IzYTUwN2M2M2VhMzQ4YzA=","HTTP_HOST":"localhost:8000","HTTP_CONNECTION":"keep-alive","HTTP_CACHE_CONTROL":"no-cache","HTTP_USER_AGENT":"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.95 Safari/537.36","HTTP_POSTMAN_TOKEN":"2d0d8404-05cd-1942-fed3-d90e63793da1","HTTP_ACCEPT":"*/*","HTTP_ACCEPT_ENCODING":"gzip, deflate, sdch, br","HTTP_ACCEPT_LANGUAGE":"zh-CN,zh;q=0.8,en;q=0.6","REQUEST_TIME_FLOAT":1482389936.345,"REQUEST_TIME":1482389936,"APP_LOG_PATH":"/Users/flyer/Work/feimi/storage/monolog/feimi","MYSQL_DB_CONNECTION":"mysql","MYSQL_DB_HOST":"121.40.179.217","MYSQL_DB_PORT":"3306","MYSQL_DB_DATABASE":"feimi","MYSQL_DB_USERNAME":"feimi","MYSQL_DB_PASSWORD":"feimi@test"}][]
    public  function message(){
        return $this->msg;

    }
    public function logformat(){
        return $this->loginfoformat;

    }

}