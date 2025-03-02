<?php

namespace app\admin\model\shopro;

use app\admin\model\shopro\Common;
use app\admin\model\shopro\user\User as UserModel;
use app\admin\model\shopro\goods\Goods as GoodsModel;

class Share extends Common
{
    protected $updateTime = false;
    
    protected $name = 'shopro_share';
    
    protected $type = [
        'ext' => 'json'
    ];
    
    protected $append = [
        'platform_text',
        'from_text'
    ];

    const FROM = ['forward' => '直接转发', 'poster' => '识别海报', 'link' => '分享链接'];

    const PLATFORM = ['H5' => 'H5网页', 'WechatOfficialAccount' => '微信公众号网页', 'WechatMiniProgram' => '微信小程序', 'App' => 'APP'];

    public function getPlatformTextAttr($value, $data)
    {
        $value = $value ?: ($data['platform'] ?? null);

        return (self::PLATFORM)[$value] ?? $value;
    }

    public function getFromTextAttr($value, $data)
    {
        $value = $value ?: ($data['from'] ?? null);

        return (self::FROM)[$value] ?? $value;
    }

    public static function log(Object $user, $params)
    {

        // 错误的分享参数
        if (empty($params['spm'])) {
            return false;
        }

        $shareId = $params['shareId'];
        // 分享用户为空
        if ($shareId <= 0) {
            return false;
        }

        // 不能分享给本人
        if ($shareId == $user->id) {
            return false;
        }

        // 新用户不能分享给老用户 按需打开
        // if($user->id < $shareId) {
        //   return false;
        // }

        $shareUser = UserModel::where('id', $shareId)->find();
        // 分享人不存在
        if (!$shareUser) {
            return false;
        }

        // 5分钟内相同的分享信息不保存，防止冗余数据
        $lastShareLog = self::where([
            'user_id' => $user->id
        ])->where('createtime', '>', time() - 300)->order('id desc')->find();

        if ($lastShareLog && $lastShareLog->spm === $params['spm']) {
            return $lastShareLog;
        }

        $memoText = '通过' . (self::FROM)[$params['from']] . '访问了';
        if ($params['page'] == '/pages/index/index') {
            $memoText .= '首页';
        }
        if ($params['page'] === '/pages/goods/index') {
            $memoText .= '商品';
            $goodsId = $params['query']['id'];
        }
        if ($params['page'] === '/pages/goods/groupon') {
            $memoText .= '拼团商品';
            $goodsId = $params['query']['id'];
        }
        if ($params['page'] === '/pages/goods/seckill') {
            $memoText .= '秒杀商品';
            $goodsId = $params['query']['id'];
        }
        if ($params['page'] === '/pages/activity/groupon/detail') {
            $memoText .= '拼团活动';
        }

        if (!empty($goodsId)) {
            $goods = GoodsModel::find($goodsId);
            if ($goods) {
                $memoText .= "[{$goods->title}]";
            }
        }

        $ext = [
            'image' => $goods->image ?? "",
            'memo' => $memoText
        ];

        $shareInfo = self::create([
            'user_id' => $user->id,
            'share_id' => $shareId,
            'spm' => $params['spm'],
            'page' => $params['page'],
            'query' => http_build_query($params['query']),
            'platform' => $params['platform'],
            'from' => $params['from'],
            'ext' => $ext
        ]);

        $data = ['shareInfo' => $shareInfo];
        \think\Hook::listen('user_share_after', $data);

        return $shareInfo;
    }


    // -- commission code start --
    public function agent()
    {
        return $this->belongsTo(\app\admin\model\shopro\commission\Agent::class, 'share_id', 'user_id');
    }
    // -- commission code end --

    public function user()
    {
        return $this->belongsTo(UserModel::class, 'user_id', 'id');
    }
}
