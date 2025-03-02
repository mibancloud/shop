<?php

namespace app\admin\controller\shopro\wechat;

use app\admin\controller\shopro\Common;
use think\Db;

/**
 * 常见问题
 */
class Reply extends Common
{

    /**
     * Faq模型对象
     * @var \app\admin\model\shopro\wechat\Reply
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\shopro\wechat\Reply;
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

        $group = $this->request->param('group', 'keywords');
        $data = $this->model->sheepFilter()->where('group', $group)->select(); 
        $this->success('操作成功', null, $data);
    }

    /**
     * 添加
     */
    public function add()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $params = $this->request->only(['group', 'keywords', 'type', 'status', 'content']);

        Db::transaction(function () use ($params) {
            $result = $this->model->save($params);
            if($result) {
                $reply = $this->model;
                if($reply->group !== 'keywords' && $reply->status === 'enable') {
                    $this->model->where('group', $reply->group)->where('id', '<>', $reply->id)->enable()->update(['status' => 'disabled']);
                }
            }
            return $result;
        });

        $this->success('保存成功');
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
        $reply = $this->model->get($id);
        $params = $this->request->only(['keywords', 'type', 'status', 'content']);

        // 参数校验
        // $this->svalidate($params);

        $result = Db::transaction(function () use ($params, $reply) {
            $result = $reply->save($params);
            if($result) {
                if($reply->group !== 'keywords' && $reply->status === 'enable') {
                    $this->model->where('group', $reply->group)->where('id', '<>', $reply->id)->enable()->update(['status' => 'disabled']);
                }
            }
            return $result;
        });

        if ($result) {
            $this->success('更新成功', null, $result);
        } else {
            $this->error('更新失败');
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
