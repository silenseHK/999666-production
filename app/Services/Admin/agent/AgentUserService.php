<?php


namespace App\Services\Admin\agent;


use App\Repositories\Admin\agent\AgentUserRepository;
use App\Services\BaseService;

class AgentUserService extends BaseAgentService
{

    protected $where;

    protected $AgentUserRepository;

    public function __construct(AgentUserRepository $agentUserRepository)
    {
        $this->AgentUserRepository = $agentUserRepository;
    }

    public function searchUser(){
        $this->getAdmin();
        $size = $this->sizeInput();
        $this->setSearchUserWhere();
        $data = $this->AgentUserRepository->searchUser($this->where, $size);
        $this->_data = $data;
        return true;
    }

    public function firstRechargeList(){
        $this->getAdmin();
        $size = $this->sizeInput();
        $this->setFirstRechargeListWhere();
        $data = $this->AgentUserRepository->firstRechargeList($this->where, $size);
        $this->_data = $data;
        return true;
    }

    public function orderInfoList(){
        $this->getAdmin();
        $size = $this->sizeInput();
        $this->setOrderInfoListWhere();
        $data = $this->AgentUserRepository->orderInfoList($this->where, $size);
        $this->_data = $data;
        return true;
    }

    public function setSearchUserWhere(){
        $where = [];
        $phone = $this->searchInput("mobile");
        if($phone)
            $where[] = ['phone', '=', $phone];
        $one_recommend_phone =$this->searchInput("one_recommend_phone");
        if($one_recommend_phone)
            $where[] = ['one_recommend_phone', '=', $one_recommend_phone];
        $two_recommend_phone =$this->searchInput("two_recommend_phone");
        if($two_recommend_phone)
            $where[] = ['two_recommend_phone', '=', $one_recommend_phone];
        $status = $this->intInput('status',-1);
        if($status != -1)
            $where[] = ['status', '=', $status];
        $register_time_start = $this->strInput('start_time');
        $register_time_end = $this->strInput('end_time');
        if($register_time_start && $register_time_end)
            $where[] = ['reg_time', 'BETWEEN', [strtotime($register_time_start), strtotime($register_time_end)]];
        $where[] = $this->relationLike();
        $this->where = $where;
    }

    public function setFirstRechargeListWhere(){
        $where = [];
        $where[] = ['r.is_first_recharge', '=', 1];
        $where[] = ['r.status', '=', 2];
        $phone = $this->searchInput("mobile");
        if($phone)
            $where[] = ['u.phone', '=', $phone];
        $register_time_start = $this->strInput('start_time');
        $register_time_end = $this->strInput('end_time');
        if($register_time_start && $register_time_end)
            $where[] = ['r.time', 'BETWEEN', [strtotime($register_time_start), strtotime($register_time_end)]];
        $where[] = ['u.invite_relation', 'like', '%-'. $this->admin->user_id .'-%'];
        $this->where = $where;
    }

    public function setOrderInfoListWhere(){
        $where = [];
        $phone = $this->searchInput("mobile");
        if($phone)
            $where[] = ['phone', '=', $phone];
        $min_recharge = $this->intInput("min_recharge");
        if($min_recharge > 0)
            $where[] = ['total_recharge', '>', $min_recharge];
        $where[] = ['invite_relation', 'like', '%-'. $this->admin->user_id .'-%'];
        $this->where = $where;
    }

}
