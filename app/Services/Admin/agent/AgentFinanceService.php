<?php


namespace App\Services\Admin\agent;


use App\Repositories\Admin\agent\AgentFinanceRepository;
use App\Repositories\Admin\agent\AgentUserRepository;
use Illuminate\Support\Facades\DB;

class AgentFinanceService extends BaseAgentService
{

    protected $AgentFinanceRepository, $AgentUserRepository;

    protected $where, $user_ids;

    public function __construct(AgentFinanceRepository $agentFinanceRepository, AgentUserRepository $agentUserRepository){
        $this->AgentFinanceRepository = $agentFinanceRepository;
        $this->AgentUserRepository = $agentUserRepository;
    }

    public function rechargeList(){
        $this->getAdmin();
        $size = $this->sizeInput();
        $this->setRechargeListWhere();
        $data = $this->AgentFinanceRepository->rechargeList($this->where, $this->user_ids, $size);
        $this->_data = $data;
        return true;
    }

    public function withdrawList(){
        $this->getAdmin();
        $size = $this->sizeInput();
        $this->setWithdrawListWhere();
        $data = $this->AgentFinanceRepository->withdrawList($this->where, $this->user_ids, $size);
        $this->_data = $data;
        return true;
    }

    public function commissionList(){
        $this->getAdmin();
        $size = $this->sizeInput();
        $this->setCommissionListWhere();
        $data = $this->AgentFinanceRepository->commissionList($this->where, $size);
        $this->_data = $data;
        return true;
    }

    protected function setRechargeListWhere(){
        $where = [];
        $status = $this->intInput('status');
        $where[] = ['status', '=', $status];
        $order_no = $this->strInput('order_no');
        if($order_no)
            $where[] = ['order_no', '=', $order_no];
        $phone = $this->strInput('phone');
        if($phone)
            $where[] = ['phone', '=', $phone];
        $start_time = $this->strInput('start_time');
        $end_time = $this->strInput('end_time');
        if($start_time && $end_time)
            $where[] = ['time', 'BETWEEN', [strtotime($start_time), strtotime($end_time)]];
        $this->user_ids = $this->AgentUserRepository->getUserIds($this->getRelationWhere($this->admin->user_id));
        $this->where = $where;
    }

    protected function setWithdrawListWhere(){
        $where = [];
        $status = $this->intInput('status');
        $where[] = ['status', '=', $status];
        $order_no = $this->strInput('order_no');
        if($order_no)
            $where[] = ['order_no', '=', $order_no];
        $phone = $this->strInput('phone');
        if($phone)
            $where[] = ['phone', '=', $phone];
        $start_time_withdraw = $this->strInput('start_time_withdraw');
        $end_time_withdraw = $this->strInput('end_time_withdraw');
        if($start_time_withdraw && $end_time_withdraw)
            $where[] = ['create_time', 'BETWEEN', [strtotime($start_time_withdraw), strtotime($end_time_withdraw)]];
        $start_time_exam = $this->strInput('start_time_exam');
        $end_time_exam = $this->strInput('end_time_exam');
        if($start_time_exam && $end_time_exam)
            $where[] = ['approval_time', 'BETWEEN', [strtotime($start_time_exam), strtotime($end_time_exam)]];
        $this->user_ids = $this->AgentUserRepository->getUserIds($this->getRelationWhere($this->admin->user_id));
        $this->where = $where;
    }

    public function setCommissionListWhere(){
        $where = [];
        $type = $this->intInput('type');
        $where[] = ['type', '=', $type];
        $user_phone = $this->strInput('user_phone');
        if($user_phone){
            $charge_user_ids = $this->AgentUserRepository->getLikePhoneUserId($user_phone);
            $where[] = ['charge_user_id', 'in', $charge_user_ids];
        }
        $betting_user_phone = $this->strInput('betting_user_phone');
        if($betting_user_phone){
            $betting_user_ids= $this->AgentUserRepository->getLikePhoneUserId($betting_user_phone);
            $where[] = ['betting_user_id', 'in', $betting_user_ids];
        }
        $user_ids = $this->AgentUserRepository->getUserIds($this->getRelationWhere($this->admin->user_id));
        $where[] = ['charge_user_id', 'in', $user_ids];
        $this->where = $where;
    }

}