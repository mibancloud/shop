define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'moment'], function ($, undefined, Backend, Table, Form, Moment) {

    var Controller = {
        add: () => {
            Controller.form();
        },
        edit: () => {
            Controller.form();
        },
        form: () => {
            const { reactive, onMounted, ref } = Vue
            const addEdit = {
                setup() {
                    const state = reactive({
                        type: new URLSearchParams(location.search).get('type'),
                        id: new URLSearchParams(location.search).get('id'),
                        title: new URLSearchParams(location.search).get('title'),
                    })

                    const form = reactive({
                        model: {
                            way: 'column',
                            type: 0,
                            name: '',
                            date_time: '',
                            anchor_name: '',
                            anchor_wechat: '',
                            sub_anchor_wechat: '',
                            is_feeds_public: 1,
                            close_kf: 0,
                            close_replay: 0,
                            close_comment: 0,
                            close_goods: 0,
                            close_like: 0,
                            feeds_img: '',
                            share_img: '',
                            cover_img: '',
                        },
                        rules: {
                            type: [{ required: true, message: '请选择直播类型', trigger: 'change' }],
                            way: [{ required: true, message: '请选择播放方式', trigger: 'change' }],
                            feeds_img: [{ required: true, message: '请选择封面图', trigger: 'change' }],
                            share_img: [{ required: true, message: '请选择分享图', trigger: 'change' }],
                            cover_img: [{ required: true, message: '请选择背景图', trigger: 'change' }],
                            date_time: [{ required: true, message: '请选择开播时间', trigger: 'change' }],
                            name: [{ required: true, message: '请输入直播间标题', trigger: 'blur' }, { validator: checkTitle, trigger: 'change' }],
                            anchor_name: [{ required: true, message: '请输入主播昵称', trigger: 'blur' }, { validator: checkNickname, trigger: 'change' }],
                            anchor_wechat: [{ required: true, message: '请输入主播微信账号', trigger: 'blur' }],
                        },
                    });
                    //获取默认开始时间为当前时间后40分钟
                    const defaultTime = ref([
                        new Date(new Date().getTime() + 40 * 60 * 1000),
                        new Date(2000, 2, 1, 23, 59, 59),
                    ]);
                    // 禁止时间
                    function disabledDate(time) {
                        return time.getTime() < Date.now() - 86400000;
                    }
                    // 获取详情
                    function getDetail() {
                        Fast.api.ajax({
                            url: `shopro/app/mplive/room/detail/id/${state.id}`,
                            type: 'GET',
                        }, function (ret, res) {
                            form.model = res.data;
                            form.model.date_time = [
                                Moment(res.data.start_time * 1000).format('YYYY-MM-DD HH:mm:ss'),
                                Moment(res.data.end_time * 1000).format('YYYY-MM-DD HH:mm:ss'),
                            ];
                            return false
                        }, function (ret, res) { })
                    }

                    function onConfirm() {

                        let submitForm = {
                            ...form.model,
                            start_time: Number(new Date(form.model.date_time[0]).getTime() / 1000),
                            end_time: Number(new Date(form.model.date_time[1]).getTime() / 1000),
                        }
                        delete submitForm.date_time
                        Fast.api.ajax({
                            url: state.type == 'add' ? 'shopro/app/mplive/room/add' : `shopro/app/mplive/room/edit/id/${state.id}`,
                            type: 'POST',
                            data: submitForm,
                        }, function (ret, res) {
                            Fast.api.close()
                        }, function (ret, res) { })
                    }
                    function checkTitle(rule, value, callback) {
                        if (!value) {
                            return callback(new Error('请输入直播间标题'));
                        }
                        const length =
                            value.match(/[^ -~]/g) == null ? value.length : value.length + value.match(/[^ -~]/g).length;
                        if (length < 6 || length > 34) {
                            callback(new Error('直播标题必须为3-17个字（一个字等于两个英文字符或特殊字符）'));
                        } else {
                            callback();
                        }
                    }
                    function checkNickname(rule, value, callback) {
                        if (!value) {
                            return callback(new Error('请输入主播昵称'));
                        }
                        const length =
                            value.match(/[^ -~]/g) == null ? value.length : value.length + value.match(/[^ -~]/g).length;
                        if (length < 4 || length > 30) {
                            callback(new Error('直播标题必须为2-15个字（一个字等于两个英文字符或特殊字符）'));
                        } else {
                            callback();
                        }
                    }

                    onMounted(() => {
                        state.type == 'edit' && getDetail()
                    })

                    return {
                        state,
                        form,
                        disabledDate,
                        onConfirm,
                        getDetail,
                        checkTitle,
                        checkNickname,
                        defaultTime
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
                        data: [],
                        selected: [],
                    });

                    function getData() {
                        Fast.api.ajax({
                            url: 'shopro/app/mplive/room/select',
                            type: 'GET',
                        }, function (ret, res) {
                            state.data = res.data;
                            return false
                        }, function (ret, res) { })
                    }

                    function isSelectable(row) {
                        return row.status === 101 || row.status === 102 || row.status === 103
                    }

                    function onSelectionChange(val) {
                        state.selected = val
                    }

                    function onConfirm() {
                        Fast.api.close(state.selected)
                    }

                    onMounted(() => {
                        getData()
                    })

                    return {
                        Moment,
                        state,
                        isSelectable,
                        onSelectionChange,
                        onConfirm,
                    }
                }
            }
            createApp('select', select);
        },
        pushurl: () => {
            const { reactive, onMounted } = Vue
            const pushUrl = {
                setup() {
                    const state = reactive({
                        id: new URLSearchParams(location.search).get('id'),
                        pushUrl: '', // 推流地址
                        serverAddress: '', // 服务器地址
                        key: '', // 串流密钥
                    })

                    function getDetail() {
                        Fast.api.ajax({
                            url: `shopro/app/mplive/room/pushUrl/id/${state.id}`,
                            type: 'GET',
                        }, function (ret, res) {
                            state.pushUrl = res.data.pushAddr;
                            state.serverAddress = state.pushUrl.split('/live/')[0] + '/live/';
                            state.key = state.pushUrl.split('/live/')[1];
                            return false
                        }, function (ret, res) { })
                    }

                    function onJump() {
                        window.open('https://docs.qq.com/doc/DV0hoWHZRdm9oT2pp');
                    }

                    onMounted(() => {
                        getDetail()
                    })

                    return {
                        state,
                        getDetail,
                        onClipboard,
                        onJump
                    }
                }
            }
            createApp('pushUrl', pushUrl);
        },
        qrcode: () => {
            const { reactive, onMounted } = Vue
            const qrcode = {
                setup() {
                    const state = reactive({
                        id: new URLSearchParams(location.search).get('id'),
                        cdnUrl: '', // 小程序码
                        path: '', // 页面路径
                        key: '', // 串流密钥
                    })

                    function getDetail() {
                        Fast.api.ajax({
                            url: `shopro/app/mplive/room/qrcode/id/${state.id}`,
                            type: 'GET',
                        }, function (ret, res) {
                            state.cdnUrl = res.data.cdnUrl;
                            state.path = res.data.pagePath;
                            return false
                        }, function (ret, res) { })
                    }

                    function saveImg() {
                        window.open(state.cdnUrl);
                    }
                    function onJump() {
                        window.open(
                            'https://developers.weixin.qq.com/miniprogram/dev/platform-capabilities/industry/liveplayer/live-player-plugin.html',
                        );
                    }

                    onMounted(() => {
                        getDetail()
                    })

                    return {
                        state,
                        getDetail,
                        onClipboard,
                        saveImg,
                        onJump
                    }
                }
            }
            createApp('qrcode', qrcode);
        },

        playback: () => {
            const { reactive, onMounted } = Vue
            const playback = {
                setup() {
                    const state = reactive({
                        id: new URLSearchParams(location.search).get('id'),
                    })
                    // 表格状态
                    const table = reactive({
                        data: [],
                        order: '',
                        sort: '',
                        selected: [],
                    });

                    function getData() {
                        Fast.api.ajax({
                            url: `shopro/app/mplive/room/playback/id/${state.id}`,
                            type: 'GET',
                            // data: {
                            //     page: pagination.page,
                            //     list_rows: pagination.list_rows,
                            //     order: state.order,
                            //     sort: state.sort,
                            // },
                        }, function (ret, res) {
                            table.data = res.data
                            table.data.forEach((item, index) => {
                                table.data[index].index = index + 1;
                            });
                            return false
                        }, function (ret, res) { })
                    }

                    const pagination = reactive({
                        page: 1,
                        list_rows: 10,
                        total: 0,
                    })
                    function play(url) {
                        window.open(url);
                    }

                    onMounted(() => {
                        getData()
                    })

                    return {
                        state,
                        getData,
                        pagination,
                        table,
                        play
                    }
                }
            }
            createApp('playback', playback);
        },
    };
    return Controller;
});
