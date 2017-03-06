define(function (require, exports, module) {
  var Vue = require('vue');
  var VueResource = require('vue-resource');
  var ElementUI = require('ELEMENT');
  var config = require('src/config');
  var userComponent = require('src/userComponent');

  Vue.use(VueResource);
  Vue.use(ElementUI);

  var timeFilter = function (date) {
    var d = new Date(date * 1);
    var fillZero = function (n) { return n > 9 ? n : '0' + n};
    return [d.getFullYear(), d.getMonth() + 1, d.getDate()].map(fillZero).join('-') +
                      ' ' +
                      [d.getHours(), d.getMinutes(), d.getSeconds()].map(fillZero).join(':');
  }
  var statusFilter = (function () {
    var STATUS_TEXT = {};
    config.auditStatusOptions.forEach(function (item) {
      STATUS_TEXT[item.value]  = item.label;
    });
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


  Vue.http.interceptors.push( function(request, next){
    var url = request.url;
    request.url = url + (url.indexOf('?') > -1 ? '&' : '?') + 'token=' + config.rootToken;
    next();
  })

  var appRoot = new Vue({
    el: '#app',
    filters: {
      jsonStringify: function (json) {
        return JSON.stringify(json, null, 2);
      }
    },
    components: {
      'user-component': userComponent
    },
    mounted: function () {
      this.$el.style.display = 'block';
      var url = '/auditor';
      this.$http.get(url).then(function (response) {
        if (response.body.error === 0) {
          this.auditor = response.body.result;
          this.updateGroupList()
        } else {
          this.auditor = false;
        }
      })
    },
    data: function () {
      return {
        auditorFormSubmiLoading: false,
        // 新增审核员dialog
        dialogNewAuditorVisible: false,
        newAuditorForm: {
          account: '',
          nickname: '',
          password: '',
          password_confirm: ''
        },
        newAuditorFormRules:{
          account: [{required: true, message: '必须输入账号名', trigger: 'blur'}],
          password: [{required: true, message: '必须输入密码', trigger: 'blur'}],
          password_confirm: [{required: true, message: '必须重复确认密码', trigger: 'blur'}]
        },
        // 所有审核员
        auditors: [],
        currentTab: 'groups',
        // 当前登陆者
        auditor: null,
        groupDetailLoading: false,
        groupListLoading: false,
        groupDetail: null,
        showGroupDetail: false,
        groups: [],
        auditStatusValue: 0,
        auditStatusOptions: config.auditStatusOptions,
        listPagesize: 10,
        listPagenumber: 1,
        listPageTotal: 0,
        searchForm: {
          orderBy: null,
          desc: null,
          search_type: 'title',
          keyword: '',
          enableDateRange: false,
          // 默认最近一天的
          createtime_range: [new Date(new Date().setDate(new Date().getDate() - 1)), new Date()],
          pickerOptions: {
            shortcuts: [{
              text: '最近一周',
              onClick(picker) {
                const end = new Date();
                const start = new Date();
                start.setDate(start.getDate() - 7);
                picker.$emit('pick', [start, end]);
              }
            }, {
              text: '最近一个月',
              onClick(picker) {
                const end = new Date();
                const start = new Date();
                start.setMonth(start.getMonth() - 1);
                picker.$emit('pick', [start, end]);
              }
            }]
          }
        }
      }
    },
    methods: {
      logout: function () {
        this.$http.delete('/auditor/99').then(function () {
          location.reload();
        });
      },
      resetPassword: function (id, name) {
        var self = this;
        this.$prompt('请输入【' + name + '】的新密码', '提示', {
          confirmButtonText: '确定',
          cancelButtonText: '取消',
        }).then(function (arg) {
          var value = arg.value;
          self.$http.put('/auditor/' + id, {password: value}).then(function (response) {
            if (response.body.error !== 0) {
              return self.$message.error(response.body.reason);
            }
            self.$message.success('修改成功');
          })
        }).catch(function(){});
      },
      newAuditorSubmit: function () {
        var form = this.newAuditorForm;
        if (!form.nickname) {
          form.nickname = form.account;
        }
        if (form.password !== form.password_confirm) {
          return this.$message.error('两次密码不一致');
        }
        var body = {
          account: form.account,
          password: form.password,
          nickname: form.nickname
        };
        this.auditorFormSubmiLoading = true;
        this.$http.post('/auditor', body).then(function (response) {
          var body = response.body;
          if (body.error !== 0) {
            this.auditorFormSubmiLoading = false;
            return this.$message.error(body.reason);
          }
          this.dialogNewAuditorVisible = false;
          this.$message.success('创建成功!');
          this.updateAuditors();
        });
      },
      addNewAuditor: function () {
        this.dialogNewAuditorVisible = true;
      },
      updateAuditors: function () {
        this.$http.get('/auditor/query').then(function (response) {
          var body = response.body;
          if (body.error === 0) {
            this.auditors = body.result;
          }
        });
      },
      tabChange:function (tab) {
        if (tab.name === "auditor") {
          this.updateAuditors();
        }
      },
      listPagenumberChange: function (current) {
        this.listPagenumber = current;
        this.updateGroupList();
      },
      listSortChange: function (param) {
        this.searchForm.orderBy = param.prop;
        this.searchForm.desc = param.order === 'descending';
        this.listPagenumber = 1;
        this.updateGroupList();
      },
      getSearchQuery: function (toString) {
        var form = this.searchForm;
        var query = {};
        query[form.search_type] = form.keyword;
        if (form.enableDateRange) {
          query.createtime_start = form.createtime_range[0].getTime();
          query.createtime_end = form.createtime_range[1].getTime();
        }
        if (form.orderBy) {
          query.order = form.orderBy;
        }
        if (form.desc) {
          query.desc = 1;
        }
        query.pagesize = this.listPagesize;
        query.pagenumber = this.listPagenumber;
        if (toString) {
          var queryArr = [];
          for (var key in query) {
            queryArr.push(key + '=' + query[key]);
          }
          query = queryArr.join('&')
        }
        return query;
      },
      onSearch: function () {
        this.listPagenumber = 1;
        this.updateGroupList();
      },
      getGroupListRowClass: function(row, index) {
        return 'group-status-' + row.status;
      },
      auditStatusChange: function (targetStatus) {
        var findLabel = '';
        var label = this.auditStatusOptions.some(function (item) {
          if (item.value === targetStatus) {
            findLabel = item.label;
            return true;
          }
        })
        var self = this;
        this.$confirm('将该团置为 [' + findLabel + '] 状态, 是否继续?', '提示', {
          confirmButtonText: '确定',
          cancelButtonText: '取消',
          type: 'warning'
        }).then(function() {
          var url = '/group/' + self.groupDetail.id;
          self.$http.put(url, { 'status': targetStatus}).then(function (response) {
            self.groupDetail.status = targetStatus;
            self.$message({
              type: 'success',
              message: '操作成功!'
            });
            self.showGroupDetail = false;
          })
        }).catch(function () {});
      },
      groupClick: function (row, event, colum) {
        var url = '/group/' + row.id;
        this.groupDetailLoading = true;
        this.showGroupDetail = true;
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
          item.participant.forEach(function (item) {
            item.createtime = timeFilter(item.createtime);
            item.commodity_sum = item.commodities.reduce(function (sum, item, index) {
              return sum + item.count * item.price;
            }, 0);
          })
          this.groupDetail = item;
          this.groupDetailLoading = false;
        })
      },
      updateGroupList: function () {
        var query = this.getSearchQuery(true);
        var url = '/group?type=3&' + query;
        this.groupListLoading = true;
        this.$http.get(url, query).then(function (response) {
          var result = response.body.result;
          this.groupListLoading = false;
          this.listPageTotal = result.total;
          this.groups = result.groups.map(function (item) {
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
