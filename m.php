<?php
/**
 * Created by PhpStorm.
 * User: M
 * Date: 2020/9/23
 * Time: 14:42
 */

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Medoo\Medoo;

class M
{
    protected $db_config = [
        'database_type' => 'mysql',
        'database_name' => 'bot.mzh.one',
        'server'        => '119.28.86.234',
        'username'      => 'root',
        'password'      => '911Forever',
    ];

    public function addLog($event, $msg)
    {
        // 创建日志频道
        $log = new Logger($event);
        $log->pushHandler(new StreamHandler(__DIR__ . '/bot.log', Logger::WARNING));
        // 添加日志记录
        // $log->addWarning($event, [$msg]);
        $log->addError($event, [$msg]);
    }

    public function insertDailyRes($db)
    {
        $lottery_id = $this->getLotteryId();
        if (empty($lottery_id)) {
            return false;
        }
        $today = date('Y-m-d');

        $table = 'ssq' === $lottery_id ? "lottery_history_ssq" : 'lottery_history_dlt';
        try {
            $rst = $db->select(
                "$table",
                "*",
                [
                    "lottery_id"   => $lottery_id,
                    "lottery_date" => $today,
                    'LIMIT'        => 1,
                ]
            );

            if (!empty($rst)) {
                return false;
            }

            $uri = 'query';
            $params = [
                'lottery_id' => $lottery_id,
                'lottery_no' => '',
            ];
            $db_rst = $this->requestApi($uri, $params);

            if ($today !== $db_rst['lottery_date']) {
                return false;
            }

            $insert_data = [
                "lottery_id"          => $lottery_id,
                "lottery_res"         => $db_rst['lottery_res'],
                "lottery_no"          => $db_rst['lottery_no'],
                "lottery_date"        => $db_rst['lottery_date'],
                "lottery_exdate"      => $db_rst['lottery_exdate'],
                "lottery_sale_amount" => $db_rst['lottery_sale_amount'],
                "lottery_pool_amount" => $db_rst['lottery_pool_amount'],
            ];

            $db->insert($table, $insert_data);

            return $db->last();
        } catch (\RuntimeException $e) {
            echo $e->getMessage();
        }
    }

    // /**
    //  * 发送消息
    //  *
    //  * @param $text
    //  * @return ServerResponse
    //  * @throws TelegramException
    //  */
    // private function sendMsg($text): ServerResponse
    // {
    //     $message = $this->getMessage();
    //
    //     $chat_id = $message->getChat()->getId();
    //
    //     $data = [
    //         'chat_id' => $chat_id,
    //         'text'    => $text,
    //     ];
    //
    //     return Request::sendMessage($data);
    // }

    /**
     * 随机生成投注内容
     *
     * @return array
     */
    public function luckyRush()
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

    /**
     * 大乐透号码
     *
     * @return array
     */
    public function dlt()
    {
        $red_no = $this->lucky(1, 35, 5);
        $blue_no = $this->lucky(1, 12, 2);
        return [$red_no, $blue_no];
    }

    /**
     * 双色球号码
     *
     * @return array
     */
    public function ssq()
    {
        $red_no = $this->lucky(1, 33, 6);
        $blue_no = $this->lucky(1, 15, 1);
        return [$red_no, $blue_no];
    }

    /**
     * 随机号生成
     *
     * @param $begin
     * @param $end
     * @param $limit
     * @return string
     */
    public function lucky($begin, $end, $limit): string
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
     * 保存投注内容
     *
     * @param $lottery_id
     * @param $lottery_no
     * @param $lottery_bets
     * @return false
     */
    public function saveBets($lottery_id, $lottery_no, $lottery_bets)
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
        $db_config = $this->db_config;

        // Initialize
        $db = new Medoo($db_config);

        return $db->insert($table, $insert_data);
    }

    /**
     * 获取当前期号
     *
     * @param $lottery_id
     * @param string $lottery_no
     * @return false|int
     * @throws Exception
     */
    public function getLotteryNo($lottery_id, $lottery_no = '')
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
     *
     * @param $uri
     * @param array $params
     * @return false|mixed
     * @throws Exception
     */
    public function requestApi($uri, $params = [])
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

    /**
     * 获取彩票类别
     *
     * @return string
     */
    public function getLotteryId()
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

    /**
     * 获取投注内容，查询中奖接口，返回中奖情况
     *
     * @param $lottery_id
     * @param string $lottery_date
     * @param string $lottery_no
     * @return string
     * @throws Exception
     */
    public function checkBonus($lottery_id, $lottery_date = '', $lottery_no = ''): string
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

    /**
     * 查找投注内容，默认根据类别，查找最新一期
     *
     * @param $lottery_id
     * @param string $lottery_date
     * @param string $lottery_no
     * @return array|false
     */
    public function findBets($lottery_id, $lottery_date = '', $lottery_no = '')
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
        $db_config = $this->db_config;

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

    /**
     * 正则替换第n次匹配的内容
     *
     * @param $search
     * @param $replace
     * @param $subject
     * @param $nth
     * @return string|string[]
     */
    private function strReplaceNth($search, $replace, $subject, $nth)
    {
        $found = preg_match_all('/' . preg_quote($search) . '/', $subject, $matches, PREG_OFFSET_CAPTURE);
        if (false !== $found && $found > $nth) {
            return substr_replace($subject, $replace, $matches[0][$nth][1], strlen($search));
        }
        return $subject;
    }
}