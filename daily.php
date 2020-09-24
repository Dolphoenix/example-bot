<?php

/**
 * This file is part of the PHP Telegram Bot example-bot package.
 * https://github.com/php-telegram-bot/example-bot/
 *
 * (c) PHP Telegram Bot Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * This file is used to set the webhook.
 */

// Load composer
require_once __DIR__ . '/vendor/autoload.php';

// 自定义class
require_once __DIR__ . '/m.php';

// Load all configuration options
/** @var array $config */
$config = require __DIR__ . '/config.php';

use Medoo\Medoo;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

$m = new m();
if (!isset($config['mysql'])) {
    throw new \RuntimeException('数据库配置有误');
}
$conf = $config['mysql'];
$db_config = [
    'database_type' => 'mysql',
    'database_name' => $conf['database'] ?? '',
    'server'        => $conf['host'] ?? '',
    'username'      => $conf['user'] ?? '',
    'password'      => $conf['password'] ?? '',
];

// Initialize
$db = new Medoo($db_config);
$rst = $m->insertDailyRes($db);
// $rst = "测试脚本自动执行！！！";

addLog('自动执行脚本：', $rst);

function addLog($event, $content)
{
    $log = new Logger('name');
    $log->pushHandler(new StreamHandler(__DIR__ . '/bot.log', Logger::WARNING));

    // $log->addWarning('Foo', ['test']);
    $log->addError($event, [$content]);
}

