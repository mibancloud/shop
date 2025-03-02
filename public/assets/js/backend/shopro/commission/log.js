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
                                event: '',
                            },
                            tools: {
                                event: {
                                    type: 'tselect',
                                    label: '动态类型',
                                    value: '',
                                    options: {
                                        data: [{
                                            label: '分销商',
                                            value: 'agent',
                                        },
                                        {
                                            label: '佣金',
                                            value: 'reward',
                                        },
                                        {
                                            label: '推荐',
                                            value: 'share',
                                        },
                                        {
                                            label: '绑定',
                                            value: 'bind',
                                        }],
                                    },
                                },
                            },
                            condition: {},
                        },
                    })

                    function getData() {
                        let tempSearch = JSON.parse(JSON.stringify(state.filter.data));
                        let search = composeFilter(tempSearch);
                        Fast.api.ajax({
                            url: 'shopro/commission/log',
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

                    const really = reactive({
                        reallyStatus: 0,
                        reallyTimer: '',
                    })
                    function onChangeReallyStatus(val) {
                        clearInterval(really.reallyTimer);
                        if (val == 1) {
                            really.reallyTimer = setInterval(() => {
                                getData();
                            }, 3000);
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
                        really,
                        onChangeReallyStatus
                    }
                }
            }
            createApp('index', index);
        },
    };
    return Controller;
});
