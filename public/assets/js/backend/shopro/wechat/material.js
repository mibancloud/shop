define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: () => {
            const { reactive, onMounted } = Vue
            const index = {
                setup() {
                    const state = reactive({
                        data: [],
                        type: 'news'
                    })

                    function getData() {
                        Fast.api.ajax({
                            url: 'shopro/wechat/material',
                            type: 'GET',
                            data: {
                                type: state.type,
                                page: pagination.page,
                                list_rows: pagination.list_rows,
                            },
                        }, function (ret, res) {
                            state.data = res.data.data
                            pagination.total = res.data.total
                            return false
                        }, function (ret, res) { })
                    }

                    function onChangeTab() {
                        state.data = []
                        pagination.page = 1
                        getData()
                    }

                    const pagination = reactive({
                        page: 1,
                        list_rows: 10,
                        total: 0,
                    })

                    function onAdd() {
                        Fast.api.open(`shopro/wechat/material/add?type=add&materialType=${state.type}`, "添加", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onEdit(id) {
                        Fast.api.open(`shopro/wechat/material/edit?type=edit&materialType=${state.type}&id=${id}`, "编辑", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onDelete(id) {
                        Fast.api.ajax({
                            url: `shopro/wechat/material/delete/id/${id}`,
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
                        onChangeTab,
                        pagination,
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
                        materialType: new URLSearchParams(location.search).get('materialType'),
                        id: new URLSearchParams(location.search).get('id')
                    })

                    const form = reactive({
                        model: {
                            type: state.materialType,
                            content: ''
                        },
                        rules: {
                            content: [{ required: true, message: '请输入内容', trigger: 'blur' }],
                            'content.title': [{ required: true, message: '请输入标题', trigger: 'blur' }],
                            'content.url': [{ required: true, message: '请输入链接地址', trigger: 'blur' }],
                            'content.image': [{ required: true, message: '请选择图片', trigger: 'blur' }],
                            'content.description': [{ required: true, message: '请输入描述', trigger: 'blur' }],
                        },
                    })

                    // 初始化数据
                    if (form.model.type == 'text') {
                        form.model.content = ''
                    } else if (form.model.type == 'link') {
                        form.model.content = {
                            title: '',
                            url: '',
                            image: '',
                            description: ''
                        }
                    }

                    function getDetail() {
                        Fast.api.ajax({
                            url: `shopro/wechat/material/detail/id/${state.id}`,
                            type: 'GET',
                        }, function (ret, res) {
                            form.model = res.data;
                            return false
                        }, function (ret, res) { })
                    }

                    const linkPopover = reactive({
                        flag: false,
                        form: {
                            model: {
                                text: '',
                                href: '',
                            },
                            rules: {
                                text: [{ required: true, message: '请输入文本内容', trigger: 'blur' }],
                                href: [{ required: true, message: '请输入链接地址', trigger: 'blur' }],
                            },
                        },
                    });
                    function onConfirmLinkPopover() {
                        proxy.$refs['linkFormRef'].validate((valid) => {
                            if (valid) {
                                form.model.content += `<a href="${linkPopover.form.model.href}" target="_blank">${linkPopover.form.model.text}</a>`;
                                onCancelLinkPopover();
                            }
                        });
                    }
                    function onCancelLinkPopover() {
                        linkPopover.flag = false;
                        linkPopover.form.model = {
                            text: '',
                            href: '',
                        };
                    }

                    function onConfirm() {
                        proxy.$refs['formRef'].validate((valid) => {
                            if (valid) {
                                Fast.api.ajax({
                                    url: state.type == 'add' ? 'shopro/wechat/material/add' : `shopro/wechat/material/edit/id/${state.id}`,
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
                        linkPopover,
                        onCancelLinkPopover,
                        onConfirmLinkPopover,
                        onConfirm
                    }
                }
            }
            createApp('addEdit', addEdit);
        }
    };
    return Controller;
});
