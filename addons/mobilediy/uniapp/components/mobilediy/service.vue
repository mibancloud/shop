<template>
	<!-- 悬浮框插件 -->
	<view class="diy-service" :style="[{'right':right},{'bottom': bottom}]">
		<!-- 在线客服 -->
		<block v-if="params.type == 'service'">
			<button open-type="contact" class="btn-normal">
				<view class="service-icon">
					<image :src="params.image"></image>
				</view>
			</button>
		</block>
		<block v-else>
			<button class="btn-normal" @click="onClick()">
				<view class="service-icon">
					<image :src="params.image"></image>
				</view>
			</button>
		</block>
	</view>
</template>

<script>
	import utils from '../../utils/mobilediy/utils.js'
	export default {
		computed: {
			right() {
				return uni.upx2px(this.itemStyle.right * 2) + '%';
			},
			bottom() {
				return uni.upx2px(this.itemStyle.bottom * 2) + '%';
			}
		},
		props: {
			itemStyle: {},
			params: {}
		},
		methods: {
			onClick(e) {
				var _this = this;
				if (_this.params.type != 'link' && !_this.params.link) return;
				utils.openLink(_this.params.link);
			},
		}
	}
</script>

<style>
	/* 在线客服 */
	.diy-service {
		position: fixed;
		z-index: 999;
	}
	.diy-service .service-icon {
		padding: 10rpx;
	}
	.diy-service .service-icon image {
		display: block;
		width: 90rpx;
		height: 90rpx;
	}
	.btn-normal {
	  display: block;
	  margin: 0;
	  padding: 0;
	  line-height: normal;
	  background: none;
	  border-radius: 0;
	  box-shadow: none;
	  border: none;
	  font-size: unset;
	  text-align: unset;
	  overflow: visible;
	  color: inherit;
	}
	.btn-normal:after {
	  border: none;
	}
	.btn-normal.button-hover {
	  color: inherit;
	}
	button:after {
	  content: none;
	  border: none;
	}
</style>
