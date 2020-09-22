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

use Medoo\Medoo;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// 创建日志频道
$log = new Logger('name');
$log->pushHandler(new StreamHandler(__DIR__ . '/bot.log', Logger::WARNING));
// 添加日志记录
$log->addWarning('Foo', ['test']);
$log->addError('Bar', ['fafdaf']);

/*use GuzzleHttp\Client;
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

    $insert_data = '[
			{
				"lottery_id":"ssq",
				"lottery_res":"01,06,12,18,22,24,03",
				"lottery_no":"20092",
				"lottery_date":"2020-09-20",
				"lottery_exdate":"2020-11-18",
				"lottery_sale_amount":"394,714,558",
				"lottery_pool_amount":"1,113,718,036"
			},
			{
				"lottery_id":"ssq",
				"lottery_res":"01,09,11,12,16,19,16",
				"lottery_no":"20091",
				"lottery_date":"2020-09-17",
				"lottery_exdate":"2020-11-15",
				"lottery_sale_amount":"362,307,424",
				"lottery_pool_amount":"1,088,865,952"
			}
		]';

    $insert_data = @json_decode($insert_data, true);
    array_multisort(array_column($insert_data, 'lottery_no'), SORT_ASC, $insert_data);

    $last_id = $database->insert("lottery_history_ssq", $insert_data);

    var_export($last_id);
    // var_export($database->info());
} catch (\RuntimeException $e) {
    echo $e->getMessage();
}*/