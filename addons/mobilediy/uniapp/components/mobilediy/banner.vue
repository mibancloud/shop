<!-- 轮播组 -->
<template>
	<view class="wrap">
		<swiper class="swiper"  autoplay :interval="params.interval" :duration="duration" circular  @change='change' :previous-margin="params.type === 'card' ? '120rpx' : '0rpx'" :next-margin="params.type === 'card' ? '120rpx' : '0rpx'" :style="{height:params.type === 'card' ? '260rpx' : '350rpx',marginTop:params.type === 'card' ? '20rpx' : '0rpx'}">
			<swiper-item v-for="(item,index) in itemData" :key='index'>
				<view v-if="itemData && itemData.length>0" class="item" :class="[params.type != 'card' ? '' : (current==index ? 'crown-active':'crown')]">
					<image v-if="!slots" class="item-img" :class="[imgShadow?'imgShadow':'']" :src="item.imgUrl" :style="{ borderRadius: params.type === 'card' ? '10rpx' : '0rpx'}" mode="" @click="onClick(item.link)"></image>
					<slot v-else :data='item'></slot>
				</view>
			</swiper-item>
		</swiper>
		<view class="dots flex" :style="{bottom: 20 + 'rpx'}">
			<view class="dot" :class="[current == i ? 'curr-dot' : '']" v-for="(d,i) in itemData" :key='i'>
			</view>
		</view>
	</view>
</template>

<script>
	import utils from '../../utils/mobilediy/utils.js'
	export default {
		computed: {
		},
		props: {
			itemStyle: {},
			params: {},
			itemData: {},
			// 图片阴影
			imgShadow: {
				type: Boolean,
				default: false
			},
		},
		data() {
			return {
				current: 0,
				slots: false,
				duration: 500
			};
		},
		watch: {
		},
		methods: {
			onClick(e) {
				if (!e) return;
				utils.openLink(e);
			},
			change(event) {
				let current = event.detail.current
				this.current = current
				this.$emit('change', this.itemData[current])
			}
		}
	}
</script>

<style lang="scss" scoped>
	.wrap {
		position: relative;

		.crown {
			transform: scale(0.93, 0.85);
		}

		.item {
			height: 100%;
			transition: 1.2s;
		}

		.item-img {
			width: 100%;
			height: 100%;
		}

		.imgShadow {
			height: calc(100% - 20rpx);
			margin-bottom: 10px;
			box-shadow: 0 12rpx 12rpx rgba(0, 0, 0, .15);
		}

		.crown-active {
			transform: scale(1);
		}

		.dots {
			display: flex;
			position: absolute;
			left: 50%;
			transform: translateX(-50%);

			.dot {
				width: 6rpx;
				height: 6rpx;
				border-radius: 50%;
				background-color: #D6D6D6;
				margin-right: 8rpx;
			}

			.curr-dot {
				height: 6rpx;
				width: 22rpx;
				border-radius: 6rpx;
				background-color: #fff;
			}
		}

	}
</style>
