define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: () => {
            const { reactive, onMounted } = Vue
            const index = {
                setup() {
                    const state = reactive({
                        data: [],
                        order: '',
                        sort: '',
                        filter: {
                            drawer: false,
                            data: {
                                status: 'all',
                                user: { field: 'user_id', value: '' },
                                withdraw_type: 'all',
                                createtime: [],
                                updatetime: [],
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
                                withdraw_type: {
                                    type: 'tselect',
                                    label: '提现方式',
                                    value: '',
                                    options: {
                                        data: [{
                                            label: '全部',
                                            value: 'all',
                                        },
                                        {
                                            label: '支付宝账户',
                                            value: 'alipay',
                                        },
                                        {
                                            label: '微信零钱',
                                            value: 'wechat',
                                        },
                                        {
                                            label: '银行卡',
                                            value: 'bank',
                                        }]
                                    },
                                },
                                createtime: {
                                    type: 'tdatetimerange',
                                    label: '下单时间',
                                    value: [],
                                },
                                updatetime: {
                                    type: 'tdatetimerange',
                                    label: '上次操作时间',
                                    value: [],
                                },
                            },
                            condition: {},
                        },
                        statusStyle: {
                            '-1': 'sa-color--danger',
                            0: 'sa-color--info',
                            1: 'sa-color--warning',
                            2: 'sa-color--success',
                        }
                    })

                    const type = reactive({
                        data: {
                            status: [{
                                name: '全部',
                                type: 'all',
                            },
                            {
                                name: '待审核',
                                type: '0',
                            },
                            {
                                name: '处理中',
                                type: '1',
                            },
                            {
                                name: '已处理',
                                type: '2',
                            },
                            {
                                name: '已拒绝',
                                type: '-1',
                            },],
                        }
                    })

                    function getData() {
                        let tempSearch = JSON.parse(JSON.stringify(state.filter.data));
                        let search = composeFilter(tempSearch, {
                            'user.nickname': 'like',
                            'user.mobile': 'like',
                            createtime: 'range',
                            updatetime: 'range',
                        });
                        Fast.api.ajax({
                            url: 'shopro/withdraw',
                            type: 'GET',
                            data: {
                                page: pagination.page,
                                list_rows: pagination.list_rows,
                                order: state.order,
                                sort: state.sort,
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

                    function onChangeTab() {
                        pagination.page = 1
                        getData()
                    }

                    const pagination = reactive({
                        page: 1,
                        list_rows: 10,
                        total: 0,
                    })

                    const handlePopover = reactive({
                        flag: {}
                    })

                    function onAgree(id, index, type) {
                        handlePopover.flag[index] = false;
                        Fast.api.ajax({
                            url: `shopro/withdraw/handle/id/${id}`,
                            type: 'POST',
                            data: {
                                action: type,
                            },
                        }, function (ret, res) {
                            getData();
                        }, function (ret, res) { })
                    }

                    function onRefuse(id) {
                        Fast.api.open(`shopro/withdraw/handle/id/${id}?id=${id}`, "拒绝", {
                            callback() {
                                getData()
                            }
                        })
                    }

                    function onLog(id) {
                        Fast.api.open(`shopro/withdraw/log/id/${id}?id=${id}`, "日志")
                    }

                    onMounted(() => {
                        getData()
                    })

                    return {
                        onClipboard,
                        state,
                        type,
                        getData,
                        onChangeSort,
                        onOpenFilter,
                        onChangeFilter,
                        onChangeTab,
                        pagination,
                        handlePopover,
                        onAgree,
                        onRefuse,
                        onLog,
                    }
                }
            }
            createApp('index', index);
        },
        handle: () => {
            const { reactive, getCurrentInstance } = Vue
            const handle = {
                setup() {
                    const { proxy } = getCurrentInstance();

                    const state = reactive({
                        id: new URLSearchParams(location.search).get('id'),
                    })

                    const form = reactive({
                        model: {
                            action: 'refuse',
                            refuse_msg: '',
                        },
                        rules: {
                            refuse_msg: [{ required: true, message: '请输入拒绝理由', trigger: 'blur' }],
                        },
                    });

                    function onConfirm() {
                        proxy.$refs['formRef'].validate((valid) => {
                            if (valid) {
                                Fast.api.ajax({
                                    url: `shopro/withdraw/handle/id/${state.id}`,
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
            createApp('handle', handle);
        },
        log: () => {
            const { reactive, onMounted } = Vue
            const log = {
                setup() {
                    const state = reactive({
                        id: new URLSearchParams(location.search).get('id'),
                        data: [],
                    })

                    function getData() {
                        Fast.api.ajax({
                            url: `shopro/withdraw/log/id/${state.id}`,
                            type: 'GET',
                        }, function (ret, res) {
                            state.data = res.data
                            return false
                        }, function (ret, res) { })
                    }

                    onMounted(() => {
                        getData()
                    })

                    return {
                        state,
                    }
                }
            }
            createApp('log', log);
        },
    };
    return Controller;
});
