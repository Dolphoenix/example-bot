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

class m
{
    public function addLog($event, $msg)
    {
        // 创建日志频道
        $log = new Logger($event);
        $log->pushHandler(new StreamHandler(__DIR__ . '/bot.log', Logger::WARNING));
        // 添加日志记录
        // $log->addWarning($event, [$msg]);
        $log->addError($event, [$msg]);
    }

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
                echo '请求响应内容有误';
                return false;
            }
            return $res['result'];
        } catch (RequestException $e) {
            echo $e->getMessage();
        }
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
            $rst = $db->select("$table", "*", [
                "lottery_id"   => $lottery_id,
                "lottery_date" => $today,
                'LIMIT'        => 1,
            ]);

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
}