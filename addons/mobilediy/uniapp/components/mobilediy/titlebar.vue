<!-- 标题单元 -->
<template>
	<view :style="[{'background': background},{'padding-top': paddingTop},{'padding-left':paddingLeft},{'padding-right':paddingLeft},{'padding-bottom': paddingTop}]" class="diy-titlebar">
		<view class="titlebar-left" :style="{color:params.textColor}">
			<view class="titlebar-left-ming">{{params.title1}}</view>
			<view class="titlebar-left-an">{{params.title2}}</view>
		</view>
		<view class="titlebar-right" @click="onClick(params.link)">{{params.title3}}</view>
	</view>
</template>

<script>
	import utils from '../../utils/mobilediy/utils.js'
	export default {
		computed: {
			paddingTop() {
				return uni.upx2px(this.itemStyle.paddingTop*2) + 'px';
			},
			background() {
				return this.itemStyle.background;
			},
			paddingLeft() {
				return uni.upx2px(this.itemStyle.paddingLeft*2) + 'px';
			}
		},
		props: {
			itemStyle: {},
			params: {}
		},
		methods: {
			onClick(e) {
				if (!e) return;
				utils.openLink(e);
			},
		}
	}
</script>

<style>
	.diy-titlebar{
		display: flex;
		justify-content: space-between;
		align-items: center;
	}
	.titlebar-left{display: flex;position: relative;}
	.titlebar-left-ming{
	    z-index: 100;
	    font-size: 36rpx;
	    font-weight: bold;
	    text-shadow: 6rpx 6rpx 8rpx rgba(0, 102, 204, 0.2);
	    margin-bottom: 12px;}
	.titlebar-left-an{
	    opacity: 0.2;
	    left: 0.5rem;
	    position: absolute;
	    display: inline-block;
	    bottom: 0.4rem;
	    font-size: 36rpx;
	    font-weight: bold;
	}
	.titlebar-right{color:#909399;}
</style>
