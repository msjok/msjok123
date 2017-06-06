<?php

namespace App\Http\Middleware;
use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use App\Business\BizError;
use App\Business\BizResponse;

class VerifyChannelToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $appkey = Input::get('appkey');
        $token = Input::get('token');
        $headers = $request->headers->all();

        if(!$appkey){
            if(array_key_exists('appkey',$headers)){
                $appkey = $headers['appkey'][0];
            }
        }

        if(!$token){
            if(array_key_exists('token',$headers)){
                $token = $headers['token'][0];
            }
        }

        if (!$appkey){
            return BizResponse::failureResponse(BizError::INVALID_APP_CODE,BizError::INVALID_APP_MSG);
        }
        if (!$token){
            return BizResponse::failureResponse(BizError::INVALID_TOKEN_CODE,BizError::INVALID_TOKEN_MSG);
        }
        $channels = Config::get('api.feimiChannel');
//        var_dump($channels);
//        var_dump($appkey);
//        var_dump($token);
//        exit();
        if(array_key_exists($appkey,$channels)){
            $appsecret = $channels[$appkey]['channelsecret'];
        }else{
            return BizResponse::failureResponse(BizError::INVALID_APP_CODE,BizError::INVALID_APP_MSG);
        }
        if (!$appsecret){
            return BizResponse::failureResponse(BizError::INVALID_APP_CODE,BizError::INVALID_APP_MSG);
        }
        $tokenbase64decoded = base64_decode($token);
//        var_dump($tokenbase64decoded);
//        var_dump($appkey);
//        var_dump($appsecret);
//        exit();
        if($token){
            $fields = explode('|',$tokenbase64decoded);
            if(count($fields) != 3){
                return BizResponse::failureResponse(BizError::INVALID_TOKEN_CODE,BizError::INVALID_TOKEN_MSG);
            }
            $timestamp = $fields[1];
//            var_dump($token);
//            if(time()*1000 - intval($timestamp) != 600000000){
//                return BizResponse::failureResponse(BizError::SESSION_TIME_OUT_CODE,BizError::SESSION_TIME_OUT_MSG);
//            }
            $text =  $fields[0].'|'.$fields[1];
            $encryptedtext = hash_hmac('sha256',$text,$appsecret);
            $encryptedtext = base64_encode($text.'|'.$encryptedtext);
//            var_dump($encryptedtext);
//            var_dump($token);
//            exit();
            if ($token != $encryptedtext){
                return BizResponse::failureResponse(BizError::INVALID_TOKEN_CODE,BizError::INVALID_TOKEN_MSG);
            }
        }else{
            return BizResponse::failureResponse(BizError::INVALID_TOKEN_CODE,BizError::INVALID_TOKEN_MSG);
        }
        return $next($request);
    }


}
