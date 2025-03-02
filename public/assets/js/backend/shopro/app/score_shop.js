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
                                keyword: { field: 'title', value: '' },
                            },
                            tools: {
                                keyword: {
                                    type: 'tinputprepend',
                                    label: '查询内容',
                                    placeholder: '请输入查询内容',
                                    value: {
                                        field: 'title',
                                        value: '',
                                    },
                                    options: {
                                        data: [{
                                            label: '商品名称',
                                            value: 'title',
                                        },
                                        {
                                            label: '商品副标题',
                                            value: 'subtitle',
                                        }],
                                    },
                                },
                            },
                            condition: {}
                        }
                    })

                    function getData() {
                        let tempSearch = JSON.parse(JSON.stringify(state.filter.data));
                        let search = composeFilter(tempSearch, {
                            title: 'like',
                            subtitle: 'like',
                        });
                        Fast.api.ajax({
                            url: 'shopro/app/score_shop',
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

                    let expandRowKeys = reactive([]);
                    function expandRow(id) {
                        if (expandRowKeys.includes(id)) {
                            expandRowKeys.length = 0;
                        } else {
                            expandRowKeys.length = 0;
                            expandRowKeys.push(id);
                            getSkuPrices(id);
                        }
                    }

                    const skuPrices = reactive({
                        data: [],
                    });
                    function getSkuPrices(goods_id) {
                        Fast.api.ajax({
                            url: `shopro/app/score_shop/skuPrices/goods_id/${goods_id}`,
                            type: 'GET',
                        }, function (ret, res) {
                            skuPrices.data = res.data;
                            return false
                        }, function (ret, res) { })
                    }

                    function onAdd() {
                        Fast.api.open(`shopro/goods/goods/select?data_type=score_shop`, "选择商品", {
                            callback(data) {
                                Fast.api.open(`shopro/app/score_shop/add?type=add&goods_id=${data.id}&title=${data.title}`, "添加", {
                                    callback() {
                                        getData()
                                    }
                                })
                            }
                        })
                    }
                    function onEdit(item) {
                        Fast.api.open(`shopro/app/score_shop/edit?type=edit&goods_id=${item.id}&title=${item.title}`, "编辑", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onDelete(id) {
                        Fast.api.ajax({
                            url: `shopro/app/score_shop/delete/goods_id/${id}`,
                            type: 'DELETE',
                        }, function (ret, res) {
                            getData()
                        }, function (ret, res) { })
                    }
                    function onRecyclebin() {
                        Fast.api.open(`shopro/app/score_shop/recyclebin`, "回收站", {
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
                        expandRowKeys,
                        expandRow,
                        skuPrices,
                        getSkuPrices,
                        onAdd,
                        onEdit,
                        onDelete,
                        onRecyclebin,
                    }
                }
            }
            createApp('index', index);
        },
        add: () => {
            Controller.form();
        },
        edit: () => {
            Controller.form();
        },
        form: () => {
            const { reactive, onMounted } = Vue
            const addEdit = {
                setup() {
                    const state = reactive({
                        type: new URLSearchParams(location.search).get('type'),
                        goods_id: new URLSearchParams(location.search).get('goods_id'),
                        title: new URLSearchParams(location.search).get('title'),
                    })

                    const goods = reactive({
                        skus: [],
                        sku_prices: [],
                        score_sku_prices: [],
                    })

                    function getSkus() {
                        Fast.api.ajax({
                            url: `shopro/app/score_shop/skus/goods_id/${state.goods_id}`,
                            type: 'GET',
                        }, function (ret, res) {
                            goods.skus = res.data.skus;
                            goods.sku_prices = res.data.sku_prices;
                            goods.score_sku_prices = res.data.score_sku_prices;
                            return false
                        }, function (ret, res) { })
                    }

                    function onConfirm() {
                        let submitForm = {
                            sku_prices: goods.score_sku_prices,
                        }
                        if (state.type == 'add') {
                            submitForm = {
                                goods_id: state.goods_id,
                                ...submitForm,
                            }
                        }
                        Fast.api.ajax({
                            url: state.type == 'add' ? 'shopro/app/score_shop/add' : `shopro/app/score_shop/edit/goods_id/${state.goods_id}`,
                            type: 'POST',
                            data: submitForm,
                        }, function (ret, res) {
                            Fast.api.close()
                        }, function (ret, res) { })
                    }

                    onMounted(() => {
                        getSkus()
                    })

                    return {
                        state,
                        goods,
                        onConfirm
                    }
                }
            }
            createApp('addEdit', addEdit);
        },
        select: () => {
            const { reactive, onMounted } = Vue
            const select = {
                setup() {
                    const state = reactive({
                        multiple: new URLSearchParams(location.search).get('multiple') || false,
                        data: [],
                        select: [],
                    })

                    function getData() {
                        Fast.api.ajax({
                            url: 'shopro/app/score_shop/select',
                            type: 'GET',
                            data: {
                                page: pagination.page,
                                list_rows: pagination.list_rows,
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

                    function onChangeSelection(val) {
                        state.select = val
                    }

                    function onSelect(item) {
                        Fast.api.close(item)
                    }

                    function onConfirm() {
                        Fast.api.close(state.select)
                    }

                    onMounted(() => {
                        getData()
                    })

                    return {
                        state,
                        getData,
                        pagination,
                        onChangeSelection,
                        onSelect,
                        onConfirm
                    }
                }
            }
            createApp('select', select);
        },
        recyclebin: () => {
            const { reactive, onMounted } = Vue
            const { ElMessageBox } = ElementPlus
            const recyclebin = {
                setup() {
                    const state = reactive({
                        data: [],
                        order: '',
                        sort: '',
                    })

                    function getData() {
                        Fast.api.ajax({
                            url: 'shopro/app/score_shop/recyclebin',
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
                            case 'restore':
                                onRestore(ids.join(','))
                                break;
                            case 'destroy':
                                ElMessageBox.confirm('此操作将销毁, 是否继续?', '提示', {
                                    confirmButtonText: '确定',
                                    cancelButtonText: '取消',
                                    type: 'warning',
                                }).then(() => {
                                    onDestroy(ids.join(','))
                                });
                                break;
                            case 'all':
                                ElMessageBox.confirm('此操作将清空回收站, 是否继续?', '提示', {
                                    confirmButtonText: '确定',
                                    cancelButtonText: '取消',
                                    type: 'warning',
                                }).then(() => {
                                    onDestroy('all')
                                });
                                break;
                        }
                    }

                    function onRestore(id) {
                        Fast.api.ajax({
                            url: `shopro/app/score_shop/restore/id/${id}`,
                            type: 'POST',
                        }, function (ret, res) {
                            getData()
                        }, function (ret, res) { })
                    }
                    function onDestroy(id) {
                        Fast.api.ajax({
                            url: `shopro/app/score_shop/destroy/id/${id}`,
                            type: 'POST',
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
                        pagination,
                        batchHandle,
                        onChangeSelection,
                        onBatchHandle,
                        onRestore,
                        onDestroy,
                    }
                }
            }
            createApp('recyclebin', recyclebin);
        },
    };
    return Controller;
});
