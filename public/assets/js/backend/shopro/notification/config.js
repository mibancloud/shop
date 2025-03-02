define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: () => {
            const { reactive, onMounted, ref } = Vue
            const index = {
                setup() {
                    const state = reactive({
                        receiver_type: 'user',
                        data: [],
                        qrcodeUrl: '',
                        eventId: '',
                        scanStatus: '',
                        oauthInfo: {},
                    })

                    function getData() {
                        Fast.api.ajax({
                            url: 'shopro/notification/config',
                            type: 'GET',
                            data: {
                                receiver_type: state.receiver_type,
                            },
                        }, function (ret, res) {
                            state.data = res.data
                            return false
                        }, function (ret, res) { })
                    }

                    function onEdit(event, channel) {
                        Fast.api.open(`shopro/notification/config/edit?event=${event}&channel=${channel}`, "编辑", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onSetStatus(status, event, channel) {
                        Fast.api.ajax({
                            url: `shopro/notification/config/setStatus/id/${event}`,
                            type: 'POST',
                            data: {
                                status, event, channel
                            }
                        }, function (ret, res) {
                            getData()
                        }, function (ret, res) { })
                    }

                    // 点击二维码
                    function onQrcode() {
                        state.qrcodeUrl = '';
                        getQrcode();
                    }

                    function onHideQrcode() {
                        state.eventId = '';
                    }

                    // 获取绑定二维码
                    function getQrcode() {
                        Fast.api.ajax({
                            url: 'shopro/wechat/admin/getQrcode?event=bind',
                            type: 'GET',
                            loading: false,
                        }, function (ret, res) {
                            if (res.code === 1) {
                                state.qrcodeUrl = ret.url;
                                state.eventId = ret.eventId;
                                // 待扫码
                                state.scanStatus = 'pending';
                                checkScanResult(ret.eventId);
                            }
                            return false;
                        }, function (data, res) {
                            if (res.code === -2) {
                                // 已绑定
                                state.scanStatus = 'binded';
                                state.oauthInfo = res.data;
                            }
                            return false;
                        })
                    }

                    // 检查扫码结果
                    function checkScanResult(eventId) {
                        if (eventId !== state.eventId) return;
                        Fast.api.ajax({
                            url: 'shopro/wechat/admin/checkScan?event=bind&eventId=' + eventId,
                            type: 'GET',
                            loading: false,
                        }, function (data, res) {
                            if (res.code === 1) {
                                // 扫码成功
                                state.scanStatus = 'scanned';
                            }
                            return false;
                        }, function (data, res) {
                            if (res.code === -1) {
                                setTimeout(function () {
                                    checkScanResult(eventId);
                                }, 2000)
                                // 待扫码
                                state.scanStatus = 'pending';
                                return false;
                            } else {
                                // 已过期
                                state.scanStatus = 'expired';
                            }
                        })
                    }

                    const qrcodePopoverRef = ref()

                    // 解除绑定
                    function onUnbind() {
                        Fast.api.ajax({
                            url: 'shopro/wechat/admin/unbind',
                            type: 'GET',
                        }, function (data, res) {
                            state.scanStatus = '';
                            qrcodePopoverRef.value.hide()
                        }, function (data, res) {
                        })
                    }

                    onMounted(() => {
                        getData()
                    })

                    return {
                        state,
                        getData,
                        onEdit,
                        onSetStatus,
                        onQrcode,
                        onHideQrcode,
                        qrcodePopoverRef,
                        onUnbind,
                    }
                }
            }
            createApp('index', index);
        },
        edit: () => {
            Controller.form();
        },
        form: () => {
            const { reactive, onMounted, getCurrentInstance } = Vue
            const addEdit = {
                setup() {
                    const { proxy } = getCurrentInstance();

                    const state = reactive({
                        event: new URLSearchParams(location.search).get('event'),
                        channel: new URLSearchParams(location.search).get('channel')
                    })

                    const form = reactive({
                        model: {},
                        rules: {},
                    })

                    function getDetail() {
                        Fast.api.ajax({
                            url: `shopro/notification/config/detail`,
                            type: 'GET',
                            data: {
                                event: state.event,
                                channel: state.channel
                            }
                        }, function (ret, res) {
                            form.model = res.data;
                            if (state.channel == 'Email') {
                                fieldList.data = res.data.content;
                                form.model.content = res.data.content_text;
                                Controller.api.bindevent();
                                $('#emailContent').html(form.model.content)
                            } else {
                                form.model.content.fields.forEach((e) => {
                                    if (!e.value) {
                                        e['value'] = '';
                                    }
                                    if (!e.template_field) {
                                        e['template_field'] = '';
                                    }
                                });
                            }
                            return false
                        }, function (ret, res) { })
                    }

                    const templateIdPopover = reactive({
                        flag: false,
                        is_delete: '1'
                    })
                    function getTemplateId(is_delete) {
                        templateIdPopover.flag = false;
                        Fast.api.ajax({
                            url: `shopro/notification/config/getTemplateId`,
                            type: 'GET',
                            data: {
                                event: state.event,
                                channel: state.channel,
                                is_delete: is_delete,
                                template_id: is_delete == 1 ? form.model.content.template_id : '',
                            }
                        }, function (ret, res) {
                            form.model.content.template_id = res.data;
                        }, function (ret, res) { })
                    }

                    function onAddField() {
                        form.model.content.fields.push({
                            name: '',
                            template_field: '',
                            value: '',
                        });
                    }
                    function onDeleteField(index) {
                        form.model.content.fields.splice(index, 1);
                    }

                    const fieldList = reactive({
                        data: {},
                    });

                    function onConfirm() {
                        proxy.$refs['formRef'].validate((valid) => {
                            if (valid) {
                                let submitForm = JSON.parse(JSON.stringify(form.model));

                                if (state.channel == 'Email') {
                                    delete submitForm.content_text;
                                    submitForm.content = $("#emailContent").val();
                                }

                                Fast.api.ajax({
                                    url: `shopro/notification/config/edit`,
                                    type: 'POST',
                                    data: {
                                        event: state.event,
                                        channel: state.channel,
                                        ...submitForm,
                                    }
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
                        state,
                        form,
                        templateIdPopover,
                        getTemplateId,
                        onAddField,
                        onDeleteField,
                        fieldList,
                        onConfirm
                    }
                }
            }
            createApp('addEdit', addEdit);
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
        },
    };
    return Controller;
});
