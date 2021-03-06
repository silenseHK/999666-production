<?php


namespace App\Http\Controllers\Plat;


use App\Http\Controllers\Controller;
use App\Libs\Games\WDYY\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BL extends Controller
{

    public function blReturn($retCode, $data=[], $msg='')
    {
        $ret = [
            "retCode" => (string)$retCode,
            "data" => $data,
        ];
        if(request()->input('is_msg',0) === 1)
        {
            $ret['msg'] = $msg;
        }
        return response()->json($ret)->setEncodingOptions(JSON_UNESCAPED_UNICODE);
    }

    protected $Client;

    public function __construct
    (
        Client $client
    )
    {
        $this->Client = $client;
    }

    public function balance()
    {
        Log::channel('plat')->info('wdyy-balance-params',request()->post());
        $validator = Validator::make(request()->input(), [
            'action' => 'required',
            'token' => 'required',
            'sign' => 'required',
        ]);
        if($validator->fails())
        {
            return $this->blReturn(1,[],$validator->errors()->first());
        }
        $this->Client->balanceHandle();
        return $this->blReturn($this->Client->_data['retCode'],$this->Client->_data['data'],$this->Client->_msg);
    }

    public function userinfo()
    {
        Log::channel('plat')->info('wdyy-userinfo-params',request()->post());
        $validator = Validator::make(request()->input(), [
            'token' => 'required',
            'sign' => 'required',
        ]);
        if($validator->fails())
        {
            return $this->blReturn(1,[], $validator->errors()->first());
        }
        $this->Client->userInfo();
        return $this->blReturn($this->Client->_data['retCode'],$this->Client->_data['data'],$this->Client->_msg);
    }

}
