<?php



class Base
{
    const APPID = '';
    const APPSECRET = '';
    const MCHID = '';
    const KEY = '';
    const UNURL = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
    const REDURL = 'https://mi.com';//支付之后跳转页面

    //生成签名
    public function getSign($arr){
        //去除空值
        $arr = array_filter($arr);
        if(isset($arr['sign'])){
            unset($arr['sign']);
        }
        //按照键名字典排序
        ksort($arr);
        //生成url格式的字符串
       $str = $this->arrToUrl($arr) . '&key=' . self::KEY;
       return strtoupper(md5($str));
    }
    //获取带签名的数组
    public function setSign($arr){
        $arr['sign'] = $this->getSign($arr);;
        return $arr;
    }
    public function arrToUrl($arr){
        return urldecode(http_build_query($arr));
    }
    //验证签名
    public function chekSign($arr){
        $sign = $this->getSign($arr);
        if($sign == $arr['sign']){
            return true;
        }else{
            return false;
        }
    }
    //获取openid
    public function getOpenId(){
        if(isset($_SESSION['openid'])){
            return $_SESSION['openid'];
        }else{
            //1.用户访问一个地址 先获取到code
           
            if(!isset($_GET['code'])){
                //print_r($_SERVER);
                $redurl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                $url = self::CODEURL . "appid=" .self::APPID ."&redirect_uri={$redurl}&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect";
                //构建跳转地址 跳转
                header("location:{$url}");
            }else{
                //2.根据code获取到openid
                //调用接口获取openid
                $openidurl = self::OPENIDURL . "appid=" . self::APPID . "&secret=".self::SECRET . "&code=" . $_GET['code'] . "&grant_type=authorization_code";
                $data = file_get_contents($openidurl);
                $arr = json_decode($data,true);
                $_SESSION['openid'] = $arr['openid'];                
                return $_SESSION['openid'];
            }
        }
    }
    //调用统一下单api
    public function unifiedOrder($oid,$type = false){
        /**
         * 1.构建原始数据
         * 2.加入签名
         * 3.将数据转换为XML
         * 4.发送XML格式的数据到接口地址
         */
        $params = [
            'appid'=> self::APPID,
            'mch_id'=> self::MCHID,
            'nonce_str'=>md5(time()),
            'body'=>'支付主体',
            'out_trade_no'=>$oid,
            'total_fee'=> 1,
            'spbill_create_ip'=>$_SERVER['REMOTE_ADDR'],
            'notify_url'=> 'https://XXXX.com/api/wxpay/notify_url',
            'trade_type'=>'JSAPI',
            'product_id'=>$oid,
            // 'openid'    => $this->getOpenId()
        ];
        if($type == 'h5'){
            $params['trade_type'] = 'MWEB';
        }else{
             $params['openid'] =$this->getOpenId();
        }
       $params = $this->setSign($params); 
       $xmldata = $this->ArrToXml($params);
       $this->logs('log.txt', $xmldata);
       $resdata = $this->postXml(self::UNURL, $xmldata);
       $arr = $this->XmlToArr($resdata);
       return $arr;
    }
     //调用查询订单接口
    public function sOrder($oid){
       //构建数据
        $params = [
            'appid'=> self::APPID,
            'mch_id'=> self::MCHID,
            'out_trade_no' => $oid,
            'nonce_str'=>md5(time()),
            'sign_type' => 'MD5'
        ];
       
       $params = $this->setSign($params); 
       $xmldata = $this->ArrToXml($params);
     
       $resdata = $this->postXml(self::SEORDERURL, $xmldata);
       $arr = $this->XmlToArr($resdata);
       return $arr;
    }
    //获取prepayid
    public function getPrepayId($oid){
        $arr = $this->unifiedOrder($oid);
        return $arr['prepay_id'];
    }
    //获取公众号支付所需要的json数据
    public function getJsParams($prepay_id){
        $params = [
            'appId' => self::APPID,
            'timeStamp' =>time(),
            'nonceStr' => md5(time()),
            'package' =>'prepay_id=' . $prepay_id,     
            'signType' =>'MD5',
     //       'paySign' => $this->getSign($params)
        ];
        $params['paySign'] = $this->getSign($params);
        return json_encode($params);
    }
    //数组转xml
    public function ArrToXml($arr)
    {
            if(!is_array($arr) || count($arr) == 0) return '';

            $xml = "<xml>";
            foreach ($arr as $key=>$val)
            {
                    if (is_numeric($val)){
                            $xml.="<".$key.">".$val."</".$key.">";
                    }else{
                            $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
                    }
            }
            $xml.="</xml>";
            return $xml; 
    }
    public function XmlToArr($xml)
    {	
        if($xml == '') return '';
        libxml_disable_entity_loader(true);
        $arr = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);		
        return $arr;
    }
    
    public function logs($filename,$data){
        file_put_contents('./logs/' . $filename, $data);
    }
    public function postXml($url,$postfields){
       
        $ch = curl_init();
        $params[CURLOPT_URL] = $url;    //请求url地址
        $params[CURLOPT_HEADER] = false; //是否返回响应头信息
        $params[CURLOPT_RETURNTRANSFER] = true; //是否将结果返回
        $params[CURLOPT_FOLLOWLOCATION] = true; //是否重定向
        $params[CURLOPT_POST] = true;
        $params[CURLOPT_POSTFIELDS] = $postfields;
        $params[CURLOPT_SSL_VERIFYPEER] = false;
	$params[CURLOPT_SSL_VERIFYHOST] = false;
        curl_setopt_array($ch, $params); //传入curl参数
        $content = curl_exec($ch); //执行
        curl_close($ch); //关闭连接
        return $content;
    }
    //获取post过来的数据
    public function getPost(){
        return file_get_contents('php://input');
    }
}