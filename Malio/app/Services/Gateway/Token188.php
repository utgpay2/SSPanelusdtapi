<?php

namespace App\Services\Gateway;

use App\Services\Auth;
use App\Models\Paylist;
use App\Services\Config;
use Exception;

class Token188 extends AbstractPayment
{
    protected $sdk;

    public function __construct()
    {
        $this->sdk = new Token188SDK([
            'token188_url'   => Config::get('token188_url'),
            'token188_mchid' => Config::get('token188_mchid'),
            'token188_key'   => Config::get('token188_key'),
            'token188_type'  => Config::get('token188_type'),
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
        $pl->userid = $user->id;
        $pl->tradeno = self::generateGuid();
        $pl->total = $amount;
        $pl->save();

        try {
            $baseUrl = rtrim($_ENV['baseUrl'], '/');
            $res = $this->sdk->pay([
                'trade_no' => $pl->tradeno,
                'total_fee' => $pl->total,
                'notify_url' => $baseUrl . '/payment/notify',
                'return_url' => $baseUrl . '/user/payment/return?out_trade_no=' . $pl->tradeno,
            ]);

            return $response->withJson([
                'ret' => 1,
                'qrcode' => $res,
                'amount' => $pl->total,
                'pid' => $pl->tradeno,
            ]);
        } catch (Exception $e) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '创建支付订单错误：' . $e->getMessage(),
            ]);
        }
    }

    public function purchase_maliopay($type, $price, $shopid = null)
    {
        $user = Auth::getUser();

        $pl = new Paylist();
        $pl->userid = $user->id;
        $pl->tradeno = self::generateGuid();
        $pl->total = $price;
        if ($shopid != 0) {
            $pl->shopid = $shopid;
        }
        $pl->save();

        try {
            $baseUrl = rtrim(Config::get('baseUrl'), '/');
            $res = $this->sdk->pay([
                'trade_no' => $pl->tradeno,
                'total_fee' => $pl->total,
                'notify_url' => $baseUrl . '/payment/notify/token188',
                'return_url' => $baseUrl . '/user/payment/return?out_trade_no=' . $pl->tradeno,
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
        $params = $this->parseNotifyParams($request);
        $pid = $this->verifyAndGetPid($params);

        if (!$pid) {
            die('fail');
        }

        if ($this->isMalioPayNotify($request)) {
            return $pid;
        }

        $this->postPayment($pid, 'token188' . $pid);
        die('success');
    }

    public function verifyAndGetPid($params = null)
    {
        if ($params === null) {
            $params = array_merge($_GET, $_POST);
        }

        if ($this->sdk->verify($params)) {
            return $params['out_trade_no'];
        }

        return false;
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
            'ret' => 1,
            'result' => $p->status,
        ]);
    }

    private function parseNotifyParams($request)
    {
        $params = [];
        if (method_exists($request, 'getParams')) {
            $params = $request->getParams();
        }
        if ((!is_array($params) || empty($params)) && method_exists($request, 'getQueryParams')) {
            $params = $request->getQueryParams();
        }
        if ((!is_array($params) || empty($params)) && method_exists($request, 'getParsedBody')) {
            $params = $request->getParsedBody();
        }
        if (!is_array($params) || empty($params)) {
            $params = array_merge($_GET, $_POST);
        }
        if (!is_array($params) || empty($params)) {
            $content = file_get_contents('php://input');
            $json = json_decode($content, true);
            $params = is_array($json) ? $json : [];
        }

        return $params;
    }

    private function isMalioPayNotify($request)
    {
        if (!method_exists($request, 'getUri')) {
            return false;
        }

        $path = $request->getUri()->getPath();
        return strpos($path, '/payment/notify/token188') !== false;
    }
}
