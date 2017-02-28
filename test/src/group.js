define(function (require, exports, module) {

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
      images: ['http://xxx', 'http://yyy'],
      contact: '11111111111',
      commodities: [{
        name: '商品A',
        price: 1234
      }, {
        name: '商品B',
        price: 493
      }],
      custom_fields: ['手机', '电话', '地址', '自定义']
    }
  }, {
    title: '获取单条拼团详情',
    url: '/group/16',
    method: 'GET'
  }];

  module.exports = {
    title: 'group',
    interfaces: groupInterface
  }
})
