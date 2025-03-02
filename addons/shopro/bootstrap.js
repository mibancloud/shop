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