<template>
	<!-- 辅助空白 -->
	<view class="diy-blank" :style="[{'height':height},{'background': background}]">
	</view>
</template>

<script>
	export default {
		computed: {
			height() {
				return uni.upx2px(this.itemStyle.height * 2) + 'px';
			},
			background() {
				return this.itemStyle.background;
			}
		},
		props: {
			itemStyle: {},
		}
	}
</script>
