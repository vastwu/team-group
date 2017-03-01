define(function (require, exports, module) {
  var util = require('src/util');

  var groupInterface = [{
    title: '创建拼团',
    url: '/group',
    method: 'POST',
    body: {
      title: '创建的标题' ,
      userid: '1234566',
      limit_amount: 20003,
      limit_users: 30,
      finishtime: Date.now(),
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
    url: '/group/16',
    method: 'GET'
  }, {
    title: '查询一组拼团信息',
    url: '/group?creator=1234566',
    method: 'GET',
    comments: [
      'pagesize: 单页数量，默认值返回所有, 不分页',
      'pagenumber: 分页页号，从1开始，查询页数越界时，result = []',
      'creator: 根据创建者uid查询',
    ]

  }];

  module.exports = {
    title: 'group',
    interfaces: groupInterface
  }
})
