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
                                user: { field: 'id', value: '' },
                                room_id: '',
                            },
                            tools: {
                                user: {
                                    type: 'tinputprepend',
                                    label: '查询内容',
                                    placeholder: '请输入查询内容',
                                    value: {
                                        field: 'id',
                                        value: '',
                                    },
                                    options: {
                                        data: [{
                                            label: 'ID',
                                            value: 'id',
                                        },
                                        {
                                            label: '名称',
                                            value: 'name',
                                        }],
                                    }
                                },
                                room_id: {
                                    type: 'tselect',
                                    label: '客服分类',
                                    value: '',
                                    options: {
                                        data: [],
                                        props: {
                                            label: 'name'
                                        }
                                    },
                                },
                            },
                            condition: {},
                        }
                    })

                    function getChatConfig() {
                        Fast.api.ajax({
                            url: `shopro/chat/index/init`,
                            type: 'GET',
                        }, function (ret, res) {
                            state.filter.tools.room_id.options.data = res.data.default_rooms
                            return false
                        }, function (ret, res) { })
                    }

                    function getData() {
                        let tempSearch = JSON.parse(JSON.stringify(state.filter.data));
                        let search = composeFilter(tempSearch, {
                            name: 'like',
                        });
                        Fast.api.ajax({
                            url: 'shopro/chat/common_word',
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
                                    url: `shopro/chat/common_word/edit/id/${ids.join(',')}`,
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
                        Fast.api.open("shopro/chat/common_word/add?type=add", "添加", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onEdit(id) {
                        Fast.api.open(`shopro/chat/common_word/edit?type=edit&id=${id}`, "编辑", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onDelete(id) {
                        Fast.api.ajax({
                            url: `shopro/chat/common_word/delete/id/${id}`,
                            type: 'DELETE',
                        }, function (ret, res) {
                            getData()
                        }, function (ret, res) { })
                    }

                    onMounted(() => {
                        getChatConfig()
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
            const { reactive, onMounted, getCurrentInstance, nextTick } = Vue
            const addEdit = {
                setup() {
                    const { proxy } = getCurrentInstance();

                    const state = reactive({
                        type: new URLSearchParams(location.search).get('type'),
                        id: new URLSearchParams(location.search).get('id')
                    })

                    const form = reactive({
                        model: {
                            room_id: '',
                            name: '',
                            content: '',
                            status: 'normal',
                            weigh: 0,
                        },
                        rules: {
                            room_id: [{ required: true, message: '请选择客服分类', trigger: ['blur', 'change'] }],
                            name: [{ required: true, message: '请输入标题', trigger: ['blur', 'change'] }],
                            content: [{ required: true, message: '请输入问题内容', trigger: ['blur', 'change'] }],
                        },
                    })

                    function getDetail() {
                        Fast.api.ajax({
                            url: `shopro/chat/common_word/detail/id/${state.id}`,
                            type: 'GET',
                        }, function (ret, res) {
                            form.model = res.data;
                            nextTick(() => {
                                Controller.api.bindevent();
                                $('#commonWordContent').html(form.model.content)
                            })
                            return false
                        }, function (ret, res) { })
                    }

                    const chat = reactive({
                        config: {}
                    })
                    function getChatConfig() {
                        Fast.api.ajax({
                            url: `shopro/chat/index/init`,
                            type: 'GET',
                        }, function (ret, res) {
                            chat.config = res.data
                            return false
                        }, function (ret, res) { })
                    }

                    function onConfirm() {
                        form.model.content = $("#commonWordContent").val();
                        proxy.$refs['formRef'].validate((valid) => {
                            if (valid) {
                                Fast.api.ajax({
                                    url: state.type == 'add' ? 'shopro/chat/common_word/add' : `shopro/chat/common_word/edit/id/${state.id}`,
                                    type: 'POST',
                                    data: form.model,
                                }, function (ret, res) {
                                    Fast.api.close()
                                }, function (ret, res) { })
                            }
                        });
                    }

                    onMounted(() => {
                        getChatConfig()
                        if (state.type == 'add') {
                            nextTick(() => {
                                Controller.api.bindevent();
                            })
                        } else if (state.type == 'edit') {
                            getDetail()
                        }
                    })

                    return {
                        state,
                        form,
                        chat,
                        onConfirm
                    }
                }
            }
            createApp('addEdit', addEdit);
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
        },
    };
    return Controller;
});
