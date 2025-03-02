/**
 * mobilediy公用方法
 */

/**
 * ip地址
*/
const api_root = 'https://diy.xiaowo6.cn';


// tabBar页面放入这里面，页面跳转tab会用switchTab，否则跳tab会失败
const tabBarLinks = [];

module.exports = {
	
	// 显示失败提示框
	showError(msg, callback) {
		uni.showModal({
			title: '友情提示',
			content: msg,
			showCancel: false,
			success: function(res) {
				callback && callback();
			}
		});
	},
	
	/**
	 * 跳转到指定页面
	 */
	navigationTo(urls) {
		console.log(urls);
		if (!urls || urls.length == 0) {
			return false;
		}
		if (tabBarLinks.indexOf(urls) > -1) {// tabBar页面
			uni.switchTab({
				url: '/' + urls
			});
		} else {// 普通页面
			uni.navigateTo({
				url: '/' + urls
			});
		}
	},
	
	// get请求的方法
	http_get(url, data, success, fail, complete) {
		let App = this;
		// 构造请求参数
		data = data || {};
	
		// 构造get请求
		let request = function() {
			uni.request({
				url: api_root + url,
				header: {
					'content-type': 'application/x-www-form-urlencoded' //自定义请求头信息
				},
				data: data,
				success: function(res) {
					if (res.statusCode !== 200 || typeof res.data !== 'object') {
						App.showError('网络请求出错');
						return false;
					}
					if (res.data.code === -1) {
	
						// 登录态失效, 重新登录
						App.doLogin();
					} else if (res.data.code === 0) {
						App.showError(res.data.msg, function() {
							fail && fail(res);
						});
						return false;
					} else {
						success && success(res.data);
					}
				},
				fail: function(res) {
					App.showError(res.errMsg, function() {
						fail && fail(res);
					});
				},
				complete: function(res) {
					uni.hideNavigationBarLoading();
					complete && complete(res);
				},
			});
		};
		request();
	},
	
	/* 打开外部浏览器 */
	openBrowser(url){
		// #ifdef MP-WEIXIN
		this.setClipboardData(url)
		return;
		// #endif

		// #ifdef APP-PLUS
		plus.runtime.openURL(url);
		return;
		// #endif

		// #ifdef H5
		window.location.href = url;
		return;
		// #endif
	},
	
	/**
	 * 跳转到指定小程序
	 * @param {string}  appId
	 * @param {string}  path
	 */
	navigateToMiniProgram(appId, path){
		// #ifndef MP
		return;
		// #endif
		
		if (!appId || appId.length == 0) {
		  return false
		}
		uni.navigateToMiniProgram({
		  appId: appId,
		  path: path,
		  extraData: {},
		  success(res) {}
		})
		return true
	},
	/**
	 * 打开qq会话
	 * @param {number} number qq号码
	 */
	openQQ(number){
		// #ifdef MP
		this.setClipboardData(number);
		return;
		// #endif
		let url = '';
		// #ifdef APP-PLUS
			url = 'mqqwpa://im/chat?chat_type=crm&uin=' + number;
		// #endif
		
		// #ifdef H5
			url = 'http://wpa.qq.com/msgrd?v=3&uin=' + number + '&site=qq&menu=yes';
		// #endif
		this.openBrowser(url);
	},
	/**设置剪贴板
	 * @param {string} text 
	 */
	setClipboardData(text){
		text = typeof text === 'string' ? text : text.toString();
		// #ifdef H5
		if (!document.queryCommandSupported('copy')) {
			uni.showToast({title: '浏览器不支持',icon:'error'});
			return;
		}
		let textarea = document.createElement("textarea")
		textarea.value = text
		textarea.readOnly = "readOnly"
		document.body.appendChild(textarea)
		textarea.select()
		textarea.setSelectionRange(0, text.length)
		let result = document.execCommand("copy")
		if(result){
			uni.showToast({title: '复制成功~'})
		}else{
			uni.showToast({title: '复制失败！',icon:'error'})
		}	
		textarea.remove()
		return;
		// #endif
		uni.setClipboardData({
			data: text,
			success: function () {
				uni.showToast({title: '复制成功~'})
			}
		});
	},
	
	/**
	 * 打开链接，服务端数据
	 * @param {Object} links
	 */
	openLink(links){
		switch (links.type) {
			case 'Inlay':
				this.navigationTo(links.path);
				break;
			case 'Custom':
				this.navigationTo(links.path);
				break;
			case 'WXMp':
				this.navigateToMiniProgram(links.appid,links.path);
				break;
			case 'Outside':
				this.openBrowser(links.url);
				break;
			case 'Phone':
				uni.makePhoneCall({
					phoneNumber: links.phone
				})
				break;
			case 'QQ':
				this.openQQ(links.qq);
				break;
			case 'Copy':
				this.setClipboardData(links.text);
				break;
			case 'notice':
				uni.$emit(links.key,{data:links.data})
				break;
			default:
				break;
		}
	}

};
