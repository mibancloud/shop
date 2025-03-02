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
                                name: '',
                            },
                            tools: {
                                name: {
                                    type: 'tinput',
                                    label: '分类名称',
                                    placeholder: '请输入分类名称',
                                    value: '',
                                },
                            },
                            condition: {},
                        }
                    })

                    function getData() {
                        let tempSearch = JSON.parse(JSON.stringify(state.filter.data));
                        let search = composeFilter(tempSearch, {
                            name: 'like',
                        });
                        Fast.api.ajax({
                            url: 'shopro/category',
                            type: 'GET',
                            data: {
                                ...search,
                            },
                        }, function (ret, res) {
                            state.data = res.data
                            return false

                        }, function (ret, res) { })
                    }

                    function onOpenFilter() {
                        state.filter.drawer = true
                    }
                    function onChangeFilter() {
                        getData()
                        state.filter.drawer && (state.filter.drawer = false)
                    }

                    function onAdd() {
                        Fast.api.open("shopro/category/add?type=add", "添加", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onEdit(id) {
                        Fast.api.open(`shopro/category/edit?type=edit&id=${id}`, "编辑", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onDelete(id) {
                        Fast.api.ajax({
                            url: `shopro/category/delete/id/${id}`,
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
                        onOpenFilter,
                        onChangeFilter,
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
            const { ref,reactive, onMounted, getCurrentInstance } = Vue
            const addEdit = {
                setup() {
                    const { proxy } = getCurrentInstance();
                    const id = ref(0)
                    const state = reactive({
                        type: new URLSearchParams(location.search).get('type'),
                        id: new URLSearchParams(location.search).get('id'),
                        styleList: {
                            1: [{ type: 'first_one', name: '一' },
                            { type: 'first_two', name: '二' }],
                            2: [{ type: 'second_one', name: '一' }],
                            3: [{ type: 'third_one', name: '一' }],
                        },
                        level: 1,
                        multIndex: 1,
                        treeData: [],
                    })

                    const form = reactive({
                        model: {
                            style: 'first_one',
                            name: '',
                            description: '',
                            weigh: 0,
                            status: 'normal',
                        },
                        rules: {
                            name: [{ required: true, message: '请输入分类名称', trigger: 'blur' }],
                        },
                    })

                    function getDetail() {
                        Fast.api.ajax({
                            url: `shopro/category/detail/id/${state.id}`,
                            type: 'GET',
                        }, function (ret, res) {
                            form.model = res.data.category;
                            state.treeData = res.data.categories;
                            if (form.model.style.substring(0, 1) == 'f') {
                                state.level = 1;
                            } else if (form.model.style.substring(0, 1) == 's') {
                                state.level = 2;
                            } else {
                                state.level = 3;
                            }
                            return false
                        }, function (ret, res) { })
                    }

                    function onChangeLevel() {
                        form.model.style = '';
                        loopChildren(state.treeData);
                    }

                    function loopChildren(data, level = 1) {
                        state.multIndex = 1;
                        loopLevel(state.treeData);
                        if (state.multIndex <= state.level) {
                            level += 1;
                            if (data.length == 0) {
                                level -= 1;
                                if (level <= state.level) {
                                    data.push({
                                        id: 'new' + (id.value++),
                                        name: '',
                                        image: '',
                                        description: '',
                                        weigh: 0,
                                        status: 'normal',
                                    });
                                    loopChildren(data, level);
                                }
                            } else {
                                if (level <= state.level) {
                                    data.forEach((k) => {
                                        if (!k.children) {
                                            k.children = [
                                                {
                                                    id: 'new' + (id.value++),
                                                    name: '',
                                                    image: '',
                                                    description: '',
                                                    weigh: 0,
                                                    status: 'normal',
                                                },
                                            ];
                                        }
                                        loopChildren(k.children, level);
                                    });
                                }
                            }
                        } else if (state.multIndex > state.level) {
                            data.forEach((l) => {
                                if (level == state.level) {
                                    delete l.children;
                                } else {
                                    level += 1;
                                    if (l.children && l.children.length > 0) {
                                        loopChildren(l.children, level);
                                    }
                                }
                            });
                        }
                    }
                    function loopLevel(arr) {
                        arr.forEach((a) => {
                            if (a.children) {
                                state.multIndex += 1;
                                loopLevel(a.children);
                            }
                        });
                    }

                    function onAdd() {
                        state.treeData.unshift({
                            id: 'new' + (id.value++),
                            name: '',
                            description: '',
                            image: '',
                            weigh: 0,
                            status: 'normal',
                            children: [],
                        });
                    }
                    function onAppend(data) {
                        const newChild = {
                            id: 'new' + (id.value++),
                            name: '',
                            description: '',
                            image: '',
                            weigh: 0,
                            status: 'normal',
                        };
                        if (!data.children) {
                            data.children = [];
                        }
                        data.children.unshift(newChild);
                    }
                    function onRemove(node, data) {
                        const parent = node.parent;
                        const children = parent.data.children || parent.data;
                        const index = children.findIndex((d) => d.id == data.id);
                        if (children[index].children) {
                            children[index].deleted = 1;
                            children[index].children.forEach((i) => {
                                i.deleted = 1;
                                if (i.children) {
                                    i.children.forEach((j) => {
                                        j.deleted = 1;
                                    });
                                }
                            });
                        } else {
                            children[index].deleted = 1;
                        }
                    }

                    function onConfirm() {
                        const submitForm = JSON.parse(JSON.stringify(form.model))
                        state.treeData.forEach(i => {
                            if (i.id?.toString().substring(0, 3) === 'new') {
                              delete i.id
                            }
                            if (i.children) {
                              i.children.forEach(j => {
                                if (j.id?.toString().substring(0, 3) === 'new') {
                                  delete j.id
                                }
                                if (j.children) {
                                  j.children.forEach(k => {
                                    if (k.id?.toString().substring(0, 3) === 'new') {
                                      delete k.id
                                    }
                                  })
                                }
                              })
                            }
                          })
                        submitForm.categories = JSON.stringify(state.treeData)
                        proxy.$refs['formRef'].validate((valid) => {
                            if (valid) {
                                Fast.api.ajax({
                                    url: state.type == 'add' ? 'shopro/category/add' : `shopro/category/edit/id/${state.id}`,
                                    type: 'POST',
                                    data: submitForm
                                }, function (ret, res) {
                                    Fast.api.close()
                                }, function (ret, res) { })
                            }
                        });
                    }

                    onMounted(() => {
                        if (state.type == 'add') {
                            loopChildren(state.treeData);
                        }
                        if (state.type == 'edit') {
                            getDetail()
                        }
                    })

                    return {
                        state,
                        form,
                        onChangeLevel,
                        onAdd,
                        onAppend,
                        onRemove,
                        onConfirm
                    }
                }
            }
            createApp('addEdit', addEdit);
        },
        select: () => {
            const { reactive, onMounted, getCurrentInstance } = Vue
            const select = {
                setup() {
                    const { proxy } = getCurrentInstance();

                    const state = reactive({
                        from: new URLSearchParams(location.search).get('from'),
                        multiple: new URLSearchParams(location.search).get('multiple') || false,
                        data: [],
                        selectedIds: []
                    })

                    function getData() {
                        Fast.api.ajax({
                            url: 'shopro/category/select',
                            type: 'GET',
                        }, function (ret, res) {
                            state.data = res.data;
                            if (state.from == 'page-category') {
                                state.data.forEach((item) => {
                                    item?.children && delete item.children;
                                });
                            }
                            if (state.from == 'coupon' || state.from == 'page-goods') {
                                state.data.forEach((item) => {
                                    item.disabled = true;
                                });
                            }
                            return false
                        }, function (ret, res) { })
                    }

                    function onConfirm() {
                        let data = [];
                        proxy.$refs['categoryRef'].checkedNodes.forEach((c) => {
                            data.push(c.data);
                        });
                        Fast.api.close({
                            ids: state.selectedIds,
                            data: data,
                        })
                    }

                    onMounted(() => {
                        getData()
                    })

                    return {
                        state,
                        onConfirm
                    }
                }
            }
            createApp('select', select);
        }
    };
    return Controller;
});
