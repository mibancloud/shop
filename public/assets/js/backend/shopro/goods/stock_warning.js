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
                                stock_type: 'all',
                                'goods.title': '',
                            },
                            tools: {
                                'goods.title': {
                                    type: 'tinput',
                                    label: '商品名称',
                                    placeholder: '请输入商品名称',
                                    value: '',
                                },
                            },
                            condition: {},
                        },
                        stockType: {
                            all: { name: '全部' },
                            over: { name: '已售罄', num: 0 },
                            no_enough: { name: '预警中', num: 0 },
                        }
                    })

                    const type = reactive({
                        data: {
                            stock_type: {
                                all: { name: '全部' },
                                over: { name: '已售罄', num: 0 },
                                no_enough: { name: '预警中', num: 0 },
                            }
                        }
                    })

                    function getData() {
                        let tempSearch = JSON.parse(JSON.stringify(state.filter.data));
                        let search = composeFilter(tempSearch, {
                            'goods.title': 'like',
                        });
                        Fast.api.ajax({
                            url: 'shopro/goods/stock_warning',
                            type: 'GET',
                            data: {
                                page: pagination.page,
                                list_rows: pagination.list_rows,
                                order: state.order,
                                sort: state.sort,
                                ...search,
                            },
                        }, function (ret, res) {
                            state.data = res.data.rows.data
                            pagination.total = res.data.rows.total
                            type.data.stock_type.over.num = res.data.over_total;
                            type.data.stock_type.no_enough.num = res.data.warning_total;
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

                    function onAddStock(item) {
                        Fast.api.open(`shopro/goods/stock_warning/addStock?id=${item.id}&stock=${item.stock}`, "补货", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onRecyclebin() {
                        Fast.api.open('shopro/goods/stock_warning/recyclebin', "历史记录", {
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
                        type,
                        getData,
                        onChangeSort,
                        onOpenFilter,
                        onChangeFilter,
                        onChangeTab,
                        pagination,
                        onAddStock,
                        onRecyclebin,
                    }
                }
            }
            createApp('index', index);
        },
        addstock: () => {
            const { reactive, getCurrentInstance } = Vue
            const addStock = {
                setup() {
                    const { proxy } = getCurrentInstance();

                    const state = reactive({
                        id: new URLSearchParams(location.search).get('id'),
                        stock: new URLSearchParams(location.search).get('stock')
                    })

                    const form = reactive({
                        model: {
                            stock: '',
                        },
                        rules: {
                            stock: [{ required: true, message: '请输入补充库存', trigger: 'blur' }],
                        },
                    })

                    function onConfirm() {
                        proxy.$refs['formRef'].validate((valid) => {
                            if (valid) {
                                Fast.api.ajax({
                                    url: `shopro/goods/stock_warning/addStock/id/${state.id}`,
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
            createApp('addStock', addStock);
        },
        recyclebin: () => {
            const { reactive, onMounted } = Vue
            const recyclebin = {
                setup() {
                    const state = reactive({
                        data: [],
                        order: '',
                        sort: '',
                    })

                    function getData() {
                        Fast.api.ajax({
                            url: 'shopro/goods/stock_warning/recyclebin',
                            type: 'GET',
                            data: {
                                page: pagination.page,
                                list_rows: pagination.list_rows,
                                order: state.order,
                                sort: state.sort,
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
                        pagination,
                    }
                }
            }
            createApp('recyclebin', recyclebin);
        },
    };
    return Controller;
});
