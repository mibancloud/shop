define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: () => {
            const { reactive, onMounted, getCurrentInstance } = Vue
            const index = {
                setup() {
                    const { proxy } = getCurrentInstance();

                    const state = reactive({
                        data: [],
                        tabActive: null,
                        configis_upgrade: false,
                    })
                    const type = reactive({
                        data: {
                            api: Config.configList.filter((item) => {
                                if (item.status) return item;
                            })
                        }
                    })

                    const form = reactive({
                        model: {},
                        rules: {
                            rechargewithdraw: {
                                recharge: {
                                    quick_amounts: {
                                        money: [{ required: true, message: '请输入金额', trigger: 'blur' }],
                                        gift: [{ required: true, message: '请输入内容', trigger: 'blur' }],
                                    }
                                }
                            },
                            commission: {
                                agent_form: {
                                    background_image: [{ required: true, message: '请选择表单背景图', trigger: 'blur' }],
                                    content: {
                                        type: [{ required: true, message: '表单类型', trigger: 'change' }],
                                        name: [{ required: true, message: '表单名称', trigger: 'blur' }],
                                    },
                                },
                            }
                        },
                    })

                    function getData() {
                        if (!state.tabActive) return;
                        if (state.tabActive == 'shopro/config/platform') {
                            getPlatformStatus()
                        } else if (state.tabActive == 'shopro/pay_config') {
                            getPayConfig()
                        } else {
                            Fast.api.ajax({
                                url: state.tabActive,
                                type: 'GET',
                            }, function (ret, res) {
                                form.model = res.data;

                                // 用户配置
                                if (state.tabActive == 'shopro/config/user') {
                                    getGroupSelect()
                                }

                                if (state.tabActive == 'shopro/config/dispatch') {
                                    form.model.sender.area_arr = []
                                    form.model.sender.area_arr.push(form.model.sender.province_name)
                                    form.model.sender.area_arr.push(form.model.sender.city_name)
                                    form.model.sender.area_arr.push(form.model.sender.district_name)

                                    express.form.model = form.model.kdniao.express
                                    getExpressSelect()
                                    getAreaSelect()
                                }

                                if (state.tabActive == 'shopro/config/commission') {
                                    if (form.model.become_agent.type == 'goods') {
                                        getGoodsList(form.model.become_agent.value);
                                    }
                                    if (!Config.is_pro) {
                                        state.configis_upgrade = true

                                    }
                                }

                                if (state.tabActive == 'shopro/config/chat') {
                                    getChatConfig()
                                }
                                return false
                            }, function (ret, res) { })
                        }
                    }

                    // 基础配置
                    function onSelectRichtext(type) {
                        Fast.api.open(`shopro/data/richtext/select`, '选择富文本', {
                            callback(data) {
                                form.model[type].id = data.id;
                                form.model[type].title = data.title;
                            }
                        })
                    }

                    // 用户配置
                    const group = reactive({
                        select: []
                    })
                    function getGroupSelect() {
                        Fast.api.ajax({
                            url: 'shopro/user/group/select',
                            type: 'GET',
                        }, function (ret, res) {
                            group.select = res.data
                            return false
                        }, function (ret, res) { })
                    }

                    // 平台配置
                    const platform = reactive({
                        data: [{
                            value: 'H5',
                            label: 'H5',
                            color: '#fc800e',
                        },
                        {
                            value: 'WechatOfficialAccount',
                            label: '微信公众号',
                            color: '#07c160',
                        },
                        {
                            value: 'WechatMiniProgram',
                            label: '微信小程序',
                            color: '#6f74e9',
                        },
                        {
                            value: 'App',
                            label: 'App',
                            color: '#806af6',
                        }],
                        status: {},
                    })
                    function getPlatformStatus() {
                        Fast.api.ajax({
                            url: 'shopro/config/platformStatus',
                            type: 'GET',
                        }, function (ret, res) {
                            platform.status = res.data
                            return false
                        }, function (ret, res) { })
                    }
                    function onEditPlatform(item) {
                        Fast.api.open(`shopro/config/platform/platform/${item.value}?platform=${item.value}&label=${item.label}`, `平台-${item.label}`, {
                            callback() {
                                getData()
                            }
                        })
                    }

                    // 物流配置
                    const area = reactive({
                        select: []
                    })
                    function getAreaSelect() {
                        Fast.api.ajax({
                            url: 'shopro/data/area/select',
                            type: 'GET',
                        }, function (ret, res) {
                            area.select = res.data;
                            return false
                        }, function (ret, res) { })
                    }

                    const express = reactive({
                        form: {
                            model: {}
                        },
                    })
                    const deliverCompany = reactive({
                        loading: false,
                        select: [],
                        pagination: {
                            page: 1,
                            list_rows: 10,
                            total: 0,
                        }
                    })
                    function getExpressSelect(keyword) {
                        let search = {};
                        if (keyword) {
                            search = { keyword: keyword };
                        }
                        Fast.api.ajax({
                            url: 'shopro/data/express',
                            type: 'GET',
                            data: {
                                page: deliverCompany.pagination.page,
                                list_rows: deliverCompany.pagination.list_rows,
                                search: JSON.stringify(search),
                            }
                        }, function (ret, res) {
                            deliverCompany.select = res.data.data
                            deliverCompany.pagination.total = res.data.total
                            return false
                        }, function (ret, res) { })
                    }
                    function onChangeExpressCode(code) {
                        express.form.model = {}
                        express.form.model.code = code
                        express.form.model.name = proxy.$refs[`express-${code}`][0].label;
                    }
                    function remoteMethod(keyword) {
                        deliverCompany.loading = true;
                        setTimeout(() => {
                            deliverCompany.loading = false;
                            getExpressSelect(keyword);
                        }, 200);
                    }

                    function onThinkApi() {
                        window.open('https://docs.topthink.com/think-api/1835396');
                    }

                    // 充值提现
                    function onAddTemplate() {
                        form.model.recharge.quick_amounts.push({
                            money: '',
                            gift: '',
                        });
                    }
                    function onDeleteTemplate(index) {
                        form.model.recharge.quick_amounts.splice(index, 1);
                    }

                    // 分销配置
                    function onChangeBecomeAgentType(type) {
                        if (type === 'user') {
                            form.model.invite_lock = 'share';
                        }
                        if (type === 'apply') {
                            form.model.agent_form.status = '1';
                        }
                        if (type === 'goods') {
                            tempGoods.list = [];
                        }
                        form.model.become_agent.value = '';
                    }
                    const tempGoods = reactive({
                        list: [],
                    });
                    async function getGoodsList(ids) {
                        Fast.api.ajax({
                            url: 'shopro/goods/goods/select',
                            type: 'GET',
                            data: {
                                type: 'select',
                                search: JSON.stringify({ id: [ids, 'in'] }),
                            },
                        }, function (ret, res) {
                            tempGoods.list = res.data;
                            return false
                        }, function (ret, res) { })
                    }
                    function onSelectGoods() {
                        let ids = [];
                        tempGoods.list.forEach((i) => {
                            ids.push(i.id);
                        });
                        Fast.api.open(`shopro/goods/goods/select?multiple=true&ids=${ids.join(',')}`, "选择商品", {
                            callback(data) {
                                tempGoods.list = data;
                                let ids = [];
                                tempGoods.list.forEach((item) => {
                                    ids.push(item.id);
                                });
                                form.model.become_agent.value = ids.join(',');
                            }
                        })
                    }
                    function onDeleteGoods(index) {
                        tempGoods.list.splice(index, 1);
                        let ids = [];
                        tempGoods.list.forEach((gl) => {
                            ids.push(gl.id);
                        });
                        form.model.become_agent.value = ids.join(',');
                    }

                    const become_register_options = [
                        {
                            value: 'text',
                            label: '文本内容',
                        },
                        {
                            value: 'number',
                            label: '纯数字',
                        },
                        {
                            value: 'image',
                            label: '上传图片',
                        },
                    ];
                    function onAddContent() {
                        form.model.agent_form.content.push({
                            type: '',
                            name: '',
                        });
                    }
                    function onDeleteContent(index) {
                        form.model.agent_form.content.splice(index, 1);
                    }

                    // 支付配置
                    const payConfig = reactive({
                        data: []
                    })
                    function getPayConfig() {
                        Fast.api.ajax({
                            url: 'shopro/pay_config',
                            type: 'GET',
                        }, function (ret, res) {
                            payConfig.data = res.data.data
                            pagination.total = res.data.total
                            return false
                        }, function (ret, res) { })
                    }
                    const pagination = reactive({
                        page: 1,
                        list_rows: 10,
                        total: 0,
                    })
                    function onCommandPayConfig(item) {
                        Fast.api.ajax({
                            url: `shopro/pay_config/edit/id/${item.id}`,
                            type: 'POST',
                            data: {
                                status: item.type
                            }
                        }, function (ret, res) {
                            getPayConfig()
                        }, function (ret, res) { })
                    }
                    function onAddPayConfig() {
                        Fast.api.open('shopro/pay_config/add?type=add', "添加", {
                            callback() {
                                getPayConfig()
                            }
                        })
                    }
                    function onEditPayConfig(id) {
                        Fast.api.open(`shopro/pay_config/edit?type=edit&id=${id}`, "编辑", {
                            callback() {
                                getPayConfig()
                            }
                        })
                    }
                    function onDeletePayConfig(id) {
                        Fast.api.ajax({
                            url: `shopro/pay_config/delete/id/${id}`,
                            type: 'DELETE',
                        }, function (ret, res) {
                            getPayConfig()
                        }, function (ret, res) { })
                    }
                    function onRecyclebinPayConfig() {
                        Fast.api.open(`shopro/pay_config/recyclebin`, "回收站", {
                            callback() {
                                getPayConfig()
                            }
                        })
                    }

                    // 客服配置
                    const chat = reactive({
                        config: {}
                    })
                    function getChatConfig() {
                        Fast.api.ajax({
                            url: `shopro/chat/index/init`,
                            type: 'GET',
                        }, function (ret, res) {
                            chat.config = res.data
                            return false
                        }, function (ret, res) { })
                    }

                    function onConfirm() {
                        const submitForm = JSON.parse(JSON.stringify(form.model))
                        if (state.tabActive == 'shopro/config/dispatch') {
                            if (submitForm.sender.area_arr) {
                                submitForm.sender.province_name = submitForm.sender.area_arr[0]
                                submitForm.sender.city_name = submitForm.sender.area_arr[1]
                                submitForm.sender.district_name = submitForm.sender.area_arr[2]
                                delete submitForm.sender.area_arr

                            } else {
                                submitForm.sender.province_name = ''
                                submitForm.sender.city_name = ''
                                submitForm.sender.district_name = ''

                            }
                            submitForm.kdniao.express = express.form.model
                        }
                        if (state.tabActive == 'shopro/config/redis') {
                            if (submitForm.empty_password) {
                                delete submitForm.password
                            }
                        }
                        proxy.$refs['formRef'].validate((valid) => {
                            if (valid) {
                                Fast.api.ajax({
                                    url: state.tabActive,
                                    type: 'POST',
                                    data: submitForm,
                                }, function (ret, res) {
                                }, function (ret, res) { })
                            }
                        });
                    }

                    function onOper(type) {
                        switch (type) {
                            case 'close':
                                state.configis_upgrade = false
                                break;
                            case 'refresh':
                                window.location.reload();
                                break;
                            case 'upgrade':
                                window.open("https://www.fastadmin.net/store/shopro.html")
                                break;
                        }
                    }

                    onMounted(() => {
                        state.tabActive = type.data.api.length ? type.data.api[0].name : null;
                        getData()
                    })

                    return {
                        onClipboard,
                        state,
                        type,
                        form,
                        getData,
                        onSelectRichtext,
                        group,
                        platform,
                        onEditPlatform,

                        area,
                        express,
                        deliverCompany,
                        getExpressSelect,
                        onChangeExpressCode,
                        remoteMethod,
                        onThinkApi,

                        onAddTemplate,
                        onDeleteTemplate,

                        onChangeBecomeAgentType,
                        tempGoods,
                        onSelectGoods,
                        onDeleteGoods,
                        become_register_options,
                        onAddContent,
                        onDeleteContent,

                        payConfig,
                        pagination,
                        onCommandPayConfig,
                        onAddPayConfig,
                        onEditPayConfig,
                        onDeletePayConfig,
                        onRecyclebinPayConfig,

                        chat,

                        onConfirm,
                        onOper
                    }
                }
            }
            createApp('index', index);
        },
        platform: () => {
            const { reactive, onMounted, getCurrentInstance } = Vue
            const platform = {
                setup() {
                    const { proxy } = getCurrentInstance();

                    const state = reactive({
                        platform: new URLSearchParams(location.search).get('platform'),
                        label: new URLSearchParams(location.search).get('label'),
                    })

                    const form = reactive({
                        model: {
                            app_id: '',
                            secret: '',
                            status: '',
                            payment: {
                                alipay: '',
                                wechat: '',
                                methods: [],
                            },
                            share: {
                                methods: [],
                                forwardInfo: {
                                    title: '',
                                    subtitle: '',
                                    image: '',
                                },
                                linkAddress: '',
                                posterInfo: {
                                    user_bg: '',
                                    goods_bg: '',
                                    groupon_bg: '',
                                },
                            },
                            download: {
                                android: '',
                                ios: '',
                                local: '',
                            },
                        },
                        rules: {}
                    })

                    function getDetail() {
                        Fast.api.ajax({
                            url: `shopro/config/platform/platform/${state.platform}`,
                            type: 'GET',
                        }, function (ret, res) {
                            form.model = res.data;
                            if (isEmpty(form.model.share)) {
                                form.model.share = {
                                    methods: [],
                                    forwardInfo: {
                                        title: '',
                                        subtitle: '',
                                        image: '',
                                    },
                                    linkAddress: '',
                                    posterInfo: {
                                        user_bg: '',
                                        goods_bg: '',
                                        groupon_bg: '',
                                    },
                                };
                            }
                            if (isEmpty(form.model.download)) {
                                form.model.download = {
                                    android: '',
                                    ios: '',
                                    local: '',
                                };
                            }
                            if (state.platform != 'H5' && !form.model.share.methods.includes('forward')) {
                                form.model.share.methods.push('forward');
                            }
                            if (form.model.payment.wechat === 0) {
                                form.model.payment.wechat = ''
                            }
                            if (form.model.payment.alipay === 0) {
                                form.model.payment.alipay = ''
                            }
                            return false
                        }, function (ret, res) { })
                    }

                    function onConfiguration() {
                        if (state.platform == 'H5') {
                            window.open('https://pay.weixin.qq.com/wiki/doc/apiv3/open/pay/chapter2_6_1.shtml');
                        }
                        if (state.platform == 'App') {
                            window.open('https://open.weixin.qq.com/');
                        }
                    }

                    const payConfig = reactive({
                        select: {
                            alipay: [],
                            wechat: [],
                        }
                    })
                    function getPayConfigSelect() {
                        Fast.api.ajax({
                            url: 'shopro/pay_config/select',
                            type: 'GET',
                        }, function (ret, res) {
                            payConfig.select.alipay = []
                            payConfig.select.wechat = []
                            res.data.forEach(item => {
                                if (item.type == 'alipay') {
                                    payConfig.select.alipay.push(item)
                                }
                                if (item.type == 'wechat') {
                                    payConfig.select.wechat.push(item)
                                }
                            })
                            return false
                        }, function (ret, res) { })
                    }
                    function onAddPayConfig() {
                        Fast.api.open('shopro/pay_config/add?type=add', "添加", {
                            callback() {
                                getPayConfigSelect()
                            }
                        })
                    }

                    function onConfirm() {
                        proxy.$refs['formRef'].validate((valid) => {
                            if (valid) {
                                Fast.api.ajax({
                                    url: `shopro/config/platform/platform/${state.platform}`,
                                    type: 'POST',
                                    data: form.model,
                                }, function (ret, res) {
                                    Fast.api.close()
                                }, function (ret, res) { })
                            }
                        });
                    }

                    onMounted(() => {
                        getPayConfigSelect()
                        getDetail()
                    })

                    return {
                        state,
                        form,
                        onConfiguration,
                        payConfig,
                        onAddPayConfig,
                        onConfirm,
                    }
                }
            }
            createApp('platform', platform);
        },
    };
    return Controller;
});
