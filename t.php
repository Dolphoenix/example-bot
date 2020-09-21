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

// Load all configuration options
/** @var array $config */
$config = require __DIR__ . '/config.php';

/*use Medoo\Medoo;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

$query_last = 'http://apis.juhe.cn/lottery/';

$client = new Client(['base_uri' => $query_last]);
$uri = 'query';
$query = [
    'lottery_id' => 'ssq',
    'lottery_no' => '',
    'key'        => '7a4beb6175a2c4dacf6cf9cab43bfe6f',
];

try {
    $response = $client->get($uri, ['query' => $query]);
    echo (string) $response->getBody();
} catch (RequestException $e) {
    echo $e->getMessage();
}*/

/*try {
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
    $database = new Medoo($db_config);

    var_export($database->info());
} catch (\RuntimeException $e) {
    echo $e->getMessage();
}*/