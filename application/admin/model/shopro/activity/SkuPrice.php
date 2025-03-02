<?php

namespace app\admin\model\shopro\activity;

use app\admin\model\shopro\Common;

class SkuPrice extends Common
{
    protected $name = 'shopro_activity_sku_price';

    protected $type = [
        'ext' => 'json',
    ];

    // 追加属性
    protected $append = [
        'status_text',
    ];


    /**
     * 普通拼团，团长价
     *
     * @param string $value
     * @param array $data
     * @return string
     */
    public function getLeaderPriceAttr($value, $data)
    {
        $ext = $data['ext'];
        $is_leader_discount = $ext['is_leader_discount'] ?? 0;
        $leader_price = $ext['leader_price'] ?? 0;

        $leader_price = $is_leader_discount ? $leader_price : $data['price'];
        return $leader_price;
    }



    private function currentLadder($data, $level, $is_leader = false)
    {
        $ext = $data['ext'];
        $is_leader_discount = $ext['is_leader_discount'] ?? 0;
        $ladders = $ext['ladders'] ?? [];
        $ladders = array_column($ladders, null, 'ladder_level');
        $currentLadder = $ladders[$level] ?? [];       // 当前阶梯的 价格数据

        $key = ($is_leader && $is_leader_discount) ? 'leader_ladder_price' : 'ladder_price';
        return $currentLadder[$key] ?? 0;
    }

    /**
     * 阶梯拼团，第一阶梯价
     *
     * @param string $value
     * @param array $data
     * @return string
     */
    public function getLadderOneAttr($value, $data)
    {
        return $this->currentLadder($data, 'ladder_one');
    }


    /**
     * 阶梯拼团，第二阶梯价
     *
     * @param string $value
     * @param array $data
     * @return string
     */
    public function getLadderTwoAttr($value, $data)
    {
        return $this->currentLadder($data, 'ladder_two');
    }


    /**
     * 阶梯拼团，第二阶梯价
     *
     * @param string $value
     * @param array $data
     * @return string
     */
    public function getLadderThreeAttr($value, $data)
    {
        return $this->currentLadder($data, 'ladder_three');
    }


    /**
     * 阶梯拼团，第一阶梯团长价
     *
     * @param string $value
     * @param array $data
     * @return string
     */
    public function getLadderOneLeaderAttr($value, $data)
    {
        return $this->currentLadder($data, 'ladder_one', true);
    }


    /**
     * 阶梯拼团，第二阶团长梯价
     *
     * @param string $value
     * @param array $data
     * @return string
     */
    public function getLadderTwoLeaderAttr($value, $data)
    {
        return $this->currentLadder($data, 'ladder_two', true);
    }


    /**
     * 阶梯拼团，第二阶团长梯价
     *
     * @param string $value
     * @param array $data
     * @return string
     */
    public function getLadderThreeLeaderAttr($value, $data)
    {
        return $this->currentLadder($data, 'ladder_three', true);
    }
}
