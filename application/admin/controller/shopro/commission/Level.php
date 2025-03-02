<?php

namespace app\admin\controller\shopro\commission;

use app\admin\controller\shopro\Common;
use app\admin\model\shopro\commission\Level as LevelModel;
use think\Db;

class Level extends Common
{

    protected $noNeedRight = ['select'];

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new LevelModel();
    }

    /**
     * 查看
     */
    public function index()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $defaultLevel = $this->model->find(1);
        if (!$defaultLevel) {
            $this->model->save([
                'name' => '默认等级',
                'level' => 1,
                'commission_rules' => [
                    'commission_1' => '0.00',
                    'commission_2' => '0.00',
                    'commission_3' => '0.00'
                ]
            ]);
        }
        $list = $this->model->sheepFilter()->select();

        $this->success('全部等级', null, $list);
    }

    /**
     * 添加
     */
    public function add()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $params = $this->request->only(['name', 'level', 'image', 'commission_rules', 'upgrade_type', 'upgrade_rules']);

        $this->model->save($params);

        $this->success('保存成功', null, $this->model);
    }

    /**
     * 编辑
     *
     * @param  $id
     */
    public function edit($id = null)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch('add');
        }

        $params = $this->request->only(['level', 'name', 'image', 'commission_rules', 'upgrade_type', 'upgrade_rules']);

        $result = Db::transaction(function () use ($id, $params) {

            $this->svalidate($params);

            $data = $this->model->where('level', $id)->find();
            if (!$data) {
                $this->error(__('No Results were found'));
            }

            return $data->save($params);
        });

        if ($result) {
            $this->success('更新成功', null, $result);
        } else {
            $this->error('更新失败');
        }
    }

    /**
     * 详情
     *
     * @param  $id
     * @return \think\Response
     */
    public function detail($id)
    {
        $detail = $this->model->get($id);
        if (!$detail) {
            $this->error(__('No Results were found'));
        }
        $this->success('等级详情', null, $detail);
    }

    /**
     * 删除
     *
     * @param  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        if (empty($id)) {
            $this->error(__('Parameter %s can not be empty', 'id'));
        }

        $result = Db::transaction(function () use ($id) {
            return $this->model->where('level', $id)->delete();
        });

        if ($result) {
            $this->success('删除成功', null, $result);
        } else {
            $this->error(__('No rows were deleted'));
        }
    }

    // 选择分销商等级
    public function select()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $data = $this->model->sheepFilter()->field('level, name, image, commission_rules')->select();
        $this->success('选择等级', null, $data);
    }
}
