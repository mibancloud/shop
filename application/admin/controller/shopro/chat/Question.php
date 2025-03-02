<?php

namespace app\admin\controller\shopro\chat;

use think\Db;
use app\admin\controller\shopro\Common;
use app\admin\model\shopro\chat\Question as ChatQuestion;

class Question extends Common
{
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new ChatQuestion;
    }

    /**
     * 猜你想问列表
     *
     * @return \think\Response
     */
    public function index()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $questions = $this->model->sheepFilter()->paginate($this->request->param('list_rows', 10));
        $this->success('获取成功', null, $questions);
    }


    /**
     * 猜你想问添加
     */
    public function add()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $params = $this->request->only(['room_id', 'title', 'status', 'weigh']);
        $params['content'] = $this->request->param('content', '', null);      // content 不经过全局过滤
        $this->svalidate($params, ".add");

        $this->model->save($params);
        $this->success('保存成功', null, $this->model);
    }



    /**
     * 猜你想问详情
     *
     * @param  $id
     */
    public function detail($id)
    {
        $question = $this->model->where('id', $id)->find();
        if (!$question) {
            $this->error(__('No Results were found'));
        }

        $this->success('获取成功', null, $question);
    }



    /**
     * 猜你想问编辑
     *
     * @param  $id
     * @return \think\Response
     */
    public function edit($id = null)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch('add');
        }

        $params = $this->request->only(['room_id', 'title', 'status', 'weigh']);
        $this->request->has('content') && $params['content'] = $this->request->param('content', '', null);      // content 不经过全局过滤
        $this->svalidate($params);

        $id = explode(',', $id);
        $list = $this->model->where('id', 'in', $id)->select();
        $result = Db::transaction(function () use ($list, $params) {
            $count = 0;
            foreach ($list as $item) {
                $params['id'] = $item->id;
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
     * 删除猜你想问
     *
     * @param  $id
     * @return \think\Response
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
}
