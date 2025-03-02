define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'moment'], function ($, undefined, Backend, Table, Form, Moment) {

    var Controller = {
        index: () => {
            const { reactive, onMounted } = Vue
            const index = {
                setup() {
                    const state = reactive({
                        total: {
                            user: {
                                data: {},
                                color: '#806AF6',
                                color1: 'rgba(128, 106, 246, 0.4)',
                                color2: 'rgba(128, 106, 246, 0)',
                                title: '用户数量',
                                tip: '今日新增用户',
                                footer: '本周新增用户人数',
                            },
                            agent: {
                                data: {},
                                color: '#409EFF',
                                color1: 'rgba(64, 158, 255, 0.4)',
                                color2: ' rgba(64, 158, 255, 0)',
                                title: '分销商数量',
                                tip: '今日新增人数',
                                footer: '本周新增分销商人数',
                            },
                            share: {
                                data: {},
                                color: '#21C732',
                                color1: 'rgba(33, 199, 50, 0.4)',
                                color2: 'rgba(33, 199, 50, 0)',
                                title: '分享次数',
                                tip: '今日分享次数',
                                footer: '本周新增分享次数',
                            },
                        },
                    })

                    function getTotal() {
                        Fast.api.ajax({
                            url: 'shopro/dashboard/total',
                            type: 'GET',
                        }, function (ret, res) {
                            state.total.agent.data = res.data.agent_data;
                            state.total.share.data = res.data.share_data;
                            state.total.user.data = res.data.user_data;
                            for (var key in state.total) {
                                if (state.total[key].data) {
                                    initChartTotal(key)
                                }
                            }
                            return false
                        }, function (ret, res) { })
                    }
                    function initChartTotal(key) {
                        var myChart = echarts.init(document.getElementById(`${key}Total`));
                        window.onresize = () => {
                            myChart.resize()
                        }
                        var option = {
                            grid: {
                                left: 6,
                                top: 6,
                                right: 6,
                                bottom: 6,
                            },
                            tooltip: {
                                trigger: 'axis',
                                axisPointer: {
                                    type: 'none',
                                },
                            },
                            xAxis: {
                                type: 'category',
                                data: ['分', 20, 36, 10, 10, 20],
                                boundaryGap: false,
                                axisTick: {
                                    show: false,
                                },
                                axisLabel: {
                                    show: false,
                                },
                                axisLine: {
                                    show: false,
                                },
                            },
                            yAxis: {
                                type: 'value',
                                splitLine: {
                                    show: false,
                                },
                                axisLabel: {
                                    show: false,
                                },
                            },
                            series: [{
                                // name: state.total.user.title,
                                data: [5, 20, 36, 10, 10, 20], // [5, 20, 36, 10, 10, 20]
                                type: 'line',
                                smooth: true,
                                showSymbol: false,
                                symbol: 'circle',
                                symbolSize: 6,
                                itemStyle: {
                                    color: state.total[key].color,
                                },
                                areaStyle: {
                                    color: new echarts.graphic.LinearGradient(
                                        0,
                                        0,
                                        0,
                                        1,
                                        [
                                            {
                                                offset: 0,
                                                color: state.total[key].color1,
                                            },
                                            {
                                                offset: 1,
                                                color: state.total[key].color2,
                                            },
                                        ],
                                        false,
                                    ),
                                },
                                lineStyle: {
                                    width: 2,
                                },
                            }]
                        };

                        initIntervalTotal(24, 'hours', option, key)

                        myChart.setOption(option);
                    }
                    function initIntervalTotal(interval, kld, option, key) {
                        let dateTime = `${Moment().format('YYYY-MM-DD')} 00:00:00`;
                        let x = [];
                        let y = [];
                        let timeStamp = [];
                        for (let i = 0; i <= interval; i++) {
                            x.push(Moment(dateTime).add(i, kld).format('HH:mm'));
                            y.push(0);
                            timeStamp.push(Moment(dateTime).add(i, kld).valueOf());
                        }

                        x.forEach((item, index) => {
                            state.total[key].data.array.forEach((item) => {
                                if (
                                    timeStamp[index + 1] &&
                                    item.createtime_unix > timeStamp[index] &&
                                    item.createtime_unix <= timeStamp[index + 1]
                                ) {
                                    y[index]++;
                                }
                            });
                        });
                        option.xAxis.data = x;
                        option.series[0].data = y;
                    }

                    const chart = reactive({
                        tabsData: {
                            order: '订单数',
                            payOrder: '支付订单',
                            payAmount: '支付金额',
                        },
                        tabActive: 'order',
                        dateTime: getTimeSlot(),
                        shortcuts: [
                            {
                                text: '今天',
                                value: getTimeSlot(),
                            },
                            {
                                text: '昨天',
                                value: () => {
                                    return getTimeSlot('yesterday');
                                },
                            },
                            {
                                text: '近一周',
                                value: () => {
                                    return getTimeSlot('week');
                                },
                            },
                        ],
                        data: {
                            payAmountArr: [], // 销售额
                            payOrderArr: [], // 订单
                            orderArr: [], //订单数
                        },
                    });

                    function onChangeTabActive(type) {
                        chart.tabActive = type;
                        chartOption.series[0].name = chart.tabsData[chart.tabActive];
                        initChart();
                    }

                    function onChangeDateTime(e) {
                        // 时间date必选
                        e && getChart();
                    }

                    const statistics = reactive({
                        order: {
                            num: 0,
                            text: '订单数',
                            path: '',
                            tip: '时间区间内总的下单数量(包含未支付订单)',
                            status: 'all',
                        },
                        payAmount: {
                            num: 0,
                            text: '支付金额',
                            path: '',
                            tip: '时间区间内支付订单的支付总金额(包含退款订单)',
                            status: 'paid',
                        },
                        payOrder: {
                            num: 0,
                            text: '支付订单',
                            path: '',
                            tip: '时间区间内支付的订单数量(包含退款订单)',
                            status: 'paid',
                        },
                        noSend: {
                            num: 0,
                            text: '待发货订单',
                            path: '',
                            tip: '时间区间内待发货订单数量',
                            status: 'nosend',
                        },
                        aftersale: {
                            num: 0,
                            text: '售后维权',
                            path: '',
                            tip: '时间区间内申请售后维权的订单数量',
                            status: 'aftersale',
                        },
                        refund: {
                            num: 0,
                            text: '退款订单',
                            path: '',
                            tip: '时间区间内退款的订单数量',
                            status: 'refund',
                        },
                    });

                    async function getChart() {
                        Fast.api.ajax({
                            url: 'shopro/dashboard/chart',
                            type: 'GET',
                            data: {
                                date: chart.dateTime.join(' - '),
                            }
                        }, function (ret, res) {
                            for (let key in statistics) {
                                statistics[key].num = res.data[`${key}Num`];
                            }

                            chart.data.payAmountArr = res.data.payAmountArr; // 销售额
                            chart.data.payOrderArr = res.data.payOrderArr; // 订单
                            chart.data.orderArr = res.data.orderArr; //订单数

                            initChart();
                            return false
                        }, function (ret, res) { })
                    }

                    // 柱状图参数
                    const chartOption = reactive({
                        grid: {
                            left: '10px',
                            top: '20px',
                            bottom: '20px',
                            right: '20px',
                            containLabel: true,
                        },
                        xAxis: {
                            type: 'category',
                            data: [],
                            offset: 5,
                            axisLine: {
                                show: false,
                            },
                            axisTick: {
                                show: false,
                            },
                        },
                        yAxis: {
                            type: 'value',
                            offset: 5,
                            splitLine: {
                                show: false,
                            },
                            axisTick: {
                                show: false,
                            },
                            axisLine: {
                                show: false,
                            },
                        },
                        tooltip: {
                            trigger: 'axis',
                            axisPointer: {
                                show: true,
                                status: 'shadow',
                                z: -1,
                                shadowStyle: {
                                    color: 'rgba(191, 191, 191, 0.24)',
                                },
                                type: 'shadow',
                            },
                        },
                        series: [
                            {
                                name: chart.tabsData[chart.tabActive],
                                type: 'bar',
                                data: [],
                                zlevel: 1,
                                z: 1,
                                label: {
                                    show: false,
                                    position: 'top',
                                },
                                itemStyle: {
                                    color: '#806af6',
                                },
                                showBackground: true,
                                backgroundStyle: {
                                    color: 'rgba(191, 191, 191, 0.24)',
                                },
                            },
                        ],
                    });

                    // 获取时间刻度
                    function initChart() {
                        if (chart.dateTime) {
                            let time =
                                (new Date(chart.dateTime[1].replace(/-/g, '/')).getTime() -
                                    new Date(chart.dateTime[0].replace(/-/g, '/')).getTime()) /
                                1000 +
                                1;
                            let kld = '';
                            let interval = 0;
                            if (time <= 60 * 60) {
                                interval = parseInt(time / 60);

                                kld = 'minutes';
                            } else if (time <= 60 * 60 * 24) {
                                interval = parseInt(time / (60 * 60));

                                kld = 'hours';
                            } else if (time <= 60 * 60 * 24 * 30 * 1.5) {
                                interval = parseInt(time / (60 * 60 * 24));

                                kld = 'days';
                            } else if (time < 60 * 60 * 24 * 30 * 24) {
                                interval = parseInt(time / (60 * 60 * 24 * 30));

                                kld = 'months';
                            } else if (time >= 60 * 60 * 24 * 30 * 24) {
                                interval = parseInt(time / (60 * 60 * 24 * 30 * 12));

                                kld = 'years';
                            }
                            drawX(interval, kld);
                            console.log(chartOption, 'chartOption')
                            var myChart2 = echarts.init(document.getElementById(`chartContent`));
                            window.onresize = () => {
                                myChart2.resize()
                            }


                            myChart2.setOption(chartOption);

                        } else {
                            chartOption.xAxis.data = [];
                            chartOption.series[0].data = [];
                        }
                    }
                    // 给柱状图数据赋值
                    function drawX(interval, kld) {
                        let x = [];
                        let y = [];
                        let timeStamp = [];
                        for (let i = 0; i <= interval - 1; i++) {
                            if (kld == 'minutes' || kld == 'hours') {
                                x.push(Moment(chart.dateTime[0]).add(i, kld).format('DD HH:mm'));
                                y.push(0);
                            } else if (kld == 'days') {
                                x.push(Moment(chart.dateTime[0]).add(i, kld).format('YYYY-MM-DD'));
                                y.push(0);
                            } else if (kld == 'months') {
                                x.push(Moment(chart.dateTime[0]).add(i, kld).format('YYYY-MM'));
                                y.push(0);
                            } else {
                                x.push(Moment(chart.dateTime[0]).add(i, kld).format('YYYY'));
                                y.push(0);
                            }
                        }
                        for (let i = 1; i <= interval; i++) {
                            timeStamp.push(Moment(chart.dateTime[0]).add(i, kld).valueOf());
                        }
                        x.forEach((item, index) => {
                            chart.data[`${chart.tabActive}Arr`].forEach((item) => {
                                if (
                                    item.createtime > (index - 1 >= 0 ? timeStamp[index - 1] : 0) &&
                                    item.createtime <= timeStamp[index]
                                ) {
                                    if (chart.tabActive == 'payAmount') {
                                        y[index] = (Number(y[index]) + Number(item.counter)).toFixed(2);
                                    } else {
                                        y[index]++;
                                    }
                                }
                            });
                        });
                        chartOption.xAxis.data = x;
                        chartOption.series[0].data = y;
                    }
                    // 默认获取当天的时间赋值
                    function getTimeSlot(e) {
                        let beginTime = Moment(new Date()).format('YYYY-MM-DD');
                        let endTime = Moment(new Date()).format('YYYY-MM-DD');
                        switch (e) {
                            case 'yesterday':
                                endTime = Moment().subtract(1, 'days').format('YYYY-MM-DD');
                                beginTime = endTime;
                                break;
                            case 'week':
                                beginTime = Moment().subtract(1, 'weeks').format('YYYY-MM-DD');
                                break;
                            case 'month':
                                beginTime = Moment().subtract(1, 'months').format('YYYY-MM-DD');
                        }
                        let timeSlot = [beginTime + ' 00:00:00', endTime + ' 23:59:59'];
                        return timeSlot;
                    }

                    const ranking = reactive({
                        goods: [],
                        hot_search: []
                    })
                    const pieOption = reactive({
                        tooltip: {
                            trigger: 'item',
                            formatter: '{a} <br/>{b}: {c} ({d}%)',
                        },
                        legend: {
                            show: false,
                        },
                        series: [
                            {
                                name: '热搜榜',
                                type: 'pie',
                                radius: ['52%', '90%'],
                                avoidLabelOverlap: false,
                                label: {
                                    show: false,
                                    position: 'center',
                                },
                                zlevel: 1,
                                z: 1,
                                emphasis: {
                                    label: {
                                        show: true,
                                        fontSize: '16',
                                        fontWeight: 'normal',
                                    },
                                },
                                labelLine: {
                                    show: false,
                                },
                                data: [],
                            },
                        ],
                    });
                    function getRanking() {
                        Fast.api.ajax({
                            url: 'shopro/dashboard/ranking',
                            type: 'GET',
                        }, function (ret, res) {
                            ranking.goods = res.data.goods
                            ranking.hot_search = res.data.hot_search
                            pieOption.series[0].data = []
                            ranking.hot_search.forEach(item => {
                                pieOption.series[0].data.push({
                                    name: item.keyword,
                                    value: item.num,
                                });
                            })

                            var myChart3 = echarts.init(document.getElementById(`rankingContent`));
                            window.onresize = () => {
                                myChart3.resize()
                            }

                            myChart3.setOption(pieOption);

                            return false
                        }, function (ret, res) { })
                    }

                    function onOpen(status) {
                        Fast.api.open(`shopro/order/order/index?status=${status}&createtime=${encodeURI(JSON.stringify(chart.dateTime))}`, "订单", {
                            callback() {
                                getChart()
                            }
                        })
                    }

                    onMounted(() => {
                        Config.cardList.forEach(item => {
                            if (item.name == 'total' && item.status) {
                                getTotal()
                            }
                            if (item.name == 'chart' && item.status) {
                                getChart()
                            }
                            if (item.name == 'ranking' && item.status) {
                                getRanking()
                            }
                        })
                    })

                    return {
                        state,
                        getTotal,
                        chart,
                        onChangeTabActive,
                        onChangeDateTime,
                        statistics,
                        getChart,
                        chartOption,
                        initChart,
                        drawX,
                        getTimeSlot,
                        ranking,
                        onOpen,
                    }
                }
            }
            createApp('index', index);
        },
    };
    return Controller;
});
