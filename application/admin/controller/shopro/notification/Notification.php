<?php

namespace app\admin\controller\shopro\notification;

use app\admin\controller\shopro\Common;
use app\admin\model\shopro\notification\Notification as NotificationModel;
use app\admin\model\shopro\Admin;

class Notification extends Common
{

    protected $noNeedRight = ['index', 'read', 'delete', 'notificationType'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new NotificationModel;
    }


    /**
     * 获取管理员的消息列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $admin = auth_admin();
        $admin = Admin::where('id', $admin['id'])->find();

        $notifiable_type = $admin->getNotifiableType();
        $notifications = NotificationModel::sheepFilter(false)
            ->where('notifiable_type', $notifiable_type)
            ->where('notifiable_id', $admin['id'])
            ->order('createtime', 'desc')
            ->paginate($this->request->param('list_rows', 10));

        $this->success('消息列表', null, $notifications);
    }


    /**
     * 指定消息标记已读
     *
     * @param string $id
     * @return void
     */
    public function read($id)
    {
        $admin = auth_admin();
        $admin = Admin::where('id', $admin['id'])->find();

        $notifiable_type = $admin->getNotifiableType();
        $notification = NotificationModel::sheepFilter()
            ->where('notifiable_type', $notifiable_type)
            ->where('notifiable_id', $admin['id'])
            ->where('id', $id)
            ->find();
        
        if (!$notification) {
            $this->error(__('No Results were found'));
        }

        $notification->read_time = time();
        $notification->save();

        $this->success('已读成功', null, $notification);
    }


    /**
     * 删除已读消息
     *
     * @return void
     */
    public function delete()
    {
        $admin = auth_admin();
        $admin = Admin::where('id', $admin['id'])->find();

        // 将已读的消息全部删除
        $notifiable_type = $admin->getNotifiableType();
        NotificationModel::sheepFilter()
            ->where('notifiable_type', $notifiable_type)
            ->where('notifiable_id', $admin['id'])
            ->whereNotNull('read_time')
            ->delete();

        $this->success('删除成功');
    }




    /**
     * 消息类别，以及未读消息数
     *
     * @return void
     */
    public function notificationType()
    {
        $admin = auth_admin();

        $notificationType = NotificationModel::$notificationType;

        $newType = [];
        foreach ($notificationType as $type => $name) {
            // 未读消息数
            $unread_num = NotificationModel::where('notifiable_type', 'admin')->where('notifiable_id', $admin['id'])
                ->notificationType($type)
                ->where('read_time', null)
                ->order('createtime', 'desc')->count();

            $newType[] = [
                'label' => $name,
                'value' => $type,
                'unread_num' => $unread_num
            ];
        }

        $result = [
            'unread_num' => array_sum(array_column($newType, 'unread_num')),
            'notification_type' => $newType
        ];

        $this->success('消息类型', null, $result);
    }
}