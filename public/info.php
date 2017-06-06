<?php
/**
 * Created by PhpStorm.
 * User: flyer
 * Date: 16/12/24
 * Time: 下午4:35
 */
use App\Business\LogMessage;
//echo phpinfo();
//$lastLogger_1 = SeasLog::getLastLogger();
var_dump(SEASLOG_DEBUG,SEASLOG_INFO,SEASLOG_NOTICE);


SeasLog::setLogger('fiemi/feimi_api');

$lastLogger_2 = SeasLog::getLastLogger();
$logmsg = new LogMessage(array("hi"=>"hihihii"));
SeasLog::info("this is a info log");
SeasLog::info($logmsg);



