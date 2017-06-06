<?php

namespace App\Http\Controllers;
use App\Business\BizError;
use Illuminate\Http\Request;

use App\Business\BizResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

use App\Models\FeimiChannel;
use App\Models\FeimiOperation;
use App\Business\LogMessage;
use NinjaMutex\Mutex;
use NinjaMutex\Lock\FlockLock;
use SeasLog;
SeasLog::setLogger('feimi/feimi_api');
use Symfony\Component\VarDumper\Caster\ExceptionCaster;

class FeiMiController extends Controller
{
    function test($uid){
        return array("msg"=>"Welcome to laravel world 1 ".$uid);
    }
    function test2($uid){
        return array("msg"=>"Welcome to laravel world 2 ".$uid);
    }
    function geifeimiapitest(Request $request){

        $logmsg = new LogMessage(__METHOD__,BizResponse::successResponseArray(array("test"=>"hihihihi")));

//
        SeasLog::info($logmsg->logformat(),$logmsg->message());
        $logmsg = new LogMessage(__METHOD__,BizResponse::failureResponseArray(array("test"=>"hihihihi")));
        SeasLog::error($logmsg->logformat(),$logmsg->message());
        SeasLog::debug($logmsg->logformat(),$logmsg->message());
        SeasLog::warning($logmsg->logformat(),$logmsg->message());
        SeasLog::critical($logmsg->logformat(),$logmsg->message());
//        exit();
//        SeasLog::info('INFO | {HTTP_USER_AGENT} | {REQUEST_TIME} | {POST} | {GET} | {MESSAGE}',$logmsg->message());

        return BizResponse::successResponse(array('res' =>$logmsg->logformat() , "res1" => $logmsg->message()));



//        $results = DB::select('select * from pre_feimi_channel where channelid > :id', ['id' => 0]);
        $results1 = FeimiChannel::all();
        $results2 = FeimiOperation::all();
        $extra = array(
            'favoriteFood' => 'ORANGE CARRATS',
            'bestFriend' => 'DAFFT DUCK'
        );

        $extra1 = array(
            'favoriteFoodError' => 'ORANGE CARRATS',
            'bestFriendError' => 'DAFFT DUCK'
        );
        $msg = new LogMessage($extra);
        $extra = $msg->message();
        $msg1 =new  LogMessage($extra1);
        $extra1 = $msg1->message();

        Log::info(__METHOD__,$extra);
        Log::error(__METHOD__,$extra1);
        Log::warning(__METHOD__,$extra1);
        Log::debug(__METHOD__,$extra1);
//        $m = new Memcached();
//        $m->addServer('localhost', 11211);
//        $m->set('test','test1232');
//        $res = $m->get('test');
//        $m->deleteByKey('test',0);
        $result = array();
        $path = '/tmp/';
        $lock = new FlockLock($path);
        $lockfile="feimi_".$uid.'.lock';
//        $lockName = "feimi_user_op_lock_".$uid;
//        $lockFile = $path.$lockName;
        $mutex = new Mutex($lockfile,$lock);
        if ($mutex->acquireLock()) { //检查文件是否存在
//            touch($lockName);
            $result ["t1"] = time() * 1000;
//            $m->add($lockName,time()*1000);
            $result['lock'] = "Get Lock";
            sleep(2);
            $result ["t2"] = time() * 1000;
            $mutex->releaseLock();
            return BizResponse::successResponse(array('res' => $result, "res1" => $results1, 'res2' => $results2));
        }else{

        }


    }

    function getFeimiByUnionid(Request $request,$uid){
        $res = DB::table('feimi_user')->where('unionid','=',intval($uid))->first();
        $userfeimi = array(
            'unionid'=> $uid,
            'feimi'=>0
        );
        if($res){
            $userfeimi['feimi'] = $res->totalnum;
        }
        $logmsg = new LogMessage(__METHOD__,BizResponse::successResponseArray(array('operations'=>$userfeimi)));
        SeasLog::debug($logmsg->logformat(),$logmsg->message());
        return BizResponse::successResponse($userfeimi);
    }




    function getFeimiChannels(Request $request){
        $res = DB::table('feimi_channel')->select('channelid','channelname')->get();
        $channels = array();
        foreach($res as $c){
            $ac = json_decode(json_encode($c),true);
            if($ac){
                $channels[] = $ac;
            }
        }
        return BizResponse::successResponse(array("channels"=>$channels));
    }


    function getFeimiOperationsTypes(Request $request){
        $res = DB::table('feimi_operation_type')->select('optypename','optype')->get();
        $types = array();
        foreach($res as $t){
            $at = json_decode(json_encode($t),true);
            if($at){
                $types[] = $at;
            }

        }
        return BizResponse::successResponse(array("types"=>$types));

    }

    function getFeimiOperations(Request $request){
        $data =  $request->headers->all();
//        var_dump($data);
//        exit();
        $wheres = array();
        $page = 1;
        $pagesize = 10;
        if(array_key_exists('page',$data)) {
            $page = intval($data['page'][0]);
        }


        //行为飞米充值,飞米奖励 101 102
        if(array_key_exists('op',$data)){
            if(is_numeric($data['op'][0])){
                $wheres['opcode'] = intval($data['op'][0]);
            }else{
                $wheres['opname'] = urldecode($data['op'][0]);
            }
        }
        //操作渠道 id 1 飞客茶馆 2 飞客旅行 3 飞米平台 4 飞米商城
        if(array_key_exists('channelid',$data)) {
            $wheres['channelid'] = $data['channelid'][0];
        }

        // 0 正常 1 禁止
        if(array_key_exists('status',$data)) {
            $wheres['status'] = $data['status'][0];
        }

        //操作类型id 1 飞米产出 2 飞米消耗 3 飞米过期 4 飞米回滚
        if(array_key_exists('optype',$data)) {
            $wheres['optype'] = $data['optype'][0];
        }
//        var_dump($wheres);
//        exit();
        $offset = $pagesize*($page -1 );
        $count = 0;
        $pagenum = 0;
//        if ($page == 1){
        $count = DB::table('feimi_channel_operation')->where($wheres)->count();
        $pagenum = ceil($count/$pagesize);
//        }

        $res = DB::table('feimi_channel_operation')->where($wheres)->offset($offset)->limit($pagesize)->get();
//        var_dump($res);
//        exit();
        $operations = array();
        foreach($res as $o){
            if ($o){
                $operations[] = json_decode(json_encode($o),true);
            }
        }
        return BizResponse::successResponse(array('operations'=>$operations,'page'=>$page,'pagesize'=>$pagesize,"pagenum"=>$pagenum,'count'=>$count));
    }

    function getFeimiOutput(Request $request){
        $data =  $request->headers->all();
//        var_dump($data);
//        exit();
//consumeid opcode channelid unionid operatorid operator value valuebefore valueafter status rollbackvalue rollbackvalueleft create_at update_at

        $wheres = array();
        //行为飞米充值,飞米奖励 101 102
        if(array_key_exists('consumeid',$data)){
            $wheres['opcode'] = intval($data['consumeid'][0]);
        }
        //操作渠道 id 1 飞客茶馆 2 飞客旅行 3 飞米平台 4 飞米商城
        if(array_key_exists('channelid',$data)) {
            $wheres['channelid'] = $data['channelid'][0];
        }

        // 0 正常 1 禁止
        if(array_key_exists('status',$data)) {
            $wheres['status'] = $data['status'][0];
        }

        //操作类型id 1 飞米产出 2 飞米消耗 3 飞米过期 4 飞米回滚
        if(array_key_exists('optype',$data)) {
            $wheres['optype'] = $data['optype'][0];
        }
//        var_dump($wheres);
//        exit();
        $res = DB::table('feimi_channel_operation')->where($wheres)->get();
//        var_dump($res);
//        exit();
        $operations = array();
        foreach($res as $o){
            if ($o){
                $operations[] = json_decode(json_encode($o),true);
            }
        }
        $logmsg = new LogMessage(__METHOD__,BizResponse::successResponseArray(array('operations'=>$operations)));
        SeasLog::debug($logmsg->logformat(),$logmsg->message());
        return BizResponse::successResponse(array('operations'=>$operations));
    }

    function getFeimiConsume(Request $request){
        $data =  $request->headers->all();
        $log_wheres = array();
        $dispatch_wheres = array();
        //消耗编号
        if(array_key_exists('consumeid',$data)){
            $log_wheres[] = array('feimi_consume_log.consumeid','=', intval($data['consumeid'][0]));
        }
        //消耗行为code
        if(array_key_exists('consumeopcode',$data)){
            $log_wheres[] = array('feimi_consume_log.opcode','=', intval($data['consumeopcode'][0]));
        }
        //消耗行为渠道 id 1 飞客茶馆 2 飞客旅行 3 飞米平台 4 飞米商城
        if(array_key_exists('consumechannelid',$data)){
            $log_wheres[] = array('feimi_consume_log.channelid','=', intval($data['consumechannelid'][0]));
        }
        //产出行为code
        if(array_key_exists('outputopcode',$data)){
            $dispatch_wheres[] = array('feimi_consume_dispatch_log.outputopcode', '=', intval($data['outputopcode'][0]));
        }
        //产出行为渠道 1 飞客茶馆 2 飞客旅行 3 飞米平台 4 飞米商城
        if(array_key_exists('outputchannelid',$data)){
            $dispatch_wheres[] = array('feimi_consume_dispatch_log.outputchannelid', '=', intval($data['outputchannelid'][0]));
        }
        //查询开始时间
        if(array_key_exists('starttime',$data)){
            $log_wheres[] = array('feimi_consume_log.create_at','>=', intval($data['starttime'][0]));
        }
        //查询结束时间
        if(array_key_exists('endtime',$data)){
            $log_wheres[] = array('feimi_consume_log.create_at','<', intval($data['endtime'][0]));
        }

        $channels = DB::table('feimi_channel')->select('channelid','channelname')->get();
        $carray = array();
        foreach ($channels as $channel) {
            $carray[$channel->channelid] = $channel->channelname;
        }
        $operations = DB::table('feimi_operation')->select('opcode','opname')->get();
        $oarray = array();
        foreach($operations as $operation){
            $oarray[$operation->opcode] = $operation->opname;
        }
        $page = 1;
        $pagesize = 10;
        if(array_key_exists('page',$data)) {
            $page = intval($data['page'][0]);
        }
        $offset = $pagesize*($page -1 );
        $count = 0;
        $pagenum = 0;
//        if ($page == 1){
        $count = DB::table('feimi_consume_dispatch_log')
            ->join('feimi_consume_log', function($join) use($log_wheres,$dispatch_wheres)
            {
                $join->on('feimi_consume_log.consumeid', '=', 'feimi_consume_dispatch_log.consumeid')
                    ->where($log_wheres)
                    ->where($dispatch_wheres);
            })
            ->count();
        $pagenum = ceil($count/$pagesize);
//        }
        $res = DB::table('feimi_consume_dispatch_log')
            ->join('feimi_consume_log', function($join) use($log_wheres,$dispatch_wheres)
            {
                $join->on('feimi_consume_log.consumeid', '=', 'feimi_consume_dispatch_log.consumeid')
                    ->where($log_wheres)
                    ->where($dispatch_wheres);
            })
            ->select('feimi_consume_log.consumeid',
                'feimi_consume_dispatch_log.dispatchid',
                'feimi_consume_log.create_at',
                'feimi_consume_log.opcode as consumeopcode',
                'feimi_consume_log.channelid as consumechannleid',
                'feimi_consume_log.value as consumevalue',
                'feimi_consume_log.rollbackvalue as rollbackvalue',
                'feimi_consume_log.rollbackvalueleft as rollbackvalueleft',
                'feimi_consume_dispatch_log.outputid',
                'feimi_consume_dispatch_log.outputopcode',
                'feimi_consume_dispatch_log.outputchannelid',
                'feimi_consume_dispatch_log.valueconsumed',
                'feimi_consume_dispatch_log.dispatchid',
                'feimi_consume_dispatch_log.outputid'
            )
            ->offset($offset)
            ->limit($pagesize)
            ->get();
        $consumes = array();
        foreach($res as $re){
            $re = json_decode(json_encode($re),true);
            $re['outputopname'] = $oarray[$re['outputopcode']];
            $re['consumeopname'] = $oarray[$re['consumeopcode']];
            $re['outputchannelname'] = $carray[$re['outputchannelid']];
            $re['consumechannelname'] = $carray[$re['consumechannleid']];
            $consumes[] = $re;
        }
//        var_dump($res);
//        exit();
        $logmsg = new LogMessage(__METHOD__,BizResponse::successResponseArray(array('consumes'=>$consumes,'page'=>$page,'pagesize'=>$pagesize,"pagenum"=>$pagenum,'count'=>$count)));
        SeasLog::debug($logmsg->logformat(),$logmsg->message());
        return BizResponse::successResponse(array('consumes'=>$consumes,'page'=>$page,'pagesize'=>$pagesize,"pagenum"=>$pagenum,'count'=>$count));
    }


    //飞米回收产出关系列表
    public function getFeimiRecycle(Request $request){
        $data =  $request->headers->all();
        $output_wheres = array();
        $log_wheres = array();
        //回收编号
        if(array_key_exists('recycleid',$data)){
            $log_wheres[] = array('feimi_recycle_log.recycleid','=', intval($data['recycleid'][0]));
        }
        //回收行为code
        if(array_key_exists('recycleopcode',$data)){
            $log_wheres[] = array('feimi_recycle_log.opcode','=', intval($data['recycleopcode'][0]));
        }
        //回收行为渠道 id 1 飞客茶馆 2 飞客旅行 3 飞米平台 4 飞米商城
        if(array_key_exists('recyclechannelid',$data)){
            $log_wheres[] = array('feimi_recycle_log.channelid','=', intval($data['recyclechannelid'][0]));
        }
        //产出编号
        if(array_key_exists('outputid',$data)){
            $log_wheres[] = array('feimi_recycle_log.outputid','=', intval($data['outputid'][0]));
        }
        //产出行为code
        if(array_key_exists('outputopcode',$data)){
            $output_wheres[] = array('feimi_output_log.opcode', '=', intval($data['outputopcode'][0]));
        }
        //产出行为渠道 1 飞客茶馆 2 飞客旅行 3 飞米平台 4 飞米商城
        if(array_key_exists('outputchannelid',$data)){
            $output_wheres[] = array('feimi_output_log.channelid', '=', intval($data['outputchannelid'][0]));
        }
        //查询开始时间
        if(array_key_exists('starttime',$data)){
            $log_wheres[] = array('feimi_recycle_log.create_at','>=', intval($data['starttime'][0]));
        }
        //查询结束时间
        if(array_key_exists('endtime',$data)){
            $log_wheres[] = array('feimi_recycle_log.create_at','<', intval($data['endtime'][0]));
        }

        $channels = DB::table('feimi_channel')->select('channelid','channelname')->get();
        $carray = array();
        foreach ($channels as $channel) {
            $carray[$channel->channelid] = $channel->channelname;
        }
        $operations = DB::table('feimi_operation')->select('opcode','opname')->get();
        $oarray = array();
        foreach($operations as $operation){
            $oarray[$operation->opcode] = $operation->opname;
        }

        $outputs = DB::table('feimi_output_log')->select('outputid','opcode','leftvalue')->get();
        $ouarray = array();
        //遍历所有用户的产出信息
        foreach($outputs as $output){
            $ouarray[$output->outputid] = $output->leftvalue;
        }

        $page = 1;
        $pagesize = 10;
        if(array_key_exists('page',$data)) {
            $page = intval($data['page'][0]);
        }
        $offset = $pagesize*($page -1 );
        $count = 0;
        $pagenum = 0;
        $count = DB::table('feimi_recycle_log')
            ->join('feimi_output_log', function($join) use($log_wheres,$output_wheres)
            {
                $join->on('feimi_output_log.outputid', '=', 'feimi_recycle_log.outputid')
                    ->where($log_wheres)
                    ->where($output_wheres);
            })
            ->count();
        $pagenum = ceil($count/$pagesize);
        $res = DB::table('feimi_recycle_log')
            ->join('feimi_output_log', function($join) use($log_wheres,$output_wheres)
            {
                $join->on('feimi_output_log.outputid', '=', 'feimi_recycle_log.outputid')
                    ->where($log_wheres)
                    ->where($output_wheres)
                    ->orderby('feimi_recycle_log.create_at','desc');
            })
            ->select('feimi_output_log.opcode',
                'feimi_output_log.channelid',
                'feimi_recycle_log.recycleid',
                'feimi_recycle_log.outputid',
                'feimi_recycle_log.opcode as recycleopcode',
                'feimi_recycle_log.channelid as recyclechannleid',
                'feimi_recycle_log.value',
                'feimi_recycle_log.remark',
                'feimi_recycle_log.reason',
                'feimi_recycle_log.create_at'
            )
            ->offset($offset)
            ->limit($pagesize)
            ->get();

        $recycles = array();
        foreach($res as $re){
            $re = json_decode(json_encode($re),true);
            $re['outputopname'] = $oarray[$re['opcode']];
            $re['recycleopname'] = $oarray[$re['recycleopcode']];
            $re['outputchannelname'] = $carray[$re['channelid']];
            $re['recyclechannelname'] = $carray[$re['recyclechannleid']];
            $re['sharevalue'] = $ouarray[$re['outputid']];
            $recycles[] = $re;
        }
        $logmsg = new LogMessage(__METHOD__,BizResponse::successResponseArray(array('recycles'=>$recycles,'page'=>$page,'pagesize'=>$pagesize,"pagenum"=>$pagenum,'count'=>$count)));
        SeasLog::debug($logmsg->logformat(),$logmsg->message());
        return BizResponse::successResponse(array('recycles'=>$recycles,'page'=>$page,'pagesize'=>$pagesize,"pagenum"=>$pagenum,'count'=>$count));
    }

    function getFeimiRollback(Request $request){
        $data =  $request->headers->all();
//        var_dump($data);
//        exit();
        $log_wheres = array();
        //回滚编号
        if(array_key_exists('rollbackid',$data)){
            $log_wheres[] = array('feimi_rollback_dispatch_log.rollbackid','=', intval($data['rollbackid'][0]));
        }
        //回滚行为code
        if(array_key_exists('rollbackopcode',$data)){
            $log_wheres[] = array('feimi_rollback_dispatch_log.opcode','=', intval($data['rollbackopcode'][0]));
        }
        //消耗行为渠道 id 1 飞客茶馆 2 飞客旅行 3 飞米平台 4 飞米商城
        if(array_key_exists('rollbackchannelid',$data)){
            $log_wheres[] = array('feimi_rollback_dispatch_log.channelid','=', intval($data['rollbackchannelid'][0]));
        }
        //消耗编号
        if(array_key_exists('consumeid',$data)){
            $log_wheres[] = array('feimi_rollback_dispatch_log.consumeid','=', intval($data['consumeid'][0]));
        }
        //产出编号
        if(array_key_exists('outputid',$data)){
            $log_wheres[] = array('feimi_rollback_dispatch_log.outputid','=', intval($data['outputid'][0]));
        }
        //产出行为code
        if(array_key_exists('outputopcode',$data)){
            $log_wheres[] = array('feimi_rollback_dispatch_log.outputopcode', '=', intval($data['outputopcode'][0]));
        }
        //产出行为渠道 1 飞客茶馆 2 飞客旅行 3 飞米平台 4 飞米商城
        if(array_key_exists('outputchannelid',$data)){
            $log_wheres[] = array('feimi_rollback_dispatch_log.outputchannelid', '=', intval($data['outputchannelid'][0]));
        }
        //查询开始时间
        if(array_key_exists('starttime',$data)){
            $log_wheres[] = array('feimi_rollback_dispatch_log.create_at','>=', intval($data['starttime'][0]));
        }
        //查询结束时间
        if(array_key_exists('endtime',$data)){
            $log_wheres[] = array('feimi_rollback_dispatch_log.create_at','<', intval($data['endtime'][0]));
        }

        $channels = DB::table('feimi_channel')->select('channelid','channelname')->get();
        $carray = array();
        foreach ($channels as $channel) {
            $carray[$channel->channelid] = $channel->channelname;
        }
        $operations = DB::table('feimi_operation')->select('opcode','opname')->get();
        $oarray = array();
        foreach($operations as $operation){
            $oarray[$operation->opcode] = $operation->opname;
        }

        $page = 1;
        $pagesize = 10;
        if(array_key_exists('page',$data)) {
            $page = intval($data['page'][0]);
        }
        $offset = $pagesize*($page -1 );
        $count = 0;
        $pagenum = 0;
//        if ($page == 1){
        /*$count = DB::table('feimi_rollback_dispatch_log')
            ->where($log_wheres)
            ->count();*/
        $count = DB::table('feimi_rollback_dispatch_log')
            ->join('feimi_rollback_log', function($join) use($log_wheres)
            {
                $join->on('feimi_rollback_log.rollbackid', '=', 'feimi_rollback_dispatch_log.rollbackid')
                    ->where($log_wheres);
            })
            ->count();

        $pagenum = ceil($count/$pagesize);
//        }

        $res = DB::table('feimi_rollback_dispatch_log')
            ->join('feimi_rollback_log', function($join) use($log_wheres)
            {
                $join->on('feimi_rollback_log.rollbackid', '=', 'feimi_rollback_dispatch_log.rollbackid')
                    ->where($log_wheres);
            })
            ->select('feimi_rollback_log.rollbackid',
                'feimi_rollback_log.value as valuerollback',
                'feimi_rollback_dispatch_log.rollbacksubid',
                'feimi_rollback_dispatch_log.rollbackid',
                'feimi_rollback_dispatch_log.dispatchid',
                'feimi_rollback_dispatch_log.outputid',
                'feimi_rollback_dispatch_log.consumeid',
                'feimi_rollback_dispatch_log.opcode',
                'feimi_rollback_dispatch_log.channelid',
                'feimi_rollback_dispatch_log.outputchannelid',
                'feimi_rollback_dispatch_log.outputopcode',
                'feimi_rollback_dispatch_log.operatorid',
                'feimi_rollback_dispatch_log.operator',
                'feimi_rollback_dispatch_log.value',
                'feimi_rollback_dispatch_log.status',
                'feimi_rollback_dispatch_log.create_at',
                'feimi_rollback_dispatch_log.update_at'
            )
            ->offset($offset)
            ->limit($pagesize)
            ->get();
        $rollbacks = array();
        foreach($res as $re){
            $re = json_decode(json_encode($re),true);
            $re['outputopname'] = $oarray[$re['outputopcode']];
            $re['rollbackopname'] = $oarray[$re['opcode']];
            $re['outputchannelname'] = $carray[$re['outputchannelid']];
            $re['rollbackchannelname'] = $carray[$re['channelid']];
            $rollbacks[] = $re;
        }
      //  print_r($rollbacks);
       // exit();
        $logmsg = new LogMessage(__METHOD__,BizResponse::successResponseArray(array('rollbacks'=>$rollbacks,'page'=>$page,'pagesize'=>$pagesize,"pagenum"=>$pagenum,'count'=>$count)));
        SeasLog::debug($logmsg->logformat(),$logmsg->message());
        return BizResponse::successResponse(array('rollbacks'=>$rollbacks,'page'=>$page,'pagesize'=>$pagesize,"pagenum"=>$pagenum,'count'=>$count));
    }

    function getFeimiOutputBill(Request $request){
        $data =  $request->headers->all();
//        var_dump($data);
//        exit();
        $log_wheres = array();
        $dispatch_wheres = array();
        //行为飞米充值,飞米奖励 101 102
        if(array_key_exists('op',$data)){
            if(is_numeric($data['op'][0])){
                $log_wheres['opcode'] = intval($data['op'][0]);
            }else{
                $log_wheres['opname'] = urldecode($data['op'][0]);
            }
        }
        //用户编号
        if(array_key_exists('unionid',$data)){
            $log_wheres[] = array('unionid','=', intval($data['unionid'][0]));
        }
        //产出类型
        if(array_key_exists('optype',$data)){
            $log_wheres[] = array('optype','=', intval($data['optype'][0]));
        }
        //查询开始时间
        if(array_key_exists('starttime',$data)){
            $log_wheres[] = array('create_at','>=', intval($data['starttime'][0]));
        }
        //查询结束时间
        if(array_key_exists('endtime',$data)){
            $log_wheres[] = array('create_at','<', intval($data['endtime'][0]));
        }
//        var_dump($log_wheres);
//        exit();
        $channels = DB::table('feimi_channel')->select('channelid','channelname','rate')->get();
        $carray = array();
        $ratearray = array();
        foreach ($channels as $channel) {
            $carray[$channel->channelid] = $channel->channelname;
            $ratearray[$channel->channelid] = $channel->rate?intval($channel->rate):1;
        }
        $operations = DB::table('feimi_operation')->select('opcode','opname')->get();


        $oarray = array();
        foreach($operations as $operation){
            $oarray[$operation->opcode] = $operation->opname;
        }
        $optypes = DB::table('feimi_operation_type')->select('optype','optypename')->get();
        $otarray = array();

        foreach($optypes as $optype){
            $otarray[$optype->optype] = $optype->optypename;
        }
//        var_dump($otarray);
//        exit();
        $page = 1;
        $pagesize = 10;
        if(array_key_exists('page',$data)) {
            $page = intval($data['page'][0]);
        }
        $offset = $pagesize*($page -1 );
        $count = 0;
        $pagenum = 0;
//        if ($page == 1){
        $count = DB::table('feimi_log')
            ->where($log_wheres)
            ->count();
        $pagenum = ceil($count/$pagesize);
//        }

        $res = DB::table('feimi_log')
            ->select('logid',
                'opcode',
                'value',
                'create_at',
                'update_at',
                'unionid',
                'status',
                'optype',
                'channelid',
                'opname'
            )->where($log_wheres)
            ->offset($offset)
            ->limit($pagesize)
            ->get();
        $logs = array();
        foreach($res as $re){
            $re = json_decode(json_encode($re),true);
            $re['opname'] = $oarray[$re['opcode']];
            $re['channelname'] = $carray[$re['channelid']];
            $re['optypename'] = $otarray[$re['optype']];
            $re['currency'] = $re['value'];
            if($ratearray[$re['channelid']]){
                $re['currency'] = $re['value']/$ratearray[$re['channelid']];
            }
            $logs[] = $re;
        }
//        var_dump($res);
//        exit();
        $logmsg = new LogMessage(__METHOD__,BizResponse::successResponseArray(array('outputbills'=>$logs,'page'=>$page,'pagesize'=>$pagesize,"pagenum"=>$pagenum,'count'=>$count)));
        SeasLog::debug($logmsg->logformat(),$logmsg->message());
        return BizResponse::successResponse(array('outputbills'=>$logs));
    }


    function getFeimiConsumeBill(Request $request){
        $data =  $request->headers->all();
//        var_dump($data);
//        exit();
        $log_wheres = array();
        $dispatch_wheres = array();
        //行为飞米充值,飞米奖励 101 102
        if(array_key_exists('op',$data)){
            if(is_numeric($data['op'][0])){
                $log_wheres['opcode'] = intval($data['op'][0]);
            }else{
                $log_wheres['opname'] = urldecode($data['op'][0]);
            }
        }

        //消耗行为类型
        if(array_key_exists('optype',$data)){
            $log_wheres[] = array('optype','=', intval($data['optype'][0]));
        }
        //消耗行为渠道 id 1 飞客茶馆 2 飞客旅行 3 飞米平台 4 飞米商城
        if(array_key_exists('starttime',$data)){
            $log_wheres[] = array('create_at','>=', intval($data['starttime'][0]));
        }
        //查询结束时间
        if(array_key_exists('endtime',$data)){
            $log_wheres[] = array('create_at','<', intval($data['endtime'][0]));
        }

        $channels = DB::table('feimi_channel')->select('channelid','channelname','rate')->get();
        $carray = array();
        $ratearray = array();
        foreach ($channels as $channel) {
            $carray[$channel->channelid] = $channel->channelname;
            $ratearray[$channel->channelid] = $channel->rate?intval($channel->rate):1;
        }
        $operations = DB::table('feimi_operation')->select('opcode','opname')->get();


        $oarray = array();
        foreach($operations as $operation){
            $oarray[$operation->opcode] = $operation->opname;
        }
        $optypes = DB::table('feimi_operation_type')->select('optype','optypename')->get();
        $otarray = array();

        foreach($optypes as $optype){
            $otarray[$optype->optype] = $optype->optypename;
        }
//        var_dump($otarray);
//        exit();
        $page = 1;
        $pagesize = 10;
        if(array_key_exists('page',$data)) {
            $page = intval($data['page'][0]);
        }
        $offset = $pagesize*($page -1 );
        $count = 0;
        $pagenum = 0;
//        if ($page == 1){
        $count = DB::table('feimi_log')
            ->where($log_wheres)
            ->count();
        $pagenum = ceil($count/$pagesize);
//        }
        $res = DB::table('feimi_log')
            ->select('logid',
                'opcode',
                'value',
                'create_at',
                'update_at',
                'unionid',
                'status',
                'optype',
                'channelid',
                'opname'
            )->where($log_wheres)
            ->offset($offset)
            ->limit($pagesize)
            ->get();
        $logs = array();
        foreach($res as $re){
            $re = json_decode(json_encode($re),true);
            $re['opname'] = $oarray[$re['opcode']];
            $re['channelname'] = $carray[$re['channelid']];
            $re['optypename'] = $otarray[$re['optype']];
            $re['currency'] = $re['value'];
            if($ratearray[$re['channelid']]){
                $re['currency'] = $re['value']/$ratearray[$re['channelid']];
            }
            $logs[] = $re;
        }
//        var_dump($res);
//        exit();
        $logmsg = new LogMessage(__METHOD__,BizResponse::successResponseArray(array('consumebills'=>$logs,'page'=>$page,'pagesize'=>$pagesize,"pagenum"=>$pagenum,'count'=>$count)));
        SeasLog::debug($logmsg->logformat(),$logmsg->message());
        return BizResponse::successResponse(array('consumebills'=>$logs));
    }



    function postFeimiOperations(Request $request){
        $data =  json_decode($request->getContent(),true);

        if (!$data){
            return BizResponse::failureResponse(BizError::PARAS_NULL_CODE,BizError::PARAS_NULL_MSG);
        }
        $operation = array();
//        var_dump($data);
//        exit();
        if(array_key_exists('oid',$data)){
//            var_dump($data);
//            exit();
            //update
            $oid = intval($data['oid']);
            if (array_key_exists('status',$data)){
                $update = array('status'=>intval($data['status']),"update_at"=>time());
                $wheres = array('oid'=>$oid);
                $res = DB::table('feimi_channel_operation')->where($wheres)->update($update);
                if($res){
//                    var_dump(DB::last_query());
//                    exit();
                    return BizResponse::successResponse();
                }else{
                    $logmsg = new LogMessage(__METHOD__,BizResponse::failureResponseArray(BizError::UPDATE_OPERATION_STATUS_FAIL_CODE,BizError::UPDATE_OPERATION_STATUS_FAIL_MSG));
                    SeasLog::error($logmsg->logformat(),$logmsg->message());
                    return BizResponse::failureResponse(BizError::UPDATE_OPERATION_STATUS_FAIL_CODE,BizError::UPDATE_OPERATION_STATUS_FAIL_MSG);
                }

            }else{
//                var_dump(DB::last_query());
//                exit();
                $logmsg = new LogMessage(__METHOD__, BizResponse::failureResponseArray(BizError::UPDATE_OPERATION_STATUS_NULL_CODE,BizError::UPDATE_OPERATION_STATUS_NULL_MSG));
                SeasLog::error($logmsg->logformat(),$logmsg->message());

                return BizResponse::failureResponse(BizError::UPDATE_OPERATION_STATUS_NULL_CODE,BizError::UPDATE_OPERATION_STATUS_NULL_MSG);
            }
        }
//        var_dump($data);
//        exit();
        if( !array_key_exists('channelid',$data) ||!array_key_exists('channelname',$data) || !array_key_exists('opcode',$data) || !array_key_exists('opname',$data) || !array_key_exists('optype',$data) ){
            $logmsg = new LogMessage(__METHOD__, BizResponse::failureResponseArray(BizError::ADD_OPERATION_PARAMS_LACK_CODE,BizError::ADD_OPERATION_PARAMS_LACK_MSG));
            SeasLog::debug($logmsg->logformat(),$logmsg->message());
            return BizResponse::failureResponse(BizError::ADD_OPERATION_PARAMS_LACK_CODE,BizError::ADD_OPERATION_PARAMS_LACK_MSG);
        }
        $operation['channelid'] = intval($data['channelid']);
        $operation['channelname'] = $data['channelname'];
        $operation['opcode'] = intval($data['opcode']);
        $operation['opname'] = $data['opname'];
        $operation['optype'] = intval($data['optype']);
        $operation['daylimit'] = 0;
        $operation['status'] = 0;
        $operation['timelimit'] = 0;
        $operation['operator'] = "";
        $operation['create_at'] = time();
        $operation['update_at'] = $operation['create_at'];
        if(array_key_exists('daylimit',$data)){
            $operation['daylimit'] = intval($data['daylimit']);
        }
        if(array_key_exists('timelimit',$data)){
            $operation['timelimit'] = intval($data['timelimit']);
        }
        if(array_key_exists('operator',$data)){
            $operation['operator'] = $data['operator'];
        }
        $oid = DB::table('feimi_channel_operation')->insertGetId($operation);
        if($oid){
            $logmsg = new LogMessage(__METHOD__,BizResponse::successResponseArray(array('operationid'=>$oid)));
            SeasLog::debug($logmsg->logformat(),$logmsg->message());
            return BizResponse::successResponse(array('operationid'=>$oid));
        }else{
            $logmsg = new LogMessage(__METHOD__,BizResponse::failureResponseArray(BizError::ADD_OPERATION_FAIL_CODE,BizError::ADD_OPERATION_FAIL_MSG));
            SeasLog::error($logmsg->logformat(),$logmsg->message());
            return BizResponse::failureResponse(BizError::ADD_OPERATION_FAIL_CODE,BizError::ADD_OPERATION_FAIL_MSG);
        }
    }

    function postFeimiByUnionid(Request $request,$uid){

        $data =  json_decode($request->getContent(),true);
        if (!$data){
            return BizResponse::failureResponse(BizError::PARAS_NULL_CODE,BizError::PARAS_NULL_MSG);
        }
        $paras = array();
        $unionid = intval($uid);
        $operatorid = 0;
        $paras['operatorid'] = $operatorid;
        if(array_key_exists('operatorid',$data)){
            $operatorid = intval($data['operatorid']);
            $paras['operatorid'] = $operatorid;
        }
        if(array_key_exists('operator',$data)){
            $paras['operator'] = $data['operator'];
        }
        else{
            $paras['operator'] = "system";
        }
        if(!array_key_exists('opcode',$data)){
            return BizResponse::failureResponse(BizError::OPID_NULL_CODE,BizError::OPID_NULL_MSG);
        }

        $opcode = intval($data['opcode']);
        $paras['opcode'] = $opcode;
        if(!array_key_exists('value',$data)){
            return BizResponse::failureResponse(BizError::OPVALUE_NULL_CODE,BizError::OPVALUE_NULL__MSG);
        }
        $channel = $this->getChannel($request);
        $channelid = $channel['channelid'];

        if(!array_key_exists('value',$data)){
            return BizResponse::failureResponse(BizError::FEIMI_OP_VALUE_NULL_CODE,BizError::FEIMI_OP_VALUE_NULL_MSG);
        }
        $value = intval($data['value']);
        $paras['value'] = $value;
        $paras['rollbackvalue'] = $value;
        if(array_key_exists('rollbackvalue',$data)) {
            $paras['rollbackvalue'] = intval($data['rollbackvalue']);
        }
        if(array_key_exists('consumeid',$data)) {
            $paras['consumeid'] = intval($data['consumeid']);
        }
        if(array_key_exists('outputid',$data)) {
            $paras['outputid'] = intval($data['outputid']);
        }
        if(array_key_exists('reason',$data)){
            $paras['reason'] = $data['reason'];
        }
        if(array_key_exists('remark',$data)){
            $paras['remark'] = $data['remark'];
        }

//        $channelid =  intval($data['channel']);
        if(!$opcode){
            return BizResponse::failureResponse(BizError::OPID_NULL_CODE,BizError::OPID_NULL_MSG);
        }
        if(!$value){
            return BizResponse::failureResponse(BizError::OPVALUE_NULL_CODE,BizError::OPVALUE_NULL__MSG);
        }
//        $otype = FeimiOperation::
        $op_channel = DB::table('feimi_channel_operation')
            ->select('feimi_channel_operation.channelid','feimi_channel_operation.opcode','feimi_operation.optype','feimi_channel_operation.daylimit','feimi_channel_operation.timelimit','feimi_operation.opname')
            ->where(array('feimi_channel_operation.channelid'=>$channelid,'feimi_channel_operation.opcode'=>$opcode,'feimi_channel_operation.status'=>0))
            ->join('feimi_operation', 'feimi_operation.opcode', '=', 'feimi_channel_operation.opcode')
            ->first();
        if(!$op_channel) {
            return BizResponse::failureResponse(BizError::INVALID_CHANNEL_OR_OPERATION_CODE, BizError::INVALID_CHANNEL_OR_OPERATION_MSG);
        }
//        var_dump($op_channel->optype);
//        exit();

        $path = '/tmp/';
        $lock = new FlockLock($path);
        $lockfile="feimi_".$uid.'_op.lock';
//        $lockName = "feimi_user_op_lock_".$uid;
//        $lockFile = $path.$lockName;
        $mutex = new Mutex($lockfile,$lock);
        if ($mutex->acquireLock()) { //检查文件是否存在
            switch($op_channel->optype){
                case 1:
                {
//                output
                    $res =  $this->doAddOutputLog($op_channel,$unionid,$paras);
                    $mutex->releaseLock();
                    return $res;
                    break;
                }
                case 2:
                {
                    //consume
                    $res =  $this->doAddConsumeLog($op_channel,$unionid,$paras);
                    $mutex->releaseLock();
                    return $res;
                    break;

                }
                case 3:
                {
                    $res = $this->doAddOutOfTimeLog($op_channel,$unionid,$paras);
                    $mutex->releaseLock();
                    return $res;
                    break;
                }
                case 4:
                {
                    //rollback
                    $res = $this->doAddRollbackLog($op_channel,$unionid,$paras);
                    $mutex->releaseLock();
                    return $res;
                    break;

                }
                case 5:
                {
                    $res = $this->doAddRecycleLog($op_channel,$unionid,$paras);
                    $mutex->releaseLock();
                    return $res;
                    break;
                }
                default:
                    $mutex->releaseLock();
                    return BizResponse::failureResponse(BizError::INVALID_CHANNEL_OR_OPERATION_CODE, BizError::INVALID_CHANNEL_OR_OPERATION_MSG);
                    break;

            }

        }else{
//            $logmsg = new LogMessage(__METHOD__,array('uid'=>$data['uid'],'lockfailed'=>$lockfile));
            $logmsg = new LogMessage(__METHOD__,BizResponse::failureResponseArray(BizError::USER_OPERATION_LOCKED_CODE,BizError::USER_OPERATION_LOCKED_MSG));
            SeasLog::error($logmsg->logformat(),$logmsg->message());
            return BizResponse::failureResponse(BizError::USER_OPERATION_LOCKED_CODE,BizError::USER_OPERATION_LOCKED_MSG);
        }

    }





    function getUserTodayOutputByUnionId($uid){
        $uid = intval($uid);
        $s = strtotime(date('Y-m-d',time()));;
        $e = $s + 86400;
        $res = DB::table('feimi_log')
            ->select(DB::raw('sum(value) as v'))
            ->where('unionid','=',$uid)
            ->where('status','=',0)
            ->where('optype','=',1)
            ->where('create_at','>',$s)
            ->where('create_at','<=',$e)
            ->first();
        return  intval($res->v);
    }

    function getUserTodayConsumeByUnionId($uid){
        $uid = intval($uid);
        $s = strtotime(date('Y-m-d',time()));;
        $e = $s + 86400;
        $res = DB::table('feimi_log')
            ->select(DB::raw('sum(value) as v'))
            ->where('unionid','=',$uid)
            ->where('status','=',0)
            ->where('optype','=',2)
            ->where('create_at','>',$s)
            ->where('create_at','<=',$e)
            ->first();
        return  intval($res->v);
    }


    protected function generateLockInformation()
    {
        $pid = getmypid();
        $hostname = gethostname();
        $host = gethostbyname($hostname);

        // Compose data to one string
        $params = array();
        $params[] = $pid;
        $params[] = $host;
        $params[] = $hostname;
        $params[] = time()*1000;

        return $params;
    }
    protected function getChannel(Request $request){
        $headers =  $request->headers->all();
        $appkey = null;
        if(array_key_exists('appkey',$headers)){
            $appkey = $headers['appkey'][0];
        }
        if(!$appkey){
            $appkey = Input::get('appkey');
        }
        if(!$appkey){
            return null;
        }
        $where = array('channelkey'=>$appkey);
        $c = DB::table('feimi_channel')->where($where)->first();
        if($c){
            return   json_decode(json_encode($c),true);
        }
        return null;
        $channels =  Config::get('api.feimiChannel');
        if(array_key_exists($appkey,$channels)){
            return $channels[$appkey];
        }
        return null;

    }

    protected function doAddOutputLog($op_channel,$unionid,$paras){
        $value = $paras['value'];
        $operatorid = $paras['operatorid'];
        $out = $this->getUserTodayOutputByUnionId($unionid);
        $newlog = array();
        $newlog['value']=$value;
        $newlog['opcode']=$op_channel->opcode;
        $newlog['channelid']=$op_channel->channelid;
        $newlog['optype']=$op_channel->optype;
        $newlog['create_at']=time();
        $newlog['update_at']=$newlog['create_at'];
        $newlog['status'] = 0 ;
        $newlog['unionid'] = $unionid ;
        $newlog['opname'] = $op_channel->opname;
        $newlog['operator'] = $paras['operator'];
        if(empty($paras['operator']) || $paras['operator'] == ''){
            $newlog['operator'] = 'system';
        }else{
            $newlog['operator'] = $paras['operator'];
        }
        $paras['code'] = 0;

        if($out > intval($op_channel->daylimit)){
            $newlog['status']=1;
            $paras['code'] = BizError::OVER_DAYLIMIT_CODE;
//            return BizResponse::failureResponse(BizError::OVER_DAYLIMIT_CODE, BizError::OVER_DAYLIMIT_MSG);
        }

        if($value >intval($op_channel->timelimit)){
            $newlog['status']=1;
            $paras['code'] = BizError::OVER_TIMEMAX_LIMIT_CODE;
//            var_dump($paras);
//           exit();
//            return BizResponse::failureResponse(BizError::OVER_TIMEMAX_LIMIT_CODE, BizError::OVER_TIMEMAX_LIMIT_MSG);
        }

        $userfeimi = DB::table('feimi_user')
            ->where('unionid','=',$unionid)
            ->first();

        try{
            return DB::transaction(function() use($newlog,$userfeimi,$operatorid,$paras) {
                $logid = DB::table('feimi_log')->insertGetId($newlog);
                if($logid){
                    $outputlog = array();
                    $outputlog['outputid'] = $logid;
                    $outputlog['opcode']= $newlog['opcode'];
                    $outputlog['channelid'] = $newlog['channelid'];
                    $outputlog['operatorid'] = $operatorid;
                    $outputlog['operator'] = $newlog['operator'];           //新增的操作员字段
                    $outputlog['originalvalue'] = $newlog['value'];
                    $outputlog['leftvalue']=$newlog['value'];
                    $outputlog['status']=$newlog['status'];
                    $outputlog['unionid']=$newlog['unionid'];
//                    $outputlog['opname']=$newlog['opname'];
                    $outputlog['create_at']=$newlog['create_at'];
                    $outputlog['update_at']=$newlog['update_at'];
                    if($userfeimi){
                        if($paras['code'] == 0){
                            $outputlog['valuebefore'] = $userfeimi->totalnum;
                            $outputlog['valueafter'] = intval($userfeimi->totalnum) + intval($newlog['value']);
                        }else{
                            $outputlog['valuebefore'] = $userfeimi->totalnum;
                            $outputlog['valueafter'] = $userfeimi->totalnum;
                        }

                        $userupdate = array();
                        if($newlog['status'] == 0){
                            $userupdate['totalnum'] = intval($newlog['value']) + intval($userfeimi->totalnum);
                            $userupdate['outputnum'] = intval($newlog['value']) + intval($userfeimi->outputnum);
                            $userupdate['update_at'] = $newlog['create_at'];
                            DB::table('feimi_user')->where('unionid','=',$userfeimi->unionid)->update($userupdate);
                        }
//                        var_dump($outputlog);
//                        var_dump($userupdate);
//                        exit();
                        DB::table('feimi_output_log')->insertGetId($outputlog);
                    }else{
                        if($paras['code'] == 0){
                            $outputlog['valuebefore']= 0 ;
                            $outputlog['valueafter']= $newlog['value'];
                        }else{
                            $outputlog['valuebefore']= 0 ;
                            $outputlog['valueafter']= 0;
                        }
                        $newuserlog =  array();
                        $newuserlog['unionid'] = $newlog['unionid'];
                        $newuserlog['totalnum'] = $newlog['status'] == 1?0:$newlog['value'];
                        $newuserlog['outputnum'] = $newlog['status'] == 1?0:$newlog['value'];
                        $newuserlog['consumnum'] = 0;
                        $newuserlog['expirednum'] = 0;
                        $newuserlog['create_at'] = $newlog['create_at'] ;
                        $newuserlog['update_at'] = $newlog['update_at'];
                        DB::table('feimi_user')->insertGetId($newuserlog);
                        DB::table('feimi_output_log')->insertGetId($outputlog);
                    }
                    if($paras['code'] == BizError::OVER_DAYLIMIT_CODE ){
                        $logmsg = new LogMessage(__METHOD__,array('fail'=>BizError::OVER_DAYLIMIT_MSG));
                        SeasLog::debug($logmsg->logformat(),$logmsg->message());
                        return BizResponse::failureResponse(BizError::OVER_DAYLIMIT_CODE, BizError::OVER_DAYLIMIT_MSG);
                    }elseif($paras['code'] == BizError::OVER_TIMEMAX_LIMIT_CODE ){
                        $logmsg = new LogMessage(__METHOD__,array('fail'=>BizError::OVER_TIMEMAX_LIMIT_MSG));
                        SeasLog::debug($logmsg->logformat(),$logmsg->message());
                        return BizResponse::failureResponse(BizError::OVER_TIMEMAX_LIMIT_CODE, BizError::OVER_TIMEMAX_LIMIT_MSG);
                    }elseif($paras['code'] == 0){
                        $logmsg = new LogMessage(__METHOD__, BizResponse::successResponseArray());
                        SeasLog::info($logmsg->logformat(),$logmsg->message());
                        return BizResponse::successResponse();
                    }

                }

            });

        }catch(\Exception $e) {
            $logmsg = new LogMessage(__METHOD__,BizResponse::failureResponseArray(BizError::ADD_OUTPUT_FAIL_CODE,$e->getMessage()));
            SeasLog::error($logmsg->logformat(),$logmsg->message());
            return BizResponse::failureResponse(BizError::ADD_OUTPUT_FAIL_CODE,$e->getMessage());

        };

    }

    protected function doAddConsumeLog($op_channel,$unionid,$paras){
        $value = $paras['value'];
        $operatorid = $paras['operatorid'];
        $consume = $this->getUserTodayConsumeByUnionId($unionid);
        $newlog = array();
        $newlog['value']=$value;
        $newlog['opcode']=$op_channel->opcode;
        $newlog['channelid']=$op_channel->channelid;
        $newlog['create_at']=time();
        $newlog['update_at']=time();
        $newlog['optype']=$op_channel->optype;
        $newlog['status'] = 0 ;
        $newlog['unionid'] = $unionid ;
        $newlog['opname'] = $op_channel->opname;
        if(empty($paras['operator']) || $paras['operator'] == ''){
            $newlog['operator'] = 'system';
        }else{
            $newlog['operator'] = $paras['operator'];
        }
        if($op_channel->daylimit >0 && $consume > intval($op_channel->daylimit)){
            $newlog['status']=1;
//            $paras['code'] = BizError::OVER_DAY_USE_LIMIT_CODE;
            return BizResponse::failureResponse(BizError::OVER_DAY_USE_LIMIT_CODE, BizError::OVER_DAY_USE_LIMIT_MSG);
        }
        if($op_channel->timelimit >0  && $value >intval($op_channel->timelimit)){
            $newlog['status']=1;
            return BizResponse::failureResponse(BizError::OVER_TIME_USE_LIMIT_CODE, BizError::OVER_TIME_USE_LIMIT_MSG);
        }
//        var_dump($newlog);
//        exit();

        $userfeimi = DB::table('feimi_user')
            ->where('unionid','=',$unionid)
            ->first();
        if(!$userfeimi ){
            return BizResponse::failureResponse(BizError::USER_NO_FEIMI_CODE, BizError::USER_NO_FEIMI_MSG);
        }
        if(intval($userfeimi->totalnum) < $value){
            return BizResponse::failureResponse(BizError::NO_ENOUGH_FEIMI_CODE, BizError::NO_ENOUGH_FEIMI_MSG);
        }
        //get outputs to dispatch consume
        $ouputs = DB::table('feimi_output_log')
            ->where('unionid','=',$unionid)
            ->where('leftvalue','>',0)
            ->where('status','=',0)
            ->orderby("create_at","asc")
            ->get();
//        var_dump($userfeimi);
//        var_dump($newlog);
//        var_dump($paras);
//        foreach($ouputs as $o){
//            var_dump($o);
//            var_dump('----------------------------------');
//        }
//        exit();

        try{
            return DB::transaction(function() use($newlog,$userfeimi,$operatorid,$ouputs,$paras) {
                $logid =  DB::table('feimi_log')->insertGetId($newlog);
                if($logid){
                    $consumelog = array();
                    $consumelog['consumeid'] = $logid;
                    $consumelog['opcode'] = $newlog['opcode'];
                    $consumelog['unionid'] = $newlog['unionid'];
                    $consumelog['channelid']=$newlog['channelid'];
                    $consumelog['operatorid']= $operatorid;
                    $consumelog['operator'] = $newlog['operator'];           //新增的操作员字段
                    $consumelog['value']=$newlog['value'];
                    if( array_key_exists('ordersn',$paras)){
                        $consumelog['ordersn']=$paras['ordersn'];
                    }
                    $consumelog['orderstatus'] = $paras['orderstatus'];
                    $consumelog['rollbackvalue'] = $paras['rollbackvalue'];
                    $consumelog['rollbackvalueleft'] = $paras['rollbackvalue'];
                    $consumelog['status']=$newlog['status'];
//                    $consumelog['opname']=$newlog['opname'];
                    $consumelog['create_at']=$newlog['create_at'];
                    $consumelog['update_at']=$newlog['update_at'];
                    $userupdate = array();
                    if($userfeimi){
                        $consumelog['valuebefore'] = $userfeimi->totalnum;
                        $consumelog['valueafter'] = intval($userfeimi->totalnum) - intval($newlog['value']);
                        $userupdate['totalnum'] = intval($userfeimi->totalnum) - intval($newlog['value']);
                        $userupdate['consumnum'] = intval($newlog['value']) + intval($userfeimi->consumnum);
                        $userupdate['update_at'] = $newlog['create_at'];
//                        var_dump($consumelog);
//                        var_dump($userupdate);
//                        exit();
                        DB::table('feimi_user')->where('unionid','=',$newlog['unionid'])->update($userupdate);
                        DB::table('feimi_consume_log')->insertGetId($consumelog);
                    }else{
                        return BizResponse::failureResponse(BizError::NO_ENOUGH_FEIMI_CODE, BizError::NO_ENOUGH_FEIMI_MSG);
                    }
                    $totalconsume = $paras['value'];
                    $i = 0;
                    $ctime = time();
                    $dispatchlogs = array();

                    while($totalconsume>0 && $i < count($ouputs) ){
                        $o = $ouputs[$i];
                        $l =  0;
                        $c = 0;
//                        var_dump($o->leftvalue."---".$totalconsume);
                        if ($o->leftvalue >= $totalconsume){
//                            var_dump('++++++++++++>');
                            $l = $o->leftvalue - $totalconsume;
                            $c = $totalconsume;
                            $totalconsume =  0;
                        }else{
//                            var_dump('------------>');
                            $c = $o->leftvalue;
                            $totalconsume = $totalconsume - $o->leftvalue ;
                            $i = $i + 1;
                        }
                        $dlog = array();
                        $dlog['outputid']= $o->outputid;
                        $dlog['consumeid']= $logid;
                        $dlog['opcode']= $consumelog['opcode'];
                        $dlog['channelid'] = $consumelog['channelid'];
                        $dlog['operatorid'] = $consumelog['operatorid'];
                        $dlog['value'] = $c;
                        $dlog['valuebefore'] = $o->leftvalue;
                        $dlog['valueafter'] = $l;
                        $dlog['valueconsumed'] =  $dlog['valuebefore'] - $dlog['valueafter'];
                        $dlog['status'] = 0;
                        $dlog['outputopcode'] = $o->opcode;
                        $dlog['outputchannelid'] = $o->channelid;
                        $dlog['status'] = 0;
                        $dlog['create_at'] = $ctime ;
                        $dlog['update_at'] = $ctime;
                        $ouputlogupdate = array();
                        $ouputlogupdate['leftvalue'] = $l;
                        $ouputlogupdate['update_at'] = $ctime;
                        $dispatchlogs[] = $dlog;
//                        var_dump($ouputlogupdate);
//                        var_dump($dlog);
                        DB::table('feimi_output_log')->where('outputid','=',$o->outputid)->update($ouputlogupdate);
                    }
                    DB::table('feimi_consume_dispatch_log')->insert($dispatchlogs);
                    //status
//uid
//respInfo
//orderCode
//accountAmount
//timestamp
//paramA
//paramB
//sign
                    $logmsg = new LogMessage(__METHOD__,BizResponse::successResponseArray(array("consumeid"=>$consumelog['consumeid'],'valueleft'=>$userupdate['totalnum'],'unionid'=>$newlog['unionid'])));
                    SeasLog::info($logmsg->logformat(),$logmsg->message());
                    return BizResponse::successResponse(array("consumeid"=>$consumelog['consumeid'],'valueleft'=>$userupdate['totalnum'],'unionid'=>$newlog['unionid']));
                }

            });

        }catch(\Exception $e) {
            $logmsg = new LogMessage(__METHOD__,BizResponse::failureResponseArray(BizError::ADD_CONSUME_FAIL_CODE,$e->getMessage(),array("consumeid"=>0,'valueleft'=>$userfeimi->totalnum,'unionid'=>$userfeimi->unionid)));
            SeasLog::error($logmsg->logformat(),$logmsg->message());
            return BizResponse::failureResponse(BizError::ADD_CONSUME_FAIL_CODE,$e->getMessage(),array("consumeid"=>0,'valueleft'=>$userfeimi->totalnum,'unionid'=>$userfeimi->unionid));
        };

    }

    protected function doAddRollbackLog($op_channel,$unionid,$paras){
        $value = $paras['value'];
//        var_dump($paras);
//        exit();
        if(!array_key_exists('consumeid',$paras)){
            return BizResponse::failureResponse(BizError::ROLLBACK_CONSUME_INVALID_CODE, BizError::ROLLBACK_CONSUME_INVALID_MSG);
        }
        $operatorid = $paras['operatorid'];
        $consume = DB::table('feimi_consume_log')
            ->where('unionid','=',$unionid)
            ->where('consumeid','=',intval($paras['consumeid']))
            ->where('rollbackvalueleft','>',0)
            ->first();// $this->getUserTodayConsumeByUnionId($unionid);
//        var_dump($consume);
//        exit();
        if (!$consume){
            return BizResponse::failureResponse(BizError::ROLLBACK_CONSUME_INVALID_CODE, BizError::ROLLBACK_CONSUME_INVALID_MSG);
        }

//        if ($consume->status == 2){
//            return BizResponse::failureResponse(BizError::ROLLBACK_CONSUME_ALREADY_DONE_CODE, BizError::ROLLBACK_CONSUME_ALREADY_DONE_MSG);
//        }

        if($consume->rollbackvalueleft < $value){
            return BizResponse::failureResponse(BizError::ROLLBACK_CONSUME_OVER_CODE, BizError::ROLLBACK_CONSUME_OVER_MSG);
        }

        $newlog = array();
        $newlog['value']=$value;
        $newlog['opcode']=$op_channel->opcode;
        $newlog['optype']=$op_channel->optype;
        $newlog['channelid']=$op_channel->channelid;
        $newlog['create_at']=time();
        $newlog['update_at']=time();
        $newlog['status'] = 0 ;
        $newlog['unionid'] = $unionid ;
        $newlog['opname'] = $op_channel->opname;
        if(empty($paras['operator']) || $paras['operator'] == ''){
            $newlog['operator'] = 'system';
        }else{
            $newlog['operator'] = $paras['operator'];
        }
//        var_dump($newlog);
//        exit();
        $userfeimi = DB::table('feimi_user')
            ->where('unionid','=',$unionid)
            ->first();
        if(!$userfeimi){
            return BizResponse::failureResponse(BizError::OPVALUE_NULL_CODE, BizError::OPVALUE_NULL__MSG);
        }
        //get outputs to dispatch consume
        $dispatchs = DB::table('feimi_consume_dispatch_log')
            ->where('consumeid','=',$paras['consumeid'])
            ->orderby('dispatchid','desc')
            ->get();
        if(count($dispatchs) == 0){
            return BizResponse::failureResponse(BizError::ROLLBACK_CONSUME_INVALID_CODE, BizError::ROLLBACK_CONSUME_INVALID_MSG);
        }
//        var_dump($dispatchs);
//        exit();

        try{
            return DB::transaction(function() use($newlog,$userfeimi,$operatorid,$dispatchs,$paras,$consume) {
                $logid = DB::table('feimi_log')->insertGetId($newlog);
                if($logid){
                    $rollbacklog = array();
                    $rollbacklog['rollbackid'] = $logid;
                    $rollbacklog['consumeid'] = $paras['consumeid'];
                    $rollbacklog['opcode'] = $newlog['opcode'];
                    $rollbacklog['unionid'] = $newlog['unionid'];
                    $rollbacklog['channelid']=$newlog['channelid'];
                    $rollbacklog['operatorid']= $operatorid;
                    $rollbacklog['operator'] = $newlog['operator'];           //新增的操作员字段
                    $rollbacklog['value']=$newlog['value'];
                    $rollbacklog['status']=$newlog['status'];
//                    $consumelog['opname']=$newlog['opname'];
                    $rollbacklog['create_at']=$newlog['create_at'];
                    $rollbacklog['update_at']=$newlog['update_at'];
                    if($userfeimi){
                        $rollbacklog['valuebefore'] = $userfeimi->totalnum;
                        $rollbacklog['valueafter'] = intval($userfeimi->totalnum) + intval($newlog['value']);
                        $consumeupdate = array();
                        $consumeupdate['status'] = 2;
                        $consumeupdate['rollbackvalueleft'] = $consume->rollbackvalueleft - intval($newlog['value']);
                        $userupdate = array();
                        $userupdate['totalnum'] = intval($userfeimi->totalnum) + intval($newlog['value']);
                        $userupdate['consumnum'] =  intval($userfeimi->consumnum) - intval($newlog['value']);
                        $userupdate['update_at'] = $newlog['create_at'];
//                        var_dump($rollbacklog);
//                        var_dump($userupdate);
//                        exit();
                        DB::table('feimi_user')->where('unionid','=',$newlog['unionid'])->update($userupdate);
                        DB::table('feimi_consume_log')
                            ->where('unionid','=',$newlog['unionid'])
                            ->where('consumeid','=',$paras['consumeid'])
                            ->update($consumeupdate);
                        DB::table('feimi_rollback_log')->insertGetId($rollbacklog);

                    }
                    $totalrollback = $paras['value'];
                    $i = 0;
                    $ctime = time();
                    $rollbacklogs = array();

                    while($totalrollback>0 && $i < count($dispatchs) ){
                        $d = $dispatchs[$i];
                        $r = 0;
//                        var_dump($d->valueconsumed."---".$totalrollback);
                        if ($d->valueconsumed >= $totalrollback){
//                            var_dump('++++++++++++>');
                            $r = $totalrollback;
                            $totalrollback =  0;
                        }else{
//                            var_dump('------------>');
                            $r = $d->valueconsumed;;
                            $totalrollback = $totalrollback - $d->valueconsumed;
                            $i = $i + 1;
                        }
                        $rlog = array();
                        $rlog['rollbackid']= $logid;
                        $rlog['dispatchid']= $d->dispatchid;
                        $rlog['outputid']= $d->outputid;
                        $rlog['consumeid']= $d->consumeid;
                        $rlog['opcode']= $d->opcode;
                        $rlog['channelid'] = $d->channelid;
                        $rlog['outputopcode'] = $d->outputopcode;
                        $rlog['outputchannelid'] = $d->outputchannelid;
                        $rlog['operatorid'] = $d->operatorid;
                        $rlog['value'] = $r;
//                        $rlog['valuebefore'] = $o->leftvalue;
//                        $rlog['valueafter'] = $l;
                        $rlog['status'] = 0;
                        $rlog['create_at'] = $ctime ;
                        $rlog['update_at'] = $ctime;
                        $ouputlogupdate = array();
                        $ouputlogupdate['leftvalue'] = DB::raw('leftvalue + '.$r);
                        $ouputlogupdate['update_at'] = $ctime;
                        $rollbacklogs[] = $rlog;
//                        var_dump($rlog);
//                        var_dump($ouputlogupdate);
                        DB::table('feimi_output_log')->where('outputid','=',$d->outputid)->update($ouputlogupdate);
                    }
//                    exit();
                    DB::table('feimi_rollback_dispatch_log')->insert($rollbacklogs);
                    $logmsg = new LogMessage(__METHOD__, BizResponse::successResponseArray());
                    SeasLog::info($logmsg->logformat(),$logmsg->message());
                    return BizResponse::successResponse();
                }

            });

        }catch(\Exception $e) {
            $logmsg = new LogMessage(__METHOD__,BizResponse::failureResponseArray(BizError::ADD_CONSUME_FAIL_CODE,$e->getMessage()));
            SeasLog::error($logmsg->logformat(),$logmsg->message());
            return BizResponse::failureResponse(BizError::ADD_CONSUME_FAIL_CODE,$e->getMessage());

        };

    }

    protected function doAddOutOfTimeLog($op_channel,$unionid,$paras){
        if(!array_key_exists('outputid',$paras)){
            return BizResponse::failureResponse(BizError::FEIMI_OUTPUT_INVALID_CODE, BizError::FEIMI_OUTPUT_INVALID_MSG);
        }
        $outputid = $paras['outputid'];
        $output = DB::table('feimi_output_log')
            ->where('unionid','=',$unionid)
            ->where('leftvalue','>',0)
            ->where('status','=',0)
            ->where('outputid','=',$outputid)
            ->first();
        if (!$output){
            return BizResponse::failureResponse(BizError::FEIMI_OUTPUT_INVALID_CODE, BizError::FEIMI_OUTPUT_INVALID_MSG);
        }
        $operatorid = $paras['operatorid'];
        $newlog = array();
        $newlog['value'] = $output->leftvalue;
        $newlog['opcode']=$op_channel->opcode;
        $newlog['channelid']=$op_channel->channelid;
        $newlog['create_at']=time();
        $newlog['update_at']=time();
        $newlog['status'] = 0 ;
        $newlog['unionid'] = $unionid ;
        $newlog['opname'] = $op_channel->opname;

        if(empty($paras['operator']) || $paras['operator'] == ''){
            $newlog['operator'] = 'system';
        }else{
            $newlog['operator'] = $paras['operator'];
        }
//        var_dump($newlog);
//        exit();
        $userfeimi = DB::table('feimi_user')
            ->where('unionid','=',$unionid)
            ->first();
        if(!$userfeimi){
            return BizResponse::failureResponse(BizError::NO_ENOUGH_FEIMI_CODE, BizError::NO_ENOUGH_FEIMI_MSG);
        }
//        var_dump($newlog);
//        var_dump($userfeimi);
//        exit();
        //get outputs to dispatch consume

        try{
            return DB::transaction(function() use($newlog,$userfeimi,$operatorid,$paras) {
                $logid = DB::table('feimi_log')->insertGetId($newlog);
                if($logid){
                    $outoftimelog = array();
                    $outoftimelog['outoftimeid'] = $logid;
                    $outoftimelog['uninonid'] = $newlog['uninonid'];
                    $outoftimelog['outputid'] = $paras['outputid'];
                    $outoftimelog['opcode'] = $newlog['opcode'];
                    $outoftimelog['channelid']=$newlog['channelid'];
                    $outoftimelog['operatorid']= $operatorid;
                    $outoftimelog['operator'] = $newlog['operator'];           //新增的操作员字段
                    $outoftimelog['value']=$newlog['value'];
                    $outoftimelog['status']=$newlog['status'];
//                    $consumelog['opname']=$newlog['opname'];
                    $outoftimelog['create_at']=$newlog['create_at'];
                    $outoftimelog['update_at']=$newlog['update_at'];
                    if($userfeimi){
                        $outoftimelog['valuebefore'] = $userfeimi->totalnum;
                        $outoftimelog['valueafter'] = intval($userfeimi->totalnum) - intval($newlog['value']);
                        $userupdate = array();
                        $userupdate['totalnum'] = intval($userfeimi->totalnum) - intval($newlog['value']);
                        $userupdate['expirednum'] =  intval($userfeimi->expirednum) + intval($newlog['value']);
                        $userupdate['update_at'] = $newlog['create_at'];
//                        var_dump($outoftimelog);
//                        var_dump($userupdate);
//                        exit();
                        DB::table('feimi_user')->where('unionid','=',$newlog['unionid'])->update($userupdate);
                        DB::table('feimi_outoftime_log')->insertGetId($outoftimelog);
                    }
                    $logmsg = new LogMessage(__METHOD__,BizResponse::successResponseArray());
                    SeasLog::info($logmsg->logformat(),$logmsg->message());
                    return BizResponse::successResponse();
                }
            });

        }catch(\Exception $e) {
            $logmsg = new LogMessage(__METHOD__,BizResponse::failureResponseArray(BizError::ADD_CONSUME_FAIL_CODE,$e->getMessage()));
            SeasLog::error($logmsg->logformat(),$logmsg->message());
            return BizResponse::failureResponse(BizError::ADD_CONSUME_FAIL_CODE,$e->getMessage());

        };

    }

    //回收
    protected function doAddRecycleLog($op_channel,$unionid,$paras){
        if(!array_key_exists('outputid',$paras)){
            return BizResponse::failureResponse(BizError::FEIMI_OUTPUT_INVALID_CODE, BizError::FEIMI_OUTPUT_INVALID_MSG);
        }
        $outputid = $paras['outputid'];
        $output = DB::table('feimi_output_log')
            ->where('unionid','=',$unionid)
            ->where('leftvalue','>',0)
            ->where('status','=',0)
            ->where('outputid','=',$outputid)
            ->first();
        if (!$output){
            return BizResponse::failureResponse(BizError::FEIMI_OUTPUT_INVALID_CODE, BizError::FEIMI_OUTPUT_INVALID_MSG);
        }
        $operatorid = $paras['operatorid'];
        $newlog = array();
        $newlog['value'] = $paras['value'];
        $newlog['opcode']=$op_channel->opcode;
        $newlog['channelid']=$op_channel->channelid;
        $newlog['create_at']=time();
        $newlog['update_at']=time();
        $newlog['status'] = 0 ;
        $newlog['optype']=$op_channel->optype;
        $newlog['unionid'] = $unionid ;
        $newlog['opname'] = $op_channel->opname;
        if(empty($paras['operator']) || $paras['operator'] == ''){
            $newlog['operator'] = 'system';
        }else{
            $newlog['operator'] = $paras['operator'];
        }
        $userfeimi = DB::table('feimi_user')
            ->where('unionid','=',$unionid)
            ->first();
        if(!$userfeimi){
            return BizResponse::failureResponse(BizError::NO_ENOUGH_FEIMI_CODE, BizError::NO_ENOUGH_FEIMI_MSG);
        }
//        var_dump($newlog);
//        var_dump($userfeimi);
//        exit();
        //get outputs to dispatch consume

        try{
            return DB::transaction(function() use($newlog,$userfeimi,$output,$operatorid,$paras) {
                $logid = DB::table('feimi_log')->insertGetId($newlog);
                if($logid){
                    $recyclelog = array();
                    $recyclelog['recycleid'] = $logid;
                    $recyclelog['outputid'] = $paras['outputid'];
                    $recyclelog['unionid'] = $newlog['unionid'];
                    $recyclelog['opcode'] = $newlog['opcode'];
                    $recyclelog['channelid']=$newlog['channelid'];
                    $recyclelog['operatorid']= $operatorid;
                    $recyclelog['operator'] = $newlog['operator'];
                    $recyclelog['value']=$newlog['value'];
                    $recyclelog['remark']=$paras['remark'];
                    $recyclelog['reason']=$paras['reason'];
                    $recyclelog['create_at']=$newlog['create_at'];
                    $recyclelog['update_at']=$newlog['update_at'];
                    if($output){
                        $outputupdate = array();
                        $outputupdate['originalvalue'] = intval($output->originalvalue) - intval($paras['value']);
                        $outputupdate['leftvalue'] =  intval($output->leftvalue) - intval($paras['value']);
                        $outputupdate['valueafter'] = intval($output->valueafter) - intval($paras['value']);
                        $outputupdate['update_at'] = $newlog['create_at'];
                        DB::table('feimi_output_log')->where('outputid','=',$paras['outputid'])->update($outputupdate);
                    }
                    if($userfeimi){
                        $recyclelog['valuebefore'] = $userfeimi->totalnum;
                        $recyclelog['valueafter'] = intval($userfeimi->totalnum) - intval($newlog['value']);
                        $userupdate = array();
                        $userupdate['totalnum'] = intval($userfeimi->totalnum) - intval($newlog['value']);
                        $userupdate['recyclenum'] =  intval($userfeimi->recyclenum) + intval($newlog['value']);
                        $userupdate['update_at'] = $newlog['create_at'];
                        DB::table('feimi_user')->where('unionid','=',$newlog['unionid'])->update($userupdate);
                        DB::table('feimi_recycle_log')->insertGetId($recyclelog);
                    }
                    $logmsg = new LogMessage(__METHOD__,BizResponse::successResponseArray());
                    SeasLog::info($logmsg->logformat(),$logmsg->message());
                    return BizResponse::successResponse();
                }
            });

        }catch(\Exception $e) {
            $logmsg = new LogMessage(__METHOD__,BizResponse::failureResponseArray(BizError::ADD_CONSUME_FAIL_CODE,$e->getMessage()));
            SeasLog::error($logmsg->logformat(),$logmsg->message());
            return BizResponse::failureResponse(BizError::ADD_CONSUME_FAIL_CODE,$e->getMessage());

        };

    }

    function postConsumeFromPointStore(Request $request){

        $data =  json_decode($request->getContent(),true);
        if (!$data){
            $result = array();
            $result['uid'] = 0;
            $result['respInfo'] = BizError::PARAS_NULL_MSG;
            $result['accountAmount'] = 0;
            $result['orderCode'] = 0;
            $result['timestamp'] = time()*1000;
            $result['paramA'] = "";
            $result['paramB'] = "";
            $result['status'] = 0;
            $result['sign'] = $this->generateSignature(0);
            return response()->json($result);
        }
        $paras = array();
        $paras['operatorid'] = 0;
        if(!array_key_exists('uid',$data)){
            $result = array();
            $result['uid'] = 0;
            $result['respInfo'] = BizError::UID_NULL_MSG;
            $result['accountAmount'] = 0;
            $result['orderCode'] = 0;
            $result['timestamp'] = time()*1000;
            $result['paramA'] = "";
            $result['paramB'] = "";
            $result['status'] = 0;
            $result['sign'] = $this->generateSignature(0);
            return response()->json($result);
        }
        if(!array_key_exists('sign',$data)){
            $result = array();
            $result['uid'] = $data['uid'];
            $result['respInfo'] = BizError::INVALID_SIGN_MSG;
            $result['accountAmount'] = 0;
            $result['orderCode'] = 0;
            $result['timestamp'] = time()*1000;
            $result['paramA'] = "";
            $result['paramB'] = "";
            $result['status'] = 0;
            $result['sign'] = $this->generateSignature($data['uid']);
            return response()->json($result);
        }
        $paras['sign'] =  $data['sign'];

        if(!$this->checkFeimiStoreSignature($data)){
            $result = array();
            $result['uid'] = $data['uid'];
            $result['respInfo'] = BizError::CHECK_SIGN_FAIL_MSG;
            $result['accountAmount'] = 0;
            $result['orderCode'] = 0;
            $result['timestamp'] = time()*1000;
            $result['paramA'] = "";
            $result['paramB'] = "";
            $result['status'] = 0;
            $result['sign'] = $this->generateSignature($data['uid']);
            return response()->json($result);
        }
        $unionid = intval($data['uid']);

        $opcode  = 201;

        if(array_key_exists('productType',$data)){
            switch(intval($data['productType'])){
                case 1:
                    $opcode = 201;
                    break;
                case 2:
                    $opcode = 204;
                    break;
                case 3:
                    $opcode = 205;
                    break;
                case 4:
                    $opcode = 202;
                    break;

            }
        }

        $paras['opcode'] = $opcode;
        if(!array_key_exists('orderPoints',$data)){
            $result = array();
            $result['uid'] = $data['uid'];
            $result['respInfo'] = BizError::FEIMI_OP_VALUE_NULL_MSG;
            $result['accountAmount'] = 0;
            $result['orderCode'] = 0;
            $result['timestamp'] = time()*1000;
            $result['paramA'] = "";
            $result['paramB'] = "";
            $result['status'] = 0;
            $result['sign'] = $this->generateSignature($data['uid']);
            return response()->json($result);
        }

        $channel = $this->getChannel($request);
        if(!$channel){
            $result = array();
            $result['uid'] = 0;
            $result['respInfo'] = BizError::OPID_NULL_MSG;
            $result['accountAmount'] = 0;
            $result['orderCode'] = 0;
            $result['timestamp'] = time()*1000;
            $result['paramA'] = "";
            $result['paramB'] = "";
            $result['status'] = 0;
            $result['sign'] = $this->generateSignature(0);
            return response()->json($result);
        }

        $channelid = $channel['channelid'];
        $value = intval($data['orderPoints']);
        $paras['value'] = $value;
        $paras['rollbackvalue'] = $value;
        if(array_key_exists('rollbackvalue',$data)) {
            $paras['rollbackvalue'] = intval($data['rollbackvalue']);
        }

        if(array_key_exists('platOrderCode',$data)) {
            $paras['ordersn'] = str_replace(' ','', $data['platOrderCode']);
            $consume = DB::table('feimi_consume_log')
                ->where('unionid','=',$unionid)
                ->where('ordersn','=',$paras['ordersn'])
                ->first();
            if($consume && $paras['ordersn'] != "" && $consume->ordersn && $consume->ordersn != ""){
                $result = array();
                $result['uid'] = 0;
                $result['respInfo'] = BizError::CONSUME_ORDER_ALREADY_EXIST_MSG;
                $result['accountAmount'] = 0;
                $result['orderCode'] = 0;
                $result['timestamp'] = time()*1000;
                $result['paramA'] = "";
                $result['paramB'] = "";
                $result['status'] = 0;
                $result['sign'] = $this->generateSignature(0);
                return response()->json($result);
            }
            $paras['orderstatus'] = 2;
        }


//        $channelid =  intval($data['channel']);
        if(!$opcode){
            $result = array();
            $result['uid'] = 0;
            $result['respInfo'] = BizError::OPID_NULL_MSG;
            $result['accountAmount'] = 0;
            $result['orderCode'] = 0;
            $result['timestamp'] = time()*1000;
            $result['paramA'] = "";
            $result['paramB'] = "";
            $result['status'] = 0;
            $result['sign'] = $this->generateSignature(0);
            return response()->json($result);
        }
        if(!$value){
            $result = array();
            $result['uid'] = 0;
            $result['respInfo'] = BizError::OPVALUE_NULL__MSG;
            $result['accountAmount'] = 0;
            $result['orderCode'] = 0;
            $result['timestamp'] = time()*1000;
            $result['paramA'] = "";
            $result['paramB'] = "";
            $result['status'] = 0;
            $result['sign'] = $this->generateSignature(0);
            return response()->json($result);
        }
//        var_dump($data);
//        var_dump($paras);
//        exit();//        $otype = FeimiOperation::
        $op_channel = DB::table('feimi_channel_operation')
            ->select('feimi_channel_operation.channelid','feimi_channel_operation.opcode','feimi_operation.optype','feimi_channel_operation.daylimit','feimi_channel_operation.timelimit','feimi_operation.opname')
            ->where(array('feimi_channel_operation.channelid'=>$channelid,'feimi_channel_operation.opcode'=>$opcode))
            ->join('feimi_operation', 'feimi_operation.opcode', '=', 'feimi_channel_operation.opcode')
            ->first();
        if(!$op_channel) {
            $result = array();
            $result['uid'] = 0;
            $result['respInfo'] = BizError::INVALID_CHANNEL_OR_OPERATION_MSG;
            $result['accountAmount'] = 0;
            $result['orderCode'] = 0;
            $result['timestamp'] = time()*1000;
            $result['paramA'] = "";
            $result['paramB'] = "";
            $result['status'] = 0;
            $result['sign'] = $this->generateSignature(0);
            return response()->json($result);
        }
//        var_dump($op_channel->optype);
//        exit();

        $path = '/tmp/';
        $lock = new FlockLock($path);
        $lockfile="feimi_channel_".$channelid.'_'.$unionid.'_op.lock';
//        $lockName = "feimi_user_op_lock_".$uid;
//        $lockFile = $path.$lockName;
        //return
        $mutex = new Mutex($lockfile,$lock);
        if ($mutex->acquireLock()) { //检查文件是否存在
            switch($op_channel->optype){
                case 2:
                {
                    //uid
                    //respInfo
                    //orderCode
                    //accountAmount
                    //timestamp
                    //paramA
                    //paramB
                    //sign
//                  return BizResponse::successResponse(array("consumeid"=>$consumelog['consumeid'],'valueleft'=>$userupdate['totalnum'],'unionid'=>$newlog['unionid']));
                    //consume
//                    var_dump($op_channel);
//                    var_dump($unionid);
//                    var_dump($paras);
                    $res =  $this->doAddConsumeLog($op_channel,$unionid,$paras);
//                    var_dump($res);
//                    exit();
                    $mutex->releaseLock();
                    $res = $res->getData();
//                    var_dump($res);
//                    exit();

                    if($res->success){
                        $data = $res->data;
                        $result = array();
                        $result['uid'] = $data->unionid;
                        $result['respInfo'] = "";
                        $result['accountAmount'] = $data->valueleft;
                        $result['orderCode'] = $data->consumeid;
                        $result['timestamp'] = time()*1000;
                        $result['paramA'] = "";
                        $result['paramB'] = "";
                        $result['status'] = 1;
                        $result['sign'] = $this->generateSignature($data->unionid);
                        return response()->json($result);
                    }else{
                        $result = array();
                        $result['uid'] = $data['uid'];
                        $result['respInfo'] = $res->message;
                        $result['accountAmount'] = 0;
                        $result['orderCode'] = 0;
                        $result['timestamp'] = time()*1000;
                        $result['paramA'] = "";
                        $result['paramB'] = "";
                        $result['status'] = 0;
                        $result['sign'] = $this->generateSignature($data['uid']);
                        return response()->json($result);
                    }
                    break;

                }
                default:
                    $mutex->releaseLock();
                    $result = array();
                    $result['uid'] = $data['uid'];
                    $result['respInfo'] = BizError::INVALID_CHANNEL_OR_OPERATION_MSG;
                    $result['accountAmount'] = 0;
                    $result['orderCode'] = 0;
                    $result['timestamp'] = time()*1000;
                    $result['paramA'] = "";
                    $result['paramB'] = "";
                    $result['status'] = 0;
                    $result['sign'] = $this->generateSignature($data['uid']);
                    return response()->json($result);
                    break;

            }

        }else{
            $result = array();
            $result['uid'] = $data['uid'];
            $result['respInfo'] = BizError::USER_OPERATION_LOCKED_MSG;
            $result['accountAmount'] = 0;
            $result['orderCode'] = 0;
            $result['timestamp'] = time()*1000;
            $result['paramA'] = "";
            $result['paramB'] = "";
            $result['status'] = 0;
            $result['sign'] = $this->generateSignature($data['uid']);
            return response()->json($result);
        }

    }

    public function getFeimiDetails(Request $request){
        $data =  $request->headers->all();
        $log_wheres = array();

        //行为飞米充值,飞米奖励 101 102
        if(array_key_exists('op',$data)){
            if(is_numeric($data['op'][0])){
                $log_wheres['opcode'] = intval($data['op'][0]);
            }else{
                $log_wheres['opname'] = urldecode($data['op'][0]);
            }
        }

        //操作渠道 id 1 飞客茶馆 2 飞客旅行 3 飞米平台 4 飞米商城
        if(array_key_exists('channelid',$data)) {
            $log_wheres['channelid'] = $data['channelid'][0];
        }

        //操作类型id 1 飞米产出 2 飞米消耗 3 飞米过期 4 飞米回滚
        if(array_key_exists('optype',$data)) {
            $log_wheres['optype'] = $data['optype'][0];
        }

        //查询开始时间
        if(array_key_exists('starttime',$data)){
            $log_wheres[] = array('create_at','>=', intval($data['starttime'][0]));
        }
        //查询结束时间
        if(array_key_exists('endtime',$data)){
            $log_wheres[] = array('create_at','<', intval($data['endtime'][0]));
        }
        //操作人
        if(array_key_exists('operator',$data)){
            $log_wheres['operator'] = $data['operator'][0];
        }
        $channels = DB::table('feimi_channel')->select('channelid','channelname')->get();
        $carray = array();
        //遍历所有操作渠道信息
        foreach($channels as $channel){
            $carray[$channel->channelid] = $channel->channelname;
        }

        $outputs = DB::table('feimi_output_log')->select('outputid','opcode','leftvalue')->get();
        $ouarray = array();
        //遍历所有用户的产出信息
        foreach($outputs as $output){
            $ouarray[$output->outputid] = $output->leftvalue;
        }
        $optypes = DB::table('feimi_operation_type')->select('optype','optypename')->get();
        $otarray = array();
        //遍历所有操作类型
        foreach($optypes as $optype){
            $otarray[$optype->optype] = $optype->optypename;
        }

        $page = 1;
        $pagesize = 10;
        if(array_key_exists('page',$data)) {
            $page = intval($data['page'][0]);
        }
        $offset = $pagesize*($page -1 );

        $count = 0;
        $pagenum = 0;
        $count = DB::table('feimi_log')
            ->where($log_wheres)
            ->count();
        $pagenum = ceil($count/$pagesize);

        $res = DB::table('feimi_log')
            ->select('logid',
                'opcode',
                'value',
                'create_at',
                'update_at',
                'unionid',
                'status',
                'optype',
                'channelid',
                'opname',
                'operator'
            )->where($log_wheres)
            ->offset($offset)
            ->limit($pagesize)
            ->get();
        $logs = array();
        foreach($res as $val){
            $val = json_decode(json_encode($val),true);
            $val['channelname'] = $carray[$val['channelid']];
            $val['optypename'] = $otarray[$val['optype']];
            if($val['optype'] == 1){
                $val['leftvalue'] = $ouarray[$val['logid']];
                $val['outputid'] = $val['logid'];
            }
            $sjc= $val['create_at'];
            $val['expiretime'] =  date("Y-12-31",strtotime('+1 year',$sjc));
            $logs[] = $val;
        }
        $logmsg = new LogMessage(__METHOD__,BizResponse::successResponseArray(array('details'=>$logs,'page'=>$page,'pagesize'=>$pagesize,"pagenum"=>$pagenum,'count'=>$count)));
        SeasLog::debug($logmsg->logformat(),$logmsg->message());
        return BizResponse::successResponse(array('details'=>$logs,'page'=>$page,'pagesize'=>$pagesize,"pagenum"=>$pagenum,'count'=>$count));
    }


    public  function pointStoreCallBack(Request $request){
        $data =  json_decode($request->getContent(),true);
        if (!$data){
            $result = array();
            $result['uid'] = 0;
            $result['respInfo'] = BizError::OPID_NULL_MSG;
            $result['accountAmount'] = 0;
            $result['orderCode'] = 0;
            $result['timestamp'] = time()*1000;
            $result['paramA'] = "";
            $result['paramB'] = "";
            $result['status'] = 0;
            $result['sign'] = $this->generateSignature(0);
            return response()->json($result);
        }
        $paras = array();
        $paras['operatorid'] = 0;
        if(!array_key_exists('uid',$data)){
            $result = array();
            $result['uid'] = 0;
            $result['respInfo'] = BizError::UID_NULL_MSG;
            $result['accountAmount'] = 0;
            $result['orderCode'] = 0;
            $result['timestamp'] = time()*1000;
            $result['paramA'] = "";
            $result['paramB'] = "";
            $result['status'] = 0;
            $result['sign'] = $this->generateSignature(0);
            return response()->json($result);
        }
        if(!array_key_exists('sign',$data)){
            $result = array();
            $result['uid'] = $data['uid'];
            $result['respInfo'] = BizError::INVALID_SIGN_MSG;
            $result['accountAmount'] = 0;
            $result['orderCode'] = 0;
            $result['timestamp'] = time()*1000;
            $result['paramA'] = "";
            $result['paramB'] = "";
            $result['status'] = 0;
            $result['sign'] = $this->generateSignature($data['uid']);
            return response()->json($result);
        }
        $paras['sign'] =  $data['sign'];

        if(!$this->checkFeimiStoreSignature($data)){
            $result = array();
            $result['uid'] = $data['uid'];
            $result['respInfo'] = BizError::INVALID_SIGN_MSG;
            $result['accountAmount'] = 0;
            $result['orderCode'] = 0;
            $result['timestamp'] = time()*1000;
            $result['paramA'] = "";
            $result['paramB'] = "";
            $result['status'] = 0;
            $result['sign'] = $this->generateSignature($data['uid']);
            return response()->json($result);
        }
        if (array_key_exists('exchangeStatus',$data)) {
            if(intval($data['exchangeStatus']) == 1){

                $consume = DB::table('feimi_consume_log')
                    ->where('unionid','=',intval($data['uid']))
                    ->where('ordersn','=',$data['platOrderCode'])
                    ->first();
                $userfeimi = DB::table('feimi_user')
                    ->where('unionid','=',intval($data['uid']))
                    ->first();
                if($data['platOrderCode'] != "" && !$consume){
                    $result = array();
                    $result['uid'] = $data['uid'];
                    $result['respInfo'] = BizError::ORDER_NOT_EXIST_MSG;
                    $result['accountAmount'] = 0;
                    if($userfeimi){
                        $result['accountAmount'] = $userfeimi->totalnum;
                    }
                    $result['orderCode'] = 0;
                    $result['timestamp'] = time()*1000;
                    $result['paramA'] = "";
                    $result['paramB'] = "";
                    $result['status'] = 0;
                    $result['sign'] = $this->generateSignature(0);
                    return response()->json($result);
                }

                // update the order status
                $wherearr = array();
                $wherearr['ordersn'] = $data['platOrderCode'];
                $consumeupdate = array();
                $consumeupdate['orderstatus'] = 1;

//                var_dump($wherearr);
//                var_dump($consumeupdate);
//                exit();
                $res = DB::table('feimi_consume_log')->where($wherearr)->update($consumeupdate);
                if($res){
                    //logger

                }else{
                    //logger
                }
//                $userfeimi = DB::table('feimi_user')
//                    ->where('unionid','=',intval($data['uid']))
//                    ->first();
                $result = array();
                $result['uid'] = $data['uid'];
                $result['respInfo'] = "";
                $result['accountAmount'] = 0;
                if($userfeimi){
                    $result['accountAmount'] = $userfeimi->totalnum;
                }
                $result['timestamp'] = time()*1000;
                $result['status'] = 1;
                $result['sign'] = $this->generateSignature($result['uid']);
                return response()->json($result);
            }
        }else{
            $result = array();
            $result['uid'] = $data['uid'];
            $result['respInfo'] = BizError::ORDER_STATUS_NULL_MSG ;
            $result['accountAmount'] = 0;
            $result['orderCode'] = 0;
            $result['timestamp'] = time()*1000;
            $result['paramA'] = "";
            $result['paramB'] = "";
            $result['status'] = 0;
            $result['sign'] = $this->generateSignature($result['uid']);
            return response()->json($result);
        }

        $channel = $this->getChannel($request);
        if(!$channel){
            $result = array();
            $result['uid'] = 0;
            $result['respInfo'] = BizError::OPID_NULL_MSG;
            $result['accountAmount'] = 0;
            $result['orderCode'] = 0;
            $result['timestamp'] = time()*1000;
            $result['paramA'] = "";
            $result['paramB'] = "";
            $result['status'] = 0;
            $result['sign'] = $this->generateSignature(0);
            return response()->json($result);
        }
//        var_dump($channel);
//        exit();
        $channelid = $channel['channelid'];
        $opcode = 401;
        $unionid = intval($data['uid']);
        $paras['ordersn'] = $data['platOrderCode'];
        $op_channel = DB::table('feimi_channel_operation')
            ->select('feimi_channel_operation.channelid','feimi_channel_operation.opcode','feimi_operation.optype','feimi_channel_operation.daylimit','feimi_channel_operation.timelimit','feimi_operation.opname')
            ->where(array('feimi_channel_operation.channelid'=>$channelid,'feimi_channel_operation.opcode'=>$opcode))
            ->join('feimi_operation', 'feimi_operation.opcode', '=', 'feimi_channel_operation.opcode')
            ->first();
        if(!$op_channel) {
            $result = array();
            $result['uid'] = $data['uid'];
            $result['respInfo'] = BizError::INVALID_CHANNEL_OR_OPERATION_MSG;
            $result['accountAmount'] = 0;
            $result['orderCode'] = 0;
            $result['timestamp'] = time()*1000;
            $result['paramA'] = "";
            $result['paramB'] = "";
            $result['status'] = 0;
            $result['sign'] = $this->generateSignature($result['uid']);
            return response()->json($result);
        }

        $paras['operatorid'] = 0;
        $paras['value'] = intval($data['orderPoints']);
        $paras['unionid'] = $data['uid'];
        $paras['ordersn'] = $data['platOrderCode'];

//        var_dump($paras);
//        var_dump($op_channel);
//        exit();

        $path = '/tmp/';
        $lock = new FlockLock($path);
        $lockfile="feimi_channel_".$channelid.'_'.$unionid.'_op.lock';
//        $lockName = "feimi_user_op_lock_".$uid;
//        $lockFile = $path.$lockName;
        //return
//        var_dump($lockfile);
//        exit();
        $mutex = new Mutex($lockfile,$lock);
        if ($mutex->acquireLock()) { //检查文件是否存在
            $res =  $this->doFeimiStoreCallback($op_channel,$unionid,$paras);
//            var_dump($res);
//            exit();
            $mutex->releaseLock();
            return $res;
        }else{
            $result = array();
            $result['uid'] = $data['uid'];
            $result['respInfo'] = BizError::USER_OPERATION_LOCKED_MSG;
            $result['accountAmount'] = 0;
            $result['timestamp'] = time()*1000;
            $result['status'] = 0;
            $result['sign'] = $this->generateSignature($data['uid']);
            return response()->json($result);
        }

    }

    protected function doFeimiStoreCallback($op_channel,$unionid,$paras){
        $operatorid = $paras['operatorid'];
        $odersn = $paras['ordersn'];

        $userfeimi = DB::table('feimi_user')
            ->where('unionid','=',$unionid)
            ->first();
        if(!$userfeimi){
            $result = array();
            $result['uid'] = $paras['unionid'];
            $result['respInfo'] = BizError::USER_NO_FEIMI_MSG;
            $result['platOrderCode'] = $paras['ordersn'];
            $result['timestamp'] = time()*1000;
            $result['exchangeStatus'] = 0;
            $result['status'] = 0;
            $result['sign'] = $this->generateSignature($unionid);
            return $result;
        }
        $consume = DB::table('feimi_consume_log')
            ->where('unionid','=',$unionid)
            ->where('ordersn','=',$odersn)
            ->first();// $this->getUserTodayConsumeByUnionId($unionid);
//        var_dump($consume);
//        exit();

        if (!$consume){
            $result = array();
            $result['uid'] = 0;
            $result['respInfo'] = BizError::ORDER_NOT_EXIST_MSG;
            $result['platOrderCode'] = $paras['ordersn'];
            $result['timestamp'] = time()*1000;
            $result['exchangeStatus'] = 0;
            $result['status'] = 0;
            $result['sign'] = $this->generateSignature($unionid);
            return $result;
        }
        if($consume->rollbackvalue < $paras['value']){
            $result = array();
            $result['uid'] = $unionid;
            $result['respInfo'] = BizError::ROLLBACKVALUE_OVER_FLOW_MSG;
            $result['platOrderCode'] = $paras['ordersn'];
            $result['timestamp'] = time()*1000;
            $result['status'] = 0;
            $result['sign'] = $this->generateSignature($result['uid']);
            return $result;
        }

        if($consume->rollbackvalueleft <= 0){
            $result = array();
            $result['uid'] = $unionid;
            $result['respInfo'] = BizError::ROLLBACK_CONSUME_ALREADY_DONE_MSG;
            $result['platOrderCode'] = $paras['ordersn'];
            $result['timestamp'] = time()*1000;
            $result['status'] = 1;
            $result['sign'] = $this->generateSignature($result['uid']);
            return $result;
        }
        $paras['consumeid'] = $consume->consumeid;
        $value = $paras['value'];
        $newlog = array();
        $newlog['value']=$value;
        $newlog['opcode']=$op_channel->opcode;
        $newlog['optype']=$op_channel->optype;
        $newlog['channelid']=$op_channel->channelid;
        $newlog['create_at']=time();
        $newlog['update_at']=time();
        $newlog['status'] = 0 ;
        $newlog['unionid'] = $unionid ;
        $newlog['opname'] = $op_channel->opname;
        $consumeid = $consume->consumeid;
        //get outputs to dispatch consume
        $dispatchs = DB::table('feimi_consume_dispatch_log')
            ->where('consumeid','=',$consumeid)
            ->orderby('dispatchid','desc')
            ->get();
//        var_dump($dispatchs);
//        exit();
        if(count($dispatchs) == 0){
            $result = array();
            $result['uid'] = $unionid;
            $result['respInfo'] = BizError::ROLLBACK_CONSUME_INVALID_MSG;
            $result['platOrderCode'] = $paras['ordersn'];
            $result['timestamp'] = time()*1000;
            $result['status'] = 0;
            $result['sign'] = $this->generateSignature($result['uid']);
            return $result;
        }
//        var_dump($paras);
//        exit();

        try{
            return DB::transaction(function() use($newlog,$userfeimi,$operatorid,$dispatchs,$paras,$consume) {
                $logid = DB::table('feimi_log')->insertGetId($newlog);
                if($logid){
                    $rollbacklog = array();
                    $rollbacklog['rollbackid'] = $logid;
                    $rollbacklog['consumeid'] = $consume->consumeid;
                    $rollbacklog['opcode'] = $newlog['opcode'];
                    $rollbacklog['unionid'] = $newlog['unionid'];
                    $rollbacklog['channelid']=$newlog['channelid'];
                    $rollbacklog['operatorid']= $operatorid;
                    if( array_key_exists('operator',$paras)){
                        $rollbacklog['operator']= str_replace(" ",'',$paras['operator']);
                    }
                    if( array_key_exists('ordersn',$paras)){
                        $rollbacklog['ordersn']= str_replace(" ",'',$paras['ordersn']);
                    }
                    $rollbacklog['value']=$newlog['value'];
                    $rollbacklog['status']=$newlog['status'];
                    $userupdate = array();
//                    $consumelog['opname']=$newlog['opname'];
                    $rollbacklog['create_at']=$newlog['create_at'];
                    $rollbacklog['update_at']=$newlog['update_at'];
                    if($userfeimi){
                        $rollbacklog['valuebefore'] = $userfeimi->totalnum;
                        $rollbacklog['valueafter'] = intval($userfeimi->totalnum) + intval($newlog['value']);
                        $consumeupdate = array();
                        $consumeupdate['status'] = 2;
                        $consumeupdate['orderstatus'] = 0;
                        $consumeupdate['rollbackvalueleft'] = $consume->rollbackvalueleft - intval($newlog['value']);
                        $userupdate['totalnum'] = intval($userfeimi->totalnum) + intval($newlog['value']);
                        $userupdate['consumnum'] =  intval($userfeimi->consumnum) - intval($newlog['value']);
                        $userupdate['update_at'] = $newlog['create_at'];
//                        var_dump($rollbacklog);
//                        var_dump($userupdate);
//                        var_dump($consumeupdate);
//                        exit();
                        DB::table('feimi_user')->where('unionid','=',$newlog['unionid'])->update($userupdate);
                        DB::table('feimi_consume_log')
                            ->where('unionid','=',$newlog['unionid'])
                            ->where('consumeid','=',$paras['consumeid'])
                            ->update($consumeupdate);
                        DB::table('feimi_rollback_log')->insertGetId($rollbacklog);

                    }
                    $totalrollback = $paras['value'];
                    $i = 0;
                    $ctime = time();
                    $rollbacklogs = array();
//                    var_dump($dispatchs);
//                    exit();
                    while($totalrollback>0 && $i < count($dispatchs) ){
                        $d = $dispatchs[$i];
                        $r = 0;
//                        var_dump($d->valueconsumed."---".$totalrollback);
                        if ($d->valueconsumed >= $totalrollback){
//                            var_dump('++++++++++++>');
                            $r = $totalrollback;
                            $totalrollback =  0;
                        }else{
//                            var_dump('------------>');
                            $r = $d->valueconsumed;;
                            $totalrollback = $totalrollback - $d->valueconsumed;
                            $i = $i + 1;
                        }
                        $rlog = array();
                        $rlog['rollbackid']= $logid;
                        $rlog['dispatchid']= $d->dispatchid;
                        $rlog['outputid']= $d->outputid;
                        $rlog['consumeid']= $d->consumeid;
                        $rlog['opcode']= $d->opcode;
                        $rlog['channelid'] = $d->channelid;
                        $rlog['outputopcode'] = $d->outputopcode;
                        $rlog['outputchannelid'] = $d->outputchannelid;
                        $rlog['operatorid'] = $d->operatorid;
                        $rlog['value'] = $r;
//                        $rlog['valuebefore'] = $o->leftvalue;
//                        $rlog['valueafter'] = $l;
                        $rlog['status'] = 0;
                        $rlog['create_at'] = $ctime ;
                        $rlog['update_at'] = $ctime;
                        $ouputlogupdate = array();
                        $ouputlogupdate['leftvalue'] = DB::raw('leftvalue + '.$r);
                        $ouputlogupdate['update_at'] = $ctime;
                        $rollbacklogs[] = $rlog;
//                        var_dump($rlog);
//                        var_dump($ouputlogupdate);
//                        exit();
                        DB::table('feimi_output_log')->where('outputid','=',$d->outputid)->update($ouputlogupdate);
                    }
//                    exit();
                    DB::table('feimi_rollback_dispatch_log')->insert($rollbacklogs);

//                    return BizResponse::successResponse();
                    $result = array();
                    $result['uid'] = $paras['unionid'];
                    $result['respInfo'] = '';
                    $result['status'] = 1;
                    $result['accountAmount'] = $userupdate['totalnum'];
                    $result['timestamp'] = time()*1000;
                    $result['sign'] = $this->generateSignature($result['uid']);
                    $logmsg = new LogMessage(__METHOD__,BizResponse::successResponseArray($result));
                    SeasLog::info($logmsg->logformat(),$logmsg->message());
                    return $result;
                }

            });

        }catch(\Exception $e) {
            $userfeimi = DB::table('feimi_user')
                ->where('unionid','=',$unionid)
                ->first();
            $result = array();
            $result['uid'] = $paras['unionid'];
            $result['respInfo'] = $e->getMessage();
            $result['status'] = 0;
            $result['accountAmount'] = 0;
            if($userfeimi){
                $result['accountAmount'] = $userfeimi->totalnum;
            }
            $result['timestamp'] = time()*1000;
            $result['sign'] = $this->generateSignature($result['uid']);
            $logmsg = new LogMessage(__METHOD__,BizResponse::successResponseArray($result));
            SeasLog::error($logmsg->logformat(),$logmsg->message());
            return $result;
        };

    }


    protected function checkFeimiStoreSignature($paras){
        if(!array_key_exists('uid',$paras) ||!array_key_exists('platOrderCode',$paras) || !array_key_exists('orderPoints',$paras) ||!array_key_exists('timestamp',$paras)||!array_key_exists('sign',$paras)   ){
            return false;
        }
        $appsecret = '9b293f40d962024f105a4f87544c0e4f';
        $sign = $paras['sign'];
        $decodedsign = base64_decode($sign);
        $fields = explode('|',$decodedsign);
        if(count($fields) != 3){
            return false;
        }
//        var_dump($fields);
//        exit();
//        $timestamp = $fields[1];
//            var_dump($token);
//            if(time()*1000 - intval($timestamp) != 600000000){
//                return BizResponse::failureResponse(BizError::SESSION_TIME_OUT_CODE,BizError::SESSION_TIME_OUT_MSG);
//            }
        $text =  $fields[0].'|'.$fields[1];
        $encryptedtext = hash_hmac('sha256',$text,$appsecret);
        $encryptedtext = base64_encode($text.'|'.$encryptedtext);
        if ($sign == $encryptedtext){
            return true;
        }

        return false;
    }

    protected function generateSignature($uid,$appsecret='9b293f40d962024f105a4f87544c0e4f'){
        $text = $uid.'|'.time()*1000;
        $hmac = hash_hmac('sha256',$text,$appsecret);
        $text = $text.'|'.$hmac;
        return base64_encode($text);
    }


    //
}
