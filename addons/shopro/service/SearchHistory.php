<?php

namespace addons\shopro\service;

use app\admin\model\shopro\SearchHistory as SearchHistoryModel;
use app\admin\model\shopro\user\User;

class SearchHistory
{

    protected $user = null;

    /**
     * @var array
     */
    public $config = [];

    public function __construct($user = null)
    {
        $this->user = is_numeric($user) ? User::get($user) : $user;
        $this->user = $this->user ?: auth_user();
    }


    public function save($params)
    {
        $keyword = $params['keyword'];

        // SearchHistoryModel::where('user_id', $this->user->id)->where('keyword', $keyword)->delete();

        $searchHistory = new SearchHistoryModel();
        $searchHistory->user_id = $this->user ? $this->user->id : 0;
        $searchHistory->keyword = $keyword;
        $searchHistory->save();
    }


    public function hotSearch()
    {
        $now = time();
        $start = time() - (86400 * 30);         // 30 天前

        $hotSearchs = SearchHistoryModel::field('keyword,count(*) as num')
            ->whereTime('createtime', 'between', [$start, $now])
            ->group('keyword')->order('num', 'desc')->limit(5)->select();

        return $hotSearchs;
    }
}
