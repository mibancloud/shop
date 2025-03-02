define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: () => {
            const { reactive, onMounted } = Vue
            const index = {
                setup() {
                    const state = reactive({
                        dispatch_type: 'express',
                        data: [],
                    })

                    function getData() {
                        Fast.api.ajax({
                            url: 'shopro/dispatch/dispatch',
                            type: 'GET',
                            data: {
                                page: pagination.page,
                                list_rows: pagination.list_rows,
                                type: state.dispatch_type
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

                    function onChangeTab() {
                        pagination.page = 1
                        getData()
                    }

                    function onAdd() {
                        Fast.api.open(`shopro/dispatch/dispatch/add?type=add&dispatch_type=${state.dispatch_type}`, "添加", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onEdit(id) {
                        Fast.api.open(`shopro/dispatch/dispatch/edit?type=edit&id=${id}&dispatch_type=${state.dispatch_type}`, "编辑", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onCopy(id) {
                        Fast.api.open(`shopro/dispatch/dispatch/add?type=copy&id=${id}&dispatch_type=${state.dispatch_type}`, "复制", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onDelete(id) {
                        Fast.api.ajax({
                            url: `shopro/dispatch/dispatch/delete/id/${id}?type=${state.dispatch_type}`,
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
                        pagination,
                        onChangeTab,
                        onAdd,
                        onEdit,
                        onCopy,
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
                        id: new URLSearchParams(location.search).get('id'),
                        dispatch_type: new URLSearchParams(location.search).get('dispatch_type'),
                        priceType: 'number',
                    })

                    const form = reactive({
                        model: {
                            // dispatch_type: state.dispatch_type,
                            name: '',
                            type: state.dispatch_type,
                            express: [],
                            autosend: {
                                type: "text",
                                content: ""
                            }
                        },
                        rules: {
                            name: [{ required: true, message: '请输入模板名称', trigger: 'blur' }],
                            express: {
                                first_num: [{ required: true, message: '请输入数量', trigger: 'blur' }],
                                first_price: [{ required: true, message: '请输入运费', trigger: 'blur' }],
                                additional_num: [{ required: true, message: '请输入数量', trigger: 'blur' }],
                                additional_price: [{ required: true, message: '请输入续费', trigger: 'blur' }],
                                district_text: [{ required: true, message: '请选择可配送区域', trigger: 'blur' }],
                            }
                        },
                    })

                    function getDetail() {
                        Fast.api.ajax({
                            url: `shopro/dispatch/dispatch/detail/id/${state.id}`,
                            type: 'GET',
                            data: {
                                type: state.dispatch_type,
                            }
                        }, function (ret, res) {
                            form.model = res.data;
                            if (state.dispatch_type == 'express') {
                                state.priceType = form.model.express.length > 0 ? form.model.express[0].type : 'number';
                            }
                            return false
                        }, function (ret, res) { })
                    }

                    function onAddTemplate() {
                        form.model.express.push({
                            type: state.priceType,
                            first_num: 0,
                            first_price: 0,
                            additional_num: 0,
                            additional_price: 0,
                            province_ids: '',
                            city_ids: '',
                            district_ids: '',
                        });
                    }
                    function onDeleteTemplate(index) {
                        form.model.express.splice(index, 1);
                    }

                    function onSelectArea(index) {
                        let selected = {
                            province: form.model.express[index].province_ids,
                            city: form.model.express[index].city_ids,
                            district: form.model.express[index].district_ids,
                        }
                        Fast.api.open(`shopro/data/area/select?selected=${encodeURI(JSON.stringify(selected))}`, "选择地区", {
                            callback(data) {
                                let text = [];
                                for (var key in data) {
                                    let ids = [];
                                    for (var id in data[key]) {
                                        ids.push(id);
                                        text.push(data[key][id]);
                                    }
                                    form.model.express[index][key + '_ids'] = ids.join(',');
                                }
                                form.model.express[index].district_text = text.join(',');
                            }
                        })
                    }

                    function onChangeAutosendType(type) {
                        form.model.autosend.content = type == 'text' ? '' : []
                    }
                    function onAddContent() {
                        console.log(123, form.model.autosend.content)
                        if(!form.model.autosend.content){
                            form.model.autosend.content=[]
                        }
                        form.model.autosend.content.push({
                            title: '',
                            content: '',
                        });
                    }
                    function onDeleteContent(index) {
                        form.model.autosend.content.splice(index, 1);
                    }

                    function onConfirm() {
                        proxy.$refs['formRef'].validate((valid) => {
                            if (valid) {
                                let submitForm = JSON.parse(JSON.stringify(form.model));

                                if (state.dispatch_type == 'express') {
                                    submitForm.express.forEach((item) => {
                                        item.type = state.priceType;
                                    });

                                    if (state.type == 'copy') {
                                        delete submitForm.id;
                                        submitForm.express.forEach((item) => {
                                            delete item.id;
                                        });
                                    }
                                }else if(state.dispatch_type == 'autosend'){
                                    if (state.type == 'copy') {
                                      delete submitForm.id;
                                    }
                                  }
                                Fast.api.ajax({
                                    url: state.type == 'add' || state.type == 'copy' ? 'shopro/dispatch/dispatch/add' : `shopro/dispatch/dispatch/edit/id/${state.id}`,
                                    type: 'POST',
                                    data: submitForm,
                                }, function (ret, res) {
                                    Fast.api.close()
                                }, function (ret, res) { })
                            }
                        });
                    }

                    onMounted(() => {
                        (state.type == 'edit' || state.type == 'copy') && getDetail()
                    })

                    return {
                        state,
                        form,
                        onAddTemplate,
                        onDeleteTemplate,
                        onSelectArea,
                        onChangeAutosendType,
                        onAddContent,
                        onDeleteContent,
                        onConfirm
                    }
                }
            }
            createApp('addEdit', addEdit);
        }
    };
    return Controller;
});
