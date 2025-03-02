<?php

namespace app\admin\controller\shopro\dispatch;

use think\Db;
use app\admin\controller\shopro\Common;
use app\admin\model\shopro\dispatch\Dispatch as DispatchModel;
use app\admin\model\shopro\dispatch\DispatchExpress as DispatchExpressModel;
use app\admin\model\shopro\dispatch\DispatchAutosend as DispatchAutosendModel;

/**
 * 配送管理
 */
class Dispatch extends Common
{

    protected $noNeedRight = ['select'];

    protected $expressModel;
    protected $autosendModel;
    protected $dispatch_type;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new DispatchModel;
        $this->expressModel = new DispatchExpressModel;
        $this->autosendModel = new DispatchAutosendModel;

        $this->dispatch_type = $this->request->param('type', 'express');
    }



    /**
     * 配送方式列表
     */
    public function index()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $dispatchs = $this->model->sheepFilter()->with([$this->dispatch_type])->where('type', $this->dispatch_type)->paginate($this->request->param('list_rows', 10));

        $this->success('获取成功', null, $dispatchs);
    }




    /**
     * 添加配送方式
     */
    public function add()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $params = $this->request->only([
            'name', 'type', 'status', 'express', 'autosend'
        ]);
        $this->svalidate($params, ".add");
        $data = $params[$this->dispatch_type] ?? [];
        unset($params['express'], $params['autosend']);

        if ($this->dispatch_type == 'express') {
            // 验证 express
            foreach ($data as $key => $express) {
                $this->svalidate($express, '.express');
            }
        } else if ($this->dispatch_type == 'autosend') {
            // 验证 autosend
            $this->svalidate($data, '.autosend');
        }

        Db::transaction(function () use ($params, $data) {
            unset($params['createtime'], $params['updatetime'], $params['id']);      // 删除时间
            $this->model->allowField(true)->save($params);

            if ($this->dispatch_type == 'express') {
                foreach ($data as $key => $express) {
                    $express['dispatch_id'] = $this->model->id;
                    $expressModel = new DispatchExpressModel();
                    unset($express['createtime'], $express['updatetime'], $express['id']);      // 删除时间
                    $expressModel->allowField(true)->save($express);
                }
            } else if ($this->dispatch_type == 'autosend') {
                $data['dispatch_id'] = $this->model->id;
                $autosendModel = new DispatchAutosendModel();
                unset($data['createtime'], $data['updatetime'], $data['id']);      // 删除时间
                $autosendModel->allowField(true)->save($data);
            }
        });

        $this->success('保存成功');
    }


    /**
     * 配送方式详情
     *
     * @param  $id
     */
    public function detail($id)
    {
        $dispatch = $this->model->with([$this->dispatch_type])->where('type', $this->dispatch_type)->where('id', $id)->find();
        if (!$dispatch) {
            $this->error(__('No Results were found'));
        }

        $this->success('获取成功', null, $dispatch);
    }



    /**
     * 修改配送方式
     */
    public function edit($id = null)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch('add');
        }

        $params = $this->request->only([
            'name', 'type', 'status', 'express', 'autosend'
        ]);
        $this->svalidate($params);
        $data = $params[$this->dispatch_type] ?? [];
        unset($params['express'], $params['autosend']);

        if ($this->dispatch_type == 'express') {
            // 验证 express
            foreach ($data as $key => $express) {
                $this->svalidate($express, '.express');
            }
        } else if ($this->dispatch_type == 'autosend') {
            // 验证 autosend
            $this->svalidate($data, '.autosend');
        }

        $id = explode(',', $id);
        $lists = $this->model->whereIn('id', $id)->select();
        Db::transaction(function () use ($lists, $params, $data) {
            foreach ($lists as $dispatch) {
                $dispatch->allowField(true)->save($params);
                if ($data) {
                    if ($this->dispatch_type == 'express') {
                        // 修改，不是只更新状态
                        $expressIds = array_column($data, 'id');
                        DispatchExpressModel::where('dispatch_id', $dispatch->id)->whereNotIn('id', $expressIds)->delete();      // 先删除被删除的记录
                        foreach ($data as $key => $express) {
                            if (isset($express['id']) && $express['id']) {
                                $expressModel = $this->expressModel->find($express['id']);
                            } else {
                                $expressModel = new DispatchExpressModel();
                                $express['dispatch_id'] = $dispatch->id;
                            }
                            $express['weigh'] = count($data) - $key;           // 权重
                            unset($express['createtime'], $express['updatetime']);
                            $expressModel && $expressModel->allowField(true)->save($express);
                        }
                    } else if ($this->dispatch_type == 'autosend') {
                        if (isset($data['id']) && $data['id']) {
                            $autosendModel = $this->autosendModel->find($data['id']);
                        } else {
                            $autosendModel = new DispatchAutosendModel();
                            $data['dispatch_id'] = $dispatch->id;
                        }

                        unset($data['createtime'], $data['updatetime']);      // 删除时间
                        $autosendModel->allowField(true)->save($data);
                    }
                }
            }
        });
        $this->success('更新成功');
    }



    /**
     * 删除配送方式
     *
     * @param string $id 要删除的配送方式列表
     * @return void
     */
    public function delete($id)
    {
        if (empty($id)) {
            $this->error(__('Parameter %s can not be empty', 'id'));
        }
        $id = explode(',', $id);
        $list = $this->model->with([$this->dispatch_type])->where('type', $this->dispatch_type)->where('id', 'in', $id)->select();
        Db::transaction(function () use ($list) {
            $count = 0;
            foreach ($list as $item) {
                if ($this->dispatch_type == 'express') {
                    // 删除相关的 express 数据
                    foreach ($item->express as $express) {
                        $express->delete();
                    }
                } else if ($this->dispatch_type == 'autosend') {
                    $item->{$this->dispatch_type}->delete();
                }

                $count += $item->delete();
            }

            return $count;
        });

        $this->success('删除成功');
    }


    /**
     * 获取所有配送模板
     */
    public function select()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $dispatchs = $this->model->sheepFilter()->field('id, name, type, status')->normal()->where('type', $this->dispatch_type)->select();

        $this->success('获取成功', null, $dispatchs);
    }
}
