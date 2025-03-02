define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: () => {
            const { reactive, onMounted } = Vue
            const index = {
                setup() {
                    const state = reactive({
                        data: [],
                        order: 'asc',
                        sort: '',
                    })

                    function getData() {
                        Fast.api.ajax({
                            url: 'shopro/data/area',
                            type: 'GET',
                            data: {
                                order: state.order,
                                sort: state.sort,
                            },
                        }, function (ret, res) {
                            state.data = res.data
                            return false
                        }, function (ret, res) { })
                    }

                    function onAdd() {
                        Fast.api.open("shopro/data/area/add?type=add", "添加", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onEdit(id) {
                        Fast.api.open(`shopro/data/area/edit?type=edit&id=${id}`, "编辑", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onDelete(id) {
                        Fast.api.ajax({
                            url: `shopro/data/area/delete/id/${id}`,
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
                            pid: 0,
                            id: '',
                            name: '',
                        },
                        rules: {
                            id: [{ required: true, message: '请输入行政区ID', trigger: 'blur' }],
                            name: [{ required: true, message: '请输入名称', trigger: 'blur' }],
                        },
                    })

                    function getDetail() {
                        Fast.api.ajax({
                            url: `shopro/data/area/detail/id/${state.id}`,
                            type: 'GET',
                        }, function (ret, res) {
                            form.model = res.data;
                            return false
                        }, function (ret, res) { })
                    }

                    const area = reactive({
                        select: []
                    })
                    function getAreaSelect() {
                        Fast.api.ajax({
                            url: 'shopro/data/area/select',
                            type: 'GET',
                        }, function (ret, res) {
                            area.select = res.data;
                            return false
                        }, function (ret, res) { })
                    }

                    function onConfirm() {
                        proxy.$refs['formRef'].validate((valid) => {
                            if (valid) {
                                Fast.api.ajax({
                                    url: state.type == 'add' ? 'shopro/data/area/add' : `shopro/data/area/edit/old_id/${state.id}`,
                                    type: 'POST',
                                    data: form.model,
                                }, function (ret, res) {
                                    Fast.api.close()
                                }, function (ret, res) { })
                            }
                        });
                    }

                    onMounted(() => {
                        getAreaSelect()
                        state.type == 'edit' && getDetail()
                    })

                    return {
                        state,
                        form,
                        area,
                        onConfirm
                    }
                }
            }
            createApp('addEdit', addEdit);
        },
        select: () => {
            const { reactive, computed, onMounted, getCurrentInstance, nextTick } = Vue
            const select = {
                setup() {
                    const { proxy } = getCurrentInstance();

                    const state = reactive({
                        selected: JSON.parse(new URLSearchParams(location.search).get('selected')),
                        data: [],
                        ids: [],
                        label: {},
                        checkedAll: false,
                    })

                    function getSelect() {
                        Fast.api.ajax({
                            url: 'shopro/data/area/select',
                            type: 'GET',
                        }, function (ret, res) {
                            state.data = res.data
                            state.checkedAll = state.selected.province.split(',').length == state.data.length;

                            nextTick(() => {
                                proxy.$refs['treeRef']?.getCheckedNodes().forEach((data) => {
                                    if (state.ids.includes(data.id)) {
                                        if (!state.label[data.level]) {
                                            state.label[data.level] = {};
                                        }
                                        state.label[data.level][data.id] = data.name;
                                    }
                                });
                            });
                            return false
                        }, function (ret, res) { })
                    }

                    const isIndeterminate = computed(() => (state.ids.length > 0 && !state.checkedAll ? true : false));

                    function onChange() {
                        if (state.checkedAll) {
                            nextTick(() => {
                                state.ids = [];
                                proxy.$refs['treeRef']?.setCheckedNodes(state.data, false);
                                state.data.forEach((d) => {
                                    state.ids.push(d.id);
                                    if (!state.label[d.level]) {
                                        state.label[d.level] = {};
                                    }
                                    state.label[d.level][d.id] = d.name;
                                });
                            });
                        } else {
                            proxy.$refs['treeRef']?.setCheckedKeys([], false);
                            state.ids = [];
                            state.label = {
                                province: {},
                                city: {},
                                district: {},
                            };
                        }
                    }

                    function onChangeCheck(data, checked, indeterminate) {
                        // 全选
                        if (!state.checkedAll) {
                            // 选中(把自己放进去)
                            if (checked) {
                                state.ids.push(data.id);
                                if (!state.label[data.level]) {
                                    state.label[data.level] = {};
                                }
                                state.label[data.level][data.id] = data.name;
                                if (!indeterminate) {
                                    deleteIds(data);
                                }
                            }
                        }

                        // 未选中(把自己删除)
                        if (!checked) {
                            if (state.ids.includes(data.id)) {
                                state.ids.splice(state.ids.indexOf(data.id), 1);
                                for (var key in state.label[data.level]) {
                                    if (Number(key) == Number(data.id)) {
                                        delete state.label[data.level][key];
                                    }
                                }
                            }
                            if (indeterminate) {
                                addIds(data);
                            }
                        }

                        if (state.label.province)
                            state.checkedAll = Object.keys(state.label.province).length == state.data.length;
                    }

                    function deleteIds(data) {
                        let keys = proxy.$refs['treeRef'].getCheckedKeys();
                        if (data.children && data.children.length > 0) {
                            data.children.forEach((d) => {
                                if (keys.includes(d.id) && state.ids.includes(d.id)) {
                                    state.ids.splice(state.ids.indexOf(d.id), 1);
                                    delete state.label[d.level][d.id];
                                }
                                deleteIds(d);
                            });
                        }
                        // TODO: 需要修复
                    }

                    function addIds(data) {
                        let keys = proxy.$refs['treeRef'].getCheckedKeys();
                        if (data.children && data.children.length > 0) {
                            data.children.forEach((d) => {
                                if (keys.includes(d.id) && !state.ids.includes(d.id)) {
                                    state.ids.push(d.id);
                                    if (!state.label[d.level]) {
                                        state.label[d.level] = {};
                                    }
                                    state.label[d.level][d.id] = d.name;
                                }
                            });
                        }
                    }

                    function onConfirm() {
                        Fast.api.close(state.label)
                    }

                    onMounted(() => {
                        // state.ids 赋值
                        for (var level in state.selected) {
                            if (state.selected[level]) {
                                state.selected[level].split(',').forEach((id) => {
                                    state.ids.push(Number(id));
                                });
                            }
                        }
                        getSelect()
                    })

                    return {
                        state,
                        isIndeterminate,
                        onChange,
                        onChangeCheck,
                        onConfirm,
                    }
                }
            }
            createApp('select', select);
        },
    };
    return Controller;
});
