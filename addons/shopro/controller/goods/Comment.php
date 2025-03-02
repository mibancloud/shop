<?php

namespace addons\shopro\controller\goods;

use addons\shopro\controller\Common;
use app\admin\model\shopro\goods\Comment as CommentModel;

class Comment extends Common
{

    protected $noNeedLogin = ['index', 'getType'];
    protected $noNeedRight = ['*'];

    public function index() 
    {
        $params = $this->request->param();
        $type = $params['type'] ?? 'all';
        $goods_id = $params['goods_id'] ?? 0;

        $comments = CommentModel::normal()->where('goods_id', $goods_id);

        if ($type != 'all' && isset(CommentModel::$typeAll[$type])) {
            $comments = $comments->{$type}();
        }

        $comments = $comments->order('id', 'desc')->paginate(request()->param('list_rows', 10));
            // ->each(function ($comment) {
            //     if ($comment->user) {
            //         $comment->user->nickname_hide = $comment->user->nickname_hide;
            //     }
            // })->toArray();

        // $data = $comments['data'];
        // foreach ($data as $key => &$comment) {
        //     if ($comment['user']) {
        //         $userData['id'] = $comment['user']['id'];
        //         $userData['nickname'] = $comment['user']['nickname_hide'];
        //         $userData['avatar'] = $comment['user']['avatar'];
        //         $userData['gender'] = $comment['user']['gender'];
        //         $userData['gender_text'] = $comment['user']['gender_text'];
        //         $comment['user'] = $userData;
        //     }
        // }
        // $comments['data'] = $data;

        $this->success('获取成功', $comments);
    }


    public function getType()
    {
        $goods_id = $this->request->param('goods_id');

        $type = array_values(CommentModel::$typeAll);

        foreach ($type as $key => $val) {
            $comment = CommentModel::normal()->where('goods_id', $goods_id);
            if ($val['code'] != 'all') {
                $comment = $comment->{$val['code']}();
            }
            $comment = $comment->count();
            $type[$key]['num'] = $comment;
        }

        $this->success('筛选类型', $type);
    }
}
