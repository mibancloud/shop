define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: () => {
            const { reactive, onMounted } = Vue
            const index = {
                setup() {
                    const state = reactive({
                        data: [],
                        filter: {
                            drawer: false,
                            data: {
                                'aftersale_list.aftersale_status': 'all',
                                'aftersale_list.goods_title': '',
                                keyword: { field: 'aftersale_list.aftersale_sn', value: '' },
                                user: { field: 'user.nickname', value: '' },
                                'aftersale_list.type': '',
                                'aftersale_list.dispatch_status': '',
                                'aftersale_list.refund_status': '',
                            },
                            tools: {
                                'aftersale_list.goods_title': {
                                    type: 'tinput',
                                    label: '商品名称',
                                    value: '',
                                },
                                keyword: {
                                    type: 'tinputprepend',
                                    label: '售后信息',
                                    placeholder: '请输入查询内容',
                                    value: {
                                        field: 'aftersale_list.aftersale_sn',
                                        value: '',
                                    },
                                    options: {
                                        data: [{
                                            label: '售后单号',
                                            value: 'aftersale_list.aftersale_sn',
                                        },
                                        {
                                            label: '售后手机号',
                                            value: 'aftersale_list.mobile',
                                        },
                                        {
                                            label: '订单编号',
                                            value: 'order_sn',
                                        }]
                                    }
                                },
                                user: {
                                    type: 'tinputprepend',
                                    label: '售后用户',
                                    placeholder: '请输入查询内容',
                                    value: {
                                        field: 'user.nickname',
                                        value: '',
                                    },
                                    options: {
                                        data: [{
                                            label: '用户昵称',
                                            value: 'user.nickname',
                                        },
                                        {
                                            label: '手机号',
                                            value: 'user.mobile',
                                        }]
                                    },
                                },
                                'aftersale_list.type': {
                                    type: 'tselect',
                                    label: '售后类型',
                                    value: '',
                                    options: {
                                        data: [],
                                        props: {
                                            label: 'name',
                                            value: 'type',
                                        },
                                    },
                                },
                                'aftersale_list.dispatch_status': {
                                    type: 'tselect',
                                    label: '发货状态',
                                    value: '',
                                    options: {
                                        data: [],
                                        props: {
                                            label: 'name',
                                            value: 'type',
                                        },
                                    },
                                },
                                'aftersale_list.refund_status': {
                                    type: 'tselect',
                                    label: '退款状态',
                                    value: '',
                                    options: {
                                        data: [],
                                        props: {
                                            label: 'name',
                                            value: 'type',
                                        },
                                    },
                                },
                            },
                            condition: {},
                        }
                    })

                    const type = reactive({
                        data: {}
                    })
                    function getType() {
                        Fast.api.ajax({
                            url: 'shopro/order/aftersale/getType',
                            type: 'GET',
                        }, function (ret, res) {
                            type.data = res.data
                            for (key in res.data) {
                                if (key == 'aftersale_status') {
                                } else {
                                    state.filter.tools[`aftersale_list.${key}`].options.data = res.data[key]
                                }
                            }
                            return false
                        }, function (ret, res) { })
                    }

                    function getData() {
                        let tempSearch = JSON.parse(JSON.stringify(state.filter.data));
                        let search = composeFilter(tempSearch, {
                            'aftersale_list.goods_title': 'like',
                            'aftersale_list.aftersale_sn': 'like',
                            'aftersale_list.mobile': 'like',
                            order_sn: 'like',
                            'user.nickname': 'like',
                            'user.mobile': 'like',
                        });
                        search = {
                            search: JSON.stringify({
                                'aftersale_list._': '',
                                ...JSON.parse(search.search),
                            }),
                        };
                        Fast.api.ajax({
                            url: 'shopro/order/aftersale',
                            type: 'GET',
                            data: {
                                page: pagination.page,
                                list_rows: pagination.list_rows,
                                ...search,
                            },
                        }, function (ret, res) {
                            state.data = res.data.data
                            pagination.total = res.data.total
                            return false
                        }, function (ret, res) { })
                    }

                    function onOpenFilter() {
                        state.filter.drawer = true
                    }
                    function onChangeFilter() {
                        pagination.page = 1
                        getData()
                        state.filter.drawer && (state.filter.drawer = false)
                    }

                    function onChangeTab() {
                        pagination.page = 1
                        getData()
                    }

                    const pagination = reactive({
                        page: 1,
                        list_rows: 10,
                        total: 0,
                    })

                    function onDetail(id) {
                        Fast.api.open(`shopro/order/aftersale/detail/id/${id}?id=${id}`, "详情", {
                            callback() {
                                getData()
                            }
                        })
                    }

                    function onOpenGoods(id) {
                        Fast.api.open(`shopro/goods/goods/add?type=edit&id=${id}`, "商品详情", {
                            callback() {
                                getData()
                            }
                        })
                    }

                    function onOpenOrderDetail(id) {
                        Fast.api.open(`shopro/order/order/detail?id=${id}`, "订单详情", {
                            callback() {
                                getData()
                            }
                        })
                    }

                    onMounted(() => {
                        getType()
                        getData()
                    })

                    return {
                        onClipboard,
                        state,
                        type,
                        getData,
                        onOpenFilter,
                        onChangeFilter,
                        onChangeTab,
                        pagination,
                        onDetail,
                        onOpenGoods,
                        onOpenOrderDetail,
                    }
                }
            }
            createApp('index', index);
        },
        detail: () => {
            const { reactive, onMounted } = Vue
            const detail = {
                setup() {
                    const state = reactive({
                        id: new URLSearchParams(location.search).get('id'),
                        data: {},
                        stepActive: 1,
                    })

                    function getDetail() {
                        Fast.api.ajax({
                            url: `shopro/order/aftersale/detail/id/${state.id}`,
                            type: 'GET',
                        }, function (ret, res) {
                            state.data = res.data;
                            setStepActive()
                            return false
                        }, function (ret, res) { })
                    }

                    function setStepActive() {
                        if (
                            state.data.aftersale_status == -1 ||
                            state.data.aftersale_status == -2 ||
                            state.data.aftersale_status == 2
                        ) {
                            state.stepActive = 3;
                        } else {
                            state.stepActive = state.data.aftersale_status + 1;
                        }
                    }

                    function onRefund() {
                        Fast.api.open(`shopro/order/aftersale/refund/id/${state.id}?id=${state.id}&suggest_refund_fee=${state.data.suggest_refund_fee}`, "售后退款", {
                            callback() {
                                getDetail()
                            }
                        })
                    }
                    function onRefuse() {
                        Fast.api.open(`shopro/order/aftersale/refuse/id/${state.id}?id=${state.id}`, "拒绝售后", {
                            callback() {
                                getDetail()
                            }
                        })
                    }
                    function onCompleted() {
                        Fast.api.ajax({
                            url: `shopro/order/aftersale/completed/id/${state.id}`,
                            type: 'POST',
                        }, function (ret, res) {
                            getDetail()
                        }, function (ret, res) { })
                    }

                    function onAddLog() {
                        Fast.api.open(`shopro/order/aftersale/addLog/id/${state.id}?id=${state.id}`, "回复买家", {
                            callback() {
                                getDetail()
                            }
                        })
                    }

                    onMounted(() => {
                        getDetail()
                    })

                    return {
                        onClipboard,
                        state,
                        onRefund,
                        onRefuse,
                        onCompleted,
                        onAddLog,
                    }
                }
            }
            createApp('detail', detail);
        },
        refund: () => {
            const { reactive, getCurrentInstance } = Vue
            const refund = {
                setup() {
                    const { proxy } = getCurrentInstance();

                    const state = reactive({
                        id: new URLSearchParams(location.search).get('id'),
                        suggest_refund_fee: new URLSearchParams(location.search).get('suggest_refund_fee'),
                    })

                    const form = reactive({
                        model: {
                            refund_type: 'back',
                            refund_money: '',
                        },
                        rules: {
                            refund_money: [{ required: true, message: '请输入退款金额', trigger: 'blur' }],
                        },
                    })

                    function onConfirm() {
                        proxy.$refs['formRef'].validate((valid) => {
                            if (valid) {
                                Fast.api.ajax({
                                    url: `shopro/order/aftersale/refund/id/${state.id}`,
                                    type: 'POST',
                                    data: form.model
                                }, function (ret, res) {
                                    Fast.api.close()
                                }, function (ret, res) { })
                            }
                        });
                    }

                    return {
                        state,
                        form,
                        onConfirm,
                    }
                }
            }
            createApp('refund', refund);
        },
        refuse: () => {
            const { reactive, getCurrentInstance } = Vue
            const refuse = {
                setup() {
                    const { proxy } = getCurrentInstance();

                    const state = reactive({
                        id: new URLSearchParams(location.search).get('id'),
                    })

                    const form = reactive({
                        model: {
                            refuse_msg: '',
                        },
                        rules: {
                            refuse_msg: [{ required: true, message: '请输入拒绝原因', trigger: 'blur' }],
                        },
                    })

                    function onConfirm() {
                        proxy.$refs['formRef'].validate((valid) => {
                            if (valid) {
                                Fast.api.ajax({
                                    url: `shopro/order/aftersale/refuse/id/${state.id}`,
                                    type: 'POST',
                                    data: form.model
                                }, function (ret, res) {
                                    Fast.api.close()
                                }, function (ret, res) { })
                            }
                        });
                    }

                    return {
                        state,
                        form,
                        onConfirm,
                    }
                }
            }
            createApp('refuse', refuse);
        },
        addlog: () => {
            const { reactive, getCurrentInstance } = Vue
            const addLog = {
                setup() {
                    const { proxy } = getCurrentInstance();

                    const state = reactive({
                        id: new URLSearchParams(location.search).get('id'),
                    })

                    const form = reactive({
                        model: {
                            content: '',
                            images: [],
                        },
                        rules: {
                            content: [{ required: true, message: '请输入', trigger: 'blur' }],
                        },
                    })

                    function onConfirm() {
                        proxy.$refs['formRef'].validate((valid) => {
                            if (valid) {
                                Fast.api.ajax({
                                    url: `shopro/order/aftersale/addLog/id/${state.id}`,
                                    type: 'POST',
                                    data: form.model
                                }, function (ret, res) {
                                    Fast.api.close()
                                }, function (ret, res) { })
                            }
                        });
                    }

                    return {
                        state,
                        form,
                        onConfirm,
                    }
                }
            }
            createApp('addLog', addLog);
        },
    };
    return Controller;
});
