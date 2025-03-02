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
                                status: 'all',
                                order: { field: 'order.order_sn', value: '' },
                                user: { field: 'user.nickname', value: '' },
                                invoice: { field: 'name', value: '' },
                                type: '',
                            },
                            tools: {
                                order: {
                                    type: 'tinputprepend',
                                    label: '订单信息',
                                    placeholder: '请输入查询内容',
                                    value: {
                                        field: 'order.order_sn',
                                        value: '',
                                    },
                                    options: {
                                        data: [{
                                            label: '订单编号',
                                            value: 'order.order_sn',
                                        },
                                        {
                                            label: '订单ID',
                                            value: 'order_id',
                                        }]
                                    }
                                },
                                user: {
                                    type: 'tinputprepend',
                                    label: '用户信息',
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
                                            label: '用户手机号',
                                            value: 'user.mobile',
                                        }]
                                    }
                                },
                                invoice: {
                                    type: 'tinputprepend',
                                    label: '发票信息',
                                    placeholder: '请输入查询内容',
                                    value: {
                                        field: 'name',
                                        value: '',
                                    },
                                    options: {
                                        data: [{
                                            label: '发票抬头',
                                            value: 'name',
                                        },
                                        {
                                            label: '税号',
                                            value: 'tax_no',
                                        },
                                        {
                                            label: ' 联系方式',
                                            value: 'mobile',
                                        },
                                        {
                                            label: ' 公司地址',
                                            value: 'address',
                                        }]
                                    }
                                },
                                type: {
                                    type: 'tselect',
                                    label: '发票类型',
                                    value: '',
                                    options: {
                                        data: [{
                                            label: '全部',
                                            value: 'all',
                                        },
                                        {
                                            label: '个人',
                                            value: 'person',
                                        },
                                        {
                                            label: '企/事业单位',
                                            value: 'company',
                                        }]
                                    },
                                },
                            },
                            condition: {},
                        },
                        statusStyle: {
                            cancel: 'danger',
                            unpaid: 'info',
                            waiting: 'warning',
                            finish: 'success',
                        },
                    })

                    const type = reactive({
                        data: {
                            status: [{
                                name: '全部',
                                type: 'all',
                            },
                            {
                                name: '已取消',
                                type: 'cancel',
                            },
                            {
                                name: '未支付',
                                type: 'unpaid',
                            },
                            {
                                name: '等待处理',
                                type: 'waiting',
                            },
                            {
                                name: '已开具',
                                type: 'finish',
                            }],
                        }
                    })

                    function getData() {
                        let tempSearch = JSON.parse(JSON.stringify(state.filter.data));
                        let search = composeFilter(tempSearch, {
                            'order.order_sn': 'like',
                            'user.nickname': 'like',
                            'user.mobile': 'like',
                            name: 'like',
                            tax_no: 'like',
                            address: 'like',
                        });
                        Fast.api.ajax({
                            url: 'shopro/order/invoice',
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

                    function onConfirm(item) {
                        let params = {
                            id: item.id,
                            order_status_text: item.order_status_text,
                            order_fee: item.order_fee,
                            amount: item.amount,
                        }
                        Fast.api.open(`shopro/order/invoice/confirm/id/${item.id}?item=${encodeURI(JSON.stringify(params))}`, "开具发票", {
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
                        onOpenOrderDetail,
                        onConfirm,
                    }
                }
            }
            createApp('index', index);
        },
        confirm: () => {
            const { reactive, getCurrentInstance } = Vue
            const confirm = {
                setup() {
                    const { proxy } = getCurrentInstance();

                    const state = reactive({
                        item: JSON.parse(new URLSearchParams(location.search).get('item')),
                    })

                    const form = reactive({
                        model: {
                            invoice_amount: state.item.amount,
                            download_urls: [],
                        },
                        rules: {
                            invoice_amount: [{ required: true, message: '请输入实际开票金额', trigger: 'blur' }],
                            download_urls: [{ required: true, message: '请选择发票图片', trigger: 'blur' }],
                        },
                    });

                    function onConfirm() {
                        proxy.$refs['formRef'].validate((valid) => {
                            if (valid) {
                                Fast.api.ajax({
                                    url: `shopro/order/invoice/confirm/id/${state.item.id}`,
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
            createApp('confirm', confirm);
        },
    };
    return Controller;
});
