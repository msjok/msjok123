<?php
/**
 * Created by PhpStorm.
 * User: flyer
 * Date: 16/12/20
 * Time: ����3:35
 */

namespace App\Business;


class BizError
{
    const INVALID_APP_CODE = 10001;
    const INVALID_APP_MSG = "非法的飞米渠道";
    const INVALID_TOKEN_CODE = 10002;
    const INVALID_TOKEN_MSG = "非法的身份";
    const INVALID_TOKEN_CHECK_CODE = 10003;
    const INVALID_TOKEN_CHECK_MSG = "身份检测失败";
    const UID_NULL_CODE = 10004;
    const UID_NULL_MSG = "用户ID为空";
    const INVALID_SIGN_CODE = 10005;
    const INVALID_SIGN_MSG = "签名验证失败";
    const CHECK_SIGN_FAIL_CODE = 10006;
    const CHECK_SIGN_FAIL_MSG = "签名验证失败";
    const USER_NO_FEIMI_CODE = 10007;
    const USER_NO_FEIMI_MSG = "用户尚无飞米";
    const NO_ENOUGH_FEIMI_CODE = 20001;
    const NO_ENOUGH_FEIMI_MSG = "飞米数量不足";
    const OVER_DAYLIMIT_CODE = 20002;
    const OVER_DAYLIMIT_MSG = "超过用户单日最大限额,待审核";
    const OVER_TIMEMAX_LIMIT_CODE = 20003;
    const OVER_TIMEMAX_LIMIT_MSG = "超过单次最大限额,待审核";
    const OVER_DAY_USE_LIMIT_CODE = 20011;
    const OVER_DAY_USE_LIMIT_MSG = "超过用户单日最大限额";
    const OVER_TIME_USE_LIMIT_CODE = 20012;
    const OVER_TIME_USE_LIMIT_MSG = "超过单次最大限额";
    const SESSION_TIME_OUT_CODE = 20004;
    const SESSION_TIME_OUT_MSG = "会话过期";
    const OPID_NULL_CODE = 20005;
    const OPID_NULL_MSG = "操作代码为空";
    const OPVALUE_NULL_CODE = 20006;
    const OPVALUE_NULL__MSG = "飞米值空";
    const FEIMICHANNEL_NULL_CODE = 20007;
    const FEIMICHANNEL_NULL__MSG = "飞米渠道缺失";
    const INVALID_CHANNEL_OR_OPERATION_CODE = 20008;
    const INVALID_CHANNEL_OR_OPERATION_MSG = "非法的飞米渠道或者操作";
    const ADD_OUTPUT_FAIL_CODE = 20009;
    const ADD_OUTPUT_FAIL_MSG = "添加飞米失败";
    const FEIMI_OP_VALUE_NULL_CODE = 20010;
    const FEIMI_OP_VALUE_NULL_MSG = "飞米操作数值不能为空";
    const ADD_CONSUME_FAIL_CODE = 20013;
    const ADD__CONSUME__FAIL_MSG = "兑换失败";
    const ROLLBACK_CONSUME_INVALID_CODE = 20014;
    const ROLLBACK_CONSUME_INVALID_MSG = "不存在的飞米消耗记录";
    const ROLLBACK_CONSUME_FAIL_CODE = 20015;
    const ROLLBACK_CONSUME_FAIL_MSG = "返还飞米失败";
    const ROLLBACK_CONSUME_OVER_CODE = 20016;
    const ROLLBACK_CONSUME_OVER_MSG = "超过最大可返回飞米数量";
    const ROLLBACK_CONSUME_ALREADY_DONE_CODE = 20017;
    const ROLLBACK_CONSUME_ALREADY_DONE_MSG = "飞米消耗已回滚";
    const FEIMI_OUTPUT_INVALID_CODE = 20018;
    const FEIMI_OUTPUT_INVALID_MSG = "不存在的飞米产出记录";
    const USER_OPERATION_LOCKED_CODE = 20019;
    const USER_OPERATION_LOCKED_MSG = "用户飞米操作冲突,请稍后再试";
    const UPDATE_OPERATION_STATUS_NULL_CODE = 20020;
    const UPDATE_OPERATION_STATUS_NULL_MSG = "飞米操作状态为空";
    const UPDATE_OPERATION_STATUS_FAIL_CODE = 20021;
    const UPDATE_OPERATION_STATUS_FAIL_MSG = "飞米操作状态更改失败";
    const ADD_OPERATION_PARAMS_LACK_CODE = 20022;
    const ADD_OPERATION_PARAMS_LACK_MSG = "增加飞米操作失败,缺少参数";
    const ADD_OPERATION_FAIL_CODE = 20023;
    const ADD_OPERATION_FAIL_MSG = "添加飞米操作失败";
    const ORDER_STATUS_NULL_CODE = 20024;
    const ORDER_STATUS_NULL_MSG = "积分商城订单缺失订单状态";
    const ORDER_NOT_EXIST_CODE = 20025;
    const ORDER_NOT_EXIST_MSG = "积分商城订单不存在";
    const ROLLBACKVALUE_OVER_FLOW_CODE = 20026;
    const ROLLBACKVALUE_OVER_FLOW_MSG = "超过可回滚额度";
    const CONSUME_ORDER_ALREADY_EXIST_CODE = 20027;
    const CONSUME_ORDER_ALREADY_EXIST_MSG = "订单已存在";
    const PARAS_NULL_CODE = 20028;
    const PARAS_NULL_MSG = "非法的飞米操作";


}