<!-- 地图组 -->
<template>
	<view :style="[{'background': background},{'padding-top': paddingTop},{'padding-left':paddingLeft},{'padding-right':paddingLeft},{'padding-bottom': paddingTop}]">
		<map class="map" :latitude="params.latitude" :longitude="params.longitude" :style="[{height:height}]" @tap="onClick"></map>
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
			height(){
				return uni.upx2px(this.params.height*2) + 'px';
			},
			paddingLeft() {
				return uni.upx2px(this.itemStyle.paddingLeft*2) + 'px';
			}
		},
		props: {
			itemStyle: {},
			params:{}
		},
		methods: {
			onClick(){
				 uni.openLocation({
					latitude: parseFloat(this.params.latitude),
					longitude: parseFloat(this.params.longitude),
					success: function () {
						console.log('success');
					},
					complete:function(e){
						console.log(e);
					}
				});
			}
		}
	}
</script>

<style>
	.map{
		width: 100%;
	}
</style>
