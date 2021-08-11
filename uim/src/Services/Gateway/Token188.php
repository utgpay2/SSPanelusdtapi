<?php

namespace App\Services\Gateway;

use App\Services\Auth;
use App\Models\Paylist;
use App\Services\View;
use Exception;

class Token188 extends AbstractPayment
{
    protected $sdk;

    public function __construct()
    {
		
        $this->sdk = new Token188SDK([
            'token188_url'      => $_ENV['token188_url'],
            'token188_mchid'    => $_ENV['token188_mchid'],
            'token188_key'      => $_ENV['token188_key'],
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
                'notify_url'    => rtrim($_ENV['baseUrl'], '/') . '/payment/notify',
                'return_url'    => rtrim($_ENV['baseUrl'], '/') . '/payment/notify',
                'return_url'    => rtrim($_ENV['baseUrl'], '/') . '/user/payment/return?out_trade_no=' . $pl->tradeno,
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

    public function notify($request, $response, $args)
    {
        $content = file_get_contents('php://input');
        $params = json_decode($content, true); //convert JSON into array

        
        if ($this->sdk->verify($params)) {
            $pid = $params['outTradeNo'];
            $this->postPayment($pid, 'token188');

            die('success'); //The response should be 'success' only
        }

        die('fail');
    }


    public function getPurchaseHTML()
    {
        return View::getSmarty()->fetch('user/token188.tpl');
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
}
