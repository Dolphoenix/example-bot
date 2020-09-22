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
use GuzzleHttp\Exception\RequestException;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use Medoo\Medoo;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

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
    protected $version = '2.0.0';

    /**
     * @var null
     */
    protected $db = null;

    /**
     * Main command execution
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute(): ServerResponse
    {
        // 数据库配置
        $db_config = $this->getConfig('credentials');

        // Initialize
        $db = new Medoo($db_config);

        list($text, $lottery_id, $bets) = $this->luckyRush();

        $lottery_no = $this->getLotteryNo($lottery_id);

        $this->saveBets($db, $lottery_id, $lottery_no, $bets);

        return $this->sendMsg($text);
    }

    private function sendMsg($text)
    {
        $message = $this->getMessage();

        $chat_id = $message->getChat()->getId();

        $data = [
            'chat_id' => $chat_id,
            'text'    => $text,
        ];

        return Request::sendMessage($data);
    }

    private function addLog($event, $content)
    {
        $log = new Logger('name');
        $log->pushHandler(new StreamHandler(__DIR__ . '/lottery.log', Logger::WARNING));

        // $log->addWarning('Foo', ['test']);
        $log->addError($event, [$content]);
    }

    private function luckyRush()
    {
        $ssq_w = array(0, 2, 4);
        $dlt_w = array(1, 3, 6);
        $date_w = date('w');
        $lottery_info = '';
        $lucky_one = '';
        $lucky_two = '';
        $lucky_three = '';
        $end_info = '';

        $bets = [];
        $lottery_id = '';
        if (in_array($date_w, $ssq_w)) {
            //ssq
            $lottery_info = '你好，帮忙买下双色球。';
            $lucky_one = '红球：04 06 07 12 18 19 蓝球：09';
            $bets[] = '04,06,07,12,18,19,09';
            list($red_no, $blue_no) = $this->ssq();
            $lucky_two = '红球：' . $red_no . '蓝球：' . $blue_no;
            $bets[] = str_replace(' ', ',', trim($red_no . $blue_no));
            list($red_no, $blue_no) = $this->ssq();
            $lucky_three = '红球：' . $red_no . '蓝球：' . $blue_no;
            $bets[] = str_replace(' ', ',', trim($red_no . $blue_no));
            $end_info = '';
            $lottery_id = 'ssq';
        } else {
            if (in_array($date_w, $dlt_w)) {
                //dlt
                $lottery_info = '你好，帮忙买下大乐透。';
                $lucky_one = '红球：04 06 09 18 19 蓝球：07 12';
                $bets[] = '04,06,09,18,19,07,12';
                list($red_no, $blue_no) = $this->dlt();
                $bets[] = str_replace(' ', ',', trim($red_no . $blue_no));
                $lucky_two = '红球：' . $red_no . '蓝球：' . $blue_no;
                list($red_no, $blue_no) = $this->dlt();
                $bets[] = str_replace(' ', ',', trim($red_no . $blue_no));
                $lucky_three = '红球：' . $red_no . '蓝球：' . $blue_no;
                $end_info = '都追加，谢谢。';
                $lottery_id = 'dlt';
            }
        }
        $text = $lottery_info . PHP_EOL . $lucky_one . PHP_EOL . $lucky_two . PHP_EOL . $lucky_three . PHP_EOL . $end_info;
        return [$text, $lottery_id, $bets];
    }

    private function dlt()
    {
        $red_no = $this->lucky(1, 35, 5);
        $blue_no = $this->lucky(1, 12, 2);
        return [$red_no, $blue_no];
    }

    private function ssq()
    {
        $red_no = $this->lucky(1, 33, 6);
        $blue_no = $this->lucky(1, 15, 1);
        return [$red_no, $blue_no];
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

    private function saveBets($db, $lottery_id, $lottery_no, $lottery_bets)
    {
        if (is_null($db) || empty($lottery_id) || empty($lottery_no) || empty($lottery_bets)) {
            return false;
        }
        if (!is_array($lottery_bets)) {
            $lottery_bets = array($lottery_bets);
        }

        $table = 'lottery_bets';
        $now = time();
        $tmp = [
            'lottery_id'   => $lottery_id,
            'lottery_no'   => $lottery_no,
            'lottery_date' => date('Y-m-d', $now),
            'created_at'   => $now,
            'updated_at'   => $now,
        ];
        $insert_data = [];
        foreach ($lottery_bets as $item) {
            $tmp['lottery_bets'] = $item;
            $insert_data[] = $tmp;
        }

        return $db->insert($table, $insert_data);
    }

    private function getLotteryNo($lottery_id, $lottery_no = '')
    {
        $res = $this->queryLotteryRst($lottery_id, $lottery_no);
        if (!$res) {
            return false;
        }
        $lottery_date = $res['lottery_date'] ?? '';
        $lottery_no = $res['lottery_no'] ?? '';
        if (empty($lottery_date) || empty($lottery_no)) {
            return false;
        }

        if (date('y') !== date('y', strtotime($lottery_date))) {
            return (int)(date('y') . '001');
        } else {
            return (int)$lottery_no + 1;
        }
    }

    private function queryLotteryRst($lottery_id, $lottery_no = '')
    {
        $query_last = 'http://apis.juhe.cn/lottery/';

        $client = new Client(['base_uri' => $query_last]);
        $uri = 'query';
        $query = [
            'lottery_id' => $lottery_id,
            'lottery_no' => $lottery_no,
            'key'        => '7a4beb6175a2c4dacf6cf9cab43bfe6f',
        ];

        try {
            $response = $client->get($uri, ['query' => $query]);
            $res = @json_decode((string)$response->getBody(), true);
            if (!$res || (int)$res['error_code'] > 0) {
                echo '请求响应内容有误';
                return false;
            }
            return $res['result'];
        } catch (RequestException $e) {
            echo $e->getMessage();
            return false;
        }
    }
}
