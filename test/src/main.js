define(function (require, exports, module) {
  var Vue = require('vue');
  var VueResource = require('vue-resource');

  var GroupApi = require('src/group');
  var UserApi = require('src/user');
  var ParticipantApi = require('src/participant');

  Vue.use(VueResource);


  var EXPAND_ALL = true;

  var interfaces = [];
  interfaces.push(ParticipantApi);
  interfaces.push(UserApi);
  interfaces.push(GroupApi);

  Vue.http.interceptors.push( function(request, next){
    var url = request.url;
    request.url = url + (url.indexOf('?') > -1 ? '&' : '?') + 'token=' + this.userToken;
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
        apiDomain: 'http://laravel.app',
        userToken: '8af033b658d7a66666370620b7c3b80b',
        interfaces: vm
      }
    },
    methods: {
      uploadFileChange: function () {

      },
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
        switch (api.method.toUpperCase()){
          case 'POST':
            this.$http.post(sendUrl, api.body).then(getSuccessHandler(api), getErrorHandler(api))
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
})
