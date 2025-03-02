<template>
	<!-- 视频组 -->
	<view class="diy-video" :style="[{'padding-top': paddingTop},{'padding-bottom': paddingTop}]">
		<video  :style="[{'height':height}]" :src="params.videoUrl" :poster="params.poster" autoplay='true' controls="false"></video>
	</view>
	
</template>

<script>
	export default {
		computed: {
			paddingTop() {
				return uni.upx2px(this.itemStyle.paddingTop*2) + 'px';
			},
			height() {
				return uni.upx2px(this.itemStyle.height * 2 ) + 'px';
			}
		},
		props: {
			itemStyle: {},
			params: {}
		}
	}
</script>

<style>
	.diy-video video {
		width: 100%;
		display: block;
	}
</style>
