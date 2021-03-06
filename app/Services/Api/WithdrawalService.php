<?php


namespace App\Services\Api;


use App\Dictionary\BankCodeDic;
use App\Repositories\Api\SettingRepository;
use App\Repositories\Api\SystemRepository;
use App\Repositories\Api\UserRepository;
use App\Repositories\Api\WithdrawalRepository;
use App\Services\BaseService;
use App\Services\Pay\PayContext;
use App\Services\Pay\PayStrategy;
use App\Services\PayService;
use App\Services\RequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WithdrawalService extends PayService
{
    private $WithdrawalRepository, $UserRepository, $systemRepository, $requestService, $payContext, $SettingRepository;

    public static $service_charge = 45;  // 手续费

    public function __construct
    (
        WithdrawalRepository $repository,
        UserRepository $userRepository,
        RequestService $requestService,
        PayContext $payContext,
        SystemRepository $systemRepository,
        SettingRepository $settingRepository
    )
    {
        $this->WithdrawalRepository = $repository;
        $this->UserRepository = $userRepository;
        $this->requestService = $requestService;
        $this->systemRepository = $systemRepository;
        $this->SettingRepository = $settingRepository;
        $this->payContext = $payContext;
    }

    public function getRecords($token)
    {
        $userId = $this->getUserId($token);
        $size = $this->sizeInput();
        $status = $this->intInput('status');
        $where = [
            'user_id' => ['=', $userId],
            'status' => ['=', $status]
        ];
        $this->_data = $this->WithdrawalRepository->findRecordByUserId($where, $size);
    }

    public function addRecord($data, $token)
    {
        $userId = $this->getUserId($token);
        $data["user_id"] = $userId;
        $data["create_time"] = time();
        if ($this->WithdrawalRepository->addRecord($data)) {
            $this->_msg = "Successful withdrawal application";
        } else {
            $this->_code = 402;
            $this->_msg = "Withdrawal application failed";
        }
    }

    public function getMessage($id)
    {
        $this->_data = $this->WithdrawalRepository->getMessage($id);
    }

    public function getAgentWithdrawalRecord($token)
    {
        $userId = $this->getUserId($token);
        $this->_data = $this->WithdrawalRepository->getAgentWithdrawalRecord($userId);
    }

    public function getAgentRewardRecord($token, $type)
    {
        $userId = $this->getUserId($token);
        $this->_data = $this->WithdrawalRepository->getAgentRewardRecord($userId, $type);
    }


    /**
     * 代理请求提现订单 (提款佣金)  先由后台审核，审核后由后台提交
     */
    public function addAgentRecord(Request $request)
    {
        if (!$data = $this->addWithdrawlLog($request, $type = 1)) {
            return false;
        }
        $onlydata["service_charge"] = self::$service_charge;  // 手续费
        $onlydata["payment"] = bcsub($data["money"], self::$service_charge, 2);
        $data = array_merge($data, $onlydata);
        $this->WithdrawalRepository->addRecord($data);

        $user = $this->UserRepository->findByIdUser($data['user_id']);
        return [
            'balance' => $user->balance,
            'commission' => $user->commission,
        ];
    }

    /**
     * 用户请求提现订单 (提款余额)  先由后台审核，审核后由后台提交
     */
    public function withdrawalOrder(Request $request)
    {

        if (!$data = $this->addWithdrawlLog($request, $type = 0)) {
            return false;
        }
//        $onlydata["payment"] = bcsub($data["money"], self::$service_charge, 2);
        if(!$pay_money = $this->countServiceCharge($data))
        {
            return false;
        }
        $onlydata["payment"] = $pay_money['amount'];
        $onlydata["service_charge"] = $pay_money['service_charge'];
        $data = array_merge($data, $onlydata);
        $this->WithdrawalRepository->addRecord($data);

        $user = $this->UserRepository->findByIdUser($data['user_id']);
        return [
            'balance' => $user->balance,
            'commission' => $user->commission,
        ];
    }

    /**
     * 计算提现服务费
     * @param $data
     */
    public function countServiceCharge($data)
    {
        $service_charge = 0;
        $amount = $data['money'];
        $conf = $this->SettingRepository->getWithdrawServiceCharge();
        if(isset($conf['status']) &&  $conf['status']== 1){ //手续费开启
//            $country = env('COUNTRY','india');
//            if($country == 'india'){
//                $service_charge = 45;  //卢比
//                $limit = 1500;
//            }else{
//                $service_charge = 14160;  //盾
//                $limit = 472000;
//            }
//            if($amount >= $limit){
//                $service_charge = bcmul($amount,0.03);
//            }
            if(isset($conf['free_status']) && $conf['free_status'] == 1){  //开启了次数免费
                $count = $this->WithdrawalRepository->countUserWithdraw($data['user_id']);
                $free_times = $conf['free_times']??0;
                if($count >= $free_times){ //收取手续费
                    $service_charge = $this->calcCharge($conf, $amount);
                }
            }else{
                $service_charge = $this->calcCharge($conf, $amount);
            }
        }
        $amount = bcsub($amount, $service_charge);
        return compact('amount','service_charge');
    }

    protected function calcCharge($config, $amount): float
    {
        if($amount <= $config['standard']) {
            $service_charge = $config['charge'];
        }else{
            $service_charge = bcmul($amount,$config['percent'],2);
        }
        return $service_charge;
    }

    /**
     * 佣金直接体现到余额
     * @param Request $request
     */
    public function applyToBalance(Request $request)
    {
        $user_id = $this->getUserId($request->header("token"));
        $user = $this->UserRepository->findByIdUser($user_id);
        $money = (float)$request->money;
        if ((float)$user->commission < $money) {
            $this->_msg = 'The withdrawal amount is greater than the balance';
            return false;
        }
        DB::beginTransaction();
        try {
            //当前余额
            $dq_balance = $user->balance;
            //变动后余额
            $wc_balance = bcadd($user->balance, $money, 2);
            //当前佣金
            $dq_commission = $user->commission;
            //变动后佣金
            $wc_commission = bcsub($user->commission, $money, 2);
            //累计提现佣金
            $user->cl_commission = bcadd($user->cl_commission, $money, 2);
            $user->commission = $wc_commission;
            $user->balance = $wc_balance;
            $user->save();
            ##增加用户余额变化记录
            $this->UserRepository->addBalanceLog($user_id, $money, 12, '佣金提现', $dq_balance, $wc_balance);
            //增加用户佣金变化记录
            $this->UserRepository->addCommissionLogs($user, $money, $dq_commission, $wc_commission, PayStrategy::onlyosn());
            DB::commit();
            return [
                'total_commission' => $wc_commission,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
//            $this->_msg = 'An unknown error occurred';
            $this->_msg = $e->getMessage();
            return false;
        }
    }

    /**
     * 用户和代理提现公共方法
     * 'type' => 1,  // 类型，0:用户提现 1:代理佣金提现
     */
    private function addWithdrawlLog(Request $request, $type = 1)
    {
        $user_id = $this->getUserId($request->header("token"));
        $user = $this->UserRepository->findByIdUser($user_id);

        if(!$user->is_withdrawal){
            $this->_msg = 'Your withdrawal function has been disabled';
            return false;
        }

        $conf = $this->SettingRepository->getWithdrawServiceCharge();
        ##判断提现次数限制
        if($conf['limit_times'] > -1)
        {
            $count = $this->WithdrawalRepository->countUserWithdraw($user->id);
            if($count >= $conf['limit_times'])
            {
                $this->_msg = "The maximum number of withdrawals today is {$conf['limit_times']}";
                return false;
            }
        }

        $bank_id = $request->bank_id;
        $money = $request->money;

        $user_bank = $this->UserRepository->getBankByBankId($bank_id);
        if ($user_bank->user_id <> $user_id) {
            $this->_msg = 'The bank card does not match';
            return false;
        }

        $withdraw_type = $request->with_type ?: "";
        ##判断提现类型是否支持
        $withdraw_conf = $this->WithdrawalRepository->getConfig();
        if(!isset($withdraw_conf[$withdraw_type])){
            $this->_msg = 'Withdrawal method not supported';
            return false;
        }
        $withdraw_conf = $withdraw_conf[$withdraw_type];
        if($money > $withdraw_conf['limit']['max']){
            $this->_msg = 'The maximum withdrawal amount is ' . $withdraw_conf['limit']['max'];
            return false;
        }
        if($money < $withdraw_conf['limit']['min']){
            $this->_msg = 'The minimum withdrawal amount is ' . $withdraw_conf['limit']['min'];
            return false;
        }

        if ($withdraw_type == 'vnpay') {
            ##验证是否支持
            if (!$user_bank->mtbpy_code) {
                $this->_msg = 'The bank card does not support this withdrawal method';
                return false;
            }
        }

//        if($withdraw_type == 'payq'){
//            ##验证银行是否在可用银行内
//            if(!in_array($user_bank->bank_type_id,BankCodeDic::$payq)){
//                $this->_msg = 'The bank card does not support this withdrawal method';
//                return false;
//            }
//        }

        // 0:用户提现 余额提现
        if ($type == 0) {

            if ((float)$user->balance < $money) {
                $this->_msg = 'The withdrawal amount is greater than the balance';
                return false;
            }

            $system = $this->systemRepository->getSystem();
            $fake_betting_money = isset($user->fake_betting_money)?$user->fake_betting_money:0;

            if($this->SettingRepository->getIsCheckRecharge() && (float)$user->total_recharge <= 0){
                $this->_msg = "Please complete one recharge first";
                return false;
            }

            if($user['force_betting_money'] > 0)
            {
                if ($user->force_betting_money > $user->cl_betting) {
                    $this->_msg = "Your order amount is not enough to complete the withdrawal of {$money} amount, please complete the corresponding order amount before initiating the withdrawal.";
                    return false;
                }
            }else{
                ##投注金额 * 打码倍数 + 虚拟流水  >= 当前提现金额
                if($system->multiple > 0) {
                    ##待审核提现金额
                    $total_withdraw = $this->WithdrawalRepository->sumUserWithdraw($user_id);
                    if ((float)$system->multiple * (float)$user->cl_betting + (float)$fake_betting_money < $money + $total_withdraw + $user->cl_withdrawal) {
                        $this->_msg = "Your order amount is not enough to complete the withdrawal of {$money} amount, please complete the corresponding order amount before initiating the withdrawal";
                        return false;
                    }
                }
            }

//            if (((float)$user->cl_betting - $user->cl_withdrawal + (float)$fake_betting_money) < $money * (int)$system->multiple) {
//                $this->_msg = "Your order amount is not enough to complete the withdrawal of {$money} amount, please complete the corresponding order amount before initiating the withdrawal1";
//                return false;
//            }

            $cur_balance = bcsub($user->balance, $money, 2);
            ##增加用户余额变化记录
            $this->UserRepository->addBalanceLog($user_id, $money, 3, '用户申请提现', $user->balance, $cur_balance);

            $user->balance = $cur_balance;
            $user->freeze_money = bcadd($user->freeze_money, $money, 2);
            $user->save();

        } elseif ($type == 1) {

            if ((float)$user->commission < $money) {
                $this->_msg = 'The withdrawal amount is greater than the balance';
                return false;
            }

            //  0:代理提现  佣金提现
            $user->commission = bcsub($user->commission, $money, 2);
            $user->freeze_agent_money = bcadd($user->freeze_agent_money, $money, 2);
            $user->save();
        }
        $account_holder = $user_bank->account_holder;
        $bank_name = $user_bank->bank_type_id;
        $bank_number = $user_bank->bank_num;
        $ifsc_code = $user_bank->ifsc_code?:'';
        $phone = $user_bank->phone;
        $email = $user_bank->mail;
        $mtb_code = $user_bank->mtbpy_code ?: "";
        $order_no = PayStrategy::onlyosn();
        $data = [
            'user_id' => $user_id,
            'phone' => $phone,
            'nickname' => $user->nickname,
            'money' => $money,
            'create_time' => time(),
            'order_no' => $order_no,
            'pltf_order_no' => '',
            'upi_id' => '',
            'account_holder' => $account_holder,
            'bank_number' => $bank_number,
            'bank_name' => $bank_name,
            'ifsc_code' => $ifsc_code,
            'pay_status' => 0,
            'status' => 0,
            'email' => $email,
            'type' => $type,
            'mtb_code' => $mtb_code,
            'with_type' => $withdraw_type,
            'bank_id' => $bank_id
        ];
        return $data;
    }

    /**
     *  出金订单回调
     *
     * 请求参数    参数名    数据类型    可空    说明
     * 商户单号    sh_order    string    否    商户系统的业务单号
     * 平台单号    pt_order    string    否    支付平台的订单号
     * 订单金额    money    float    否    与支付提交的金额一致
     * 支付完成时间    time    int    否    系统时间戳UTC秒/毫秒（10/13位））
     * 订单状态    state    int    否    订单状态
     * 0已提交       1已接单
     * 2超时补单     3订单失败
     * 4交易完成     5未接单
     * 商品描述        goods_desc    string    否    订单描述或备注信息
     * 签名    sign    string    否    见签名算法
     */
    public function withdrawalCallback(Request $request)
    {
        \Illuminate\Support\Facades\Log::channel('mytest')->info('withdrawalCallback', $request->all());

        $payProvide = $request->input('backup_type', '');
        if(!$payProvide)
            $payProvide = $request->input('type', '');
        if (!$payProvide) {
            $this->_msg = 'can not find pay Provide';
            return false;
        }
        if(strpos($payProvide, 'TongLink') !== false){
            $payProvide = 'TongLink';
        }
        $strategyClass = $this->payContext->getStrategy($payProvide);  // 获取支付提供商类
        if (!$strategyClass) {
            $this->_msg = 'can not find pay mode';
            return false;
        }
        if (!$where = $strategyClass->withdrawalCallback($request)) {
            $this->_msg = $strategyClass->_msg;
            return false;
        }

        $pltf_order_no = isset($where['plat_order_id']) ? $where['plat_order_id'] : '';
        $pay_status = $where['pay_status']??0;
        $withdrawlLog = $this->WithdrawalRepository->getWithdrawalInfoByCondition($where);
        if (!$withdrawlLog) {
            $this->_msg = "Can't find this order";
            return false;
        }

        if ($withdrawlLog->status != 1) {
            $this->_msg = 'This operation is not supported';
            return false;
        }

        if ($withdrawlLog->pay_status == 1) {
            $this->_msg = 'Withdraw successfully, no need to call back';
            return false;
        }

        $money = $withdrawlLog->money;      // 申请金额
        $payment = $withdrawlLog->payment;  // 手续费之后的金额
        DB::beginTransaction();
        try {
            $user = $this->UserRepository->findByIdUser($withdrawlLog->user_id);
            if($pay_status == 1){
                // 普通用户
                if ($withdrawlLog->type == 0) {
                    // 记录提现成功余额变动
//                    $dq_balance = bcadd($user->balance, $user->freeze_money, 2);     // 当前余额 (总余额+冻结金额)
//                    $wc_balance = bcsub($dq_balance, $money, 2);                   // 变动后余额
//                    $this->UserRepository->addBalanceLog($user->id, $money, 3, "成功提现{$money};减少冻结金额{$money}", $dq_balance, $wc_balance);
                    ##以上步骤重复了

                    // 更新用户金额
                    $user->freeze_money = bcsub($user->freeze_money, $money, 2); // 减掉冻结资金
                    $user->cl_withdrawal = bcadd($user->cl_withdrawal, $money, 2); // 累计提现
                    $user->save();

                    // 代理用户
                } elseif ($withdrawlLog->type == 1) {
                    // 记录充值成功余额变动
                    $dq_commission = bcadd($user->commission, $user->freeze_agent_money, 2);     // 当前余额 (总佣金余额+冻结佣金金额)
                    $wc_commission = bcsub($dq_commission, $money, 2);                            // 变动后余额
                    $order_no = $withdrawlLog->order_no;
                    $this->UserRepository->addCommissionLogs($user, $money, $dq_commission, $wc_commission, $order_no);

                    // 更新用户金额
                    $user->freeze_agent_money = bcsub($user->freeze_agent_money, $money, 2); // 减掉代理冻结代理资金
                    $user->cl_commission = bcadd($user->cl_commission, $money, 2);
                    $user->save();
                }

                // 更新提现成功记录
                $withdrawlLog->pltf_order_no = $pltf_order_no;
                $withdrawlLog->pay_status = 1;
                $withdrawlLog->loan_time = time();
                $withdrawlLog->payment = $payment;
                $withdrawlLog->save();
            }elseif($pay_status == 3){ ##提现失败
                ##更新提现失败记录
                $withdrawlLog->pltf_order_no = $pltf_order_no;
                $withdrawlLog->pay_status = 3;
                $withdrawlLog->loan_time = time();
                $withdrawlLog->save();
            }
            $this->_msg = $strategyClass->withdrawRtn;

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->_msg = $e->getMessage();
            return false;
        }
        return true;
    }

    /**
     * 提现查询
     *
     * 商户可以主动查询出金订单状态
     * 建议商户在接收到异步通知后，主动查询一次订单状态和通知状态对比。不建议采用轮询方式过于频繁的执行查询请求
     */
    public function withdrawalQuery($order_no)
    {
        $params = [
            "mch_id" => self::$merchantID,
            "out_order_sn" => $order_no,
            "time" => time(),
        ];
        $params['sign'] = self::generateSign($params);
        return $this->requestService->postJsonData(self::$url . '/withdrawalQuery', $params);
    }

    public function withdrawType()
    {
        $withdraw_type = config('pay.withdraw',[]);
        $setting_value = $this->SettingRepository->getWithdraw();
        $config = [];
        foreach($withdraw_type as $key => $item){
            if(isset($setting_value[$key])){
                $val = $setting_value[$key];
                if(isset($val['status']) && $val['status'] == 1)
                    $config[] = [
                        'type' => $key,
                        'limit' => $val['limit'],
                        'btn' => $val['btn'],
                        'start_week' => $val['start_week'],
                        'end_week' => $val['end_week'],
                        'during_time' => $val['during_time'],
                    ];
            }
        }
        $this->_data = $config;
    }

    public function withdrawFee()
    {
        $this->_data = $this->SettingRepository->getWithdrawServiceCharge();
    }

}
