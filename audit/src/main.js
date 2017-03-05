define(function (require, exports, module) {
  var Vue = require('vue');
  var VueResource = require('vue-resource');
  var ElementUI = require('ELEMENT');

  Vue.use(VueResource);
  Vue.use(ElementUI);


  var timeFilter = function (date) {
    var d = new Date(date);
    var fillZero = function (n) { return n > 9 ? n : '0' + n};
    return [d.getFullYear(), d.getMonth() + 1, d.getDate()].map(fillZero).join('-') +
                      ' ' +
                      [d.getHours(), d.getMinutes(), d.getSeconds()].map(fillZero).join(':');
  }
  var statusFilter = (function () {
    var STATUS_TEXT = {
      '0': '正常',
      '1': '过期',
      '-1': '违法内容',
      '-2': '低俗色情',
      '-3': '其他原因'
    }
    return function (s) {
      return STATUS_TEXT[s] || '未知状态';
    }
  })();

  var getRelativeTime = (function () {
    var MINUTE = 60 * 1000;
    var HOUR = MINUTE * 60;
    var DAY = HOUR * 24;;
    return function (s) {
      var result = "";
      if (s > DAY) {
        result += Math.floor(s / DAY) + ' 天';
        s = s % DAY;
      }
      if (s > HOUR) {
        result += Math.floor(s / HOUR) + ' 小时';
        s = s % HOUR;
      }
      if (s > MINUTE) {
        result += Math.floor(s / MINUTE) + ' 分钟';
        s = s % MINUTE;
      }
      return result;
    }
  })();

  var appRoot = new Vue({
    el: '#app',
    filters: {
      jsonStringify: function (json) {
        return JSON.stringify(json, null, 2);
      }
    },
    components: {
    },
    mounted: function () {
      this.$el.style.display = 'block';
      this.updateGroupList()
    },
    data: function () {
      return {
        groupDetail: null,
        showGroupDetail: false,
        groups: [],
        apiDomain: 'http://laravel.app',
        //apiDomain: 'http://pintuan.yuanwei100.com',
        rootToken: '2cc4d8f81bfdbdda3193cd57d7ce34fc'
      }
    },
    methods: {
      groupClick: function (row, event, colum) {
        var url = this.apiDomain + '/group/' + row.id + '?token=' + this.rootToken;
        this.$http.get(url).then(function (response) {
          var item = response.body.result;
          item.statusLabel = statusFilter(item.status);
          item.remaining = getRelativeTime(item.finishtime - Date.now());
          item.createtime = timeFilter(item.createtime);
          item.finishtime = timeFilter(item.finishtime);
          item.users = item.total_users + ' / ' + item.limit_users;
          item.amount = item.total_amount + ' / ' + item.limit_amount;
          item.per_users = (item.total_users / item.limit_users).toFixed(2);
          item.per_amount = (item.total_amount / item.limit_amount).toFixed(2);
          this.groupDetail = item;
          this.showGroupDetail = true;
        })
      },
      updateGroupList: function () {
        var url = this.apiDomain + '/group?type=3&pagesize=10&pagenumber=1&token=' + this.rootToken;
        this.$http.get(url).then(function (response) {
          console.log(response);
          this.groups = response.body.result.map(function (item) {
            item.createtime = timeFilter(item.createtime);
            item.statusLabel = statusFilter(item.status);
            item.users = item.total_users + ' / ' + item.limit_users;
            item.amount = item.total_amount + ' / ' + item.limit_amount;
            return item;
          });
        })
      }
    }
  })
})
