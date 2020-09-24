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

use Longman\TelegramBot\Request;
use Medoo\Medoo;
use Monolog\Logger;

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

function findBets($db, $lottery_id, $lottery_date = '', $lottery_no = '')
{
    if (empty($lottery_id)) {
        return false;
    }
    $params['lottery_id'] = $lottery_id;
    if (!empty($lottery_date)) {
        $params['lottery_date'] = $lottery_date;
    }
    if (!empty($lottery_no)) {
        $params['lottery_no'] = $lottery_no;
    }

    $params['ORDER'] = ["id" => "DESC"];
    $params['LIMIT'] = 3;

    $table = 'lottery_bets';

    $rst = $db->select($table, '*', $params);

    if (empty($rst)) {
        return false;
    }
    $bets = [];

    foreach ($rst as $item) {
        $bets[] = $item['lottery_bets'];
    }

    return $bets;
}

$a = findBets($db, 'dlt', '2020-09-23', '20093');
var_export($a);
die();

$m = new m();

$telegram = new Longman\TelegramBot\Telegram($config['api_key'], $config['bot_username']);

$m_chat_id = 43709453;

sendMsg($m_chat_id, 'Just a test!');

function addLog($event, $content)
{
    $log = new Logger('name');
    $log->pushHandler(new StreamHandler(__DIR__ . '/bot.log', Logger::WARNING));

    // $log->addWarning('Foo', ['test']);
    $log->addError($event, [$content]);
}

function sendMsg($chat_id, $text)
{
    try {
        $data = [
            'chat_id' => $chat_id,
            'text'    => $text,
        ];

        Request::sendMessage($data);
    } catch (\Exception $e) {
        addLog('测试：', $e->getMessage());
    }
}

// $a = $m->insertDailyRes($db);
// var_dump($a);
// $uri = 'bonus';
// $query = [
//     'lottery_id' => 'ssq',
//     'lottery_res' => '04,06,07,12,18,19@09',
//     'lottery_no' => '',
// ];
// $m->requestApi($uri, $query);

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