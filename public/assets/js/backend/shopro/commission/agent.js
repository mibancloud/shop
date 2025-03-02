define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        data: () => {
            return {
                statusStyle: {
                    normal: { label: '正常', color: '#52c41a' },
                    pending: { label: '审核中', color: '#faad14' },
                    reject: { label: '拒绝', color: '#f56c6c' },
                    freeze: { label: '冻结', color: '#409eff' },
                    forbidden: { label: '禁用', color: '#999999' },
                }
            }
        },
        index: () => {
            const { reactive, onMounted } = Vue
            const index = {
                setup() {
                    const state = reactive({
                        data: [],
                        filter: {
                            drawer: false,
                            data: {
                                tabActive: 'all',
                                user: { field: 'user_id', value: '' },
                                level: '',
                                status: '',
                                createtime: [],
                            },
                            tools: {
                                user: {
                                    type: 'tinputprepend',
                                    label: '会员信息',
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
                                level: {
                                    type: 'tselect',
                                    label: '分销商等级',
                                    value: '',
                                    options: {
                                        data: [],
                                        props: {
                                            label: 'name',
                                            value: 'level',
                                        },
                                    },
                                },
                                status: {
                                    type: 'tselect',
                                    label: '审核状态',
                                    value: '',
                                    options: {
                                        data: [{
                                            value: 'normal',
                                            label: '正常',
                                        },
                                        {
                                            value: 'forbidden',
                                            label: '禁用',
                                        },
                                        {
                                            value: 'pending',
                                            label: '审核中',
                                        },
                                        {
                                            value: 'freeze',
                                            label: '冻结',
                                        },
                                        {
                                            value: 'reject',
                                            label: '拒绝',
                                        }],
                                    },
                                },
                                createtime: {
                                    type: 'tdatetimerange',
                                    label: '更新时间',
                                    value: [],
                                },
                            },
                            condition: {},
                        }
                    })

                    const type = reactive({
                        data: {
                            status: [{
                                type: 'all',
                                name: '分销商',
                            },
                            {
                                type: 'pending',
                                name: '待审核',
                            },
                            {
                                type: '0',
                                name: '待升级',
                            }],
                        }
                    })

                    function getLevelSelect() {
                        Fast.api.ajax({
                            url: `shopro/commission/level/select`,
                            type: 'GET',
                        }, function (ret, res) {
                            state.filter.tools.level.options.data = res.data;
                            return false
                        }, function (ret, res) { })
                    }

                    function getData() {
                        let tempSearch = JSON.parse(JSON.stringify(state.filter.data));
                        let search = composeFilter(tempSearch, {
                            'user.nickname': 'like',
                            'user.mobile': 'like',
                            createtime: 'range',
                        });
                        let temp = JSON.parse(search.search);
                        if (temp && temp.tabActive) {
                            if (temp.tabActive[0] == 'pending') {
                                temp.status = ['pending', '='];
                                delete temp.tabActive;
                            } else if (temp.tabActive[0] == '0') {
                                temp.level_status = ['0', '>'];
                                delete temp.tabActive;
                            }
                        }
                        search = { search: JSON.stringify(temp) };
                        Fast.api.ajax({
                            url: 'shopro/commission/agent',
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

                    function onEdit(id, data) {
                        Fast.api.ajax({
                            url: `shopro/commission/agent/edit/id/${id}`,
                            type: 'POST',
                            data: data,
                        }, function (ret, res) {
                            getData();
                        }, function (ret, res) { })
                    }

                    function onDetail(id) {
                        Fast.api.open(`shopro/commission/agent/detail/id/${id}?id=${id}`, "详情", {
                            callback() {
                                getData()
                            }
                        })
                    }

                    onMounted(() => {
                        getLevelSelect()
                        getData()
                    })

                    return {
                        ...Controller.data(),
                        state,
                        type,
                        getData,
                        onOpenFilter,
                        onChangeFilter,
                        onChangeTab,
                        pagination,
                        onEdit,
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
                        data: {},
                    })

                    function getDetail() {
                        Fast.api.ajax({
                            url: `shopro/commission/agent/detail/id/${state.id}`,
                            type: 'GET',
                        }, function (ret, res) {
                            state.data = res.data
                            applyInfo.data = JSON.parse(JSON.stringify(state.data.apply_info))
                            getLog()
                            return false
                        }, function (ret, res) { })
                    }

                    function onChangeStatus(status) {
                        onEdit({ status });
                    }

                    function onChangeLevel() {
                        Fast.api.open(`shopro/commission/level/select?level=${state.data.level}`, "更换分销等级", {
                            callback(level) {
                                onEdit({ level });
                            }
                        })
                    }

                    function onChangeParentUser() {
                        Fast.api.open(`shopro/commission/agent/select?id=${state.data.user_id}`, "更换上级分销商", {
                            callback() {
                                getDetail()
                            }
                        })
                    }

                    const applyInfo = reactive({
                        flag: false,
                        data: []
                    })
                    function onCancelApplyInfo() {
                        applyInfo.data = JSON.parse(JSON.stringify(state.data.apply_info));
                        applyInfo.flag = false;
                    }
                    function onSaveApplyInfo() {
                        onEdit({ apply_info: applyInfo.data });
                        applyInfo.flag = false;
                    }
                    function onDeleteApplyInfo(index) {
                        applyInfo.data.splice(index, 1);
                    }

                    function onEdit(data) {
                        Fast.api.ajax({
                            url: `shopro/commission/agent/edit/id/${state.id}`,
                            type: 'POST',
                            data,
                        }, function (ret, res) {
                            getDetail()
                        }, function (ret, res) { })
                    }

                    function onTeam() {
                        Fast.api.open(`shopro/commission/agent/team?id=${state.id}`, "查看团队")
                    }

                    const log = reactive({
                        tabActive: 'log',
                        data: [],
                        status: {
                            0: 'info',
                            1: 'success',
                            '-1': 'warning',
                            '-2': 'danger',
                        }
                    })

                    function getLog() {
                        let search = composeFilter({ agent_id: state.id });
                        Fast.api.ajax({
                            url: `shopro/commission/${log.tabActive}`,
                            type: 'GET',
                            data: {
                                page: pagination.page,
                                list_rows: pagination.list_rows,
                                ...search,
                            },
                        }, function (ret, res) {
                            if (log.tabActive == 'order') {
                                log.data = res.data.list.data;
                                pagination.total = res.data.list.total;
                            } else {
                                log.data = res.data.data;
                                pagination.total = res.data.total;
                            }
                            return false
                        }, function (ret, res) { })
                    }

                    function onChangeTab() {
                        pagination.page = 1
                        log.data = []
                        getLog()
                    }

                    const pagination = reactive({
                        page: 1,
                        list_rows: 10,
                        total: 0,
                    })

                    function countCommission(item) {
                        if (item) {
                            let commission = 0;
                            let commissioned = 0;
                            item.forEach((r) => {
                                if (r.status == 1) {
                                    commissioned += Number(r.commission);
                                }
                                commission += Number(r.commission);
                            });

                            return `${commission}元/${commissioned}元`;
                        }
                    }

                    onMounted(() => {
                        getDetail()
                    })

                    return {
                        ...Controller.data(),
                        state,
                        getDetail,
                        onChangeStatus,
                        onChangeLevel,
                        onChangeParentUser,
                        applyInfo,
                        onCancelApplyInfo,
                        onSaveApplyInfo,
                        onDeleteApplyInfo,
                        onEdit,
                        onTeam,
                        log,
                        getLog,
                        onChangeTab,
                        pagination,
                        countCommission,
                    }
                }
            }
            createApp('detail', detail);
        },
        select: () => {
            const { reactive, onMounted } = Vue
            const { ElMessage } = ElementPlus
            const select = {
                setup() {
                    const state = reactive({
                        id: new URLSearchParams(location.search).get('id'),
                        data: [],
                        filter: {
                            data: {
                                user: { field: 'user_id', value: '' },
                            },
                        },
                        userDetail: {},
                        parent_user_id: ''
                    })

                    async function getUserDetail() {
                        Fast.api.ajax({
                            url: `shopro/user/user/detail/id/${state.id}`,
                            type: 'GET',
                        }, function (ret, res) {
                            state.userDetail = res.data;
                            state.parent_user_id = state.userDetail?.parent_user_id;
                            return false
                        }, function (ret, res) { })
                    }

                    function getData() {
                        let tempSearch = JSON.parse(JSON.stringify(state.filter.data));
                        let search = composeFilter(tempSearch, {
                            'user.nickname': 'like',
                            'user.mobile': 'like',
                        });
                        Fast.api.ajax({
                            url: 'shopro/commission/agent/select',
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

                    const pagination = reactive({
                        page: 1,
                        list_rows: 10,
                        total: 0,
                    })

                    function onSelect(row) {
                        state.parent_user_id = row.user_id;
                    }
                    async function onConfirm() {
                        if (state.parent_user_id == -1) {
                            ElMessage.info('请选择上级');
                            return;
                        }
                        Fast.api.ajax({
                            url: `shopro/commission/agent/changeParentUser/id/${state.id}`,
                            type: 'POST',
                            data: {
                                parent_user_id: state.parent_user_id,
                            },
                        }, function (ret, res) {
                            Fast.api.close()
                        }, function (ret, res) { })
                    }

                    onMounted(() => {
                        getUserDetail()
                        getData()
                    })

                    return {
                        ...Controller.data(),
                        state,
                        getData,
                        pagination,
                        onSelect,
                        onConfirm,
                    }
                }
            }
            createApp('select', select);
        },
        team: () => {
            const { reactive, onMounted } = Vue
            const team = {
                setup() {
                    const state = reactive({
                        id: new URLSearchParams(location.search).get('id'),
                        data: {},
                    })

                    async function getTeam() {
                        Fast.api.ajax({
                            url: `shopro/commission/agent/team/id/${state.id}`,
                            type: 'GET',
                        }, function (ret, res) {
                            state.data = res.data;
                            return false
                        }, function (ret, res) { })
                    }

                    onMounted(() => {
                        getTeam()
                    })

                    return {
                        ...Controller.data(),
                        state,
                    }
                }
            }
            createApp('team', team);
        },
    };
    return Controller;
});
