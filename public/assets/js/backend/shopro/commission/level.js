define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: () => {
            const { reactive, onMounted } = Vue
            const index = {
                setup() {
                    const state = reactive({
                        data: [],
                    })

                    function getData() {
                        Fast.api.ajax({
                            url: 'shopro/commission/level',
                            type: 'GET',
                        }, function (ret, res) {
                            state.data = res.data
                            return false
                        }, function (ret, res) { })
                    }

                    function onAdd() {
                        Fast.api.open(`shopro/commission/level/add?type=add`, "添加", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onEdit(id) {
                        Fast.api.open(`shopro/commission/level/edit?type=edit&id=${id}`, "编辑", {
                            callback() {
                                getData()
                            }
                        })
                    }
                    function onDelete(id) {
                        Fast.api.ajax({
                            url: `shopro/commission/level/delete/id/${id}`,
                            type: 'POST',
                        }, function (ret, res) {
                            getData();
                        }, function (ret, res) { })
                    }

                    onMounted(() => {
                        getData()
                    })

                    return {
                        state,
                        getData,
                        onAdd,
                        onEdit,
                        onDelete,
                    }
                }
            }
            createApp('index', index);
        },
        add: () => {
            Controller.form();
        },
        edit: () => {
            Controller.form();
        },
        form: () => {
            const { reactive, onMounted, getCurrentInstance } = Vue
            const addEdit = {
                setup() {
                    const { proxy } = getCurrentInstance();

                    const state = reactive({
                        type: new URLSearchParams(location.search).get('type'),
                        id: new URLSearchParams(location.search).get('id')
                    })

                    const form = reactive({
                        model: {
                            level: null,
                            name: '',
                            image: '',
                            commission_rules: {
                                commission_1: '0.00',
                                commission_2: '0.00',
                                commission_3: '0.00',
                            },
                            upgrade_type: 0,
                            upgrade_rules: {},
                        },
                        rules: {
                            level: [{ required: true, message: '请选择等级权重', trigger: 'blur' }],
                            name: [{ required: true, message: '请输入等级名称', trigger: 'blur' }],
                            commission_rules: {
                                commission: [{ required: true, message: '佣金比例', trigger: 'blur' }],
                            },
                            upgrade_rules: [
                                {
                                    validator: (rule, value, callback) => {
                                        if (isEmpty(value)) {
                                            callback(new Error('请填写升级条件'));
                                        } else {
                                            callback();
                                        }
                                    },
                                    trigger: 'blur',
                                },
                            ],
                            upgrade_rules_inner: {
                                rules: [{ required: true, message: '请输入', trigger: 'blur' }],
                                level: [{ required: true, message: '请选择分销商等级', trigger: 'blur' }],
                            },
                        },
                    })

                    function getDetail() {
                        Fast.api.ajax({
                            url: `shopro/commission/level/detail/id/${state.id}`,
                            type: 'GET',
                        }, function (ret, res) {
                            form.model = res.data;

                            if (!isObject(form.model.upgrade_rules)) form.model.upgrade_rules = {};

                            // level=1时 等级权重不可修改
                            if (form.model.level == 1) {
                                level.data = [
                                    {
                                        name: '一级',
                                        level: 1,
                                    },
                                ];

                                level.existLevel = [];
                                level.data.forEach((e) => {
                                    level.existLevel.push(e.level);
                                });
                            } else {
                                level.data = JSON.parse(JSON.stringify(defaultLevel));
                            }

                            let index = level.existLevel.findIndex((l) => l == form.model.level);
                            level.existLevel.splice(index, 1);
                            return false
                        }, function (ret, res) { })
                    }

                    const defaultLevel = [
                        {
                            name: '一级',
                            level: 1,
                        },
                        {
                            name: '二级',
                            level: 2,
                        },
                        {
                            name: '三级',
                            level: 3,
                        },
                        {
                            name: '四级',
                            level: 4,
                        },
                        {
                            name: '五级',
                            level: 5,
                        },
                        {
                            name: '六级',
                            level: 6,
                        },
                        {
                            name: '七级',
                            level: 7,
                        },
                        {
                            name: '八级',
                            level: 8,
                        },
                        {
                            name: '九级',
                            level: 9,
                        },
                        {
                            name: '十级',
                            level: 10,
                        },
                    ];
                    function onSelectLevel(l) {
                        if (!level.existLevel.includes(l)) {
                            form.model.level = l;
                            // 清空规则
                            form.model.upgrade_rules = {};
                        }
                    }

                    const level = reactive({
                        data: [],
                        select: [],
                        existLevel: [],
                    });
                    function getLevelSelect() {
                        Fast.api.ajax({
                            url: `shopro/commission/level/select`,
                            type: 'GET',
                        }, function (ret, res) {
                            level.select = res.data;
                            level.select.forEach((e) => {
                                level.existLevel.push(e.level);
                            });
                            return false
                        }, function (ret, res) { })
                    }

                    const upgradeCondition = {
                        user: {
                            total_consume: {
                                name: '用户消费金额',
                            },
                            child_user_count_1: {
                                name: '一级用户人数',
                            },
                            child_user_count_2: {
                                name: '二级用户人数',
                            },
                            child_user_count_all: {
                                name: '团队用户人数',
                            },
                        },
                        order_money: {
                            child_order_money_0: {
                                name: '自购分销订单总金额',
                            },
                            child_order_money_1: {
                                name: '一级分销订单金额',
                            },
                            child_order_money_2: {
                                name: '二级分销订单金额',
                            },
                            child_order_money_all: {
                                name: '团队分销订单金额',
                            },
                        },
                        order_count: {
                            child_order_count_0: {
                                name: '自购分销订单数量',
                            },
                            child_order_count_1: {
                                name: '一级分销订单数量',
                            },
                            child_order_count_2: {
                                name: '二级分销订单数量',
                            },
                            child_order_count_all: {
                                name: '团队分销订单数量',
                            },
                        },
                        agent_count: {
                            child_agent_count_1: {
                                name: '一级分销商人数',
                            },
                            child_agent_count_2: {
                                name: '二级分销商人数',
                            },
                            child_agent_count_all: {
                                name: '团队分销商人数',
                            },
                        },
                        agent_level: {
                            child_agent_level_all: {
                                name: '团队分销商等级统计',
                            },
                            child_agent_level_1: {
                                name: '一级分销商等级统计',
                            },
                        },
                    };
                    function onSelectUpgradeCondition(key) {
                        form.model.upgrade_rules[key] = '';
                        if (key == 'child_agent_level_all' || key == 'child_agent_level_1') {
                            form.model.upgrade_rules[key] = [
                                {
                                    level: '',
                                    count: '',
                                },
                            ];
                        }
                    }

                    function onAddUpgradeRules(key) {
                        form.model.upgrade_rules[key].push({
                            level: '',
                            count: '',
                        });
                    }
                    function onDeleteRules(key, index) {
                        if (key == 'child_agent_level_all' || key == 'child_agent_level_1') {
                            form.model.upgrade_rules[key].splice(index, 1);
                            if (form.model.upgrade_rules[key].length == 0) delete form.model.upgrade_rules[key];
                        } else {
                            delete form.model.upgrade_rules[key];
                        }
                    }

                    function initUnit(key) {
                        if (key.includes('child_user_count') || key.includes('child_agent_count')) {
                            return '人';
                        }
                        if (key.includes('total_consume') || key.includes('child_order_money')) {
                            return '元';
                        }
                        if (key.includes('child_order_count')) {
                            return '单';
                        }
                    }

                    function onConfirm() {
                        proxy.$refs['formRef'].validate((valid) => {
                            if (valid) {
                                Fast.api.ajax({
                                    url: state.type == 'add' ? 'shopro/commission/level/add' : `shopro/commission/level/edit/id/${state.id}`,
                                    type: 'POST',
                                    data: JSON.parse(JSON.stringify(form.model))
                                }, function (ret, res) {
                                    Fast.api.close()
                                }, function (ret, res) { })
                            }
                        });
                    }

                    onMounted(() => {
                        getLevelSelect()
                        if (state.type == 'add') {
                            level.data = JSON.parse(JSON.stringify(defaultLevel));
                        } else if (state.type == 'edit') {
                            getDetail()
                        }
                    })

                    return {
                        state,
                        form,
                        onSelectLevel,
                        defaultLevel,
                        level,
                        getLevelSelect,
                        upgradeCondition,
                        onSelectUpgradeCondition,
                        onAddUpgradeRules,
                        onDeleteRules,
                        initUnit,
                        onConfirm
                    }
                }
            }
            createApp('addEdit', addEdit);
        },
        select: () => {
            const { reactive, onMounted } = Vue
            const select = {
                setup() {
                    const state = reactive({
                        level: new URLSearchParams(location.search).get('level'),
                        data: [],
                    })

                    function getData() {
                        Fast.api.ajax({
                            url: 'shopro/commission/level/select',
                            type: 'GET',
                        }, function (ret, res) {
                            state.data = res.data
                            return false
                        }, function (ret, res) { })
                    }

                    function onSelect(level) {
                        state.level = level;
                    }

                    function onConfirm() {
                        Fast.api.close(state.level)
                    }

                    onMounted(() => {
                        getData()
                    })

                    return {
                        state,
                        getData,
                        onSelect,
                        onConfirm,
                    }
                }
            }
            createApp('select', select);
        },
    };
    return Controller;
});
