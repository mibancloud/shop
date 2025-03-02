define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        data: () => {
            const { ElMessage } = ElementPlus
            return {
                activityData: [{
                    type: 'promo',
                    title: '营销',
                    children: {
                        full_coupon: {
                            title: '优惠券',
                            subtitle: '向客户发送店铺优惠券',
                        },
                        full_reduce: {
                            title: '满额立减',
                            subtitle: '满足活动条件享受立减优惠',
                        },
                        full_discount: {
                            title: '满额折扣',
                            subtitle: '满足活动条件享受折扣优惠',
                        },
                        full_gift: {
                            title: '满赠',
                            subtitle: '吸引客流，刺激消费',
                        },
                        free_shipping: {
                            title: '满额包邮',
                            subtitle: '满足活动条件享受包邮优惠',
                        },
                    },
                },
                {
                    type: 'activity',
                    title: '活动',
                    children: {
                        groupon: {
                            title: '普通拼团',
                            subtitle: '多人拼团享优惠',
                        },
                        groupon_ladder: {
                            title: '阶梯拼团',
                            subtitle: '人数越多价格越优惠',
                        },
                        seckill: {
                            title: '秒杀',
                            subtitle: '限时特卖引流涨粉',
                        },
                    },
                },
                {
                    type: 'app',
                    title: '应用',
                    children: {
                        score_shop: {
                            title: '积分商城',
                            subtitle: '引导客户积分消费有效促活',
                        },
                        signin: {
                            title: '签到',
                            subtitle: '签到享好礼，客户更活跃',
                        },
                        wechat_mplive: {
                            title: '微信小程序直播',
                            subtitle: '一键同步直播间,管理直播间',
                        },
                    },
                }],
                getForm: (type) => {
                    let form = {
                        model: {
                            title: '',
                            type: type,
                            dateTime: [],
                            start_time: '',
                            end_time: '',
                            richtext_id: '',
                            richtext_title: '',
                        },
                        rules: {
                            title: [{ required: true, message: '请输入活动名称', trigger: 'blur' }],
                            dateTime: [{ required: true, message: '请选择活动时间', trigger: 'blur' }],
                            start_time: [{ required: true, message: '请选择活动开始时间', trigger: 'blur' }],
                            end_time: [{ required: true, message: '请选择活动结束时间', trigger: 'blur' }],
                            prehead_time: [{ required: true, message: '请选择预热时间', trigger: 'blur' }],
                            goods_list: [{ required: true, message: '请选择商品', trigger: 'blur' }],
                        },
                    };
                    let tempForm = {
                        full_reduce: {
                            model: {
                                rules: {
                                    type: 'money',
                                    discounts: [
                                        {
                                            full: '',
                                            discount: '',
                                        },
                                    ],
                                },
                                goods_ids: null,
                                goods_list: [],
                            },
                            rules: {
                                rules: {
                                    discounts: {
                                        full: [{ required: true, message: '请输入', trigger: 'blur' }],
                                        discount: [{ required: true, message: '请输入', trigger: 'blur' }],
                                    },
                                }
                            },
                        },
                        full_discount: {
                            model: {
                                rules: {
                                    type: 'money',
                                    discounts: [
                                        {
                                            full: '',
                                            discount: '',
                                        },
                                    ],
                                },
                                goods_ids: null,
                                goods_list: [],
                            },
                            rules: {
                                rules: {
                                    discounts: {
                                        full: [
                                            {
                                                required: true,
                                                message: '请输入',
                                                trigger: 'blur',
                                            },
                                        ],
                                        discount: [
                                            {
                                                required: true,
                                                message: '请输入',
                                                trigger: 'blur',
                                            },
                                        ],
                                    },
                                }
                            },
                        },
                        full_gift: {
                            model: {
                                rules: {
                                    limit_num: 0, // 参与次数 0=不限制
                                    type: 'money', // 优惠类型 money=满足金额 num=满足件数
                                    event: 'paid', // 赠送时机 paid=支付完成 confirm=确认收货 finish=交易完成
                                    discounts: [
                                        {
                                            full: '',
                                            gift_num: '', // 礼品份数
                                            types: [], // 赠送类型 coupon_ids=优惠券 score=积分 money=余额
                                            coupon_list: [], // 暂存数据
                                            coupon_ids: '',
                                            total: '',
                                            score: '',
                                            money: '',
                                            // goods_ids:"",
                                        },
                                    ],
                                },
                                goods_ids: null,
                                goods_list: [],
                            },
                            rules: {
                                rules: {
                                    discounts: {
                                        full: [
                                            {
                                                required: true,
                                                message: '请输入',
                                                trigger: 'blur',
                                            },
                                        ],
                                        gift_num: [
                                            {
                                                required: true,
                                                message: '请输入',
                                                trigger: 'blur',
                                            },
                                        ],
                                        types: [
                                            {
                                                required: true,
                                                message: '请选择赠送类型',
                                                trigger: 'blur',
                                            },
                                        ],
                                    },
                                }
                            },
                        },
                        free_shipping: {
                            model: {
                                rules: {
                                    type: 'money',
                                    full_num: '',
                                    province_except: '', // 区
                                    city_except: '', // 市
                                    district_except: '', // 街道
                                    district_text: {}, // label数据
                                },
                                goods_ids: null,
                                goods_list: [],
                            },
                            rules: {
                                rules: {
                                    full_num: { required: true, message: '请输入', trigger: 'blur' },
                                }
                            },
                        },
                        groupon: {
                            model: {
                                prehead_time: '',
                                rules: {
                                    is_commission: 0, // 是否参与分销
                                    is_free_shipping: 0, // 是否包邮
                                    sales_show_type: 'real', // real=真实活动销量|goods=商品总销量（包含虚拟销量）
                                    team_num: 2, // 成团人数，最少两人
                                    is_alone: 0, // 是否允许单独购买
                                    is_fictitious: 0, // 是否允许虚拟成团
                                    fictitious_num: 0, // 最多虚拟人数 0:不允许虚拟 '' 不限制
                                    fictitious_time: 0, // 开团多长时间自动虚拟成团
                                    is_team_card: 0, // 参团卡显示
                                    is_leader_discount: 0, // 团长优惠
                                    valid_time: 24, // 组团有效时间, 0：一直有效
                                    limit_num: 0, // 每人限购数量 0:不限购
                                    refund_type: 'back', // 退款方式 back=原路退回|money=退回到余额
                                    order_auto_close: 5, // 订单自动关闭时间，如果为 0 将使用系统级订单自动关闭时间
                                },
                                goods_ids: null,
                                goods_list: [],
                            },
                            rules: {
                                rules: {
                                    valid_time: [{ required: true, message: '请输入拼团解散时间', trigger: 'blur' }],
                                    team_num: [{ required: true, message: '请输入成团人数', trigger: 'blur' }],
                                    fictitious_num: [{ required: true, message: '请输入最多虚拟人数', trigger: 'blur' }],
                                    fictitious_time: [{ required: true, message: '请输入虚拟成团时间', trigger: 'blur' }],
                                    order_auto_close: [
                                        { required: true, message: '请输入订单支付时间', trigger: 'blur' },
                                        {
                                            validator: (rule, value, callback) => {
                                                if (Number(value) <= 0) {
                                                    callback(new Error('值必须大于0'));
                                                } else {
                                                    callback();
                                                }
                                            },
                                            trigger: 'blur',
                                        },
                                    ],
                                }
                            },
                        },
                        groupon_ladder: {
                            model: {
                                prehead_time: '',
                                rules: {
                                    is_commission: 0, // 是否参与分销
                                    is_free_shipping: 0, // 是否包邮
                                    sales_show_type: 'real', // real=真实活动销量|goods=商品总销量（包含虚拟销量）
                                    ladders: {
                                        ladder_one: 2,
                                        ladder_two: 3,
                                    }, // {ladder_one:2,ladder_two:2,ladder_three:2}
                                    is_alone: 0, // 是否允许单独购买
                                    is_fictitious: 0, // 是否允许虚拟成团
                                    fictitious_num: 0, // 最多虚拟人数 0:不允许虚拟 '' 不限制
                                    fictitious_time: 0, // 开团多长时间自动虚拟成团
                                    is_team_card: 0, // 参团卡显示
                                    is_leader_discount: 0, // 团长优惠
                                    valid_time: 24, // 组团有效时间, 0：一直有效
                                    limit_num: 0, // 每人限购数量 0:不限购
                                    refund_type: 'back', // 退款方式 back=原路退回|money=退回到余额
                                    order_auto_close: 5, // 订单自动关闭时间，如果为 0 将使用系统级订单自动关闭时间
                                },
                                goods_ids: null,
                                goods_list: [],
                            },
                            rules: {
                                rules: {
                                    valid_time: [{ required: true, message: '请输入拼团解散时间', trigger: 'blur' }],
                                    ladder_one: [{ required: true, message: '最少两人', trigger: 'blur' }],
                                    ladder_two: [{ required: true, message: '最少两人', trigger: 'blur' }],
                                    ladder_three: [{ required: true, message: '最少两人', trigger: 'blur' }],
                                    fictitious_num: [{ required: true, message: '请输入最多虚拟人数', trigger: 'blur' }],
                                    fictitious_time: [{ required: true, message: '请输入虚拟成团时间', trigger: 'blur' }],
                                    order_auto_close: [
                                        { required: true, message: '请输入订单支付时间', trigger: 'blur' },
                                        {
                                            validator: (rule, value, callback) => {
                                                if (Number(value) <= 0) {
                                                    callback(new Error('值必须大于0'));
                                                } else {
                                                    callback();
                                                }
                                            },
                                            trigger: 'blur',
                                        },
                                    ],
                                }
                            },
                        },
                        seckill: {
                            model: {
                                prehead_time: '',
                                rules: {
                                    is_commission: 0, // 是否参与分销
                                    is_free_shipping: 0, // 是否包邮
                                    sales_show_type: 'real', // real=真实活动销量|goods=商品总销量（包含虚拟销量）
                                    limit_num: 0, // 每人限购数量 0:不限购
                                    order_auto_close: 5, // 订单自动关闭时间，如果为 0 将使用系统级订单自动关闭时间is_commission: 0,  // 是否参与分销
                                },
                                goods_ids: null,
                                goods_list: [],
                            },
                            rules: {
                                rules: {
                                    order_auto_close: [
                                        { required: true, message: '请输入订单支付时间', trigger: 'blur' },
                                        {
                                            validator: (rule, value, callback) => {
                                                if (Number(value) <= 0) {
                                                    callback(new Error('值必须大于0'));
                                                } else {
                                                    callback();
                                                }
                                            },
                                            trigger: 'blur',
                                        },
                                    ],
                                }
                            },
                        },
                        signin: {
                            model: {
                                rules: {
                                    everyday: 0, // 每日签到固定积分
                                    is_inc: 0, // 是否递增签到
                                    inc_num: 0, // 递增奖励
                                    until_day: 0, // 递增持续天数
                                    discounts: [], // 连续签到奖励 {full:5, value:10}
                                    is_replenish: 0, // 是否开启补签
                                    replenish_days: 1, // 可补签天数 最小1
                                    replenish_limit: 0, // 补签事件限制，0 不限制
                                    replenish_num: 1, // 补签所消耗积分
                                },
                            },
                            rules: {
                                rules: {
                                    everyday: [{ required: true, message: '请输入日签奖励', trigger: 'blur' }],
                                    inc_num: [{ required: true, message: '请输入', trigger: 'blur' }],
                                    until_day: [{ required: true, message: '请输入', trigger: 'blur' }],
                                    discounts: {
                                        full: [{ required: true, message: '请输入', trigger: 'blur' }],
                                        value: [{ required: true, message: '请输入', trigger: 'blur' }],
                                    },
                                    replenish_days: [{ required: true, message: '请输入最多虚拟人数', trigger: 'blur' }],
                                    replenish_limit: [{ required: true, message: '请输入虚拟成团时间', trigger: 'blur' }],
                                    replenish_num: [{ required: true, message: '请输入虚拟成团时间', trigger: 'blur' }],
                                },
                            },
                        },
                    };
                    console.log(tempForm[type], type, 'kjkj')
                    form.model = {
                        ...form.model,
                        ...tempForm[type].model,
                    };
                    form.rules = { ...form.rules, ...tempForm[type].rules };
                    return form;
                },
                handleForm: (submitForm) => {
                    if (submitForm.type != 'signin') {
                        //   处理商品
                        let goodsIds = [];
                        submitForm.goods_list.forEach((g) => {
                            goodsIds.push(g.id);
                        });
                        submitForm.goods_ids = goodsIds.join(',');
                    }

                    // 满减
                    if (submitForm.type == 'full_reduce') {
                        if (submitForm.rules.type == 'money') {
                            let flag = true;
                            submitForm.rules.discounts.forEach((d) => {
                                if (Number(d.full) < Number(d.discount)) {
                                    flag = false;
                                }
                            });
                            if (!flag) {
                                ElMessage({
                                    message: '请输入正确的规则',
                                    type: 'warning',
                                });
                                return;
                            }
                        }
                    }

                    // 满赠
                    if (submitForm.type == 'full_gift') {
                        // 优惠券
                        submitForm.rules.discounts.forEach((d) => {
                            let couponIds = [];
                            let total = 0;
                            d.coupon_list.forEach((c) => {
                                couponIds.push(c.id);
                                total += Number(c.amount);
                            });
                            d.coupon_ids = couponIds.join(',');
                            d.total = total;
                            delete d.coupon_list;
                        });
                    }

                    // 阶梯拼团
                    if (submitForm.type == 'groupon_ladder') {
                        if (
                            !(
                                (!submitForm.rules.ladders.hasOwnProperty('ladder_three') &&
                                    Number(submitForm.rules.ladders.ladder_one) <
                                    Number(submitForm.rules.ladders.ladder_two)) ||
                                (submitForm.rules.ladders.hasOwnProperty('ladder_three') &&
                                    Number(submitForm.rules.ladders.ladder_one) <
                                    Number(submitForm.rules.ladders.ladder_two) &&
                                    Number(submitForm.rules.ladders.ladder_two) <
                                    Number(submitForm.rules.ladders.ladder_three))
                            )
                        ) {
                            ElMessage({
                                message: '请输入成团人数(阶梯人数依次增加)',
                                type: 'warning',
                            });
                            return;
                        }

                        let flag = false;
                        submitForm.goods_list.forEach((goods) => {
                            if (goods.activity_sku_prices) {
                                goods.activity_sku_prices.forEach((sku) => {
                                    if (sku.status == 'up' && submitForm.rules.ladders.hasOwnProperty('ladder_three')) {
                                        if (!(sku.hasOwnProperty('ladder_three') && sku.hasOwnProperty('ladder_three'))) {
                                            flag = true;
                                        }
                                    }
                                });
                            }
                        });
                        if (flag) {
                            ElMessage({
                                message: '请完善商品规格信息',
                                type: 'warning',
                            });
                            return;
                        }
                    }

                    submitForm.start_time = submitForm.dateTime[0];
                    submitForm.end_time = submitForm.dateTime[1];
                    delete submitForm.dateTime;

                    return submitForm;
                }
            }
        },
        index: () => {
            const { activityData } = Controller.data()
            const { reactive, onMounted } = Vue
            const index = {
                setup() {

                    function onActivity(key, title) {
                        if (key == 'full_coupon') {
                            Fast.api.addtabs(`shopro/coupon/index`, '优惠券')
                        } else if (key == 'score_shop') {
                            Fast.api.addtabs(`shopro/app/score_shop/index`, '积分商城')
                        } else if (key == 'wechat_mplive') {
                            Fast.api.addtabs(`shopro/app/mplive/index`, '小程序直播')
                        } else {
                            Fast.api.addtabs(`shopro/activity/activity/index?type=${key}&title=${title}`, title)
                        }
                    }

                    const state = reactive({
                        type: new URLSearchParams(location.search).get('type'),
                        title: new URLSearchParams(location.search).get('title'),
                        data: [],
                        order: '',
                        sort: '',
                        filter: {
                            drawer: false,
                            data: {
                                title: '',
                                status: 'all',
                                activity_time: [],
                            },
                            tools: {
                                title: {
                                    type: 'tinput',
                                    label: '活动名称',
                                    placeholder: '请输入活动名称',
                                    value: '',
                                },
                                status: {
                                    type: 'tselect',
                                    label: '状态',
                                    value: '',
                                    options: {
                                        data: [{
                                            label: '全部',
                                            value: 'all',
                                        },
                                        {
                                            label: '未开始',
                                            value: 'nostart',
                                        },
                                        {
                                            label: '进行中',
                                            value: 'ing',
                                        },
                                        {
                                            label: '已结束',
                                            value: 'ended',
                                        }],
                                    },
                                },
                                activity_time: {
                                    type: 'tdatetimerange',
                                    label: '时间',
                                    value: [],
                                },
                            },
                            condition: {},
                        },
                        statusStyle: {
                            nostart: 'sa-color--info',
                            ing: 'sa-color--success',
                            ended: 'sa-color--danger',
                        }
                    })

                    function getData() {
                        let tempSearch = JSON.parse(JSON.stringify(state.filter.data));
                        let search = composeFilter(tempSearch, {
                            title: 'like',
                            activity_time: 'range',
                        });
                        Fast.api.ajax({
                            url: 'shopro/activity/activity',
                            type: 'GET',
                            data: {
                                type: state.type,
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

                    const pagination = reactive({
                        page: 1,
                        list_rows: 10,
                        total: 0,
                    })

                    function onAdd() {
                        Fast.api.open(`shopro/activity/activity/add?type=add&activity_type=${state.type}`, "添加", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onEdit(id) {
                        Fast.api.open(`shopro/activity/activity/edit?type=edit&activity_type=${state.type}&id=${id}`, "编辑", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onDelete(id) {
                        Fast.api.ajax({
                            url: `shopro/activity/activity/delete/id/${id}`,
                            type: 'DELETE',
                        }, function (ret, res) {
                            getData()
                        }, function (ret, res) { })
                    }

                    function onRecyclebin() {
                        Fast.api.open(`shopro/activity/activity/recyclebin?activity_type=${state.type}`, "回收站", {
                            callback() {
                                getData()
                            }
                        })
                    }

                    function onGroupon(id) {
                        Fast.api.addtabs(`shopro/activity/groupon/index?activity_id=${id}`, "拼团列表", {
                            callback() {
                                getData()
                            }
                        })
                    }

                    onMounted(() => {
                        getData()
                    })

                    return {
                        activityData,
                        onActivity,
                        state,
                        getData,
                        onChangeSort,
                        onOpenFilter,
                        onChangeFilter,
                        pagination,
                        onAdd,
                        onEdit,
                        onDelete,
                        onRecyclebin,
                        onGroupon
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
            const { getForm, handleForm } = Controller.data()
            const { reactive, computed, onMounted, getCurrentInstance } = Vue
            const addEdit = {
                setup() {
                    const { proxy } = getCurrentInstance();

                    const state = reactive({
                        type: new URLSearchParams(location.search).get('type'),
                        id: new URLSearchParams(location.search).get('id'),
                        activity_type: new URLSearchParams(location.search).get('activity_type'),
                        activityStatus: 0,
                        limitNumType: 'all',
                        is_discounts: '0',
                        goodsType: 'all',
                    })

                    const isActivity = computed(
                        () =>
                            state.activity_type == 'groupon' ||
                            state.activity_type == 'groupon_ladder' ||
                            state.activity_type == 'seckill',
                    );

                    const form = reactive(getForm(state.activity_type))

                    function getDetail() {
                        Fast.api.ajax({
                            url: `shopro/activity/activity/detail/id/${state.id}`,
                            type: 'GET',
                        }, function (ret, res) {
                            form.model = res.data;

                            // 处理时间
                            form.model.dateTime = [form.model.start_time, form.model.end_time];

                            // 处理商品
                            if (form.model.goods_ids) {
                                state.goodsType = 'part';
                            }

                            if (form.model.rules.limit_num) {
                                state.limitNumType = 'part'
                            }

                            if (state.activity_type == 'signin') {
                                state.is_discounts = form.model.rules.discounts.length > 0 ? '1' : '0'
                            }

                            state.activityStatus = res.data.status == 'ing' ? 1 : 0;
                            return false
                        }, function (ret, res) { })
                    }

                    function onChangeEndtime(val) {
                        form.model.dateTime[1] = val
                    }

                    function onAddDiscounts() {
                        if (form.model.type == 'full_reduce' || form.model.type == 'full_discount') {
                            form.model.rules.discounts.push({
                                full: '',
                                discount: '',
                            })
                        } else if (form.model.type == 'full_gift') {
                            form.model.rules.discounts.push({
                                full: '',
                                gift_num: '',
                                types: [],
                                coupon_ids: '',
                                total: '',
                                coupon_list: [],
                                score: '',
                                money: '',
                            })
                        } else if (form.model.type == 'signin') {
                            form.model.rules.discounts.push({
                                full: '',
                                value: '',
                            })
                        }
                    }
                    function onDeleteDiscounts(index) {
                        form.model.rules.discounts.splice(index, 1);
                    }
                    function onChangeDiscounts() {
                        form.model.rules.discounts = []
                    }

                    function onChangeLimitNumType() {
                        if (state.limitNumType == 'all') {
                            form.model.rules.limit_num = 0;
                        } else if (state.limitNumType == 'part') {
                            form.model.rules.limit_num = '';
                        }
                    }

                    function onSelectCoupon(index) {
                        Fast.api.open(`shopro/coupon/select?multiple=true&status=hidden`, "选择优惠券", {
                            callback(data) {
                                form.model.rules.discounts[index].coupon_list.push(...data);
                            }
                        })
                    }
                    function onDeleteCoupon(index, dindex) {
                        form.model.rules.discounts[index].coupon_list.splice(dindex, 1);
                    }

                    function onSelectArea() {
                        let selected = {
                            province: form.model.rules.province_except,
                            city: form.model.rules.city_except,
                            district: form.model.rules.district_except,
                        }
                        Fast.api.open(`shopro/data/area/select?selected=${encodeURI(JSON.stringify(selected))}`, "选择地区", {
                            callback(data) {
                                for (var level in data) {
                                    let ids = [];
                                    for (var id in data[level]) {
                                        ids.push(id);
                                    }
                                    form.model.rules[level + '_except'] = ids.join(',');
                                }
                                form.model.rules.district_text = data;
                            }
                        })
                    }

                    function onAddLadders() {
                        form.model.rules.ladders.ladder_three = 4;
                    }
                    function onDeleteLadders() {
                        delete form.model.rules.ladders.ladder_three;
                    }

                    function onChangeGoodsType() {
                        if (state.goodsType == 'all') {
                            form.model.goods_ids = null;
                            form.model.goods_list = [];
                        } else if (state.goodsType == 'part') { }
                    }

                    function onSelectGoods() {
                        let ids = [];
                        form.model.goods_list.forEach((i) => {
                            ids.push(i.id);
                        });
                        Fast.api.open(`shopro/goods/goods/select?multiple=true&ids=${ids.join(',')}`, "选择商品", {
                            callback(data) {
                                data.forEach((item) => {
                                    let findItem = form.model.goods_list.find((k) => k.id == item.id);
                                    if (findItem) {
                                        item.activity_sku_prices = findItem.activity_sku_prices;
                                    }
                                });
                                form.model.goods_list = data;
                            }
                        })
                    }
                    function onDeleteGoods(index) {
                        form.model.goods_list.splice(index, 1);
                    }
                    function onSetActivitySkuPrices(index, id) {
                        localStorage.setItem("activity-skus", JSON.stringify(form.model))
                        Fast.api.open(`shopro/activity/activity/skus?activityStatus=${state.activityStatus}&goods_id=${id}`, "设置商品", {
                            callback(data) {
                                form.model.goods_list[index].activity_sku_prices = data;
                            }
                        })
                    }

                    function onSelectRichtext() {
                        Fast.api.open(`shopro/data/richtext/select`, "选选择活动说明", {
                            callback(data) {
                                form.model.richtext_title = data.title;
                                form.model.richtext_id = data.id;
                            }
                        })
                    }

                    function onConfirm() {
                        let submitForm = handleForm(JSON.parse(JSON.stringify(form.model)));
                        submitForm.goods_list = JSON.stringify(submitForm.goods_list)
                        proxy.$refs['formRef'].validate((valid) => {
                            if (valid) {
                                Fast.api.ajax({
                                    url: state.type == 'add' ? 'shopro/activity/activity/add' : `shopro/activity/activity/edit/id/${state.id}`,
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
                        if (isActivity.value) {
                            state.goodsType = 'part';
                        }
                    })

                    return {
                        state,
                        isActivity,
                        form,
                        onChangeEndtime,
                        onAddDiscounts,
                        onDeleteDiscounts,
                        onChangeDiscounts,
                        onChangeLimitNumType,
                        onSelectCoupon,
                        onDeleteCoupon,
                        onSelectArea,
                        onAddLadders,
                        onDeleteLadders,
                        onChangeGoodsType,
                        onSelectGoods,
                        onDeleteGoods,
                        onSetActivitySkuPrices,
                        onSelectRichtext,
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
                        type: new URLSearchParams(location.search).get('type'),
                        data: [],
                    })

                    function getData() {
                        let tempSearch = {
                            status: 'noend'
                        };
                        let search = composeFilter(tempSearch);
                        Fast.api.ajax({
                            url: 'shopro/activity/activity/select',
                            type: 'GET',
                            data: {
                                type: state.type,
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

                    function onConfirm(item) {
                        Fast.api.close(item)
                    }

                    onMounted(() => {
                        getData()
                    })

                    return {
                        state,
                        getData,
                        pagination,
                        onConfirm,
                    }
                }
            }
            createApp('select', select);
        },
        recyclebin: () => {
            const { reactive, onMounted } = Vue
            const recyclebin = {
                setup() {
                    const state = reactive({
                        activity_type: new URLSearchParams(location.search).get('activity_type'),
                        data: [],
                        order: '',
                        sort: '',
                    })

                    function getData() {
                        Fast.api.ajax({
                            url: 'shopro/activity/activity/recyclebin',
                            type: 'GET',
                            data: {
                                type: state.activity_type,
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

                    onMounted(() => {
                        getData()
                    })

                    return {
                        state,
                        getData,
                        onChangeSort,
                        pagination,
                    }
                }
            }
            createApp('recyclebin', recyclebin);
        },
        skus: () => {
            const { reactive, computed, onMounted } = Vue
            const skus = {
                setup() {

                    const state = reactive({
                        model: JSON.parse(localStorage.getItem("activity-skus")) || {},
                        goods_id: new URLSearchParams(location.search).get('goods_id'),
                        activityStatus: Number(new URLSearchParams(location.search).get('activityStatus')),
                        skus: [],
                        sku_prices: [],
                        activity_sku_prices: [],
                    })

                    const currentItem = computed(() => state.model.goods_list.find(
                        (item) => item.id == state.goods_id,
                    ))

                    function getSkus() {
                        Fast.api.ajax({
                            url: 'shopro/activity/activity/skus',
                            type: 'GET',
                            data: {
                                id: state.model.id,
                                goods_id: state.goods_id,
                                activity_type: state.model.type,
                                start_time: state.model.start_time,
                                end_time: state.model.end_time,
                                prehead_time: state.model.prehead_time,
                            }
                        }, function (ret, res) {
                            state.skus = res.data.skus;
                            state.sku_prices = res.data.sku_prices;
                            state.activity_sku_prices = currentItem.value?.activity_sku_prices || res.data.activity_sku_prices;
                            return false
                        }, function (ret, res) { })
                    }

                    function onConfirm() {
                        Fast.api.close(state.activity_sku_prices)
                    }

                    onMounted(() => {
                        getSkus()
                    })

                    return {
                        state,
                        onConfirm
                    }
                }
            }
            createApp('skus', skus);
        },
    };
    return Controller;
});
