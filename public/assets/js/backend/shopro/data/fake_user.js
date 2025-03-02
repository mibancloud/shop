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
                                gender: 'all',
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
                                            label: '用户昵称',
                                            value: 'nickname',
                                        },
                                        {
                                            label: '用户手机号',
                                            value: 'mobile',
                                        },
                                        {
                                            label: '邮箱',
                                            value: 'email',
                                        }],
                                    }
                                },
                                gender: {
                                    type: 'tselect',
                                    label: '用户性别',
                                    value: '',
                                    options: {
                                        data: [{
                                            label: '全部',
                                            value: 'all',
                                        },
                                        {
                                            label: '女',
                                            value: '0',
                                        },
                                        {
                                            label: '男',
                                            value: '1',
                                        }]
                                    },
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
                        });
                        Fast.api.ajax({
                            url: 'shopro/data/fake_user',
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
                        }
                    }

                    function onAdd() {
                        Fast.api.open("shopro/data/fake_user/add?type=add", "添加", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onEdit(id) {
                        Fast.api.open(`shopro/data/fake_user/edit?type=edit&id=${id}`, "编辑", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onDelete(id) {
                        Fast.api.ajax({
                            url: `shopro/data/fake_user/delete/id/${id}`,
                            type: 'DELETE',
                        }, function (ret, res) {
                            getData()
                        }, function (ret, res) { })
                    }

                    function onRandom() {
                        Fast.api.open('shopro/data/fake_user/random', "自动生成", {
                            callback() {
                                getData()
                            }
                        })
                    }

                    onMounted(() => {
                        getData()
                    })

                    return {
                        Fast,
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
                        onDelete,
                        onRandom
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
                            avatar: '',
                            username: '',
                            nickname: '',
                            email: '',
                            mobile: '',
                            password: '',
                            gender: 0,
                        },
                        rules: {
                            avatar: [{ required: true, message: '请选择用户头像', trigger: 'blur' }],
                            username: [{ required: true, message: '请输入用户名', trigger: 'blur' }],
                            nickname: [{ required: true, message: '请输入用户昵称', trigger: 'blur' }],
                            email: [{ required: true, message: '请输入电子邮箱', trigger: 'blur' }],
                            mobile: [{ required: true, message: '请输入手机号', trigger: 'blur' }],
                        },
                    })

                    function getDetail() {
                        Fast.api.ajax({
                            url: `shopro/data/fake_user/detail/id/${state.id}`,
                            type: 'GET',
                        }, function (ret, res) {
                            form.model = res.data;
                            form.model.password = '';
                            return false
                        }, function (ret, res) { })
                    }

                    function onConfirm() {
                        proxy.$refs['formRef'].validate((valid) => {
                            if (valid) {
                                Fast.api.ajax({
                                    url: state.type == 'add' ? 'shopro/data/fake_user/add' : `shopro/data/fake_user/edit/id/${state.id}`,
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
        },
        select: function () {
            const { reactive, onMounted } = Vue
            const select = {
                setup() {
                    const state = reactive({
                        data: [],
                    })

                    function getData() {
                        Fast.api.ajax({
                            url: 'shopro/data/fake_user/select',
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

                    function onSelect(item) {
                        Fast.api.close(item)
                    }

                    onMounted(() => {
                        getData()
                    })

                    return {
                        state,
                        getData,
                        pagination,
                        onSelect
                    }
                }
            }
            createApp('select', select);
        },
        random: () => {
            const { reactive, getCurrentInstance } = Vue
            const random = {
                setup() {
                    const { proxy } = getCurrentInstance();

                    const form = reactive({
                        model: {
                            num: 1,
                        },
                        rules: {
                            num: [{ required: true, message: '请输入生成虚拟人数', trigger: 'blur' }],
                        },
                    })

                    function onConfirm() {
                        proxy.$refs['formRef'].validate((valid) => {
                            if (valid) {
                                Fast.api.ajax({
                                    url: 'shopro/data/fake_user/random',
                                    type: 'POST',
                                    data: JSON.parse(JSON.stringify(form.model))
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
            createApp('random', random);
        },
    };
    return Controller;
});
