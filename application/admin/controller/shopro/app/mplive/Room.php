<?php

namespace app\admin\controller\shopro\app\mplive;

use app\admin\model\shopro\app\mplive\Room as MpliveRoomModel;

/**
 * 小程序直播
 */
class Room extends Index
{

    protected $noNeedRight = ['select'];

    // 直播间列表
    public function index()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $list = (new MpliveRoomModel)->sheepFilter()->select();

        $this->success('', null, $list);
    }

    // 直播间详情
    public function detail($id)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $room = (new MpliveRoomModel)->where('roomid', $id)->findOrFail();

        $this->success('', null, $room);
    }

    // 同步直播间列表
    public function sync()
    {
        $res = $this->wechat->broadcast->getRooms();
        $data = [];

        $this->catchLiveError($res);

        MpliveRoomModel::where('roomid', '>', 0)->delete();
        foreach ($res['room_info'] as $room) {
            $room['status'] = $room['live_status'];
            $room['type'] = $room['live_type'];
            $data[] = $room;
        }

        MpliveRoomModel::strict(false)->insertAll($data);
        $list = MpliveRoomModel::select();

        $this->success('', null, $list);
    }

    // 创建直播间
    public function add()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $params = $this->request->param();

        $data = [
            'name' => $params['name'],  // 房间名字
            'coverImg' => $this->uploadMedia($params['cover_img']),   // 通过 uploadfile 上传，填写 mediaID
            'shareImg' =>   $this->uploadMedia($params['share_img']),  //通过 uploadfile 上传，填写 mediaID
            'feedsImg' =>  $this->uploadMedia($params['feeds_img']),   //通过 uploadfile 上传，填写 mediaID
            'startTime' => $params['start_time'],   // 开始时间
            'endTime' => $params['end_time'], // 结束时间
            'anchorName' => $params['anchor_name'],  // 主播昵称
            'anchorWechat' => $params['anchor_wechat'],  // 主播微信号
            'subAnchorWechat' => $params['sub_anchor_wechat'],  // 主播副号微信号
            'isFeedsPublic' => $params['is_feeds_public'], // 是否开启官方收录，1 开启，0 关闭
            'type' => $params['type'], // 直播类型，1 推流 0 手机直播
            'closeLike' => $params['close_like'], // 是否关闭点赞 1：关闭
            'closeGoods' => $params['close_goods'], // 是否关闭商品货架，1：关闭
            'closeComment' => $params['close_comment'], // 是否开启评论，1：关闭
            'closeReplay' => $params['close_replay'], // 是否关闭回放 1 关闭
            'closeKf' => $params['close_kf'], // 是否关闭客服，1 关闭
        ];

        $res = $this->wechat->broadcast->createLiveRoom($data);

        $this->catchLiveError($res);

        return $this->sync();
    }

    // 更新直播间
    public function edit($id = null)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch('add');
        }

        $params = $this->request->param();

        $data = [
            'id' => $id,
            'name' => $params['name'],  // 房间名字
            'coverImg' => $this->uploadMedia($params['cover_img']),   // 通过 uploadfile 上传，填写 mediaID
            'shareImg' =>   $this->uploadMedia($params['share_img']),  //通过 uploadfile 上传，填写 mediaID
            'feedsImg' =>  $this->uploadMedia($params['feeds_img']),   //通过 uploadfile 上传，填写 mediaID
            'startTime' => $params['start_time'],   // 开始时间
            'endTime' => $params['end_time'], // 结束时间
            'anchorName' => $params['anchor_name'],  // 主播昵称
            'anchorWechat' => $params['anchor_wechat'],  // 主播昵称
            'isFeedsPublic' => $params['is_feeds_public'], // 是否开启官方收录，1 开启，0 关闭
            'type' => $params['type'], // 直播类型，1 推流 0 手机直播
            'closeLike' => $params['close_like'], // 是否关闭点赞 1：关闭
            'closeGoods' => $params['close_goods'], // 是否关闭商品货架，1：关闭
            'closeComment' => $params['close_comment'], // 是否开启评论，1：关闭
            'closeReplay' => $params['close_replay'], // 是否关闭回放 1 关闭
            'closeKf' => $params['close_kf'], // 是否关闭客服，1 关闭
        ];

        $res = $this->wechat->broadcast->updateLiveRoom($data);

        $this->catchLiveError($res);

        return $this->sync();
    }

    // 删除直播间
    public function delete($id)
    {
        $res = $this->wechat->broadcast->deleteLiveRoom([
            'id' => $id,
        ]);

        $this->catchLiveError($res);

        MpliveRoomModel::where('roomid', $id)->delete();
        $this->success('操作成功');
    }

    // 推流地址
    public function pushUrl($id)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $res = $this->wechat->broadcast->getPushUrl([
            'roomId' => $id
        ]);

        $this->catchLiveError($res);

        $this->success('', null, ['pushAddr' => $res['pushAddr']]);
    }

    // 分享二维码
    public function qrcode($id)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $res = $this->wechat->broadcast->getShareQrcode([
            'roomId' => $id
        ]);

        $this->catchLiveError($res);

        $this->success('', null, ['pagePath' => $res['pagePath'], 'cdnUrl' => $res['cdnUrl']]);
    }

    // 查看回放
    public function playback($id)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $res = $this->wechat->broadcast->getPlaybacks((int)$id, (int)$start = 0, (int)$limit = 10);

        $this->catchLiveError($res);

        $data = $res['live_replay'];

        $this->success('', null, $data);
    }

    public function select()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $list = (new MpliveRoomModel)->sheepFilter()->select();

        $this->success('', null, $list);
    }
}
