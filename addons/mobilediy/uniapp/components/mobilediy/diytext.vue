<!-- 文本组 -->
<template>
	<view :style="[{'background': background},{'padding-top': paddingTop},{'padding-left':paddingLeft},{'padding-right':paddingLeft},{'padding-bottom': paddingTop}]">
		<text :style="[{'color': itemStyle.textColor},{'fontSize': fontSize}]">{{itemStyle.text}}</text>
	</view>
</template>

<script>
	export default {
		computed: {
			paddingTop() {
				return uni.upx2px(this.itemStyle.paddingTop*2) + 'px';
			},
			background() {
				return this.itemStyle.background;
			},
			fontSize(){
				return uni.upx2px(this.itemStyle.fontsize*2) + 'px';
			},
			paddingLeft() {
				return uni.upx2px(this.itemStyle.paddingLeft*2) + 'px';
			}
		},
		props: {
			itemStyle: {},
		},
		methods: {
		}
	}
</script>

<style>
</style>
