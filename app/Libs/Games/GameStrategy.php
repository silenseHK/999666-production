<?php


namespace App\Libs\Games;


use App\Repositories\Api\UserRepository;

abstract class GameStrategy
{

    protected $UserRepository;

    public function __construct
    (
        UserRepository $userRepository
    )
    {
        $this->UserRepository = $userRepository;
    }

    abstract function launch($productId);

    abstract function userInfo();

    public function doRequest($url = '', $param = '', $headers)
    {
        if (empty($url) || empty($param)) {
            return false;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    public function addUserBetting($user_id, $betting)
    {
        $this->UserRepository->addUserBetting($user_id, $betting);
    }

}
