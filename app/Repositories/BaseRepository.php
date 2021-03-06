<?php


namespace App\Repositories;


use App\Models\Cx_User;
use Illuminate\Support\Facades\DB;

abstract class BaseRepository
{
    private $list = [];

    public function getUserIds($phone)
    {
        $ids = Cx_User::where("phone", "like", "%" . $phone . "%")->get("id")->toArray();
        return array_column($ids, "id");
    }


    protected function whereCondition($data, $model)
    {
        if (!array_key_exists("conditions", $data)) {
            return $model;
        }
        $this->list = $data["conditions"];
        $ops = $data["ops"];
        foreach ($ops as $index => $op) {
            if(isset($this->list[$index]) && $this->list[$index] !== '')
            {
                switch ($op) {
                    case "between":
                        $model = $model->where($this->betweenCondition($index));
                        break;
                    case "like":
                        $model = $model->where($this->likeCondition($index));
                        break;
                    case ">":
                        $model = $model->where($this->greaterCondition($index));
                        break;
                    case "<":
                        $model = $model->where($this->lessCondition($index));
                        break;
                    case "in":
                        $model = $model->where($this->inCondition($index));
                        break;
                    default:
                        $model = $model->where($this->equalCondition($index));
                }
            }
        }
        return $model;
    }

    public function rectifyCondition($conditions)
    {
        foreach ($conditions as $key => $condition) {
            if (empty($condition)) {
                if ($condition != 0) {
                    unset($conditions[$key]);
                }
            }
        }
        return $conditions;
    }

    /**
     * 如果是in数组的条件拼装
     * @param $key
     * @return \Closure
     */
    public function inCondition($key)
    {
        if (!array_key_exists($key, $this->list)) {
            return null;
        }
        $value = $this->list[$key];
        if (is_null($value)) {
            return null;
        }
        return function ($query) use ($key, $value) {
            $query->whereIn($key, $value);
        };
    }

    public function renderWhere()
    {
        return function ($query) {
            $query->whereRaw("1 = 1");
        };
    }

    /**
     * 如果是小于的条件拼装
     * @param $key
     * @return \Closure
     */
    public function lessCondition($key)
    {
        if (!array_key_exists($key, $this->list)) {
            return null;
        }
        $value = $this->list[$key];
        if (is_null($value)) {
            return null;
        }
        return function ($query) use ($key, $value) {
            $query->where($key, "<", $value);
        };
    }

    /**
     * 如果是大于的条件拼装
     * @param $key
     * @return \Closure
     */
    public function greaterCondition($key)
    {
        if (!array_key_exists($key, $this->list)) {
            return null;
        }
        $value = $this->list[$key];
        if (is_null($value)) {
            return null;
        }
        return function ($query) use ($key, $value) {
            $query->where($key, ">", $value);
        };
    }

    /**
     * 如果是时间区间的条件拼装
     * @param $key
     * @return \Closure
     */
    private function betweenCondition($key)
    {
        if (!array_key_exists($key, $this->list)) {
            return null;
        }
        $value = $this->list[$key];
        if (is_null($value)) {
            return null;
        }
        return function ($query) use ($key, $value) {
            $query->whereBetween($key, $value);
        };
    }

    /**
     * 如果是相等的条件拼装
     * @param $key
     */
    private function equalCondition($key)
    {
        if (!array_key_exists($key, $this->list)) {
            return null;
        }
        $value = $this->list[$key];
        if (is_null($value)) {
            return null;
        }
        return function ($query) use ($key, $value) {
            $query->where($key, $value);
        };
    }

    /**
     * 如果是模糊查询的条件拼装
     * @param $key
     */
    private function likeCondition($key)
    {
        if (!array_key_exists($key, $this->list)) {
            return null;
        }
        $value = $this->list[$key];
        if (is_null($value)) {
            return null;
        }
        return function ($query) use ($key, $value) {
            $query->where($key, "like", "%" . $value . "%");
        };
    }
}
