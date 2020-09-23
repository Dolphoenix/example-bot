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
$m->insertDailyRes($db);
