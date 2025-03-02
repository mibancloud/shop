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
                                user: { field: 'id', value: '' },
                                createtime: [],
                                logintime: [],
                            },
                            tools: {
                                user: {
                                    type: 'tinputprepend',
                                    label: '用户信息',
                                    placeholder: '请输入查询内容',
                                    value: {
                                        field: 'id',
                                        value: '',
                                    },
                                    options: {
                                        data: [{
                                            label: '用户ID',
                                            value: 'id',
                                        },
                                        {
                                            label: '用户名',
                                            value: 'username',
                                        },
                                        {
                                            label: '昵称',
                                            value: 'nickname',
                                        },
                                        {
                                            label: '手机号',
                                            value: 'mobile',
                                        },
                                        {
                                            label: '邮箱',
                                            value: 'email',
                                        }],
                                    }
                                },
                                createtime: {
                                    type: 'tdatetimerange',
                                    label: '注册时间',
                                    value: [],
                                },
                                logintime: {
                                    type: 'tdatetimerange',
                                    label: '上次登录',
                                    value: [],
                                },
                            },
                            condition: {},
                        }
                    })

                    function getData() {
                        let tempSearch = JSON.parse(JSON.stringify(state.filter.data));
                        let search = composeFilter(tempSearch, {
                            username: 'like',
                            nickname: 'like',
                            mobile: 'like',
                            email: 'like',
                            createtime: 'range',
                            logintime: 'range',
                        });
                        Fast.api.ajax({
                            url: 'shopro/user/user',
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

                    const pagination = reactive({
                        page: 1,
                        list_rows: 10,
                        total: 0,
                    })

                    function onDetail(id) {
                        Fast.api.open(`shopro/user/user/detail?id=${id}`, "详情", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onDelete(id) {
                        Fast.api.ajax({
                            url: `shopro/user/user/delete/id/${id}`,
                            type: 'DELETE',
                        }, function (ret, res) {
                            getData()
                        }, function (ret, res) { })
                    }

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
                        onDetail,
                        onDelete
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
                        type: new URLSearchParams(location.search).get('type'),
                        id: new URLSearchParams(location.search).get('id'),
                        detail: {}
                    })

                    function getDetail() {
                        Fast.api.ajax({
                            url: `shopro/user/user/detail/id/${state.id}`,
                            type: 'GET',
                        }, function (ret, res) {
                            state.detail = res.data;
                            return false
                        }, function (ret, res) { })
                    }

                    const platform = {
                        wechat: {
                            openPlatform: '微信开放平台',
                            miniProgram: '微信小程序',
                            officialAccount: '微信公众平台',
                        },
                    };

                    function statusStyle(key) {
                        let flag = state.detail.verification?.[key];
                        return `<span style="color:${flag ? 'var(--el-color-success)' : 'var(--el-color-warning)'}">
                      ${flag ? '已' : '未'}${key == 'username' || key == 'password' ? '设置' : '认证'}
                      </span>`;
                    }

                    function onSelectAvatar() {
                        Fast.api.open(`general/attachment/select`, "选择", {
                            callback: function (data) {
                                state.detail.avatar = data.url;
                            }
                        });
                    }

                    async function onSave() {
                        Fast.api.ajax({
                            url: `shopro/user/user/edit/id/${state.id}`,
                            type: 'POST',
                            data: state.detail,
                        }, function (ret, res) {
                            getDetail()
                        }, function (ret, res) { })
                    }

                    function onChangeParentUser() {
                        Fast.api.open(`shopro/commission/agent/select?id=${state.id}`, "更换上级分销商", {
                            callback() {
                                getDetail()
                            }
                        })
                    }

                    function onRecharge(type) {
                        Fast.api.open(`shopro/user/user/recharge?type=${type}&id=${state.detail.id}`, "充值", {
                            callback: function () {
                                getDetail();
                                getLog()
                            }
                        });
                    }

                    const log = reactive({
                        tabActive: 'money',
                        data: [],
                    })
                    function getLog() {
                        let url
                        let search = {}
                        if (log.tabActive == 'money' || log.tabActive == 'score' || log.tabActive == 'commission') {
                            url = `shopro/user/wallet_log/${log.tabActive}/id/${state.id}`
                        }
                        if (log.tabActive == 'order') {
                            url = `shopro/order/order`
                            search = {
                                search: JSON.stringify({
                                    user_id: state.id
                                })
                            }
                        }
                        if (log.tabActive == 'share') {
                            url = `shopro/share`
                            search = {
                                id: state.id
                            }
                        }
                        if (log.tabActive == 'coupon') {
                            url = `shopro/user/user/coupon/id/${state.id}`
                        }
                        Fast.api.ajax({
                            url: url,
                            type: 'GET',
                            data: {
                                page: pagination.page,
                                list_rows: pagination.list_rows,
                                ...search,
                            },
                        }, function (ret, res) {
                            if (log.tabActive == 'order') {
                                log.data = res.data.orders.data
                                pagination.total = res.data.orders.total
                            } else {
                                log.data = res.data.data
                                pagination.total = res.data.total
                            }
                            return false
                        }, function (ret, res) { })
                    }
                    const pagination = reactive({
                        page: 1,
                        list_rows: 10,
                        total: 0,
                    })
                    function onChangeTab() {
                        log.data = []
                        pagination.page = 1
                        pagination.total = 0
                        getLog()
                    }

                    onMounted(() => {
                        getDetail()
                        getLog()
                    })

                    return {
                        state,
                        getDetail,
                        platform,
                        statusStyle,
                        onSelectAvatar,
                        onSave,
                        onChangeParentUser,
                        onRecharge,
                        log,
                        getLog,
                        pagination,
                        onChangeTab,
                    }
                }
            }
            createApp('detail', detail);
        },
        recharge: () => {
            const { reactive, getCurrentInstance } = Vue
            const recharge = {
                setup() {
                    const { proxy } = getCurrentInstance();

                    const state = reactive({
                        type: new URLSearchParams(location.search).get('type'),
                        id: new URLSearchParams(location.search).get('id')
                    })

                    const form = reactive({
                        model: {
                            id: state.id,
                            type: state.type,
                            amount: '',
                            memo: '',
                        },
                        rules: {
                            amount: [{ required: true, message: '请输入', trigger: 'blur' }],
                        },
                    })

                    function onConfirm() {
                        proxy.$refs['formRef'].validate((valid) => {
                            if (valid) {
                                Fast.api.ajax({
                                    url: `shopro/user/user/recharge`,
                                    type: 'POST',
                                    data: form.model,
                                }, function (ret, res) {
                                    Fast.api.close()
                                }, function (ret, res) { })
                            }
                        });
                    }

                    return {
                        state,
                        form,
                        onConfirm
                    }
                }
            }
            createApp('recharge', recharge);
        },
        select: function () {
            const { reactive, onMounted } = Vue
            const select = {
                setup() {
                    const state = reactive({
                        id: new URLSearchParams(location.search).get('id') || false,

                        filter: {
                            drawer: false,
                            data: {
                                keyword: '',
                            },
                            tools: {},
                            condition: {},
                        },
                        data: [],

                        ids: [],
                        isSelectAll: false,
                        isIndeterminate: false,
                    })

                    function getData() {
                        let tempSearch = JSON.parse(JSON.stringify(state.filter.data));
                        let search = composeFilter(tempSearch, {
                            keyword: 'like',
                        });
                        Fast.api.ajax({
                            url: 'shopro/user/user/select',
                            type: 'GET',
                            data: {
                                page: pagination.page,
                                list_rows: pagination.list_rows,
                                ...search,
                            },
                        }, function (ret, res) {
                            state.data = res.data.data
                            pagination.total = res.data.total

                            calculateSelect();

                            return false
                        }, function (ret, res) { })
                    }

                    const pagination = reactive({
                        page: 1,
                        list_rows: 10,
                        total: 0,
                    })

                    function onChangeFilter() {
                        pagination.page = 1
                        getData()
                    }

                    function onSelect(type, row) {
                        if (type) {
                            state.ids.push(row.id);
                        } else {
                            let findIndex = state.ids.findIndex((id) => id == row.id);
                            state.ids.splice(findIndex, 1);
                        }
                        calculateSelect();
                    };
                    function onSelectAll(type) {
                        if (type) {
                            state.data.forEach((item) => {
                                state.ids.push(item.id);
                            });
                            state.ids = Array.from(new Set(state.ids));
                        } else {
                            state.data.forEach((item) => {
                                if (state.ids.findIndex((id) => id == item.id) !== -1) {
                                    state.ids.splice(
                                        state.ids.findIndex((id) => id == item.id),
                                        1,
                                    );
                                }
                            });
                        }
                        calculateSelect();
                    }
                    function calculateSelect() {
                        state.isSelectAll = false;
                        state.isIndeterminate = false;
                        if (state.data.every((item) => state.ids.includes(item.id))) {
                            state.isSelectAll = true;
                            state.isIndeterminate = false;
                        } else if (state.data.some((item) => state.ids.includes(item.id))) {
                            state.isSelectAll = false;
                            state.isIndeterminate = true;
                        }

                        if (state.data.length === 0) {
                            state.isSelectAll = false;
                            state.isIndeterminate = false;
                        }
                    }

                    function onConfirm() {
                        Fast.api.ajax({
                            url: `shopro/coupon/send/id/${state.id}`,
                            type: 'POST',
                            data: {
                                user_ids: state.ids
                            },
                        }, function (ret, res) {
                            Fast.api.close()
                        }, function (ret, res) { })
                    }

                    onMounted(() => {
                        getData()
                    })

                    return {
                        state,
                        getData,
                        pagination,
                        onChangeFilter,
                        onSelect,
                        onSelectAll,
                        onConfirm
                    }
                }
            }
            createApp('select', select);
        },
    };
    return Controller;
});
