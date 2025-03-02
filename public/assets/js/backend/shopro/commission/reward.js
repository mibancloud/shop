define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: () => {
            const { reactive, onMounted, ref } = Vue
            const index = {
                setup() {
                    const state = reactive({
                        data: [],
                        filter: {
                            drawer: false,
                            data: {
                                buyer: { field: 'buyer_id', value: '' },
                                agent: { field: 'agent_id', value: '' },
                                'order.order_sn': '',
                                commission_time: [],
                                status: '',
                            },
                            tools: {
                                buyer: {
                                    type: 'tinputprepend',
                                    label: '下单用户',
                                    placeholder: '请输入查询内容',
                                    value: {
                                        field: 'buyer_id',
                                        value: '',
                                    },
                                    options: {
                                        data: [{
                                            label: '用户ID',
                                            value: 'buyer_id',
                                        },
                                        {
                                            label: '用户昵称',
                                            value: 'buyer.nickname',
                                        },
                                        {
                                            label: '用户手机号',
                                            value: 'buyer.mobile',
                                        }],
                                    }
                                },
                                agent: {
                                    type: 'tinputprepend',
                                    label: '结算分销商',
                                    placeholder: '请输入查询内容',
                                    value: {
                                        field: 'agent_id',
                                        value: '',
                                    },
                                    options: {
                                        data: [{
                                            label: '结算ID',
                                            value: 'agent_id',
                                        },
                                        {
                                            label: '结算昵称',
                                            value: 'agent.nickname',
                                        },
                                        {
                                            label: '结算手机号',
                                            value: 'agent.mobile',
                                        }],
                                    }
                                },
                                'order.order_sn': {
                                    type: 'tinput',
                                    label: '订单号',
                                    placeholder: '请输入查询内容',
                                    value: '',
                                },
                                commission_time: {
                                    type: 'tdatetimerange',
                                    label: '分佣时间',
                                    value: [],
                                },
                                status: {
                                    type: 'tselect',
                                    label: '入账状态',
                                    value: '',
                                    options: {
                                        data: [{
                                            label: '已退回',
                                            value: '-2',
                                        },
                                        {
                                            label: '已取消',
                                            value: '-1',
                                        },
                                        {
                                            label: '未结算',
                                            value: '0',
                                        },
                                        {
                                            label: '已结算',
                                            value: '1',
                                        }],
                                    },
                                },
                            },
                            condition: {},
                        },
                        statusStyle: {
                            '-2': 'danger',
                            '-1': 'warning',
                            0: 'info',
                            1: 'success',
                        },
                    })

                    function getData() {
                        let tempSearch = JSON.parse(JSON.stringify(state.filter.data));
                        let search = composeFilter(tempSearch, {
                            'buyer.nickname': 'like',
                            'buyer.mobile': 'like',
                            'agent.nickname': 'like',
                            'agent.mobile': 'like',
                            'order.order_sn': 'like',
                            commission_time: 'range',
                        });
                        Fast.api.ajax({
                            url: 'shopro/commission/reward',
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

                    const pagination = reactive({
                        page: 1,
                        list_rows: 10,
                        total: 0,
                    })

                    const exportLoading = ref(false);
                    function onExport(type) {
                        exportLoading.value = true;
                        let tempSearch = JSON.parse(JSON.stringify(state.filter.data));
                        let search = composeFilter(tempSearch, {
                            'buyer.nickname': 'like',
                            'buyer.mobile': 'like',
                            'agent.nickname': 'like',
                            'agent.mobile': 'like',
                            'order.order_sn': 'like',
                            commission_time: 'range',
                        });

                        if (Config.save_type == 'download') {
                            window.location.href = `${Config.moduleurl}/shopro/commission/reward/${type}?page=${pagination.page}&list_rows=${pagination.list_rows}&search=${search.search}`;
                            exportLoading.value = false;
                        } else if (Config.save_type == 'save') {
                            Fast.api.ajax({
                                url: `shopro/commission/reward/${type}`,
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

                    onMounted(() => {
                        getData()
                    })

                    return {
                        state,
                        getData,
                        onOpenFilter,
                        onChangeFilter,
                        pagination,
                        exportLoading,
                        onExport,
                    }
                }
            }
            createApp('index', index);
        },
    };
    return Controller;
});
