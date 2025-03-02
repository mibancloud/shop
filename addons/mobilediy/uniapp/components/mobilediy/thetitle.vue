<template>
	<!-- 主副标题 -->
	<view class="diy-thetitle" :style="[{'background': background },{'padding-left':paddingLeft},{'padding-right':paddingLeft}]">
		<view class="lineStyle1" v-if="params.lineStyle == 'lineStyle1'"  :style="{padding: itemStyle.paddingTop +'px '+'0', background:itemStyle.background }">
			<view>
				<view class="line" :style="{background: params.lineColor}"></view>
				<view class="zhutitle" :style="{color: params.zhutitlecolor,fontSize:params.zhutitlesize+'px'}">{{params.zhutitle}}</view>
				<view class="line" :style="{background: params.lineColor}"></view>
			</view>
			<view class="text_futitle" :style="{color: params.futitlecolor,fontSize : params.futitlesize+'px'}">{{params.futitle}}</view>
		</view>


		<view v-if="params.lineStyle == 'lineStyle2'" :style="{padding: itemStyle.paddingTop +'px '+'0', background:itemStyle.background,paddingLeft:params.marginLeft +'px' }">
			<view style="line-height: 14px" :style="{color: params.zhutitlecolor,fontSize:params.zhutitlesize+'px'}">
				<span style="width: 2px;display: inline-block;margin-right: 5px;vertical-align: -2px;" :style="{background: params.lineColor,height:params.zhutitlesize+'px'}"></span>
				{{params.zhutitle}}
			</view>
			<view style="line-height: 12px;margin-top: 8px" :style="{color: params.futitlecolor,fontSize : params.futitlesize+'px'}">{{params.futitle}}</view>
		</view>

		<view v-if="params.lineStyle == 'lineStyle3'" :style="{padding: itemStyle.paddingTop +'px '+'0', background:itemStyle.background,paddingLeft:params.marginLeft +'px' }">
			<view style="line-height: 14px" :style="{color: params.zhutitlecolor,fontSize:params.zhutitlesize+'px'}">
				<span style="width: 2px;display: inline-block;margin-right: 5px;vertical-align: -2px;" :style="{background: params.lineColor,height:params.zhutitlesize+'px'}"></span>
				{{params.futitle}}
				<span style="line-height: 12px;margin-left: 5px" :style="{color: params.futitlecolor,fontSize : params.futitlesize+'px'}">{{params.futitle}}</span>
			</view>
		</view>


	</view>
</template>

<script>
	export default {
		computed: {
			background() {
				return this.itemStyle.background;
			},
			paddingLeft() {
				return uni.upx2px(0) + 'px';
			},
		},
		props: {
			itemStyle: {},
			params: {},
		}
	}
</script>

<style>
	.lineStyle1{
		text-align: center;
	}
	.lineStyle1 .line{
		display: inline-block;width: 15px;height: 2px;vertical-align: 5px;
	}
	.lineStyle1 .zhutitle{
		display: inline-block;color: #434343;margin:0 10px;line-height: 20px;
	}
	.lineStyle1 .text_futitle{
		line-height:14px;margin-top: 8px;text-align: center;
	}
</style>
