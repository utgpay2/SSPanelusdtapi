# SSPanelusdtapi
SSPanel usdt支付插件 点对点个人对个人 没有中间商 无手续费 实时到账
### 网站配置
 - 下载SDK并解压到面板目录

### uim
```
修改 config/.config.php 找到 $_ENV['payment_system'] 将值改为 token188，并插入以下内容

$_ENV['baseUrl']        = isset($_ENV['baseUrl']) ? $_ENV['baseUrl'] : $System_Config['baseUrl'];
$_ENV['token188_url']   = 'https://api.token188.com/utg/pay/address';
$_ENV['token188_mchid'] = '商户ID';
$_ENV['token188_key']   = '商户密钥';
```
### malio
```
1. 下载SDK并解压到面板目录

2. 修改 config/.config.php 找到 $_ENV['payment_system'] 将值改为 malio，并插入以下内容

base_url需要设置

# token188支付 https://token188.com/
$_ENV['token188_url']         = 'https://api.token188.com/utg/pay/address';
$_ENV['token188_mchid']       = '商户ID';
$_ENV['token188_key']         = '商户密钥';
3. 修改 config/.malio_config.php

$Malio_Config['mups_alipay'] = '';   // Malio 聚合支付系统里面的 支付宝 要用的支付平台
$Malio_Config['mups_wechat'] = '';   // Malio 聚合支付系统里面的 微信支付 要用的支付平台
$Malio_Config['mups_token188'] = 'token188';   // Malio 聚合支付系统里面的 微信支付 要用的支付平台
```
 - 商户ID, 商户密钥  请到[TOKEN188](https://www.token188.com/) 官网注册获取.

### 产品介绍

 - [TOKEN188 USDT支付平台主页](https://www.token188.com)
 - [TOKEN188钱包](https://www.token188.com)（即将推出）
 - [商户平台](https://mar.token188.com/)
### 特点
 - 使用您自己的USDT地址收款没有中间商
 - 五分钟完成对接
 - 没有任何支付手续费

## 安装流程
1. 注册[TOKEN188商户中心](https://www.token188.com/manager)
2. 在商户中心添加需要监听的地址
3. 根据使用的不同面板进行回调设置(回调地址填写自己网站域名即可)


## 有问题和合作可以小飞机联系我们
 - telegram：@token188
