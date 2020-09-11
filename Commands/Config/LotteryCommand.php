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
 * User "/date" command
 *
 * Shows the date and time of the location passed as the parameter.
 *
 * A Google API key is required for this command!
 * You can be set in your config.php file:
 * ['commands']['configs']['date'] => ['google_api_key' => 'your_google_api_key_here']
 */

namespace Longman\TelegramBot\Commands\UserCommands;

use GuzzleHttp\Client;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;

class LotteryCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'lottery';

    /**
     * @var string
     */
    protected $description = 'Be a better man.';

    /**
     * @var string
     */
    protected $usage = '/lottery';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * Main command execution
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute(): ServerResponse
    {
        $message = $this->getMessage();

        $chat_id  = $message->getChat()->getId();

        $text = $this->iamlucky();

        $data = [
            'chat_id' => $chat_id,
            'text'    => $text,
        ];

        return Request::sendMessage($data);
    }

    private function iamlucky()
    {
        $ssq_w = array(0, 2, 4);
        $dlt_w = array(1, 3, 6);
        $date_w = date('w');
        $lottery_info = '';
        $lucky_one = '';
        $lucky_two = '';
        $lucky_three = '';
        $end_info = '';

        if (in_array($date_w, $ssq_w)) {
            //ssq
            $lottery_info = '你好，帮忙买下双色球。';
            $lucky_one = '红球：04 06 07 12 18 19 蓝球：09';
            $lucky_two = $this->ssq();
            $lucky_three = '';
            $end_info = '再随机一注，谢谢。';
        } else if (in_array($date_w, $dlt_w)) {
            //dlt
            $lottery_info = '你好，帮忙买下大乐透。';
            $lucky_one = '红球：04 06 09 18 19 蓝球：07 12';
            $lucky_two = $this->dlt();
            $lucky_three = '红球：05 08 17 23 31 蓝球：09 03';
            $end_info = '都追加，谢谢。';
        }
        $text = $lottery_info . PHP_EOL . $lucky_one . PHP_EOL . $lucky_two . PHP_EOL . $lucky_three . PHP_EOL . $end_info;
        return $text;
    }

    private function dlt()
    {
        $red_no = $this->lucky(1, 35, 5);
        $blue_no = $this->lucky(1, 12, 2);
        $result = '红球：' . $red_no . '蓝球：' . $blue_no;
        return $result;
    }

    private function ssq()
    {
        $red_no = $this->lucky(1, 33, 6);
        $blue_no = $this->lucky(1, 15, 1);
        $result = '红球：' . $red_no . '蓝球：' . $blue_no;
        return $result;
    }

    private function lucky($begin, $end, $limit)
    {
        $result = '';
        $rand_array = range($begin, $end);
        shuffle($rand_array);
        $rand_array = array_slice($rand_array, 0, $limit);
        foreach ($rand_array as $num) {
            $rnow = sprintf("%02d", $num) . " ";
            $result .= $rnow;
        }
        return $result;
    }
}
