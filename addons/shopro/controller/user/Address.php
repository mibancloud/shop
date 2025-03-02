<?php

namespace addons\shopro\controller\user;

use think\Db;
use addons\shopro\controller\Common;
use app\admin\model\shopro\data\Area as AreaModel;
use app\admin\model\shopro\user\Address as UserAddressModel;

class Address extends Common
{

    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    public function index()
    {
        $user = auth_user();

        $userAddresses = UserAddressModel::where('user_id', $user->id)
            ->order('is_default', 'desc')
            ->order('id', 'desc')
            ->select();

        $this->success('获取成功', $userAddresses);
    }


    /**
     * 添加收货地址
     */
    public function add()
    {
        $user = auth_user();

        $params = $this->request->only([
            'consignee', 'mobile', 'province_name', 'city_name', 'district_name', 'address', 'is_default'
        ]);

        $params['user_id'] = $user->id;
        $this->svalidate($params, ".add");

        $params = $this->getAreaIdByName($params);

        Db::transaction(function () use ($user, $params) {
            $userAddress = new UserAddressModel();
            $userAddress->save($params);

            if ($userAddress->is_default) {
                // 修改其他收货地址为非默认
                UserAddressModel::where('id', '<>', $userAddress->id)
                    ->where('user_id', $user->id)
                    ->where('is_default', 1)
                    ->update(['is_default' => 0]);
            }
        });

        $this->success('保存成功');
    }

    /**
     * 收货地址详情
     *
     * @param  $id
     * @return \think\Response
     */
    public function detail()
    {
        $user = auth_user();
        $id = $this->request->param('id');

        $userAddress = UserAddressModel::where('user_id', $user->id)->where('id', $id)->find();
        if (!$userAddress) {
            $this->error(__('No Results were found'));
        }

        $this->success('获取成功', $userAddress);
    }

    /**
     * 默认收货地址
     *
     * @return \think\Response
     */
    public function default()
    {
        $user = auth_user();
        $userAddress = UserAddressModel::default()->where('user_id', $user->id)->find();

        $this->success('获取成功', $userAddress);
    }

    /**
     * 编辑收货地址
     *
     * @return \think\Response
     */
    public function edit()
    {
        $user = auth_user();
        $id = $this->request->param('id');

        $userAddress = UserAddressModel::where('user_id', $user->id)->where('id', $id)->find();
        if (!$userAddress) {
            $this->error(__('No Results were found'));
        }

        $params = $this->request->only([
            'consignee', 'mobile', 'province_name', 'city_name', 'district_name', 'address', 'is_default'
        ]);
        $this->svalidate($params, ".edit");

        $params = $this->getAreaIdByName($params);

        Db::transaction(function () use ($user, $params, $userAddress) {
            $userAddress->save($params);

            if ($userAddress->is_default) {
                // 修改其他收货地址为非默认
                UserAddressModel::where('id', '<>', $userAddress->id)
                    ->where('user_id', $user->id)
                    ->where('is_default', 1)
                    ->update(['is_default' => 0]);
            }
        });

        $this->success('保存成功');
    }


    /**
     * 删除收货地址
     *
     * @param string $id 要删除的收货地址
     * @return void
     */
    public function delete()
    {
        $user = auth_user();
        $id = $this->request->param('id');

        $userAddress = UserAddressModel::where('user_id', $user->id)->where('id', $id)->find();
        if (!$userAddress) {
            $this->error(__('No Results were found'));
        }

        $userAddress->delete();

        $this->success('删除成功');
    }

    private function getAreaIdByName($params)
    {
        $province = AreaModel::where([
            'name' => $params['province_name'],
            'level' => 'province'
        ])->find();
        if (!$province) $this->error('请选择正确的行政区');
        $params['province_id'] = $province->id;

        $city = AreaModel::where([
            'name' => $params['city_name'],
            'level' => 'city',
            'pid' => $province->id
        ])->find();
        if (!$city) $this->error('请选择正确的行政区');
        $params['city_id'] = $city->id;

        $district = AreaModel::where([
            'name' => $params['district_name'],
            'level' => 'district',
            'pid' => $city->id
        ])->find();
        if (!$district) $this->error('请选择正确的行政区');
        $params['district_id'] = $district->id;

        return $params;
    }
}
