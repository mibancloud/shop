<!-- 单图组 -->
<template>
	<view class="diy-imageSingle" :style="[{'padding-bottom': paddingTop},{'background': background}]">
		<view class="item-image" v-for="(dataItem,index) in dataList" :key='index' :style="[{'padding-top': paddingTop},{'padding-left':paddingLeft},{'padding-right':paddingLeft},{'padding-bottom': paddingbottom}]">
			<view class="nav-to" @tap="onClick(dataItem.link)" :data-url="dataItem.linkUrl" >
				<image :src="dataItem.imgUrl" mode="widthFix" :style="[{'border-radius': borderradius}]"></image>
			</view>
		</view>
	</view>
</template>

<script>
	import utils from '../../utils/mobilediy/utils.js'
	export default {
		computed: {
			paddingTop() {
				return uni.upx2px(this.itemStyle.paddingTop*2) + 'px';
			},
			borderradius() {
				return uni.upx2px(this.itemStyle.borderradius*2) + 'px';
			},
			background() {
				return this.itemStyle.background;
			},
			paddingLeft() {
				return uni.upx2px(this.itemStyle.paddingLeft*2) + 'px';
			},
			paddingbottom() {
				return uni.upx2px(0) + 'px';
			}
		},
		props: {
			itemStyle: {},
			params: {},
			dataList: {}
		},
		methods: {
			//跳转
			onClick(e) {
				if (!e) return;
				utils.openLink(e);
			},
		}
	}
</script>

<style>
	.diy-imageSingle .item-image image {
		display: block;
		width: 100%;
	}
</style>
