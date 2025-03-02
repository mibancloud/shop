define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: () => {
            const { reactive, onMounted } = Vue
            const index = {
                setup() {

                    const state = reactive({
                        activity_id: new URLSearchParams(location.search).get('activity_id'),
                        data: [],
                        order: '',
                        sort: '',
                        filter: {
                            drawer: false,
                            data: {
                                status: 'all',
                                user: { field: 'user_id', value: '' },
                                'goods.title': '',
                            },
                            tools: {
                                user: {
                                    type: 'tinputprepend',
                                    label: '团长信息',
                                    placeholder: '请输入查询内容',
                                    value: {
                                        field: 'user_id',
                                        value: '',
                                    },
                                    options: {
                                        data: [
                                            {
                                                label: '团长ID',
                                                value: 'user_id',
                                            },
                                            {
                                                label: '团长昵称',
                                                value: 'user.nickname',
                                            },
                                            {
                                                label: '团长手机号',
                                                value: 'user.mobile',
                                            },
                                        ],
                                    }
                                },
                                'goods.title': {
                                    type: 'tinput',
                                    label: '商品名称',
                                    value: '',
                                },
                            },
                            condition: {},
                        },
                        statusList: [{
                            name: '全部',
                            type: 'all',
                        },
                        {
                            name: '进行中',
                            type: 'ing',
                        },
                        {
                            name: '已成团',
                            type: 'finish',
                        },
                        {
                            name: '虚拟成团',
                            type: 'finish_fictitious',
                        },
                        {
                            name: '已过期',
                            type: 'invalid ',
                        }],
                        statusClass: {
                            ing: 'warning',
                            finish: 'success',
                            finish_fictitious: 'success',
                            invalid: 'danger',
                        },
                    })

                    function getData() {
                        let tempSearch = JSON.parse(JSON.stringify(state.filter.data));
                        tempSearch.activity_id = state.activity_id
                        let search = composeFilter(tempSearch, {
                            'user.nickname': 'like',
                            'goods.title': 'like',
                        });
                        Fast.api.ajax({
                            url: 'shopro/activity/groupon',
                            type: 'GET',
                            data: {
                                type: state.type,
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

                    function onDetail(id) {
                        Fast.api.open(`shopro/activity/groupon/detail/id/${id}?id=${id}`, "详情", {
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
                        onChangeSort,
                        onOpenFilter,
                        onChangeFilter,
                        onChangeTab,
                        pagination,
                        onDetail,
                    }
                }
            }
            createApp('index', index);
        },
        detail: () => {
            const { reactive, onMounted, getCurrentInstance } = Vue
            const detail = {
                setup() {
                    const { proxy } = getCurrentInstance();

                    const state = reactive({
                        id: new URLSearchParams(location.search).get('id'),
                        data: {},
                        statusClass: {
                            ing: 'warning',
                            finish: 'success',
                            finish_fictitious: 'success',
                            invalid: 'danger',
                        },
                    })

                    function getDetail() {
                        Fast.api.ajax({
                            url: `shopro/activity/groupon/detail/id/${state.id}`,
                            type: 'GET',
                        }, function (ret, res) {
                            state.data = res.data
                            return false
                        }, function (ret, res) { })
                    }

                    function onInvalid() {
                        Fast.api.ajax({
                            url: `shopro/activity/groupon/invalid/id/${state.id}`,
                            type: 'GET',
                        }, function (ret, res) {
                            getDetail()
                        }, function (ret, res) { })
                    }

                    function onAddUser() {
                        Fast.api.ajax({
                            url: 'shopro/data/fake_user/getRandom',
                            type: 'GET',
                        }, function (ret, res) {
                            state.data.groupon_logs.push({
                                avatar: res.data.avatar,
                                nickname: res.data.nickname,
                                is_fictitious: 1,
                                is_temp: true,
                            });
                            return false
                        }, function (ret, res) { })
                    }

                    function onConfirm(item) {
                        Fast.api.ajax({
                            url: `shopro/activity/groupon/addUser/id/${state.id}`,
                            type: 'POST',
                            data: {
                                avatar: item.avatar,
                                nickname: item.nickname,
                            }
                        }, function (ret, res) {
                            getDetail()
                        }, function (ret, res) { })
                    }

                    function onCancel(index) {
                        state.data.groupon_logs.splice(index, 1);
                    }

                    onMounted(() => {
                        getDetail()
                    })

                    return {
                        state,
                        onInvalid,
                        onAddUser,
                        onConfirm,
                        onCancel,
                    }
                }
            }
            createApp('detail', detail);
        },
    };
    return Controller;
});
