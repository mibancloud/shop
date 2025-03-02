<template>
	<view class="container">
		<mobilediy :diyItems="diyItems" v-if="diyItems"></mobilediy>
	</view>
</template>
<script>
	import mobilediy from '../../../components/mobilediy/mobilediy.vue'
	import utils from '../../../utils/mobilediy/utils.js'
	export default {
		components: {mobilediy},
		data() {
			return {
				options: [],
				diyItems: null,
				page: {},
			}
		},
		/**
		 * 生命周期函数--监听页面加载
		 */
		onLoad: function(options) {
			let _this = this;
			// 页面id
			_this.page_id = options.page_id;
			// 加载页面数据
			_this.getPageData();
		},

		/**
		 * 下拉刷新
		 */
		onPullDownRefresh: function() {
			// 获取首页数据
			this.getPageData(function() {
				uni.stopPullDownRefresh();
			});
		},
		// #ifdef MP-WEIXIN
		onShareAppMessage() {
			return {
				title: this.page.params.share_title,
			}
		},
		onShareTimeline() {
			return {
				title: this.page.params.share_title,
			}
		},
		// #endif
		methods: {
			/**
			 * 加载页面数据
			 */
			getPageData: function(callback) {
				let _this = this;
				utils.http_get('/addons/mobilediy/mobilediy/getPage', {
					page_id: _this.page_id
				}, function(result) {
					// 设置顶部导航栏栏
					_this.setPageBar(result.data.page);
					_this.page = result.data.page;
					_this.diyItems = result.data.items,
					// 回调函数
					typeof callback === 'function' && callback();
				});
			},

			/**
			 * 设置顶部导航栏
			 */
			setPageBar: function(page) {
				// 设置页面标题
				uni.setNavigationBarTitle({
					title: page.params.title
				});
				// #ifdef H5
				if (!page.style.showNav) document.getElementsByTagName('uni-page-head')[0].style.display = 'block';
				else document.getElementsByTagName('uni-page-head')[0].style.display = 'none';
				if (page.style.showNav) return;
				// #endif
				
				// 设置navbar标题、颜色
				uni.setNavigationBarColor({
					frontColor: page.style.titleTextColor === 'white' ? '#ffffff' : '#000000',
					backgroundColor: page.style.titleBackgroundColor
				})
			},
		}
	}
</script>
<style lang="scss">
	page {
		background: $uni-bg-color-grey;
		height: 100%;
	}
	.container{
		background: $uni-bg-color;
		height: 100%;
	}
</style>
