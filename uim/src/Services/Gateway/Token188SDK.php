<?php

namespace App\Services\Gateway;

class Token188SDK {
    const DEFAULT_GATEWAY_URL = 'https://api2.188pay.top';
    const DEFAULT_PAYMENT_TYPE = 'usdt';

    protected $config = [];

    public function __construct($config) {
        $this->config = $config;
    }

    public function pay($order) {
        $params = [
            'pid' => $this->configValue('token188_mchid'),
            'type' => $this->paymentType(),
            'out_trade_no' => $order['trade_no'],
            'money' => sprintf('%.2f', (float)$order['total_fee']),
            'name' => $order['trade_no'],
            'notify_url' => $order['notify_url'],
            'return_url' => $order['return_url'],
        ];

        $params['sign'] = self::getSign($this->configValue('token188_key'), $params);
        $params['sign_type'] = 'MD5';

        return $this->buildSubmitUrl($params);
    }

    public function verify($params) {
        if (!is_array($params) || empty($params['sign'])) {
            return false;
        }

        $expectedSign = self::getSign($this->configValue('token188_key'), $params);
        if (!self::hashEquals($expectedSign, strtolower(trim($params['sign'])))) {
            return false;
        }

        $tradeStatus = isset($params['trade_status']) ? $params['trade_status'] : '';
        if ($tradeStatus !== 'TRADE_SUCCESS') {
            return false;
        }

        return !empty($params['out_trade_no']) && !empty($params['trade_no']);
    }

    public static function getSign($secret, $params)
    {
        $data = [];
        foreach ($params as $key => $value) {
            if ($key === 'sign' || $key === 'sign_type') {
                continue;
            }
            if ($value === null || $value === '') {
                continue;
            }
            $data[$key] = $value;
        }

        ksort($data, SORT_STRING);

        $pairs = [];
        foreach ($data as $key => $value) {
            $pairs[] = $key . '=' . $value;
        }

        // 188Pay EPay 模式：参数串后直接追加密钥，不带 &key= 前缀。
        return strtolower(md5(implode('&', $pairs) . trim($secret)));
    }

    private function paymentType()
    {
        $type = $this->configValue('token188_type');
        return $type === '' ? self::DEFAULT_PAYMENT_TYPE : $type;
    }

    private function buildSubmitUrl($params)
    {
        $baseUrl = $this->configValue('token188_url', self::DEFAULT_GATEWAY_URL);
        $baseUrl = rtrim($baseUrl, '/');

        if (preg_match('/\/submit$/i', $baseUrl) && !preg_match('/\/epay\/submit$/i', $baseUrl)) {
            $baseUrl .= '.php';
        } elseif (!preg_match('/\/(submit\.php|epay\/submit|epay\/submit\.php)$/i', $baseUrl)) {
            $baseUrl .= '/submit.php';
        }

        return $baseUrl . '?' . http_build_query($params);
    }

    private function configValue($key, $default = '')
    {
        if (!isset($this->config[$key])) {
            return $default;
        }

        $value = trim((string)$this->config[$key]);
        return $value === '' ? $default : $value;
    }

    private static function hashEquals($known, $user)
    {
        if (function_exists('hash_equals')) {
            return hash_equals($known, $user);
        }

        if (strlen($known) !== strlen($user)) {
            return false;
        }

        $result = 0;
        for ($i = 0; $i < strlen($known); $i++) {
            $result |= ord($known[$i]) ^ ord($user[$i]);
        }

        return $result === 0;
    }
}
