<template>
	<!-- 信息卡片 -->
 	<view class="diy-sharingGoods" :style="[{'background': background}]">
 		<view class="box pd16_15 flex">
 			<image mode="aspectFill" class="tuan-product-l" :src="params.image"></image>
 			<view class="tuan-product-r pl15">
 				<view class="ft16 ftw600 cl-main one-t" >{{params.title}}</view>
 				<view class="mt16">
 					<text class="ft12 cl-notice text-over2">{{params.content}}</text>
 				</view>
 				<view class="mt16 flex space">
 					<view class="btn-settlement" @click="onClick(params.link)">{{params.btntxt}}</view>
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
			borderradius() {
				return uni.upx2px(this.itemStyle.borderradius*2) + 'px';
			},
		},
		props: {
			itemStyle: {},
			params: {},
		},
		methods: {
			onClick(e) {
				if (!e) return;
				utils.openLink(e);
			},
		}
	}
</script>

 <style>
 	.diy-sharingGoods {
 		padding: 20upx;
 	}

 	.btn-settlement {
 		position: absolute;
 		right: 0;
 		bottom: 0;
 		padding: 0 28upx;
 		border-radius: 40upx;
 		background: linear-gradient(to right, #539a86 0%, #1ec74e 100%);
 		box-shadow: 0 2px 10px -2px #40755f;
 		font-size: 28upx;
 		line-height: 54upx;
 		text-align: center;
 		color: #fff;
 	}

 	.tuan-product-l {
 		width: 240rpx;
 		height: 240rpx;
 		border-radius: 16rpx;
 		background: #F2F2F2;
 	}

 	.tuan-product-r {
 		width: calc(100% - 260rpx);
 	}

 	.pd16_15 {
 		padding: 32rpx 30rpx;
 	}

 	.box {
 		background: #FFFFFF;
 		box-shadow: 0rpx 8rpx 40rpx 0rpx rgba(0, 0, 0, 0.04);
 		border-radius: 18rpx;
 	}

 	.flex {
 		display: flex;
 	}

 	.flex.space {
 		justify-content: space-between;
 		position: relative;
 		height: 27px;
 	}

 	.pl15 {
 		padding-left: 30rpx;
 	}


 	.ftw600 {
 		font-weight: 600;
 	}

 	.cl-main {
 		color: #2E2F33;
 	}

 	.text-over2 {
 		overflow: hidden;
 		text-overflow: ellipsis;
 		display: -webkit-box;
 		-webkit-line-clamp: 2;
 		-webkit-box-orient: vertical;
 	}

 	.cl-notice {
 		color: #AEB2C1;
 	}

 	.ft16 {
 		font-size: 32rpx;
 	}

 	.ft12 {
 		font-size: 24rpx;
 	}

 	.mt16 {
 		margin-top: 32rpx;
 	}
	
	/*单行文本溢出省略号*/
	.one-t {
		overflow: hidden;
		white-space: nowrap;
		text-overflow: ellipsis;
		transition: all linear 0.2s;
	}
	/*多行文本溢出省略号*/
	.more-t {
		overflow: hidden;
		text-overflow: ellipsis;
		display: -webkit-box;
		-webkit-line-clamp: 3;
		-webkit-box-orient: vertical;
		transition: all linear 0.2s;
	}
	
</style>
