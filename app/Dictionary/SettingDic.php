<?php


namespace App\Dictionary;


class SettingDic
{

    protected static $settingKeys = [
        'staff_id' => [
            'title' => '员工角色ID',
            'key' => 'staff_id'
        ],
        'GROUP_LEADER_ROLE_ID' => [
            'title' => '组长角色ID',
            'key' => 'GROUP_LEADER_ROLE_ID'
        ],
        'withdraw' => [
            'title' => '提现配置',
            'key' => 'withdraw'
        ],
        'recharge' => [
            'title' => '充值配置',
            'key' => 'recharge'
        ],
        'login_alert' => [
            'title' => '登陆弹窗信息',
            'key' => 'login_alert'
        ],
        'logout_alert' => [
            'title' => '未登陆弹窗信息',
            'key' => 'logout_alert'
        ],
        'SERVICE' => [
            'title' => '客服配置',
            'key' => 'service'
        ],
    ];

    public static function key($key)
    {
        return self::$settingKeys[$key]['key'];
    }

}