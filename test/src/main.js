define(function (require, exports, module) {
  var Vue = require('vue');
  var VueResource = require('vue-resource');

  var GroupApi = require('src/api/group');
  var UserApi = require('src/api/user');
  var ParticipantApi = require('src/api/participant');
  var util = require('src/util');

  Vue.use(VueResource);


  var EXPAND_ALL = true;
  var ADMIN_TOKEN = util.getAdminToken();

  var interfaces = [];
  interfaces.push(ParticipantApi);
  interfaces.push(UserApi);
  interfaces.push(GroupApi);

  Vue.http.interceptors.push( function(request, next){
    var url = request.url;
    if (url.indexOf('token') === -1) {
      request.url = url + (url.indexOf('?') > -1 ? '&' : '?') + 'token=' + this.userToken;
    }
    next();
  })

  var getSuccessHandler = function (api) {
    return function (response) {
      api.responseType = '';
      api.submiting = false;
      if (response.error !== 0) {
        api.responseType = 'warn';
      }
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
      var vm = interfaces.map(function (item) {
        item.expand = EXPAND_ALL;
        item.interfaces = item.interfaces.map(function (api) {
          api.expand = EXPAND_ALL;
          api.response = null;
          api.responseType = '';
          api.submiting = false;
          return api;
        });
        return item;
      });
      return {
        images: [],
        //apiDomain: 'http://laravel.app',
        apiDomain: 'http://pintuan.yuanwei100.com',
        userToken: 'b6393e276771e416dc299467dce7fa0f',
        interfaces: vm,
        qr: {
          src: 'about:blank',
          input: '/qr?gid=282&path=' + encodeURIComponent('pages/cardDetail/cardDetail?id=282') + '&width=500&height=1000'
        },
        comments: [
          '接口尽可能遵循restful规范设计, 以url path表示资源',
          '不信任接口输入的uid，所有涉及用户自身的行为，均以传递的token为准，例如创建拼团不需要传创建者uid，但必须带上token，创建者为token对应的用户',
          '除user接口外，其余接口必须以 GET 参数形式传递token',
          '标记为 admin 的接口，为后台需要用的或协助调试的，需要特殊的token，只有该token可以调用这些接口',
          '每个接口示例的query参数可以直接修改，post参数需要在代码里修改，每类接口对应 src/ 目录下的文件',
          '目前服务端开启了Access-Control-Allow-Origin 方便调试，正式上线后会关闭'
        ]
      }
    },
    methods: {
      toggleAll: function (expand) {
        this.interfaces.forEach(function (item) {
          item.expand = expand;
          item.interfaces.forEach(function (api) {
            api.expand = expand;
          })
        })
      },
      toggle: function (item) {
        item.expand = !item.expand;
      },
      send: function (api) {
        if (typeof api.send === 'function') {
          return api.send(this.apDomain);
        }
        var sendUrl = this.apiDomain + api.url;
        api.submiting = true;
        if (api.admin) {
          sendUrl = sendUrl + (sendUrl.indexOf('?') > -1 ? '&' : '?') + 'token=' + ADMIN_TOKEN;
        }
        switch (api.method.toUpperCase()){
          case 'POST':
            this.$http.post(sendUrl, api.body).then(getSuccessHandler(api), getErrorHandler(api))
            break;
          case 'PUT':
            this.$http.put(sendUrl, api.body).then(getSuccessHandler(api), getErrorHandler(api))
            break;
          case 'DELETE':
            this.$http.delete(sendUrl).then(getSuccessHandler(api), getErrorHandler(api))
            break;
          default:
            //get
            this.$http.get(sendUrl).then(getSuccessHandler(api), getErrorHandler(api))
        }
      }
    }
  })

  window.addEventListener('message',function(e){
    var images = e.data;
    appRoot.images = images;
  }, false);


})
