define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: () => { },
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
                            name: '',
                            type: 'wechat',
                            params: {
                                app_id: '',
                                mode: '0',
                                mch_secret_cert: '',
                                mch_public_cert_path: '',
                                alipay_public_cert_path: '',
                                app_public_cert_path: '',
                                alipay_root_cert_path: '',
                                app_secret_cert: '',
                                service_provider_id: '',
                                mch_id: '',
                                mch_secret_key: '',
                                sub_mch_id: '',
                                sub_mch_secret_key: '',
                                sub_mch_public_cert_path: '',
                                sub_mch_secret_cert: '',
                            },
                            status: 'normal',
                        },
                        rules: {
                            name: [{ required: true, message: '请输入标题', trigger: 'blur' }],
                            params: {
                                mch_id: [{ required: true, message: '请输入商户号', trigger: 'blur' }],
                                mch_secret_key: [{ required: true, message: '请输入商户密钥', trigger: 'blur' }],
                                mch_secret_cert: [{ required: true, message: '请上传商户key证书', trigger: 'blur' }],
                                mch_public_cert_path: [{ required: true, message: '请上传商户证书', trigger: 'blur' }],
                                app_id: [{ required: true, message: '请输入主商户AppId', trigger: 'blur' }],
                                sub_mch_id: [{ required: true, message: '请输入子商户号', trigger: 'blur' }],
                                alipay_public_cert_path: [
                                    { required: true, message: '请上传支付宝公钥证书', trigger: 'blur' },
                                ],
                                app_public_cert_path: [{ required: true, message: '请上传应用公钥证书', trigger: 'blur' }],
                                alipay_root_cert_path: [{ required: true, message: '请上传支付宝根证书', trigger: 'blur' }],
                                app_secret_cert: [{ required: true, message: '请输入私钥', trigger: 'blur' }],
                                service_provider_id: [{ required: true, message: '请输入主商户ID', trigger: 'blur' }],
                            },
                        },
                    })

                    function getDetail() {
                        Fast.api.ajax({
                            url: `shopro/pay_config/detail/id/${state.id}`,
                            type: 'GET',
                        }, function (ret, res) {
                            form.model = res.data;
                            return false
                        }, function (ret, res) { })
                    }

                    function onAjaxUpload(id) {
                        var formData = new FormData();
                        formData.append("file", $('#' + id)[0].files[0]);
                        formData.append('shopro_type', 'simple');
                        $.ajax({
                            type: "POST",
                            url: "ajax/upload",
                            data: formData,
                            cache: false,
                            processData: false,
                            contentType: false,
                            success: function (res) {
                                if (res.code == 1) {
                                    form.model.params[id] = res.data.url
                                } else {
                                    Toastr.error(res.msg);
                                }
                            },
                        })
                    }

                    function onConfirm() {
                        proxy.$refs['formRef'].validate((valid) => {
                            if (valid) {
                                Fast.api.ajax({
                                    url: state.type == 'add' ? 'shopro/pay_config/add' : `shopro/pay_config/edit/id/${state.id}`,
                                    type: 'POST',
                                    data: JSON.parse(JSON.stringify(form.model))
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
                        onAjaxUpload,
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
                            url: 'shopro/pay_config/recyclebin',
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
                            url: `shopro/pay_config/restore/id/${id}`,
                            type: 'POST',
                        }, function (ret, res) {
                            getData()
                        }, function (ret, res) { })
                    }
                    function onDestroy(id) {
                        Fast.api.ajax({
                            url: `shopro/pay_config/destroy/id/${id}`,
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
