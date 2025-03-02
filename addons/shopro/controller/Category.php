<?php

namespace addons\shopro\controller;

use app\admin\model\shopro\Category as CategoryModel;

class Category extends Common
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function index()
    {
        $id = $this->request->param('id', 0);
        $category = CategoryModel::where('parent_id', 0)->normal()->order('weigh', 'desc')->order('id', 'desc');
        if ($id) {
            // 指定 id 分类，否则获取 权重最高的一级分类
            $category = $category->where('id', $id);
        }
        $category = $category->find();
        if (!$category) {
            $this->error(__('No Results were found'));
        }

        $childrenString = $category->getChildrenString($category);
        $categories = CategoryModel::where('id', $category->id)->normal()->with([$childrenString])->find();

        $this->success('商城分类', $categories);
    }
}
