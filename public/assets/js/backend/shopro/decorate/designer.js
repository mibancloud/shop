define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        data: () => {
            return {
                platformList: [
                    {
                        type: 'WechatMiniProgram',
                        label: '微信小程序',
                        color: '#6F74E9',
                    },
                    {
                        type: 'WechatOfficialAccount',
                        label: '微信公众号',
                        color: '#07C160',
                    },
                    {
                        type: 'H5',
                        label: 'H5',
                        color: '#FC800E',
                    },
                    {
                        type: 'App',
                        label: 'APP',
                        color: '#806AF6',
                    },
                ]
            }
        },
        index: () => {
            const { reactive, onMounted } = Vue
            const index = {
                setup() {
                    const state = reactive({
                        data: [],
                    })

                    function getData() {
                        $.ajax({
                            url: 'https://api.sheepjs.com/api/designer',
                            type: 'GET',
                            success: function (ret) {
                                if (ret.error == 0) {
                                    state.data = ret.data
                                } else {
                                    Toastr.error(ret.msg);
                                }
                            },
                            error: function (xhr, textStatus, error) { }
                        });
                    }

                    function onUse(id) {
                        $.ajax({
                            url: `https://api.sheepjs.com/api/designer/${id}`,
                            type: 'GET',
                            success: function (ret) {
                                if (ret.error == 0) {
                                    Fast.api.ajax({
                                        url: `shopro/decorate/designer/use`,
                                        type: 'POST',
                                        data: ret.data
                                    }, function (ret, res) {
                                        getData()
                                    }, function (ret, res) { })
                                } else {
                                    Toastr.error(ret.msg);
                                }
                            },
                            error: function (xhr, textStatus, error) { }
                        });
                    }

                    function onPreview(item) {
                        let previewData = {
                            name: item.name,
                            type: 'designer',
                            platform: item.platform,
                            h5Url: item.h5Url,
                            wxacode: item.wxacode,
                        }
                        localStorage.setItem("preview-data", JSON.stringify(previewData));
                        Fast.api.open(`shopro/decorate/page/preview`, "预览")
                    }

                    onMounted(() => {
                        getData()
                    })

                    return {
                        ...Controller.data(),
                        state,
                        getData,
                        onUse,
                        onPreview,
                    }
                }
            }
            createApp('index', index);
        },
    };
    return Controller;
});
