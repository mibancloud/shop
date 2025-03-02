define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'moment', 'moment/locale/zh-cn'], function ($, undefined, Backend, Table, Form, Moment) {

    var Controller = {
        index: () => {
            const { unref, reactive, onMounted } = Vue
            const index = {
                setup() {
                    const state = reactive({
                        id: new URLSearchParams(location.search).get('id'),
                        nickname: new URLSearchParams(location.search).get('nickname'),
                        room_id: 'admin',
                        room_name: '',
                        data: [],
                    })

                    const chat = reactive({
                        config: {}
                    })
                    function getChatConfig() {
                        Fast.api.ajax({
                            url: `shopro/chat/index/init`,
                            type: 'GET',
                        }, function (ret, res) {
                            chat.config = res.data
                            onCommand()
                            return false
                        }, function (ret, res) { })
                    }

                    function getData() {
                        pagination.page += 1;
                        pagination.loadStatus = 'loading';
                        Fast.api.ajax({
                            url: 'shopro/chat/record',
                            type: 'GET',
                            data: {
                                page: pagination.page,
                                list_rows: pagination.list_rows,
                                search: JSON.stringify({
                                    chat_user_id: state.id,
                                    room_id: state.room_id,
                                }),
                            },
                        }, function (ret, res) {
                            if (pagination.page == 1) {
                                state.data = []
                            }
                            res.data.data.forEach((item) => {
                                state.data.unshift(item);
                            });

                            pagination.page = res.data.current_page;
                            pagination.lastPage = res.data.last_page;
                            pagination.loadStatus = pagination.page < pagination.lastPage ? 'loadmore' : 'nomore';
                            if (pagination.last_id == 0) {
                                pagination.last_id = res.data.data.length > 0 ? res.data.data[0].id : 0;
                            }
                            return false
                        }, function (ret, res) { })
                    }

                    const pagination = reactive({
                        page: 0,
                        list_rows: 10,
                        total: 0,
                        last_id: 0,
                        lastPage: 0,
                        loadStatus: 'loadmore',
                    })

                    function onLoadMore() {
                        pagination.page < pagination.lastPage && getData();
                    }

                    function onCommand(room_id = state.room_id) {
                        state.room_id = room_id
                        state.room_name = chat.config.default_rooms?.find(item => item.value == state.room_id).name

                        pagination.page = 0
                        pagination.last_id = 0
                        pagination.lastPage = 0
                        pagination.loadStatus = 'loadmore'

                        getData()
                    }

                    const loadingMap = {
                        loadmore: {
                            title: '查看更多',
                        },
                        nomore: {
                            title: '没有更多了',
                        },
                        loading: {
                            title: '加载中... ',
                        },
                    };

                    function replaceEmoji(data) {
                        let newData = data;
                        if (typeof newData != 'object') {
                            let reg = /\[(.+?)\]/g; // [] 中括号
                            let zhEmojiName = newData.match(reg);
                            if (zhEmojiName) {
                                zhEmojiName.forEach((item) => {
                                    let emojiFile = selEmojiFile(item);
                                    newData = newData.replace(
                                        item,
                                        `<img class="record-emoji" src="${Fast.api.cdnurl(`/assets/addons/shopro/img/chat/emoji/${emojiFile}`)}" />`,
                                    );
                                });
                            }
                        }
                        return newData;
                    }
                    function selEmojiFile(name) {
                        let emojiData = [
                            { "name": "[笑掉牙]", "file": "xiaodiaoya.png" },
                            { "name": "[可爱]", "file": "keai.png" },
                            { "name": "[冷酷]", "file": "lengku.png" },
                            { "name": "[闭嘴]", "file": "bizui.png" },
                            { "name": "[生气]", "file": "shengqi.png" },
                            { "name": "[惊恐]", "file": "jingkong.png" },
                            { "name": "[瞌睡]", "file": "keshui.png" },
                            { "name": "[大笑]", "file": "daxiao.png" },
                            { "name": "[爱心]", "file": "aixin.png" },
                            { "name": "[坏笑]", "file": "huaixiao.png" },
                            { "name": "[飞吻]", "file": "feiwen.png" },
                            { "name": "[疑问]", "file": "yiwen.png" },
                            { "name": "[开心]", "file": "kaixin.png" },
                            { "name": "[发呆]", "file": "fadai.png" },
                            { "name": "[流泪]", "file": "liulei.png" },
                            { "name": "[汗颜]", "file": "hanyan.png" },
                            { "name": "[惊悚]", "file": "jingshu.png" },
                            { "name": "[困~]", "file": "kun.png" },
                            { "name": "[心碎]", "file": "xinsui.png" },
                            { "name": "[天使]", "file": "tianshi.png" },
                            { "name": "[晕]", "file": "yun.png" },
                            { "name": "[啊]", "file": "a.png" },
                            { "name": "[愤怒]", "file": "fennu.png" },
                            { "name": "[睡着]", "file": "shuizhuo.png" },
                            { "name": "[面无表情]", "file": "mianwubiaoqing.png" },
                            { "name": "[难过]", "file": "nanguo.png" },
                            { "name": "[犯困]", "file": "fankun.png" },
                            { "name": "[好吃]", "file": "haochi.png" },
                            { "name": "[呕吐]", "file": "outu.png" },
                            { "name": "[龇牙]", "file": "ziya.png" },
                            { "name": "[懵比]", "file": "mengbi.png" },
                            { "name": "[白眼]", "file": "baiyan.png" },
                            { "name": "[饿死]", "file": "esi.png" },
                            { "name": "[凶]", "file": "xiong.png" },
                            { "name": "[感冒]", "file": "ganmao.png" },
                            { "name": "[流汗]", "file": "liuhan.png" },
                            { "name": "[笑哭]", "file": "xiaoku.png" },
                            { "name": "[流口水]", "file": "liukoushui.png" },
                            { "name": "[尴尬]", "file": "ganga.png" },
                            { "name": "[惊讶]", "file": "jingya.png" },
                            { "name": "[大惊]", "file": "dajing.png" },
                            { "name": "[不好意思]", "file": "buhaoyisi.png" },
                            { "name": "[大闹]", "file": "danao.png" },
                            { "name": "[不可思议]", "file": "bukesiyi.png" },
                            { "name": "[爱你]", "file": "aini.png" },
                            { "name": "[红心]", "file": "hongxin.png" },
                            { "name": "[点赞]", "file": "dianzan.png" },
                            { "name": "[恶魔]", "file": "emo.png" }
                        ]
                        for (let index in emojiData) {
                            if (emojiData[index].name == name) {
                                return emojiData[index].file;
                            }
                        }
                        return false;
                    }

                    function showTime(item, index) {
                        if (state.data[index + 1]) {
                            let dateString = Moment(state.data[index + 1].createtime * 1000).fromNow();
                            if (dateString == Moment(item.createtime * 1000).fromNow()) {
                                return false;
                            } else {
                                dateString = Moment(item.createtime * 1000).fromNow();
                                return true;
                            }
                        }
                        return false;
                    };

                    // 格式化时间
                    function formatTime(time) {
                        let diffTime = Moment().unix() - time;
                        if (diffTime > 28 * 24 * 60) {
                            return Moment(time * 1000).format('MM/DD HH:mm');
                        }
                        if (diffTime > 360 * 28 * 24 * 60) {
                            return Moment(time * 1000).format('YYYY/MM/DD HH:mm');
                        }
                        return Moment(time * 1000).fromNow();
                    };

                    onMounted(() => {
                        getChatConfig()
                    })

                    return {
                        Fast,
                        state,
                        chat,
                        getData,
                        pagination,
                        onLoadMore,
                        onCommand,
                        loadingMap,
                        replaceEmoji,
                        showTime,
                        formatTime,
                    }
                }
            }
            createApp('index', index);
        },
    };
    return Controller;
});
