<?php

namespace app\admin\controller\shopro;

use think\Db;
use app\admin\model\shopro\Feedback as FeedbackModel;

class Feedback extends Common
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new FeedbackModel();
    }


    /**
     * 查看
     *
     * @return Response
     */
    public function index()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $feedbacks = $this->model->sheepFilter()->with('user')->paginate($this->request->param('list_rows', 10));

        $this->success('获取成功', null, $feedbacks);
    }



    /**
     * 详情
     *
     * @param  $id
     * @return \think\Response
     */
    public function detail($id)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $detail = $this->model->with('user')->where('id', $id)->find();
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
        $params = $this->request->only(['status', 'remark']);

        $list = $this->model->where('id', 'in', $id)->select();
        $result = Db::transaction(function () use ($list, $params) {
            $count = 0;
            foreach ($list as $item) {
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
     * 删除优惠券
     *
     * @param string $id 要删除的意见反馈
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
}
