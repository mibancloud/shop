define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: () => {
            const { reactive, onMounted } = Vue
            const index = {
                setup() {
                    const state = reactive({
                        coupon_id: new URLSearchParams(location.search).get('coupon_id'),
                        data: [],
                        order: '',
                        sort: '',
                        filter: {
                            drawer: false,
                            data: {
                                user: { field: 'user_id', value: '' },
                                'order.order_sn': '',
                                status: '',
                                createtime: [],
                                use_time: [],
                            },
                            tools: {
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
                                        }]
                                    }
                                },
                                'order.order_sn': {
                                    type: 'tinput',
                                    label: '订单号',
                                    placeholder: '请输入订单号',
                                    value: '',
                                },
                                status: {
                                    type: 'tselect',
                                    label: '状态',
                                    value: '',
                                    options: {
                                        data: [{
                                            label: '未使用',
                                            value: 'geted',
                                        },
                                        {
                                            label: '已使用',
                                            value: 'used',
                                        },
                                        {
                                            label: '已过期',
                                            value: 'expired',
                                        }],
                                    },
                                },
                                createtime: {
                                    type: 'tdatetimerange',
                                    label: '领取时间',
                                    value: [],
                                },
                                use_time: {
                                    type: 'tdatetimerange',
                                    label: '使用时间',
                                    value: [],
                                },
                            },
                            condition: {},
                        },
                        statusClass: {
                            geted: 'info',
                            used: 'success',
                            expired: 'error',
                        },
                    })

                    function getData() {
                        let tempSearch = JSON.parse(JSON.stringify(state.filter.data));
                        let search = composeFilter(tempSearch, {
                            'user.nickname': 'like',
                            'user.mobile': 'like',
                            createtime: 'range',
                            use_time: 'range',
                        });
                        Fast.api.ajax({
                            url: 'shopro/user/coupon',
                            type: 'GET',
                            data: {
                                page: pagination.page,
                                list_rows: pagination.list_rows,
                                order: state.order,
                                sort: state.sort,
                                coupon_id: state.coupon_id,
                                ...search,
                            },
                        }, function (ret, res) {
                            state.data = res.data.data
                            pagination.total = res.data.total
                            return false
                        }, function (ret, res) { })
                    }

                    function onChangeSort({ prop, order }) {
                        state.order = order == 'ascending' ? 'asc' : 'desc';
                        state.sort = prop;
                        getData();
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

                    onMounted(() => {
                        getData()
                    })

                    return {
                        state,
                        getData,
                        onChangeSort,
                        onOpenFilter,
                        onChangeFilter,
                        pagination,
                    }
                }
            }
            createApp('index', index);
        },
    };
    return Controller;
});
