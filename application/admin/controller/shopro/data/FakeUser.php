<?php

namespace app\admin\controller\shopro\data;

use app\admin\controller\shopro\Common;
use think\Db;

/**
 * 虚拟用户
 */
class FakeUser extends Common
{

    protected $noNeedRight = ['select', 'getRandom'];

    /**
     * Faq模型对象
     * @var \app\admin\model\shopro\data\Express
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\shopro\data\FakeUser;
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

        $list = $this->model->sheepFilter()->paginate($this->request->param('list_rows', 10));
        $this->success('', null, $list);
    }


    /**
     * 随机获取一个虚拟用户
     *
     * @return void
     */
    public function getRandom()
    {
        $userFake = $this->model->orderRaw('rand()')->find();

        $userFake ? $this->success('获取成功', null, $userFake) : $this->error('请在数据维护中添加虚拟用户');
    }


    public function select()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $list = $this->model->sheepFilter()->paginate($this->request->param('list_rows', 10));
        $this->success('', null, $list);
    }


    /**
     * 添加
     */
    public function add()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $params = $this->request->only(['username', 'nickname', 'mobile', 'password', 'avatar', 'gender', 'email']);
        $this->svalidate($params, '.add');

        $this->model->save($params);

        $this->success('保存成功', null, $this->model);
    }



    /**
     * 随机生成用户
     */
    public function random()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        set_time_limit(0);
        $num = $this->request->param('num', 1);

        for ($i = 0; $i < $num; $i++) {
            $style = [
                'adventurer',
                'big-smile',
                'bottts',
                'croodles',
                'croodles-neutral',
                'identicon',
                'micah'
            ];

            $username = gen_random_str(mt_rand(6, 15), mt_rand(0, 1));
            $avatarSources = [
                // "https://joeschmoe.io/api/v1/random",        // 生成的是 svg ，无法使用
                "https://avatars.dicebear.com/api/%s/" . $username . ".png",
                "https://api.multiavatar.com/" . $username . ".png"
            ];

            $avatar_url = $avatarSources[array_rand($avatarSources)];
            $avatar_url = sprintf($avatar_url, $style[array_rand($style)]);

            $store_path = '/uploads/' . date('Ymd') . '/' . md5(time() . mt_rand(1000, 9999)) . '.png';       // 存数据库路径
            $save_path = ROOT_PATH . 'public' . $store_path;                                            // 服务器绝对路径
            image_resize_save($avatar_url, $save_path);

            $fakeUser = new \app\admin\model\shopro\data\FakeUser();
            $fakeUser->username = $username;
            $fakeUser->nickname = $username;
            $fakeUser->mobile = random_mobile();
            $fakeUser->password = gen_random_str();
            $fakeUser->avatar = cdnurl($store_path, true);            // 这里存了完整路径
            $fakeUser->gender = mt_rand(0, 1);
            $fakeUser->email = random_email($fakeUser->mobile);
            $fakeUser->save();
        }

        $this->success('生成成功');
    }


    /**
     * 详情
     *
     * @param  $id
     * @return \think\Response
     */
    public function detail($id)
    {
        $detail = $this->model->where('id', $id)->find();
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

        $params = $this->request->only(['username', 'nickname', 'mobile', 'password', 'avatar', 'gender', 'email']);

        $list = $this->model->where('id', 'in', $id)->select();
        $result = Db::transaction(function () use ($list, $params) {
            $count = 0;
            foreach ($list as $item) {
                $params['id'] = $item->id;
                $this->svalidate($params);
                $count += $item->save($params);
            }
            return $count;
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
