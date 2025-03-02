<template>
	<!-- 导航组 -->
	<view class="diy-navBar" :style="[{'background': background}]">
		<view class="data-list" :class="['avg-sm-'+itemStyle.rowsNum]">
			<view class="item-nav" v-for="(dataItem,index) in dataList" :key="index">
				<view class="nav-to" @click="onClick(dataItem.link)">
					<view class="item-image">
						<image mode="widthFix" :src="dataItem.imgUrl" :style="[{'border-radius': borderradius}]"></image>
					</view>
					<view class="item-text f-28 onelist-hidden" :style="[{'color': dataItem.color}]">{{ dataItem.text }}</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	import utils from '../../utils/mobilediy/utils.js'
	export default {
		computed: {
			background() {
				return this.itemStyle.background;
			},
			color() {
				return this.itemStyle.color;
			},
			borderradius() {
				return uni.upx2px(this.itemStyle.borderradius*2) + 'px';
			},
		},
		props: {
			itemStyle: {},
			params: {},
			dataList: {}
		},
		methods: {
			onClick(e) {
				console.log(e);
				if (!e) return;
				utils.openLink(e);
			},
		}
	}
</script>
<style>

	/* 导航组 */

	.diy-navBar .data-list::after {
		clear: both;
		content: " ";
		display: table;
	}

	.diy-navBar .item-nav {
		float: left;
		margin: 20rpx 0;
		text-align: center;
	}

	.diy-navBar .item-nav .item-image {
		margin-bottom: 8rpx;
		font-size: 0;
	}

	.diy-navBar .item-nav image {
		width: 88rpx;
		height: 88rpx;
	}

	/* 分列布局 */

	.diy-navBar .avg-sm-3>.item-nav {
		width: 33.33333333%;
	}

	.diy-navBar .avg-sm-4>.item-nav {
		width: 25%;
	}

	.diy-navBar .avg-sm-5>.item-nav {
		width: 20%;
	}
	
	.f-28 {
	  font-size: 28rpx;
	}
</style>
