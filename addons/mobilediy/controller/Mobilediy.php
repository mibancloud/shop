<?php

namespace addons\mobilediy\controller;

use app\common\controller\Api;
use think\Db;


/**
 * 页面接口
 */
class Mobilediy extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];


    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 页面数据
     *
     */
    public function getPage()
    {
        $page_id = (int)$this->request->request('page_id');

        if (Db("mobilediy_page")->where(['deletetime' => null])->order(['id' => 'desc'])->count() <= 0) {
            return $this->error('未定义页面');
        }
        // 页面详情
        $detail = $page_id > 0 ? Db("mobilediy_page")->where('id', $page_id)->find() : Db("mobilediy_page")->where('status', 'home')->find();
        if (!$detail) {
            return $this->error('页面错误');
        }
        // 页面diy元素
        return $this->success('', json_decode($detail['page_data']));
    }
}
