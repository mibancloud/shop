<?php

namespace addons\shopro\library\chat\provider\getter;

use think\helper\Str;
use addons\shopro\exception\ShoproException;
use addons\shopro\library\chat\traits\DebugEvent;
use addons\shopro\library\chat\Getter;
use addons\shopro\controller\traits\UnifiedToken;
use app\admin\model\shopro\chat\User as ChatUser;
use app\admin\model\shopro\chat\ServiceLog as ChatServiceLog;
use app\admin\model\shopro\chat\Record as ChatRecord;
use app\admin\model\shopro\chat\CustomerService as ChatCustomerService;
use app\admin\model\shopro\chat\CustomerServiceUser as ChatCustomerServiceUser;
use app\admin\model\shopro\chat\Question as ChatQuestion;
use app\admin\model\shopro\chat\CommonWord as ChatCommonWord;

/**
 * 从数据库获取
 */
class Db
{

    /**
     * token 验证助手
     */
    use UnifiedToken;

    /**
     * getter 实例
     *
     * @var Getter
     */
    protected $getter;

    /**
     * 实例化的模型数组
     *
     * @var array
     */
    protected $models = [];

    public function __construct(Getter $getter)
    {
        $this->getter = $getter;
    }


    /**
     * 获取动态模型实例
     *
     * @param string $type      动态类型
     * @param string $model     模型名称
     * @param boolean $is_instance  是否实例化
     * @return string|object    返回模型
     */
    public function getModel($type, $model, $is_instance = true)
    {
        $key = $type . '_' . $model . '_' . strval($is_instance);
        if (isset($this->models[$key])) {
            return $this->models[$key];
        }

        switch($type) {
            case 'auth' :
                if ($model == 'user') {
                    $class = '\\app\\admin\\model\\shopro\\user\\' . ucfirst($model);
                } else {
                    $class = '\\app\\admin\\model\\shopro\\' . ucfirst($model);
                }
                break;
        }

        if ($is_instance) {
            $class = new $class;
        }

        $this->models[$key] = $class;

        return $class;
    }


    /**
     * 通过token 获取用户 id
     *
     * @param string $token
     * @param string $auth      认证类型
     * @return mixed
     */
    public function getAuthIdFromToken($token, $auth)
    {
        // 获取加密数据
        $content = $this->getUnifiedContent($token);
        // 判断并返回用户 id
        if ($content && strpos($content, $auth) !== false) {
            return str_replace($auth . ':', '', $content);
        }
        return false;
    }



    /**
     * 通过 token 获取 用户信息
     *
     * @param string $token
     * @param string $auth  认证类型
     * @return null|object
     */
    public function getAuthByToken($token, $auth)
    {
        // 获取用户 id
        $user_id = $this->getAuthIdFromToken($token, $auth);

        // 获取用户
        return $this->getAuthById($user_id, $auth);
    }



    /**
     * 通过 id 获取用户
     *
     * @param int $id
     * @param string $auth
     * @return null|object
     */
    public function getAuthById($id, $auth)
    {
        return $this->getModel('auth', $auth)->where('id', $id)->find();
    }



    /**
     * 通过 session_id 获取chat 用户
     *
     * @param string $session_id
     * @param string $auth      认证类型
     * @return null|object
     */
    public function getChatUserBySessionId($session_id)
    {
        $chatUser = ChatUser::where('session_id', $session_id)->order('id asc')->find();

        return $chatUser;
    }


    /**
     * 通过 auth 获取 library 用户信息
     *
     * @param integer $auth_id
     * @param string $auth
     * @return void
     */
    public function getChatUserByAuth($auth_id, $auth)
    {
        $chatUser = ChatUser::where('auth', $auth)->where('auth_id', $auth_id)->order('id asc')->find();

        return $chatUser;
    }



    /**
     * 获取最后一次被服务记录
     *
     * @param string $room_id
     * @param integer $chat_user_id
     * @return object|null
     */
    public function getLastServiceLogByChatUser($room_id, $chat_user_id)
    {
        return ChatServiceLog::where('room_id', $room_id)
                    ->where('chat_user_id', $chat_user_id)
                    ->where('customer_service_id', '<>', 0)
                    ->order('id', 'desc')->find();
    }



    /**
     * 获取郑子昂服务中的记录
     *
     * @param string $room_id
     * @param integer $chat_user_id
     * @return object
     */
    public function getServiceLogIngByChatUser($room_id, $chat_user_id, $customer_service_id = 0)
    {
        return ChatServiceLog::where('room_id', $room_id)
                    ->where('chat_user_id', $chat_user_id)
                    ->where(function ($query) use ($customer_service_id) {
                        $query->where(function($query) use ($customer_service_id) {
                            $query->where('status', 'waiting')->where('customer_service_id', 0);
                        })->whereOr(function ($query) use ($customer_service_id) {
                            $query->where('status', 'ing')->where('customer_service_id',  $customer_service_id);
                        });
                    })
                    ->order('id', 'desc')->find();
    }



    /**
     * 添加服务记录 
     *
     * @param string $room_id
     * @param array $chatUser
     * @param array $customerService
     * @param string $status
     * @return void
     */
    public function addServiceLog($room_id, $chatUser, $customerService, $status) 
    {
        $chatServiceLog = new ChatServiceLog();

        $chatServiceLog->chat_user_id = $chatUser ? $chatUser['id'] : 0;
        $chatServiceLog->customer_service_id = $customerService ? $customerService['id'] : 0;
        $chatServiceLog->room_id = $room_id;
        $chatServiceLog->starttime = time();
        $chatServiceLog->endtime = $status == 'end' ? time() : null;
        $chatServiceLog->status = $status;
        $chatServiceLog->save();

        return $chatServiceLog;
    }



    /**
     * 创建正在进行中的 服务
     *
     * @param [type] $room_id
     * @param [type] $chatUser
     * @param [type] $customerService
     * @return void
     */
    public function createServiceLog($room_id, $chatUser, $customerService)
    {
        // 正在进行中的连接
        $serviceLog = $this->getServiceLogIngByChatUser($room_id, $chatUser['id'], $customerService['id']);

        if (!$serviceLog) {
            // 不存在，创建新的
            $serviceLog = $this->addServiceLog($room_id, $chatUser, $customerService, 'ing');
        }

        return $serviceLog;
    }



    /**
     * 结束 服务
     *
     * @param string $room_id       房间号
     * @param array $chatUser       顾客
     * @param array $customerService    客服
     * @return object
     */
    public function endServiceLog($room_id, $chatUser, $customerService)
    {
        // 正在进行中的连接
        $serviceLog = $this->getServiceLogIngByChatUser($room_id, $chatUser['id'], $customerService['id']);

        if (!$serviceLog) {
            // 不存在，创建新的
            $serviceLog = $this->addServiceLog($room_id, $chatUser, $customerService, 'end');
        } else {
            $serviceLog->customer_service_id =  $customerService ? $customerService['id'] : 0;
            $serviceLog->endtime = time();
            $serviceLog->status = 'end';
            $serviceLog->save();
        }

        return $serviceLog;
    }



    /**
     * 通过用户获取到用户绑定的在指定房间的客服信息（判断 auth 在指定房间是否是客服，是了才能登录客服）
     *
     * @param string $room_id       房间号
     * @param string $auth          用户类型
     * @param integer $auth_id      用户 id
     * @return array|null
     */
    public function getCustomerServiceByAuthAndRoom($room_id, $auth_id, $auth)
    {
        // 获取当前 auth 绑定的所有客服的 id
        $customerServiceIds = ChatCustomerServiceUser::where('auth', $auth)->where('auth_id', $auth_id)->column('customer_service_id');
        // 通过上一步的 客服id 配合 房间获取第一条客服（只能有一条）
        $customerService = ChatCustomerService::where('room_id', $room_id)->where('id', 'in', $customerServiceIds)->find();

        return $customerService;
    }


    /**
     * 通过用户获取到用户绑定的所有客服信息（暂不使用，当独立登录是，让用户选择客服身份）
     *
     * @param string $auth          用户类型
     * @param integer $auth_id      用户 id
     * @return array
     */
    public function getCustomerServicesByAuth($auth_id, $auth, $first = false)
    {
        // 获取当前 auth 绑定的所有客服的 id
        $customerServiceIds = ChatCustomerServiceUser::where('auth', $auth)->where('auth_id', $auth_id)->column('customer_service_id');
        // 通过上一步的 客服id 配合 房间获取第一条客服（只能有一条）
        $customerServices = ChatCustomerService::where('id', 'in', $customerServiceIds)->order('id', 'asc')->select();

        return $first ? ($customerServices[0] ?? null) : $customerServices;
    }



    /**
     * 获取客服服务的历史用户
     *
     * @param string $room_id  房间号
     * @param integer $customer_service_id  客服 id
     * @param array $exceptIds  要排除的ids （正在服务的用户）
     * @return array
     */
    public function getCustomersHistoryByCustomerService($room_id, $customer_service_id, $exceptIds = [])
    {
        // $logs = ChatServiceLog::with('chat_user')
        //             ->where('room_id', $room_id)
        //             ->field('chat_user_id')
        //             ->whereNotIn('chat_user_id', $exceptIds)
        //             ->where('customer_service_id', $customer_service_id)
        //             ->group('chat_user_id')
        //             ->order('id', 'desc')
        //             ->select();
        // $logs = collection($logs)->toArray();

        // $chatUsers = array_column($logs, 'chat_user');

        // 替代上面的方法，上面方法 group by 在 mysql 严格模式必须要关闭 ONLY_FULL_GROUP_BY
        $chatUsers = [];
        $logs = ChatServiceLog::with('chat_user')
            ->field('id,chat_user_id')
            ->where('room_id', $room_id)
            ->whereNotIn('chat_user_id', $exceptIds)
            ->where('customer_service_id', $customer_service_id)
            ->chunk(100, function ($currentLogs) use (&$chatUsers) {
                foreach ($currentLogs as $log) {
                    $chatUser = $log->chat_user;
                    $currentIds = array_column($chatUsers, 'id');
                    if ($chatUser && !in_array($chatUser->id, $currentIds)) {
                        $chatUsers[] = $chatUser;
                    }

                    if (count($chatUsers) >= 20) {
                        break;
                    }
                }

                if (count($chatUsers) >= 20) {
                    return false;
                }
            }, 'id', 'desc');           // 如果 id 重复，有坑 (date < 2020-03-28)

        return $chatUsers;
    }



    /**
     * 通过 session_id 获取 顾客
     *
     * @param string $session_id
     * @return object
     */
    public function getCustomerBySessionId($session_id)
    {
        return ChatUser::where('session_id', $session_id)->find();
    }


    /**
     * 删除客服的一个顾客的所有服务记录
     *
     * @param string $room_id
     * @param integer $chat_user_id
     * @param array $customerService
     * @param boolean $is_del_record
     * @return void
     */
    public static function delCustomerByCustomerService($room_id, $chat_user_id, $customerService, $is_del_record = false)
    {
        ChatServiceLog::where('room_id', $room_id)
                ->where('customer_service_id', $customerService['id'])
                ->where('chat_user_id', $chat_user_id)->delete();

        if ($is_del_record) {
            self::delCustomerRecordById($room_id, $chat_user_id);
        }
    }


    /**
     * 删除客服的一个顾客的所有服务记录
     *
     * @param string $room_id
     * @param array $customerService
     * @param boolean $is_del_record
     * @return void
     */
    public static function delCustomerAllByCustomerService($room_id, $customerService, $is_del_record = false)
    {
        if ($is_del_record) {
            $chatUserIds = ChatServiceLog::where('room_id', $room_id)
                    ->where('customer_service_id', $customerService['id'])->column('chat_user_id');
            $chatUserIds = array_values(array_unique($chatUserIds));

            foreach ($chatUserIds as $chat_user_id) {
                self::delCustomerRecordById($room_id, $chat_user_id);
            }
        }

        ChatServiceLog::where('room_id', $room_id)
                ->where('customer_service_id', $customerService['id'])->delete();

    }


    /**
     * 删除客户聊天记录
     *
     * @param string $room_id
     * @param int $chat_user_id
     * @return void
     */
    public static function delCustomerRecordById($room_id, $chat_user_id)
    {
        ChatRecord::where('room_id', $room_id)->where('chat_user_id', $chat_user_id)->delete();
    }


    /**
     * 获取顾客的聊天记录
     *
     * @param string $room_id
     * @param string $session_id
     * @param  $select_identify
     * @return array
     */
    public function getCustomerMessagesBySessionId($room_id, $session_id, $select_identify, $data)
    {
        $selectIdentify = Str::camel($select_identify);

        $customer = $this->getCustomerBySessionId($session_id);
        $chat_user_id = $customer ? $customer['id'] : 0;

        // 将消息标记为已读
        ChatRecord::where('room_id', $room_id)->where('chat_user_id', $chat_user_id)->{$selectIdentify}()->update([
            'read_time' => time()
        ]);

        $page = $data['page'] ?? 1;
        $list_rows = $data['list_rows'] ?? 20;
        $last_id = $data['last_id'] ?? 0;

        $messageList = ChatRecord::where('room_id', $room_id)->where('chat_user_id', $chat_user_id);

        // 避免消息重复
        if ($last_id) {
            $messageList = $messageList->where('id', '<=', $last_id);
        }

        $messageList = $messageList->order('id', 'desc')->paginate([
            'page' => $page,
            'list_rows' => $list_rows
        ]);
        $messageList = $this->getMessageSender($messageList);

        return $messageList;
    }


    /**
     * 获取用户的最后一条消息(当前房间的)
     *
     * @param string $room_id
     * @param integer $chat_user_id
     * @return object
     */
    public function getMessageLastByChatUser($room_id, $chat_user_id) 
    {
        return ChatRecord::where('room_id', $room_id)->where('chat_user_id', $chat_user_id)->order('id', 'desc')->find();
    }



    /**
     * 根据身份获取未读消息条数(当前房间的)
     *
     * @param string $room_id
     * @param integer $chat_user_id
     * @param string $select_identify
     * @return integer
     */
    public function getMessageUnReadNumByChatUserAndIndentify($room_id, $chat_user_id, $select_identify) 
    {
        $selectIdentify = Str::camel($select_identify);
        return ChatRecord::where('room_id', $room_id)->where('chat_user_id', $chat_user_id)->where('read_time', 'null')->{$selectIdentify}()->count();
    }

    /**
     * 更新客服信息
     *
     * @param integer $id
     * @param array $data
     * @return void
     */
    public function updateCustomerService($id, $data) 
    {
        $customerService = ChatCustomerService::where('id', $id)->update($data);
    }



    /**
     * 添加消息记录 
     *
     * @param string $room_id
     * @param string $name
     * @param array $arguments
     * @return object
     */
    public function addMessage($room_id, $name, $arguments)
    {
        $content = $arguments[2] ?? [];      // 额外参数
        $message = $content['message'];
        $sender = $content['sender'];
        $sender_identify = $sender['sender_identify'] ?? 'customer';
        $receive = $arguments[3] ?? [];

        if ($sender_identify == 'customer') {
            $session_id = $sender['session_id'];
            $chatUser = $this->getChatUserBySessionId($session_id);
            $sender_id = $chatUser['id'] ?? 0;
            $chat_user_id = $sender_id;
        } else {
            $customerService = $sender['customer_service'];
            $sender_id = $customerService['id'] ?? 0;

            $session_id = $receive['id'];
            $chatUser = $this->getChatUserBySessionId($session_id);
            $chat_user_id = $chatUser['id'] ?? 0;
        }

        $chatRecord = new ChatRecord();

        $chatRecord->chat_user_id = $chat_user_id;
        $chatRecord->room_id = $room_id;
        $chatRecord->sender_identify = $sender_identify;
        $chatRecord->sender_id = $sender_id;
        $chatRecord->message_type = $message['message_type'] ?? 'text';
        $chatRecord->message = $message['message'] ?? '';
        $chatRecord->createtime = time();
        $chatRecord->updatetime = time();
        $chatRecord->save();

        // 加载消息人
        $chatRecord = $this->getMessageSender([$chatRecord])[0];

        return $chatRecord;
    }



    /**
     * 获取猜你想问
     *
     * @param string $room_id   房间
     * @return object
     */
    public function getChatQuestions($room_id) 
    {
        $chatQuestions = ChatQuestion::roomId($room_id)->order(['weigh' => 'desc', 'id' => 'desc'])->select();

        return $chatQuestions;
    }


    /**
     * 根据 id 获取猜你想问
     *
     * @param string $room_id   房间
     * @return object
     */
    public function getChatQuestion($room_id, $question_id) 
    {
        if ($question_id) {
            $chatQuestion = ChatQuestion::roomId($room_id)->find($question_id);
    
            return $chatQuestion;
        }

        return null;
    }


    /**
     * 获取客服常用语
     *
     * @param string $room_id   房间
     * @return object
     */
    public function getChatCommonWords($room_id) 
    {
        $chatCommonWords = ChatCommonWord::normal()->roomId($room_id)->order(['weigh' => 'desc', 'id' => 'desc'])->select();

        return $chatCommonWords;
    }


    /**
     * 获取消息的 发送人
     *
     * @param array|object $messageList
     * @return array|object
     */
    private function getMessageSender($messageList) 
    {
        $morphs = [
            'customer' => \app\admin\model\shopro\chat\User::class,
            'customer_service' => \app\admin\model\shopro\chat\CustomerService::class
        ];
        $messageList = morph_to($messageList, $morphs, ['sender_identify', 'sender_id']);

        return $messageList;
    }
}
