define([], function () {
    if (Config.modulename == 'admin' && Config.controllername == 'index' && Config.actionname == 'index') {
    require.config({
        paths: {
            'vue3': "../addons/shopro/libs/vue",
            'vue': "../addons/shopro/libs/vue.amd",
            'text': "../addons/shopro/libs/require-text",
            'SaChat': '../addons/shopro/chat/index',
            'ElementPlus': '../addons/shopro/libs/element-plus/index',
            'ElementPlusIconsVue3': "../addons/shopro/libs/element-plus/icons-vue",
            'ElementPlusIconsVue': '../addons/shopro/libs/element-plus/icons-vue.amd',
            'io': '../addons/shopro/libs/socket.io',
        },
        shim: {
            'ElementPlus': {
                deps: ['css!../addons/shopro/libs/element-plus/index.css']
            },
        },
    });
    require(['vue3', 'ElementPlusIconsVue3'], function (Vue3, ElementPlusIconsVue3) {
        require(['vue', 'jquery', 'SaChat', 'text!../addons/shopro/chat/index.html', 'ElementPlus', 'ElementPlusIconsVue', 'io'], function (Vue, $, SaChat, SaChatTemplate, ElementPlus, ElementPlusIconsVue, io) {
            if (Config.dark_type != 'none') {
                SaChatTemplate = SaChatTemplate.replaceAll('__DARK__', `<link rel="stylesheet" href="__CDN__/assets/addons/shopro/css/dark.css?v={$site.version|htmlentities}" />`)
            }

            SaChatTemplate = SaChatTemplate.replaceAll('__DARK__', ``)
            SaChatTemplate = SaChatTemplate.replaceAll('__CDN__', Config.__CDN__)

            Fast.api.ajax({
                url: 'shopro/chat/index/init',
                loading: false,
                type: 'GET'
            }, function (ret, res) {
                $("body").append(`<div id="SaChatTemplateContainer"></div>
                <div id="SaChatWrap"><sa-chat></sa-chat></div>`);

                $("#SaChatTemplateContainer").append(SaChatTemplate);

                const { createApp } = Vue
                const app = createApp({})

                app.use(ElementPlus)
                for (const [key, component] of Object.entries(ElementPlusIconsVue)) {
                    app.component(key, component)
                }

                app.component('sa-chat', SaChat)
                app.mount(`#SaChatWrap`)
                return false;
            }, function (ret, res) {
                if (res.msg == '') {
                    return false;
                }
            })
        });

    });
}
require.config({
    paths: {
        'summernote': '../addons/summernote/lang/summernote-zh-CN.min'
    },
    shim: {
        'summernote': ['../addons/summernote/js/summernote.min', 'css!../addons/summernote/css/summernote.min.css'],
    }
});
require(['form', 'upload'], function (Form, Upload) {
    var _bindevent = Form.events.bindevent;
    Form.events.bindevent = function (form) {
        _bindevent.apply(this, [form]);
        try {
            //绑定summernote事件
            if ($(Config.summernote.classname || '.editor', form).length > 0) {
                var selectUrl = typeof Config !== 'undefined' && Config.modulename === 'index' ? 'user/attachment' : 'general/attachment/select';
                require(['summernote'], function () {
                    var imageButton = function (context) {
                        var ui = $.summernote.ui;
                        var button = ui.button({
                            contents: '<i class="fa fa-file-image-o"/>',
                            tooltip: __('Choose'),
                            click: function () {
                                parent.Fast.api.open(selectUrl + "?element_id=&multiple=true&mimetype=image/", __('Choose'), {
                                    callback: function (data) {
                                        var urlArr = data.url.split(/\,/);
                                        $.each(urlArr, function () {
                                            var url = Fast.api.cdnurl(this, true);
                                            context.invoke('editor.insertImage', url);
                                        });
                                    }
                                });
                                return false;
                            }
                        });
                        return button.render();
                    };
                    var attachmentButton = function (context) {
                        var ui = $.summernote.ui;
                        var button = ui.button({
                            contents: '<i class="fa fa-file"/>',
                            tooltip: __('Choose'),
                            click: function () {
                                parent.Fast.api.open(selectUrl + "?element_id=&multiple=true&mimetype=*", __('Choose'), {
                                    callback: function (data) {
                                        var urlArr = data.url.split(/\,/);
                                        $.each(urlArr, function () {
                                            var url = Fast.api.cdnurl(this, true);
                                            var node = $("<a href='" + url + "'>" + url + "</a>");
                                            context.invoke('insertNode', node[0]);
                                        });
                                    }
                                });
                                return false;
                            }
                        });
                        return button.render();
                    };

                    $(Config.summernote.classname || '.editor', form).each(function () {
                        $(this).summernote($.extend(true, {}, {
                            // height: 250,
                            minHeight: 250,
                            lang: 'zh-CN',
                            fontNames: [
                                'Arial', 'Arial Black', 'Serif', 'Sans', 'Courier',
                                'Courier New', 'Comic Sans MS', 'Helvetica', 'Impact', 'Lucida Grande',
                                "Open Sans", "Hiragino Sans GB", "Microsoft YaHei",
                                '微软雅黑', '宋体', '黑体', '仿宋', '楷体', '幼圆',
                            ],
                            fontNamesIgnoreCheck: [
                                "Open Sans", "Microsoft YaHei",
                                '微软雅黑', '宋体', '黑体', '仿宋', '楷体', '幼圆'
                            ],
                            toolbar: [
                                ['style', ['style', 'undo', 'redo']],
                                ['font', ['bold', 'underline', 'strikethrough', 'clear']],
                                ['fontname', ['color', 'fontname', 'fontsize']],
                                ['para', ['ul', 'ol', 'paragraph', 'height']],
                                ['table', ['table', 'hr']],
                                ['insert', ['link', 'picture', 'video']],
                                ['select', ['image', 'attachment']],
                                ['view', ['fullscreen', 'codeview', 'help']],
                            ],
                            buttons: {
                                image: imageButton,
                                attachment: attachmentButton,
                            },
                            dialogsInBody: true,
                            followingToolbar: false,
                            callbacks: {
                                onChange: function (contents) {
                                    $(this).val(contents);
                                    $(this).trigger('change');
                                },
                                onInit: function () {
                                },
                                onImageUpload: function (files) {
                                    var that = this;
                                    //依次上传图片
                                    for (var i = 0; i < files.length; i++) {
                                        Upload.api.send(files[i], function (data) {
                                            var url = Fast.api.cdnurl(data.url, true);
                                            $(that).summernote("insertImage", url, 'filename');
                                        });
                                    }
                                }
                            }
                        }, $(this).data("summernote-options") || {}));
                    });
                });
            }
        } catch (e) {

        }

    };
});

});