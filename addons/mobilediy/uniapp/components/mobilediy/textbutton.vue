<template>
	<!-- 文字按钮 -->
	<view class="diy-navBar" :style="[{'background': background},{padding: itemStyle.paddingTop +'px '+'0'}]">
		<view class="diy-textbutton" style="padding:4px 5px;">
			<view class="diy-textbutton-item" v-for="dataItem in dataList" :style="{color: dataItem.color}" @click="onClick(dataItem.link)">{{dataItem.text}}
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
			}
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

	/* 导航组 */

	.diy-navBar .data-list::after {
		clear: both;
		content: " ";
		display: table;
	}

	.diy-navBar .item-nav {
		float: left;
		margin: 20upx 0;
		text-align: center;
	}

	.diy-navBar .item-nav .item-image {
		margin-bottom: 8upx;
		font-size: 0;
	}

	.diy-navBar .item-nav image {
		width: 88upx;
		height: 88upx;
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
	
	  .diy-textbutton{
		height: auto;
	   }
	  .diy-textbutton-item{
		padding: 0.5rem;
		text-align: center;
		position: relative;
		color: #666;
		font-size: 0.75rem;
		width: 1%;
		display: table-cell;
		-webkit-user-select: none;
		-moz-user-select: none;
		transition: background-color 300ms;
		-webkit-transition: background-color 300ms;
		}
</style>
