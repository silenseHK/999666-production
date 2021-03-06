<?php


namespace App\Services\Admin;


use App\Repositories\Admin\ProductRepository;
use App\Repositories\Admin\UserRepository;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class ProductService extends BaseService
{
    protected $ProductRepository, $UserRepository;

    public function __construct
    (
        ProductRepository $productRepository,
        UserRepository $userRepository
    )
    {
        $this->ProductRepository = $productRepository;
        $this->UserRepository = $userRepository;
    }

    public function add():bool
    {
        $data = $this->getProductData();
        $images = request()->post('images');
        if(empty($images)){
            $this->_code = 403;
            $this->_msg = '商品图片不能为空';
            return false;
        }
        DB::beginTransaction();
        try{
            ##增加商品
            $product = $this->ProductRepository->addProduct($data);
            if(!$product)throw new \Exception('商品创建失败');
            ##绑定banner
            $res = $this->ProductRepository->addProductImages($images, $product->product_id);
            if($res === false)throw new \Exception('banner关联失败');
            DB::commit();
            $this->ProductRepository->updateProductCache($product->product_id);
            return true;
        }catch(\Exception $e){
            DB::rollBack();
            $this->_code = 402;
            $this->_msg = $e->getMessage();
            return false;
        }
    }

    public function lists()
    {
        $size = $this->sizeInput();
        $this->_data = $this->ProductRepository->productLists($size);
    }

    public function update():bool
    {
        $product_id = $this->intInput('product_id');
        $data = $this->getProductData();
        $images = request()->post('images');
        if(empty($images)){
            $this->_code = 403;
            $this->_msg = '商品图片不能为空';
            return false;
        }
        DB::beginTransaction();
        try{
            ##增加商品
            $res = $this->ProductRepository->updateProduct($product_id, $data);
            if($res === false)throw new \Exception('商品更新失败');
            ##删除原来的banner关联
            $res = $this->ProductRepository->delProductImages($product_id);
            if($res === false)throw new \Exception('banner更新失败');
            ##绑定banner
            $res = $this->ProductRepository->addProductImages($images, $product_id);
            if($res === false)throw new \Exception('banner关联失败');
            DB::commit();
            $this->ProductRepository->updateProductCache($product_id);
            return true;
        }catch(\Exception $e){
            DB::rollBack();
            $this->_code = 402;
            $this->_msg = $e->getMessage();
            return false;
        }
    }

    protected function getProductData():array
    {
        return [
            'name' => $this->strInput('name'),
            'price' => $this->floatInput('price'),
            'back_money' => $this->floatInput('back_money'),
            'content' => $this->htmlInput('content'),
            'sort' => $this->intInput('sort',9999),
            'status' => $this->intInput('status'),
            'cover' => $this->intInput('cover'),
            'buy_status' => $this->intInput('buy_status')
        ];
    }

    public function edit():bool
    {
        $product_id = $this->intInput('product_id');
        $field = $this->strInput('field');
        switch($field){
            case 'status':
                $status = $this->ProductRepository->getProductValue($product_id,'status');
                $value = ($status + 1)%2;
                break;
            case 'sort':
                $value = $this->intInput('sort',9999);
                break;
            default:
                break;
        }
        if(!isset($value)){
            $this->_code = 403;
            $this->_msg = '参数错误';
            return false;
        }
        $res = $this->ProductRepository->updateProduct($product_id, [$field=>$value]);
        $this->ProductRepository->updateProductCache($product_id);
        if($res === false){
            $this->_code = 402;
            $this->_msg = '修改失败';
            return false;
        }
        return true;
    }

    public function detail():bool
    {
        $product_id = $this->intInput('product_id');
        $product = $this->ProductRepository->getProduct($product_id);
        if(!$product){
            $this->_code = 402;
            $this->_msg = '商品不存在';
            return false;
        }
        $this->_data = $product;
        return true;
    }

    public function del()
    {
        $product_id = $this->intInput('product_id');
        $this->ProductRepository->delProduct($product_id);
        $this->ProductRepository->delProductCache($product_id);
    }

    public function orders()
    {
        $size = $this->sizeInput();
        $where = $this->setOrdersWhere();
        $data = $this->ProductRepository->orders($where, $size);
        if($data->isEmpty()){
            $this->_code = 304;
            $this->_msg = '没有更多数据';
            return false;
        }
        $this->_data = $data;
        return true;
    }

    protected function setOrdersWhere():array
    {
        $where = [];
        $user_id = $this->intInput('user_id');
        if($user_id)$where['user_id'] = ['=', $user_id];
        $start_time = $this->intInput('start_time');
        $end_time = $this->intInput('end_time');
        if($start_time && $end_time)$where['created_at'] = ['BETWEEN', [$start_time, $end_time]];
        $phone = $this->strInput('phone');
        if($phone && !$user_id){
            $user_ids = $this->UserRepository->getUserIds($phone);
            $where['user_id'] = ['in', $user_ids];
        }
        return $where;
    }

}
