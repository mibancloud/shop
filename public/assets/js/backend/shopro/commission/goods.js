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
                                keyword: { field: 'id', value: '' },
                                'commission_goods.status': '',
                            },
                            tools: {
                                keyword: {
                                    type: 'tinputprepend',
                                    label: '商品信息',
                                    placeholder: '请输入查询内容',
                                    value: {
                                        field: 'id',
                                        value: '',
                                    },
                                    options: {
                                        data: [{
                                            label: '商品ID',
                                            value: 'id',
                                        },
                                        {
                                            label: '商品名称',
                                            value: 'title',
                                        },
                                        {
                                            label: '商品副标题',
                                            value: 'subtitle',
                                        }],
                                    }
                                },
                                'commission_goods.status': {
                                    type: 'tselect',
                                    label: '分销状态',
                                    value: '',
                                    options: {
                                        data: [{
                                            label: '不参与',
                                            value: '0',
                                        },
                                        {
                                            label: '参与中',
                                            value: '1',
                                        }],
                                    },
                                },
                            },
                            condition: {},
                        },
                        goodsStatusStyle: {
                            0: 'danger',
                            1: 'success',
                        },
                        statusStyle: {
                            up: 'success',
                            down: 'danger',
                            hidden: 'info',
                        },
                    })

                    function getData() {
                        let tempSearch = JSON.parse(JSON.stringify(state.filter.data));
                        let search = composeFilter(tempSearch, {
                            title: 'like',
                            subtitle: 'like',
                        });
                        Fast.api.ajax({
                            url: 'shopro/commission/goods',
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

                    const batchHandle = reactive({
                        data: [],
                    })
                    function onChangeSelection(val) {
                        batchHandle.data = val
                    }
                    function onBatchHandle(type) {
                        let ids = []
                        batchHandle.data.forEach((item) => {
                            ids.push(item.id)
                        })
                        switch (type) {
                            case 'edit':
                                onEdit(ids.join(','), 'batch')
                                break;
                        }
                    }

                    function onEdit(ids, rulesType = '') {
                        Fast.api.open(`shopro/commission/goods/edit?type=edit&id=${ids}&rulesType=${rulesType}`, "设置佣金", {
                            callback() {
                                getData()
                            }
                        })
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
                        batchHandle,
                        onChangeSelection,
                        onBatchHandle,
                        onEdit,
                    }
                }
            }
            createApp('index', index);
        },
        edit: () => {
            Controller.form();
        },
        form: () => {
            const { reactive, onMounted } = Vue
            const { ElMessage } = ElementPlus
            const addEdit = {
                setup() {
                    const state = reactive({
                        type: new URLSearchParams(location.search).get('type'),
                        id: new URLSearchParams(location.search).get('id'),
                        rulesType: new URLSearchParams(location.search).get('rulesType'),
                        detailData: {},
                        commission_goods: {
                            status: 1,
                            self_rules: new URLSearchParams(location.search).get('rulesType') == 'batch' ? 2 : 0,
                            commission_order_status: 1,
                            commission_config: {},
                            commission_rules: {},
                        },
                        defaultCommissionConfig: {
                            status: 0,
                            level: 2,
                            self_buy: 0,
                            reward_type: 'goods_price',
                            reward_event: 'paid',
                        },
                        commission_config_temp: {},
                        commission_rules: {},
                        levelData: [],
                    })

                    function getDetail() {
                        Fast.api.ajax({
                            url: `shopro/commission/goods/detail/id/${state.id}`,
                            type: 'GET',
                        }, function (ret, res) {
                            state.data = res.data;

                            if (state.rulesType != 'batch') {
                                state.detailData = state.data.goods[0];
                            }

                            Object.keys(state.defaultCommissionConfig).forEach((key) => {
                                if (key != 'status') state.defaultCommissionConfig[key] = state.data.config[key];
                            });

                            state.commission_config_temp = JSON.parse(JSON.stringify(state.defaultCommissionConfig));

                            if (isEmpty(state.detailData.commission_goods)) {
                                initCommission(state.commission_goods.self_rules);
                            } else {
                                state.commission_goods = state.detailData.commission_goods;

                                if (
                                    state.detailData.commission_goods.commission_config &&
                                    state.detailData.commission_goods.commission_config.status
                                ) {
                                    state.commission_config_temp = state.detailData.commission_goods.commission_config;
                                }

                                initCommission(state.detailData.commission_goods.self_rules);
                            }
                            return false
                        }, function (ret, res) { })
                    }

                    function getLevelSelect() {
                        Fast.api.ajax({
                            url: `shopro/commission/level/select`,
                            type: 'GET',
                            data: {
                                sort: 'level',
                                order: 'asc',
                            }
                        }, function (ret, res) {
                            state.levelData = res.data;
                            state.type == 'edit' && getDetail()
                            return false
                        }, function (ret, res) { })
                    }

                    function initCommission(type) {
                        let rate_money = {
                            1: {
                                rate: '',
                                money: '',
                            },
                            2: {
                                rate: '',
                                money: '',
                            },
                            3: {
                                rate: '',
                                money: '',
                            },
                        };
                        if (!isObject(state.commission_goods.commission_rules)) {
                            state.commission_goods.commission_rules = {};
                        }
                        if (type == 0) {
                            state.commission_goods.commission_rules = null;
                        } else if (type == 1) {
                            state.detailData.sku_prices.forEach((i) => {
                                if (state.commission_goods.commission_rules[i.id]) {
                                    state.levelData.forEach((j) => {
                                        if (state.commission_goods.commission_rules[i.id][j.level]) {
                                            for (let key in state.commission_goods.commission_rules[i.id][j.level]) {
                                                if (!state.commission_goods.commission_rules[i.id][j.level][key].rate) {
                                                    state.commission_goods.commission_rules[i.id][j.level][key].rate = '';
                                                } else if (!state.commission_goods.commission_rules[i.id][j.level][key].money) {
                                                    state.commission_goods.commission_rules[i.id][j.level][key].money = '';
                                                }
                                            }
                                        } else {
                                            state.commission_goods.commission_rules[i.id][j.level] = JSON.parse(JSON.stringify(rate_money));
                                        }
                                    });

                                    for (var level in state.commission_goods.commission_rules[i.id]) {
                                        let index = state.levelData.findIndex((l) => Number(l.level) == Number(level));
                                        if (index == -1) {
                                            delete state.commission_goods.commission_rules[i.id][level];
                                        }
                                    }
                                } else {
                                    state.commission_goods.commission_rules[i.id] = {};
                                    state.levelData.forEach((l) => {
                                        state.commission_goods.commission_rules[i.id][l.level] = JSON.parse(JSON.stringify(rate_money));
                                    });
                                }
                            });

                            for (var sku in state.commission_goods.commission_rules) {
                                let index = state.detailData.sku_prices.findIndex((s) => Number(s.id) == Number(sku));
                                if (index == -1) {
                                    delete state.commission_goods.commission_rules[sku];
                                }
                            }
                        } else if (type == 2) {
                            state.levelData.forEach((l) => {
                                if (state.commission_goods.commission_rules[l.level]) {
                                } else {
                                    state.commission_goods.commission_rules[l.level] = JSON.parse(JSON.stringify(rate_money));
                                }
                            });

                            for (var level in state.commission_goods.commission_rules) {
                                let index = state.levelData.findIndex((l) => Number(l.level) == Number(level));
                                if (index == -1) {
                                    delete state.commission_goods.commission_rules[level];
                                }
                            }
                        }
                    }

                    function onChangeSelfRules(self_rules) {
                        if (self_rules == 1 || self_rules == 2) {
                            state.commission_goods.commission_rules = {};
                            if (state.commission_config_temp.status == 0) {
                                state.commission_config_temp = JSON.parse(JSON.stringify(state.defaultCommissionConfig));
                            }
                        } else if (self_rules == 0) {
                            state.commission_goods.commission_rules = null;
                            state.commission_config_temp = JSON.parse(JSON.stringify(state.defaultCommissionConfig));
                        }
                        initCommission(self_rules);
                    }

                    function onChangeCommissionConfigStatus(val) {
                        if (val == 0) {
                            // 默认
                            state.commission_config_temp = JSON.parse(JSON.stringify(state.defaultCommissionConfig));
                        } else {
                            // 自定义
                            if (
                                state.detailData.commission_goods &&
                                state.detailData.commission_goods.commission_config
                            ) {
                                state.commission_config_temp = state.detailData.commission_goods.commission_config;
                            }

                            state.commission_config_temp.status = 1;
                        }
                    }

                    const commissionPopover = reactive({
                        flag: {},
                        form: {
                            money: '',
                            rate: '',
                        },
                    });
                    function onConfirmCommissionPopover(cl) {
                        if (state.commission_goods.self_rules == 1) {
                            for (let sku in state.commission_goods.commission_rules) {
                                for (let level in state.commission_goods.commission_rules[sku]) {
                                    state.commission_goods.commission_rules[sku][level][cl] = JSON.parse(JSON.stringify(commissionPopover.form))
                                }
                            }
                        } else if (state.commission_goods.self_rules == 2) {
                            for (let level in state.commission_goods.commission_rules) {
                                state.commission_goods.commission_rules[level][cl] = JSON.parse(JSON.stringify(commissionPopover.form))
                            }
                        }
                        onCancelCommissionPopover(cl);
                    }
                    function onCancelCommissionPopover(cl) {
                        commissionPopover.flag[cl] = false;
                        commissionPopover.form = {
                            money: '',
                            rate: '',
                        };
                    }

                    function onConfirm() {
                        const commission_goods = {
                            ...state.commission_goods,
                        };
                        commission_goods.commission_config = JSON.stringify({
                            ...state.commission_config_temp,
                        });

                        let flag = true
                        if (commission_goods.self_rules == 1) {
                            for (var key1 in commission_goods.commission_rules) {
                                for (var key2 in commission_goods.commission_rules[key1]) {
                                    for (var key3 in commission_goods.commission_rules[key1][key2]) {
                                        if (key3 <= state.commission_config_temp.level) {
                                            if ((commission_goods.commission_rules[key1][key2][key3].rate === '' || commission_goods.commission_rules[key1][key2][key3].rate < 0)
                                                && (commission_goods.commission_rules[key1][key2][key3].money === '' || commission_goods.commission_rules[key1][key2][key3].money < 0)) {
                                                flag = false
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        if (commission_goods.self_rules == 2) {
                            for (var key1 in commission_goods.commission_rules) {
                                for (var key2 in commission_goods.commission_rules[key1]) {
                                    if (key2 <= state.commission_config_temp.level) {
                                        if (commission_goods.commission_rules[key1][key2].rate === '' || commission_goods.commission_rules[key1][key2].rate < 0) {
                                            flag = false
                                        }
                                    }
                                }
                            }
                        }
                        if (!flag) {
                            ElMessage({
                                message: '请将数据填写完整',
                                type: 'warning',
                            })
                            return false
                        }

                        Fast.api.ajax({
                            url: `shopro/commission/goods/edit/id/${state.id}`,
                            type: 'POST',
                            data: JSON.parse(JSON.stringify(commission_goods))
                        }, function (ret, res) {
                            Fast.api.close()
                        }, function (ret, res) { })
                    }

                    onMounted(() => {
                        getLevelSelect()
                    })

                    return {
                        state,
                        onChangeSelfRules,
                        onChangeCommissionConfigStatus,
                        commissionPopover,
                        onConfirmCommissionPopover,
                        onCancelCommissionPopover,
                        onConfirm,
                    }
                }
            }
            createApp('addEdit', addEdit);
        }
    };
    return Controller;
});
