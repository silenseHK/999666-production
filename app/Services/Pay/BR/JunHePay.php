<?php


namespace App\Services\Pay\BR;


use App\Services\Pay\PayStrategy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JunHePay extends PayStrategy
{

    protected static $url = 'https://bra.junhepay.com/web/index.html';    // 支付网关

    protected static $url_cashout = 'https://bra.junhepay.com/api/otcPayOrder/unifiedOrder'; // 提现网关

    protected static $login = 'https://bra.junhepay.com/api/otcAppUser/login';  //签名登录url

    private $recharge_callback_url = '';     // 充值回调地址
    private $withdrawal_callback_url = '';  //  提现回调地址

    public $withdrawMerchantID;
    public $withdrawSecretkey;
    public $rechargeMerchantID;
    public $rechargeSecretkey;

    public $rechargeRtn = "success";
    public $withdrawRtn = 'success';

    public $company = 'junhe';   // 支付公司名

    public function _initialize()
    {
        $withdrawConfig = DB::table('settings')->where('setting_key', 'withdraw')->value('setting_value');
        $rechargeConfig = DB::table('settings')->where('setting_key', 'recharge')->value('setting_value');
        $withdrawConfig && $withdrawConfig = json_decode($withdrawConfig, true);
        $rechargeConfig && $rechargeConfig = json_decode($rechargeConfig, true);

        $this->withdrawMerchantID = isset($withdrawConfig[$this->company]) ? $withdrawConfig[$this->company]['merchant_id'] : "";
        $this->withdrawSecretkey = isset($withdrawConfig[$this->company]) ? $withdrawConfig[$this->company]['secret_key'] : "";

        $this->rechargeMerchantID = isset($rechargeConfig[$this->company]) ? $rechargeConfig[$this->company]['merchant_id'] : "";
        $this->rechargeSecretkey = isset($rechargeConfig[$this->company]) ? $rechargeConfig[$this->company]['secret_key'] : "";

        $this->recharge_callback_url = self::$url_callback . '/api/recharge_callback' . '?type=' . $this->company;
        $this->withdrawal_callback_url = self::$url_callback . '/api/withdrawal_callback' . '?type=' . $this->company;
    }

    public function generateSign($params, $flag = 1)
    {
        $secret = $flag == 1 ? $this->rechargeSecretkey : $this->withdrawSecretkey;
        ksort($params);
        $string = [];
        foreach ($params as $key => $value) {
            if($value != "")
                $string[] = $key . '=' . $value;
        }
        $sign = implode('&', $string);
        $sign = urlencode($sign);
        Log::channel('mytest')->info('JunHe-login-sign-str',[$sign]);
        return hash_hmac('sha1',$sign,$secret);
    }

    protected function signLogin($flag=1)
    {
        $params = [
            'appId' => $flag == 1 ? $this->rechargeMerchantID : $this->withdrawMerchantID,
            'ts' => time() * 1000,
            'terminalType' => 'app'
        ];

        $params['sign'] = $this->generateSign($params, $flag);
        Log::channel('mytest')->info('JunHe-login-sign',$params);
        $res = $this->requestService->postFormData(self::$login, $params);
        Log::channel('mytest')->info('JunHe-login-return',[$res]);
        if($res['code'] != 200)
        {
            $this->_msg = $res['message'];
            return false;
        }
        return $res['data'];
    }

    protected function createNativeUrl($params):string
    {
        $url = self::$url . "?";
        foreach ($params as $key => $val)
        {
            $url .= $key .'='. $val . '&';
        }
        return trim($url,'&');
    }

    /**
     * 充值下单接口
     */
    public function rechargeOrder($pay_type, $money)
    {
        $order_no = self::onlyosn();
        if(!$token = $this->signLogin())
        {
            return false;
        }
        $user = $this->getUser();
        $params = [
            'outOrderNo' => $order_no,
            'token' => $token,
            'amount' => intval($money),
            'payType' => 9,
            'notifyUrl' => urlencode($this->recharge_callback_url),
            'userFlag' => $user->phone,
        ];
        $is_post=3;

        $native_url = self::$url;
        $resData = [
            'pay_type' => $pay_type,
            'out_trade_no' => $order_no,
            'order_no' => $order_no,
            'native_url' => $native_url,
            'notify_url' => $this->recharge_callback_url,
            'pltf_order_id' => '',
            'verify_money' => '',
            'match_code' => '',
            'params' => $params,
            'is_post' => isset($is_post) ? $is_post : 0
        ];
        return $resData;
    }

    /**
     * 充值回调
     */
    function rechargeCallback(Request $request)
    {
        \Illuminate\Support\Facades\Log::channel('mytest')->info('JunHe_rechargeCallback', $request->post());
        $params = $request->post();
        if($params['orderState'] != 1) {
            $this->_msg = 'JunHe-recharge-交易未完成';
            return false;
        }
        // 验证签名
        $sign = $params['sign'];
        unset($params['sign']);
        unset($params['type']);
        if ($this->generateSign($params) <> $sign) {
            $this->_msg = 'JunHe-签名错误';
            return false;
        }
        $this->amount = $params['tradeAmount'];
        $where = [
            'order_no' => $params['outOrderNo'],
        ];
        $this->rechargeRtn = json_encode([
            'code' => 200,
            'data' => true,
            'message' => '请求成功',
            'success' => true,
        ]);
        return $where;
    }

    /**
     *  后台审核请求提现订单 (提款)  代付方式
     */
    public function withdrawalOrder(object $withdrawalRecord)
    {
        $money = $withdrawalRecord->payment;    // 打款金额
        $order_no = $withdrawalRecord->order_no;

        $params = [
            'appId' => $this->withdrawMerchantID,
            'terminalType' => 'app',
            'ts' => time() * 1000,
            'payType' => 4,
            'tradeAmount' => intval($money),
            'outOrderNo' => $order_no,
            'notifyUrl' => $this->withdrawal_callback_url,
            'upiAccount' => $withdrawalRecord->bank_number,
            'ifscCode' => '',
            'receiveName' => '',
            'receiveAccount' => '',
            'bankName' => '',
            'customerName' => $withdrawalRecord->account_holder,
        ];
        $params['sign'] = $this->generateSign($params, 2);
        \Illuminate\Support\Facades\Log::channel('mytest')->info('JunHe_withdraw_params', $params);
        $res = $this->requestService->postFormData(self::$url_cashout, $params);
        \Illuminate\Support\Facades\Log::channel('mytest')->info('JunHe_withdraw_return', [$res]);
        if (!$res) {
            $this->_msg = '提交代付失败';
            return false;
        }
        if ($res['code'] != 200 && $res['success'] !== true) {
            $this->_msg = $res['message'];
            return false;
        }
        return [
            'pltf_order_no' => '',
            'order_no' => $order_no
        ];
    }

    /**
     * 提现回调
     */
    function withdrawalCallback(Request $request)
    {
        \Illuminate\Support\Facades\Log::channel('mytest')->info('JunHe_withdrawalCallback', $request->post());
        $params = $request->post();
//        if ($params['code'] != 1) {
//            $this->_msg = 'BRHX-withdrawal-交易未完成';
//            return false;
//        }
        $pay_status = 0;
        $status = (int)$params['orderState'];
        if ($status == 1) {
            $pay_status = 1;
        }
        if ($status == 2) {
            $pay_status = 3;
        }
        if ($pay_status == 0) {
            $this->_msg = 'JunHe-withdrawal-交易未完成';
            return false;
        }
        // 验证签名

        $sign = $params['sign'];
        unset($params['sign']);
        unset($params['type']);
        if ($this->generateSIgn($params, 2) <> $sign) {
            $this->_msg = 'JunHe-签名错误';
            return false;
        }
        $where = [
            'order_no' => $params['outOrderNo'],
            'plat_order_id' => '',
            'pay_status' => $pay_status
        ];
        return $where;
    }
}
