<template>
	<!-- 辅助线 -->
	<view class="diy-guide" :style="[{'background': background }, {'padding-top': paddingTop},{'padding-left':paddingLeft},{'padding-right':paddingLeft},{'padding-bottom': paddingTop}]">
		<view class="line" :style="{'border-top':line}">
		</view>
	</view>
</template>

<script>
	export default {
		computed: {
			paddingTop() {
				return uni.upx2px(this.itemStyle.paddingTop * 2) + 'px';
			},
			background() {
				return this.itemStyle.background;
			},
			paddingLeft() {
				return uni.upx2px(0) + 'px';
			},
			line() {
				return uni.upx2px(this.itemStyle.lineHeight * 2) + 'px' +' '+this.itemStyle.lineStyle +' '+this.itemStyle.lineColor;
			}
		},
		props: {
			itemStyle: {}
		}
	}
</script>

<style>
	.diy-guide .line {
		width: 100%;
	}
</style>
