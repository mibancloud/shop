define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: () => {
            const { ref, reactive, onMounted, getCurrentInstance } = Vue
            const { ElMessageBox } = ElementPlus
            const index = {
                setup() {

                    const { proxy } = getCurrentInstance();

                    const state = reactive({
                        data: [],
                        order: 'desc',
                        sort: 'id',
                        filter: {
                            drawer: false,
                            data: {
                                status: 'all',
                                keyword: { field: 'id', value: '' },
                                category_ids: 'all',
                                activity_type: '',
                                price: { min: '', max: '' },
                                sales: { min: '', max: '' },
                            },
                            tools: {
                                keyword: {
                                    type: 'tinputprepend',
                                    label: '商品信息',
                                    placeholder: '请输入查询内容',
                                    value: {
                                        field: 'id',
                                        value: '',
                                    },
                                    options: {
                                        data: [{
                                            label: '商品ID',
                                            value: 'id',
                                        },
                                        {
                                            label: '商品名称',
                                            value: 'title',
                                        },
                                        {
                                            label: '商品副标题',
                                            value: 'subtitle',
                                        }],
                                    }
                                },
                                // category_ids: {
                                //     type: 'tcascader',
                                //     label: '商品分类',
                                //     value: [],
                                //     options: {
                                //         data: [],
                                //         props: {
                                //             children: 'children',
                                //             label: 'name',
                                //             value: 'id',
                                //             checkStrictly: true,
                                //             emitPath: false,
                                //             multiple: true,
                                //         },
                                //     },
                                // },
                                activity_type: {
                                    type: 'tselect',
                                    label: '活动类型',
                                    value: '',
                                    options: {
                                        data: [],
                                        props: {
                                            label: 'name',
                                            value: 'type',
                                        },
                                    },
                                },
                            },
                            condition: {},
                        },
                        statusData: {
                            type: {
                                up: 'success',
                                down: 'danger',
                                hidden: 'info',
                            },
                            color: {
                                up: 'var(--el-color-success)',
                                down: 'var(--el-color-danger)',
                                hidden: 'var(--el-color-info)',
                            }
                        },
                    })

                    const type = reactive({
                        data: []
                    })
                    function getTypeData() {
                        Fast.api.ajax({
                            url: 'shopro/goods/goods/getType',
                            type: 'GET',
                        }, function (ret, res) {
                            type.data = res.data
                            for (key in state.filter.tools) {
                                if (key == 'activity_type') {
                                    state.filter.tools[key].options.data = res.data[key]
                                }
                            }
                            return false
                        }, function (ret, res) { })
                    }

                    const category = reactive({
                        select: []
                    })
                    function getCategorySelect() {
                        Fast.api.ajax({
                            url: 'shopro/category/goodsSelect',
                            type: 'GET',
                        }, function (ret, res) {
                            category.select = res.data
                            // for (key in state.filter.tools) {
                            //     if (key == 'category_ids') {
                            //         state.filter.tools[key].options.data = res.data
                            //     }
                            // }
                            return false
                        }, function (ret, res) { })
                    }

                    function getData() {
                        let tempSearch = JSON.parse(JSON.stringify(state.filter.data));
                        for (key in tempSearch) {
                            if (key == 'price' || key == 'sales') {
                                if (Number(tempSearch[key].min) && Number(tempSearch[key].max)) {
                                    tempSearch[key] = `${tempSearch[key].min} - ${tempSearch[key].max}`
                                }
                            }
                        }
                        let search = composeFilter(tempSearch, {
                            title: 'like',
                            subtitle: 'like',
                            category_ids: {
                                spacer: ',',
                            },
                            price: 'between',
                            sales: 'between',
                        });
                        Fast.api.ajax({
                            url: 'shopro/goods/goods',
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

                    function onChangeTab() {
                        pagination.page = 1
                        getData()
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
                            default:
                                onCommand({ id: ids.join(','), type: type })
                                break;
                        }
                    }

                    function onCommand(item) {
                        Fast.api.ajax({
                            url: `shopro/goods/goods/edit/id/${item.id}`,
                            type: 'POST',
                            data: {
                                status: item.type
                            }
                        }, function (ret, res) {
                            getData()
                        }, function (ret, res) { })
                    }

                    function onSkuCommand(item) {
                        Fast.api.ajax({
                            url: `shopro/goods/sku_price/edit/id/${item.id}`,
                            type: 'POST',
                            data: {
                                status: item.type
                            }
                        }, function (ret, res) {
                            getSkuPrice(item.goods_id);
                        }, function (ret, res) { })
                    }

                    const expandRowKeys = reactive([]);
                    function onExpand(id) {
                        skuPrice.data = [];
                        if (expandRowKeys.includes(id)) {
                            expandRowKeys.length = 0;
                        } else {
                            expandRowKeys.length = 0;
                            expandRowKeys.push(id);
                            getSkuPrice(id);
                        }
                    }

                    const skuPrice = reactive({
                        data: []
                    })
                    function getSkuPrice(id) {
                        Fast.api.ajax({
                            url: `shopro/goods/sku_price?goods_id=${id}`,
                            type: 'GET',
                        }, function (ret, res) {
                            skuPrice.data = res.data;
                            return false
                        }, function (ret, res) { })
                    }

                    function onOpenActivityDetail(activity) {
                        Fast.api.open(`shopro/activity/activity/edit?type=edit&activity_type=${activity.type}&id=${activity.id}`, `${activity.type_text}活动`, {
                            callback() {
                                getData()
                            }
                        })
                    }

                    function onEditStock(item) {
                        console.log('编辑库存')
                        Fast.api.open(`shopro/goods/goods/addStock?id=${item.id}&stock=${item.stock || 0}&is_sku=${item.is_sku}`, "编辑库存", {
                            callback() {
                                getData()
                            }
                        })
                    }

                    function onAdd() {
                        Fast.api.open("shopro/goods/goods/add?type=add", "添加", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onEdit(id) {
                        Fast.api.open(`shopro/goods/goods/edit?type=edit&id=${id}`, "编辑", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onCopy(id) {
                        Fast.api.open(`shopro/goods/goods/add?type=copy&id=${id}`, "复制", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onDelete(id) {
                        Fast.api.ajax({
                            url: `shopro/goods/goods/delete/id/${id}`,
                            type: 'DELETE',
                        }, function (ret, res) {
                            getData()
                        }, function (ret, res) { })
                    }

                    function onRecyclebin() {
                        Fast.api.open('shopro/goods/goods/recyclebin', "回收站", {
                            callback() {
                                getData()
                            }
                        })
                    }

                    function onChangeCategoryIds(val) {
                        state.filter.data.category_ids = val?.id || 'all'
                        pagination.page = 1
                        getData();
                    }

                    const defaultExpandedKeys = ref([])
                    function onFold() {
                        for (var key in proxy.$refs.treeRef.store.nodesMap) {
                            proxy.$refs.treeRef.store.nodesMap[key].expanded = false
                        }
                        defaultExpandedKeys.value = []
                    }

                    onMounted(() => {
                        getTypeData()
                        getCategorySelect()
                        getData()
                    })

                    return {
                        state,
                        type,
                        category,
                        getData,
                        onChangeSort,
                        onOpenFilter,
                        onChangeFilter,
                        onChangeTab,
                        pagination,
                        batchHandle,
                        onChangeSelection,
                        onBatchHandle,
                        onCommand,
                        onSkuCommand,
                        expandRowKeys,
                        onExpand,
                        skuPrice,
                        getSkuPrice,
                        onOpenActivityDetail,
                        onEditStock,
                        onAdd,
                        onEdit,
                        onCopy,
                        onDelete,
                        onRecyclebin,
                        onChangeCategoryIds,
                        onFold,
                        defaultExpandedKeys,
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
            const { ref, reactive, onBeforeMount, onMounted, getCurrentInstance, watch, nextTick } = Vue
            const addEdit = {
                setup() {
                    const { proxy } = getCurrentInstance();

                    const state = reactive({
                        type: new URLSearchParams(location.search).get('type'),
                        id: new URLSearchParams(location.search).get('id'),
                        activeStep: 0,
                        tempData: {
                            isStockWarning: false,
                        }
                    })

                    const form = reactive({
                        model: {
                            type: 'normal', // 商品类型
                            image: '',
                            images: [],
                            title: '',
                            subtitle: '',
                            category_ids: '',
                            weigh: 0,
                            sales_show_type: 'exact',
                            show_sales: 0,
                            limit_type: 'none',
                            limit_num: 0,
                            status: 'up',
                            dispatch_type: 'express',
                            dispatch_id: '',
                            is_offline: 0,
                            is_sku: 0,
                            price: '',
                            original_price: 0,
                            cost_price: 0,
                            stock_show_type: 'exact',
                            stock: '',
                            stock_warning: '',
                            weight: '',
                            sn: '',
                            skus: [
                                {
                                    id: 0,
                                    name: '',
                                    goods_id: 0,
                                    parent_id: 0,
                                    weigh: 0,
                                    children: [
                                        {
                                            id: 0,
                                            name: '',
                                            goods_id: 0,
                                            parent_id: 0,
                                            weigh: 0,
                                        },
                                    ],
                                },
                            ],
                            sku_prices: [],
                            service_ids: [],
                            params: [],
                            content: '',
                        },
                        rules: {
                            image: [{ required: true, message: '请选择商品主图', trigger: 'blur' }],
                            images: [{ required: true, message: '请选择轮播图', trigger: 'blur' }],
                            title: [{ required: true, message: '请输入商品标题', trigger: 'blur' }],
                            // category_ids: [{ required: true, message: '请输入商品分类', trigger: 'blur' }],
                            dispatch_id: [{ required: true, message: '请选择物流快递', trigger: 'blur' }],
                            price: [{ required: true, message: '请输入售卖价格', trigger: 'blur' }],
                        },
                    })

                    function getDetail() {
                        Fast.api.ajax({
                            url: `shopro/goods/goods/detail/id/${state.id}`,
                            type: 'GET',
                        }, function (ret, res) {
                            form.model = res.data;

                            // 商品分类
                            initCategoryIds()

                            // 单规格
                            if (form.model.is_sku == 0) {
                                form.model.price = Number(form.model.price);
                                state.tempData.isStockWarning = form.model.stock_warning ? true : false
                            }
                            // 多规格
                            if (form.model.is_sku == 1) {
                                getInit();
                            }

                            form.model.params = form.model.params ? form.model.params : [];
                            form.model.service_ids = form.model.service_ids ? form.model.service_ids : [];

                            // 富文本
                            Controller.api.bindevent();
                            $('#goodsContent').html(form.model.content)

                            getDispatchSelect()
                            return false
                        }, function (ret, res) { })
                    }

                    function onChangeGoodsType(type) {
                        console.log(type, 'type')
                        form.model.type = type;
                        form.model.dispatch_type = type == 'normal' ? 'express' : 'autosend';
                        form.model.dispatch_id = ''
                        getDispatchSelect()
                    }

                    let categoryRef = {};
                    const setCategoryRef = (el, tab) => {
                        if (el) {
                            categoryRef[tab.id + '-' + tab.name] = el;
                        }
                    };
                    const tempCategory = reactive({
                        tabActive: '',
                        idsArr: {},
                        label: {}
                    })
                    function initCategoryIds() {
                        tempCategory.idsArr = {}
                        form.model.category_ids_arr.forEach(item => {
                            if (tempCategory.idsArr[item[0]]) {
                                tempCategory.idsArr[item[0]].push(item.pop())
                            } else {
                                tempCategory.idsArr[item[0]] = []
                                tempCategory.idsArr[item[0]].push(item.pop())
                            }
                        })
                        onChangeCategoryIds()
                    }
                    function onChangeCategoryIds() {
                        nextTick(() => {
                            tempCategory.label = {}
                            for (var key in categoryRef) {
                                let keyArr = key.split('-');
                                if (categoryRef[key].checkedNodes.length > 0) {
                                    categoryRef[key].checkedNodes.forEach((row) => {
                                        tempCategory.label[row.value] = keyArr[1] + '/' + row.pathLabels.join('/');
                                    });
                                }
                            }
                        })
                    }
                    function onDeleteCategoryIds(id) {
                        delete tempCategory.label[id];
                        let idx = -1
                        for (var key in tempCategory.idsArr) {
                            tempCategory.idsArr[key].forEach((item, index) => {
                                if (item == id) {
                                    idx = index
                                }
                            })
                            if (idx != -1) {
                                tempCategory.idsArr[key].splice(idx, 1)
                                idx = -1
                            }
                        }
                    }
                    function onClearCategoryIds() {
                        tempCategory.idsArr = {}
                        tempCategory.label = {}
                    }
                    const category = reactive({
                        select: []
                    })
                    function getCategorySelect(type) {
                        Fast.api.ajax({
                            url: 'shopro/category/select',
                            type: 'GET',
                        }, function (ret, res) {
                            category.select = res.data

                            // 分类选项卡赋值
                            if (res.data.length > 0) {
                                tempCategory.tabActive = res.data[0].id + ''
                            }
                            if (type) {
                                if (state.type == 'edit' || state.type == 'copy') {
                                    getDetail()
                                } else {
                                    getInit();
                                    getDispatchSelect()
                                    nextTick(() => {
                                        Controller.api.bindevent();
                                    })
                                }
                            }
                            return false
                        }, function (ret, res) { })
                    }
                    function onAddCategory() {
                        Fast.api.open("shopro/category/add?type=add", "添加", {
                            callback() {
                                getCategorySelect()
                            }
                        })
                    }

                    const dispatch = reactive({
                        select: []
                    })
                    function getDispatchSelect() {
                        Fast.api.ajax({
                            url: 'shopro/dispatch/dispatch/select',
                            type: 'GET',
                            data: {
                                type: form.model.dispatch_type
                            }
                        }, function (ret, res) {
                            dispatch.select = res.data
                            return false
                        }, function (ret, res) { })
                    }
                    function onAddDispatch(dispatch_type) {
                        Fast.api.open(`shopro/dispatch/dispatch/add?type=add&dispatch_type=${dispatch_type}`, "添加", {
                            callback() {
                                getDispatchSelect()
                            }
                        })
                    }
                    function onChangeDispatchType(val) {
                        form.model.dispatch_id = val == 'custom' ? 0 : ''
                        getDispatchSelect()
                    }

                    const service = reactive({
                        select: []
                    })
                    function getServiceSelect() {
                        Fast.api.ajax({
                            url: 'shopro/goods/service/select',
                            type: 'GET',
                        }, function (ret, res) {
                            service.select = res.data
                            return false
                        }, function (ret, res) { })
                    }
                    function onAddService() {
                        Fast.api.open("shopro/goods/service/add?type=add", "添加", {
                            callback() {
                                getServiceSelect()
                            }
                        })
                    }

                    function onBack() {
                        state.activeStep--;
                    }

                    function onNext() {
                        // proxy.$refs['formRef'].validate((valid) => {
                        //     if (valid) {
                        state.activeStep++;
                        //     } else {
                        //         return false;
                        //     }
                        // });
                    }

                    const validateData = ref({
                        0: 0,
                        1: 0,
                        2: 0,
                        3: 0,
                        4: 0,
                    })
                    function isValidate() {
                        nextTick(async () => {
                            for (var key in validateData.value) {
                                await proxy.$refs[`formRef${key}`].validate((valid) => {
                                    if (valid) {
                                        validateData.value[key] = 0;
                                    } else {
                                        validateData.value[key] = 1;
                                    }
                                });
                            }
                        })
                    }

                    const isEditInit = ref(false);
                    function getInit() {
                        let tempIdArr = {};
                        for (let i in form.model.skus) {
                            // 为每个 规格增加当前页面自增计数器，比较唯一用
                            form.model.skus[i]['temp_id'] = countId.value++;
                            for (let j in form.model.skus[i]['children']) {
                                // 为每个 规格项增加当前页面自增计数器，比较唯一用
                                form.model.skus[i]['children'][j]['temp_id'] = countId.value++;
                                // 记录规格项真实 id 对应的 临时 id
                                tempIdArr[form.model.skus[i]['children'][j]['id']] =
                                    form.model.skus[i]['children'][j]['temp_id'];
                            }
                        }
                        for (var i = 0; i < form.model.sku_prices.length; i++) {
                            let tempSkuPrice = form.model.sku_prices[i];
                            tempSkuPrice['temp_id'] = i + 1;
                            // 将真实 id 数组，循环，找到对应的临时 id 组合成数组
                            tempSkuPrice['goods_sku_temp_ids'] = [];
                            let goods_sku_id_arr = tempSkuPrice['goods_sku_ids'].split(',');
                            for (let ids of goods_sku_id_arr) {
                                tempSkuPrice['goods_sku_temp_ids'].push(tempIdArr[ids]);
                            }
                            form.model.sku_prices[i] = tempSkuPrice;
                        }

                        if (state.type == 'copy') {
                            for (let i in form.model.skus) {
                                // 为每个 规格增加当前页面自增计数器，比较唯一用
                                form.model.skus[i].id = 0;
                                for (let j in form.model.skus[i]['children']) {
                                    form.model.skus[i]['children'][j].id = 0;
                                }
                            }
                        }

                        if (form.model.sku_prices.length > 0) {
                            form.model.sku_prices.forEach((si) => {
                                si.stock_warning_switch = false;
                                if (si.stock_warning || si.stock_warning == 0) {
                                    si.stock_warning_switch = true;
                                }
                            });
                        }
                        setTimeout(() => {
                            isEditInit.value = true;
                        }, 200);
                    }
                    //添加主规格
                    const skuModal = ref('');
                    const countId = ref(1);
                    function addMainSku() {
                        form.model.skus.push({
                            id: 0,
                            temp_id: countId.value++,
                            name: skuModal.value,
                            pid: 0,
                            children: [],
                        });
                        skuModal.value = '';
                        buildSkuPriceTable();
                    }
                    function deleteMainSku(k) {
                        let data = form.model.skus[k];

                        // 删除主规格
                        form.model.skus.splice(k, 1);

                        // 如果当前删除的主规格存在子规格，则清空 skuPrice， 不存在子规格则不清空
                        if (data.children.length > 0) {
                            form.model.sku_prices = []; // 规格大变化，清空skuPrice
                            isResetSku.value = 1; // 重置规格
                        }
                        buildSkuPriceTable();
                    }
                    //添加子规格
                    const isResetSku = ref(0);
                    const childrenModal = [];
                    function addChildrenSku(k) {
                        let isExist = false;
                        form.model.skus[k].children.forEach((e) => {
                            if (e.name == childrenModal[k] && e.name != '') {
                                isExist = true;
                            }
                        });
                        if (isExist) {
                            alert('子规格已存在');
                            return false;
                        }

                        form.model.skus[k].children.push({
                            id: 0,
                            temp_id: countId.value++,
                            name: childrenModal[k],
                            pid: form.model.skus[k].id,
                        });
                        childrenModal[k] = '';

                        // 如果是添加的第一个子规格，清空 skuPrice
                        if (form.model.skus[k].children.length == 1) {
                            form.model.sku_prices = []; // 规格大变化，清空skuPrice
                            isResetSku.value = 1; // 重置规格
                        }
                        buildSkuPriceTable();
                    }
                    function deleteChildrenSku(k, i) {
                        let data = form.model.skus[k].children[i];
                        form.model.skus[k].children.splice(i, 1);

                        // 查询 skuPrice 中包含被删除的的子规格的项，然后移除
                        let deleteArr = [];
                        form.model.sku_prices.forEach((item, index) => {
                            item.goods_sku_text.forEach((e, i) => {
                                if (e == data.name) {
                                    deleteArr.push(index);
                                }
                            });
                        });
                        deleteArr.sort(function (a, b) {
                            return b - a;
                        });
                        // 移除有相关子规格的项
                        deleteArr.forEach((i, e) => {
                            form.model.sku_prices.splice(i, 1);
                        });

                        // 当前规格项，所有子规格都被删除，清空 skuPrice
                        if (form.model.skus[k].children.length <= 0) {
                            form.model.sku_prices = []; // 规格大变化，清空skuPrice
                            isResetSku.value = 1; // 重置规格
                        }
                        buildSkuPriceTable();
                    }
                    watch(
                        () => form.model.skus,
                        () => {
                            if (isEditInit.value && form.model.is_sku) {
                                buildSkuPriceTable();
                            }
                        },
                        { deep: true },
                    );
                    //组成新的规格
                    function buildSkuPriceTable() {
                        let arr = [];
                        //遍历sku子规格生成新数组，然后执行递归笛卡尔积
                        form.model.skus.forEach((s1, k1) => {
                            let children = s1.children;
                            let childrenIdArray = [];
                            if (children.length > 0) {
                                children.forEach((s2, k2) => {
                                    childrenIdArray.push(s2.temp_id);
                                });
                                // 如果 children 子规格数量为 0,则不渲染当前规格, （相当于没有这个主规格）
                                arr.push(childrenIdArray);
                            }
                        });
                        recursionSku(arr, 0, []);
                    }
                    //递归找笛卡尔规格集合
                    function recursionSku(arr, k, temp) {
                        if (k == arr.length && k != 0) {
                            let tempDetail = [];
                            let tempDetailIds = [];
                            temp.forEach((item, index) => {
                                for (let sku of form.model.skus) {
                                    for (let child of sku.children) {
                                        if (item == child.temp_id) {
                                            tempDetail.push(child.name);
                                            tempDetailIds.push(child.temp_id);
                                        }
                                    }
                                }
                            });
                            let flag = false; // 默认添加新的
                            for (let i = 0; i < form.model.sku_prices.length; i++) {
                                if (form.model.sku_prices[i].goods_sku_temp_ids.join(',') == tempDetailIds.join(',')) {
                                    flag = i;
                                    break;
                                }
                            }

                            if (flag === false) {
                                form.model.sku_prices.push({
                                    id: 0,
                                    temp_id: form.model.sku_prices.length + 1,
                                    goods_sku_ids: '',
                                    goods_id: 0,
                                    weigh: 0,
                                    image: '',
                                    stock: 0,
                                    stock_warning: null,
                                    stock_warning_switch: false,
                                    price: 0,
                                    sn: '',
                                    weight: 0,
                                    status: 'up',
                                    goods_sku_text: tempDetail,
                                    goods_sku_temp_ids: tempDetailIds,
                                });
                            } else {
                                form.model.sku_prices[flag].goods_sku_text = tempDetail;
                                form.model.sku_prices[flag].goods_sku_temp_ids = tempDetailIds;
                            }
                            return;
                        }
                        if (arr.length) {
                            for (let i = 0; i < arr[k].length; i++) {
                                temp[k] = arr[k][i];
                                recursionSku(arr, k + 1, temp);
                            }
                        }
                    }

                    const batchPopover = reactive({
                        flag: {
                            price: false,
                            original_price: false,
                            cost_price: false,
                            weight: false,
                            sn: false,
                        },
                        value: ''
                    })
                    function onbatchPopover(type, oper) {
                        switch (oper) {
                            case 'confirm':
                                form.model.sku_prices.forEach((i) => {
                                    i[type] = batchPopover.value;
                                });
                                batchPopover.value = '';
                                batchPopover.flag[type] = false;
                                break;
                            case 'cancel':
                                batchPopover.value = '';
                                batchPopover.flag[type] = false;
                                break;
                        }
                    }

                    function onChangeStockWarningSwitch(index) {
                        form.model.sku_prices[index].stock_warning = form.model.sku_prices[index].stock_warning_switch
                            ? 0
                            : null;
                    }

                    const paramsRules = {
                        title: [{ required: true, message: '请输入名称', trigger: 'blur' }],
                        content: [{ required: true, message: '请输入内容', trigger: 'blur' }],
                    };
                    function onAddParams() {
                        form.model.params.push({
                            title: '',
                            content: '',
                        });
                    }
                    function onDeleteParams(index) {
                        form.model.params.splice(index, 1);
                    }

                    function onSuccess(data) {
                        form.model.image_wh = {
                            w: data.image_width,
                            h: data.image_height,
                        };
                    }

                    function onConfirm() {
                        isValidate()
                        setTimeout(() => {
                            if (validateData.value[0] == 0 && validateData.value[1] == 0 && validateData.value[2] == 0) {
                                let submitForm = JSON.parse(JSON.stringify(form.model));

                                let idsArr = [];
                                for (var key in tempCategory.idsArr) {
                                    idsArr.push(...tempCategory.idsArr[key]);
                                }
                                submitForm.category_ids = idsArr.join(',');

                                if (submitForm.is_sku == 0) {
                                    if (!state.tempData.isStockWarning) {
                                        submitForm.stock_warning = null
                                    }
                                }

                                if (submitForm.is_sku == 1) {
                                    delete submitForm.price;
                                    delete submitForm.original_price;
                                    delete submitForm.cost_price;
                                    delete submitForm.stock_show_type
                                    delete submitForm.stock;
                                    delete submitForm.stock_warning;
                                    delete submitForm.weight;
                                    delete submitForm.sn;
                                }

                                submitForm.content = $("#goodsContent").val()

                                if (state.type == 'copy') {
                                    delete submitForm.id;
                                }

                                // 虚拟商品is_offline=0
                                if (submitForm.type == 'virtual') {
                                    submitForm.is_offline = 0
                                }
                                
                                Fast.api.ajax({
                                    url: state.type == 'add' || state.type == 'copy' ? 'shopro/goods/goods/add' : `shopro/goods/goods/edit/id/${state.id}`,
                                    type: 'POST',
                                    data: submitForm
                                }, function (ret, res) {
                                    Fast.api.close()
                                }, function (ret, res) { })
                            }
                        }, 500)

                    }

                    onBeforeMount(() => {
                        getCategorySelect(true)
                        getServiceSelect()
                    })

                    return {
                        state,
                        form,
                        onChangeGoodsType,
                        categoryRef,
                        setCategoryRef,
                        tempCategory,
                        onChangeCategoryIds,
                        onDeleteCategoryIds,
                        onClearCategoryIds,
                        category,
                        onAddCategory,
                        dispatch,
                        getDispatchSelect,
                        onAddDispatch,
                        onChangeDispatchType,
                        service,
                        onAddService,
                        onBack,
                        onNext,
                        validateData,
                        isValidate,
                        isEditInit,
                        getInit,
                        skuModal,
                        countId,
                        addMainSku,
                        deleteMainSku,
                        isResetSku,
                        childrenModal,
                        addChildrenSku,
                        deleteChildrenSku,
                        buildSkuPriceTable,
                        recursionSku,
                        batchPopover,
                        onbatchPopover,
                        onChangeStockWarningSwitch,
                        paramsRules,
                        onAddParams,
                        onDeleteParams,
                        onSuccess,
                        onConfirm
                    }
                }
            }
            createApp('addEdit', addEdit);
        },
        select: () => {
            const { reactive, onMounted, watch, getCurrentInstance, nextTick } = Vue
            const select = {
                setup() {
                    const { proxy } = getCurrentInstance();

                    const state = reactive({
                        data_type: new URLSearchParams(location.search).get('data_type'),
                        multiple: new URLSearchParams(location.search).get('multiple') || false,
                        max: new URLSearchParams(location.search).get('max') || 0,
                        ids: new URLSearchParams(location.search).get('ids'), // 选中的商品ids
                        goods_ids: new URLSearchParams(location.search).get('goods_ids'), // 需要搜索的商品id列表
                        data: [],
                        filter: {
                            drawer: false,
                            data: {
                                category_ids: '',
                                keyword: '',
                                price: {
                                    min: '',
                                    max: '',
                                },
                            },
                            tools: {},
                        },
                    })
                    state.ids = state.ids ? state.ids.split(',') : []

                    const category = reactive({
                        id: '',
                        select: [],
                        detail: []
                    })
                    function getCategorySelect() {
                        Fast.api.ajax({
                            url: 'shopro/category/select',
                            type: 'GET',
                        }, function (ret, res) {
                            category.select = res.data
                            category.select.unshift({
                                children: [],
                                id: 'all',
                                name: '全部分类',
                            });
                            if (category.select.length > 0) {
                                category.id = category.select[0].id;
                                getCategoryDetail();
                            }
                            return false
                        }, function (ret, res) { })
                    }
                    function getCategoryDetail() {
                        const data = category.select.find((item) => item.id == category.id);
                        category.detail = data.children || [];
                        state.filter.data.category_ids = data.id;
                    }
                    function changeCategoryIds(data) {
                        state.filter.data.category_ids = data.id;
                    }

                    function getData() {
                        let tempSearch = JSON.parse(JSON.stringify(state.filter.data));
                        if (tempSearch.price.min && tempSearch.price.max) {
                            tempSearch.price = `${tempSearch.price.min} - ${tempSearch.price.max}`;
                        }
                        // activity_type 搜索
                        if (state.activity_type) {
                            tempSearch.activity_type = state.activity_type;
                        }
                        // id 搜索
                        if (state.goods_ids) {
                            tempSearch.goods = { field: 'id', value: state.goods_ids };
                        }
                        let search = composeFilter(tempSearch, {
                            keyword: 'like',
                            price: 'between',
                        });
                        Fast.api.ajax({
                            url: 'shopro/goods/goods/select',
                            type: 'GET',
                            data: {
                                data_type: state.data_type,
                                type: 'page',
                                page: pagination.page,
                                list_rows: pagination.list_rows,
                                ...search,
                            },
                        }, function (ret, res) {
                            state.data = res.data.data
                            pagination.total = res.data.total

                            nextTick(() => {
                                state.data.forEach((l) => {
                                    if (state.ids?.includes(l.id + '')) {
                                        proxy.$refs['multipleTableRef']?.toggleRowSelection(l, true);
                                        toggleRowSelection('row', [l], l);
                                    }
                                });
                            });
                            return false
                        }, function (ret, res) { })
                    }

                    watch(() => state.filter.data, () => {
                        pagination.page = 1;
                        getData()
                    }, {
                        deep: true,
                    })

                    const pagination = reactive({
                        page: 1,
                        list_rows: 10,
                        total: 0,
                    })

                    function onSelect(selection, row) {
                        if (
                            !state.max ||
                            (state.max && state.max > state.ids.length)
                        ) {
                            if (state.ids.includes(row.id + '')) {
                                let index = state.ids.findIndex((id) => id == row.id);
                                state.ids.splice(index, 1);
                            } else {
                                state.ids.push(row.id);
                            }
                        }
                        toggleRowSelection('row', selection, row);
                    }
                    function onSelectAll(selection) {
                        if (
                            !state.max ||
                            (state.max && state.max > state.ids.length + selection.length)
                        ) {
                            if (selection.length == 0) {
                                state.data.forEach((l) => {
                                    if (state.ids.includes(l.id)) {
                                        let index = state.ids.findIndex((id) => id == l.id);
                                        state.ids.splice(index, 1);
                                    }
                                });
                            } else {
                                state.data.forEach((l) => {
                                    if (!state.ids.includes(l.id)) {
                                        state.ids.push(l.id);
                                    }
                                });
                            }
                        }
                        toggleRowSelection('all', selection);
                    }
                    function toggleRowSelection(type, selection, row) {
                        // 限制数量
                        if (state.max && state.max < selection.length) {
                            if (type == 'row') {
                                proxy.$refs['multipleTableRef'].toggleRowSelection(row, false);
                            } else if (type == 'all') {
                                proxy.$refs['multipleTableRef']?.clearSelection();
                                state.data.forEach((l) => {
                                    if (state.ids?.includes(l.id)) {
                                        proxy.$refs['multipleTableRef']?.toggleRowSelection(l, true);
                                    }
                                });
                            }
                            ElMessage({
                                type: 'warning',
                                message: '已到选择上限',
                            });
                            return false;
                        }
                    }

                    function onSingleSelect(item) {
                        Fast.api.close(item)
                    }

                    function onConfirm() {
                        let ids = state.ids.join(',')
                        Fast.api.ajax({
                            url: 'shopro/goods/goods/select',
                            type: 'GET',
                            data: {
                                type: 'select',
                                search: JSON.stringify({ id: [ids, 'in'] })
                            },
                        }, function (ret, res) {
                            Fast.api.close(res.data)
                            return false
                        }, function (ret, res) { })
                    }

                    onMounted(() => {
                        getCategorySelect()
                        getData()
                    })

                    return {
                        state,
                        category,
                        getCategoryDetail,
                        getData,
                        changeCategoryIds,
                        pagination,
                        onSelect,
                        onSelectAll,
                        onSingleSelect,
                        onConfirm
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
                            url: 'shopro/goods/goods/recyclebin',
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
                            url: `shopro/goods/goods/restore/id/${id}`,
                            type: 'POST',
                        }, function (ret, res) {
                            getData()
                        }, function (ret, res) { })
                    }
                    function onDestroy(id) {
                        Fast.api.ajax({
                            url: `shopro/goods/goods/destroy/id/${id}`,
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
        addstock: () => {
            const { reactive, onMounted, getCurrentInstance, watch } = Vue
            const addStock = {
                setup() {
                    const { proxy } = getCurrentInstance();

                    const state = reactive({
                        id: new URLSearchParams(location.search).get('id'),
                        is_sku: new URLSearchParams(location.search).get('is_sku'),
                        stock: new URLSearchParams(location.search).get('stock') || 0,
                    })

                    const form = reactive({
                        model: {
                            add_stock: '',
                            sku_prices: [
                                {
                                    id: '',
                                    goods_sku_text: [],
                                    add_stock: '',
                                },
                            ],
                        },
                        rules: {
                            add_stock: [
                                { required: true, message: '请输入补充库存', trigger: 'change' },
                                {
                                    pattern: /^(-)?[1-9][0-9]*$/,
                                    message: '只能输入正整数、负整数',
                                    trigger: 'change',
                                },
                            ],
                        },
                    })

                    const batchPopover = reactive({
                        flag: false,
                        add_stock: '',
                    })
                    function onBatchPopover(type) {
                        if (type == 'cancel') {
                            batchPopover.add_stock = '';
                            batchPopover.flag = false;
                        } else {
                            form.model.sku_prices.forEach((i) => {
                                i.add_stock = batchPopover.add_stock;
                            });
                            batchPopover.add_stock = '';
                            batchPopover.flag = false;
                        }
                    }

                    function getSkuPrice() {
                        Fast.api.ajax({
                            url: `shopro/goods/sku_price?goods_id=${state.id}`,
                            type: 'GET',
                        }, function (ret, res) {
                            form.model.sku_prices = res.data;
                            return false
                        }, function (ret, res) { })
                    }

                    function onConfirm() {
                        proxy.$refs['formRef'].validate((valid) => {
                            if (valid) {
                                Fast.api.ajax({
                                    url: `shopro/goods/goods/addStock/id/${state.id}`,
                                    type: 'POST',
                                    data: JSON.parse(JSON.stringify(form.model))
                                }, function (ret, res) {
                                    Fast.api.close()
                                }, function (ret, res) { })
                            }
                        });
                    }

                    onMounted(() => {
                        getSkuPrice()
                    })

                    return {
                        state,
                        form,
                        batchPopover,
                        onBatchPopover,
                        getSkuPrice,
                        onConfirm
                    }
                }
            }
            createApp('addStock', addStock);
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
        },
    };
    return Controller;
});
