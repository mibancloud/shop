<?php

namespace app\admin\controller\shopro\data;

use app\admin\controller\shopro\Common;
use think\Db;

/**
 * 富文本
 */
class Richtext extends Common
{

    protected $noNeedRight = ['select'];

    /**
     * Richtext模型对象
     * @var \app\admin\model\shopro\data\Richtext
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\shopro\data\Richtext;

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

        $list = $this->model->sheepFilter()->paginate($this->request->request('list_rows', 10));
        $this->success('', null, $list);
    }



    public function select()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $type = $this->request->param('type', 'page');

        $list = $this->model->sheepFilter();

        if ($type == 'select') {
            // 普通结果
            $list = $list->select();
        } elseif ($type == 'find') {
            $list = $list->find();
        } else {
            // 分页结果
            $list = $list->paginate($this->request->request('list_rows', 10));
        }

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

        $params = $this->request->only(['title', 'content']);
        $this->svalidate($params, '.add');

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
    public function edit($id = null)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch('add');
        }

        $params = $this->request->only(['title', 'content']);

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
     * 删除(支持批量)
     *
     * @param  $id
     * @return \think\Response
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


}
