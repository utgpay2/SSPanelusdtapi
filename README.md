# SSPanel 188Pay 支付插件

适用于 SSPanel UIM 和 Malio 的 188Pay EPay 支付插件。插件会跳转到 188Pay 收银台，支付成功后由 188Pay 回调面板自动完成订单。

## 支持的支付类型

| 场景 | 支付类型 |
| --- | --- |
| USDT TRC20 | `usdt` |
| TRX | `trx` |
| 支付宝收款 | `fiat_alipay` |

未填写支付类型时默认使用 `usdt`。

## 安装

下载本仓库文件后，按目录覆盖到对应面板代码中：

- UIM：复制 `uim/` 目录内文件
- Malio：复制 `Malio/` 目录内文件

覆盖前建议先备份原站点同名文件。

## UIM 配置

修改 `config/.config.php`，找到 `$_ENV['payment_system']`，将值改为 `token188`，并加入：

```php
$_ENV['baseUrl'] = isset($_ENV['baseUrl']) ? $_ENV['baseUrl'] : $System_Config['baseUrl'];
$_ENV['token188_url'] = 'https://api2.188pay.top';
$_ENV['token188_mchid'] = '商户ID';
$_ENV['token188_key'] = '商户密钥';
$_ENV['token188_type'] = 'usdt';
```

`token188_type` 可不填，不填时默认 `usdt`。

## Malio 配置

修改 `config/.config.php`，找到 `$_ENV['payment_system']`，将值改为 `malio`，并加入：

```php
$_ENV['token188_url'] = 'https://api2.188pay.top';
$_ENV['token188_mchid'] = '商户ID';
$_ENV['token188_key'] = '商户密钥';
$_ENV['token188_type'] = 'usdt';
```

确认站点的 `baseUrl` 是公网可访问地址。

修改 `config/.malio_config.php`：

```php
$Malio_Config['mups_alipay'] = '';
$Malio_Config['mups_wechat'] = '';
$Malio_Config['mups_token188'] = 'token188';
```

如果要用支付宝通道，把 `token188_type` 改为：

```php
$_ENV['token188_type'] = 'fiat_alipay';
```

如果要用 TRX 通道，把 `token188_type` 改为：

```php
$_ENV['token188_type'] = 'trx';
```

## 回调要求

- `baseUrl` 必须是公网可访问地址，否则 188Pay 无法回调。
- UIM 回调地址为 `{baseUrl}/payment/notify`。
- Malio 聚合支付回调地址为 `{baseUrl}/payment/notify/token188`。
- 回调成功后必须返回纯文本 `success`。当前插件已按 188Pay 要求处理。

## 对接协议

插件使用 188Pay EPay 协议：

```text
GET https://api2.188pay.top/submit.php
  ?pid=商户ID
  &type=usdt
  &out_trade_no=面板订单号
  &money=订单金额
  &name=订单名称
  &notify_url=面板回调地址
  &return_url=面板支付返回页
  &sign=签名
  &sign_type=MD5
```

签名规则：

1. 排除 `sign`、`sign_type`，过滤空值
2. 参数名按 ASCII 升序排序
3. 拼接为 `key=value&key=value`
4. 末尾直接追加商户密钥，不加 `&key=`
5. 计算 MD5 小写值

`token188_url` 填 `https://api2.188pay.top` 即可，插件会自动拼接 `/submit.php`。如果已填完整的 `https://api2.188pay.top/submit.php`，插件也会兼容。

## 常见问题

### 支付后订单没有自动完成

检查 `baseUrl` 是否为公网地址，并确认服务器没有拦截 `/payment/notify` 或 `/payment/notify/token188`。

### 签名错误

确认商户密钥填写的是 188Pay 商户后台 API 密钥页面的密钥。旧版插件使用 `&key=` 签名，本版已改为 188Pay EPay 签名规则。

### 仍然跳到旧网关

确认配置里的 `token188_url` 已改为：

```text
https://api2.188pay.top
```

旧地址 `https://payapi.188pay.net/utg/pay/address` 已不适用于当前系统。

## 相关链接

- 官网：https://www.188pay.top
- EPay 文档：https://www.188pay.top/docs/epay
- Telegram：https://t.me/token188
