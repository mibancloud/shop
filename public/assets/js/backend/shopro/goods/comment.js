define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: () => {
            const { reactive, onMounted } = Vue
            const { ElMessageBox } = ElementPlus
            const index = {
                setup() {
                    const state = reactive({
                        order_id: new URLSearchParams(location.search).get('order_id'),
                        order_item_id: new URLSearchParams(location.search).get('order_item_id'),
                        data: [],
                        order: '',
                        sort: '',
                        filter: {
                            drawer: false,
                            data: {
                                'goods.title': '',
                                user: { field: 'user.nickname', value: '' },
                                content: '',
                                status: '',
                            },
                            tools: {
                                'goods.title': {
                                    type: 'tinput',
                                    label: '商品名称',
                                    value: '',
                                },
                                user: {
                                    type: 'tinputprepend',
                                    label: '用户信息',
                                    placeholder: '请输入查询内容',
                                    value: {
                                        field: 'user.nickname',
                                        value: '',
                                    },
                                    options: {
                                        data: [
                                            {
                                                label: '用户昵称',
                                                value: 'user.nickname',
                                            },
                                            {
                                                label: '用户手机号',
                                                value: 'user.mobile',
                                            },
                                        ]
                                    }
                                },
                                content: {
                                    type: 'tinput',
                                    label: '评价内容',
                                    value: '',
                                },
                                status: {
                                    type: 'tselect',
                                    label: '状态',
                                    value: '',
                                    options: {
                                        data: [
                                            {
                                                label: '显示',
                                                value: 'normal',
                                            },
                                            {
                                                label: '隐藏',
                                                value: 'hidden',
                                            },
                                        ],
                                    },
                                },
                            },
                            condition: {},
                        }
                    })

                    function getData() {
                        let tempSearch = JSON.parse(JSON.stringify(state.filter.data));

                        if (state.order_id) {
                            tempSearch = { ...tempSearch, order_id: state.order_id }
                        }
                        if (state.order_item_id) {
                            tempSearch = { ...tempSearch, order_item_id: state.order_item_id }
                        }

                        let search = composeFilter(tempSearch, {
                            'goods.title': 'like',
                            'user.nickname': 'like',
                            'user.mobile': 'like',
                            content: 'like',
                        });
                        Fast.api.ajax({
                            url: 'shopro/goods/comment',
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
                            case 'delete':
                                ElMessageBox.confirm('此操作将删除, 是否继续?', '提示', {
                                    confirmButtonText: '确定',
                                    cancelButtonText: '取消',
                                    type: 'warning',
                                }).then(() => {
                                    onDelete(ids.join(','))
                                });
                                break;
                            default:
                                onCommand({ id: ids.join(','), type: type })
                                break;
                        }
                    }
                    function onCommand(item) {
                        Fast.api.ajax({
                            url: `shopro/goods/comment/edit/id/${item.id}`,
                            type: 'POST',
                            data: {
                                status: item.type
                            }
                        }, function (ret, res) {
                            getData()
                        }, function (ret, res) { })
                    }

                    function onOpenGoodsDetail(id) {
                        Fast.api.open(`shopro/goods/goods/add?type=edit&id=${id}`, "商品详情", {
                            callback() {
                                getData()
                            }
                        })
                    }

                    function onAdd() {
                        Fast.api.open("shopro/goods/comment/add?type=add", "添加虚拟评价", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onEdit(id) {
                        Fast.api.open(`shopro/goods/comment/edit?type=edit&id=${id}`, "编辑", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onDelete(id) {
                        Fast.api.ajax({
                            url: `shopro/goods/comment/delete/id/${id}`,
                            type: 'DELETE',
                        }, function (ret, res) {
                            getData()
                        }, function (ret, res) { })
                    }

                    function onRecyclebin() {
                        Fast.api.open('shopro/goods/comment/recyclebin', "回收站", {
                            callback() {
                                getData()
                            }
                        })
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
                        batchHandle,
                        onChangeSelection,
                        onBatchHandle,
                        onCommand,
                        onOpenGoodsDetail,
                        onAdd,
                        onEdit,
                        onDelete,
                        onRecyclebin
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
            const { reactive, onMounted, getCurrentInstance } = Vue
            const addEdit = {
                setup() {
                    const { proxy } = getCurrentInstance();

                    const state = reactive({
                        type: new URLSearchParams(location.search).get('type'),
                        id: new URLSearchParams(location.search).get('id'),
                        fakeUserData: {},
                        goodsData: {}
                    })

                    const form = reactive({
                        model: {
                            content: '',
                            images: [],
                            level: 0,
                            user_id: '',
                            goods_id: '',
                            status: 'normal',
                        },
                        rules: {
                            content: [{ required: true, message: '请输入评价内容', trigger: 'blur' }],
                            level: [{ required: true, message: '请选择评价星级', trigger: 'blur' }],
                            user_id: [{ required: true, message: '请选择评价用户', trigger: 'blur' }],
                            goods_id: [{ required: true, message: '请选择商品信息', trigger: 'blur' }],
                            status: [{ required: true, message: '请选择状态', trigger: 'blur' }],
                        },
                    })

                    function getDetail() {
                        Fast.api.ajax({
                            url: `shopro/goods/comment/detail/id/${state.id}`,
                            type: 'GET',
                        }, function (ret, res) {
                            form.model = res.data
                            return false
                        }, function (ret, res) { })
                    }

                    function onSelectFakeUser() {
                        Fast.api.open("shopro/data/fake_user/select", "选择虚拟用户", {
                            callback(data) {
                                state.fakeUserData = data
                                form.model.user_id = data.id
                            }
                        })
                    }
                    function onDeleteFakeUser() {
                        state.fakeUserData = {}
                        form.model.user_id = ''
                    }

                    function onSelectGoods() {
                        Fast.api.open(`shopro/goods/goods/select`, "选择商品", {
                            callback(data) {
                                state.goodsData = data;
                                form.model.goods_id = data.id;
                            }
                        })
                    }
                    function onDeleteGoods() {
                        state.goodsData = {};
                        form.model.goods_id = '';
                    }

                    function onChangeStatus() {
                        Fast.api.ajax({
                            url: `shopro/goods/comment/edit/id/${state.id}`,
                            type: 'POST',
                            data: {
                                status: form.model.status
                            }
                        }, function (ret, res) {
                            getDetail()
                        }, function (ret, res) { })
                    }

                    function onReply() {
                        Fast.api.open(`shopro/goods/comment/reply/id/${state.id}?id=${state.id}`, "回复", {
                            callback() {
                                getDetail()
                            }
                        })
                    }

                    function onConfirm() {
                        proxy.$refs['formRef'].validate((valid) => {
                            if (valid) {
                                Fast.api.ajax({
                                    url: 'shopro/goods/comment/add',
                                    type: 'POST',
                                    data: form.model,
                                }, function (ret, res) {
                                    Fast.api.close()
                                }, function (ret, res) { })
                            }
                        });
                    }

                    onMounted(() => {
                        state.type == 'edit' && getDetail()
                    })

                    return {
                        state,
                        form,
                        onSelectFakeUser,
                        onDeleteFakeUser,
                        onSelectGoods,
                        onDeleteGoods,
                        onChangeStatus,
                        onReply,
                        onConfirm
                    }
                }
            }
            createApp('addEdit', addEdit);
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
                            url: 'shopro/goods/comment/recyclebin',
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
                        tools: [
                            {
                                type: 'restore',
                                label: '还原',
                                class: 'primary',
                            },
                            {
                                type: 'destroy',
                                label: '销毁',
                                class: 'danger',
                            },
                            {
                                type: 'all',
                                label: '清空回收站',
                                class: 'danger',
                            },
                        ]
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
                            url: `shopro/goods/comment/restore/id/${id}`,
                            type: 'POST',
                        }, function (ret, res) {
                            getData()
                        }, function (ret, res) { })
                    }
                    function onDestroy(id) {
                        Fast.api.ajax({
                            url: `shopro/goods/comment/destroy/id/${id}`,
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
        reply: () => {
            const { reactive, getCurrentInstance } = Vue
            const reply = {
                setup() {
                    const { proxy } = getCurrentInstance();

                    const state = reactive({
                        id: new URLSearchParams(location.search).get('id'),
                    })

                    const form = reactive({
                        model: {
                            content: '',
                        },
                        rules: {
                            content: [{ required: true, message: '请输入回复内容', trigger: 'change' }],
                        },
                    })

                    function onConfirm() {
                        proxy.$refs['formRef'].validate((valid) => {
                            if (valid) {
                                Fast.api.ajax({
                                    url: `shopro/goods/comment/reply/id/${state.id}`,
                                    type: 'POST',
                                    data: form.model,
                                }, function (ret, res) {
                                    Fast.api.close()
                                }, function (ret, res) { })
                            }
                        });
                    }

                    return {
                        form,
                        onConfirm
                    }
                }
            }
            createApp('reply', reply);
        },
    };
    return Controller;
});
