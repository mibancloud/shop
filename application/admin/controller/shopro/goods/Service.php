<?php

namespace app\admin\controller\shopro\goods;

use app\admin\controller\shopro\Common;
use think\Db;
use app\admin\model\shopro\goods\Service as ServiceModel;

/**
 * 服务保障
 */
class Service extends Common
{

    protected $noNeedRight = ['select'];
    
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new ServiceModel;
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

        $services = $this->model->sheepFilter()->paginate(request()->param('list_rows', 10));

        $this->success('获取成功', null, $services);
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
            'name', 'image', 'description'
        ]);
        $this->svalidate($params, ".add");

        Db::transaction(function () use ($params) {
            $this->model->save($params);
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
        $service = $this->model->where('id', $id)->find();

        if (!$service) {
            $this->error(__('No Results were found'));
        }

        $this->success('获取成功', null, $service);
    }



    /**
     * 修改服务保障
     *
     * @return \think\Response
     */
    public function edit($id = null)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch('add');
        }

        $params = $this->request->only([
            'name', 'image', 'description'
        ]);
        $this->svalidate($params, ".edit");

        $id = explode(',', $id);
        $list = $this->model->whereIn('id', $id)->select();
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

        $list = $this->model->where('id', 'in', $id)->select();
        Db::transaction(function () use ($list) {
            $count = 0;
            foreach ($list as $item) {
                $count += $item->delete();
            }

            return $count;
        });

        $this->success('删除成功');
    }


    /**
     * 获取所有服务列表
     *
     * @return \think\Response
     */
    public function select()
    {
        $services = $this->model->field('id, name')->select();

        $this->success('获取成功', null, $services);
    }
}
