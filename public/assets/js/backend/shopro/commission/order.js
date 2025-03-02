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
                                reward: { field: 'reward.agent_id', value: '' },
                                'order.order_sn': '',
                                'order_item.goods_title': '',
                                'order.createtime': [],
                                commission_time: [],
                                commission_order_status: '',
                                commission_reward_status: '',
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
                                    label: '推广分销商',
                                    placeholder: '请输入查询内容',
                                    value: {
                                        field: 'agent_id',
                                        value: '',
                                    },
                                    options: {
                                        data: [{
                                            label: '推广ID',
                                            value: 'agent_id',
                                        },
                                        {
                                            label: '推广昵称',
                                            value: 'agent.nickname',
                                        },
                                        {
                                            label: '推广手机号',
                                            value: 'agent.mobile',
                                        }],
                                    }
                                },
                                reward: {
                                    type: 'tinputprepend',
                                    label: '结算分销商',
                                    placeholder: '请输入查询内容',
                                    value: {
                                        field: 'reward.agent_id',
                                        value: '',
                                    },
                                    options: {
                                        data: [{
                                            label: '结算ID',
                                            value: 'reward.agent_id',
                                        },
                                        {
                                            label: '结算昵称',
                                            value: 'reward.agent_nickname',
                                        },
                                        {
                                            label: '结算手机号',
                                            value: 'reward.agent_mobile',
                                        }],
                                    }
                                },
                                'order.order_sn': {
                                    type: 'tinput',
                                    label: '订单号',
                                    placeholder: '请输入查询内容',
                                    value: '',
                                },
                                'order_item.goods_title': {
                                    type: 'tinput',
                                    label: '商品名称',
                                    placeholder: '请输入查询内容',
                                    value: '',
                                },
                                'order.createtime': {
                                    type: 'tdatetimerange',
                                    label: '下单时间',
                                    value: [],
                                },
                                commission_time: {
                                    type: 'tdatetimerange',
                                    label: '分佣时间',
                                    value: [],
                                },
                                commission_order_status: {
                                    type: 'tselect',
                                    label: '订单结算状态',
                                    value: '',
                                    options: {
                                        data: [{
                                            label: '已扣除',
                                            value: '-2',
                                        },
                                        {
                                            label: '已取消',
                                            value: '-1',
                                        },
                                        {
                                            label: '不计入',
                                            value: '0',
                                        },
                                        {
                                            label: '已计入',
                                            value: '1',
                                        }],
                                    },
                                },
                                commission_reward_status: {
                                    type: 'tselect',
                                    label: '佣金结算状态',
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
                        count: {},
                        statusStyle: {
                            '-2': 'danger',
                            '-1': 'warning',
                            0: 'info',
                            1: 'success',
                        }
                    })

                    function getData() {
                        let tempSearch = JSON.parse(JSON.stringify(state.filter.data));
                        let search = composeFilter(tempSearch, {
                            'buyer.nickname': 'like',
                            'buyer.mobile': 'like',
                            'agent.nickname': 'like',
                            'agent.mobile': 'like',
                            'reward.agent_nickname': 'like',
                            'reward.agent_mobile': 'like',
                            'order.order_sn': 'like',
                            'order_item.goods_title': 'like',
                            'order.createtime': 'range',
                            commission_time: 'range',
                        });
                        Fast.api.ajax({
                            url: 'shopro/commission/order',
                            type: 'GET',
                            data: {
                                page: pagination.page,
                                list_rows: pagination.list_rows,
                                ...search,
                            },
                        }, function (ret, res) {
                            state.count = res.data.count;
                            state.data = res.data.list.data
                            pagination.total = res.data.list.total
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

                    const arraySpanMethod = ({ row, column, rowIndex, columnIndex }) => {
                        if (columnIndex == 1) {
                            return [1, 6];
                        } else if (
                            columnIndex == 3 ||
                            columnIndex == 4 ||
                            columnIndex == 5 ||
                            columnIndex == 6 ||
                            columnIndex == 7
                        ) {
                            return [0, 0];
                        }
                    };

                    function countRewards(row) {
                        let commission = 0;
                        let commissioned = 0;
                        row.forEach((r) => {
                            if (r.status == 1) {
                                commissioned += Number(r.commission);
                            }
                            commission += Number(r.commission);
                        });

                        return `${commission.toFixed(2)}元/${commissioned.toFixed(2)}元`;
                    }

                    const rewardsPopover = reactive({
                        flag: {},
                        commission: '',
                    });
                    function onConfirmRewardsPopover(index, id) {
                        Fast.api.ajax({
                            url: 'shopro/commission/order/edit',
                            type: 'POST',
                            data: {
                                commission_reward_id: id,
                                commission: rewardsPopover.commission,
                            },
                        }, function (ret, res) {
                            onCancelRewardsPopover(index)
                            getData()
                        }, function (ret, res) { })
                    }
                    function onCancelRewardsPopover(index) {
                        rewardsPopover.flag[index] = false;
                        rewardsPopover.commission = '';
                    }

                    const commissionPopover = reactive({
                        flag: {},
                        type: null,
                        isDelete: '1',
                    });
                    function onConfirmCommissionPopover(index, id) {
                        let params = { commission_order_id: id };
                        if (commissionPopover.type == 'confirm') {
                            onConfirm(params);
                        }
                        if (commissionPopover.type == 'cancel') {
                            params = { ...params, deduct_order_money: commissionPopover.isDelete };
                            onCancel(params);
                        }
                        if (commissionPopover.type == 'back') {
                            params = { ...params, deduct_order_money: commissionPopover.isDelete };
                            onBack(params);
                        }
                        onCancelCommissionPopover(index);
                    }
                    function onCancelCommissionPopover(index) {
                        commissionPopover.flag[index] = false;
                        commissionPopover.isDelete = '1';
                    }

                    function onConfirm(data) {
                        Fast.api.ajax({
                            url: 'shopro/commission/order/confirm',
                            type: 'POST',
                            data: data,
                        }, function (ret, res) {
                            getData();
                        }, function (ret, res) { })
                    }

                    function onCancel(data) {
                        Fast.api.ajax({
                            url: 'shopro/commission/order/cancel',
                            type: 'POST',
                            data: data,
                        }, function (ret, res) {
                            getData();
                        }, function (ret, res) { })
                    }

                    function onBack(data) {
                        Fast.api.ajax({
                            url: 'shopro/commission/order/back',
                            type: 'POST',
                            data: data,
                        }, function (ret, res) {
                            getData();
                        }, function (ret, res) { })
                    }

                    function onOpenGoodsDetail(id) {
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

                    const exportLoading = ref(false);
                    function onExport(type) {
                        exportLoading.value = true;
                        let tempSearch = JSON.parse(JSON.stringify(state.filter.data));
                        let search = composeFilter(tempSearch, {
                            'buyer.nickname': 'like',
                            'buyer.mobile': 'like',
                            'agent.nickname': 'like',
                            'agent.mobile': 'like',
                            'reward.agent_nickname': 'like',
                            'reward.agent_mobile': 'like',
                            'order.order_sn': 'like',
                            'order_item.goods_title': 'like',
                            'order.createtime': 'range',
                            commission_time: 'range',
                        });
                        if (Config.save_type == 'download') {
                            window.location.href = `${Config.moduleurl}/shopro/commission/order/${type}?page=${pagination.page}&list_rows=${pagination.list_rows}&search=${search.search}`;
                            exportLoading.value = false;
                        } else if (Config.save_type == 'save') {
                            Fast.api.ajax({
                                url: `shopro/commission/order/${type}`,
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
                        onClipboard,
                        state,
                        getData,
                        onOpenFilter,
                        onChangeFilter,
                        pagination,
                        arraySpanMethod,
                        countRewards,
                        rewardsPopover,
                        onConfirmRewardsPopover,
                        onCancelRewardsPopover,
                        commissionPopover,
                        onConfirmCommissionPopover,
                        onCancelCommissionPopover,
                        onConfirm,
                        onCancel,
                        onBack,
                        onOpenGoodsDetail,
                        onOpenOrderDetail,
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
