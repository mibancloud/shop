define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        data: () => {
            return {
                platformList: [
                    {
                        type: 'WechatMiniProgram',
                        label: '微信小程序',
                        color: '#6F74E9',
                    },
                    {
                        type: 'WechatOfficialAccount',
                        label: '微信公众号',
                        color: '#07C160',
                    },
                    {
                        type: 'H5',
                        label: 'H5',
                        color: '#FC800E',
                    },
                    {
                        type: 'App',
                        label: 'APP',
                        color: '#806AF6',
                    },
                ]
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
                                type: 'template',
                            },
                            tools: {},
                        }
                    })

                    const type = reactive({
                        data: {
                            type: [
                                { name: '模板列表', type: 'template' },
                                { name: '自定义页面', type: 'diypage' }
                            ]
                        }
                    })

                    function getData() {
                        let tempSearch = JSON.parse(JSON.stringify(state.filter.data));
                        let search = composeFilter(tempSearch);
                        Fast.api.ajax({
                            url: 'shopro/decorate/template',
                            type: 'GET',
                            data: {
                                ...search,
                            },
                        }, function (ret, res) {
                            state.data = res.data
                            return false
                        }, function (ret, res) { })
                    }

                    function onAdd() {
                        Fast.api.open(`shopro/decorate/template/add?type=add&template=${state.filter.data.type}`, "添加", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onEdit(id) {
                        Fast.api.open(`shopro/decorate/template/edit?type=edit&id=${id}&template=${state.filter.data.type}`, "编辑", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onCopy(id) {
                        Fast.api.ajax({
                            url: `shopro/decorate/template/copy/id/${id}`,
                            type: 'POST',
                        }, function (ret, res) {
                            getData()
                        }, function (ret, res) { })
                    }
                    function onDelete(id) {
                        Fast.api.ajax({
                            url: `shopro/decorate/template/delete/id/${id}`,
                            type: 'DELETE',
                        }, function (ret, res) {
                            getData()
                        }, function (ret, res) { })
                    }
                    function onRecyclebin() {
                        Fast.api.open(`shopro/decorate/template/recyclebin`, "回收站", {
                            callback() {
                                getData()
                            }
                        })
                    }

                    function onDecorate(id) {
                        Fast.api.addtabs(`shopro/decorate/page/index?id=${id}&from=${state.filter.data.type}`, '装修模板')
                    }

                    function onChangeStatus(item) {
                        Fast.api.ajax({
                            url: `shopro/decorate/template/status/id/${item.id}`,
                            type: 'POST',
                            data: {
                                status: item.status == 'enable' ? 'disabled' : 'enable',
                            },
                        }, function (ret, res) {
                            getData()
                        }, function (ret, res) { })
                    }

                    onMounted(() => {
                        getData()
                    })

                    return {
                        ...Controller.data(),
                        Fast,
                        state,
                        type,
                        getData,
                        onAdd,
                        onEdit,
                        onCopy,
                        onDelete,
                        onRecyclebin,
                        onDecorate,
                        onChangeStatus,
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
                        template: new URLSearchParams(location.search).get('template'),
                    })

                    const form = reactive({
                        model: {
                            type: state.template,
                            name: '',
                            memo: '',
                            platform: [],
                        },
                        rules: {
                            name: [{ required: true, message: '请输入模板名称', trigger: 'blur' }],
                        },
                    })

                    function getDetail() {
                        Fast.api.ajax({
                            url: `shopro/decorate/template/detail/id/${state.id}`,
                            type: 'GET',
                        }, function (ret, res) {
                            form.model = res.data;
                            return false
                        }, function (ret, res) { })
                    }

                    function onConfirm() {
                        proxy.$refs['formRef'].validate((valid) => {
                            if (valid) {
                                Fast.api.ajax({
                                    url: state.type == 'add' ? 'shopro/decorate/template/add' : `shopro/decorate/template/edit/id/${state.id}`,
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
                        ...Controller.data(),
                        state,
                        form,
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
                        data: [],
                    })

                    function getData() {
                        Fast.api.ajax({
                            url: 'shopro/decorate/template/select',
                            type: 'GET',
                        }, function (ret, res) {
                            state.data = res.data
                            return false
                        }, function (ret, res) { })
                    }

                    function onSelect(item) {
                        Fast.api.close(item)
                    }

                    onMounted(() => {
                        getData()
                    })

                    return {
                        state,
                        onSelect,
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
                            url: 'shopro/decorate/template/recyclebin',
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
                            url: `shopro/decorate/template/restore/id/${id}`,
                            type: 'POST',
                        }, function (ret, res) {
                            getData()
                        }, function (ret, res) { })
                    }
                    function onDestroy(id) {
                        Fast.api.ajax({
                            url: `shopro/decorate/template/destroy/id/${id}`,
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
