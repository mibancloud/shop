<?php

namespace app\admin\controller\shopro\goods;

use think\Db;
use app\admin\controller\shopro\Common;
use app\admin\model\shopro\goods\Comment as CommentModel;
use app\admin\model\shopro\data\FakeUser as FakeUserModel;

class Comment extends Common
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new CommentModel;
    }

    /**
     * 商品评价列表
     *
     * @return \think\Response
     */
    public function index()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $comments = $this->model->sheepFilter()->with(['goods' => function ($query) {
            $query->field('id,image,title');
        }, 'order' => function ($query) {
            $query->removeOption('soft_delete');
        }, 'order_item'])->paginate($this->request->param('list_rows', 10));

        $morphs = [
            'user' => \app\admin\model\shopro\user\User::class,
            'fake_user' => \app\admin\model\shopro\data\FakeUser::class
        ];
        $comments = morph_to($comments, $morphs, ['user_type', 'user_id']);

        $this->success('获取成功', null, $comments);
    }



    public function detail($id)
    {
        $comment = $this->model->with(['admin', 'goods' => function ($query) {
            $query->field('id,image,title,price');
        }, 'order' => function ($query) {
            $query->removeOption('soft_delete');
        }, 'order_item'])->where('id', $id)->find();

        if (!$comment) {
            $this->error(__('No Results were found'));
        }

        $morphs = [
            'user' => \app\admin\model\shopro\user\User::class,
            'fake_user' => \app\admin\model\shopro\data\FakeUser::class
        ];
        $comments = morph_to([$comment], $morphs, ['user_type', 'user_id']);

        $this->success('获取成功', null, $comments->all()[0]);
    }


    public function add() 
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $params = $this->request->only([
            'goods_id', 'user_id', 'level', 'content', 'images', 'status'
        ]);
        $params['user_type'] = 'fake_user';
        $this->svalidate($params, ".add");
        $fakeUser = FakeUserModel::find($params['user_id']);
        $params['user_nickname'] = $fakeUser ? $fakeUser->nickname : null;
        $params['user_avatar'] = $fakeUser ? $fakeUser->avatar : null;

        Db::transaction(function () use ($params) {
            $this->model->save($params);
        });
        $this->success('保存成功');
    }


    public function edit($id = null) 
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch('add');
        }

        $params = $this->request->only([
            'status'
        ]);

        $id = explode(',', $id);
        $list = $this->model->whereIn('id', $id)->select();
        $result = Db::transaction(function () use ($list, $params) {
            $count = 0;
            foreach ($list as $comment) {
                $comment->status = $params['status'] ?? 'hidden';
                $count += $comment->save();
            }

            return $count;
        });
        
        if ($result) {
            $this->success('更新成功', null, $result);
        } else {
            $this->error('更新失败，未改变任何记录');
        }
    }


    public function reply($id)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $params = $this->request->only([
            'content'
        ]);
        $this->svalidate($params, '.reply');

        $comment = $this->model->noReply()->find($id);
        if (!$comment) {
            $this->error(__('No Results were found'));
        }

        $comment->reply_content = $params['content'];
        $comment->reply_time = time();
        $comment->admin_id = $this->auth->id;
        $comment->save();

        $this->success('回复成功');
    }

    
    /**
     * 删除商品评价
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
     * 评价回收站
     *
     * @return void
     */
    public function recyclebin() 
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $comments = $this->model->onlyTrashed()->sheepFilter()->paginate($this->request->param('list_rows', 10));

        $this->success('获取成功', null, $comments);
    }


    /**
     * 还原(支持批量)
     *
     * @param  $id
     * @return \think\Response
     */
    public function restore($id = null)
    {
        if (empty($id)) {
            $this->error(__('Parameter %s can not be empty', 'id'));
        }
        $items = $this->model->onlyTrashed()->where('id', 'in', $id)->select();
        Db::transaction(function () use ($items) {
            $count = 0;
            foreach ($items as $item) {
                $count += $item->restore();
            }

            return $count;
        });

        $this->success('还原成功');
    }


    /**
     * 销毁(支持批量)
     *
     * @param  $id
     * @return \think\Response
     */
    public function destroy($id = null)
    {
        if (empty($id)) {
            $this->error(__('Parameter %s can not be empty', 'id'));
        }
        if ($id !== 'all') {
            $items = $this->model->onlyTrashed()->whereIn('id', $id)->select();
        } else {
            $items = $this->model->onlyTrashed()->select();
        }
        $result = Db::transaction(function () use ($items) {
            $count = 0;
            foreach ($items as $comment) {
                // 删除评价
                $count += $comment->delete(true);
            }
            return $count;
        });

        if ($result) {
            $this->success('销毁成功', null, $result);
        }
        $this->error('销毁失败');
    }

}
