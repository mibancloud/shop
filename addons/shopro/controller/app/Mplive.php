<?php

namespace addons\shopro\controller\app;

use addons\shopro\controller\Common;
use app\admin\model\shopro\app\mplive\Room;
use addons\shopro\facade\Wechat;
use addons\shopro\library\mplive\ServiceProvider;

class Mplive extends Common
{
    protected $noNeedLogin = ['getRoomList', 'getMpLink'];
    protected $noNeedRight = ['*'];

    public function getRoomList()
    {
        // 通过客户端访问触发，每10分钟刷新一次房间状态
        $lastUpdateTime = cache('wechatMplive.update');

        if ($lastUpdateTime < (time() - 60 * 10)) {
            cache('wechatMplive.update', time());
            $app = Wechat::miniProgram();
            (new ServiceProvider())->register($app);

            $res = $app->broadcast->getRooms();
            $data = [];
            if (isset($res['errcode']) && ($res['errcode'] !== 0 && $res['errcode'] !== 1001)) {
                $this->error($res['errmsg'] ?? '');
            } else {
                // 更新直播间列表
                Room::where('roomid', '>', 0)->delete();
                foreach ($res['room_info'] as $room) {
                    $room['status'] = $room['live_status'];
                    $room['type'] = $room['live_type'];
                    $data[] = $room;
                }
                Room::strict(false)->insertAll($data);
            }
        }

        $params = $this->request->param();

        $ids = $params['ids'] ?? '';

        $list = Room::where('roomid', 'in', $ids)->select();

        $this->success('获取成功', $list);
    }

    public function getMpLink()
    {
        $wechat = Wechat::miniProgram();
        (new ServiceProvider())->register($wechat);
        // TODO: 需防止被恶意消耗次数
        $res = $wechat->broadcast->urlscheme();
        $link = $res['openlink'];
        $this->success('获取成功', $link);
    }
}
