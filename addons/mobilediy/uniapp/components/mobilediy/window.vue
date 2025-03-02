<template>
	<!-- 图片橱窗 -->
	<view class="diy-window" :style="[{'background': background }, {'padding-top': paddingTop},{'padding-left':paddingLeft},{'padding-right':paddingLeft},{'padding-bottom': paddingTop}]">
		<view v-if=" itemStyle.layout > -1 " class="data-list" :class="['avg-sm-'+itemStyle.layout]">
			<view v-for="(dataItem,index) in dataList" :key='index' class="data-item" :style="[{'padding-top': paddingTop},{'padding-left':paddingLeft},{'padding-right':paddingLeft},{'padding-bottom': paddingTop}]">
				<view class="item-image" @click="onClick(dataItem.link)">
					<image class="image" :src="dataItem.imgUrl" mode="widthFix" :style="[{'border-radius': borderradius}]"></image>
				</view>
			</view>
		</view>
		<view v-else class="display">
			<view class="display-left" :style="[{'padding-top': paddingTop},{'padding-left':paddingLeft},{'padding-right':paddingLeft},{'padding-bottom': paddingTop}]">
				<image class="image" @click="onClick(dataList[0].link)" :src="dataList[0].imgUrl " :style="[{'border-radius': borderradius}]"></image>
			</view>
			<view class="display-right">
				<view v-if="dataList.length >= 2" class="display-right1" :style="[{'padding-top': paddingTop},{'padding-left':paddingLeft},{'padding-right':paddingLeft},{'padding-bottom': paddingTop}]">
					<image class="image" @click="onClick(dataList[1].link)" :src="dataList[1].imgUrl " :style="[{'border-radius': borderradius}]"></image>
				</view>
				<view class="display-right2">
					<view v-if="dataList.length >= 3" class="left" :style="[{'padding-top': paddingTop},{'padding-left':paddingLeft},{'padding-right':paddingLeft},{'padding-bottom': paddingTop}]">
						<image class="image" @click="onClick(dataList[2].link)" :src="dataList[2].imgUrl " :style="[{'border-radius': borderradius}]"></image>
					</view>
					<view v-if="dataList.length >= 4" class="right" :style="[{'padding-top': paddingTop},{'padding-left':paddingLeft},{'padding-right':paddingLeft},{'padding-bottom': paddingTop}]">
						<image class="image" @click="onClick(dataList[3].link)" :src=" dataList[3].imgUrl " :style="[{'border-radius': borderradius}]"></image>
					</view>
				</view>
			</view>
		</view>
	</view>
	
</template>

<script>
	import utils from '../../utils/mobilediy/utils.js'
	export default {
		computed: {
			paddingTop() {
				return uni.upx2px(this.itemStyle.paddingTop * 2) + 'px';
			},
			background() {
				return this.itemStyle.background;
			},
			paddingLeft() {
				return uni.upx2px(this.itemStyle.paddingLeft * 2) + 'px';
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
				if (!e) return;
				utils.openLink(e);
			},
		}
	}
</script>

<style>
	/* 图片橱窗 */
	
	.diy-window .data-list::after {
	  clear: both;
	  content: " ";
	  display: table;
	}
	
	.diy-window .data-list .data-item {
	  float: left;
	  box-sizing: border-box;
	}
	
	.diy-window .data-list .image {
	  display: block;
	  width: 100%;
	}
	
	/* 分列布局 */
	
	.diy-window .avg-sm-2 > .data-item {
	  width: 50%;
	}
	
	.diy-window .avg-sm-3 > .data-item {
	  width: 33.33333333%;
	}
	
	.diy-window .avg-sm-4 > .data-item {
	  width: 25%;
	}
	
	.diy-window .avg-sm-5 > .data-item {
	  width: 20%;
	}
	
	/* 橱窗样式 */
	
	.diy-window {
	  box-sizing: border-box;
	}
	
	.diy-window .display {
	  height: 0;
	  width: 100%;
	  margin: 0;
	  padding-bottom: 50%;
	  position: relative;
	  box-sizing: border-box;
	}
	
	.diy-window .display .image {
	  width: 100%;
	  height: 100%;
	}
	
	.diy-window .display .display-left {
	  width: 50%;
	  height: 100%;
	  position: absolute;
	  top: 0;
	  left: 0;
	  box-sizing: border-box;
	}
	
	.diy-window .display .display-right {
	  width: 50%;
	  height: 100%;
	  position: absolute;
	  top: 0;
	  left: 50%;
	  box-sizing: border-box;
	}
	
	.diy-window .display .display-right1 {
	  width: 100%;
	  height: 50%;
	  position: absolute;
	  top: 0;
	  box-sizing: border-box;
	  left: 0;
	}
	
	.diy-window .display .display-right2 {
	  width: 100%;
	  height: 50%;
	  position: absolute;
	  top: 50%;
	  left: 0;
	  box-sizing: border-box;
	}
	
	.diy-window .display .display-right2 .left {
	  width: 50%;
	  height: 100%;
	  position: absolute;
	  top: 0;
	  left: 0;
	  box-sizing: border-box;
	}
	
	.diy-window .display .display-right2 .right {
	  width: 50%;
	  height: 100%;
	  position: absolute;
	  top: 0;
	  left: 50%;
	  box-sizing: border-box;
	}

</style>
