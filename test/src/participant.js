define(function (require, exports, module) {
  var util = require('src/util');

  var participantInterface = [{
    title: '参加拼团(创建订单)',
    url: '/group/41/participant',
    comments: [
      'commodities 为所选择商品的数量，请务必确保同group.commodities的顺序和数组长度一致，如果未选择某件商品，请传0'
    ],
    method: 'POST',
    body: {
      uid: 21,
      custom_values: ['手机value', '电话value', '地址value', '自定义value'],
      commodities: [util.random(4, 10), util.random(1, 6)]
    }
  }, {
    title: '获取订单详情',
    url: '/group/41/participant/16',
    method: 'GET'
  }, {
    title: '查询单个拼团下的所有参与者(订单)',
    url: '/group/41/participant',
    method: 'GET',
    comments: [
      'pagesize: 单页数量，默认值返回所有, 不分页',
      'pagenumber: 分页页号，从1开始，查询页数越界时，result = []'
    ]
  }, {
    title: '删除拼团订单',
    url: '/group/41/participant/16',
    method: 'DELETE',
    comments: [
      '仅用于方便调试(单个用户只能参与某个拼团一次,groupid + uid 唯一)，目前需求中不涉及退出'
    ]
  }];

  module.exports = {
    title: 'participant',
    interfaces: participantInterface
  }
})
