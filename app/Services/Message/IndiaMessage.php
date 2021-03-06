<?php


namespace App\Services\Message;


use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IndiaMessage extends MessageStrategy
{

    private $desc = "印度短信";

    function sendRegisterCode($phone): array
    {
        date_default_timezone_set("Asia/Shanghai");
        $url = "http://sms.skylinelabs.cc:20003/sendsmsV2";
        $phone = "91" . $phone;
        $account = "cs_aheln9";
        $sign = md5($account . "u2AGYncI" . date("YmdHis"));
        $code = mt_rand(100000, 999999);
        $context = urlencode("Your SMS verification code is:{$code}");
        date_default_timezone_set("Asia/Shanghai");
        $params = [
            "account" => $account,
            "sign" => $sign,
            "numbers" => $phone,
            "content" => $context,
            "datetime" => date("YmdHis")
        ];
        $result = Http::post($url, $params)->json();
        if ($result["status"] == 0) {
            return ["code" => 200, "obj" => $code];
        }
        Log::channel('kidebug')->info('印度短信rtn',[$result]);
        return ["code" => 402];
    }
}
