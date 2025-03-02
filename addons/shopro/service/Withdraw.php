<?php

namespace addons\shopro\service;

use addons\shopro\exception\ShoproException;
use think\Db;
use think\Log;
use think\exception\HttpResponseException;
use app\admin\model\shopro\Withdraw as WithdrawModel;
use app\admin\model\shopro\WithdrawLog as WithdrawLogModel;
use addons\shopro\library\Operator;
use app\admin\model\shopro\ThirdOauth;
use addons\shopro\service\Wallet as WalletService;
use addons\shopro\library\pay\PayService;
use app\admin\model\shopro\user\User;

class Withdraw
{

    protected $user = null;

    /**
     * @var array
     */
    public $config = [];

    public function __construct($user)
    {
        $this->user = is_numeric($user) ? User::get($user) : $user;

        // 提现规则
        $config = sheep_config('shop.recharge_withdraw.withdraw');

        $config['min_amount'] = $config['min_amount'] == 0 ? $config['min_amount'] : number_format(floatval($config['min_amount']), 2, '.', '');
        $config['max_amount'] = $config['max_amount'] == 0 ? $config['max_amount'] : number_format(floatval($config['max_amount']), 2, '.', '');
        $config['charge_rate_format'] = round(floatval($config['charge_rate']), 1);      // 1 位小数
        $config['charge_rate'] = round((floatval($config['charge_rate']) * 0.01), 3);

        $this->config = $config;
    }


    private function checkApply($type, $money, $charge)
    {
        if (!in_array($type, $this->config['methods'])) {
            error_stop('暂不支持该提现方式');
        }

        if ($money <= 0) {
            error_stop('请输入正确的提现金额');
        }

        // 检查最小提现金额
        if ($this->config['min_amount'] > 0 && $money < $this->config['min_amount']) {
            error_stop('提现金额不能少于 ' . $this->config['min_amount'] . '元');
        }
        // 检查最大提现金额
        if ($this->config['max_amount'] > 0 && $money > $this->config['max_amount']) {
            error_stop('提现金额不能大于 ' . $this->config['max_amount'] . '元');
        }


        if ($this->user->commission < bcadd($charge, $money, 2)) {
            error_stop('可提现佣金不足');
        }

        // 检查最大提现次数
        if (isset($this->config['max_num']) && $this->config['max_num'] > 0) {
            $start_time = $this->config['num_unit'] == 'day' ? strtotime(date('Y-m-d', time())) : strtotime(date('Y-m', time()));

            $num = WithdrawModel::where('user_id', $this->user->id)->where('createtime', '>=', $start_time)->count();

            if ($num >= $this->config['max_num']) {
                error_stop('每' . ($this->config['num_unit'] == 'day' ? '日' : '月') . '提现次数不能大于 ' . $this->config['max_num'] . '次');
            }
        }
    }


    public function accountInfo($type, $params)
    {
        $platform = request()->header('platform');

        switch ($type) {
            case 'wechat':
                if ($platform == 'App') {
                    $platform = 'openPlatform';
                } elseif (in_array($platform, ['WechatOfficialAccount', 'WechatMiniProgram'])) {
                    $platform = lcfirst(str_replace('Wechat', '', $platform));
                }
                $thirdOauth = ThirdOauth::where('provider', 'wechat')->where('platform', $platform)->where('user_id', $this->user->id)->find();
                if (!$thirdOauth) {
                    error_stop('请先绑定微信账号', -1);
                }
                $withdrawInfo = [
                    '真实姓名' => $params['account_name'],
                    '微信用户' => $thirdOauth['nickname'],
                    '微信ID'  => $thirdOauth['openid'],
                ];
                break;
            case 'alipay':
                $withdrawInfo = [
                    '真实姓名' => $params['account_name'],
                    '支付宝账户' => $params['account_no']
                ];
                break;
            case 'bank':
                $withdrawInfo = [
                    '真实姓名' => $params['account_name'],
                    '开户行' => $params['account_header'] ?? '',
                    '银行卡号' => $params['account_no']
                ];
                break;
        }
        if (!isset($withdrawInfo)) {
            error_stop('您的提现信息有误');
        }

        return $withdrawInfo;
    }



    public function apply($params)
    {
        $type = $params['type'] ?? 'wechat';
        $money = $params['money'] ?? 0;
        $money = (string)$money;
        // 手续费
        $charge = bcmul($money, (string)$this->config['charge_rate'], 2);

        // 检查提现规则
        $this->checkApply($type, $money, $charge);

        // 获取账号信息
        $withdrawInfo = $this->accountInfo($type, $params);
        $withdraw = Db::transaction(function () use ($type, $money, $charge, $withdrawInfo) {
            $platform = request()->header('platform');

            // 添加提现记录
            $withdraw = new WithdrawModel();
            $withdraw->user_id = $this->user->id;
            $withdraw->amount = $money;
            $withdraw->charge_fee = $charge;
            $withdraw->charge_rate = $this->config['charge_rate'];
            $withdraw->withdraw_sn = get_sn($this->user->id, 'W');
            $withdraw->withdraw_type = $type;
            $withdraw->withdraw_info = $withdrawInfo;
            $withdraw->status = 0;
            $withdraw->platform = $platform;
            $withdraw->save();

            // 佣金钱包变动
            WalletService::change($this->user, 'commission', - bcadd($charge, $money, 2), 'withdraw', [
                'withdraw_id' => $withdraw->id,
                'amount' => $withdraw->amount,
                'charge_fee' => $withdraw->charge_fee,
                'charge_rate' => $withdraw->charge_rate,
            ]);

            $this->handleLog($withdraw, '用户发起提现申请', $this->user);

            return $withdraw;
        });

        // 检查是否执行自动打款
        $autoCheck = false;
        if ($type !== 'bank' && $this->config['auto_arrival']) {
            $autoCheck = true;
        }

        if ($autoCheck) {
            Db::startTrans();
            try {
                $withdraw = $this->handleAgree($withdraw, $this->user);
                $withdraw = $this->handleWithdraw($withdraw, $this->user);

                Db::commit();
            } catch (ShoproException $e) {
                Db::commit();       // 不回滚，记录错误日志
                error_stop($e->getMessage());
            } catch (HttpResponseException $e) {
                $data = $e->getResponse()->getData();
                $message = $data ? ($data['msg'] ?? '') : $e->getMessage();
                error_stop($message);
            } catch (\Exception $e) {
                Db::rollback();
                error_stop($e->getMessage());
            }
        }

        return $withdraw;
    }

    // 同意
    public function handleAgree($withdraw, $oper = null)
    {
        if ($withdraw->status != 0) {
            throw new ShoproException('请勿重复操作');
        }
        $withdraw->status = 1;
        $withdraw->save();
        return $this->handleLog($withdraw, '同意提现申请', $oper);
    }

    // 处理打款
    public function handleWithdraw($withdraw, $oper = null)
    {
        $withDrawStatus = false;
        if ($withdraw->status != 1) {
            throw new ShoproException('请勿重复操作');
        }
        if ($withdraw->withdraw_type !== 'bank') {
            $withDrawStatus = $this->handleTransfer($withdraw);
        } else {
            $withDrawStatus = true;
        }
        if ($withDrawStatus) {
            $withdraw->status = 2;
            $withdraw->paid_fee = $withdraw->amount;
            $withdraw->save();
            return $this->handleLog($withdraw, '已打款', $oper);
        }
        return $withdraw;
    }

    // 拒绝
    public function handleRefuse($withdraw, $refuse_msg)
    {
        if ($withdraw->status != 0 && $withdraw->status != 1) {
            throw new ShoproException('请勿重复操作');
        }
        $withdraw->status = -1;
        $withdraw->save();

        // 退回用户佣金
        WalletService::change($this->user, 'commission', bcadd($withdraw->charge_fee, $withdraw->amount, 2), 'withdraw_error', [
            'withdraw_id' => $withdraw->id,
            'amount' => $withdraw->amount,
            'charge_fee' => $withdraw->charge_fee,
            'charge_rate' => $withdraw->charge_rate,
        ]);

        return $this->handleLog($withdraw, '拒绝:' . $refuse_msg);
    }


    // 企业付款提现
    private function handleTransfer($withdraw)
    {
        operate_disabled();
        $type = $withdraw->withdraw_type;
        $platform = $withdraw->platform;

        $payService = new PayService($type, $platform);
        if ($type == 'wechat') {
            $payload = [
                'out_batch_no' => $withdraw->withdraw_sn,
                'batch_name' => '商家转账到零钱',
                'batch_remark' => "用户[" . ($withdraw->withdraw_info['微信用户'] ?? '') . "]提现",
                'total_amount' => $withdraw->amount,
                'total_num' => 1,
                'transfer_detail_list' => [
                    [
                        'out_detail_no' => $withdraw->withdraw_sn,
                        'transfer_amount' => $withdraw->amount,
                        'transfer_remark' => "用户[" . ($withdraw->withdraw_info['微信用户'] ?? '') . "]提现",
                        'openid' => $withdraw->withdraw_info['微信ID'] ?? '',
                        'user_name' => $withdraw->withdraw_info['真实姓名'] ?? '',
                    ],
                ],
            ];
        } elseif ($type == 'alipay') {
            $payload = [
                'out_biz_no' => $withdraw->withdraw_sn,
                'trans_amount' => $withdraw->amount,
                'product_code' => 'TRANS_ACCOUNT_NO_PWD',
                'biz_scene' => 'DIRECT_TRANSFER',
                // 'order_title' => '余额提现到',
                'remark' => '用户提现',
                'payee_info' => [
                    'identity' => $withdraw->withdraw_info['支付宝账户'] ?? '',
                    'identity_type' => 'ALIPAY_LOGON_ID',
                    'name' => $withdraw->withdraw_info['真实姓名'] ?? '',
                ]
            ];
        }

        try {
            list($code, $response) = $payService->transfer($payload);
            Log::write('transfer-origin-data：' . json_encode($response));
            if ($code === 1) {
                $withdraw->payment_json = json_encode($response, JSON_UNESCAPED_UNICODE);
                $withdraw->save();
                return true;
            }
            throw new ShoproException(json_encode($response, JSON_UNESCAPED_UNICODE));
        } catch (HttpResponseException $e) {
            $data = $e->getResponse()->getData();
            $message = $data ? ($data['msg'] ?? '') : $e->getMessage();
            throw new ShoproException($message);
        } catch (\Exception $e) {
            \think\Log::error('提现失败：' . ' 行号：' . $e->getLine() . '文件：' . $e->getFile() . '错误信息：' . $e->getMessage());
            $this->handleLog($withdraw, '提现失败：' . $e->getMessage());
            throw new ShoproException($e->getMessage());     // 弹出错误信息
        }
        return false;
    }




    private function handleLog($withdraw, $oper_info, $oper = null)
    {
        $oper = Operator::get($oper);

        WithdrawLogModel::insert([
            'withdraw_id' => $withdraw->id,
            'content' => $oper_info,
            'oper_type' => $oper['type'],
            'oper_id' => $oper['id'],
            'createtime' => time()
        ]);
        return $withdraw;
    }
}
