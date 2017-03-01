define(function (require, exports, module) {
  var util = require('src/util');

  var participantInterface = [{
    title: '参加拼团',
    url: '/group/41/participant',
    method: 'POST',
    body: {
      uid: 21,
      custom_values: ['手机value', '电话value', '地址value', '自定义value'],
      commodities: [util.random(4, 10), util.random(1, 6)]
    }
  }, {
    title: '获取单参加详情',
    url: '/group/41/participant/16',
    method: 'GET'
  }, {
    title: '查询单个拼团下的所有参与者',
    url: '/group/41/participant',
    method: 'GET',
    comments: [
      'pagesize: 单页数量，默认值返回所有, 不分页',
      'pagenumber: 分页页号，从1开始，查询页数越界时，result = []'
    ]

  }];

  module.exports = {
    title: 'participant',
    interfaces: participantInterface
  }
})
