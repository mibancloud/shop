define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: () => {
            const { reactive, onMounted } = Vue
            const index = {
                setup() {
                    const state = reactive({
                        data: [],
                        order: '',
                        sort: '',
                        filter: {
                            drawer: false,
                            data: {
                                keyword: '',
                                type: '',
                                use_scope: '',
                            },
                            tools: {
                                keyword: {
                                    type: 'tinput',
                                    label: '查询内容',
                                    placeholder: '请输入查询内容',
                                    value: '',
                                },
                                type: {
                                    type: 'tselect',
                                    label: '类型',
                                    value: '',
                                    options: {
                                        data: [{
                                            label: '满减券',
                                            value: 'reduce',
                                        },
                                        {
                                            label: '折扣券',
                                            value: 'discount',
                                        }],
                                    },
                                },
                                use_scope: {
                                    type: 'tselect',
                                    label: '可用范围',
                                    value: '',
                                    options: {
                                        data: [{
                                            label: '全场通用',
                                            value: 'all_use',
                                        },
                                        {
                                            label: '指定商品可用',
                                            value: 'goods',
                                        },
                                        {
                                            label: '指定商品不可用',
                                            value: 'disabled_goods',
                                        },
                                        {
                                            label: '指定分类可用',
                                            value: 'category',
                                        }],
                                    },
                                },
                            },
                            condition: {},
                        },
                        dashboard: {
                            total_num: {
                                name: '总发券量/张',
                                num: '',
                                tip: '用户领取的优惠券的总张数，包含已经被后台删除的优惠券'
                            },
                            expire_num: {
                                name: '已过期/张',
                                num: '',
                                tip: '用户已领取的并且已经超过可使用日期的未使用优惠券'
                            },
                            use_num: {
                                name: '已使用/张',
                                num: '',
                                tip: '用户已领取并且已使用的优惠券',
                            },
                            use_percent: {
                                name: '使用率',
                                num: '',
                                tip: '用户已使用优惠和总发券量的比例'
                            },
                        }
                    })

                    function getData() {
                        let tempSearch = JSON.parse(JSON.stringify(state.filter.data));
                        let search = composeFilter(tempSearch, {
                            keyword: 'like',
                        });
                        Fast.api.ajax({
                            url: 'shopro/coupon',
                            type: 'GET',
                            data: {
                                page: pagination.page,
                                list_rows: pagination.list_rows,
                                order: state.order,
                                sort: state.sort,
                                ...search,
                            },
                        }, function (ret, res) {
                            state.data = res.data.coupons.data
                            pagination.total = res.data.coupons.total

                            for (var key in state.dashboard) {
                                state.dashboard[key].num = res.data[key]
                            }
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

                    async function onCommand(item) {
                        Fast.api.ajax({
                            url: `shopro/coupon/edit/id/${item.id}`,
                            type: 'POST',
                            data: {
                                status: item.type
                            },
                        }, function (ret, res) {
                            getData();
                        }, function (ret, res) { })
                    }

                    function onAdd() {
                        Fast.api.open(`shopro/coupon/add?type=add`, "添加", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onEdit(id) {
                        Fast.api.open(`shopro/coupon/edit?type=edit&id=${id}`, "编辑", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onDelete(id) {
                        Fast.api.ajax({
                            url: `shopro/coupon/delete/id/${id}`,
                            type: 'DELETE',
                        }, function (ret, res) {
                            getData()
                        }, function (ret, res) { })
                    }

                    function onRecyclebin() {
                        Fast.api.open(`shopro/coupon/recyclebin`, "回收站", {
                            callback() {
                                getData()
                            }
                        })
                    }

                    function onCoupon(id) {
                        Fast.api.addtabs(`shopro/user/coupon?coupon_id=${id}`, '领取记录')
                    }

                    function onSend(id) {
                        Fast.api.open(`shopro/user/user/select?id=${id}`, '选择用户', {
                            callback() {
                                getData()
                            }
                        })
                    }

                    onMounted(() => {
                        getData()
                    })

                    return {
                        state,
                        getData,
                        onChangeSort,
                        onOpenFilter,
                        onChangeFilter,
                        pagination,
                        onCommand,
                        onAdd,
                        onEdit,
                        onDelete,
                        onRecyclebin,
                        onCoupon,
                        onSend,
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
            const { ElMessage } = ElementPlus
            const addEdit = {
                setup() {
                    const { proxy } = getCurrentInstance();

                    const state = reactive({
                        type: new URLSearchParams(location.search).get('type'),
                        id: new URLSearchParams(location.search).get('id'),
                    })

                    const form = reactive({
                        model: {
                            name: '',
                            type: 'reduce', // 优惠券类型:reduce=满减券,discount=折扣券
                            use_scope: 'all_use', // 可用范围:all=全场通用,goods=指定商品可用,disabled_goods=指定商品不可用,category=指定分类可用
                            items: '',
                            items_value: [],
                            amount: '',
                            max_amount: '',
                            enough: '',
                            stock: '',
                            limit_num: '',
                            get_time: '',
                            use_time_type: 'days',
                            use_time: '',
                            start_days: '',
                            days: '',
                            is_double_discount: '',
                            description: '',
                            status: 'normal', // 状态:normal=公开,hidden=后台发放,disabled=禁用
                        },
                        rules: {
                            name: [{ required: true, message: '请输入券名称', trigger: 'blur' }],
                            type: [{ required: true, message: '请选择券类型', trigger: 'blur' }],
                            enough: [{ required: true, message: '请输入消费门槛', trigger: 'blur' }],
                            amount: [{ required: true, message: '请输入使用面额', trigger: 'blur' }],
                            max_amount: [{ required: true, message: '请输入最大优惠', trigger: 'blur' }],
                            stock: [{ required: true, message: '请输入发券总量', trigger: 'blur' }],
                            get_time: [{ required: true, message: '请选择优惠券发放时间', trigger: 'blur' }],
                            use_time: [{ required: true, message: '请选择优惠券可使用时间', trigger: 'blur' }],
                            use_time_type: [{ required: true, message: '请选择优惠券使用时间类型', trigger: 'blur' }],
                            days: [{ required: true, message: '请输入优惠券有效天数', trigger: 'blur' }],
                            use_scope: [{ required: true, message: '请选择可用范围', trigger: 'blur' }],
                        },
                    });

                    function getDetail() {
                        Fast.api.ajax({
                            url: `shopro/coupon/detail/id/${state.id}`,
                            type: 'GET',
                        }, function (ret, res) {
                            form.model = res.data;
                            form.model.get_time = [form.model.get_start_time, form.model.get_end_time];
                            if (form.model.use_time_type == 'days') {
                                form.model.use_time = '';
                            } else if (form.model.use_time_type == 'range') {
                                form.model.use_time = [form.model.use_start_time, form.model.use_end_time];
                            }
                            return false
                        }, function (ret, res) { })
                    }

                    function onSelectGoods() {
                        let ids = [];
                        form.model.items_value.forEach((i) => {
                            ids.push(i.id);
                        });
                        Fast.api.open(`shopro/goods/goods/select?multiple=true&ids=${ids.join(',')}`, "选择商品", {
                            callback(data) {
                                form.model.items_value = data;
                            }
                        })
                    }
                    function onDeleteGoods(index) {
                        form.model.items_value.splice(index, 1);
                    }

                    function onSelectCategory() {
                        let ids = [];
                        form.model.items_value.forEach((i) => {
                            ids.push(i.id);
                        });
                        Fast.api.open(`shopro/category/select?from=coupon&multiple=true`, "选择分类", {
                            callback(data) {
                                form.model.items_value = data.data;
                            }
                        })
                    }
                    function onDeleteCategory(index) {
                        form.model.items_value.splice(index, 1);
                    }

                    function onConfirm() {
                        proxy.$refs['formRef'].validate((valid) => {
                            if (valid) {
                                let submitForm = JSON.parse(JSON.stringify(form.model));
                                if (Number(submitForm.enough) < Number(submitForm.amount)) {
                                    ElMessage({
                                        message: '请输入正确的使用门槛',
                                        type: 'warning',
                                    });
                                    return;
                                }
                                if (
                                    submitForm.use_scope == 'goods' ||
                                    submitForm.use_scope == 'disabled_goods' ||
                                    submitForm.use_scope == 'category'
                                ) {
                                    let ids = [];
                                    submitForm.items_value.forEach((i) => {
                                        ids.push(i.id);
                                    });
                                    submitForm.items = ids.join(',');
                                } else if (submitForm.use_scope == 'all_use') {
                                    submitForm.items = '';
                                }
                                delete submitForm.items_value;

                                if (!isEmpty(submitForm.use_time)) {
                                    submitForm.use_time = submitForm.use_time.join(' - ');
                                }

                                if (!isEmpty(submitForm.get_time)) {
                                    submitForm.get_time = submitForm.get_time.join(' - ');
                                }
                                Fast.api.ajax({
                                    url: state.type == 'add' ? 'shopro/coupon/add' : `shopro/coupon/edit/id/${state.id}`,
                                    type: 'POST',
                                    data: submitForm
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
                        onSelectGoods,
                        onDeleteGoods,
                        onSelectCategory,
                        onDeleteCategory,
                        onConfirm
                    }
                }
            }
            createApp('addEdit', addEdit);
        },
        select: () => {
            const { reactive, onMounted } = Vue
            const select = {
                setup() {

                    const state = reactive({
                        multiple: new URLSearchParams(location.search).get('multiple') || false,
                        status: new URLSearchParams(location.search).get('status'),
                        data: [],
                        selected: [],
                    })

                    function getData() {
                        let tempSearch = {
                            status: state.status
                        };
                        let search = composeFilter(tempSearch);
                        Fast.api.ajax({
                            url: 'shopro/coupon/select',
                            type: 'GET',
                            data: {
                                page: pagination.page,
                                list_rows: pagination.list_rows,
                                ...search,
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

                    function onChangeSelection(val) {
                        state.selected = val
                    }

                    function onConfirm() {
                        Fast.api.close(state.selected)
                    }

                    onMounted(() => {
                        getData()
                    })

                    return {
                        state,
                        getData,
                        pagination,
                        onSelect,
                        onChangeSelection,
                        onConfirm,
                    }
                }
            }
            createApp('select', select);
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
                            url: 'shopro/coupon/recyclebin',
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
                            url: `shopro/coupon/restore/id/${id}`,
                            type: 'POST',
                        }, function (ret, res) {
                            getData()
                        }, function (ret, res) { })
                    }
                    function onDestroy(id) {
                        Fast.api.ajax({
                            url: `shopro/coupon/destroy/id/${id}`,
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
