<?php

namespace app\admin\controller\shopro\wechat;

use app\admin\controller\shopro\Common;
use addons\shopro\facade\Wechat;
use think\Db;
use addons\shopro\exception\ShoproException;

class Menu extends Common
{

    protected $wechat = null;
    protected $model = null;


    public function _initialize()
    {
        parent::_initialize();
        $this->wechat = Wechat::officialAccountManage();
        $this->model = new \app\admin\model\shopro\wechat\Menu;
    }

    /**
     * 公众号配置
     */
    public function index()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $current = $this->getCurrentMenu();
        $data = $this->model->sheepFilter()->paginate(request()->param('list_rows', 10));

        $this->success('操作成功', null, ['current' => $current, 'list' => $data]);
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
     * 添加菜单
     *
     * @param  int $publish   发布状态:0=不发布,1=直接发布
     * @return \think\Response
     */
    public function add()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $publish = $this->request->param('publish', 0);
        $params = $this->request->only(['name', 'rules']);
        // 参数校验
        $this->svalidate($params, '.add');

        Db::transaction(function () use ($params, $publish) {
            $menu = $this->model->save($params);
            if ($menu && $publish) {
                $this->publishMenu($this->model->id);
            }
            return $menu;
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

        $menu = $this->model->get($id);

        $publish = $this->request->param('publish', 0);
        $params = $this->request->only(['name', 'rules']);

        // 参数校验
        $this->svalidate($params);

        $menu = Db::transaction(function () use ($params, $menu, $publish) {
            $menu->save($params);
            if ($publish) {
                $this->publishMenu($menu->id);
            }
            return $menu;
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
        Db::transaction(function () use ($id) {
            $menu = $this->model->get($id);
            $menu->delete();
        });

        $this->success('删除成功');
    }


    /**
     * 发布菜单
     *
     * @param  $id
     * @return \think\Response
     */
    public function publish($id)
    {
        Db::transaction(function () use ($id) {
            return $this->publishMenu($id);
        });

        $this->success('发布成功');
    }

    /**
     * 复制菜单
     *
     * @return Response
     */
    public function copy($id = 0)
    {
        if ($id == 0) {
            $data = [
                'name' => '复制 当前菜单',
                'rules' => $this->getCurrentMenu(),
            ];
        } else {
            $menu = $this->model->get($id);
            $data = [
                'name' => '复制 ' . $menu->name,
                'rules' => $menu->rules
            ];
        }

        $menu = $this->model->save($data);
        $this->success('复制成功');
    }

    // 发布菜单
    private function publishMenu($id)
    {
        $menu = $this->model->get($id);

        if ($this->setCurrentMenu($menu->rules)) {
            $this->model->where('id', '<>', $menu->id)->update(['status' => 0]);

            return $menu->save([
                'status' => 1,
                'publishtime' => time()
            ]);
        }
        return false;
    }


    // 获取当前菜单
    private function getCurrentMenu()
    {
        try {
            $currentMenu = $this->wechat->menu->current();
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        if (isset($currentMenu['selfmenu_info']['button'])) {
            $buttons = $currentMenu['selfmenu_info']['button'];
            foreach ($buttons as &$button) {
                if (isset($button['sub_button'])) {
                    $button['sub_button'] = $button['sub_button']['list'];
                }
            }
            return $buttons;
        } else {
            return [];
        }
    }

    // 设置菜单
    private function setCurrentMenu($rules)
    {
        try {
            $result = $this->wechat->menu->create($rules);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        if (isset($result['errcode']) && $result['errcode'] === 0) {
            return true;
        } else {
            $this->error($result['errmsg'] ?? '发布失败');
        }
    }
}
