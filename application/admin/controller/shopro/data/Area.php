<?php

namespace app\admin\controller\shopro\data;

use app\admin\controller\shopro\Common;
use think\Db;

/**
 * 地区管理
 */
class Area extends Common
{

    protected $noNeedRight = ['select'];

    /**
     * Faq模型对象
     * @var \app\admin\model\shopro\data\Express
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\shopro\data\Area;
    }


    /**
     * 查看
     *
     * @return string|Json
     * @throws \think\Exception
     * @throws DbException
     */
    public function index()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $list = $this->model->sheepFilter()->with('children.children')->where('pid', 0)->select();               // 查询全部
        $this->success('', null, $list);
    }


    public function select()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $level = $this->request->param('level', 'all');
        $with = ['children.children'];
        if ($level == 'city') {
            $with = ['children'];
        } else if ($level == 'province') {
            $with = [];
        }
        
        $list = $this->model->with($with)->where('pid', 0)->field('id, name, pid, level')->select();               // 查询全部
        $this->success('', null, $list);
    }


    /**
     * 添加
     */
    public function add()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $params = $this->request->only(['id', 'pid', 'name']);

        $params['level'] = 'province';
        if (isset($params['pid']) && $params['pid']) {
            $parent = $this->model->find($params['pid']);
            if (!$parent) {
                $this->error(__('No Results were found'));
            }
            if ($parent['level'] == 'province') {
                $params['level'] = 'city';
            } else if ($parent['level'] == 'city') {
                $params['level'] = 'district';
            }
        }
        $this->model->save($params);

        $this->success('保存成功', null, $this->model);
    }




    /**
     * 详情
     *
     * @param  $id
     * @return \think\Response
     */
    public function detail($id)
    {
        $detail = $this->model->where('id', $id)->find();
        if (!$detail) {
            $this->error(__('No Results were found'));
        }
        $this->success('获取成功', null, $detail);
    }


    /**
     * 编辑(支持批量)
     */
    public function edit($old_id = null)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch('add');
        }

        $params = $this->request->only(['id', 'pid', 'name']);
        $params['level'] = 'province';
        if (isset($params['pid']) && $params['pid']) {
            $parent = $this->model->find($params['pid']);
            if (!$parent) {
                $this->error(__('No Results were found'));
            }
            if ($parent['level'] == 'province') {
                $params['level'] = 'city';
            } else if ($parent['level'] == 'city') {
                $params['level'] = 'district';
            }
        }

        $area = $this->model->where('id', 'in', $old_id)->find();
        if (!$area) {
            $this->error(__('No Results were found'));
        }

        $area->save($params);
        $this->success('更新成功', null, $area);
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

        $area = $this->model->where('id', 'in', $id)->find();
        if (!$area) {
            $this->error(__('No Results were found'));
        }

        $children = $this->model->where('pid', $id)->count();
        if ($children) {
            $this->error('请先删除下级地市');
        }
        
        $area->delete();
        $this->success('删除成功');
    }
}
