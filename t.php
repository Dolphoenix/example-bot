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

$m = new M();
$telegram = new Longman\TelegramBot\Telegram($config['api_key'], $config['bot_username']);

$m_chat_id = 43709453;
list($text, $lottery_id, $bets) = $m->luckyRush();

sendMsg($m_chat_id, $text);
echo "233333333333333333333333333";

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
