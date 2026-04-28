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
            'token188_url' => isset($_ENV['token188_url']) ? $_ENV['token188_url'] : '',
            'token188_mchid' => isset($_ENV['token188_mchid']) ? $_ENV['token188_mchid'] : '',
            'token188_key' => isset($_ENV['token188_key']) ? $_ENV['token188_key'] : '',
            'token188_type' => isset($_ENV['token188_type']) ? $_ENV['token188_type'] : '',
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

    public function notify($request, $response, $args)
    {
        $params = $this->parseNotifyParams($request);
        if ($this->sdk->verify($params)) {
            $pid = $params['out_trade_no'];
            $this->postPayment($pid, 'token188');

            die('success');
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
}
