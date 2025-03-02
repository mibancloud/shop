<?php

namespace app\admin\controller\shopro\decorate;

use think\Db;
use app\admin\controller\shopro\Common;
use app\admin\model\shopro\decorate\Page as PageModel;

class Page extends Common
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new PageModel();
    }
    /**
     * 页面列表
     */
    public function index()
    {
        return $this->view->fetch();
    }

    /**
     * 页面详情
     *
     * @param  int $id
     */
    public function detail($id)
    {
        $type = $this->request->param('type');
        $page = $this->model->where(['decorate_id' => $id, 'type' => $type])->find();
        $this->success('获取成功', null, $page);
    }

    /**
     * 编辑
     *
     * @param  $id
     * @return \think\Response
     */
    public function edit($id = null)
    {
        $type = $this->request->param('type');
        $page = $this->request->param('page');
        $image = $this->request->param('image');

        $pageRow = PageModel::where(['decorate_id' => $id, 'type' => $type])->find();

        operate_filter();
        if ($pageRow) {
            $pageRow->page = $page;
            $pageRow->image = $image;
            $pageRow->save();
        } else {
            PageModel::create([
                'decorate_id' => $id,
                'type' => $type,
                'page' => $page,
                'image' => $image
            ]);
        }
        $this->success('保存成功');
    }

    /**
     * 预览
     */
    public function preview()
    {
        return $this->view->fetch();
    }
}
