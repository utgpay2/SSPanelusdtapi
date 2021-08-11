<?php
/**
 * Created by PhpStorm.
 * User: tonyzou
 * Date: 2018/9/24
 * Time: 下午9:24
 */

namespace App\Services\Gateway;

use App\Services\Auth;
use App\Models\Paylist;
use App\Services\View;
use App\Services\Config;
use Exception;

class Token188 extends AbstractPayment
{
    protected $sdk;

    public function __construct()
    {
        $this->sdk = new Token188SDK([
            'token188_url'      => Config::get('token188_url'),
            'token188_mchid'    => Config::get('token188_mchid'),
            'token188_key'      => Config::get('token188_key'),
        ]);
    }


    public function purchase($request, $response, $args)
    {
        $amount = (int)$request->getParam('amount');
        $user = Auth::getUser();
        if ($amount <= 0) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '订单金额错误：' . $amount
            ]);
        }

        $pl = new Paylist();
        $pl->userid     = $user->id;
        $pl->tradeno    = self::generateGuid();
        $pl->total      = $amount;
        $pl->save();

        try {
            $res = $this->sdk->pay([
                'trade_no'      => $pl->tradeno,
                'total_fee'     => $pl->total,
                'notify_url'    => $_ENV['baseUrl'] . '/payment/notify',
                'return_url'    => $_ENV['baseUrl'] . '/user/payment/return?out_trade_no=' . $pl->tradeno,
            ]);
			
			
            return $response->withJson([
                'ret'       => 1,
                'qrcode'    => $res,
                'amount'    => $pl->total,
                'pid'       => $pl->tradeno,
            ]);
        } catch (Exception $e) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '创建支付订单错误：' . $e->getMessage(),
            ]);
        }
    }

    public function purchase_maliopay($type, $price, $shopid=null)
    {
        
        $amount = $price;
        $user = Auth::getUser();

        $pl = new Paylist();
        $pl->userid = $user->id;
        $pl->tradeno = self::generateGuid();
        $pl->total = $amount;
        if ($shopid != 0) {
            $pl->shopid = $shopid;
        }
        $pl->save();
        
        try {
            $res = $this->sdk->pay([
                'trade_no'      => $pl->tradeno,
                'total_fee'     => $pl->total,
                'notify_url'    => trim(Config::get('baseUrl'), '/') . '/payment/notify/token188',
                'return_url'    => trim(Config::get('baseUrl'), '/') . '/user/payment/return?out_trade_no=' . $pl->tradeno,
            ]);
            return json_encode([
                'ret' => 1,
                'type' => 'url',
                'tradeno' => $pl->tradeno,
                'url' => $res,
            ]);
        } catch (Exception $e) {
            return json_encode([
                'ret' => 0,
                'msg' => '创建支付订单错误：' . $e->getMessage(),
            ]);
        }
    }

    public function notify($request, $response, $args)
    {
        $pid = $this->verifyAndGetPid();
        if($pid){
            $this->postPayment($pid, 'token188' . $pid);
            return $pid;
        }else{
            die("fail");
        }
        
        //die('success'); //The response should be 'success' only
        
    }

    public function verifyAndGetPid() {
        $content = file_get_contents('php://input');
		//$content = file_get_contents('php://input', 'r');
		$json_param = json_decode($content, true); //convert JSON into array
		$params=$json_param;
		
        if ($this->sdk->verify($params)) {
            return $params['outTradeNo'];
        }
        die('fail');
    }

    public function getPurchaseHTML()
    {
        return 0;
    }

    public function getReturnHTML($request, $response, $args)
    {
        return 0;
    }

    public function getStatus($request, $response, $args)
    {
        $p = Paylist::where('tradeno', $_POST['pid'])->first();
        return $response->withJson([
            'ret'       => 1,
            'result'    => $p->status,
        ]);
    }
	
	/**
     * 设置签名，详见签名生成算法
     * @param $secret
     * @param $params
     * @return array
     */
    public function GetSign($secret, $params)
    {
        $p=ksort($params);
        reset($params);

		if ($p) {
			$str = '';
			foreach ($params as $k => $val) {
				$str .= $k . '=' .  $val . '&';
			}
			$strs = rtrim($str, '&');
		}
		$strs .='&key='.$secret;

        $signature = md5($strs);

        //$params['sign'] = base64_encode($signature);
        return $signature;
    }
    public function msectime() {
		list($msec, $sec) = explode(' ', microtime());
		$msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
		return $msectime;
    }
    /**
     * 返回随机字符串
     * @param int $length
     * @return string
     */
    public static function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    private function _curlPost($url,$params=false,$signature,$ispost=0){
        
		$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); //设置超时
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt(
            $ch, CURLOPT_HTTPHEADER, array('token:'.$signature)
        );
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
	
	
}
