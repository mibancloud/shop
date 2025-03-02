<?php

namespace app\admin\model\mobilediy;

use think\Cache;
/**
 * diy页面模型
 * Class Mobilediy
 * @package app\admin\model\mobilediy
 */

use think\Model;
use traits\model\SoftDelete;

class Mobilediy extends Model {
	
	use SoftDelete;
	
    // 表名
    protected $name = 'mobilediy_page';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';
    
    // 追加属性
    protected $append = [];
	
    /**
     * 获取diy页面列表
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
	public function getList()
	{	    
	    return $this->order(['id' => 'desc'])->select();
	}
	
    /**
     * 获取用户前端固定页面列表
	 * uni端固定页，非本插件自定义
     */
	public function getCustomLink()
	{	    
		return [
			['title' => '用户隐私','path' => 'pages/help/index'],
		];
	}
    /**
     * 获取Url列表
     */
	public function getLinkUrl()
	{	    
		return [
			'Inlay'=> ['type' => 'Inlay','name' => '内置页面'],
			'Custom'=> ['type' => 'Custom','name' => '固定页','list' => $this->getCustomLink()],
			'WXMp'=> ['type' => 'WXMp','name' => '小程序'],
			'Outside'=> ['type' => 'Outside','name' => '外链'],
			'Phone'=> ['type' => 'Phone','name' => '拨打电话'],
			'QQ'=> ['type' => 'QQ','name' => 'QQ会话'],
			'Copy'=> ['type' => 'Copy','name' => '复制文本'],
			'notice'=> ['type' => 'notice','name' => '消息机制'],
		];
	}

    /**
     * 获取Url列表
     */
	public function getUrlList()
	{	    
	    return $this->where('status','custom')->order(['id' => 'desc'])->select();
	}
	
    /**
     * 获取Url列表数量
     */
	public function getUrlListCount()
	{	    
	    return $this->where('status','custom')->order(['id' => 'desc'])->count();
	}

	public function getTotal()
	{
	    return $this->order(['id' => 'desc'])->count();
	}
	
    /**
     * 获取状态列表
     */
    public function getStatusList()
    {
        return ['home' => __('Page Home'), 'custom' => __('Page Custom')];
    }
    
    /**
     * 新增页面
     * @param $data
     * @return bool
     */
    public function add($data)
    {
		$count = $this->order(['id' => 'desc'])->count();
		if ($count > 0){
			return $this->save([
				'status' => 'custom',
				'page_name' => json_decode($data, true)['page']['params']['name'],
				'page_data' => $data
			]);
		}else{
			return $this->save([
				'status' => 'home',
				'page_name' => json_decode($data, true)['page']['params']['name'],
				'page_data' => $data
			]);
		}
    }

    /**
     * 更新页面
     * @param $data
     * @return bool
     */
    public function edit($data)
    {
        // 保存数据
        return $this->save([
			'page_name' => json_decode($data, true)['page']['params']['name'],
                'page_data' => $data
            ]) !== false;
    }

    /**
     * 删除记录
     * @return int
     */
    public function setDelete()
    {
        if ($this['status'] == 'home') {
            $this->error = '默认首页不可以删除';
            return false;
        }
        // 删除wxapp缓存
		return $this->save(array("deletetime" => time()));
    }

    /**
     * 更新权重
     * @return int
     */
    public function setWeigh($ids,$page_name='',$weigh=100)
    {
        return $this->where(['id' => $ids])->update(['page_name' => $page_name,'weigh' => $weigh]);
    }
    
    /**
     * 设为默认首页
     * @return int
     */
    public function setHome()
    {
        // 取消原默认首页
        $this->where(['status' => 'home'])->update(['status' => 'custom']);
        // 删除wxapp缓存
        return $this->save(['status' => 'home']);
    }
	
	
	/**
	 * 页面标题栏默认数据
	 * @return array
	 */
	public function getDefaultPage()
	{
		static $defaultPage = [];
		if (!empty($defaultPage)) return $defaultPage;
		return [
			'type' => 'page',
			'name' => '主题设置',
			'params' => [
				'name' => '页面名称',
				'title' => '首页',
				'share_title' => '分享标题',
			],
			'style' => [
				'titleTextColor' => 'white',
				'titleBackgroundColor' => '#3F74F9',
				'showNav' => false//false 显示
			]
		];
	}
	
	
	/**
	 * 页面diy元素默认数据
	 * @return array
	 */
	public function getDefaultItems($url)
	{
		return [
            'thetitle' => [
                'name' => '主副标题',
				'type' => 'thetitle',
                'style' => [
					'paddingTop' => 10,
					'background' => '#ffffff'
                ],
                'params' => [
                    'lineStyle' => 'lineStyle1',//线条风格
                    'lineColor' => "#434343",//线条颜色
                    'zhutitle' => '主标题',//主标题
                    'zhutitlecolor' => '#434343',//主标题颜色
                    'zhutitlesize' => 20,//主标题字体大小
                    'futitle' => '副标题描述',//副标题
                    'futitlecolor' => '#838383',//副标题颜色
                    'futitlesize' => 14,//副标题字体大小
                    'marginLeft' => 20,
                ]
            ],
			'banner' => [
				'name' => '图片轮播',
				'type' => 'banner',
				'style' => [
				],
				'params' => [
					'type' => '2d',
					'interval' => '2800'
				],
				'data' => [
					[
						'imgUrl' => $url ."://".$_SERVER["HTTP_HOST"]."/" . 'assets/addons/mobilediy/img/banner.jpg',
						'link' => null
					],
					[
						'imgUrl' => $url ."://".$_SERVER["HTTP_HOST"]."/" . 'assets/addons/mobilediy/img/banner.jpg',
						'link' => null
					]
				]
			],
			'images' => [
				'name' => '单图组',
				'type' => 'images',
				'style' => [
					'paddingTop' => 0,
					'paddingLeft' => 0,
					'borderradius' => 0,
					'background' => '#ffffff'
				],
				'data' => [
					[
						'imgUrl' => $url ."://".$_SERVER["HTTP_HOST"]."/" . 'assets/addons/mobilediy/img/banner.jpg',
						'link' => null
					]
				]
			],
			'navBar' => [
				'name' => '导航组',
				'type' => 'navBar',
				'style' => ['background' => '#ffffff', 'borderradius' => 0,'rowsNum' => '4'],
				'data' => [
					[
						'imgUrl' => $url ."://".$_SERVER["HTTP_HOST"]."/" . 'assets/addons/mobilediy/img/menus.png',
						'link' => null,
						'text' => '按钮文字1',
						'color' => '#666666'
					],
					[
						'imgUrl' => $url ."://".$_SERVER["HTTP_HOST"]."/" . 'assets/addons/mobilediy/img/menus.png',
						'link' => null,
						'text' => '按钮文字2',
						'color' => '#666666'
					],
					[
						'imgUrl' => $url ."://".$_SERVER["HTTP_HOST"]."/" . 'assets/addons/mobilediy/img/menus.png',
						'link' => null,
						'text' => '按钮文字3',
						'color' => '#666666'
					],
					[
						'imgUrl' => $url ."://".$_SERVER["HTTP_HOST"]."/" . 'assets/addons/mobilediy/img/menus.png',
						'link' => null,
						'text' => '按钮文字4',
						'color' => '#666666'
					],
					[
						'imgUrl' => $url ."://".$_SERVER["HTTP_HOST"]."/" . 'assets/addons/mobilediy/img/menus.png',
						'link' => null,
						'text' => '按钮文字5',
						'color' => '#666666'
					],
					[
						'imgUrl' => $url ."://".$_SERVER["HTTP_HOST"]."/" . 'assets/addons/mobilediy/img/menus.png',
						'link' => null,
						'text' => '按钮文字6',
						'color' => '#666666'
					],
					[
						'imgUrl' => $url ."://".$_SERVER["HTTP_HOST"]."/" . 'assets/addons/mobilediy/img/menus.png',
						'link' => null,
						'text' => '按钮文字7',
						'color' => '#666666'
					],
					[
						'imgUrl' => $url ."://".$_SERVER["HTTP_HOST"]."/" . 'assets/addons/mobilediy/img/menus.png',
						'link' => null,
						'text' => '按钮文字8',
						'color' => '#666666'
					]
				]
			],
            'textbutton' => [
                'name' => '文字按钮',
                'type' => 'textbutton',
                'style' => ['background' => '#ffffff','paddingTop' => 5],
                'data' => [
                    [
						'link' => null,
                        'text' => '按钮文字1',
                        'color' => '#666666'
                    ],
                    [
						'link' => null,
                        'text' => '按钮文字2',
                        'color' => '#666666'
                    ],
                    [
						'link' => null,
                        'text' => '按钮文字3',
                        'color' => '#666666'
                    ]
                ]
            ],
			'blank' => [
				'name' => '辅助空白',
				'type' => 'blank',
				'style' => [
					'height' => '20',
					'background' => '#d9d9d9'
				]
			],
			'guide' => [
				'name' => '辅助线条',
				'type' => 'guide',
				'style' => [
					'background' => '#ffffff',
					'lineStyle' => 'solid',
					'lineHeight' => '1',
					'lineColor' => "#000000",
					'paddingTop' => 10
				]
			],
			'notice' => [
				'name' => '广播',
				'type' => 'notice',
				'params' => [
					'text' => '这里是第一条来自后台自定义广播的信息',
					'icon' => $url ."://".$_SERVER["HTTP_HOST"]."/" . 'assets/addons/mobilediy/img/notice.png'
				],
				'style' => [
					'paddingTop' => 4,
					'background' => '#ffffff',
					'textColor' => '#000000'
				]
			],
			'window' => [
				'name' => '图片橱窗',
				'type' => 'window',
				'style' => [
					'paddingTop' => 0,
					'paddingLeft' => 0,
					'background' => '#ffffff',
					'borderradius' => 0,
					'layout' => '2'
				],
				'data' => [
					[
						'imgUrl' => $url ."://".$_SERVER["HTTP_HOST"]."/" . 'assets/addons/mobilediy/img/01.jpg',
						'link' => null
					],
					[
						'imgUrl' => $url ."://".$_SERVER["HTTP_HOST"]."/" . 'assets/addons/mobilediy/img/02.jpg',
						'link' => null
					],
					[
						'imgUrl' => $url ."://".$_SERVER["HTTP_HOST"]."/" . 'assets/addons/mobilediy/img/03.jpg',
						'link' => null
					],
					[
						'imgUrl' => $url ."://".$_SERVER["HTTP_HOST"]."/" . 'assets/addons/mobilediy/img/04.jpg',
						'link' => null
					]
				],
				'dataNum' => 4
			],
			'service' => [
				'name' => '悬浮框',
				'type' => 'service',
				'params' => [
					'type' => 'service',
					'image' => $url ."://".$_SERVER["HTTP_HOST"]."/" . 'assets/addons/mobilediy/img/service.png',
					'link' => null
				],
				'style' => [
					'right' => 1,
					'bottom' => 10,
					'opacity' => 100
				]
			],
            'informationcard' => [
                'name' => '信息卡片',
                'type' => 'informationcard',
                'params' => [
                    'title' => '此处是标题此处是标题',
                    'image' => $url ."://".$_SERVER["HTTP_HOST"]."/" . 'assets/addons/mobilediy/img/menus.png',
                    'content' => '信息内容',
                    'btntxt' => '查看详情',
					'link' => null
                ],
                'style' => [
					'borderradius' => 0,
                    'background' => '#F6F6F6'
                ]
            ],
			'video' => [
				'name' => '在线视频',
				'type' => 'video',
				'params' => [
					'videoUrl' => 'https://img.cdn.aliyun.dcloud.net.cn/guide/uniapp/%E7%AC%AC1%E8%AE%B2%EF%BC%88uni-app%E4%BA%A7%E5%93%81%E4%BB%8B%E7%BB%8D%EF%BC%89-%20DCloud%E5%AE%98%E6%96%B9%E8%A7%86%E9%A2%91%E6%95%99%E7%A8%8B@20200317.mp4',
					'poster' => $url ."://".$_SERVER["HTTP_HOST"]."/" . 'assets/addons/mobilediy/img/video_bg.png',
					'autoplay' => '0'
				],
				'style' => [
					'paddingTop' => 0,
					'height' => 190
				]
			],
			'webview' => [
				'name' => 'webview',
				'type' => 'webview',
				'params' => [
					'url' => 'https://www.yoursite.com/'
				]
			],
			'ad' => [
				'name' => 'AD组件',
				'type' => 'ad',
				'style' => [
					'paddingTop' => 0,
					'background' => '#ffffff',
				],
				'params'=>[
					'type' => 'banner',
					'adid' => ''
			    ]
			],
			'text' => [
				'name' => '文本组',
				'type' => 'text',
				'style' => [
					'paddingTop' => 0,
					'paddingLeft' => 0,
					'background' => '#ffffff',
					'text' => '这里是文本的内容',
					'textColor' => '#000000',
					'fontsize' => 15
				]
			],
			'titlebar' => [
				'name' => '标题单元',
				'type' => 'titlebar',
				'style' => [
					'paddingTop' => 8,
					'paddingLeft' => 12,
					'background' => '#ffffff',
				],
				'params'=>[
					'title1' => '明文标题',
					'title2' => 'THISISTITLEBAR',
					'title3' => '更多',
					'textColor' => '#0441f5',
					'link' => null
			    ]
			],
            'richText' => [
                'name' => '富文本',
                'type' => 'richText',
                'params' => [
                    'content' => '<p>这里是富文本的内容</p>',
                    'paddingTop' => 12,
                    'paddingLeft' => 12,
					'borderradius' => 4,
                    'background' => '#ffffff'
                ],
                'style' => [
                    'paddingTop' => 15,
                    'paddingLeft' => 15,
                    'background' => '#f0f0f0'
                ]
            ],
			'bgm' => [
				'name' => '背景音乐',
				'type' => 'bgm',
				'params' => [
					'playImgUrl' => $url ."://".$_SERVER["HTTP_HOST"]."/" . 'assets/addons/mobilediy/img/play.png',
					'stopImgUrl' => $url ."://".$_SERVER["HTTP_HOST"]."/" . 'assets/addons/mobilediy/img/stop.png',
					'musicUrl' => ''
				],
				'style' => [
					'right' => 1,
					'bottom' => 80,
					'opacity' => 100
				]
			],
			'button' => [
                'name' => '图文按钮',
                'type' => 'button',
                'params' => [
					'imgUrl' => $url ."://".$_SERVER["HTTP_HOST"]."/" . 'assets/addons/mobilediy/img/menus.png',
					'showimg' => true,
                    'type' => 'default',
                    'size' => 'default',
                    'plain' => false,//1不镂空 2镂空
                    'round' => 'square',//1不圆角 2圆角
                    'disabled' => false,
                    'text' => '默认按钮',
                    'link' => null,
                ],
                'style' => [
                    'paddingTop' => 10,
                    'paddingLeft' => 10,
                    'background' => '#ffffff'
                ]
            ],
			'diymap' => [
                'name' => '地图展示',
                'type' => 'diymap',
                'params' => [
					'height' => 60,
					'latitude' => 39.909,
                    'longitude' => 116.39742,
                ],
                'style' => [
                    'paddingTop' => 10,
                    'paddingLeft' => 10,
                    'background' => '#ffffff'
                ]
            ],
            'timeline' => [
                'name' => '时间线',
                'type' => 'timeline',
                'style' => [
                    'paddingTop' => 10,
                    'paddingLeft' => 10,
                    'background' => '#ffffff',
                ],
                'data' => [
                    [
                        'time' => date('Y-m-d'),
                        'color' => '#0FAFFF',
                        'hide' => true,
                        'content' => '【申请账号】至Fastadmin申请账号；',
                    ],
                    [
                        'time' => date('Y-m-d'),
                        'color' => '#0FAFFF',
                        'hide' => true,
                        'content' => '【安装系统】下载服务端源码完整包，部署到站点完成搭建；',
                    ],
                    [
                        'time' => date('Y-m-d'),
                        'color' => '#0FAFFF',
                        'hide' => true,
                        'content' => '【安装插件】至后台插件管理，搜索《移动端DIY拖拽布局组件》在线安装，刷新缓存即可使用；',
                    ],
                ],
            ],
            'AlertTips' => [
                'name' => '警告提示',
                'type' => 'AlertTips',
                'params' => [
                    'type' => 'success',
                    'title' => '提示说明',
                    'description' => '《移动端DIY拖拽布局组件》十年如一日，专注于快速提供设计相应服务；',
                    'closable' => true,
                    'showicon' => true,
                ],
                'style' => [
                    'paddingTop' => 5,
                    'paddingLeft' => 5,
                    'background' => '#ffffff',
                ],
            ],
            
		];
	}
	
	
	/**
	 * diy页面详情
	 * @param int $id
	 * @return static|null
	 * @throws \think\exception\DbException
	 */
	public static function getdetail($id)
	{
		return static::get(['id' => $id]);
	}
	
	/**
	 * diy页面详情
	 * @return static|null
	 * @throws \think\exception\DbException
	 */
	public static function getHomePage()
	{
		return self::get(['status' => 'home']);
	}
}
