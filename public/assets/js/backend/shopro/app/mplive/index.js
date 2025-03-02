define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'moment'], function ($, undefined, Backend, Table, Form, Moment) {

    var Controller = {
        index: () => {
            const { reactive, onMounted, ref, watch } = Vue
            const index = {
                setup() {
                    const state = reactive({
                        status: 0,
                        data: [],
                        filter: {
                            drawer: false,
                            data: {
                                name: '',
                            },
                            tools: {
                                name: {
                                    type: 'tinput',
                                    field: 'name',
                                    value: '',
                                    label: '商品名称',
                                    placeholder: '请输入商品名称',
                                },
                            },
                            condition: {}
                        }
                    })
                    const dispatchType = ref('live');

                    // 直播间表格
                    const live = reactive({
                        data: [],
                        order: '',
                        sort: '',
                        selected: [],
                    });
                    //商品库表格
                    const goods = reactive({
                        data: [],
                        order: '',
                        sort: '',
                    });
                    // 获取直播间数据
                    function getLiveData() {

                        Fast.api.ajax({
                            url: 'shopro/app/mplive/room',
                            type: 'GET',
                            data: {
                                sort: live.sort,
                                order: live.order
                            },
                        }, function (ret, res) {
                            live.data = res.data;
                            return false
                        }, function (ret, res) { })
                    }

                    function getData() {
                        let tempSearch = JSON.parse(JSON.stringify(state.filter.data));
                        let search = composeFilter(tempSearch, {
                            name: 'like',
                        });
                        Fast.api.ajax({
                            url: 'shopro/app/mplive/goods',
                            type: 'GET',
                            data: {
                                page: pagination.page,
                                list_rows: pagination.list_rows,
                                ...search,
                            },
                        }, function (ret, res) {
                            goods.data = res.data.data
                            pagination.total = res.data.total
                            return false
                        }, function (ret, res) { })
                    }

                    function formatPrice(price, type) {
                        if (type === 1) {
                            return '';
                        } else if (type === 2) {
                            return '~' + price + '元';
                        } else {
                            return price + '元';
                        }
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
                    function addRow() {
                        Fast.api.open(`shopro/app/mplive/room/add?type=add`, "添加直播间", {
                            callback() {
                                getLiveData()
                            }
                        })
                    }
                    function editRow(id) {
                        Fast.api.open(`shopro/app/mplive/room/edit?type=edit&id=${id}`, "编辑直播间", {
                            callback() {
                                getLiveData()
                            }
                        })
                    }
                    function deleteApi(id) {
                        Fast.api.ajax({
                            url: `shopro/app/mplive/room/delete/id/${id}`,
                            type: 'DELETE',
                        }, function (ret, res) {
                            getLiveData()
                        }, function (ret, res) { })
                    }
                    //表格排序
                    function onChangeSort({ prop, order }) {
                        live.order = order == 'ascending' ? 'asc' : 'desc';
                        live.sort = prop;
                        getLiveData();
                    }
                    //推流地址
                    async function pushUrl(id) {
                        Fast.api.open(`shopro/app/mplive/room/pushUrl?id=${id}`, "推流地址", {
                            callback() {
                                getLiveData()
                            }
                        })
                    }

                    //分享二维码
                    async function shareQrcode(id) {
                        Fast.api.open(`shopro/app/mplive/room/qrcode?id=${id}`, "分享二维码", {
                            callback() {
                                getLiveData()
                            }
                        })
                    }
                    //同步直播间
                    function sync() {
                        Fast.api.ajax({
                            url: `shopro/app/mplive/room/sync`,
                            type: 'GET',
                        }, function (ret, res) {
                            live.data = res.data;
                        }, function (ret, res) { })
                    }
                    //直播回放
                    function playBack(id) {
                        Fast.api.open(`shopro/app/mplive/room/playback?id=${id}`, "直播回放列表", {
                            callback() {
                                getLiveData()
                            }
                        })
                    }

                    //添加商品
                    function addGoods() {
                        Fast.api.open(`shopro/app/mplive/goods/add?type=add`, "添加商品", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    //编辑商品
                    function editGoods(id) {
                        Fast.api.open(`shopro/app/mplive/goods/edit?type=edit&id=${id}`, "编辑商品", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    // 删除商品
                    function deleteGoods(id) {
                        Fast.api.ajax({
                            url: `shopro/app/mplive/goods/delete/id/${id}`,
                            type: 'DELETE',
                        }, function (ret, res) {
                            getData()
                        }, function (ret, res) { })
                    }

                    // 获取状态
                    function getStatus(id) {
                        Fast.api.ajax({
                            url: `shopro/app/mplive/goods/status/id/${id}`,
                            type: 'GET',
                        }, function (ret, res) {
                            getData()
                        }, function (ret, res) { })
                    }

                    //审核
                    function check(id, act) {
                        Fast.api.ajax({
                            url: `shopro/app/mplive/goods/audit/id/${id}`,
                            type: 'POST',
                            data: {
                                act
                            },
                        }, function (ret, res) {
                            getData()
                        }, function (ret, res) { })
                    }


                    watch(() => dispatchType.value, () => {
                        if (dispatchType.value === 'live') {
                            getLiveData()
                        } else {
                            getData()
                        }
                    })

                    onMounted(() => {
                        getLiveData()
                    })

                    return {
                        state,
                        live,
                        goods,
                        dispatchType,
                        getData,
                        onOpenFilter,
                        onChangeFilter,
                        pagination,
                        formatPrice,
                        getLiveData,
                        addRow,
                        editRow,
                        deleteApi,
                        Moment,
                        sync,
                        playBack,
                        shareQrcode,
                        pushUrl,
                        addGoods,
                        editGoods,
                        deleteGoods,
                        check,
                        getStatus,
                        onChangeSort
                    }
                }
            }
            createApp('index', index);
        },
    };
    return Controller;
});
