define(function (require, exports, module) {
  var util = require('src/util');

  var groupInterface = [{
    title: '创建拼团',
    url: '/group',
    method: 'POST',
    body: {
      title: '创建的标题' ,
      limit_amount: 20003,
      limit_users: 30,
      finishtime: Date.now() + 24 * 3600 * 1000,
      summary: '简介简介现货，秒发🌸自然乐园芦荟胶。请认准牌子再比价！十大用途: 1🍃可当补水面霜和面膜 ，2🍃妆前',
      images: ['http://www.baidu.com/a.png', 'http://yyy.com/ba/b'],
      contact: '11111111111',
      commodities: [{
        name: '商品A',
        price: util.random(100, 500, 2)
      }, {
        name: '商品B',
        price: util.random(100, 500, 2)
      }],
      custom_fields: ['手机', '电话', '地址', '自定义']
    }
  }, {
    title: '获取单条拼团详情',
    url: '/group/42?participant_limit=1',
    method: 'GET',
    comments: [
      'query.participant_limit: 获取几个参与者的信息, 默认获取 4 个',
      'response.current_user_participant: 当前用户在该团中的订单状态，如果未参加则为null',
      'tips: 该接口可以通过传participant_limit=5，来检测是否有4+ 个用户，需求只展示4个，如果返回5个表示实际数量大于4，可以显示...样式'
    ]
  }, {
    title: '查询拼团信息(by 用户创建)',
    url: '/group?type=1&pagesize=1&pagenumber=1',
    method: 'GET',
    comments: [
      'query.pagesize: 单页数量，默认值返回所有, 不分页',
      'query.pagenumber: 分页页号，从1开始，查询页数越界时，result = []',
    ]
  }, {
    title: '查询拼团信息(by 用户参与)',
    url: '/group?type=2&pagesize=1&pagenumber=1',
    method: 'GET',
    comments: [
      'query.pagesize: 单页数量，默认值返回所有, 不分页',
      'query.pagenumber: 分页页号，从1开始，查询页数越界时，result = []',
      '相比常规的拼团数据，该接口会额外返回一些参与者订单相关信息',
      'group.jointime: 参与时间, 排序和分页会依照该时间倒叙排列',
      'group.custom_values: 参与者填写的字段值, 此处因涉及顺序问题，没有和value合并,展示时需要协同custom_fields',
      'group.commodities.*.count: 参与者选择的商品数量',
      'grou.total_price: 商品总价'
    ]
  }, {
    title: '查询拼团信息(by admin)',
    url: '/group?type=1&pagesize=1&pagenumber=1',
    admin: true,
    method: 'GET',
    comments: [
      'TODO',
      '该接口用于支持任意形式的查询，仅用于管理后台',
      'query.type: 除支持默认的1，2外，还支持3，任意query方式',
      'query.pagesize: 单页数量，默认值返回所有, 不分页',
      'query.pagenumber: 分页页号，从1开始，查询页数越界时，result = []',
    ]
  }, {
    title: '更新拼团状态(提前结束拼团)',
    url: '/group/41',
    method: 'PUT',
    body: {
      'finishtime': 1
    },
    comments: [
      '提前结束拼团，如果满足拼团条件，则会变更为已截团'
    ]
  }, {
    title: '更新拼团状态(分享记次)',
    url: '/group/41',
    method: 'PUT',
    body: {
      'share': 1
    },
    comments: [
      '团分享次数加1， share的值没有意义，每次调用后均会加1，'
    ]
  }, {
    title: '更新拼团状态(审核)',
    url: '/group/41',
    admin: true,
    method: 'PUT',
    body: {
      'status': -1
    },
    comments: [
      '更新拼团状态，用于审核后台'
    ]
  }, {
    title: '删除拼团',
    url: '/group/41',
    admin: true,
    method: 'DELETE',
    comments: [
      '该行为会彻底删除数据库中的数据',
      '仅用于方便调试，目前需求中不涉及删除',
    ]
  }];

  module.exports = {
    title: 'group',
    interfaces: groupInterface,
    comments: [
      'group.status: 状态说明, 小于0的均为审核后台操作的非常规状态',
      '   0: 正常',
      '   1: 已结束',
      '   2: 已截团',
      '   -1: 违法内容',
      '   -2: 低俗色情',
      '   -3: 其他原因'
    ]
  }
})
