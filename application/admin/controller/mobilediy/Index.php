<?php

namespace app\admin\controller\mobilediy;

use app\common\controller\Backend;
use app\admin\model\mobilediy\Mobilediy as MobilediyModel;

/**
 *  前端页面
 */
class Index extends Backend
{
    protected $model = null;

    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new MobilediyModel;
        $this->view->assign("statusList", $this->model->getStatusList());
    }

    /**
     * 列表
     */
    public function index()
    {
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {

            list($where, $sort, $order, $offset, $limit) = $this->buildparams(null);
            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $total = $this->model->getTotal();

            foreach ($list as $k => $v) {
                if ($v['status'] == 'cunsom') {
                    $v['short'] = 'pages/mobilediy/index/index';
                } else {
                    $v['short'] = 'pages/mobilediy/custom/index?page_id=' . $v['id'];
                }
                $v['url'] = $this->getUrl() . $v['short'];
            }

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 设置默认首页
     * @param $id
     * @return array
     * @throws \think\exception\DbException
     */
    public function sethome($id)
    {

        $model = $this->model->getdetail($id);
        if (!$model->setHome()) {
            $this->error(__('No Results were found'));
        }
        $this->success("设置成功！");
    }

    /**
     * 删除页面
     * @param $id
     * @return array
     * @throws \think\exception\DbException
     */
    public function del($ids = null)
    {
        $model = $this->model->getdetail($ids);
        if (!$model->setDelete()) {
            return $this->error($model->getError() ?: '删除失败');
        }
        $this->success('删除成功');
    }

    /**
     * 编辑
     * @param unknown $ids
     * @return string
     */
    public function edit($ids = null)
    {
        $model = $this->model->getdetail($ids);

        if (!$this->request->isAjax()) {
            return $this->view->fetch('', [
                'defaultData' => json_encode($model->getDefaultItems($this->request->scheme())),
                'jsonData'    => $model['page_data'],

            ]);
        }
        // 接收post数据
        $post = $this->request->post('data', null, null);
        if (!$model->edit($post)) {
            return $this->error('更新失败');
        }
        $this->success('更新成功');
    }

    /**
     * 编辑名称与权重
     * @param unknown $ids
     * @return string
     */
    public function editinfo($ids)
    {
        $details = $this->model->getdetail($ids);
        if (!$this->request->isAjax()) {
            return $this->view->fetch('', [
                'row' => $details
            ]);
        }
        $data = $this->request->post('row/a');

        if (!$this->model->setWeigh($ids, $data['page_name'], $data['weigh'])) {
            $this->error(__('修改失败'));
        }
        $this->success("修改成功！");


    }

    /**
     * 新增页面
     * @param unknown $ids
     * @return string
     */
    public function add()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch('add', [
                'defaultData' => json_encode($this->model->getDefaultItems($this->request->scheme())),
                'jsonData'    => json_encode(['page' => $this->model->getDefaultPage(), 'items' => []]),
            ]);
        }
        // 接收post数据
        $post = $this->request->post('data', null, null);

        if (!$this->model->add($post)) {
            return $this->error('添加失败');
        }

        $this->success('更新成功');
    }

    /**
     * 编辑富文本
     */
    public function editrichtext()
    {
        return $this->view->fetch();
    }

    /**
     * 选择URL
     */
    public function selectUrlPro()
    {
        $list = $this->model->getLinkUrl();
        $list['Inlay']['list'] = [];

        $pagelist = $this->model->getUrlList();
        foreach ($pagelist as $k => $v) {
            array_push($list['Inlay']['list'], ['id' => $v['id'], 'title' => $v['page_name'], 'path' => 'pages/mobilediy/custom/index?page_id=' . $v['id']]);
        }
        $result = array("rows" => $list);
        return json($result);
    }

    /**
     * H5 URL
     */
    public function getUrl()
    {
        $config = get_addon_config('mobilediy');
        return $config['h5_url'];
    }

}
