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
                    })

                    function getData() {
                        Fast.api.ajax({
                            url: 'shopro/data/page',
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
                        Fast.api.open("shopro/data/page/add?type=add", "添加", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onEdit(id) {
                        Fast.api.open(`shopro/data/page/edit?type=edit&id=${id}`, "编辑", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onDelete(id) {
                        Fast.api.ajax({
                            url: `shopro/data/page/delete/id/${id}`,
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
                            name: '',
                            path: '',
                            group: '',
                        },
                        rules: {
                            name: [{ required: true, message: '请输入名称', trigger: 'blur' }],
                            path: [{ required: true, message: '请输入路径', trigger: 'blur' }],
                            group: [{ required: true, message: '请输入分组', trigger: 'blur' }],
                        },
                    })

                    function getDetail() {
                        Fast.api.ajax({
                            url: `shopro/data/page/detail/id/${state.id}`,
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
                                    url: state.type == 'add' ? 'shopro/data/page/add' : `shopro/data/page/edit/id/${state.id}`,
                                    type: 'POST',
                                    data: form.model
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
        select: () => {
            const { ref, reactive, onMounted, getCurrentInstance, nextTick } = Vue
            const select = {
                setup() {
                    const { proxy } = getCurrentInstance();

                    const state = reactive({
                        data: [],
                        height: [],
                        currentIndex: 0,
                        selected: {},
                    });

                    async function getSelect() {
                        Fast.api.ajax({
                            url: 'shopro/data/page/select',
                            type: 'GET',
                        }, function (ret, res) {
                            state.data = res.data;
                            nextTick(() => {
                                getHeight();
                            });
                            return false
                        }, function (ret, res) { })
                    }

                    function onChangeIndex(index) {
                        proxy.$refs.rightScrollRef.setScrollTop(state.height[index]);
                        state.currentIndex = index;
                    }

                    function onRightScroll(e) {
                        let index = state.height.findIndex((item) => {
                            return item > e.scrollTop;
                        });
                        if (index > 0) {
                            state.currentIndex = index - 1;
                        } else if (index == -1) {
                            state.currentIndex = state.height.length - 1;
                        }
                    }

                    function onSelect(link) {
                        state.selected = { ...link };
                        if (link.path == '/pages/index/page') {
                            Fast.api.open('shopro/decorate/template/select', "选择自定义页面", {
                                callback(data) {
                                    state.selected.path += (!isEmpty(data.id) ? '?id=' + data.id : '');
                                }
                            })
                        } else if (link.path == '/pages/goods/index') {
                            Fast.api.open('shopro/goods/goods/select', "选择商品", {
                                callback(data) {
                                    state.selected.path += (!isEmpty(data.id) ? '?id=' + data.id : '');
                                }
                            })
                        } else if (link.path == '/pages/index/category') {
                            Fast.api.open('shopro/category/select?from=page-category', "选择分类", {
                                callback(data) {
                                    state.selected.path += (!isEmpty(data.ids) ? '?id=' + data.ids : '');
                                }
                            })
                        } else if (link.path == '/pages/goods/list') {
                            Fast.api.open('shopro/category/select?from=page-goods', "选择分类", {
                                callback(data) {
                                    state.selected.path += (!isEmpty(data.ids) ? '?categoryId=' + data.ids : '');
                                }
                            })
                        } else if (link.path == '/pages/public/richtext') {
                            Fast.api.open('shopro/data/richtext/select', "选择富文本", {
                                callback(data) {
                                    state.selected.path += (!isEmpty(data.id) ? '?id=' + data.id : '');
                                }
                            })
                        } else if (
                            link.path == '/pages/activity/groupon/list' ||
                            link.path == '/pages/activity/seckill/list'
                        ) {
                            let activityType = {
                                groupon: 'groupon,groupon_ladder',
                                seckill: 'seckill',
                            };
                            Fast.api.open(`shopro/activity/activity/select?type=${activityType[link.path.split('/')[3]]}`, "选择活动", {
                                callback(data) {
                                    state.selected.path += (!isEmpty(data.id) ? '?id=' + data.id : '');
                                }
                            })
                        } else if (link.path == '/pages/goods/groupon' || link.path == '/pages/goods/seckill') {
                            let activityType = {
                                groupon: 'groupon,groupon_ladder',
                                seckill: 'seckill',
                            };
                            Fast.api.open(`shopro/activity/activity/select?type=${activityType[link.path.split('/').pop()]}`, "选择活动", {
                                callback(data) {
                                    state.selected.path += (!isEmpty(data.id) ? '?activity_id=' + data.id : '');
                                    Fast.api.open(`shopro/goods/goods/select?goods_ids=${data.goods_ids}`, "选择商品", {
                                        callback(data) {
                                            state.selected.path += (!isEmpty(data.id) ? '&id=' + data.id : '');
                                        }
                                    })
                                }
                            })
                        } else if (link.path == '/pages/goods/score') {
                            Fast.api.open(`shopro/app/score_shop/select`, "选择积分商品", {
                                callback(data) {
                                    state.selected.path += (!isEmpty(data.id) ? '?id=' + data.id : '');
                                }
                            })
                        } else if (link.path == '/pages/coupon/detail') {
                            Fast.api.open(`shopro/coupon/select?status=normal`, "选择优惠券", {
                                callback(data) {
                                    state.selected.path += (!isEmpty(data.id) ? '?id=' + data.id : '');
                                }
                            })
                        }
                    }

                    const rightRef = {};
                    function setRightRef(el, item, index) {
                        rightRef[item.group + index] = el;
                    }
                    function getHeight() {
                        state.height = [];
                        for (let e in rightRef) {
                            state.height.push(rightRef[e].offsetTop);
                        }
                    }

                    const platformUrl = ref({});
                    function getPlatformUrl() {
                        Fast.api.ajax({
                            url: 'shopro/config/getPlatformUrl',
                            type: 'GET',
                        }, function (ret, res) {
                            platformUrl.value = res.data
                            return false
                        }, function (ret, res) { })
                    }

                    async function onConfirm() {
                        state.selected.fullPath = {
                            url: `${platformUrl.value.url.endsWith('/')
                                ? platformUrl.value.url.substr(0, platformUrl.value.url.length - 1)
                                : platformUrl.value.url
                                }${state.selected.path}`,
                            appid: platformUrl.value.appid,
                            pagepath: state.selected.path
                                ? '/pages/index/index?page=' + encodeURIComponent(state.selected.path)
                                : '/pages/index/index',
                        };
                        Fast.api.close(state.selected)
                    }

                    onMounted(() => {
                        getSelect();
                        getPlatformUrl();
                    });

                    return {
                        state,
                        getSelect,
                        onRightScroll,
                        onChangeIndex,
                        onSelect,
                        rightRef,
                        setRightRef,
                        getHeight,
                        platformUrl,
                        getPlatformUrl,
                        onConfirm,
                    }
                }
            }
            createApp('select', select);
        }
    };
    return Controller;
});
