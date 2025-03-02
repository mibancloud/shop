define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        data: () => {
            return {
                pageTypeList: [
                    { type: 'basic', label: '基础配置', },
                    { type: 'home', label: '首页', },
                    { type: 'user', label: '个人页', }
                ],
                systemList: [
                    { type: 'android', label: 'Android', color: '#6F74E9', },
                    { type: 'ios', label: 'IOS', color: '#333333', },
                ],
                platformList: [
                    { type: 'WechatMiniProgram', label: '微信小程序', color: '#6F74E9', },
                    { type: 'WechatOfficialAccount', label: '微信公众号', color: '#07C160', },
                    { type: 'H5', label: 'H5', color: '#FC800E', },
                    { type: 'App', label: 'APP', color: '#806AF6', },
                ],
                pageLeft: {
                    basic: [
                        {
                            name: '应用设置',
                            type: 'basic',
                            data: [
                                { name: '底部导航', type: 'tabbar', },
                                { name: '悬浮按钮', type: 'floatMenu', },
                                { name: '弹窗广告', type: 'popupImage', },
                            ],
                        },
                        {
                            name: '主题色',
                            type: 'theme',
                            data: [
                                { name: '淘宝橙', type: 'orange', },
                                { name: '香槟金', type: 'golden', },
                                { name: '美团黄', type: 'yellow', },
                                { name: '低奢黑', type: 'black', },
                                { name: '微信绿', type: 'green', },
                                { name: '尊贵紫', type: 'purple', },
                            ],
                        },
                    ],
                    compList: [
                        {
                            name: '会员组件',
                            type: '0',
                            show: ['user', 'diypage'],
                            data: [
                                { name: '会员卡片', type: 'userCard', },
                                { name: '订单卡片', type: 'orderCard', },
                                { name: '资产卡片', type: 'walletCard', },
                                { name: '卡券卡片', type: 'couponCard', },
                            ],
                        },
                        {
                            name: '基础组件',
                            type: '1',
                            data: [
                                { name: '搜索框', type: 'searchBlock', },
                                { name: '公告栏', type: 'noticeBlock', },
                                { name: '菜单导航', type: 'menuButton', },
                                { name: '列表导航', type: 'menuList', },
                                { name: '宫格导航', type: 'menuGrid', },
                            ],
                        },
                        {
                            name: '商品组件',
                            type: '2',
                            data: [
                                { name: '商品卡片', type: 'goodsCard', },
                                { name: '商品栏', type: 'goodsShelves', },
                            ],
                        },
                        {
                            name: '图文组件',
                            type: '3',
                            data: [
                                { name: '图片展示', type: 'imageBlock', },
                                { name: '图片轮播', type: 'imageBanner', },
                                { name: '标题栏', type: 'titleBlock', },
                                { name: '广告魔方', type: 'imageCube', },
                                { name: '视频播放', type: 'videoPlayer', },
                                { name: '辅助线', type: 'lineBlock', },
                                { name: '富文本', type: 'richtext', },
                                { name: '热区', type: 'hotzone', },
                            ],
                        },
                        {
                            name: '营销组件',
                            type: '4',
                            data: [
                                { name: '拼团', type: 'groupon', },
                                { name: '秒杀', type: 'seckill', },
                                { name: '积分商城', type: 'scoreGoods', },
                                { name: '小程序直播', type: 'mplive', },
                                {
                                    name: '优惠券',
                                    type: 'coupon',
                                },
                                // { name: '关注公众号', type: 'subscribeWechatOfficialAccount', },
                            ],
                        },
                    ]
                },
                defaultTemplateData: {
                    basic: {
                        // splashScreen: {
                        //     status: false, // false|true
                        //     src: '',
                        //     countdown: 5,
                        //     url: '',
                        // },
                        // guidePage: {
                        //     status: false, // false|true
                        //     list: [],
                        // },
                        tabbar: {
                            mode: 1, // 1 2
                            layout: 1, // 1=文字+图片 2=文字 3=图片
                            inactiveColor: '#EEEEEE',
                            activeColor: '#000000',
                            list: [],
                            background: {
                                type: 'color', // color=纯色 image=背景图
                                bgImage: '',
                                bgColor: '#FFFFFF',
                            },
                        },
                        floatMenu: {
                            show: 0, // 0|1
                            mode: 1, // 1|2
                            isText: 0, // 0|2
                            list: [
                                {
                                    src: '',
                                    url: '',
                                    title: {
                                        text: '',
                                        color: '',
                                    },
                                },
                            ],
                        },
                        popupImage: {
                            list: [],
                        },
                        theme: 'orange',
                    },
                    home: {
                        data: [],
                        style: {
                            background: {
                                color: '#F6F6F6',
                                src: '',
                            },
                            navbar: {
                                mode: 'normal', // normal inner
                                alwaysShow: 0, // 0 1
                                type: 'color',
                                color: '',
                                src: '',
                                list: {
                                    mp: [],
                                    app: [],
                                },
                            },
                        },
                    },
                    user: {
                        data: [
                            {
                                type: 'userCard',
                                style: {
                                    marginLeft: 0,
                                    marginRight: 0,
                                    marginTop: 0,
                                    marginBottom: 10,
                                    borderRadiusTop: 0,
                                    borderRadiusBottom: 0,
                                    background: {
                                        type: 'color',
                                        bgImage: '',
                                        bgColor: '#FFFFFF',
                                    },
                                },
                            },
                        ],
                        style: {
                            background: {
                                color: '#F6F6F6',
                                src: '',
                            },
                            navbar: {
                                mode: 'normal', // normal inner
                                alwaysShow: 0, // 0 1
                                type: 'color',
                                color: '',
                                src: '',
                                list: {
                                    mp: [],
                                    app: [],
                                },
                            },
                        },
                    },
                    diypage: {
                        data: [],
                        style: {
                            background: {
                                color: '',
                                src: '',
                            },
                            navbar: {
                                mode: 'normal', // normal inner
                                alwaysShow: 0, // 0 1
                                type: 'color',
                                color: '',
                                src: '',
                                list: {
                                    mp: [],
                                    app: [],
                                },
                            },
                        },
                    },
                },
                compNameObj: {
                    tabbar: {
                        label: '底部导航',
                        item: {
                            inactiveIcon: '',
                            activeIcon: '',
                            url: '',
                            text: '',
                        }
                    },
                    floatMenu: {
                        label: '悬浮按钮',
                        item: {
                            src: '',
                            url: '',
                            title: {
                                text: '',
                                color: '',
                            },
                        }
                    },
                    popupImage: {
                        label: '弹窗广告',
                        item: {
                            src: '',
                            url: '',
                            show: 1
                        }
                    },
                    page: {
                        label: '页面设置',
                        item: {
                            width: 0,
                            height: 0,
                            top: 0,
                            left: 0,
                            minRow: 0,
                            maxRow: 0,
                            minCol: 0,
                            maxCol: 0,
                            type: 'text',
                            text: '',
                            textColor: '#111111',
                            src: '',
                            url: '',
                            placeholder: '',
                            borderRadius: 0,
                        },
                        scale: 38,
                        map: { tr: 1, td: 8 }
                    },
                    userCard: {
                        label: '会员卡片',
                        item: {}
                    },
                    orderCard: {
                        label: '订单卡片',
                        item: {}
                    },
                    walletCard: {
                        label: '资产卡片',
                        item: {}
                    },
                    couponCard: {
                        label: '卡券卡片',
                        item: {}
                    },
                    searchBlock: {
                        label: '搜索框',
                        item: {
                            text: '',
                            color: '',
                        }
                    },
                    noticeBlock: {
                        label: '公告栏',
                        item: {
                            text: '',
                            color: '',
                        }
                    },
                    menuButton: {
                        label: '菜单导航',
                        item: {
                            src: '',
                            title: {
                                text: '',
                                color: '#000',
                            },
                            url: '',
                            badge: {
                                show: 0,
                                text: '',
                                color: '#FFFFFF',
                                bgColor: '#FF6000',
                            },
                        }
                    },
                    menuList: {
                        label: '列表导航',
                        item: {
                            src: '',
                            title: {
                                text: '',
                                color: '#333',
                            },
                            tip: {
                                text: '',
                                color: '#bbb',
                            },
                            url: '',
                        }
                    },
                    menuGrid: {
                        label: '宫格导航',
                        item: {
                            src: '',
                            title: {
                                text: '',
                                color: '#333',
                            },
                            tip: {
                                text: '',
                                color: '#bbb',
                            },
                            url: '',
                            badge: {
                                show: 0,
                                text: '',
                                color: '#FFFFFF',
                                bgColor: '#FF6000',
                            },
                        }
                    },
                    goodsCard: {
                        label: '商品卡片',
                        item: {},
                        fieldLabel: {
                            title: '商品标题',
                            subtitle: '副标题',
                            price: '商品价格',
                            original_price: '原价',
                            sales: '销量',
                            stock: '库存',
                        },
                        width: {
                            1: '100%',
                            2: '50%',
                            3: '100%',
                        }
                    },
                    goodsShelves: {
                        label: '商品栏',
                        item: {},
                        fieldLabel: {
                            title: '商品标题',
                            price: '商品价格',
                        },
                        width: {
                            1: '50%',
                            2: '33.3%',
                            3: '32%',
                        }
                    },
                    imageBlock: {
                        label: '图片展示',
                        item: {}
                    },
                    imageBanner: {
                        label: '图片轮播',
                        item: {
                            title: '',
                            type: 'image',
                            src: '',
                            poster: '',
                            url: ''
                        }
                    },
                    titleBlock: {
                        label: '标题栏',
                        item: {}
                    },
                    imageCube: {
                        label: '广告魔方',
                        item: {}
                    },
                    videoPlayer: {
                        label: '视频播放',
                        item: {}
                    },
                    lineBlock: {
                        label: '辅助线',
                        item: {}
                    },
                    richtext: {
                        label: '富文本',
                        item: {}
                    },
                    hotzone: {
                        label: '热区',
                        item: {}
                    },
                    groupon: {
                        label: '拼团',
                        item: {},
                        fieldLabel: {
                            1: {
                                title: '标题',
                                price: '价格',
                            },
                            2: {
                                title: '商品标题',
                                subtitle: '副标题',
                                price: '商品价格',
                                original_price: '原价',
                                sales: '销量',
                            },
                        },
                    },
                    seckill: {
                        label: '秒杀',
                        item: {},
                        fieldLabel: {
                            1: {
                                title: '标题',
                                price: '价格',
                                // original_price: '原价',
                            },
                            2: {
                                title: '商品标题',
                                subtitle: '副标题',
                                price: '商品价格',
                                original_price: '原价',
                                sales: '销量',
                            },
                        },
                    },
                    scoreGoods: {
                        label: '积分商城',
                        item: {},
                        fieldLabel: {
                            title: '商品标题',
                            subtitle: '副标题',
                            score_price: '兑换颜色',
                            price: '原价',
                        },
                    },
                    mplive: {
                        label: '小程序直播',
                        item: {},
                        fieldLabel: {
                            name: '直播标题',
                            anchor_name: '主播昵称',
                        },
                        width: {
                            1: '100%',
                            2: '50%',
                        },
                    },
                    coupon: {
                        label: '优惠券',
                        item: {},
                        width: {
                            1: '90%',
                            2: '50%',
                            3: '33.3%',
                        },
                    },
                },
                themeColor: {
                    orange: {
                        color1: '#FF6000',
                        color2: '#FE832A',
                    },
                    golden: {
                        color1: '#E9B461',
                        color2: '#EECC89',
                    },
                    yellow: {
                        color1: '#FFC300',
                        color2: '#FDDF47',
                    },
                    black: {
                        color1: '#484848',
                        color2: '#6D6D6D',
                    },
                    green: {
                        color1: '#2AAE67',
                        color2: '#3ACD72',
                    },
                    purple: {
                        color1: '#652ABF',
                        color2: '#A36FFF',
                    },
                },
                cloneComponent: (type, theme = 'orange') => {
                    let comp = {
                        userCard: {
                            type: 'userCard',
                            style: {
                                background: {
                                    type: 'color',
                                    bgImage: '',
                                    bgColor: '#FFFFFF',
                                },
                                marginLeft: 0,
                                marginRight: 0,
                                marginTop: 0,
                                marginBottom: 10,
                                borderRadiusTop: 0,
                                borderRadiusBottom: 0,
                                padding: 0,
                            },
                        },
                        orderCard: {
                            type: 'orderCard',
                            style: {
                                background: {
                                    type: 'color',
                                    bgImage: '',
                                    bgColor: '#FFFFFF',
                                },
                                marginLeft: 0,
                                marginRight: 0,
                                marginTop: 0,
                                marginBottom: 10,
                                borderRadiusTop: 0,
                                borderRadiusBottom: 0,
                                padding: 0,
                            },
                        },
                        walletCard: {
                            type: 'walletCard',
                            style: {
                                background: {
                                    type: 'color',
                                    bgImage: '',
                                    bgColor: '#FFFFFF',
                                },
                                marginLeft: 0,
                                marginRight: 0,
                                marginTop: 0,
                                marginBottom: 10,
                                borderRadiusTop: 0,
                                borderRadiusBottom: 0,
                                padding: 0,
                            },
                        },
                        couponCard: {
                            type: 'couponCard',
                            style: {
                                background: {
                                    type: 'color',
                                    bgImage: '',
                                    bgColor: '#FFFFFF',
                                },
                                marginLeft: 0,
                                marginRight: 0,
                                marginTop: 0,
                                marginBottom: 10,
                                borderRadiusTop: 0,
                                borderRadiusBottom: 0,
                                padding: 0,
                            },
                        },
                        searchBlock: {
                            type: 'searchBlock',
                            data: {
                                placeholder: '',
                                borderRadius: 0,
                                keywords: [
                                    // {
                                    //     text: '',
                                    //     color: '#8C8C8C',
                                    // }
                                ],
                            },
                            style: {
                                background: {
                                    type: 'color',
                                    bgImage: '',
                                    bgColor: '#FFFFFF',
                                },
                                marginLeft: 0,
                                marginRight: 0,
                                marginTop: 0,
                                marginBottom: 10,
                                borderRadiusTop: 0,
                                borderRadiusBottom: 0,
                                padding: 0,
                            },
                        },
                        noticeBlock: {
                            type: 'noticeBlock',
                            data: {
                                mode: 1,
                                src: '/assets/addons/shopro/img/decorate/notice/1.png',
                                title: {
                                    text: '',
                                    color: '#111111',
                                },
                                url: '',
                            },
                            style: {
                                background: {
                                    type: 'color',
                                    bgImage: '',
                                    bgColor: '#FFFFFF',
                                },
                                marginLeft: 0,
                                marginRight: 0,
                                marginTop: 0,
                                marginBottom: 10,
                                borderRadiusTop: 0,
                                borderRadiusBottom: 0,
                                padding: 0,
                            },
                        },
                        menuButton: {
                            type: 'menuButton',
                            data: {
                                layout: 1, // 1=图片+文字 2=图片
                                col: 3, // 列数 3|4|5
                                row: 1, // 1 2 3行数 超出滑动
                                list: [
                                    // {
                                    //     src: '',
                                    //     title: {
                                    //         text: '',
                                    //         color: '#000'
                                    //     },
                                    //     url: '',
                                    //     badge: {
                                    //         show: 0, // 0|1
                                    //         text: '',
                                    //         color: '#FFFFFF',
                                    //         bgColor: '#FF6000',
                                    //     },
                                    // }
                                ],
                            },
                            style: {
                                background: {
                                    type: 'color',
                                    bgImage: '',
                                    bgColor: '#FFFFFF',
                                },
                                marginLeft: 0,
                                marginRight: 0,
                                marginTop: 0,
                                marginBottom: 10,
                                borderRadiusTop: 0,
                                borderRadiusBottom: 0,
                                padding: 0,
                            },
                        },
                        menuList: {
                            type: 'menuList',
                            data: {
                                list: [
                                    // {
                                    //     src: '',
                                    //     title: {
                                    //         text: '',
                                    //         color: '#333'
                                    //     },
                                    //     tip: {
                                    //         text: '',
                                    //         color: '#bbb'
                                    //     },
                                    //     url: '',
                                    // }
                                ],
                            },
                            style: {
                                background: {
                                    type: 'color',
                                    bgImage: '',
                                    bgColor: '#FFFFFF',
                                },
                                marginLeft: 0,
                                marginRight: 0,
                                marginTop: 0,
                                marginBottom: 10,
                                borderRadiusTop: 0,
                                borderRadiusBottom: 0,
                                padding: 0,
                            },
                        },
                        menuGrid: {
                            type: 'menuGrid',
                            data: {
                                col: 3, // 列数 3|4
                                // border: 0, // 边框 0|1
                                list: [
                                    {
                                        src: '',
                                        title: {
                                            text: '',
                                            color: '#333',
                                        },
                                        tip: {
                                            text: '',
                                            color: '#bbb',
                                        },
                                        url: '',
                                        badge: {
                                            show: 0, // 0|1
                                            text: '',
                                            color: '#FFFFFF',
                                            bgColor: '#FF6000',
                                        },
                                    },
                                ],
                            },
                            style: {
                                background: {
                                    type: 'color',
                                    bgImage: '',
                                    bgColor: '#FFFFFF',
                                },
                                marginLeft: 0,
                                marginRight: 0,
                                marginTop: 0,
                                marginBottom: 10,
                                borderRadiusTop: 0,
                                borderRadiusBottom: 0,
                                padding: 0,
                            },
                        },
                        goodsCard: {
                            type: 'goodsCard',
                            data: {
                                mode: 1,
                                goodsFields: {
                                    title: {
                                        show: 1, // 0|1
                                        color: '#000',
                                    },
                                    subtitle: {
                                        show: 1, // 0|1
                                        color: '#999',
                                    },
                                    price: {
                                        show: 1, // 0|1
                                        color: '#ff3000',
                                    },
                                    original_price: {
                                        show: 1, // 0|1
                                        color: '#c4c4c4',
                                    },
                                    sales: {
                                        show: 1, // 0|1
                                        color: '#c4c4c4',
                                    },
                                    stock: {
                                        show: 0, // 0|1
                                        color: '#c4c4c4',
                                    },
                                },
                                buyNowStyle: {
                                    mode: 1,
                                    text: '立即购买',
                                    color1: Controller.data().themeColor[theme].color1,
                                    color2: Controller.data().themeColor[theme].color2,
                                    src: '',
                                },
                                tagStyle: {
                                    show: 0,
                                    src: '',
                                },
                                goodsIds: [],
                                goodsList: [],
                                borderRadiusTop: 0,
                                borderRadiusBottom: 0,
                                space: 8,
                            },
                            style: {
                                background: {
                                    type: 'color',
                                    bgImage: '',
                                    bgColor: '',
                                },
                                marginLeft: 8,
                                marginRight: 8,
                                marginTop: 0,
                                marginBottom: 10,
                                borderRadiusTop: 0,
                                borderRadiusBottom: 0,
                                padding: 0,
                            },
                        },
                        goodsShelves: {
                            type: 'goodsShelves',
                            data: {
                                mode: 1,
                                goodsFields: {
                                    title: {
                                        show: 1, // 0|1
                                        color: '#333',
                                    },
                                    price: {
                                        show: 1, // 0|1
                                        color: '#ff3000',
                                    },
                                },
                                tagStyle: {
                                    show: 0,
                                    src: '',
                                },
                                goodsIds: [],
                                goodsList: [],
                                borderRadiusTop: 0,
                                borderRadiusBottom: 0,
                                space: 0,
                            },
                            style: {
                                background: {
                                    type: 'color',
                                    bgImage: '',
                                    bgColor: '#FFFFFF',
                                },
                                marginLeft: 0,
                                marginRight: 0,
                                marginTop: 0,
                                marginBottom: 10,
                                borderRadiusTop: 0,
                                borderRadiusBottom: 0,
                                padding: 0,
                            },
                        },
                        imageBlock: {
                            type: 'imageBlock',
                            data: {
                                src: '',
                                url: '',
                            },
                            style: {
                                background: {
                                    type: 'color',
                                    bgImage: '',
                                    bgColor: '#FFFFFF',
                                },
                                marginLeft: 0,
                                marginRight: 0,
                                marginTop: 0,
                                marginBottom: 10,
                                borderRadiusTop: 0,
                                borderRadiusBottom: 0,
                                padding: 0,
                                // height: 300,
                            },
                        },
                        imageBanner: {
                            type: 'imageBanner',
                            data: {
                                mode: 1, // 1 2
                                indicator: 1, // 1 2
                                autoplay: false,
                                interval: 3000,
                                list: [
                                    // {
                                    //     title: '',
                                    //     type: 'image',
                                    //     src: '',
                                    //     poster: '',
                                    //     url: ''
                                    // }
                                ],
                                space: 0,
                            },
                            style: {
                                background: {
                                    type: 'color',
                                    bgImage: '',
                                    bgColor: '#FFFFFF',
                                },
                                marginLeft: 0,
                                marginRight: 0,
                                marginTop: 0,
                                marginBottom: 10,
                                borderRadiusTop: 0,
                                borderRadiusBottom: 0,
                                padding: 0,
                                // height: 300,
                            },
                        },
                        titleBlock: {
                            type: 'titleBlock',
                            data: {
                                src: '/assets/addons/shopro/img/decorate/title/1.png',
                                location: 'left', // left=居左 center=居中
                                skew: 0,
                                title: {
                                    text: '标题栏',
                                    color: '#111111',
                                    textFontSize: 14,
                                    other: [], // bold=加粗 italic=倾斜
                                },
                                subtitle: {
                                    text: '副标题',
                                    color: '#8c8c8c',
                                    textFontSize: 12,
                                    other: [], // bold=加粗 italic=倾斜
                                },
                                more: {
                                    show: 0, // 0=不显示 1=显示
                                    url: '',
                                },
                            },
                            style: {
                                background: {
                                    type: 'color',
                                    bgImage: '',
                                    bgColor: '#FFFFFF',
                                },
                                marginLeft: 0,
                                marginRight: 0,
                                marginTop: 0,
                                marginBottom: 0,
                                borderRadiusTop: 0,
                                borderRadiusBottom: 0,
                                padding: 0,
                                height: 40,
                            },
                        },
                        imageCube: {
                            type: 'imageCube',
                            data: {
                                borderRadiusTop: 0,
                                borderRadiusBottom: 0,
                                space: 0,
                                list: [
                                    // {
                                    //     width: 0,
                                    //     height: 0,
                                    //     top: 0,
                                    //     left: 0,
                                    //     src: '',
                                    //     url: ''
                                    // }
                                ],
                            },
                            style: {
                                background: {
                                    type: 'color',
                                    bgImage: '',
                                    bgColor: '#FFFFFF',
                                },
                                marginLeft: 0,
                                marginRight: 0,
                                marginTop: 0,
                                marginBottom: 10,
                                borderRadiusTop: 0,
                                borderRadiusBottom: 0,
                                padding: 0,
                            },
                        },
                        videoPlayer: {
                            type: 'videoPlayer',
                            data: {
                                videoUrl: '', // 视频地址
                                src: '' // 视频封面
                            },
                            style: {
                                background: {
                                    type: 'color',
                                    bgImage: '',
                                    bgColor: '#FFFFFF',
                                },
                                marginLeft: 0,
                                marginRight: 0,
                                marginTop: 0,
                                marginBottom: 10,
                                borderRadiusTop: 0,
                                borderRadiusBottom: 0,
                                padding: 0,
                                height: 300,
                            },
                        },
                        lineBlock: {
                            type: 'lineBlock',
                            data: {
                                mode: 'solid', // solid dotted dashed
                                lineColor: '',
                            },
                            style: {
                                background: {
                                    type: 'color',
                                    bgImage: '',
                                    bgColor: '#FFFFFF',
                                },
                                marginLeft: 0,
                                marginRight: 0,
                                marginTop: 0,
                                marginBottom: 10,
                                padding: 0,
                            },
                        },
                        richtext: {
                            type: 'richtext',
                            data: {
                                id: '',
                                title: '',
                            },
                            style: {
                                background: {
                                    type: 'color',
                                    bgImage: '',
                                    bgColor: '#FFFFFF',
                                },
                                marginLeft: 0,
                                marginRight: 0,
                                marginTop: 0,
                                marginBottom: 10,
                                padding: 0,
                            },
                        },
                        hotzone: {
                            type: 'hotzone',
                            data: {
                                src: '',
                                list: [],
                            },
                            style: {
                                background: {
                                    type: 'color',
                                    bgImage: '',
                                    bgColor: '#FFFFFF',
                                },
                                // marginLeft: 0,
                                // marginRight: 0,
                                // marginTop: 0,
                                // marginBottom: 10,
                                // padding: 0,
                            },
                        },
                        groupon: {
                            type: 'groupon',
                            data: {
                                activityId: '',
                                activityList: [],
                                goodsList: [],
                                mode: 1,
                                tagStyle: {
                                    show: 0, // 0,1
                                    src: '',
                                },
                                goodsFields: {
                                    title: {
                                        show: 1, // 0|1
                                        color: '#000',
                                    },
                                    subtitle: {
                                        show: 1, // 0|1
                                        color: '#999',
                                    },
                                    price: {
                                        show: 1, // 0|1
                                        color: '#FF0000',
                                    },
                                    original_price: {
                                        show: 1, // 0|1
                                        color: '#C4C4C4',
                                    },
                                    sales: {
                                        show: 1, // 0|1
                                        color: '#c4c4c4',
                                    },
                                },
                                buyNowStyle: {
                                    mode: 1,
                                    text: '立即拼团',
                                    color1: Controller.data().themeColor[theme].color1,
                                    color2: Controller.data().themeColor[theme].color2,
                                    src: '',
                                },
                                borderRadiusTop: 0,
                                borderRadiusBottom: 0,
                                space: 0,
                            },
                            style: {
                                background: {
                                    type: 'color',
                                    bgImage: '',
                                    bgColor: '#FFFFFF',
                                },
                                marginLeft: 0,
                                marginRight: 0,
                                marginTop: 0,
                                marginBottom: 10,
                                borderRadiusTop: 0,
                                borderRadiusBottom: 0,
                                padding: 0,
                            },
                        },
                        seckill: {
                            type: 'seckill',
                            data: {
                                activityId: '',
                                activityList: [],
                                goodsList: [],
                                mode: 1,
                                tagStyle: {
                                    show: 0, // 0,1
                                    src: '',
                                },
                                goodsFields: {
                                    title: {
                                        show: 1, // 0|1
                                        color: '#000',
                                    },
                                    subtitle: {
                                        show: 1, // 0|1
                                        color: '#999',
                                    },
                                    price: {
                                        show: 1, // 0|1
                                        color: '#FF0000',
                                    },
                                    original_price: {
                                        show: 1, // 0|1
                                        color: '#C4C4C4',
                                    },
                                    sales: {
                                        show: 1, // 0|1
                                        color: '#c4c4c4',
                                    },
                                },
                                buyNowStyle: {
                                    mode: 1,
                                    text: '去抢购',
                                    color1: Controller.data().themeColor[theme].color1,
                                    color2: Controller.data().themeColor[theme].color2,
                                    src: '',
                                },
                                borderRadiusTop: 0,
                                borderRadiusBottom: 0,
                                space: 0,
                            },
                            style: {
                                background: {
                                    type: 'color',
                                    bgImage: '',
                                    bgColor: '#FFFFFF',
                                },
                                marginLeft: 0,
                                marginRight: 0,
                                marginTop: 0,
                                marginBottom: 10,
                                borderRadiusTop: 0,
                                borderRadiusBottom: 0,
                                padding: 0,
                            },
                        },
                        scoreGoods: {
                            type: 'scoreGoods',
                            data: {
                                goodsIds: [],
                                goodsList: [],
                                mode: 1,
                                goodsFields: {
                                    title: {
                                        show: 1, // 0|1
                                        color: '#333',
                                    },
                                    subtitle: {
                                        show: 1, // 0|1
                                        color: '#999',
                                    },
                                    score_price: {
                                        show: 1, // 0|1
                                        color: '#FF3000',
                                    },
                                    price: {
                                        show: 1, // 0|1
                                        color: '#C4C4C4',
                                    },
                                },
                                buyNowStyle: {
                                    mode: 1,
                                    text: '去兑换',
                                    color1: Controller.data().themeColor[theme].color1,
                                    color2: Controller.data().themeColor[theme].color2,
                                    src: '',
                                },
                                borderRadiusTop: 0,
                                borderRadiusBottom: 0,
                                space: 0,
                            },
                            style: {
                                background: {
                                    type: 'color',
                                    bgImage: '',
                                    bgColor: '#FFFFFF',
                                },
                                marginLeft: 0,
                                marginRight: 0,
                                marginTop: 0,
                                marginBottom: 10,
                                borderRadiusTop: 0,
                                borderRadiusBottom: 0,
                                padding: 0,
                            },
                        },
                        mplive: {
                            type: 'mplive',
                            data: {
                                mode: 1,
                                goodsFields: {
                                    name: {
                                        show: 1, // 0|1
                                        color: '#FDFDFD',
                                    },
                                    anchor_name: {
                                        show: 1, // 0|1
                                        color: '#FDFDFD',
                                    },
                                },
                                mpliveIds: [],
                                mpliveList: [],
                                borderRadiusTop: 0,
                                borderRadiusBottom: 0,
                                space: 8,
                            },
                            style: {
                                background: {
                                    type: 'color',
                                    bgImage: '',
                                    bgColor: '',
                                },
                                marginLeft: 8,
                                marginRight: 8,
                                marginTop: 0,
                                marginBottom: 10,
                                borderRadiusTop: 0,
                                borderRadiusBottom: 0,
                                padding: 0,
                            },
                        },
                        coupon: {
                            type: 'coupon',
                            data: {
                                couponIds: [],
                                couponList: [],
                                mode: 1,
                                fill: {
                                    color: '',
                                    bgImage: '',
                                },
                                button: {
                                    color: '',
                                    bgColor: '',
                                },
                                space: 0,
                            },
                            style: {
                                background: {
                                    type: 'color',
                                    bgImage: '',
                                    bgColor: '#FFFFFF',
                                },
                                marginLeft: 0,
                                marginRight: 0,
                                marginTop: 0,
                                marginBottom: 10,
                                borderRadiusTop: 0,
                                borderRadiusBottom: 0,
                                padding: 0,
                            },
                        },
                    };
                    return comp[type];
                },
                handleTempData: (data) => {
                    data.data.forEach((t) => {
                        if (['goodsCard', 'goodsShelves'].includes(t.type)) {
                            Fast.api.ajax({
                                url: 'shopro/goods/goods/select',
                                type: 'GET',
                                data: {
                                    type: 'select',
                                    search: JSON.stringify({ id: [t.data.goodsIds.join(','), 'in'] })
                                },
                            }, function (ret, res) {
                                t.data.goodsList = res.data
                                return false
                            }, function (ret, res) {
                                t.data.goodsList = []
                            })
                        } else if (t.type == 'coupon') {
                            Fast.api.ajax({
                                url: 'shopro/coupon/select',
                                type: 'GET',
                                data: {
                                    type: 'select',
                                    search: JSON.stringify({ id: [t.data.couponIds.join(','), 'in'], status: ['normal'] })
                                },
                            }, function (ret, res) {
                                t.data.couponList = res.data
                                return false
                            }, function (ret, res) {
                                t.data.couponList = []
                            })
                        } else if (t.type == 'richtext') {
                            Fast.api.ajax({
                                url: 'shopro/data/richtext/select',
                                type: 'GET',
                                data: {
                                    type: 'find',
                                    search: JSON.stringify({ id: [t.data.id, 'in'] }),
                                },
                            }, function (ret, res) {
                                t.data.richtext = res.data
                                return false
                            }, function (ret, res) {
                                t.data.richtext = ''
                            })
                        } else if (t.type == 'groupon' || t.type == 'seckill') {
                            if (t.data.activityId) {
                                Fast.api.ajax({
                                    url: `shopro/activity/activity/detail/id/${t.data.activityId}`,
                                    type: 'GET',
                                }, function (ret, res) {
                                    t.data.activityList = [res.data];
                                    Fast.api.ajax({
                                        url: 'shopro/goods/goods/activitySelect',
                                        type: 'GET',
                                        data: {
                                            activity_id: t.data.activityId,
                                            need_buyers: t.type == 'groupon' ? 1 : 0,
                                        },
                                    }, function (ret, res) {
                                        t.data.goodsList = res.data
                                        return false
                                    }, function (ret, res) {
                                        t.data.goodsList = []
                                    })
                                    return false
                                }, function (ret, res) {
                                    t.data.activityList = [];
                                    t.data.goodsList = [];
                                })
                            } else {
                                t.data.activityList = [];
                                t.data.goodsList = [];
                            }
                        } else if (t.type == 'scoreGoods') {
                            Fast.api.ajax({
                                url: 'shopro/app/score_shop/select',
                                type: 'GET',
                                data: {
                                    type: 'select',
                                    search: JSON.stringify({ id: [t.data.goodsIds.join(','), 'in'] }),
                                },
                            }, function (ret, res) {
                                t.data.goodsList = res.data
                                return false
                            }, function (ret, res) {
                                t.data.goodsList = []
                            })
                        } else if (t.type == 'mplive') {
                            Fast.api.ajax({
                                url: 'shopro/app/mplive/room/select',
                                type: 'GET',
                                data: {
                                    type: 'select',
                                    search: JSON.stringify({ roomid: [t.data.mpliveIds.join(','), 'in'] }),
                                },
                            }, function (ret, res) {
                                t.data.mpliveList = res.data
                                return false
                            }, function (ret, res) {
                                t.data.mpliveList = []
                            })
                        }
                    });

                    return data;
                },
                handleSubmitData: (data) => {
                    data.data.forEach((t) => {
                        if (['goodsCard', 'goodsShelves'].includes(t.type)) {
                            t.data.goodsIds = [];
                            t.data.goodsList.forEach((g) => {
                                t.data.goodsIds.push(g.id);
                            });
                            delete t.data.goodsList;
                        } else if (t.type == 'coupon') {
                            t.data.couponIds = [];
                            t.data.couponList?.forEach((c) => {
                                t.data.couponIds.push(c.id);
                            });
                            delete t.data.couponList;
                        } else if (t.type == 'richtext') {
                            delete t.data.richtext;
                        } else if (t.type == 'groupon' || t.type == 'seckill') {
                            t.data.activityId = [];
                            t.data.activityList.forEach((c) => {
                                t.data.activityId.push(c.id);
                            });
                            t.data.activityId = t.data.activityId.join(',');
                            delete t.data.activityList;
                            delete t.data.goodsList;
                        } else if (t.type == 'scoreGoods') {
                            t.data.goodsIds = [];
                            t.data.goodsList.forEach((c) => {
                                t.data.goodsIds.push(c.id);
                            });
                            delete t.data.goodsList;
                        } else if (t.type == 'mplive') {
                            t.data.mpliveIds = [];
                            t.data.mpliveList.forEach((c) => {
                                t.data.mpliveIds.push(c.roomid);
                            });
                            delete t.data.mpliveList;
                        }
                    });

                    return data;
                }
            }
        },
        index: function () {
            const { pageTypeList, systemList, platformList, pageLeft, defaultTemplateData, compNameObj, themeColor, cloneComponent, handleTempData, handleSubmitData } = Controller.data()
            const { ref, reactive, onMounted, computed, nextTick } = Vue
            const { ElMessageBox, ElMessage } = ElementPlus
            const index = {
                setup() {
                    const state = reactive({
                        id: new URLSearchParams(location.search).get('id'),
                        from: new URLSearchParams(location.search).get('from') || 'tempalte',
                        pageType: 'basic',
                        systemType: 'android',
                        platformType: 'WechatMiniProgram',

                        collapseLeftPanel: false,
                        collapseRightPanel: false,
                    })

                    function onChangePageType(type) {
                        if (JSON.stringify(centerData.templateData[state.pageType]) == JSON.stringify(centerData.tempTemplate[state.pageType])) {
                            initChangePageType(type);
                            return false;
                        }
                        ElMessageBox.confirm('是否保存数据', {
                            confirmButtonText: '保存',
                            cancelButtonText: '放弃',
                            type: 'warning',
                        })
                            .then(async () => {
                                // 保存
                                await onSave();
                                // 请求数据
                                initChangePageType(type, item);
                            })
                            .catch(() => {
                                // 放弃修改
                                initChangePageType(type);
                            });
                    }
                    function initChangePageType(type) {
                        state.pageType = type
                        currentComp.index = -2
                        currentComp.type = null
                        currentComp.right = {}
                        getData(state.pageType);
                    }

                    const leftData = reactive({
                        collapsePanel: true,
                        activeCollapse: ['basic', 'theme', '0', '1', '2', '3', '4'],
                        activeBasic: 'tabbar',
                        activeTheme: 'orange',
                        saveHtml: '',
                    })
                    function onSelectLeftBasic(type, value) {
                        if (type == 'basic') {
                            leftData.activeBasic = value
                            currentComp.index = 0;
                            currentComp.type = value;
                            currentComp.right = centerData.templateData[type][value];
                            rightData.activeTab = 'data'
                        }
                        if (type == 'theme') {
                            leftData.activeTheme = value
                            centerData.templateData.basic[type] = value;
                        }
                        console.log(currentComp, 'currentComp')
                    }
                    function onSelectLeftComp(type) {
                        if (currentComp.index == -2 || currentComp.index == -1) {
                            currentComp.index = centerData.templateData[state.pageType].data.length;
                        } else {
                            currentComp.index = currentComp.index + 1;
                        }
                        centerData.templateData[state.pageType].data.splice(currentComp.index, 0, cloneComponent(type, centerData.templateData?.basic?.theme));
                        onSelectComp(currentComp.index, type)
                    }
                    function onLeftStart(e) {
                        leftData.saveHtml = e.clone.innerHTML;
                    }
                    function onLeftMove(e) {
                        if (e.to.className.indexOf('comp-wrap') != -1) {
                            e.dragged.innerHTML = `<div style="padding:0 20px;width:100%;height:50px;line-height:50px;background:var(--el-color-primary);color:#fff">新添加的元素</div>`;
                        } else {
                            return false;
                        }
                    }
                    function onLeftEnd(e) {
                        if (e.to.className.indexOf('comp-wrap') != -1) {
                            e.item.innerHTML = leftData.saveHtml;
                            let type = e.item.classList[1];

                            centerData.templateData[state.pageType].data.splice(e.newIndex, 0, cloneComponent(type, centerData.templateData?.basic
                                ?.theme));
                            centerData.templateData[state.pageType].data.splice(JSON.parse(JSON.stringify(e.newIndex)) + 1, 1);

                            onSelectComp(e.newIndex, type)
                        }
                    }

                    const centerData = reactive({
                        templateData: {},
                        tempTemplate: {},
                        floatMenuIsFold: false,
                        popupImageCurrent: -1,
                    })

                    function onUpdatePageSetting() {
                        console.log(1)
                        if (state.pageType != 'basic') {
                            console.log(2)
                            currentComp.index = -1;
                            currentComp.type = 'page';
                            currentComp.right = centerData.templateData[state.pageType].style;
                            rightData.activeTab = 'data'
                            console.log(currentComp, 'currentComp')
                        }
                    }

                    function imageCubeScale(element) {
                        return (375 -
                            element.style.marginLeft -
                            element.style.marginRight -
                            element.style.padding * 2 +
                            element.data.space) /
                            4
                    }

                    function imageCubeStyle(element) {
                        let height =
                            element.data.list.length > 0
                                ? element.data.list.reduce((prev, next) => {
                                    return prev.top > next.top || (prev.top == next.top && prev.height > next.height)
                                        ? prev
                                        : next;
                                })
                                : 30;
                        return {
                            margin: `-${element.data.space / 2}px`,
                            height: (height.top + height.height) * imageCubeScale(element) + 'px',
                            position: 'relative',
                        };
                    }

                    const currentComp = reactive({
                        index: null, // -2=未选中|-1=选中页面
                        type: null,
                        right: {}
                    })

                    function onCenterMove(e, originalEvent) {
                        if (originalEvent.target.className.indexOf('undraggable') != -1) {
                            return false;
                        }
                        if (
                            state.pageType == 'user' &&
                            e.draggedContext.futureIndex == 0 &&
                            e.draggedContext.element.type != 'userCard'
                        ) {
                            return false;
                        }
                    }
                    function onCenterEnd(e) {
                        if (currentComp.index != -1) {
                            currentComp.index = e.newIndex;
                            currentComp.right = centerData.templateData[state.pageType].data[currentComp.index];
                            currentComp.type = currentComp.right.type;
                        }
                    }

                    function onSelectComp(index, type) {
                        // index=索引|type=组件类型
                        currentComp.index = index;
                        currentComp.type = type;
                        currentComp.right = centerData.templateData[state.pageType].data[currentComp.index];
                        rightData.activeTab = Object.keys(currentComp.right).includes('data') ? 'data' : 'style'
                        if (!rightData.collapsePanel) {
                            rightData.collapsePanel = true;
                        }
                    }

                    function onUpComp(index) {
                        centerData.templateData[state.pageType].data.splice(
                            index - 1,
                            2,
                            centerData.templateData[state.pageType].data[index],
                            centerData.templateData[state.pageType].data[index - 1],
                        );
                        onSelectComp(index - 1, centerData.templateData[state.pageType].data[index - 1].type);
                    }
                    function onDownComp(index) {
                        centerData.templateData[state.pageType].data.splice(
                            index,
                            2,
                            centerData.templateData[state.pageType].data[index + 1],
                            centerData.templateData[state.pageType].data[index],
                        );
                        onSelectComp(index + 1, centerData.templateData[state.pageType].data[index + 1].type);
                    }
                    function onCopyComp(index) {
                        centerData.templateData[state.pageType].data.splice(
                            index + 1,
                            0,
                            JSON.parse(JSON.stringify(centerData.templateData[state.pageType].data[index])),
                        );
                        onSelectComp(index + 1, centerData.templateData[state.pageType].data[index].type);
                    }
                    function onDeleteComp(index) {
                        centerData.templateData[state.pageType].data.splice(index, 1);
                        currentComp.index = -2;
                        currentComp.type = null;
                        currentComp.right = {};
                    }

                    function compStyle(element) {
                        if (element.style) {
                            let height = {};
                            let padding = {};
                            if (element.style.height || element.style.height == 0) {
                                height = {
                                    height: `${element.style.height}px`,
                                };
                            }
                            if (element.style.padding || element.style.padding == 0) {
                                padding = {
                                    padding: `${element.style.padding}px`,
                                };
                            }
                            return {
                                background:
                                    element.style.background &&
                                    (element.style.background.type == 'color'
                                        ? element.style.background.bgColor
                                        : element.style.background.bgImage
                                            ? 'url(' + Fast.api.cdnurl(element.style.background.bgImage) + ')'
                                            : ''),
                                'margin-top': element.style.marginTop + 'px',
                                'margin-right': element.style.marginRight + 'px',
                                'margin-bottom': element.style.marginBottom + 'px',
                                'margin-left': element.style.marginLeft + 'px',
                                'border-top-left-radius': element.style.borderRadiusTop + 'px',
                                'border-top-right-radius': element.style.borderRadiusTop + 'px',
                                'border-bottom-left-radius': element.style.borderRadiusBottom + 'px',
                                'border-bottom-right-radius': element.style.borderRadiusBottom + 'px',
                                ...height,
                                ...padding,
                            };
                        }
                    }
                    function pageStyle() {
                        if (state.pageType != 'basic') {
                            if (
                                centerData.templateData[state.pageType] &&
                                centerData.templateData[state.pageType].style &&
                                centerData.templateData[state.pageType].style.background
                            ) {
                                let css = {
                                    'background-color': centerData.templateData[state.pageType].style.background.color,
                                };
                                if (centerData.templateData[state.pageType].style.background.src) {
                                    css = {
                                        ...css,
                                        'background-image':
                                            'url(' + Fast.api.cdnurl(centerData.templateData[state.pageType].style.background.src) + ')',
                                    };
                                }
                                return css;
                            }
                        }
                    }

                    const buttonStyle = computed(() => ({
                        background: themeColor[centerData.templateData.basic.theme || 'orange'].color1,
                    }));

                    const rightData = reactive({
                        collapsePanel: true,
                        tabList: [
                            {
                                name: '内容',
                                type: 'data',
                            },
                            {
                                name: '样式',
                                type: 'style',
                            },
                            {
                                name: '数据',
                                type: 'css',
                            },
                        ],
                        activeTab: 'data',
                        noticeList: [
                            '/assets/addons/shopro/img/decorate/notice/1.png',
                            '/assets/addons/shopro/img/decorate/notice/2.png',
                            '/assets/addons/shopro/img/decorate/notice/3.png'
                        ],
                    })

                    // 标题栏
                    const titleBlockDialog = reactive({
                        visible: false,
                        titleList: [
                            '/assets/addons/shopro/img/decorate/title/1.png'
                        ],
                        active: '/assets/addons/shopro/img/decorate/title/1.png'
                    })
                    function onSelectTitleBlock() {
                        currentComp.right.data.src = titleBlockDialog.active
                        titleBlockDialog.visible = false
                    }
                    function onSelectTitleBlockImage() {
                        Fast.api.open('general/attachment/select', "选择", {
                            callback: function (data) {
                                currentComp.right.data.src = data.url
                            }
                        });
                    }

                    function onSelectVideo() {
                        Fast.api.open('general/attachment/select', "选择", {
                            callback: function (data) {
                                currentComp.right.data.videoUrl = data.url
                            }
                        });
                    }

                    function onSelectRichtext() {
                        Fast.api.open('shopro/data/richtext/select', "选择富文本", {
                            callback: function (data) {
                                currentComp.right.data.id = data.id;
                                currentComp.right.data.title = data.title;
                                currentComp.right.data.richtext = data;
                            }
                        });
                    }

                    const hotzoneDialog = reactive({
                        visible: false,
                        dragFlag : false,
                        mapList: [],
                        currentIndex: null,
                    })
                    function onSetHotzone() {
                        hotzoneDialog.visible = true
                        hotzoneDialog.mapList = JSON.parse(JSON.stringify(currentComp.right.data.list))
                        console.log(hotzoneDialog.mapList,'333')
                    }
                    const srcRef = ref();

                    function onAddHotzone() {
                      hotzoneDialog.mapList.push({
                        width: 200,
                        height: 200,
                        top: 0,
                        left: 0,
                        name: '双击选择链接',
                        url: '',
                      });
                      hotzoneDialog.currentIndex = hotzoneDialog.mapList.length - 1;
                    }
                  
                    function onDeleteHotzone(index) {
                      hotzoneDialog.mapList[index].show = true;
                    }
                  
                    function onSelectHotzone(index) {
                      hotzoneDialog.currentIndex = index;
                    }
                  
                    function onLinkHotzone(index) {
                        Fast.api.open("shopro/data/page/select", "选择链接", {
                            callback(data) {
                                hotzoneDialog.mapList[index].name = data.name;
                                hotzoneDialog.mapList[index].url = data.path;
                            }
                        })
                    }
                  
                    function mousedown(event, index) {
                      let offsetWidth = srcRef.value.width || 750;
                      let offsetHeight = srcRef.value.height;
                  
                      hotzoneDialog.dragFlag = true;
                      hotzoneDialog.currentIndex = index;
                  
                      event = event || window.event;
                      var _target = event.target;
                      var x = event.clientX - _target.offsetLeft;
                      var y = event.clientY - _target.offsetTop;
                  
                      if (event.preventDefault) {
                        event.preventDefault();
                      } else {
                        event.returnValue = false;
                      }
                      document.onmousemove = (event) => {
                        event = event || window.event;
                        if (event.target.dataset.type) {
                          if (hotzoneDialog.dragFlag) {
                            var left = event.clientX - x;
                            var top = event.clientY - y;
                  
                            if (_target.dataset.type === 'item') {
                              if (left <= 0) {
                                left = 0;
                              } else if (left > offsetWidth - _target.offsetWidth) {
                                left = offsetWidth - _target.offsetWidth;
                              }
                              if (top <= 0) {
                                top = 0;
                              } else if (top > offsetHeight - _target.offsetHeight) {
                                top = offsetHeight - _target.offsetHeight;
                              }
                  
                              hotzoneDialog.mapList[hotzoneDialog.currentIndex].left = left;
                              hotzoneDialog.mapList[hotzoneDialog.currentIndex].top = top;
                            }
                  
                            _target.style.left = left + 'px';
                            _target.style.top = top + 'px';
                  
                            if (_target.dataset.type === 'scale') {
                              let width = _target.offsetLeft + _target.clientWidth;
                              let height = _target.offsetTop + _target.clientHeight;
                  
                              if (width + hotzoneDialog.mapList[hotzoneDialog.currentIndex].left > offsetWidth) {
                                width = offsetWidth - hotzoneDialog.mapList[hotzoneDialog.currentIndex].left;
                              }
                              if (height + hotzoneDialog.mapList[hotzoneDialog.currentIndex].top > offsetHeight) {
                                height = offsetHeight - hotzoneDialog.mapList[hotzoneDialog.currentIndex].top;
                              }
                  
                              hotzoneDialog.mapList[hotzoneDialog.currentIndex].width = width;
                              hotzoneDialog.mapList[hotzoneDialog.currentIndex].height = height;
                  
                              _target.style.left = width - _target.offsetWidth + 'px';
                              _target.style.top = height - _target.offsetHeight + 'px';
                            }
                          }
                        } else {
                            hotzoneDialog.dragFlag = false;
                        }
                      };
                    }
                    function mouseup(e) {
                      document.onmousemove = null;
                      hotzoneDialog.dragFlag = false;
                    }
                  
                    function onSaveHotzone() {
                      let arr = hotzoneDialog.mapList.filter((item) => !item.show);
                      let flag = false;
                      arr.forEach((item) => {
                        if (!item.url) {
                          flag = true;
                        }
                      });
                      if (flag) {
                        ElMessage({
                          message: '请选择链接',
                          type: 'warning',
                        });
                        return false;
                      }
                      currentComp.right.data.list = arr;
                      hotzoneDialog.visible = false;
                    }

                    function onSelectGroupon() {
                        Fast.api.open('shopro/activity/activity/select?type=groupon,groupon_ladder', "选择拼团活动", {
                            callback: function (data) {
                                currentComp.right.data.activityList = [data];
                                Fast.api.ajax({
                                    url: 'shopro/goods/goods/activitySelect',
                                    type: 'GET',
                                    data: {
                                        activity_id: data.id,
                                        need_buyers: 1,
                                    },
                                }, function (ret, res) {
                                    currentComp.right.data.goodsList = res.data;
                                    return false
                                }, function (ret, res) { })
                            }
                        });
                    }
                    function onDeleteGroupon() {
                        currentComp.right.data.activityList = [];
                        currentComp.right.data.goodsList = [];
                    }

                    function onSelectSeckill() {
                        Fast.api.open('shopro/activity/activity/select?type=seckill', "选择秒杀活动", {
                            callback: function (data) {
                                currentComp.right.data.activityList = [data];
                                Fast.api.ajax({
                                    url: 'shopro/goods/goods/activitySelect',
                                    type: 'GET',
                                    data: {
                                        activity_id: data.id
                                    },
                                }, function (ret, res) {
                                    currentComp.right.data.goodsList = res.data;
                                    return false
                                }, function (ret, res) { })
                            }
                        });
                    }
                    function onDeleteSeckill() {
                        currentComp.right.data.activityList = [];
                        currentComp.right.data.goodsList = [];
                    }

                    function onSelectScoreGoods() {
                        Fast.api.open('shopro/app/score_shop/select?multiple=true', "选择积分商品", {
                            callback: function (data) {
                                currentComp.right.data.goodsList.push(...data);
                            }
                        });
                    }

                    function onSelectCoupon() {
                        Fast.api.open('shopro/coupon/select?multiple=true&status=normal', "选择优惠券", {
                            callback: function (data) {
                                currentComp.right.data.couponList.push(...data);
                            }
                        });
                    }

                    const templateDetail = ref({});
                    function getTemplateDetail() {
                        Fast.api.ajax({
                            url: `shopro/decorate/template/detail/id/${state.id}`,
                            type: 'GET',
                        }, function (ret, res) {
                            state.pageType = res.data.type == 'template' ? 'basic' : 'diypage'
                            templateDetail.value = res.data
                            getData()
                            return false
                        }, function (ret, res) { })
                    }

                    function getData() {
                        Fast.api.ajax({
                            url: `shopro/decorate/page/detail/id/${state.id}`,
                            type: 'GET',
                            data: {
                                type: state.pageType
                            }
                        }, function (ret, res) {

                            centerData.templateData[state.pageType] = isEmpty(res.data?.page) ? defaultTemplateData[state.pageType] : res.data.page;

                            // home user diypage
                            if (state.pageType != 'basic') {
                                centerData.templateData[state.pageType] = handleTempData(centerData.templateData[state.pageType]);
                            }

                            // 暂存数据
                            centerData.tempTemplate[state.pageType] = JSON.parse(JSON.stringify(centerData.templateData[state.pageType]));

                            // 初始化basic
                            if (state.from == 'template' && state.pageType == 'basic') {
                                onSelectLeftBasic('basic', 'tabbar')
                            }
                            return false
                        }, function (ret, res) { })
                    }

                    function getHtml2canvas() {
                        return new Promise((resolve) => {
                            nextTick(() => {
                                html2canvas(document.getElementById('html2canvasWrap'), {
                                    allowTaint: false,
                                    taintTest: true,
                                    useCORS: true,
                                    height: 1000,
                                    scrollX: 0,
                                    scrollY: 0,
                                    dpi: 100,
                                    scale: 0.7,
                                }).then((canvas) => {
                                    let blob = dataURLToBlob(canvas.toDataURL('image/png'));
                                    var formData = new FormData();
                                    let fileOfBlob = new File([blob], new Date() + '.png');
                                    formData.append('file', fileOfBlob);
                                    formData.append('shopro_type', 'simple');
                                    $.ajax({
                                        type: "POST",
                                        url: "ajax/upload",
                                        data: formData,
                                        cache: false,
                                        processData: false,
                                        contentType: false,
                                        success: function (res) {
                                            if (res.code == 1) {
                                                resolve(res.data.url)
                                            } else {
                                                Toastr.error(res.msg);
                                            }
                                        },
                                    })
                                });
                            });
                        });
                    }
                    function dataURLToBlob(dataurl) {
                        let arr = dataurl.split(',');
                        let mime = arr[0].match(/:(.*?);/)[1];
                        let bstr = atob(arr[1]);
                        let n = bstr.length;
                        let u8arr = new Uint8Array(n);
                        while (n--) {
                            u8arr[n] = bstr.charCodeAt(n);
                        }
                        return new Blob([u8arr], { type: mime });
                    }

                    async function onSave() {
                        let temp = JSON.parse(JSON.stringify(centerData.templateData[state.pageType]));
                        // 截图
                        let pageCover = {};
                        if (state.pageType != 'basic') {
                            pageCover.image = await getHtml2canvas();
                            temp = handleSubmitData(temp);
                        }

                        Fast.api.ajax({
                            url: `shopro/decorate/page/edit/id/${state.id}`,
                            type: 'POST',
                            data: {
                                type: state.pageType,
                                page: JSON.stringify(temp),
                                ...pageCover,
                            }
                        }, function (ret, res) {
                            centerData.tempTemplate[state.pageType] = JSON.parse(JSON.stringify(centerData.templateData[state.pageType]));
                        }, function (ret, res) { })
                    }

                    function onPreview() {
                        let previewData = {
                            id: templateDetail.value.id,
                            name: templateDetail.value.name,
                            type: templateDetail.value.type,
                            platform: templateDetail.value.platform,
                        }
                        localStorage.setItem("preview-data", JSON.stringify(previewData));
                        Fast.api.open(`shopro/decorate/page/preview`, "预览")
                    }

                    onMounted(() => {
                        getTemplateDetail()
                    })

                    return {
                        Fast,
                        pageTypeList,
                        systemList,
                        platformList,
                        pageLeft,
                        defaultTemplateData,
                        compNameObj,
                        state,
                        onChangePageType,
                        leftData,
                        onSelectLeftBasic,
                        onSelectLeftComp,
                        onLeftStart,
                        onLeftMove,
                        onLeftEnd,
                        onUpdatePageSetting,
                        centerData,
                        imageCubeScale,
                        imageCubeStyle,
                        currentComp,
                        onCenterMove,
                        onCenterEnd,
                        onSelectComp,
                        onUpComp,
                        onDownComp,
                        onCopyComp,
                        onDeleteComp,
                        compStyle,
                        pageStyle,
                        buttonStyle,
                        rightData,
                        titleBlockDialog,
                        onSelectTitleBlock,
                        onSelectTitleBlockImage,
                        onSelectVideo,
                        onSelectRichtext,

                        hotzoneDialog,
                        onSetHotzone,
                        srcRef,
                        onAddHotzone,
                        onDeleteHotzone,
                        onSelectHotzone,
                        onLinkHotzone,
                        mousedown,
                        mouseup,
                        onSaveHotzone,

                        onSelectGroupon,
                        onDeleteGroupon,
                        onSelectSeckill,
                        onDeleteSeckill,
                        onSelectScoreGoods,
                        onSelectCoupon,
                        onSave,
                        onPreview,

                        isString,
                    }
                }
            }
            Controller.createApp('index', index);
        },
        preview: () => {
            const { reactive, computed, onMounted, nextTick } = Vue
            const preview = {
                setup() {
                    const state = reactive({
                        detail: JSON.parse(localStorage.getItem("preview-data")) || {},
                        domain: '',
                    })

                    function getConfig() {
                        Fast.api.ajax({
                            url: `shopro/config/basic`,
                            type: 'GET',
                        }, function (ret, res) {
                            state.domain = res.data.domain
                            return false
                        }, function (ret, res) { })
                    }

                    const urlData = computed(() => {
                        let obj
                        if (state.detail.type == 'designer') {
                            obj = {
                                H5: `${state.detail?.h5Url}`,
                                WechatMiniProgram: `${state.detail?.wxacode}`,
                            }
                        } else if (state.detail.type == 'template') {
                            obj = {
                                H5: `${state.domain}pages/index/index?templateId=${state.detail.id}`,
                                WechatMiniProgram: `${window.location.origin}/addons/shopro/third.wechat/wxacode?platform=miniProgram&payload=${encodeURIComponent(
                                    JSON.stringify({
                                        path: `/pages/index/index?templateId=${state.detail.id}`
                                    }),
                                )}`,
                            };
                        } else if (state.detail.type == 'diypage') {
                            obj = {
                                H5: `${state.domain}pages/index/page?id=${state.detail.id}`,
                                WechatMiniProgram: `${window.location.origin}/addons/shopro/third.wechat/wxacode?platform=miniProgram&payload=${encodeURIComponent(
                                    JSON.stringify({
                                        path: `/pages/index/page?id=${state.detail.id}`
                                    }),
                                )}`,
                            };
                        }
                        console.log(obj, 'obj')
                        return obj
                    });

                    const isShowIframe = computed(() => {
                        if (window.location.protocol == 'https:' && urlData.value.H5.split('://')[0] == 'http') {
                            return '您的商城前端域名ssl未开启，<br/>请扫码预览';
                        } else {
                            if (!state.domain) {
                                return '请在商城配置设置您的前端域名';
                            }
                        }
                    });

                    onMounted(() => {
                        getConfig()
                        nextTick(() => {
                            new QRCode(document.getElementById("qrcode"), urlData.value.H5);  // 设置要生成二维码的链接
                        })
                    })

                    return {
                        ...Controller.data(),
                        state,
                        urlData,
                        isShowIframe,
                    }
                }
            }
            createApp('preview', preview);
        },
        createApp: (id, testIndex) => {
            const { createApp } = Vue

            const app = createApp(testIndex)

            app.use(ElementPlus, { locale: ElementPlusLocaleZhCn })
            for (const [key, component] of Object.entries(ElementPlusIconsVue)) {
                app.component(key, component)
            }

            app.component('draggable', window.vuedraggable)

            app.component('sa-image', SaImage)
            app.component('sa-uploader', SaUploader)
            app.component('sa-user-profile', SaUserProfile)
            app.component('sa-filter', SaFilter)
            app.component('sa-pagination', SaPagination)

            app.component('d-color-picker', DColorPicker)
            app.component('d-list', DList)
            app.component('d-url', DUrl)
            app.component('d-text-color', DTextColor)
            app.component('d-slider', DSlider)
            app.component('d-goods-select', DGoodsSelect)
            app.component('d-cube', DCube)

            app.component('center-header', CenterHeader)
            app.component('center-navbar', CenterNavbar)

            app.mount(`#${id}`)
        }
    };
    return Controller;
});
