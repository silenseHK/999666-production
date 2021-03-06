<?php


namespace App\Http\Controllers\Ag;


use App\Http\Requests\Ag\UserRequest;
use App\Services\Ag\UserService;

class User extends Base
{

    protected $UserService;

    public function __construct
    (
        UserService $userService
    )
    {
        $this->UserService = $userService;
    }

    public function inviteIndex()
    {
        $tab = request()->input('tab',1);
        if($tab == 1){
            return view('ag.invite_index', ['idx'=>4]);
        }else{
            $this->UserService->getLinkList();
            return view('ag.invite_link', ['idx'=>4, 'data'=>$this->UserService->_data]);
        }
    }

    public function addLink(UserRequest $userRequest)
    {
        try{
            $this->UserService->addLink($userRequest->validated());
            return $this->AppServiceReturn($this->UserService);
        }catch (\Exception $e){
            return $this->AppHostErr($e);
        }
    }

    public function delLink(UserRequest $userRequest)
    {
        try{
            $this->UserService->delLink($userRequest->validated());
            return $this->AppServiceReturn($this->UserService);
        }catch (\Exception $e){
            return $this->AppHostErr($e);
        }
    }

    public function userList()
    {
        $this->UserService->getUserList();
        return view('ag.user_list', ['idx'=>5, 'data'=>$this->UserService->_data]);
    }

    public function mInviteIndex()
    {
        $tab = request()->input('tab',1);
        if($tab == 1){
            return view('ag.m.invite_index', ['title'=>trans('ag.manage_link'), 'prev'=>1]);
        }else{
            $this->UserService->getLinkList();
            return view('ag.m.invite_link', ['title'=>trans('ag.invite_code'), 'prev'=>1, 'data'=>$this->UserService->_data]);
        }
    }

    public function mUserList()
    {
        $this->UserService->getUserList();
        return view('ag.m.user_list', ['title'=>trans('ag.member_center'), 'prev'=>1, 'data'=>$this->UserService->_data]);
    }

}
