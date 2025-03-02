define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'moment'], function ($, undefined, Backend, Table, Form, Moment) {

    var Controller = {
        add: () => {
            Controller.form();
        },
        edit: () => {
            Controller.form();
        },
        form: () => {
            const { reactive, onMounted } = Vue
            const addEdit = {
                setup() {
                    const state = reactive({
                        type: new URLSearchParams(location.search).get('type'),
                        id: new URLSearchParams(location.search).get('id'),
                        title: new URLSearchParams(location.search).get('title'),
                    })

                    const form = reactive({
                        model: {
                            type: 0,
                            name: '',
                            price_type: 1,
                            third_party_appid: '',
                            price: '',
                            price2: '',
                            cover_img_url: '',
                        },
                        rules: {
                            name: [{ validator: checkTitle, trigger: 'change' }],
                            cover_img_url: [{ required: true, message: '请选择商品封面', trigger: 'change' }],
                        },
                    });

                    const goods = reactive({
                        id: '', // 商品id
                        image: '', // 商品图片
                        title: '', // 商品名称
                    });

                    function checkTitle(rule, value, callback) {
                        if (!value) {
                            return callback(new Error('请输入商品名称'));
                        }
                        const length =
                            value.match(/[^ -~]/g) == null ? value.length : value.length + value.match(/[^ -~]/g).length;
                        if (length < 6 || length > 28) {
                            callback(new Error('直播标题必须为3-14个字（一个字等于两个英文字符或特殊字符）'));
                        } else {
                            callback();
                        }
                    }

                    //选择商品
                    function selectGoods() {
                        let id = '';
                        Fast.api.open(`shopro/goods/goods/select`, "选择商品", {
                            callback(data) {
                                console.log(data, 'data');
                                goods.image = data.image;
                                goods.title = data.title;
                                goods.id = data.id;

                                form.model.cover_img_url = data.image;
                                form.model.name = data.title;
                                form.model.url = 'pages/goods/index?id=' + goods.id;
                                if (data.price.length === 2) {
                                    form.model.price_type = 2;
                                    form.model.price = data.price[0];
                                    form.model.price2 = data.price[1];
                                } else {
                                    if (Number(data.original_price)) {
                                        form.model.price_type = 3;
                                        form.model.price = data.original_price;
                                        form.model.price2 = data.price[0];
                                    } else {
                                        form.model.price_type = 1;
                                        form.model.price = data.price[0];
                                    }
                                }
                                form.model.goods_id = data.id;
                            }
                        })
                    }

                    // 获取商品信息
                    function getGoodsList(id) {
                        Fast.api.ajax({
                            url: `shopro/goods/goods/select`,
                            type: 'GET',
                            data: {
                                type: 'select',
                                search: JSON.stringify({ id }),
                            },
                        }, function (ret, res) {
                            goods.image = res.data[0].image;
                            goods.title = res.data[0].title;
                            return false
                        }, function (ret, res) { })
                    }

                    //获取详情
                    function getDetail() {
                        Fast.api.ajax({
                            url: `shopro/app/mplive/goods/detail/id/${state.id}`,
                            type: 'GET',
                        }, function (ret, res) {
                            form.model = res.data;
                            getGoodsList(form.model.goods_id);
                            return false
                        }, function (ret, res) { })
                    }

                    function onConfirm() {
                        Fast.api.ajax({
                            url: state.type == 'add' ? 'shopro/app/mplive/goods/add' : `shopro/app/mplive/goods/edit/id/${state.id}`,
                            type: 'POST',
                            data: form.model,
                        }, function (ret, res) {
                            Fast.api.close()
                        }, function (ret, res) { })
                    }

                    onMounted(() => {
                        state.type == 'edit' && getDetail()
                    })

                    return {
                        state,
                        goods,
                        form,
                        onConfirm,
                        getDetail,
                        checkTitle,
                        selectGoods,
                        getGoodsList
                    }
                }
            }
            createApp('addEdit', addEdit);
        },
    };
    return Controller;
});
