<template>
	<view>
		<block v-for="(item,index) in diyItems" :key='index'>
			<!-- 轮播图 -->
			<block v-if="item.type == 'banner'">
				<banner :itemStyle="item.style"  :itemData="item.data" :params="item.params"></banner>
			</block>
			<!-- 单图组 -->
			<block v-if="item.type == 'images'">
				<mobilediyimages :itemStyle="item.style" :dataList="item.data"></mobilediyimages>
			</block>
			<!-- 图片橱窗 -->
			<block v-if="item.type == 'window'">
				<window :itemStyle="item.style" :dataList="item.data"></window>
			</block>
			<!-- 视频组 -->
			<block v-if="item.type == 'video'">
				<divvideo :itemStyle="item.style" :params="item.params"></divvideo>
			</block>
			<!-- 广播组 -->
			<block v-if="item.type == 'notice'">
				<notice :itemStyle="item.style" :params="item.params"></notice>
			</block>
			<!-- 导航组 -->
			<block v-if="item.type == 'navBar'">
				<navBar :itemStyle="item.style" :dataList="item.data"></navBar>
			</block>
			<!-- 悬浮框 -->
			<block v-if="item.type == 'service'">
				<service :itemStyle="item.style" :params="item.params"></service>
			</block>
			<!-- 空白组 -->
			<block v-if="item.type == 'blank'">
				<diyblank :itemStyle="item.style"></diyblank>
			</block>
			<!-- 辅助线 -->
			<block v-if="item.type == 'guide'">
				<guide :itemStyle="item.style"></guide>
			</block>
			<!-- 信息卡片 -->
			<block v-if="item.type == 'informationcard'">
				<informationcard :itemStyle="item.style" :params="item.params"></informationcard>
			</block>
			<!-- 主副标题 -->
			<block v-if="item.type == 'thetitle'">
				<thetitle :itemStyle="item.style" :params="item.params"></thetitle>
			</block>
			<!-- 文字按钮 -->
			<block v-if="item.type == 'textbutton'">
				<textbutton :itemStyle="item.style" :dataList="item.data"></textbutton>
			</block>
			<!-- webview -->
			<block v-if="item.type == 'webview'">
				<webview :url="item.params.url"></webview>
			</block>
			<!-- 广告组 -->
			<block v-if="item.type == 'ad'">
				<ad :itemStyle="item.style" :params="item.params"></ad>
			</block>
			<!-- 文本组 -->
			<block v-if="item.type == 'text'">
				<diytext :itemStyle="item.style"></diytext>
			</block>
			<!-- 标题单元 -->
			<block v-if="item.type == 'titlebar'">
				<titlebar :itemStyle="item.style" :params="item.params"></titlebar>
			</block>
			<!-- 富文本 -->
			<block v-if="item.type == 'richText'">
				<richText :itemStyle="item.style" :params="item.params"></richText>
			</block>
			<!-- 背景音乐 -->
			<block v-if="item.type == 'bgm'">
				<bgm :itemStyle="item.style" :params="item.params"></bgm>
			</block>
			<!-- 图文按钮 -->
			<block v-if="item.type == 'button'">
				<diyButton :itemStyle="item.style" :params="item.params"></diyButton>
			</block>
			<!-- 地图组件 -->
			<block v-if="item.type == 'diymap'">
				<diymap :itemStyle="item.style" :params="item.params"></diymap>
			</block>
			<!-- 时间线 -->
			<block v-if="item.type == 'timeline'">
				<timeline :itemStyle="item.style" :dataList="item.data"></timeline>
			</block>
			<!-- 地图组件 -->
			<block v-if="item.type == 'AlertTips'">
				<diyAlertTips :itemStyle="item.style" :params="item.params"></diyAlertTips>
			</block>
			
		</block>
	</view>
</template>

<script>
	import banner from './banner.vue'
	import mobilediyimages from './images.vue'
	import window from './window.vue'
	import divvideo from './divvideo.vue'
	import notice from './notice.vue'
	import navBar from './navBar.vue'
	import service from './service.vue'
	import diyblank from './diyblank.vue'
	import guide from './guide.vue'
	import informationcard from './informationcard.vue'
	import thetitle from './thetitle.vue'
	import textbutton from './textbutton.vue'
	import webview from './webview.vue'
	import ad from './ad.vue'
	import titlebar from './titlebar.vue'
	import diytext from './diytext.vue'
	import richText from './richText.vue'
	import bgm from './bgm.vue'
	import diyButton from './diyButton.vue'
	import diymap from './diymap.vue'
	import timeline from './timeline.vue'
	import diyAlertTips from './diyAlertTips.vue'
	export default {
		components: {
			// （组件命名）
			banner,
			mobilediyimages,
			window,
			divvideo,
			notice,
			navBar,
			service,
			diyblank,
			guide,
			textbutton,
			thetitle,
			webview,
			ad,
			informationcard,
			titlebar,
			diytext,
			richText,
			bgm,
			diyButton,
			diymap,
			timeline,
			diyAlertTips
		},
		props: {
			diyItems: {},
		},
		
	}
</script>
