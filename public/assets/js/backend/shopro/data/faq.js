define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: () => {
            const { reactive, onMounted } = Vue
            const { ElMessageBox } = ElementPlus
            const index = {
                setup() {
                    const state = reactive({
                        data: [],
                        order: '',
                        sort: '',
                        filter: {
                            drawer: false,
                            data: {
                                keyword: '',
                            },
                            tools: {
                                keyword: {
                                    type: 'tinput',
                                    label: '查询内容',
                                    placeholder: '请输入查询内容',
                                    value: '',
                                },
                            },
                            condition: {},
                        }
                    })

                    function getData() {
                        let tempSearch = JSON.parse(JSON.stringify(state.filter.data));
                        let search = composeFilter(tempSearch, {
                            keyword: 'like',
                        });
                        Fast.api.ajax({
                            url: 'shopro/data/faq',
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
                                Fast.api.ajax({
                                    url: `shopro/data/faq/edit/id/${ids.join(',')}`,
                                    type: 'POST',
                                    data: {
                                        status: type
                                    }
                                }, function (ret, res) {
                                    getData()
                                }, function (ret, res) { })
                                break;
                        }
                    }

                    function onAdd() {
                        Fast.api.open("shopro/data/faq/add?type=add", "添加", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onEdit(id) {
                        Fast.api.open(`shopro/data/faq/edit?type=edit&id=${id}`, "编辑", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onDelete(id) {
                        Fast.api.ajax({
                            url: `shopro/data/faq/delete/id/${id}`,
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
                        batchHandle,
                        onChangeSelection,
                        onBatchHandle,
                        onAdd,
                        onEdit,
                        onDelete
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
                        id: new URLSearchParams(location.search).get('id')
                    })

                    const form = reactive({
                        model: {
                            title: '',
                            content: '',
                            status: 'normal',
                        },
                        rules: {
                            title: [{ required: true, message: '请输入标题', trigger: 'blur' }],
                            content: [{ required: true, message: '请输入内容', trigger: 'blur' }],
                        },
                    })

                    function getDetail() {
                        Fast.api.ajax({
                            url: `shopro/data/faq/detail/id/${state.id}`,
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
                                    url: state.type == 'add' ? 'shopro/data/faq/add' : `shopro/data/faq/edit/id/${state.id}`,
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
                        onConfirm
                    }
                }
            }
            createApp('addEdit', addEdit);
        }
    };
    return Controller;
});
