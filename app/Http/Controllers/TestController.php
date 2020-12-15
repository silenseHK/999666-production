<?php

namespace App\Http\Controllers;

use App\Models\Cx_Game_Betting;
use App\Models\Cx_Game_Play;
use App\Repositories\Game\GameRepository;
use App\Services\Game\Ssc_TwoService;
use App\Services\Game\SscService;
use App\Services\Pay\Winpay;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Redis;

class TestController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $Cx_Game_Play, $Cx_Game_Betting, $GameRepository;

    public function __construct(Cx_Game_Betting $game_Betting, Cx_Game_Play $game_Play, GameRepository $gameRepository){
        $this->Cx_Game_Play = $game_Play;
        $this->Cx_Game_Betting = $game_Betting;
        $this->GameRepository = $gameRepository;
    }

    public function test2(Winpay $winpay)
    {
        echo 123;die;
        $pay_type = '222';
        $money = 500;
        return ($winpay->rechargeOrder($pay_type, $money));
    }

    public function test(){
        $phone = request()->input('phone');
        $res = Redis::set("REGIST_CODE:" . $phone, 666666);
        dd($res);
    }

    public function openGame(SscService $sscService, Ssc_TwoService $ssc_TwoService){
        $sscService->ssc_ki(1);
    }

    public function openGameBetting(){
        $betting_id = request()->input('betting_id');
        $betting = $this->Cx_Game_Betting->where('id', $betting_id)->first();
        if(!$betting)
            return "betting不存在";
        if($betting->status != 0)
            return  "betting处理过了";
        $game = $this->Cx_Game_Play->where('id', $betting->game_p_id)->first();
        if($game->status == 0)
            return "未开奖";
        switch ($game->game_id){
            case 1:
                $this->result_1($game->prize_number, $betting);
                break;
            case 2:
                $this->result_2($game->prize_number, $betting);
                break;
            case 3:
                $this->result_3($game->prize_number, $betting);
                break;
            case 4:
                $this->result_4($game->prize_number, $betting);
                break;
        }
        return 'success';
    }

    public function result_1($result, $val){
        if($val->game_c_x_id==49){
            if($result==0){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==1){
            if($result==1){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==2){
            if($result==2){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==3){
            if($result==3){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==4){
            if($result==4){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==5){
            if($result==5){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==6){
            if($result==6){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==7){
            if($result==7){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==8){
            if($result==8){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==9){
            if($result==9){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==10){
            if($result==1 || $result==3 || $result==5 || $result==7 || $result==9){
                if($result==5){
                    $this->GameRepository->Result_Entry($val,1,1.5);
                }else{
                    $this->GameRepository->Result_Entry($val,1,2);
                }

            }else{
                if($result==5){
                    $this->GameRepository->Result_Entry($val,2,1.5);
                }else{
                    $this->GameRepository->Result_Entry($val,2,2);
                }

            }
        }else if($val->game_c_x_id==11){
            if($result==0 || $result==2 || $result==4 || $result==6 || $result==8){
                if($result==0){
                    $this->GameRepository->Result_Entry($val,1,1.5);
                }else{
                    $this->GameRepository->Result_Entry($val,1,2);
                }
            }else{
                if($result==0){
                    $this->GameRepository->Result_Entry($val,2,1.5);
                }else{
                    $this->GameRepository->Result_Entry($val,2,2);
                }
            }
        }else if($val->game_c_x_id==12){
            if($result==0 || $result==5 ){
                $this->GameRepository->Result_Entry($val,1,4.5);
            }else{
                $this->GameRepository->Result_Entry($val,2,4.5);
            }
        }
    }

    public function result_2($result, $val){
        if($val->game_c_x_id==50){
            if($result==0){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==13){
            if($result==1){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==14){
            if($result==2){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==15){
            if($result==3){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==16){
            if($result==4){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==17){
            if($result==5){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==18){
            if($result==6){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==19){
            if($result==7){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==20){
            if($result==8){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==21){
            if($result==9){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==22){
            if($result==1 || $result==3 || $result==5 || $result==7 || $result==9){
                if($result==5){
                    $this->GameRepository->Result_Entry($val,1,1.5);
                }else{
                    $this->GameRepository->Result_Entry($val,1,2);
                }

            }else{
                if($result==5){
                    $this->GameRepository->Result_Entry($val,2,1.5);
                }else{
                    $this->GameRepository->Result_Entry($val,2,2);
                }

            }
        }else if($val->game_c_x_id==23){
            if($result==0 || $result==2 || $result==4 || $result==6 || $result==8){
                if($result==0){
                    $this->GameRepository->Result_Entry($val,1,1.5);
                }else{
                    $this->GameRepository->Result_Entry($val,1,2);
                }
            }else{
                if($result==0){
                    $this->GameRepository->Result_Entry($val,2,1.5);
                }else{
                    $this->GameRepository->Result_Entry($val,2,2);
                }
            }
        }else if($val->game_c_x_id==24){
            if($result==0 || $result==5 ){
                $this->GameRepository->Result_Entry($val,1,4.5);
            }else{
                $this->GameRepository->Result_Entry($val,2,4.5);
            }
        }
    }

    public function result_3($result, $val){
        if($val->game_c_x_id==51){
            if($result==0){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==25){
            if($result==1){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==26){
            if($result==2){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==27){
            if($result==3){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==28){
            if($result==4){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==29){
            if($result==5){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==30){
            if($result==6){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==31){
            if($result==7){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==32){
            if($result==8){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==33){
            if($result==9){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==34){
            if($result==1 || $result==3 || $result==5 || $result==7 || $result==9){
                if($result==5){
                    $this->GameRepository->Result_Entry($val,1,1.5);
                }else{
                    $this->GameRepository->Result_Entry($val,1,2);
                }

            }else{
                if($result==5){
                    $this->GameRepository->Result_Entry($val,2,1.5);
                }else{
                    $this->GameRepository->Result_Entry($val,2,2);
                }

            }
        }else if($val->game_c_x_id==35){
            if($result==0 || $result==2 || $result==4 || $result==6 || $result==8){
                if($result==0){
                    $this->GameRepository->Result_Entry($val,1,1.5);
                }else{
                    $this->GameRepository->Result_Entry($val,1,2);
                }
            }else{
                if($result==0){
                    $this->GameRepository->Result_Entry($val,2,1.5);
                }else{
                    $this->GameRepository->Result_Entry($val,2,2);
                }
            }
        }else if($val->game_c_x_id==36){
            if($result==0 || $result==5 ){
                $this->GameRepository->Result_Entry($val,1,4.5);
            }else{
                $this->GameRepository->Result_Entry($val,2,4.5);
            }
        }
    }

    public function result_4($result, $val){
        if($val->game_c_x_id==52){
            if($result==0){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==37){
            if($result==1){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==38){
            if($result==2){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==39){
            if($result==3){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==40){
            if($result==4){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==41){
            if($result==5){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==42){
            if($result==6){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==43){
            if($result==7){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==44){
            if($result==8){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==45){
            if($result==9){
                $this->GameRepository->Result_Entry($val,1,9);
            }else{
                $this->GameRepository->Result_Entry($val,2,9);
            }
        }else if($val->game_c_x_id==46){
            if($result==1 || $result==3 || $result==5 || $result==7 || $result==9){
                if($result==5){
                    $this->GameRepository->Result_Entry($val,1,1.5);
                }else{
                    $this->GameRepository->Result_Entry($val,1,2);
                }

            }else{
                if($result==5){
                    $this->GameRepository->Result_Entry($val,2,1.5);
                }else{
                    $this->GameRepository->Result_Entry($val,2,2);
                }

            }
        }else if($val->game_c_x_id==47){
            if($result==0 || $result==2 || $result==4 || $result==6 || $result==8){
                if($result==0){
                    $this->GameRepository->Result_Entry($val,1,1.5);
                }else{
                    $this->GameRepository->Result_Entry($val,1,2);
                }
            }else{
                if($result==0){
                    $this->GameRepository->Result_Entry($val,2,1.5);
                }else{
                    $this->GameRepository->Result_Entry($val,2,2);
                }
            }
        }else if($val->game_c_x_id==48){
            if($result==0 || $result==5 ){
                $this->GameRepository->Result_Entry($val,1,4.5);
            }else{
                $this->GameRepository->Result_Entry($val,2,4.5);
            }
        }
    }
}
