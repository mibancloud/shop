define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        data: () => {
            return {
                onClipboard,
                dispatchStyle: (item) => {
                    let data = {
                        class: '',
                        text: item.dispatch_status_text
                    }
                    switch (item.dispatch_status) {
                        case 0: // 未发货
                            data.class = 'info';
                            data.text = '待发货'
                            break;
                        case 1: // 已发货
                            data.class = 'warning';
                            break;
                        case 2: // 已收货
                            data.class = 'success';
                            break;
                    }
                    return data
                },
                refundStyle: (item) => {
                    let data = {
                        class: '',
                        text: item.refund_status_text
                    }
                    switch (item.refund_status) {
                        case 0: // 主动退款
                            data.class = 'info';
                            if (item.btns?.includes('refund')) {
                                data.text = '主动退款'
                            }
                            break;
                        case 1: // 同意退款
                            data.class = 'success';
                            break;
                        case 2: // 退款完成
                            data.class = 'success';
                            break;
                    }
                    return data
                },
                aftersaleStyle: (item) => {
                    let data = {
                        class: '',
                        text: ''
                    }
                    switch (item.aftersale_status) {
                        case -1: // 拒绝
                            data.class = 'danger';
                            break;
                        case 0: // 未申请
                            data.class = 'info';
                            break;
                        case 1: // 申请售后
                            data.class = 'warning';
                            break;
                        case 2: // 售后完成
                            data.class = 'success';
                            break;
                    }
                    data.text = item.aftersale_status_text;
                    return data
                },
            }
        },
        index: () => {
            const { ref, reactive, onMounted } = Vue
            const index = {
                setup() {
                    const state = reactive({
                        status: new URLSearchParams(location.search).get('status'),
                        createtime: JSON.parse(new URLSearchParams(location.search).get('createtime')) || [],
                        data: [],
                        filter: {
                            drawer: false,
                            data: {
                                status: 'all',
                                order: { field: 'id', value: '' },
                                user: { field: 'user_id', value: '' },
                                type: '',
                                platform: '',
                                'pay.pay_type': '',
                                activity_type: '',
                                promo_types: '',
                                'item.goods_title': '',
                                createtime: [],
                            },
                            tools: {
                                order: {
                                    type: 'tinputprepend',
                                    label: '查询内容',
                                    placeholder: '请输入查询内容',
                                    value: {
                                        field: 'id',
                                        value: '',
                                    },
                                    options: {
                                        data: [{
                                            label: '订单ID',
                                            value: 'id',
                                        },
                                        {
                                            label: '订单编号',
                                            value: 'order_sn',
                                        },
                                        {
                                            label: '售后单号',
                                            value: 'aftersale.aftersale_sn',
                                        },
                                        {
                                            label: '支付单号',
                                            value: 'pay.pay_sn',
                                        },
                                        {
                                            label: '交易流水号',
                                            value: 'pay.transaction_id',
                                        }]
                                    },
                                },
                                user: {
                                    type: 'tinputprepend',
                                    label: '查询内容',
                                    placeholder: '请输入查询内容',
                                    value: {
                                        field: 'user_id',
                                        value: '',
                                    },
                                    options: {
                                        data: [{
                                            label: '用户ID',
                                            value: 'user_id',
                                        },
                                        {
                                            label: '用户昵称',
                                            value: 'user.nickname',
                                        },
                                        {
                                            label: '用户手机号',
                                            value: 'user.mobile',
                                        },
                                        {
                                            label: '收货人',
                                            value: 'address.consignee',
                                        },
                                        {
                                            label: '收货人手机号',
                                            value: 'address.mobile',
                                        }],
                                    }
                                },
                                type: {
                                    type: 'tselect',
                                    label: '订单类型',
                                    value: '',
                                    options: {
                                        data: [],
                                        props: {
                                            label: 'name',
                                            value: 'type',
                                        },
                                    },
                                },
                                platform: {
                                    type: 'tselect',
                                    label: '订单来源',
                                    value: '',
                                    options: {
                                        data: [],
                                        props: {
                                            label: 'name',
                                            value: 'type',
                                        },
                                    },
                                },
                                'pay.pay_type': {
                                    type: 'tselect',
                                    label: '支付方式',
                                    value: '',
                                    options: {
                                        data: [],
                                        props: {
                                            label: 'name',
                                            value: 'type',
                                        },
                                    },
                                },
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
                                promo_types: {
                                    type: 'tselect',
                                    label: '促销类型',
                                    value: '',
                                    options: {
                                        data: [],
                                        props: {
                                            label: 'name',
                                            value: 'type',
                                        },
                                    },
                                },
                                'item.goods_title': {
                                    type: 'tinput',
                                    label: '商品名称',
                                    value: '',
                                },
                                createtime: {
                                    type: 'tdatetimerange',
                                    label: '下单时间',
                                    value: [],
                                },
                            },
                            condition: {},
                        }
                    })

                    if (state.status) {
                        state.filter.data.status = state.status
                    }
                    if (state.createtime.length > 0) {
                        state.filter.data.createtime = state.createtime
                        state.filter.tools.createtime.value = state.createtime
                    }

                    const type = reactive({
                        data: {}
                    })
                    function getType() {
                        Fast.api.ajax({
                            url: 'shopro/order/order/getType',
                            type: 'GET',
                        }, function (ret, res) {
                            type.data = res.data
                            for (key in res.data) {
                                if (key == 'pay_type') {
                                    state.filter.tools['pay.pay_type'].options.data = res.data[key]
                                } else if (key == 'status' || key == 'apply_refund_status') {
                                } else {
                                    state.filter.tools[key].options.data = res.data[key]
                                }
                            }
                            getData()
                            return false
                        }, function (ret, res) { })
                    }

                    function getData() {
                        let tempSearch = JSON.parse(JSON.stringify(state.filter.data));
                        let search = composeFilter(tempSearch, {
                            order_sn: 'like',
                            'aftersale.aftersale_sn': 'like',
                            'pay.pay_sn': 'like',
                            'pay.transaction_id': 'like',
                            'user.nickname': 'like',
                            'user.mobile': 'like',
                            'address.consignee': 'like',
                            'address.mobile': 'like',
                            promo_types: 'find_in_set',
                            'item.goods_title': 'like',
                            createtime: 'range',
                        });
                        Fast.api.ajax({
                            url: 'shopro/order/order',
                            type: 'GET',
                            data: {
                                page: pagination.page,
                                list_rows: pagination.list_rows,
                                ...search,
                            },
                        }, function (ret, res) {
                            state.data = res.data.orders.data
                            pagination.total = res.data.orders.total
                            type.data?.status.forEach(item => {
                                item.num = res.data[item.type]
                            })
                            return false
                        }, function (ret, res) { })
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
                            case 'dispatch':
                                Fast.api.open(`shopro/order/order/batchDispatch?order_ids=${ids.join(',')}`, "批量发货", {
                                    callback() {
                                        getData()
                                    }
                                })
                                break;
                        }
                    }

                    const spanMethod = ({ row, column, rowIndex, columnIndex }) => {
                        if (columnIndex == 2) {
                            return [1, 4];
                        } else if (columnIndex == 3 || columnIndex == 4 || columnIndex == 5) {
                            return [0, 0];
                        }
                    };

                    const spanMethodExpand = ({ row, column, rowIndex, columnIndex }) => {
                        if (columnIndex == 0) {
                            return [0, 0];
                        }
                        if (columnIndex == 1) {
                            return [1, 2];
                        }
                        if (columnIndex == 3 || columnIndex == 4 || columnIndex == 7) {
                            if (rowIndex == 0) {
                                return {
                                    rowspan: 200,
                                    colspan: 1,
                                };
                            } else {
                                return {
                                    rowspan: 0,
                                    colspan: 0,
                                };
                            }
                        }
                    };

                    function statusCode(item) {
                        let data = {
                            class: '',
                            text: item.status_text,
                        }
                        switch (item.status_code) {
                            case 'cancel': // 已取消
                                data.class = 'status-danger';
                                break;
                            case 'closed': // 交易关闭
                                data.class = 'status-danger';
                                break;
                            case 'unpaid': // 待付款
                                data.class = 'status-info';
                                break;
                            case 'nosend': // 待发货
                                data.class = 'status-warning';
                                break;
                            case 'noget': // 待收货
                                data.class = 'status-warning';
                                break;
                            case 'nocomment': // 待评价
                                data.class = 'status-warning';
                                break;
                            case 'commented': // 已评价
                                data.class = 'status-success';
                                break;
                            case 'apply_refund': //申请退款中
                                data.class = 'status-danger';
                                break;
                            case 'refund_completed': // 退款完成
                                data.class = 'status-success';
                                break;
                            case 'refund_agree': // 退款已同意
                                data.class = 'status-success';
                                break;
                            case 'groupon_ing': // 等待成团
                                data.class = 'status-warning';
                                break;
                            case 'groupon_invalid': // 拼团失败
                                data.class = 'status-danger';
                                break;
                            case 'completed': // 交易完成
                                data.class = 'status-success';
                                break;
                        }
                        return data
                    }

                    function onAftersale(item) {
                        Fast.api.open(`shopro/order/aftersale/detail/id/${item.ext?.aftersale_id}?id=${item.ext?.aftersale_id}`, "售后详情", {
                            callback() {
                                getData()
                            }
                        })
                    }

                    const exportLoading = ref(false);
                    function onExport(type) {
                        exportLoading.value = true;
                        let tempSearch = JSON.parse(JSON.stringify(state.filter.data));
                        let search = composeFilter(tempSearch, {
                            order_sn: 'like',
                            'aftersale.aftersale_sn': 'like',
                            'pay.pay_sn': 'like',
                            'pay.transaction_id': 'like',
                            'user.nickname': 'like',
                            'user.mobile': 'like',
                            'address.consignee': 'like',
                            'address.mobile': 'like',
                            promo_types: 'find_in_set',
                            'item.goods_title': 'like',
                            createtime: 'range',
                        });

                        if (Config.save_type == 'download') {
                            window.location.href = `${Config.moduleurl}/shopro/order/order/${type}?page=${pagination.page}&list_rows=${pagination.list_rows}&search=${search.search}`;
                            exportLoading.value = false;
                        } else if (Config.save_type == 'save') {
                            Fast.api.ajax({
                                url: `shopro/order/order/${type}`,
                                type: 'GET',
                                data: {
                                    page: pagination.page,
                                    list_rows: pagination.list_rows,
                                    ...search,
                                },
                            }, function (ret, res) {
                                exportLoading.value = false;
                            }, function (ret, res) { })
                        }
                    }

                    // 订单改价
                    function onChangeFee(item) {
                        Fast.api.open(`shopro/order/order/changeFee/id/${item.id}?id=${item.id}&pay_fee=${item.pay_fee}`, "改价", {
                            callback() {
                                getData()
                            }
                        })
                    }

                    const refundPopover = reactive({});
                    function onApplyRefundRefuse(id, index) {
                        refundPopover[index] = false;
                        Fast.api.ajax({
                            url: `shopro/order/order/applyRefundRefuse/id/${id}`,
                            type: 'POST',
                        }, function (ret, res) {
                            getData();
                        }, function (ret, res) { })
                    }
                    function onFullRefund(item, index) {
                        refundPopover[index] = false;
                        Fast.api.open(`shopro/order/order/fullRefund/id/${item.id}?id=${item.id}`, "同意用户申请退款", {
                            callback() {
                                getData()
                            }
                        })
                    }

                    // 立即发货
                    function onDispatch(id) {
                        Fast.api.open(`shopro/order/order/dispatch?id=${id}`, "立即发货", {
                            callback() {
                                getData()
                            }
                        })
                    }

                    const confirmPopover = reactive({});
                    function onOfflineRefuse(id, index) {
                        confirmPopover[index] = false;
                        Fast.api.ajax({
                            url: `shopro/order/order/offlineRefuse/id/${id}`,
                            type: 'POST',
                        }, function (ret, res) {
                            getData();
                        }, function (ret, res) { })
                    }
                    function onOfflineConfirm(id, index) {
                        confirmPopover[index] = false;
                        Fast.api.ajax({
                            url: `shopro/order/order/offlineConfirm/id/${id}`,
                            type: 'POST',
                        }, function (ret, res) {
                            getData();
                        }, function (ret, res) { })
                    }

                    function onDetail(id) {
                        Fast.api.open(`shopro/order/order/detail/id/${id}?id=${id}`, "详情", {
                            callback() {
                                getData()
                            }
                        })
                    }

                    function onAction(id) {
                        Fast.api.open(`shopro/order/order/action/id/${id}?id=${id}`, "日志", {
                            callback() {
                                getData()
                            }
                        })
                    }

                    onMounted(() => {
                        getType()
                    })

                    return {
                        ...Controller.data(),
                        state,
                        type,
                        getData,
                        onOpenFilter,
                        onChangeFilter,
                        onChangeTab,
                        pagination,
                        batchHandle,
                        onChangeSelection,
                        onBatchHandle,
                        spanMethod,
                        spanMethodExpand,
                        statusCode,
                        onAftersale,
                        exportLoading,
                        onExport,
                        onChangeFee,
                        refundPopover,
                        onApplyRefundRefuse,
                        onFullRefund,
                        onDispatch,
                        confirmPopover,
                        onOfflineRefuse,
                        onOfflineConfirm,
                        onDetail,
                        onAction,
                    }
                }
            }
            createApp('index', index);
        },
        detail: () => {
            const { ref, reactive, onMounted, getCurrentInstance } = Vue
            const detail = {
                setup() {
                    const { proxy } = getCurrentInstance();

                    const state = reactive({
                        id: new URLSearchParams(location.search).get('id'),
                        detail: {},
                        stepActive: 1,
                    })

                    function getDetail() {
                        Fast.api.ajax({
                            url: `shopro/order/order/detail/id/${state.id}`,
                            type: 'GET',
                        }, function (ret, res) {
                            state.detail = res.data;
                            setStepActive()
                            return false
                        }, function (ret, res) { })
                    }

                    function onDispatch() {
                        Fast.api.open(`shopro/order/order/dispatch?id=${state.id}`, "立即发货", {
                            callback() {
                                getDetail()
                            }
                        })
                    }

                    function onFullRefund() {
                        Fast.api.open(`shopro/order/order/fullRefund/id/${state.id}?id=${state.id}`, "全部退款", {
                            callback() {
                                getDetail()
                            }
                        })
                    }

                    // 备注
                    const memo = reactive({
                        flag: false,
                        form: {
                            memo: ''
                        },
                    });
                    function onChangeMemoEdit(val) {
                        memo.flag = true;
                        memo.form.memo = val;
                    }
                    function onConfirmMemo() {
                        Fast.api.ajax({
                            url: `shopro/order/order/editMemo/id/${state.id}`,
                            type: 'POST',
                            data: memo.form,
                        }, function (ret, res) {
                            memo.flag = false;
                            getDetail();
                        }, function (ret, res) { })
                    }

                    function setStepActive() {
                        if (state.detail.status == 'unpaid') {
                            state.stepActive = 1;
                        } else if (state.detail.status == 'paid' || (state.detail.status == 'pending' && state.detail.pay_mode == 'offline')) {
                            state.stepActive = 2;
                            switch (state.detail.status_code) {
                                case 'nosend':
                                    state.stepActive = 2;
                                    break;
                                case 'noget':
                                    state.stepActive = 3;
                                    break;
                                case 'nocomment':
                                    state.stepActive = 4;
                                    break;
                                case 'commented':
                                    state.stepActive = 4;
                                    break;
                            }
                        } else if (state.detail.status == 'completed') {
                            state.stepActive = 5;
                        }
                    }

                    // 订单改价
                    function onChangeFee(item) {
                        Fast.api.open(`shopro/order/order/changeFee/id/${item.id}?id=${item.id}&pay_fee=${item.pay_fee}`, "改价", {
                            callback() {
                                getDetail()
                            }
                        })
                    }

                    // 订单
                    const orderTab = ref('1')
                    function onChangeTabOrder() {
                        express.isEdit = false
                    }

                    // 编辑订单收货地址
                    const address = reactive({
                        flag: false,
                        form: {},
                    })
                    function onChangeAddressEdit(type) {
                        if (state.detail.address) {
                            address.form = {
                                consignee: state.detail.address.consignee,
                                mobile: state.detail.address.mobile,
                                pcd: [
                                    state.detail.address.province_id,
                                    state.detail.address.city_id,
                                    state.detail.address.district_id,
                                ],
                                address: state.detail.address.address,
                            };
                        }
                        address.flag = type;
                        getAreaSelect()
                    }
                    function onConfirmAddress() {
                        let label = proxy.$refs['addressRef'].getCheckedNodes()[0].pathLabels;
                        let tempForm = JSON.parse(JSON.stringify(address.form));
                        tempForm = {
                            ...tempForm,
                            province_name: label[0],
                            province_id: address.form.pcd[0],
                            city_name: label[1],
                            city_id: address.form.pcd[1],
                            district_name: label[2],
                            district_id: address.form.pcd[2],
                        };
                        delete tempForm.pcd;
                        Fast.api.ajax({
                            url: `shopro/order/order/editConsignee/id/${state.id}`,
                            type: 'POST',
                            data: tempForm,
                        }, function (ret, res) {
                            getDetail();
                            address.flag = false;
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

                    function onInvoice(item) {
                        let params = {
                            id: item.id,
                            order_status_text: item.order_status_text,
                            order_fee: item.order_fee,
                            amount: item.amount,
                        }
                        Fast.api.open(`shopro/order/invoice/confirm/id/${item.id}?item=${encodeURI(JSON.stringify(params))}`, "确认开具", {
                            callback() {
                                getDetail()
                            }
                        })
                    }

                    const express = reactive({
                        form: {
                            model: {}
                        },
                        isEdit: false
                    })
                    function onChangeExpressEdit(item) {
                        express.isEdit = true;
                        express.form.model.name = item.express_name;
                        express.form.model.code = item.express_code;
                        express.form.model.no = item.express_no;
                        getExpressSelect()
                    }
                    function onConfirmExpress(order_express_id) {
                        Fast.api.ajax({
                            url: 'shopro/order/order/dispatch',
                            type: 'POST',
                            data: {
                                order_id: state.id,
                                order_express_id,
                                action: 'change',
                                express: express.form.model,
                            }
                        }, function (ret, res) {
                            express.isEdit = false;
                            getDetail();
                        }, function (ret, res) { })
                    }
                    function onCancelExpress(order_express_id) {
                        Fast.api.ajax({
                            url: 'shopro/order/order/dispatch',
                            type: 'POST',
                            data: {
                                order_id: state.id,
                                order_express_id,
                                action: 'cancel',
                            }
                        }, function (ret, res) {
                            getDetail();
                        }, function (ret, res) { })
                    }

                    const deliverCompany = reactive({
                        loading: false,
                        select: [],
                        pagination: {
                            page: 1,
                            list_rows: 10,
                            total: 0,
                        }
                    })
                    function getExpressSelect(keyword) {
                        let search = {};
                        if (keyword) {
                            search = { keyword: keyword };
                        }
                        Fast.api.ajax({
                            url: 'shopro/data/express',
                            type: 'GET',
                            data: {
                                page: deliverCompany.pagination.page,
                                list_rows: deliverCompany.pagination.list_rows,
                                search: JSON.stringify(search),
                            }
                        }, function (ret, res) {
                            deliverCompany.select = res.data.data
                            deliverCompany.pagination.total = res.data.total
                            return false
                        }, function (ret, res) { })
                    }
                    function onChangeExpressCode(code) {
                        express.form.model.name = proxy.$refs[`express-${code}`][0].label;
                    }
                    function remoteMethod(keyword) {
                        deliverCompany.loading = true;
                        setTimeout(() => {
                            deliverCompany.loading = false;
                            getExpressSelect(keyword);
                        }, 200);
                    }

                    function onUpdateExpress(order_express_id, type) {
                        Fast.api.ajax({
                            url: `shopro/order/order/updateExpress/order_express_id/${order_express_id}`,
                            type: 'GET',
                            data: {
                                type,
                            }
                        }, function (ret, res) {
                            getDetail();
                        }, function (ret, res) { })
                    }

                    function onRefund(item) {
                        Fast.api.open(`shopro/order/order/refund/id/${state.id}/item_id/${item.id}?id=${state.id}&item_id=${item.id}&suggest_refund_fee=${item.suggest_refund_fee}`, "主动退款", {
                            callback() {
                                getDetail()
                            }
                        })
                    }

                    function onAftersale(item) {
                        Fast.api.open(`shopro/order/aftersale/detail/id/${item.ext?.aftersale_id}?id=${item.ext?.aftersale_id}`, "售后详情", {
                            callback() {
                                getDetail()
                            }
                        })
                    }

                    function onComment(item) {
                        Fast.api.open(`shopro/goods/comment/index?order_id=${state.id}&order_item_id=${item.id}`, "查看评价", {
                            callback() {
                                getDetail()
                            }
                        })
                    }

                    function onConfirm() {
                        proxy.$refs['formRef'].validate((valid) => {
                            if (valid) {
                                Fast.api.ajax({
                                    url: state.type == 'add' ? 'shopro/order/order/add' : `shopro/order/order/edit/id/${state.id}`,
                                    type: 'POST',
                                    data: JSON.parse(JSON.stringify(form.model))
                                }, function (ret, res) {
                                    Fast.api.close()
                                }, function (ret, res) { })
                            }
                        });
                    }

                    onMounted(() => {
                        getDetail()
                    })

                    return {
                        ...Controller.data(),
                        state,
                        getDetail,
                        onDispatch,
                        onFullRefund,
                        memo,
                        onChangeMemoEdit,
                        onConfirmMemo,
                        setStepActive,
                        onChangeFee,
                        orderTab,
                        onChangeTabOrder,
                        address,
                        onChangeAddressEdit,
                        onConfirmAddress,
                        area,
                        getAreaSelect,
                        onInvoice,
                        express,
                        onChangeExpressEdit,
                        onConfirmExpress,
                        onCancelExpress,
                        deliverCompany,
                        getExpressSelect,
                        onChangeExpressCode,
                        remoteMethod,
                        onUpdateExpress,
                        onRefund,
                        onAftersale,
                        onComment,
                        onConfirm
                    }
                }
            }
            createApp('detail', detail);
        },
        fullrefund: () => {
            const { reactive, getCurrentInstance } = Vue
            const fullRefund = {
                setup() {
                    const { proxy } = getCurrentInstance();

                    const state = reactive({
                        id: new URLSearchParams(location.search).get('id'),
                    })

                    const form = reactive({
                        model: {
                            refund_type: 'back',
                        },
                        rules: {},
                    })

                    function onConfirm() {
                        proxy.$refs['formRef'].validate((valid) => {
                            if (valid) {
                                Fast.api.ajax({
                                    url: `shopro/order/order/fullRefund/id/${state.id}`,
                                    type: 'POST',
                                    data: form.model,
                                }, function (ret, res) {
                                    Fast.api.close()
                                }, function (ret, res) { })
                            }
                        });
                    }

                    return {
                        state,
                        form,
                        onConfirm
                    }
                }
            }
            createApp('fullRefund', fullRefund);
        },
        dispatch: () => {
            const { reactive, onMounted, getCurrentInstance } = Vue
            const dispatch = {
                setup() {
                    const { proxy } = getCurrentInstance();

                    const state = reactive({
                        id: new URLSearchParams(location.search).get('id'),
                        detail: {},
                        nosendItem: [],
                        dispatch_type: 'express',
                        customItem: [],
                        custom_type: 'text',
                        custom_content: [],
                    })

                    function getDetail() {
                        Fast.api.ajax({
                            url: `shopro/order/order/detail/id/${state.id}`,
                            type: 'GET',
                        }, function (ret, res) {
                            state.detail = res.data;
                            state.nosendItem = state.detail.items.filter(
                                (i) => i.dispatch_status == 0 && i.refund_status == 0 && i.dispatch_type == 'express',
                            )
                            state.customItem = state.detail.items.filter(
                                (i) => i.dispatch_status == 0 && i.refund_status == 0 && i.dispatch_type == 'custom',
                            )
                            if (state.nosendItem.length) {
                                state.dispatch_type = 'express'
                            } else if (state.customItem.length) {
                                state.dispatch_type = 'custom'
                            }
                            return false
                        }, function (ret, res) { })
                    }

                    const batchHandle = reactive({
                        data: [],
                    })
                    function onChangeSelection(val) {
                        batchHandle.data = val
                    }

                    const express = reactive({
                        method: 'input',
                        form: {
                            model: {
                                name: '',
                                no: '',
                                code: ''
                            },
                            rules: {
                                code: [{ required: true, message: '请选择', trigger: 'none' }],
                                no: [{ required: true, message: '请输入快递单号', trigger: 'none' }],
                            },
                        },
                    })

                    const deliverCompany = reactive({
                        loading: false,
                        select: [],
                        pagination: {
                            page: 1,
                            list_rows: 10,
                            total: 0,
                        }
                    })
                    function getExpressSelect(keyword) {
                        let search = {};
                        if (keyword) {
                            search = { keyword: keyword };
                        }
                        Fast.api.ajax({
                            url: 'shopro/data/express',
                            type: 'GET',
                            data: {
                                page: deliverCompany.pagination.page,
                                list_rows: deliverCompany.pagination.list_rows,
                                search: JSON.stringify(search),
                            }
                        }, function (ret, res) {
                            deliverCompany.select = res.data.data
                            deliverCompany.pagination.total = res.data.total
                            return false
                        }, function (ret, res) { })
                    }
                    function onChangeExpressCode(code) {
                        express.form.model.name = proxy.$refs[`express-${code}`][0].label;
                    }
                    function remoteMethod(keyword) {
                        deliverCompany.loading = true;
                        setTimeout(() => {
                            deliverCompany.loading = false;
                            getExpressSelect(keyword);
                        }, 200);
                    }

                    function onChangeAutosendType(type) {
                        state.custom_content = type == 'text' ? '' : []
                    }
                    function onAddContent() {
                        if (!state.custom_content) {
                            state.custom_content = []
                        }
                        state.custom_content.push({
                            title: '',
                            content: '',
                        });
                    }
                    function onDeleteContent(index) {
                        state.custom_content.splice(index, 1);
                    }

                    function onConfirm() {
                        let order_item_ids = [];
                        batchHandle.data.forEach((item) => {
                            order_item_ids.push(item.id);
                        });
                        if (state.dispatch_type == 'express') {
                            if (express.method == 'input') {
                                proxy.$refs['expressRef'].validate((valid) => {
                                    if (valid) {
                                        Fast.api.ajax({
                                            url: 'shopro/order/order/dispatch',
                                            type: 'POST',
                                            data: {
                                                order_id: state.id,
                                                order_item_ids,
                                                action: 'confirm',
                                                method: 'input',
                                                express: express.form.model,
                                            }
                                        }, function (ret, res) {
                                            Fast.api.close()
                                        }, function (ret, res) { })
                                    }
                                });
                            } else if (express.method == 'api') {
                                Fast.api.ajax({
                                    url: 'shopro/order/order/dispatch',
                                    type: 'POST',
                                    data: {
                                        order_id: state.id,
                                        order_item_ids,
                                        action: 'confirm',
                                        method: 'api',
                                    }
                                }, function (ret, res) {
                                    Fast.api.close()
                                }, function (ret, res) { })
                            }
                        } else if (state.dispatch_type == 'custom') {
                            Fast.api.ajax({
                                url: 'shopro/order/order/customDispatch',
                                type: 'POST',
                                data: {
                                    order_id: state.id,
                                    order_item_ids,
                                    custom_type: state.custom_type,
                                    custom_content: state.custom_content,
                                }
                            }, function (ret, res) {
                                Fast.api.close()
                            }, function (ret, res) { })
                        }
                    }

                    onMounted(() => {
                        getExpressSelect()
                        getDetail()
                    })

                    return {
                        state,
                        batchHandle,
                        onChangeSelection,
                        express,
                        deliverCompany,
                        getExpressSelect,
                        onChangeExpressCode,
                        remoteMethod,
                        onChangeAutosendType,
                        onAddContent,
                        onDeleteContent,
                        onConfirm
                    }
                }
            }
            createApp('dispatch', dispatch);
        },
        action: () => {
            const { reactive, onMounted } = Vue
            const action = {
                setup() {
                    const state = reactive({
                        id: new URLSearchParams(location.search).get('id'),
                        data: [],
                    })

                    function getAction() {
                        Fast.api.ajax({
                            url: `shopro/order/order/action/id/${state.id}`,
                            type: 'GET',
                        }, function (ret, res) {
                            state.data = res.data
                            return false
                        }, function (ret, res) { })
                    }

                    onMounted(() => {
                        getAction()
                    })

                    return {
                        state,
                    }
                }
            }
            createApp('action', action);
        },
        changefee: () => {
            const { reactive, getCurrentInstance } = Vue
            const changeFee = {
                setup() {
                    const { proxy } = getCurrentInstance();

                    const state = reactive({
                        id: new URLSearchParams(location.search).get('id'),
                        pay_fee: new URLSearchParams(location.search).get('pay_fee'),
                    })

                    const form = reactive({
                        model: {
                            pay_fee: '',
                            change_msg: '',
                        },
                        rules: {
                            pay_fee: [{ required: true, message: '数值必须大于0', trigger: 'blur' }],
                            change_msg: [{ required: true, message: '请输入改价原因', trigger: 'blur' }],
                        },
                    })

                    function onConfirm() {
                        proxy.$refs['formRef'].validate((valid) => {
                            if (valid) {
                                Fast.api.ajax({
                                    url: `shopro/order/order/changeFee/id/${state.id}`,
                                    type: 'POST',
                                    data: form.model
                                }, function (ret, res) {
                                    Fast.api.close()
                                }, function (ret, res) { })
                            }
                        });
                    }

                    return {
                        state,
                        form,
                        onConfirm,
                    }
                }
            }
            createApp('changeFee', changeFee);
        },
        batchdispatch: () => {
            const { reactive, onMounted, getCurrentInstance } = Vue
            const batchDispatch = {
                setup() {
                    const { proxy } = getCurrentInstance();

                    const state = reactive({
                        order_ids: (new URLSearchParams(location.search).get('order_ids')).split(','),
                    })

                    const express = reactive({
                        method: 'input',
                        form: {
                            model: {
                                file: '',
                                name: '',
                                no: '',
                                code: ''
                            },
                            rules: {
                                file: [{ required: true, message: '请导入发货单', trigger: 'none' }],
                                code: [{ required: true, message: '请选择快递公司', trigger: 'none' }],
                                no: [{ required: true, message: '请输入快递单号', trigger: 'none' }],
                            },
                        },
                    })

                    const deliverCompany = reactive({
                        loading: false,
                        select: [],
                        pagination: {
                            page: 1,
                            list_rows: 10,
                            total: 0,
                        }
                    })
                    function getExpressSelect(keyword) {
                        let search = {};
                        if (keyword) {
                            search = { keyword: keyword };
                        }
                        Fast.api.ajax({
                            url: 'shopro/data/express',
                            type: 'GET',
                            data: {
                                page: deliverCompany.pagination.page,
                                list_rows: deliverCompany.pagination.list_rows,
                                search: JSON.stringify(search),
                            }
                        }, function (ret, res) {
                            deliverCompany.select = res.data.data
                            deliverCompany.pagination.total = res.data.total
                            return false
                        }, function (ret, res) { })
                    }
                    function onChangeExpressCode(code) {
                        express.form.model.name = proxy.$refs[`express-${code}`][0].label;
                    }
                    function remoteMethod(keyword) {
                        deliverCompany.loading = true;
                        setTimeout(() => {
                            deliverCompany.loading = false;
                            getExpressSelect(keyword);
                        }, 200);
                    }

                    function onSelectFile(file) {
                        express.form.model.file = file
                    }
                    function onBatchDispatch() {
                        proxy.$refs['expressRef'].validate((valid) => {
                            if (valid) {
                                const dispatchData = {
                                    action: 'multiple',
                                    'express[name]': express.form.model.name,
                                    'express[code]': express.form.model.code,
                                    file: express.form.model.file.raw,
                                };

                                let dispatchForm = new FormData();
                                for (let name in dispatchData) {
                                    dispatchForm.append(name, dispatchData[name]);
                                }

                                $.ajax({
                                    url: 'shopro/order/order/dispatch',
                                    type: 'POST',
                                    data: dispatchForm,
                                    cache: false,
                                    processData: false,
                                    contentType: false,
                                    success: function (res) {
                                        if (res.code == 0) {
                                            Toastr.error(res.msg);
                                        }
                                        if (res.code == 1) {
                                            localStorage.setItem("batch-dispatch", JSON.stringify(res.data));
                                            Fast.api.open(`shopro/order/order/dispatchList?method=upload`, "导入发货单发货", {
                                                callback: function () {
                                                    Fast.api.close()
                                                }
                                            });
                                        }
                                    },
                                })
                            }
                        });
                    }
                    function onDispatchList() {
                        Fast.api.ajax({
                            url: 'shopro/order/order/dispatch',
                            type: 'POST',
                            data: {
                                order_ids: state.order_ids,
                                action: 'multiple',
                            }
                        }, function (ret, res) {
                            localStorage.setItem("batch-dispatch", JSON.stringify(res.data));
                            Fast.api.open(`shopro/order/order/dispatchList?method=api`, "批量发货", {
                                callback: function () {
                                    Fast.api.close()
                                }
                            });
                            return false
                        }, function (ret, res) { })
                    }

                    onMounted(() => {
                        getExpressSelect()
                    })

                    return {
                        state,
                        express,
                        deliverCompany,
                        getExpressSelect,
                        onChangeExpressCode,
                        remoteMethod,
                        onSelectFile,
                        onBatchDispatch,
                        onDispatchList,
                    }
                }
            }
            createApp('batchDispatch', batchDispatch);
        },
        dispatchlist: () => {
            const { reactive, onMounted } = Vue
            const dispatchList = {
                setup() {
                    const state = reactive({
                        method: new URLSearchParams(location.search).get('method'),
                        status: 'all',
                        statusList: {
                            all: {
                                label: '全部',
                            },
                            nosend: {
                                label: '待发货',
                            },
                            success: {
                                label: '成功',
                            },
                            error: {
                                label: '失败',
                            },
                        },
                        all: [],
                        nosend: [],
                        success: [],
                        error: [],
                    });

                    const loop = reactive({
                        flag: false,
                        index: 0,
                        item: {},
                    });

                    // 开始
                    function onStartDispatch() {
                        loop.flag = true;
                        loopDispatch('start');
                    }

                    function onSuspendDispatch() {
                        loop.flag = false;
                    }

                    // 重新发货
                    function onAgainDispatch() {
                        loop.flag = true;
                        loop.index = 0;
                        state.error = [];
                        loopDispatch('again');
                    }

                    // 循环发货
                    async function loopDispatch(type) {
                        // type = start | again
                        if (loop.index >= state.all.length) {
                            loop.index++;
                        } else {
                            // 1.开始直接循环 2.批量重新发货(过滤掉成功的)循环
                            if (type == 'start' || (type == 'again' && state.all[loop.index].dispatch_status != 0)) {
                                await onDispatch(loop.index, type);
                            }
                            loop.index++;
                            if (loop.flag) {
                                loopDispatch(type);
                            }
                        }
                    }
                    // 发货
                    async function onDispatch(index = loop.index, type) {
                        // 当前item
                        loop.item = state.all[index];

                        Fast.api.ajax({
                            url: 'shopro/order/order/dispatch',
                            type: 'POST',
                            data: {
                                order_id: loop.item.order_id,
                                order_item_ids: loop.item.order_item_ids,
                                action: 'confirm',
                                method: state.method,
                                express: loop.item.express,
                            }
                        }, function (ret, res) {
                            handleData(res)
                        }, function (ret, res) {
                            handleData(res)
                        })

                        // 数据分组 1成功 !1失败 error已发货
                        function handleData(res) {
                            state.all[index].dispatch_status = res.code;
                            state.all[index].dispatch_status_text = res.msg;

                            if (res.code == 1 && state.method == 'api') {
                                state.all[index].express = {
                                    name: res.data.name,
                                    code: res.data.code,
                                    no: res.data.no,
                                };
                            }

                            if (res.code == 1) {
                                state.success.push(state.all[index]);
                            }
                            if (res.code == 0) {
                                state.error.push(state.all[index]);
                            }

                            if (type == 'start') {
                                state.nosend = state.all.slice(index + 1);
                            }
                        }

                    }

                    // 单独重新发货
                    async function onAloneDispatch(index) {
                        loop.item = state[state.status][index];
                        let idx = state.all.findIndex((a) => a.order_id == loop.item.order_id);

                        Fast.api.ajax({
                            url: 'shopro/order/order/dispatch',
                            type: 'POST',
                            data: {
                                order_id: loop.item.order_id,
                                order_item_ids: loop.item.order_item_ids,
                                action: 'confirm',
                                method: state.method,
                                express: loop.item.express,
                            }
                        }, function (ret, res) {
                            handleData(res)
                        }, function (ret, res) {
                            handleData(res)
                        })

                        function handleData(res) {
                            state.all[index].dispatch_status = res.code;
                            state.all[index].dispatch_status_text = res.msg;

                            if (res.code == 1 && state.method == 'api') {
                                state.all[index].express = {
                                    name: res.data.name,
                                    code: res.data.code,
                                    no: res.data.no,
                                };
                            }

                            if (res.code == 1) {
                                state.success.push(state.all[idx]);
                                state.error.splice(index, 1);
                            }
                        }
                    }

                    onMounted(() => {
                        state.all = JSON.parse(localStorage.getItem("batch-dispatch"))
                        state.nosend = JSON.parse(JSON.stringify(state.all))
                    })

                    return {
                        state,
                        loop,
                        onStartDispatch,
                        onSuspendDispatch,
                        onAgainDispatch,
                        onDispatch,
                        onAloneDispatch,
                    }
                }
            }
            createApp('dispatchList', dispatchList);
        },
        refund: () => {
            const { reactive, getCurrentInstance } = Vue
            const refund = {
                setup() {
                    const { proxy } = getCurrentInstance();

                    const state = reactive({
                        id: new URLSearchParams(location.search).get('id'),
                        item_id: new URLSearchParams(location.search).get('item_id'),
                        suggest_refund_fee: new URLSearchParams(location.search).get('suggest_refund_fee'),
                    })

                    const form = reactive({
                        model: {
                            refund_type: 'back',
                            refund_money: '',
                        },
                        rules: {
                            refund_money: [{ required: true, message: '请输入退款金额', trigger: 'blur' }],
                        },
                    })

                    function onConfirm() {
                        proxy.$refs['formRef'].validate((valid) => {
                            if (valid) {
                                Fast.api.ajax({
                                    url: `shopro/order/order/refund/id/${state.id}/item_id/${state.item_id}`,
                                    type: 'POST',
                                    data: form.model,
                                }, function (ret, res) {
                                    Fast.api.close()
                                }, function (ret, res) { })
                            }
                        });
                    }

                    return {
                        state,
                        form,
                        onConfirm
                    }
                }
            }
            createApp('refund', refund);
        },
    };
    return Controller;
});
