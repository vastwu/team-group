define(function (require, exports, module) {
  var Vue = require('vue');
  var VueResource = require('vue-resource');

  //var GroupApi = require('src/group')

  Vue.use(VueResource);

  var interfaces = [{
    title: '创建',
    url: '/group',
    method: 'POST',
    body: {
      title: '创建的标题' ,
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
    title: '获取单条详情',
    url: '/group/16',
    method: 'GET'
  }]


  var getSuccessHandler = function (api) {
    return function (response) {
      api.responseType = '';
      api.submiting = false;
      api.response = response.body;
    }
  }
  var getErrorHandler = function (api) {
    return function (response) {
      api.responseType = 'error';
      api.submiting = false;
      api.response = response.status + ' ' + response.statusText;
    }
  }

  var appRoot = new Vue({
    el: '#app',
    filters: {
      jsonStringify: function (json) {
        return JSON.stringify(json, null, 2);
      }
    },
    components: {
      //'group-api': GroupApi
    },
    mounted: function () {
      this.$el.style.display = 'block';
    },
    data: function () {
      return {
        apiDomain: 'http://laravel.app',
        interfaces: interfaces.map(function (api) {
          api.response = null;
          api.responseType = '';
          api.submiting = false;
          return api;
        })
      }
    },
    methods: {
      send: function (api) {
        var sendUrl = this.apiDomain + api.url;
        api.submiting = true;
        switch (api.method.toUpperCase()){
          case 'POST':
            this.$http.post(sendUrl, api.body).then(getSuccessHandler(api), getErrorHandler(api))
            break;
          default:
            //get
            this.$http.get(sendUrl).then(getSuccessHandler(api), getErrorHandler(api))
        }
      }
    }
  })
})
