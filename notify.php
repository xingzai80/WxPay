<?php
include './Base.php';
/*
 *  1.获取通知数据 ->转换为数组
 *  2.验证签名 (使用以前的方法)
 *  3.验证业务结果 (return_code 和 result_code)
 *  4.验证订单号和金额 (out_trade_no total_fee)
 *  5.记录日志 修改订单状态 给用户发货
 */
class Notify extends Base
{
    public function __construct() {
        $xmlData = $this->getPost();
        $arr = $this->XmlToArr($xmlData);
        if($this->chekSign($arr)){
            if($arr['return_code'] == 'SUCCESS' && $arr['result_code'] == 'SUCCESS'){
                //生产环境需要根据订单号来查询价格
                if($arr['total_fee'] == 2){
                    $this->logs('stat.txt', '交易成功!'); //更改订单状态
                    $returnParams = [
                        'return_code' => 'SUCCESS',
                        'return_msg'  => 'OK'
                    ];
                    echo $this->ArrToXml($returnParams);
                }else{
                    $this->logs('stat.txt', '金额有误!');
                }
            }else{
                $this->logs('stat.txt', '业务结果不正确!');
            }
        }else{
            $this->logs('stat.txt', '签名失败!');
        }
    }
}
$obj = new Notify();