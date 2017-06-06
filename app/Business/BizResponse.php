<?php
/**
 * Created by PhpStorm.
 * User: flyer
 * Date: 16/12/20
 * Time: ����4:28
 */

namespace App\Business;
use Symfony\Component\HttpFoundation\Response;


class BizResponse
{
   static function successResponse($data=array()){
        return response()->json(array('success'=>true,'code'=>0,'message'=>'','data'=>$data));
    }

   static function failureResponse($code = 0,$msg = '',$data=array()){
        return response()->json(array('success'=>false,'code'=>$code,'message'=>$msg,'data'=>$data));
    }
    static function successResponseArray($data=array()){
        return array('success'=>true,'code'=>0,'message'=>'','data'=>$data);
    }
    static function failureResponseArray($code = 0,$msg = '',$data=array()){
        return array('success'=>false,'code'=>$code,'message'=>$msg,'data'=>$data);
    }

}