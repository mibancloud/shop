define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: () => {
            const { reactive, onMounted, getCurrentInstance } = Vue
            const index = {
                setup() {
                    const { proxy } = getCurrentInstance();

                    const form = reactive({
                        model: {},
                        rules: {
                            name: [{ required: true, message: '请输入系统名称', trigger: 'blur' }],
                            logo: [{ required: true, message: '请选择公众号Logo', trigger: 'blur' }],
                            qrcode: [{ required: true, message: '请选择公众号二维码', trigger: 'blur' }],
                            app_id: [{ required: true, message: '请输入开发者AppId', trigger: 'blur' }],
                            secret: [{ required: true, message: '请输入开发者AppSecret', trigger: 'blur' }],
                            token: [{ required: true, message: '请输入令牌(Token)', trigger: 'blur' }],
                            aes_key: [{ required: true, message: '请输入消息加解密密钥', trigger: 'blur' }],
                        },
                    })

                    function getData() {
                        Fast.api.ajax({
                            url: 'shopro/wechat/config',
                            type: 'GET',
                        }, function (ret, res) {
                            form.model = res.data;
                            return false
                        }, function (ret, res) { })
                    }

                    function onConfirm() {
                        proxy.$refs['formRef'].validate((valid) => {
                            if (valid) {
                                Fast.api.ajax({
                                    url: 'shopro/wechat/config',
                                    type: 'POST',
                                    data: form.model,
                                }, function (ret, res) {
                                }, function (ret, res) { })
                            }
                        });
                    }

                    onMounted(() => {
                        getData()
                    })

                    return {
                        onClipboard,
                        form,
                        getData,
                        onConfirm,
                    }
                }
            }
            createApp('index', index);
        },
    };
    return Controller;
});
