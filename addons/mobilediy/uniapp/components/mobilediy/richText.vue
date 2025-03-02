<template>
	<!-- 富文本 -->
	<view class="diy-richText" :style="[{'padding-top': paddingTop},{'padding-left':paddingLeft},{'padding-right':paddingLeft},{'padding-bottom': paddingTop},{background: background}]">
		<view :style="[{'padding-top': cardPaddingTop},{'padding-left':cardPddingLeft},{'padding-right':cardPddingLeft},{'padding-bottom': cardPaddingTop},{borderRadius:borderradius},{background: cardbackground}]">
			<rich-text :nodes="params.content"></rich-text>
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
				return uni.upx2px(this.itemStyle.paddingLeft * 2) + 'px';
			},
			
			cardbackground() {
				return this.params.background;
			},
			cardPaddingTop() {
				return uni.upx2px(this.params.paddingTop * 2) + 'px';
			},
			cardPddingLeft() {
				return uni.upx2px(this.params.paddingLeft * 2) + 'px';
			},
			borderradius(){
				return uni.upx2px(this.params.borderradius * 2) + 'px';
			}
		},
		props: {
			itemStyle: {},
			params: {}
		},
		methods: {

		}
	}
</script>

<style>
</style>
