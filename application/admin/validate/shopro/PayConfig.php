<?php

namespace app\admin\validate\shopro;

use think\Validate;

class PayConfig extends Validate
{
    protected $rule = [
        'name' => 'require',
        'type' => 'require',
        'params' => 'require|array',

        
        'mode' => 'require',        // 模式必选

        'mch_id' => 'require',
        'mch_secret_key' => 'require',
        'mch_secret_cert' => 'require',
        'mch_public_cert_path' => 'require',
        'sub_mch_id' => 'requireIf:mode,2',
        'app_id' => 'requireIf:mode,2',                          // 这个支付宝也用这个 app_id 参数名，这里有点问题，支付宝普通模式如果不填这里验证拦不住
        'sub_mch_secret_key' => 'requireIf:mode,2',
        'sub_mch_secret_cert' => 'requireIf:mode,2',
        'sub_mch_public_cert_path' => 'requireIf:mode,2',

        // 支付宝参数校验
        'service_provider_id' => 'requireIf:mode,service',
        'alipay_public_cert_path' => 'require',
        'app_public_cert_path' => 'require',
        'alipay_root_cert_path' => 'require',
        'private_key' => 'require',
    ];

    protected $message  =   [
        'name.require'     => '请填写支付配置名称',
        'type.require'     => '请选择支付配置类型',
        'params.require'     => '请填写正确的支付参数',
        'params.array'     => '请填写正确的支付参数',

        // 微信支付参数校验
        'mode.require' => '请选择商户类型',
        'app_id.requireIf' => '请填写商户相关 AppId',             // 这个支付宝也用这个 app_id 参数名

        'mch_id.require' => '请填写商户 ID',
        'mch_secret_key.require' => '请填写商户密钥',
        'mch_secret_cert.require' => '请上传商户 key 证书',
        'mch_public_cert_path.require' => '请上传商户证书',
        'sub_mch_id.requireIf' => '请填写子商户 ID',
        'sub_mch_secret_key.requireIf' => '请填写子商户密钥',
        'sub_mch_secret_cert.requireIf' => '请上传子商户 key 证书',
        'sub_mch_public_cert_path.requireIf' => '请上传子商户证书',


        // 支付宝参数校验
        'service_provider_id.requireIf' => '请填写主商户 ID',
        'alipay_public_cert_path.require' => '请上传支付宝公钥证书',
        'app_public_cert_path.require' => '请上传应用公钥证书',
        'alipay_root_cert_path.require' => '请上传支付宝根证书',
        'app_secret_cert.require' => '请填写支付宝私钥',
    ];


    protected $scene = [
        'add'  =>  ['name', 'type', 'params'],

        'wechat' => ['mode', 'mch_id', 'mch_secret_key', 'mch_secret_cert', 'mch_public_cert_path', 'app_id', 'sub_mch_id'],

        'alipay' => ['mode', 'service_provider_id', 'app_id', 'alipay_public_cert_path', 'app_public_cert_path', 'alipay_root_cert_path', 'app_secret_cert'],
    ];
}
