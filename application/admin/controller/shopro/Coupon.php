<?php

namespace app\admin\controller\shopro;

use think\Db;
use app\admin\model\shopro\Coupon as CouponModel;
use app\admin\model\shopro\user\Coupon as UserCouponModel;
use addons\shopro\traits\CouponSend;
use app\admin\model\shopro\user\User;

/**
 * 优惠券
 */
class Coupon extends Common
{

    use CouponSend;

    protected $noNeedRight = ['select'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new CouponModel;
    }



    /**
     * 优惠券列表
     *
     * @return \think\Response
     */
    public function index()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $coupons = $this->model->sheepFilter()->paginate($this->request->param('list_rows', 10))->each(function ($coupon) {
            // 优惠券领取和使用数量
            $coupon->get_num = $coupon->get_num;
            $coupon->use_num = $coupon->use_num;
        });

        $result = [
            'coupons' => $coupons,
        ];

        $result['total_num'] = UserCouponModel::count();
        $result['expire_num'] = UserCouponModel::expired()->count();
        $result['use_num'] = UserCouponModel::used()->count();
        $result['use_percent'] = 0 . '%';
        if ($result['total_num']) {
            $result['use_percent'] = bcdiv(bcmul((string)$result['use_num'], '100'), (string)$result['total_num'], 1) . '%';
        }

        $this->success('获取成功', null, $result);
    }




    /**
     * 添加优惠券
     *
     * @return \think\Response
     */
    public function add()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $params = $this->request->only([
            'name', 'type', 'use_scope', 'items', 'amount', 'max_amount', 'enough',
            'stock', 'limit_num', 'get_time', 
            'use_time_type', 'use_time', 'start_days', 'days',
            'is_double_discount', 'description', 'status'
        ]);
        $this->svalidate($params, ".add");

        // 时间转换
        $getTime = explode(' - ', $params['get_time']);
        $useTime = explode(' - ', $params['use_time']);
        unset($params['get_time'], $params['use_time']);
        $params['get_start_time'] = $getTime[0] ?? 0;
        $params['get_end_time'] = $getTime[1] ?? 0;
        $params['use_start_time'] = $useTime[0] ?? 0;
        $params['use_end_time'] = $useTime[1] ?? 0;

        $this->model->save($params);
        
        $this->success('保存成功');
    }


    /**
     * 优惠券详情
     *
     * @param  $id
     * @return \think\Response
     */
    public function detail($id)
    {
        $coupon = $this->model->where('id', $id)->find();
        if (!$coupon) {
            $this->error(__('No Results were found'));
        }

        $coupon->items_value = $coupon->items_value;       // 可用范围值

        $this->success('获取成功', null, $coupon);
    }



    /**
     * 修改优惠券
     *
     * @return \think\Response
     */
    public function edit($id = null)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch('add');
        }

        $params = $this->request->only([
            'name', 'use_scope', 'items', 'amount', 'max_amount', 'enough',
            'stock', 'limit_num', 'get_time',
            'use_time_type', 'use_time', 'start_days', 'days',
            'is_double_discount', 'description', 'status'
        ]);
        $this->svalidate($params);

        // 时间转换
        if (isset($params['get_time'])) {
            $getTime = explode(' - ', $params['get_time']);
            
            $params['get_start_time'] = $getTime[0] ?? 0;
            $params['get_end_time'] = $getTime[1] ?? 0;
            unset($params['get_time']);
        }
        
        if (isset($params['use_time'])) {
            $useTime = explode(' - ', $params['use_time']);

            $params['use_start_time'] = $useTime[0] ?? 0;
            $params['use_end_time'] = $useTime[1] ?? 0;
            unset($params['use_time']);
        }

        $coupon = $this->model->where('id', $id)->find();
        if (!$coupon) {
            $this->error(__('No Results were found'));
        }

        $coupon->save($params);
        $this->success('更新成功');
    }



    /**
     * 删除优惠券
     *
     * @param string $id 要删除的优惠券
     * @return void
     */
    public function delete($id)
    {
        if (empty($id)) {
            $this->error(__('Parameter %s can not be empty', 'id'));
        }

        $id = explode(',', $id);
        $list = $this->model->where('id', 'in', $id)->select();
        $result = Db::transaction(function () use ($list) {
            $count = 0;
            foreach ($list as $item) {
                $count += $item->delete();
            }

            return $count;
        });

        if ($result) {
            $this->success('删除成功', null, $result);
        } else {
            $this->error(__('No rows were deleted'));
        }
    }


    /**
     * 优惠券列表
     */
    public function select()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $type = $this->request->param('type', 'page');
        
        $coupons = $this->model->sheepFilter();

        if ($type == 'select') {
            // 普通结果
            $coupons = $coupons->select();
        } elseif ($type == 'find') {
            $coupons = $coupons->find();
        } else {
            // 分页结果
            $coupons = $coupons->paginate($this->request->param('list_rows', 10));
        }

        $this->success('获取成功', null, $coupons);
    }



    public function send($id)
    {
        $user_ids = $this->request->post('user_ids/a');

        $users = User::whereIn('id', $user_ids)->select();

        Db::transaction(function () use ($users, $id) {
            $this->manualSend($users, $id);
        });

        $this->success('发放成功');
    }



    public function recyclebin()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $coupons = $this->model->onlyTrashed()->sheepFilter()->paginate($this->request->param('list_rows', 10));

        $this->success('获取成功', null, $coupons);
    }


    /**
     * 还原(支持批量)
     *
     * @param  $id
     * @return \think\Response
     */
    public function restore($id = null)
    {
        if (empty($id)) {
            $this->error(__('Parameter %s can not be empty', 'id'));
        }

        $items = $this->model->onlyTrashed()->where('id', 'in', $id)->select();
        $result = Db::transaction(function () use ($items) {
            $count = 0;
            foreach ($items as $item) {
                $count += $item->restore();
            }

            return $count;
        });

        if ($result) {
            $this->success('还原成功', null, $result);
        } else {
            $this->error(__('No rows were updated'));
        }
    }


    /**
     * 销毁(支持批量)
     *
     * @param  $id
     * @return \think\Response
     */
    public function destroy($id = null)
    {
        if (empty($id)) {
            $this->error(__('Parameter %s can not be empty', 'id'));
        }

        if ($id !== 'all') {
            $items = $this->model->onlyTrashed()->whereIn('id', $id)->select();
        } else {
            $items = $this->model->onlyTrashed()->select();
        }
        $result = Db::transaction(function () use ($items) {
            $count = 0;
            foreach ($items as $item) {
                // 删除商品
                $count += $item->delete(true);
            }
            return $count;
        });

        if ($result) {
            $this->success('销毁成功', null, $result);
        }
        $this->error('销毁失败');
    }
}
