<?php


namespace App\Repositories\Ag;


use App\Models\Cx_User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;

class LoginRepository
{

    protected $Cx_Users;

    public function __construct
    (
        Cx_User $cx_User
    )
    {
        $this->Cx_Users = $cx_User;
    }

    public function getUserByPhone($phone)
    {
        return $this->Cx_Users->where("phone", $phone)->select("id", "phone", "password", "user_type", "balance", "status", "rebate_rate", "code")->first();
    }

    public function setLoginTimes()
    {
        cookie('login_time',$this->getLoginTimes() + 1,20);
    }

    public function getLoginTimes()
    {
        return cookie('login_time')->getValue() ? : 0;
    }

    public function clearLoginTimes()
    {
        cookie()->forget('login_time');
    }

    public function doLogin($user)
    {
        $this->clearLoginTimes();
        ##存入session
        session_start();
        $_SESSION['user'] = $user->toArray();
    }

    public function doLogout()
    {
        unset($_SESSION['user']);
    }

}
