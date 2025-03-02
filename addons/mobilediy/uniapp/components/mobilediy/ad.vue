<template>
	<!-- #ifdef MP-WEIXIN -->
	<view :style="[{'padding': paddingTop + ' 0'},{'background': background}]">
		<view v-if="params.type == 'banner' && params.adid != ''" class="ad-center">
			<ad :unit-id="params.adid" ad-intervals="100"></ad>
		</view>
		<view v-if="params.type == 'custom' && params.adid != ''" class="ad-center">
			<ad-custom :unit-id="params.adid"></ad-custom>
		</view>
	</view>
	<!-- #endif -->
</template>

<script>
	export default{
		name: 'mobilediy-ad',
		computed: {
			paddingTop() {
				return uni.upx2px(this.itemStyle.paddingTop*2) + 'px';
			},
			background() {
				return this.itemStyle.background;
			},
		},
		props: {
			itemStyle: {},
			params: {},
		}
	}
</script>

<style>
	.ad-center{
		display: grid;
		justify-items: center;
	}
</style>
