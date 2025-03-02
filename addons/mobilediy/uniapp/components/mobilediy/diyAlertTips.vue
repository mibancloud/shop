<!-- 警告提示 -->
<template>
	<block v-if="isshow" >
		<view :style="[{'background': background},{'padding-top': paddingTop},{'padding-left':paddingLeft},{'padding-right':paddingLeft},{'padding-bottom': paddingTop}]">
			<u-alert-tips 
				:type="params.type" 
				:title="params.title" 
				:description="params.description" 
				:close-able="params.closable"
				:show="show"
				@close="onClick"
				:show-icon="params.showicon">
			</u-alert-tips>
		</view>
	</block>
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
			paddingLeft() {
				return uni.upx2px(this.itemStyle.paddingLeft*2) + 'px';
			},
			paddingbottom() {
				return uni.upx2px(0) + 'px';
			}
		},
		props: {
			itemStyle: {},
			params: {},
		},
		data(){
			return{
				show:true,
				isshow:true,
			}
		},
		methods: {
			onClick(){
				this.show = false;
				this.isshow = false;
			}
		}
	}
</script>

<style>
</style>
