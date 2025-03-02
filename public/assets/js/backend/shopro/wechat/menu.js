define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: () => {
            const { reactive, onMounted } = Vue
            const index = {
                setup() {
                    const state = reactive({
                        current: [],
                        data: [],
                        order: '',
                        sort: '',
                    })

                    function getData() {
                        Fast.api.ajax({
                            url: 'shopro/wechat/menu',
                            type: 'GET',
                            data: {
                                page: pagination.page,
                                list_rows: pagination.list_rows,
                                order: state.order,
                                sort: state.sort,
                            },
                        }, function (ret, res) {
                            state.current = res.data.current
                            state.data = res.data.list.data
                            pagination.total = res.data.list.total
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

                    function onAdd() {
                        Fast.api.open(`shopro/wechat/menu/add?type=add`, "添加", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onEdit(id) {
                        Fast.api.open(`shopro/wechat/menu/edit?type=edit&id=${id}`, "编辑", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onDelete(id) {
                        Fast.api.ajax({
                            url: `shopro/wechat/menu/delete/id/${id}`,
                            type: 'DELETE',
                        }, function (ret, res) {
                            getData()
                        }, function (ret, res) { })
                    }
                    function onPublish(id) {
                        Fast.api.ajax({
                            url: `shopro/wechat/menu/publish/id/${id}`,
                            type: 'POST',
                        }, function (ret, res) {
                            getData()
                        }, function (ret, res) { })
                    }
                    function onCopy(id) {
                        Fast.api.ajax({
                            url: `shopro/wechat/menu/copy/id/${id}`,
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
                        onAdd,
                        onEdit,
                        onDelete,
                        onPublish,
                        onCopy,
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
                        id: new URLSearchParams(location.search).get('id'),

                        rightShow: false,
                        selectLevel: null,
                        selectedIndex1: null,
                        selectedIndex2: null,
                        right: [],
                    })

                    const defaultSubButton = {
                        name: '未命名',
                        type: 'view',
                        selected: true,
                        show: true,
                        url: '',
                        appid: '',
                        pagepath: '',
                        sub_button: [],
                        media_type: 'news',
                        media_id: '',
                    };

                    const form = reactive({
                        model: {
                            name: '',
                            rules: []
                        },
                        rules: {
                            name: [{ required: true, message: '请输入菜单名称', trigger: 'blur' }],
                        },
                    })

                    function getDetail() {
                        Fast.api.ajax({
                            url: `shopro/wechat/menu/detail/id/${state.id}`,
                            type: 'GET',
                        }, function (ret, res) {
                            form.model = res.data;
                            initData();
                            return false
                        }, function (ret, res) { })
                    }
                    function initData() {
                        form.model.rules.forEach((d) => {
                            loopData(d);
                        });
                        function loopData(d) {
                            for (var key in defaultSubButton) {
                                if (!d[key]) {
                                    d[key] = JSON.parse(JSON.stringify(defaultSubButton))[key];
                                }
                                d.selected = false;
                                d.show = false;
                            }
                            if (d.type == 'click') {
                                d.media_type = d.key?.split('|')[0];
                                d.media_id = d.key?.split('|')[1];
                            }
                            if (d.sub_button && d.sub_button.length > 0) {
                                d.sub_button.forEach((s) => {
                                    loopData(s);
                                });
                            }
                        }
                    }

                    function onAddMenu(index, level) {
                        //右侧显示
                        state.rightShow = true;
                        state.selectLevel = level;
                        // 添加level2的数据
                        if (index != null) {
                            state.selectedIndex1 = index;
                            form.model.rules.forEach((i) => {
                                i.selected = false;
                                if (i.sub_button) {
                                    i.sub_button.forEach((j) => {
                                        j.selected = false;
                                    });
                                }
                            });
                            form.model.rules[index].sub_button.push(JSON.parse(JSON.stringify(defaultSubButton)));
                            state.right = form.model.rules[index].sub_button[form.model.rules[index].sub_button.length - 1];
                            state.selectedIndex2 = form.model.rules[index].sub_button.length - 1;
                        } else {
                            // 添加level1的数据 所有的level1不显示
                            form.model.rules.forEach((i) => {
                                i.selected = false;
                                i.show = false;
                            });
                            form.model.rules.push(JSON.parse(JSON.stringify(defaultSubButton)));
                            state.selectedIndex1 = form.model.rules.length - 1;
                            state.right = form.model.rules[form.model.rules.length - 1];
                        }
                    }
                    function onEditMenu(index1, index2) {
                        state.selectedIndex1 = index1;
                        state.selectedIndex2 = index2;
                        state.rightShow = true;
                        form.model.rules.forEach((i) => {
                            i.selected = false;
                            i.show = false;
                            if (i.sub_button) {
                                i.sub_button.forEach((j) => {
                                    j.selected = false;
                                });
                            }
                        });
                        form.model.rules[index1].show = true;
                        if (index2 == null) {
                            state.selectLevel = 1;
                            form.model.rules[index1].selected = true;
                            form.model.rules[index1].show = true;
                            state.right = form.model.rules[index1];
                        } else {
                            state.selectLevel = 2;
                            form.model.rules[index1].sub_button[index2].selected = true;
                            state.right = form.model.rules[index1].sub_button[index2];
                        }
                        getMaterialSelect()
                    }
                    function onDeleteMenu() {
                        if (state.selectedIndex2 != null) {
                            form.model.rules[state.selectedIndex1].sub_button.splice(state.selectedIndex2, 1);
                            if (form.model.rules[state.selectedIndex1].sub_button.length > 0) {
                                if (state.selectedIndex2 == 0) {
                                    form.model.rules[state.selectedIndex1].sub_button[0].selected = true;
                                    state.right = menuData[state.selectedIndex1].sub_button[0];
                                } else {
                                    form.model.rules[state.selectedIndex1].sub_button[state.selectedIndex2 - 1].selected = true;
                                    state.right = form.model.rules[state.selectedIndex1].sub_button[state.selectedIndex2 - 1];
                                    state.selectedIndex2--;
                                }
                            } else {
                                state.right = {};
                                state.rightShow = false;
                            }
                        } else {
                            form.model.rules.splice(state.selectedIndex1, 1);
                            if (form.model.rules.length > 0) {
                                if (state.selectedIndex1 == 0) {
                                    form.model.rules[0].selected = true;
                                    form.model.rules[0].show = true;
                                    state.right = form.model.rules[0];
                                } else {
                                    form.model.rules[state.selectedIndex1 - 1].selected = true;
                                    form.model.rules[state.selectedIndex1 - 1].show = true;
                                    state.right = form.model.rules[state.selectedIndex1 - 1];
                                    state.selectedIndex1--;
                                }
                            } else {
                                state.right = {};
                                state.rightShow = false;
                            }
                        }
                    }

                    function onSelectUrl() {
                        Fast.api.open("shopro/data/page/select", "选择链接", {
                            callback(data) {
                                state.right.url = data.fullPath.url;
                                if (state.right.type == 'miniprogram') {
                                    state.right.appid = data.fullPath.appid;
                                    state.right.pagepath = data.fullPath.pagepath;
                                }
                            }
                        })
                    }

                    function onChangeType() {
                        if (state.right.type == 'click') {
                            getMaterialSelect();
                        }
                    }

                    function onChangeMediaType() {
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
                                type: state.right.media_type,
                                page: material.pagination.page,
                                list_rows: material.pagination.list_rows,
                            },
                        }, function (ret, res) {
                            material.select = initMaterialData(res.data.data, state.right.media_type)
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

                    function formatData() {
                        // 不同类型包含的数据组
                        const view = ['name', 'type', 'url', 'sub_button'];
                        const miniprogram = ['name', 'type', 'url', 'appid', 'pagepath', 'sub_button'];
                        const click = ['name', 'type', 'media_type', 'media_id', 'sub_button'];

                        const data = JSON.parse(JSON.stringify(form.model.rules));
                        data.forEach((d) => {
                            loopData(d);
                        });
                        function loopData(d) {
                            if (d.type == 'view') {
                                for (let j in d) {
                                    if (!view.includes(j)) delete d[j];
                                }
                            }
                            if (d.type == 'miniprogram') {
                                for (let j in d) {
                                    if (!miniprogram.includes(j)) delete d[j];
                                }
                            }
                            if (d.type == 'click') {
                                for (let j in d) {
                                    if (!click.includes(j)) delete d[j];
                                }
                                d.key = d.media_type + '|' + d.media_id;
                                delete d.media_type;
                                delete d.media_id;
                            }
                            if (d.sub_button && d.sub_button.length > 0) {
                                for (let j in d) {
                                    if (j != 'name' && j != 'sub_button') delete d[j];
                                }
                                d.sub_button.forEach((s) => {
                                    loopData(s);
                                });
                            } else {
                                delete d.sub_button;
                            }
                        }
                        return data;
                    }
                    function onConfirm(data = {}) {
                        let submitForm = { name: form.model.name, rules: formatData(), ...data };
                        // proxy.$refs['formRef'].validate((valid) => {
                        //     if (valid) {
                        Fast.api.ajax({
                            url: state.type == 'add' ? 'shopro/wechat/menu/add' : `shopro/wechat/menu/edit/id/${state.id}`,
                            type: 'POST',
                            data: submitForm,
                        }, function (ret, res) {
                            Fast.api.close()
                        }, function (ret, res) { })
                        //     }
                        // });
                    }
                    function onPublish() {
                        onConfirm({ publish: 1 })
                    }

                    onMounted(() => {
                        state.type == 'edit' && getDetail()
                    })

                    return {
                        state,
                        form,
                        onAddMenu,
                        onEditMenu,
                        onDeleteMenu,
                        onSelectUrl,
                        onChangeType,
                        onChangeMediaType,
                        material,
                        getMaterialSelect,
                        onConfirm,
                        onPublish
                    }
                }
            }
            createApp('addEdit', addEdit);
        }
    };
    return Controller;
});
