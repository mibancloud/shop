<?php

namespace app\admin\controller\shopro;

use think\Db;
use app\admin\model\shopro\PayConfig as PayConfigModel;

/**
 * 支付配置
 */
class PayConfig extends Common
{

    protected $noNeedRight = ['select'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new PayConfigModel;
    }


    /**
     * 支付配置列表
     */
    public function index()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $payConfigs = $this->model->sheepFilter()->paginate($this->request->param('list_rows', 10));

        $this->success('获取成功', null, $payConfigs);
    }




    /**
     * 添加支付配置
     */
    public function add()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $params = $this->request->only([
            'name', 'type', 'params', 'status'
        ]);
        $this->svalidate($params, ".add");
        $this->svalidate($params['params'], '.' . $params['type']);     // 验证对应的支付参数是否设置完整

        $this->model->save($params);
        
        $this->success('保存成功');
    }


    /**
     * 支付配置详情
     *
     * @param  $id
     */
    public function detail($id)
    {
        $payConfig = $this->model->where('id', $id)->find();
        if (!$payConfig) {
            $this->error(__('No Results were found'));
        }

        $payConfig->append(['params']);
        $this->success('获取成功', null, pay_config_show($payConfig));
    }



    /**
     * 修改支付配置
     */
    public function edit($id = null)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch('add');
        }

        $params = $this->request->only([
            'name', 'params', 'status'
        ]);
        $this->svalidate($params);

        $payConfig = $this->model->where('id', $id)->find();
        if (!$payConfig) {
            $this->error(__('No Results were found'));
        }

        if (isset($params['params'])) {
            $this->svalidate($params['params'], '.' . $payConfig['type']);     // 验证对应的支付参数是否设置完整
        }

        $payConfig->save($params);
        $this->success('更新成功');
    }



    /**
     * 删除支付配置
     *
     * @param string $id 要删除的商品分类列表
     */
    public function delete($id)
    {
        if (empty($id)) {
            $this->error(__('Parameter %s can not be empty', 'id'));
        }

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
     * 获取所有支付配置
     */
    public function select()
    {
        $payConfigs = $this->model->sheepFilter()->normal()
            ->field('id, name, type,status')
            ->select();

        $this->success('获取成功', null, $payConfigs);
    }



    public function recyclebin()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $goods = $this->model->onlyTrashed()->sheepFilter()->paginate($this->request->param('list_rows', 10));
        $this->success('获取成功', null, $goods);
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
            foreach ($items as $config) {
                // 删除商品
                $count += $config->delete(true);
            }
            return $count;
        });

        if ($result) {
            $this->success('销毁成功', null, $result);
        }
        $this->error('销毁失败');
    }
}
