<?php
namespace Cevenquan\ChinaumsSDK;
/**
 * Created by PhpStorm.
 * User: Cevenquan
 * Date: 2019/6/14
 * Time: 16:29
 * 参数为银联测试
 */

class UnionClient
{
    //请求地址
    public $requestUrl = 'https://qr-test2.chinaums.com/netpay-portal/webpay/pay.do';

    //秘钥
    public $key = 'fcAmtnx7MwismjWNhNKdHC44mNXtnEQeJkRrhKJwyrW2ysRR';

    public $msgSrcId = '3194';

    //支付参数,如有需要自行添加
    public $params = [
        'mid' => '',    //商户号
        'tid' => '',    //终端号
        'instMid' => 'QRPAYDEFAULT',    //业务类型
        'msgId' => '',    //消息id
        'msgSrc' => '',    //消息来源
        'msgType' => '',    //消息类型
        'requestTimestamp' => '',    //报文请求时间:yyyy-MM-dd HH:mm:ss
        'billNo' => '',    //账单号
        'billDate' => '',    //账单日期：yyyy-MM-dd
        'billDesc' => '',    //账单描述
        'totalAmount' => '',    //支付总金额
        'merOrderId'=>'',
        'expireTime' => '',    //过期时间
        'notifyUrl' => '',    //支付结果通知地址
        'returnUrl' => '',    //网页跳转地址
        'qrCodeId' => '',    //二维码ID
        'systemId' => '',    //系统ID
        'secureTransaction' => '',    //担保交易标识
        'walletOption' => '',    //钱包选项
        'name' => '',    //实名认证姓名
        'mobile' => '',    //实名认证手机号
        'certType' => '',    //实名认证证件类型
        'certNo' => '',    //实名认证证件号
        'fixBuyer' => '',    //是否需要实名认证
        'limitCreditCard' => '',    //是否需要限制信用卡支付
        'signType' => 'md5',    //签名方式
        'sign' => '',    //签名
    ];

    /**
     * 请求
     * @return mixed
     * @throws Exception
     */
    public function request()
    {
        $params = $this->params;

        //去除空值
        $params_new=[];
        foreach ($params as $k=>$v){
            if($v === 0 || $v !== ''){
                $params_new[$k]=$v;
            }
        }

        $sign = $this->generateSign($params_new, $params['signType']);
        $this->setParams('sign', $sign);
        $params_new['sign']=$sign;
//        $params_new['merOrderId']=$params_new['billNo'];
        //curl模拟请求
//        var_dump($params_new);

        echo $this->requestUrl.'?'.http_build_query($params_new);
        echo " <script   language = 'javascript'  type = 'text/javascript' > ";
        echo " window.location.href = '".$this->requestUrl.'?'.http_build_query($params_new)."' ";
        echo " </script> ";
        exit;
//        $resp = $this->curl($this->requestUrl, $params_new);
//        var_dump($resp);exit;
        //准备验签
        $respList = json_decode($resp, true);
        if (!$this->verify($respList)) {
            ccnn_syslog('C扫B业务返回签名验证失败');
        }

        if ($respList['errCode'] != 'SUCCESS') {
            ccnn_syslog('C扫B业务'.$respList['errMsg']);
        }

        return $respList;
    }



    /**
     * 验证签名是否正确
     * @param $data
     * @return bool
     */
    function verify($data)
    {
        //返回参数生成sign
        $signType = empty($data['signType']) ? 'md5' : $data['signType'];
        $sign = $this->generateSign($data, $signType);
        //返回的sign
        $returnSign = $data['sign'];
        if ($returnSign != $sign) {
            return false;
        }

        return true;
    }

    /**
     * 设置参数
     * @param $key
     * @param $valve
     */
    public function setParams($key, $valve)
    {
        $this->params[$key] = $valve;
    }

    /**
     * 根绝类型生成sign
     * @param $params
     * @param string $signType
     * @return string
     */
    public function generateSign($params, $signType = 'md5')
    {
        return $this->sign($this->getSignContent($params), $signType);
    }

    /**
     * 生成signString
     * @param $params
     * @return string
     */
    public function getSignContent($params)
    {
        //sign不参与计算
        $params['sign'] = '';

        //去除空值
        //$params = array_filter($params);
        $params_new=[];
        foreach ($params as $k=>$v){
            if($v === 0 || $v !== ''){
                $params_new[$k]=$v;
            }
        }
        //排序
        ksort($params_new);
        $paramsToBeSigned = [];
        foreach ($params_new as $k => $v) {
            if(is_array($v)){
                $v= json_encode($v,JSON_UNESCAPED_UNICODE);//回调验签
            }
            if($v === true){
                $paramsToBeSigned[] = "{$k}=true";
            }else{
                $paramsToBeSigned[] = "{$k}={$v}";
            }
        }

        unset ($k, $v);

        //签名字符串
        $stringToBeSigned = implode('&', $paramsToBeSigned);
        $stringToBeSigned .= $this->key;
        return $stringToBeSigned;
    }

    /**
     * 生成签名
     * @param $data
     * @param string $signType
     * @return string
     */
    protected function sign($data, $signType = "md5")
    {
        $sign = hash($signType, $data);

        return strtoupper($sign);
    }


    /**
     * 用CURL模拟获取网页页面内容
     *
     * @param string $url     所要获取内容的网址
     * @param array  $data        所要提交的数据
     * @param string $proxy   代理设置
     * @param integer $expire 时间限制
     * @return string
     */
    private function curl($url, $data = array(), $charset= 'UTF-8', $proxy = null, $expire = 30) {

        //参数分析
        if (!$url) {
            return false;
        }
        if (!is_array($data)) {
            $data = (array)$data;
        }

        //分析是否开启SSL加密
        $ssl = substr($url, 0, 8) == 'https://' ? true : false;

        //读取网址内容
        $ch = curl_init();

        //设置代理
        if (!is_null($proxy)) {
            curl_setopt ($ch, CURLOPT_PROXY, $proxy);
        }

        //分析网址中的参数
        $paramUrl = http_build_query($data, '', '&');
        $extStr   = (strpos($url, '?') !== false) ? '&' : '?';
        $url      = $url . (($paramUrl) ? $extStr . $paramUrl : '');

        curl_setopt($ch, CURLOPT_URL, $url);

        if ($ssl) {
            // 对认证证书来源的检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            // 从证书中检查SSL加密算法是否存在
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        }

        $this_header = ["content-type: application/x-www-form-urlencoded;charset=".$charset];
        curl_setopt($ch,CURLOPT_HTTPHEADER,$this_header);

        //设置浏览器
        curl_setopt($ch, CURLOPT_USERAGENT, isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36');
        curl_setopt($ch, CURLOPT_HEADER, 0);

        //使用自动跳转
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $expire);

        $content = curl_exec($ch);
        curl_close($ch);

        return $content;
    }
}