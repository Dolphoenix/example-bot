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
        $receiveMsg = trim($this->getMessage()->getText(true));
        if ($receiveMsg !== '' && 'bonus' === $receiveMsg) {
            return $this->replyToChat('I can do it!');
        } elseif ($receiveMsg !== '' && (0 === strpos($receiveMsg, 'dlt') || 0 === strpos($receiveMsg, 'ssq'))) {
            // 格式“dlt=2020-09-24=200093“
            $arr = explode('=', $receiveMsg);
            $lottery_id = $arr[0] ?? '';
            $lottery_date = $arr[1] ?? '';
            $lottery_no = $arr[2] ?? '';

            $text = $this->checkBonus($lottery_id, $lottery_date, $lottery_no);

            if (!empty($text)) {
                return $this->replyToChat($text);
            } else {
                return $this->replyToChat('什么都没查到……');
            }
        } elseif ($receiveMsg !== '') {
            return $this->replyToChat('I can\'t do it!');
        }

        list($text, $lottery_id, $bets) = $this->luckyRush();

        $lottery_no = $this->getLotteryNo($lottery_id);

        $this->saveBets($lottery_id, $lottery_no, $bets);

        return $this->sendMsg($text);
    }

    /**
     * 发送消息
     *
     * @param $text
     * @return ServerResponse
     * @throws TelegramException
     */
    private function sendMsg($text): ServerResponse
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
        $lottery_info = '';
        $lucky_one = '';
        $lucky_two = '';
        $lucky_three = '';
        $end_info = '';

        $bets = [];
        $lottery_id = $this->getLotteryId();
        if ('ssq' === $lottery_id) {
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
        } elseif ('dlt' === $lottery_id) {
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

    /**
     * 保存bet内容
     * @param $lottery_id
     * @param $lottery_no
     * @param $lottery_bets
     * @return false
     */
    private function saveBets($lottery_id, $lottery_no, $lottery_bets)
    {
        if (empty($lottery_id) || empty($lottery_no) || empty($lottery_bets)) {
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

        // 数据库配置
        $db_config = $this->getConfig('credentials');

        // Initialize
        $db = new Medoo($db_config);

        return $db->insert($table, $insert_data);
    }

    /**
     * 获取当前期号
     * @param $lottery_id
     * @param string $lottery_no
     * @return false|int
     */
    private function getLotteryNo($lottery_id, $lottery_no = '')
    {
        $uri = 'query';
        $params = [
            'lottery_id' => $lottery_id,
            'lottery_no' => $lottery_no,
        ];
        $res = $this->requestApi($uri, $params);
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

    /**
     * 请求聚合api
     * @param $uri
     * @param array $params
     * @return false|mixed
     */
    private function requestApi($uri, $params = [])
    {
        $query_last = 'http://apis.juhe.cn/lottery/';
        $juhe_key = '7a4beb6175a2c4dacf6cf9cab43bfe6f';
        $params['key'] = $juhe_key;

        $client = new Client(['base_uri' => $query_last]);

        try {
            $response = $client->get($uri, ['query' => $params]);
            $res = @json_decode((string)$response->getBody(), true);
            if (!$res || (int)$res['error_code'] > 0) {
                $this->addLog('接口请求，响应内容有误：', '====>' . $res['reason'] . '<====');
                return false;
            }
            return $res['result'] ?? [];
        } catch (RequestException $e) {
            $this->addLog('接口请求，响应内容有误：', '====>' . $e->getMessage() . '<====');
            return false;
        }
    }

    private function getLotteryId()
    {
        $ssq_w = array(0, 2, 4);
        $dlt_w = array(1, 3, 6);
        $date_w = date('w');

        if (in_array($date_w, $ssq_w)) {
            return 'ssq';
        } elseif (in_array($date_w, $dlt_w)) {
            return 'dlt';
        } else {
            return '';
        }
    }

    private function checkBonus($lottery_id, $lottery_date = '', $lottery_no = '')
    {
        $bets = $this->findBets($lottery_id, $lottery_date, $lottery_no);
        $intRed = 'ssq' === $lottery_id ? 6 : 5;
        $intBlue = 'ssq' === $lottery_id ? 1 : 2;

        $text = '';
        for ($i = 0; $i < count($bets); $i++) {
            $params = [
                'lottery_id'  => $lottery_id,
                'lottery_res' => $bets[$i],
                'lottery_no'  => $lottery_no,
            ];
            $rst = $this->requestApi('bonus', $params);
            if (0 === $i) {
                $text .= '开奖号码：' . $rst['real_lottery_res'] . PHP_EOL;
            }

            $text .= "第" . ($i + 1) . "个投注：" . $rst['lottery_res'] . PHP_EOL;
            $text .= '红球命中：' . $rst['hit_red_ball_num'] . "个（{$intRed}）" . PHP_EOL;
            $text .= '蓝球命中：' . $rst['hit_blue_ball_num'] . "个（{$intBlue}）" . PHP_EOL;
            if (1 === (int)$rst['is_prize']) {
                $content = "{$rst['prize_msg']}，{$rst['lottery_prize'][0]['prize_name']}，奖金：{$rst['lottery_prize'][0]['prize_money']}元";
            } else {
                $content = '下次努力!';
            }
            $text .= $content . PHP_EOL;
        }
        return $text;
    }

    private function findBets($lottery_id, $lottery_date = '', $lottery_no = '')
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

        // 数据库配置
        $db_config = $this->getConfig('credentials');

        // Initialize
        $db = new Medoo($db_config);

        $rst = $db->select($table, '*', $params);

        if (empty($rst)) {
            return false;
        }
        $bets = [];

        foreach ($rst as $item) {
            $nth = $lottery_id === 'ssq' ? 5 : 4;
            $bets[] = $this->strReplaceNth(',', '@', $item['lottery_bets'], $nth);
        }

        return $bets;
    }

    private function strReplaceNth($search, $replace, $subject, $nth)
    {
        $found = preg_match_all('/' . preg_quote($search) . '/', $subject, $matches, PREG_OFFSET_CAPTURE);
        if (false !== $found && $found > $nth) {
            return substr_replace($subject, $replace, $matches[0][$nth][1], strlen($search));
        }
        return $subject;
    }
}
