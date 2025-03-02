<template>
	<view class="diy-button" :style="[{'background': background},{'padding-top': paddingTop},{'padding-left':paddingLeft},{'padding-right':paddingLeft},{'padding-bottom': paddingTop}]">
		<u-button style="width: 100%;"
			:type="params.type  == 'danger'?'error':params.type"
			:size="params.size"
			:shape="params.round"
			:plain="params.plain"
			:disabled="params.disabled"
			@click="click()"
		>
		<image class="img" v-if="params.showimg" :src="params.imgUrl" mode="widthFix"></image>
		{{params.text}}</u-button>
	</view>
</template>

<script>
export default {
	props: {
		itemStyle: {},
		params: {}
	},
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
	data() {
		return {
		};
	},
	methods: {
		// 按钮点击
		click(e) {
			console.log(e);
			if (!this.params.link) return;
			utils.openLink(this.params.link);
		}
	}
};
</script>

<style scoped lang="scss">
@import '../../utils/mobilediy/theme.scss';

.img{
	margin-right: 20rpx;
	width: 40rpx;
	height: 40rpx;
	border-radius: 8rpx;
}
</style>
