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
      finishtime: Date.now() + 24 * 3600,
      summary: '简介简介',
      images: ['http://www.baidu.com/a.png', 'http://yyy.com/ba/b'],
      contact: '11111111111',
      commodities: [{
        name: '商品A',
        price: util.random(100, 500)
      }, {
        name: '商品B',
        price: util.random(100, 500)
      }],
      custom_fields: ['手机', '电话', '地址', '自定义']
    }
  }, {
    title: '获取单条拼团详情',
    url: '/group/42?participant_limit=1',
    method: 'GET',
    comments: [
      'query.participant_limit: 获取几个参与者的信息, 默认获取 4 个',
      'tips: 该接口可以通过传participant_limit=5，来检测是否有4+ 个用户，需求只展示4个，如果返回5个表示实际数量大于4，可以显示...样式'
    ]
  }, {
    title: '根据创建者查询拼团信息',
    url: '/group?creator=1234566&pagesize=1&pagenumber=1',
    method: 'GET',
    comments: [
      'query.pagesize: 单页数量，默认值返回所有, 不分页',
      'query.pagenumber: 分页页号，从1开始，查询页数越界时，result = []',
      'query.creator: 根据创建者uid查询',
    ]
  }, {
    title: '根据参与者查询拼团信息',
    url: '/group?participant=21&pagesize=1&pagenumber=1',
    method: 'GET',
    comments: [
      'query.pagesize: 单页数量，默认值返回所有, 不分页',
      'query.pagenumber: 分页页号，从1开始，查询页数越界时，result = []',
      'query.participant: 根据参与者uid查询',
      '相比常规的拼团数据，该接口会额外返回一些参与者订单相关信息',
      'group.jointime: 参与时间, 排序和分页会依照该时间倒叙排列',
      'group.custom_values: 参与者填写的字段值, 此处因涉及顺序问题，没有和value合并,展示时需要协同custom_fields',
      'group.commodities.*.count: 参与者选择的商品数量',
      'grou.total_price: 商品总价'
    ]
  }];

  module.exports = {
    title: 'group',
    interfaces: groupInterface,
    comments: [
      'group.status: 状态说明, 小于0的均为审核后台操作的非常规状态',
      '   0: 正常',
      '   1: 过期',
      '   -1: 违法内容',
      '   -2: 低俗色情',
      '   -3: 其他原因'
    ]
  }
})
