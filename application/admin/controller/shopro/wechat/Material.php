<?php

namespace app\admin\controller\shopro\wechat;

use app\admin\controller\shopro\Common;
use addons\shopro\facade\Wechat;
use think\Db;
use addons\shopro\exception\ShoproException;

class Material extends Common
{

    protected $wechat = null;
    protected $model = null;
    protected $noNeedRight = ['select'];


    public function _initialize()
    {
        parent::_initialize();
        $this->wechat = Wechat::officialAccountManage();
        $this->model = new \app\admin\model\shopro\wechat\Material;
    }

    /**
     * 素材列表
     */
    public function index()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $list_rows = intval($this->request->param('list_rows', 10));
        $page = intval($this->request->param('page', 1));
        $type = $this->request->param('type', 'news');
        $offset = intval(($page - 1) * $list_rows);

        if (in_array($type, ['text', 'link'])) {
            $data = $this->model->sheepFilter()->where('type', $type)->paginate(request()->param('list_rows', 10));
        } else {
            // 使用微信远程素材列表
            try {
                $res = $this->wechat->material->list($type, $offset, $list_rows);
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
          
            $data = [
                'current_page' => $page,
                'data' => $res['item'],
                'last_page' => intval(ceil($res['total_count'] / $list_rows)),
                'per_page' => $list_rows,
                'total' => intval($res['total_count']),
            ];
        }

        $this->success('', null, $data);
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
     * 添加
     *
     * @return \think\Response
     */
    public function add()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $params = $this->request->only(['type', 'content']);

        if ($params['type'] === 'text') {
            $params['content'] =  urldecode($params['content']);
        }

        Db::transaction(function () use ($params) {
            $this->model->save($params);
        });
        $this->success('保存成功');
    }

    /**
     * 编辑
     *
     * @param  $id
     * @return \think\Response
     */
    public function edit($id = null)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch('add');
        }

        $material = $this->model->get($id);
        $params = $this->request->only(['type', 'content']);
        if ($params['type'] === 'text') {
            $params['content'] =  urldecode($params['content']);
        }

        Db::transaction(function () use ($params, $material) {
            $material->save($params);
        });

        $this->success('更新成功');
    }


    /**
     * 删除
     *
     * @param  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        $is_real = $this->request->param('is_real', 0);
        Db::transaction(function () use ($id, $is_real) {
            $menu = $this->model->get($id);
            if ($is_real) {
                $menu->force()->delete();
            } else {
                $menu->delete();
            }
        });

        $this->success('删除成功');
    }

    /**
     * 菜单列表
     *
     * @return Response
     */
    public function select()
    {
        $list_rows = intval($this->request->param('list_rows', 10));
        $page = intval($this->request->param('page', 1));
        $type = $this->request->param('type', 'news');
        $offset = intval(($page - 1) * $list_rows);

        if (in_array($type, ['text', 'link'])) {
            $data = $this->model->where('type', $type)->order('id desc')->paginate(request()->param('list_rows', 10));
        } else {
            // 使用微信远程素材列表
            try {
                $res = $this->wechat->material->list($type, $offset, $list_rows);
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }

            $data = [
                'current_page' => $page,
                'data' => $res['item'],
                'last_page' => intval(ceil($res['total_count'] / $list_rows)),
                'per_page' => $list_rows,
                'total' => intval($res['total_count']),
            ];
        }
        $this->success('获取成功', null, $data);
    }
}
