define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        data: () => {
            return {
                statusStyle: {
                    unpaid: 'warning',
                    paid: 'success',
                    completed: 'success',
                    closed: 'danger',
                    cancel: 'info',
                    closed: 'danger',
                }
            }
        },
        index: () => {
            const { ref, reactive, onMounted } = Vue
            const index = {
                setup() {
                    const state = reactive({
                        data: [],
                        filter: {
                            drawer: false,
                            data: {
                                status: 'all',
                                order: { field: 'id', value: '' },
                                user: { field: 'user_id', value: '' },
                                platform: '',
                                'pay.pay_type': '',
                                createtime: [],
                            },
                            tools: {
                                order: {
                                    type: 'tinputprepend',
                                    label: '查询内容',
                                    placeholder: '请输入查询内容',
                                    value: {
                                        field: 'id',
                                        value: '',
                                    },
                                    options: {
                                        data: [{
                                            label: '订单ID',
                                            value: 'id',
                                        },
                                        {
                                            label: '订单编号',
                                            value: 'order_sn',
                                        },
                                        {
                                            label: '支付单号',
                                            value: 'pay.pay_sn',
                                        }],
                                    }
                                },
                                user: {
                                    type: 'tinputprepend',
                                    label: '用户信息',
                                    placeholder: '请输入查询内容',
                                    value: {
                                        field: 'user_id',
                                        value: '',
                                    },
                                    options: {
                                        data: [{
                                            label: '用户ID',
                                            value: 'user_id',
                                        },
                                        {
                                            label: '用户昵称',
                                            value: 'user.nickname',
                                        },
                                        {
                                            label: '用户手机号',
                                            value: 'user.mobile',
                                        }],
                                    }
                                },
                                platform: {
                                    type: 'tselect',
                                    label: '订单来源',
                                    value: '',
                                    options: {
                                        data: [],
                                        props: {
                                            label: 'name',
                                            value: 'type',
                                        },
                                    },
                                },
                                'pay.pay_type': {
                                    type: 'tselect',
                                    label: '支付方式',
                                    value: '',
                                    options: {
                                        data: [],
                                        props: {
                                            label: 'name',
                                            value: 'type',
                                        },
                                    },
                                },
                                createtime: {
                                    type: 'tdatetimerange',
                                    label: '下单时间',
                                    value: [],
                                },
                            },
                            condition: {},
                        },
                    })

                    const type = reactive({
                        data: {}
                    })
                    function getType() {
                        Fast.api.ajax({
                            url: 'shopro/trade/order/getType',
                            type: 'GET',
                        }, function (ret, res) {
                            type.data = res.data
                            for (key in res.data) {
                                if (key == 'pay_type') {
                                    state.filter.tools['pay.pay_type'].options.data = res.data[key]
                                } else if (key == 'platform') {
                                    state.filter.tools[key].options.data = res.data[key]
                                }
                            }
                            return false
                        }, function (ret, res) { })
                    }

                    function getData() {
                        let tempSearch = JSON.parse(JSON.stringify(state.filter.data));
                        let search = composeFilter(tempSearch, {
                            order_sn: 'like',
                            'pay.pay_sn': 'like',
                            'user.nickname': 'like',
                            'user.mobile': 'like',
                            createtime: 'range',
                        });
                        Fast.api.ajax({
                            url: 'shopro/trade/order',
                            type: 'GET',
                            data: {
                                page: pagination.page,
                                list_rows: pagination.list_rows,
                                ...search,
                            },
                        }, function (ret, res) {
                            state.data = res.data.orders.data
                            pagination.total = res.data.orders.total
                            type.data?.status?.forEach(item => {
                                item.num = res.data[item.type]
                            })
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

                    const exportLoading = ref(false);
                    function onExport() {
                        exportLoading.value = true;
                        let tempSearch = JSON.parse(JSON.stringify(state.filter.data));
                        let search = composeFilter(tempSearch, {
                            order_sn: 'like',
                            'pay.pay_sn': 'like',
                            'user.nickname': 'like',
                            'user.mobile': 'like',
                            createtime: 'range',
                        });

                        if (Config.save_type == 'download') {
                            window.location.href = `${Config.moduleurl}/shopro/trade/order/export?page=${pagination.page}&list_rows=${pagination.list_rows}&search=${search.search}`;
                            exportLoading.value = false;
                        } else if (Config.save_type == 'save') {
                            Fast.api.ajax({
                                url: 'shopro/trade/order/export',
                                type: 'GET',
                                data: {
                                    page: pagination.page,
                                    list_rows: pagination.list_rows,
                                    ...search,
                                },
                            }, function (ret, res) {
                                exportLoading.value = false;
                            }, function (ret, res) { })
                        }
                    }

                    function onDetail(id) {
                        Fast.api.open(`shopro/trade/order/detail/id/${id}?id=${id}`, "详情", {
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
                        ...Controller.data(),
                        state,
                        type,
                        getData,
                        onOpenFilter,
                        onChangeFilter,
                        onChangeTab,
                        pagination,
                        exportLoading,
                        onExport,
                        onDetail,
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
                        detail: {},
                    })

                    function getDetail() {
                        Fast.api.ajax({
                            url: `shopro/trade/order/detail/id/${state.id}`,
                            type: 'GET',
                        }, function (ret, res) {
                            state.detail = res.data;
                            return false
                        }, function (ret, res) { })
                    }

                    onMounted(() => {
                        getDetail()
                    })

                    return {
                        ...Controller.data(),
                        state,
                    }
                }
            }
            createApp('detail', detail);
        },
    };
    return Controller;
});
