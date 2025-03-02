<?php

namespace app\admin\controller\shopro;

use think\Db;
use addons\shopro\library\Tree;
use app\admin\model\shopro\Category as CategoryModel;

/**
 * 商品分类
 */
class Category extends Common
{

    protected $noNeedRight = ['select', 'goodsSelect'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new CategoryModel;
    }


    /**
     * 服务保障列表
     *
     * @return \think\Response
     */
    public function index()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }
        
        $categories = $this->model->sheepFilter()->where('parent_id', 0)->order('weigh', 'desc')->order('id', 'desc')->select();

        $this->success('获取成功', null, $categories);
    }




    /**
     * 添加服务保障
     *
     * @return \think\Response
     */
    public function add()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $params = $this->request->only([
            'name', 'style', 'description', 'weigh', 'categories'
        ]);
        $this->svalidate($params, ".add");
        $categories = json_decode($params['categories'], true);

        Db::transaction(function () use ($params, $categories) {
            $this->model->allowField(true)->save($params);

            //递归处理分类数据
            $this->createOrEditCategory($categories, $this->model->id);
        });
        $this->success('保存成功');
    }


    /**
     * 服务保障详情
     *
     * @param  $id
     * @return \think\Response
     */
    public function detail($id)
    {
        $category = $this->model->where('parent_id', 0)->where('id', $id)->find();
        if (!$category) {
            $this->error(__('No Results were found'));
        }
        
        $categories = $this->model->with('children.children')->where('parent_id', $category->id)->order('weigh', 'desc')->order('id', 'desc')->select();

        $this->success('获取成功', null, ['category' => $category, 'categories' => $categories]);
    }



    /**
     * 修改商品分类
     *
     * @return \think\Response
     */
    public function edit($id = null)
    {
        if (!$this->request->isPost()) {
            return $this->view->fetch('add');
        }

        $params = $this->request->only([
            'name', 'style', 'description', 'weigh', 'categories'
        ]);
        $this->svalidate($params, ".edit");
        $categories = json_decode($params['categories'], true);
        $category = $this->model->where('parent_id', 0)->where('id', $id)->find();
        if (!$category) {
            $this->error(__('No Results were found'));
        }
        Db::transaction(function () use ($category, $params, $categories) {
            $category->allowField(true)->save($params);

            //递归处理分类数据
            $this->createOrEditCategory($categories, $category->id);
        });
        $this->success('更新成功');
    }



    /**
     * 删除服务标签
     *
     * @param string $id 要删除的服务保障列表
     * @return void
     */
    public function delete($id)
    {
        if (empty($id)) {
            $this->error(__('Parameter %s can not be empty', 'id'));
        }

        $list = $this->model->with('children')->where('id', 'in', $id)->select();
        $result = Db::transaction(function () use ($list) {
            $count = 0;
            foreach ($list as $item) {
                if ($item->children) {
                    $this->error('请先删除子分类');
                }
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


    /**
     * 获取所有服务列表
     *
     * @return \think\Response
     */
    public function select()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $categories = (new Tree(function () {
            // 组装搜索条件，排序等
            return $this->model->field("id, name, parent_id, status, weigh")->normal()
                ->order('weigh', 'desc')->order('id', 'asc');
        }))->getTree(0);

        $this->success('获取成功', null, $categories);
    }



    /**
     * 商品列表左边分类
     *
     * @return void
     */
    public function goodsSelect()
    {
        $categories = $this->model->with(['children'])
            ->where('parent_id', 0)->order('weigh', 'desc')->order('id', 'desc')->select();

        $this->success('获取成功', null, $categories);
    }



    private function createOrEditCategory($categories, $parent_id)
    {
        foreach ($categories as $key => $data) {
            $data['parent_id'] = $parent_id;

            if (isset($data['id']) && $data['id']) {
                $category = $this->model->find($data['id']);
                if (!$category) {
                    $this->error(__('No Results were found'));
                }
                if (isset($data['deleted']) && $data['deleted'] == 1) {
                    $category->delete();
                } else {
                    $category->name = $data['name'];
                    $category->parent_id = $data['parent_id'];
                    $category->image = $data['image'];
                    $category->description = $data['description'] ?? null;
                    $category->status = $data['status'];
                    $category->weigh = $data['weigh'];
                    $category->save();
                }
            } else {
                if (!isset($data['deleted']) || !$data['deleted']) {
                    $category = new CategoryModel;
                    $category->name = $data['name'];
                    $category->parent_id = $data['parent_id'];
                    $category->image = $data['image'];
                    $category->description = $data['description'] ?? null;
                    $category->status = $data['status'];
                    $category->weigh = $data['weigh'];
                    $category->save();
                    $data['id'] = $category->id;
                }
            }

            if (isset($data['children']) && !empty($data['children']) && isset($data['id'])) {
                $this->createOrEditCategory($data['children'], $data['id']);
            }
        }
    }
}
