<!-- 单图组 -->
<template>
	<view :style="[{'background': background},{'padding-top': paddingTop},{'padding-left':paddingLeft},{'padding-right':paddingLeft},{'padding-bottom': paddingTop}]">
		<u-time-line>
			<u-time-line-item v-for="(item,index) in dataList" :key="index" :bgColor="item.color">
				<template v-slot:content>
					<view>
						<view class="u-order-desc">{{item.content}}</view>
						<view class="u-order-time" v-if="item.hide">{{item.time}}</view>
					</view>
				</template>
			</u-time-line-item>
		</u-time-line>
	</view>
</template>

<script>
	export default {
		components: {},
		computed: {
			paddingTop() {
				return uni.upx2px(this.itemStyle.paddingTop*2) + 'px';
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
			dataList: {}
		},
		methods: {
		}
	}
</script>

<style>
		
	.u-order-time {
		color: rgb(200, 200, 200);
		font-size: 26rpx;
		margin-top: 10rpx;
	}
</style>
