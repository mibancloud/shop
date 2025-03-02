<template>
	<view>
		<web-view :src="url"></web-view>
	</view>
</template>

<script>
	export default {	
		props: {
			url: '',
		}
		
	}
</script>
