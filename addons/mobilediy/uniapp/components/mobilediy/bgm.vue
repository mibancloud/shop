<template>
	<!-- 背景音乐插件 -->
	<view class="diy-service" :style="[{'right':right},{'bottom': bottom}]" :class="[musicState ? 'mcStart':'']" @click="onClick()">
		<image :src="playImage"></image>
	</view>
</template>

<script>
	export default {
		computed: {
			playImage() {
				return this.musicState ? this.params.playImgUrl:this.params.stopImgUrl;
			},
			right() {
				return uni.upx2px(this.itemStyle.right * 2) + '%';
			},
			bottom() {
				return uni.upx2px(this.itemStyle.bottom * 2) + '%';
			}
		},
		data() {
			return {
			  musicState: true,
			  isPlaying: false,
			}
		},
		props: {
			itemStyle: {},
			params: {}
		},
		mounted(){
			this._audioContext = null;
			this.createAudio();
		},
		methods: {
			onClick(e) {
				if (this.params.musicUrl === '' || !this._audioContext)return;
				if (this.musicState && this.isPlaying){
					this.pause();
				}else if (!this.musicState && !this.isPlaying){
					this.play();
				}
				this.musicState = !this.musicState;
			},
			createAudio(){
				var _this = this;
				if (this.params.musicUrl === ''){
					this.musicState = false;
					return;
				} 
				var innerAudioContext = this._audioContext = uni.createInnerAudioContext();
				innerAudioContext.autoplay = true;
				innerAudioContext.loop = true;
				innerAudioContext.src = this.params.musicUrl;
				innerAudioContext.onPlay(() => {
					_this.isPlaying = true;
				});
			},
			play() {
				this.isPlaying = true;
				this._audioContext.play();
			},
			pause() {
				this.isPlaying = false;
				this._audioContext.pause();
			},
		}
	}
</script>

<style>
	/* 图片旋转动画样式 */
	@keyframes box-ani {
	    from {transform: rotate(0)}
	    to {transform: rotate(360deg)}
	}
	.mcStart{
		animation: box-ani 4s  infinite linear; /*旋转动画*/
	}
	
	/* 在线客服 */
	.diy-service {
		position: fixed;
		z-index: 999;
	}
	.diy-service image {
		display: block;
		width: 70rpx;
		height: 70rpx;
	}
</style>
