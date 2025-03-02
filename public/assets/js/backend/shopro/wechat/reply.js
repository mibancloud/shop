define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: () => {
            const { reactive, onMounted } = Vue
            const index = {
                setup() {
                    const state = reactive({
                        data: [],
                        group: 'keywords',
                    })

                    function getData() {
                        Fast.api.ajax({
                            url: 'shopro/wechat/reply',
                            type: 'GET',
                            data: {
                                group: state.group,
                            },
                        }, function (ret, res) {
                            state.data = res.data
                            return false
                        }, function (ret, res) { })
                    }

                    function onAdd() {
                        Fast.api.open(`shopro/wechat/reply/add?type=add&group=${state.group}`, "添加", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onEdit(id) {
                        Fast.api.open(`shopro/wechat/reply/edit?type=edit&group=${state.group}&id=${id}`, "编辑", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onDelete(id) {
                        Fast.api.ajax({
                            url: `shopro/wechat/reply/delete/id/${id}`,
                            type: 'DELETE',
                        }, function (ret, res) {
                            getData()
                        }, function (ret, res) { })
                    }

                    function onChangeStatus(item) {
                        Fast.api.ajax({
                            url: `shopro/wechat/reply/edit/id/${item.id}`,
                            type: 'POST',
                            data: {
                                status: item.status,
                            },
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
                        onAdd,
                        onEdit,
                        onDelete,
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
            const { reactive, ref, onMounted, getCurrentInstance } = Vue
            const addEdit = {
                setup() {
                    const { proxy } = getCurrentInstance();

                    const state = reactive({
                        type: new URLSearchParams(location.search).get('type'),
                        group: new URLSearchParams(location.search).get('group'),
                        id: new URLSearchParams(location.search).get('id')
                    })

                    const form = reactive({
                        model: {
                            group: state.group,
                            keywords: [],
                            type: 'news',
                            content: '1',
                            status: 'enable',
                        },
                        rules: {
                            keywords: [{ required: true, message: '请输入关键字', trigger: 'blur' }],
                            content: [{ required: true, message: '请选择回复内容', trigger: 'blur' }],
                        },
                    })

                    function getDetail() {
                        Fast.api.ajax({
                            url: `shopro/wechat/reply/detail/id/${state.id}`,
                            type: 'GET',
                        }, function (ret, res) {
                            form.model = res.data;
                            getMaterialSelect()
                            return false
                        }, function (ret, res) { })
                    }

                    const keywords = ref('')
                    function onAddKeywords(val) {
                        if (val.trim()) {
                            if (form.model.keywords.indexOf(val.trim()) == -1) {
                                form.model.keywords.push(val.trim());
                                keywords.value = '';
                            } else {
                                ElMessage({
                                    message: '已存在不可再次添加.',
                                    type: 'warning',
                                });
                            }
                        } else {
                            ElMessage({
                                message: '请输入关键字.',
                                type: 'warning',
                            });
                        }
                    }
                    function onDeleteKeywords(index) {
                        form.model.keywords.splice(index, 1);
                    }

                    function onChangeType() {
                        material.pagination.page = 1
                        getMaterialSelect()
                    }

                    const material = reactive({
                        select: [],
                        pagination: {
                            page: 1,
                            list_rows: 10,
                            total: 0,
                        }
                    })
                    function getMaterialSelect() {
                        Fast.api.ajax({
                            url: 'shopro/wechat/material/select',
                            type: 'GET',
                            data: {
                                type: form.model.type,
                                page: material.pagination.page,
                                list_rows: material.pagination.list_rows,
                            },
                        }, function (ret, res) {
                            material.select = initMaterialData(res.data.data,form.model.type)
                            material.pagination.total = res.data.total
                            return false
                        }, function (ret, res) { })
                    }
                    function initMaterialData(data, type) {
                        let options = [];
                        if (type == 'news') {
                          data.forEach((i) => {
                            i.content.news_item.forEach((e) => {
                              options.push({
                                media_id: i.media_id,
                                title: e.title,
                                thumb_url: e.thumb_url,
                                type,
                              });
                            });
                          });
                        } else if (type == 'image') {
                          data.forEach((i) => {
                            options.push({
                              media_id: i.media_id,
                              title: i.name,
                              thumb_url: i.url,
                              type,
                            });
                          });
                        } else if (type == 'video') {
                          data.forEach((i) => {
                            options.push({
                              media_id: i.media_id,
                              title: i.name,
                              thumb_url: i.cover_url,
                              type,
                            });
                          });
                        } else if (type == 'voice') {
                          data.forEach((i) => {
                            options.push({
                              media_id: i.media_id,
                              title: i.name,
                              thumb_url: '',
                              type,
                            });
                          });
                        } else if (type == 'text') {
                          data.forEach((i) => {
                            options.push({
                              media_id: i.id,
                              title: i.content,
                              thumb_url: i.content,
                              type,
                            });
                          });
                        } else if (type == 'link') {
                          data.forEach((i) => {
                            options.push({
                              media_id: i.id,
                              title: i.content.title,
                              thumb_url: i.content.image,
                              description: i.content.description,
                              type,
                            });
                          });
                        }
                        return options;
                      }

                    function onConfirm() {
                        proxy.$refs['formRef'].validate((valid) => {
                            if (valid) {
                                Fast.api.ajax({
                                    url: state.type == 'add' ? 'shopro/wechat/reply/add' : `shopro/wechat/reply/edit/id/${state.id}`,
                                    type: 'POST',
                                    data: form.model,
                                }, function (ret, res) {
                                    Fast.api.close()
                                }, function (ret, res) { })
                            }
                        });
                    }

                    onMounted(() => {
                        state.type == 'add' && getMaterialSelect()
                        state.type == 'edit' && getDetail()
                    })

                    return {
                        state,
                        form,
                        keywords,
                        onAddKeywords,
                        onDeleteKeywords,
                        onChangeType,
                        material,
                        getMaterialSelect,
                        onConfirm
                    }
                }
            }
            createApp('addEdit', addEdit);
        }
    };
    return Controller;
});
