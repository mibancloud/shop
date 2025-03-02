<?php

namespace app\admin\controller\shopro\decorate;

use think\Db;
use app\admin\controller\shopro\Common;
use app\admin\model\shopro\decorate\Decorate as DecorateModel;
use app\admin\model\shopro\decorate\Page as PageModel;

class Template extends Common
{
    protected $model = null;

    protected $noNeedRight = ['select'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new DecorateModel();
    }

    /**
     * 查看
     */
    public function index()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $list = $this->model->sheepFilter()->with(['page' => function ($query) {
            return $query->where('type', 'in', ['home', 'user', 'diypage'])->field('decorate_id, type, image');
        }])->select();
        $this->success('获取成功', null, $list);
    }

    /**
     * 添加
     */
    public function add()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $params = $this->request->only(['type', 'name', 'memo', 'platform']);
        $this->svalidate($params, '.add');

        $this->model->save($params);

        $this->success('保存成功', null, $this->model);
    }



    /**
     * 详情
     *
     * @param  $id
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
    public function edit($id = null)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch('add');
        }

        operate_filter();
        $params = $this->request->only([
            'type', 'name', 'memo', 'platform'
        ]);

        $list = $this->model->where('id', 'in', $id)->select();
        $result = Db::transaction(function () use ($list, $params) {
            $count = 0;
            foreach ($list as $item) {
                $params['id'] = $item->id;
                $this->svalidate($params);
                $count += $item->save($params);
            }
            return $count;
        });

        if ($result) {
            $this->success('更新成功', null, $result);
        } else {
            $this->error('更新失败，未改变任何记录');
        }
    }



    /**
     * 复制
     *
     * @param  $id
     */
    public function copy($id)
    {
        $template = $this->model->where('id', $id)->find();
        if (!$template) {
            $this->error(__('No Results were found'));
        }

        Db::transaction(function () use ($template) {
            $params = [
                'name' => '复制 ' . $template->name,
                'type' => $template->type,
                'memo' => $template->memo,
                'platform' => $template->platform,
                'status' => 'disabled'
            ];
            $newTemplate = $this->model->create($params);

            $pageList = PageModel::where('decorate_id', $template->id)->select();

            $newPageList = [];
            foreach ($pageList as $page) {
                $newPageList[] = [
                    'decorate_id' => $newTemplate->id,
                    'type' => $page['type'],
                    'page' => json_encode($page['page']),
                    'image' => $page['image']
                ];
            }
            if (count($newPageList) > 0) {
                PageModel::insertAll($newPageList);
            }
        });

        $this->success('复制成功');
    }

    /**
     * 启用/禁用
     *
     * @param  $id
     */
    public function status($id)
    {
        $status = $this->request->param('status');
        $template = $this->model->where('id', $id)->find();
        if (!$template) {
            $this->error(__('No Results were found'));
        }

        operate_filter();
        $template->status = $status;
        $template->save();
        $this->success('操作成功', null, $template);
    }


    /**
     * 删除(支持批量)
     *
     * @param  $id
     */
    public function delete($id)
    {
        if (empty($id)) {
            $this->error(__('Parameter %s can not be empty', 'id'));
        }

        operate_filter();
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


    public function recyclebin()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $templates = $this->model->onlyTrashed()->sheepFilter()->paginate($this->request->param('list_rows', 10));
        $this->success('获取成功', null, $templates);
    }


    /**
     * 还原(支持批量)
     *
     * @param  $id
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
                PageModel::where('decorate_id', $item->id)->delete();

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


    /**
     * 选择自定义页面
     */
    public function select()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $list = $this->model->where('type', 'diypage')->with(['page' => function ($query) {
            return $query->field('decorate_id, type, image');
        }])->select();

        $this->success('获取成功', null, $list);
    }
}
