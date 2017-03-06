define(function (require, exports, module) {
  module.exports = {
    apiDomain: 'http://laravel.app',
    //apiDomain: 'http://pintuan.yuanwei100.com',
    rootToken: '2cc4d8f81bfdbdda3193cd57d7ce34fc',
    auditStatusOptions: [{
      'label': '正常',
      'value': 0
    }, {
      'label': '过期',
      'value': 1
    }, {
      'label': '违法内容',
      'value': -1
    }, {
      'label': '低俗色情',
      'value': -2
    }, {
      'label': '其他原因',
      'value': -3
    }]
  };
})
