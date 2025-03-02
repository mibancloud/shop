define(['jquery', 'bootstrap', 'backend', 'table', 'form','sortable','template'], function($, undefined, Backend, Table, Form,Template) {

	var Controller = {
		index: function() {
			// 初始化表格参数配置
			Table.api.init({
				extend: {
					index_url: 'mobilediy/index/index'  + location.search,
					add_url: 'mobilediy/index/add',
					table: 'mobilediy_page',
				}
			});
            
			var table = $("#table");
			var tableOptions = {
				url: $.fn.bootstrapTable.defaults.extend.index_url,
				pk: 'id',
				sortName: 'weigh',
				pagination: true,
				search: false,
                sortable:true,
                showExport: false,
				onLoadSuccess:function(){
					// 这里就是数据渲染结束后的回调函数
					$(".btn-add, .btn-look,.btn-split,.btn-editone,.btn-edit").data("area", ['100%', '100%']);
				},
				columns: [
					[{
						checkbox: true
						},
						{
							field: 'id',
							title: __('Id')
						},
						{
							field: 'page_name',
							title: __('Page Name')
						},
                        {field: 'status', title: __('Page Type'), searchList: {"home":__('Page Home'),"custom":__('Page Custom')}, formatter: Table.api.formatter.flag},
                        {field: 'url', title: __('H5 Link'), formatter: Table.api.formatter.url},
						{
							field: 'createtime',
							title: __('Createtime'),
							formatter: Table.api.formatter.datetime,
                            operate: 'RANGE',
                            sortable: true
						},
                        {field: 'weigh', title: __('Weigh'), operate: false},
						{
							field: 'operate',
							title: __('Operate'),
							table: table,
							events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'ajax',
                                    title: __('set as homepage'),
                                    text: __('set as homepage'),
                                    classname: 'btn btn-xs btn-info btn-magic btn-ajax',
                                    icon: 'fa fa-location-arrow',
									url: function (row) {
									    return 'mobilediy/index/sethome?id=' + row.id;
									},
                                    success: function (data, ret) {
                                        setTimeout(() => {
                                            location.reload();
                                        }, 600);
                                    },
                                    hidden: function (row) {
                                        if (row.status != 'home') {
                                            return false;
                                        } else {
                                            return true;
                                        }
                                    },
                                    error: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        return false;
                                    }
                                },
                                // 修改
                                {
                                    name: 'editinfo',
                                    title: __('Page Edit'),
                                    text: __('Page Edit'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-pencil',
									url: 'mobilediy/index/editinfo'
                                },
                                // 装修
                                {
                                    name: 'Decoration',
                                    title: __('Decoration'),
                                    text: __('Decoration'),
                                    classname: 'btn btn-xs btn-warning btn-dialog btn-edit',
                                    icon: 'fa fa-magic',
									url: 'mobilediy/index/edit'
                                },
                                // 删除
                                {
                                    name: 'Del',
                                    title: __('Del'),
                                    classname: 'btn btn-xs btn-danger btn-magic btn-ajax',
                                    icon: 'fa fa-trash',
                                    confirm: __('qiurendel'),
									url: 'mobilediy/index/del',
                                    success: function (data, ret) {location.reload();}
                                },
                            ]
						}
					]
				]
			};
			// 初始化表格
			table.bootstrapTable(tableOptions);
			// 为表格绑定事件
			Table.api.bindevent(table);
		},
        recyclebin: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    'dragsort_url': ''
                }
            });
            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: 'mobilediy/index/recyclebin' + location.search,
                pk: 'id',
                sortName: 'id',
                showToggle: false,
                showExport: false,
                maintainSelected: false,
				pagination: false,
				commonSearch: false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'page_name', title: __('Name'), align: 'left'},
                        {
                            field: 'deletetime',
                            title: __('Deletetime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime
                        },
                        {
                            field: 'operate',
                            width: '130px',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'Restore',
                                    text: __('Restore'),
                                    classname: 'btn btn-xs btn-info btn-ajax btn-restoreit',
                                    icon: 'fa fa-rotate-left',
                                    url: 'mobilediy/index/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'mobilediy/index/destroy',
                                    refresh: true
                                }
                            ],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        editrichtext: function () {
            document.getElementById('textareacallback').value = localStorage.getItem("EditorRichtextdata");
            Controller.api.bindevent();
            $(document).on('click', '.btn-callback', function () {
                Fast.api.close($("textarea[name=textareacallback]").val());
            });
        },
		add: function() {
            new Vue({
                el: '#mobilediy',
                data: {
                    mobilediydata: data,
                    selectedIndex: -1,
                    defaultdata:defaultdata,
                    curItem: {},
                    // 选择url start
                    dialogData: null,
                    dialogSelecturl:false,
                    currmenuidx: 'Inlay',//索引
                    fcall:null,// 回调
                    dialogform:{
                        param1:'',
                        param2:'',
                    }
                    // 选择url end
                },
                error: '',
                methods: {
                    /**
                     * 新增组件
                     * @param key
                     */
                    onAdd:function(key){
                        if (!this.onCheckAddwebview(key)) {
                            return false;
                        }
                        // 复制默认diy组件数据
                        var data = $.extend(true, {}, defaultdata[key]);
                        // 新增到diy列表数据
                        this.mobilediydata.items.push(data);
                        
                        // 编辑当前选中的元素
                        this.onEditer(this.mobilediydata.items.length - 1);
                    },

                    /**
                     * 拖动diy元素更新当前索引
                     * @param e
                     */
                    onDragItemEnd:function(e){
                        this.onEditer(e.newIndex);
                    },

                    /**
                     * 验证新增Diy组件
                     * @param key
                     */
                     onCheckAddwebview:function(key) {
                        // 验证webview组件只能存在一个
                        if (key === 'webview') {
                            if (this.mobilediydata.items.length != 0){
                                Toastr.error('webview不能和其他组件一起使用');
                                return false;
                            }
                        }else{
                            for (var index in this.mobilediydata.items) {
                                if (this.mobilediydata.items.hasOwnProperty(index)) {
                                    var item = this.mobilediydata.items[index];
                                    if (item.type === 'webview') {
                                        Toastr.error('webview不能和其他组件一起使用');
                                        return false;
                                    }
                                }
                            }
                        }
                        return true;
                    },

                    /**
                     * 编辑当前选中的Diy元素
                     * @param index
                     */
                    onEditer: function (index) {
                        // 记录当前选中元素的索引
                        this.selectedIndex = index;
                        // 当前选中的元素数据
                        this.curItem = this.selectedIndex === 'page' ? this.mobilediydata.page : this.mobilediydata.items[this.selectedIndex];
                        // 注册编辑器事件
                    },

                    /**
                     * 复制diy元素
                     * @param index
                     */
                    onCopyItem: function (index) {
                        var _this = this;
                        // 复制默认diy组件数据
                        var data = $.extend(true, {}, _this.mobilediydata.items[index]);
                        // 新增到diy列表数据
                        this.mobilediydata.items.push(data);
                        
                        // 编辑当前选中的元素
                        this.onEditer(this.mobilediydata.items.length - 1);
                    },
                    /**
                     * diy元素上移
                     * @param index
                     */
                    onMoveUp: function (index) {
                        if (0 == index){
                            return;
                        }
                        this.mobilediydata.items[index] = this.mobilediydata.items.splice(index - 1, 1, this.mobilediydata.items[index])[0];
                    },
                    
                    /**
                     * diy元素下移
                     * @param index
                     */
                    onMoveDown: function(index) {
                        if (this.mobilediydata.items.length-1 == index){
                            return;
                        }
                        this.mobilediydata.items[index] = this.mobilediydata.items.splice(index + 1, 1, this.mobilediydata.items[index])[0];
                    },
                    /**
                     * 删除diy元素
                     * @param index
                     */
                    onDeleleItem: function (index) {
                        this.mobilediydata.items.splice(index, 1);
                        this.selectedIndex = -1;
                    },
                    /**
                     * 编辑器：选择图片
                     * @param source
                     * @param index
                     */
                    onEditorSelectImage: function (source, index,type="image/") {
                        parent.Fast.api.open(`general/attachment/select?element_id=&multiple=true&mimetype=${type}*`, __('Choose'), {
                            callback: function (data) {
                                if (data.multiple){
                                    source[index] = window.location.protocol + '//' + window.location.host + data.url;
                                }
                            }
                        });
                    },
                    /**
                     * 编辑器：编辑富文本
                     * @param source
                     * @param index
                     */
                    onEditorRichtext: function (source) {
                        localStorage.setItem("EditorRichtextdata", source['content']);
                        Fast.api.open('mobilediy/index/editrichtext', __('Edit'), {
                            callback: function (data) {
                                if (data){
                                    source['content'] = data;
                                }
                            }
                        });
                    },
                    /**
                     * 编辑器：选择URL
                     * @param source
                     * @param index
                     */
                    onEditorSelecturl: function (source) {
                        var _this = this;
                        
                        var call = function(type, row){
                            source['link'] = null;
                            if (type === 'Inlay'){
                                source['link'] = {type:type,title:row.title,path:row.path};
                            }else if(type === 'Phone'){
                                source['link'] = {type:type,title:row.name,phone:_this.dialogform.param1};
                            }else if(type === 'Custom'){
                                source['link'] = {type:type,title:row.title,path:row.path};
                            }else if(type === 'WXMp'){
                                source['link'] = {type:type,title:row.name,appid:_this.dialogform.param1,path:_this.dialogform.param2};
                            }else if(type === 'Outside'){
                                source['link'] = {type:type,title:row.name,url:_this.dialogform.param1 +_this.dialogform.param2};
                            }else if(type === 'QQ'){
                                source['link'] = {type:type,title:row.name,qq:_this.dialogform.param1};
                            }else if(type === 'Copy'){
                                source['link'] = {type:type,title:row.name,text:_this.dialogform.param1};
                            }else if(type === 'notice'){
                                source['link'] = {type:type,title:row.name,key:_this.dialogform.param1,data:_this.dialogform.param2};
                            }
                            
                            _this.dialogSelecturl = false;
                        }
                        _this.fcall = call;
                        _this.dialogform.param1 = '';
                        _this.dialogform.param2 = '';
                        $.post('mobilediy/index/selectUrlPro', {}, function (result) {
                            _this.dialogData = result.rows;
                            _this.dialogSelecturl = true;
                        });
                    },
                    /**
                     * 编辑器：重置颜色
                     * @param holder
                     * @param attribute
                     * @param color
                     */
                    onEditorResetColor: function (holder, attribute, color) {
                        holder[attribute] = color;
                    },

                    /**
                     * 编辑器：删除data元素
                     * @param index
                     * @param selectedIndex
                     */
                    onEditorDeleleData: function (index,selectedIndex) {
                        if (this.mobilediydata.items[selectedIndex].data.length <= 1) {
                            Toastr.error('至少保留一个组件');
                            return false;
                        }
                        this.mobilediydata.items[selectedIndex].data.splice(index, 1);
                    },

                    /**
                     * 编辑器：添加data元素
                     */
                    onEditorAddData: function () {
                        // 新增data数据
                        var newDataItem = $.extend(true, {}, defaultdata[this.curItem.type].data[0]);
                        this.curItem.data.push(newDataItem);
                    },

                    /**
                     * 提交后端保存
                     * @returns {boolean}
                     */
                    onSubmit: function () {
                        if (this.mobilediydata.items.length <= 0) {
                            Toastr.error('至少保留一个组件');
                            return false;
                        }
                        var data = JSON.stringify(this.mobilediydata);
                        $.post('', {data: data}, function (result) {
                            if (result.code === 1){
                                parent.window.$(".btn-refresh").trigger("click");
                                
                                Toastr.success(result.msg);
                                return setTimeout(() => {
                                    Fast.api.close({});
                                }, 1000);
                            }
                            return Toastr.error(result.msg);
                        });
                    }

                }
            });
			Controller.api.bindevent();
		},
		edit: function() {
            new Vue({
                el: '#mobilediy',
                data: {
                    mobilediydata: data,
                    selectedIndex: -1,
                    defaultdata:defaultdata,
                    curItem: {},
                    // 选择url start
                    dialogData: null,
                    dialogSelecturl:false,
                    currmenuidx: 'Inlay',//索引
                    fcall:null,// 回调
                    dialogform:{
                        param1:'',
                        param2:'',
                    }
                    // 选择url end
                },
                error: '',
                methods: {
                    /**
                     * 新增组件
                     * @param key
                     */
                    onAdd:function(key){

                        if (!this.onCheckAddwebview(key)) {
                            return false;
                        }


                        // 复制默认diy组件数据
                        var data = $.extend(true, {}, defaultdata[key]);
                        // 新增到diy列表数据
                        this.mobilediydata.items.push(data);
                        
                        // 编辑当前选中的元素
                        this.onEditer(this.mobilediydata.items.length - 1);
                    },

                    /**
                     * 拖动diy元素更新当前索引
                     * @param e
                     */
                    onDragItemEnd:function(e){
                        this.onEditer(e.newIndex);
                    },

                    /**
                     * 验证新增Diy组件
                     * @param key
                     */
                     onCheckAddwebview:function(key) {
                        // 验证webview组件只能存在一个
                        if (key === 'webview') {
                            if (this.mobilediydata.items.length != 0){
                                Toastr.error('webview不能和其他组件一起使用');
                                return false;
                            }
                        }else{
                            for (var index in this.mobilediydata.items) {
                                if (this.mobilediydata.items.hasOwnProperty(index)) {
                                    var item = this.mobilediydata.items[index];
                                    if (item.type === 'webview') {
                                        Toastr.error('webview不能和其他组件一起使用');
                                        return false;
                                    }
                                }
                            }
                        }
                        return true;
                    },

                    /**
                     * 编辑当前选中的Diy元素
                     * @param index
                     */
                    onEditer: function (index) {
                        // 记录当前选中元素的索引
                        this.selectedIndex = index;
                        // 当前选中的元素数据
                        this.curItem = this.selectedIndex === 'page' ? this.mobilediydata.page
                            : this.mobilediydata.items[this.selectedIndex];
                    },

                    /**
                     * 复制diy元素
                     * @param index
                     */
                    onCopyItem: function (index) {
                        var _this = this;
                        // 复制默认diy组件数据
                        var data = $.extend(true, {}, _this.mobilediydata.items[index]);
                        // 新增到diy列表数据
                        this.mobilediydata.items.push(data);
                        
                        // 编辑当前选中的元素
                        this.onEditer(this.mobilediydata.items.length - 1);
                    },
                    /**
                     * diy元素上移
                     * @param index
                     */
                    onMoveUp: function (index) {
                        if (0 == index){
                            return;
                        }
                        this.mobilediydata.items[index] = this.mobilediydata.items.splice(index - 1, 1, this.mobilediydata.items[index])[0];
                    },
                    
                    /**
                     * diy元素下移
                     * @param index
                     */
                    onMoveDown: function(index) {
                        if (this.mobilediydata.items.length-1 == index){
                            return;
                        }
                        this.mobilediydata.items[index] = this.mobilediydata.items.splice(index + 1, 1, this.mobilediydata.items[index])[0];
                    },
                    /**
                     * 删除diy元素
                     * @param index
                     */
                    onDeleleItem: function (index) {
                        this.mobilediydata.items.splice(index, 1);
                        this.selectedIndex = -1;
                    },
                    /**
                     * 编辑器：选择图片
                     * @param source
                     * @param index
                     */
                    onEditorSelectImage: function (source, index,type="image/") {
                        parent.Fast.api.open(`general/attachment/select?element_id=&multiple=true&mimetype=${type}*`, __('Choose'), {
                            callback: function (data) {
                                if (data.multiple){
                                    source[index] = window.location.protocol + '//' + window.location.host + data.url;
                                }
                            }
                        });
                    },
                    /**
                     * 编辑器：编辑富文本
                     * @param source
                     * @param index
                     */
                    onEditorRichtext: function (source) {
                        localStorage.setItem("EditorRichtextdata", source['content']);
                        Fast.api.open('mobilediy/index/editrichtext', __('Edit'), {
                            callback: function (data) {
                                if (data){
                                    source['content'] = data;
                                }
                            }
                        });
                    },
                    /**
                     * 编辑器：选择URL
                     * @param source
                     * @param index
                     */
                    onEditorSelecturl: function (source) {
                        var _this = this;
                        
                        var call = function(type, row){
                            source['link'] = null;
                            if (type === 'Inlay'){
                                source['link'] = {type:type,title:row.title,path:row.path};
                            }else if(type === 'Phone'){
                                source['link'] = {type:type,title:row.name,phone:_this.dialogform.param1};
                            }else if(type === 'Custom'){
                                source['link'] = {type:type,title:row.title,path:row.path};
                            }else if(type === 'WXMp'){
                                source['link'] = {type:type,title:row.name,appid:_this.dialogform.param1,path:_this.dialogform.param2};
                            }else if(type === 'Outside'){
                                source['link'] = {type:type,title:row.name,url:_this.dialogform.param1 +_this.dialogform.param2};
                            }else if(type === 'QQ'){
                                source['link'] = {type:type,title:row.name,qq:_this.dialogform.param1};
                            }else if(type === 'Copy'){
                                source['link'] = {type:type,title:row.name,text:_this.dialogform.param1};
                            }else if(type === 'notice'){
                                source['link'] = {type:type,title:row.name,key:_this.dialogform.param1,data:_this.dialogform.param2};
                            }
                            
                            _this.dialogSelecturl = false;
                        }
                        _this.fcall = call;
                        _this.dialogform.param1 = '';
                        _this.dialogform.param2 = '';
                        $.post('mobilediy/index/selectUrlPro', {}, function (result) {
                            _this.dialogData = result.rows;
                            _this.dialogSelecturl = true;
                        });
                    },
                    /**
                     * 编辑器：重置颜色
                     * @param holder
                     * @param attribute
                     * @param color
                     */
                    onEditorResetColor: function (holder, attribute, color) {
                        holder[attribute] = color;
                    },

                    /**
                     * 编辑器：删除data元素
                     * @param index
                     * @param selectedIndex
                     */
                    onEditorDeleleData: function (index,selectedIndex) {
                        if (this.mobilediydata.items[selectedIndex].data.length <= 1) {
                            Toastr.error('至少保留一个组件');
                            return false;
                        }
                        this.mobilediydata.items[selectedIndex].data.splice(index, 1);
                    },

                    /**
                     * 编辑器：添加data元素
                     */
                    onEditorAddData: function () {
                        // 新增data数据
                        var newDataItem = $.extend(true, {}, defaultdata[this.curItem.type].data[0]);
                        this.curItem.data.push(newDataItem);
                    },

                    /**
                     * 提交后端保存
                     * @returns {boolean}
                     */
                    onSubmit: function () {
                        if (this.mobilediydata.items.length <= 0) {
                            Toastr.error('至少保留一个组件');
                            return false;
                        }
                        var data = JSON.stringify(this.mobilediydata);
                        $.post('', {data: data}, function (result) {
                            if (result.code === 1){
                                parent.window.$(".btn-refresh").trigger("click");
                                
                                Toastr.success(result.msg);
                                return setTimeout(() => {
                                    Fast.api.close({});
                                }, 1000);
                            }
                            return Toastr.error(result.msg);
                        });
                    }

                }
            });
			Controller.api.bindevent();
		},
        editinfo: function () {
            Controller.api.bindevent();
        },
		api: {
			bindevent: function() {
				Form.api.bindevent($("form[role=form]"));
			},
            formatter: {//渲染的方法
                browser: function (value, row, index) {
                    //这里我们直接使用row的数据
                    return '<a class="btn btn-xs btn-browser">' + row.short.split(" ")[0] + '</a>';
                }
            },
            events: {//绑定事件的方法
                browser: {
                    'click .btn-browser': function (e, value, row, index) {
						e.stopPropagation();
						window.open(row.url)
                    }
                },
            }
		}
	};
	return Controller;
});
