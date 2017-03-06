define(function (require, exports, module) {
  var config = require('src/config');

  var userComponent = {
    template: '#login-template',
    data: function () {
      return {
        loginVisible: true,
        form: {
          account: '',
          password: ''
        }
      }
    },
    methods: {
      onSubmit: function () {
        var url = config.apiDomain + '/auditor?account=' + this.form.account + '&password=' + this.form.password;
        this.$http.get(url).then(function (response) {
          var body = response.body;
          if (body.error === 0) {
            this.$message.success('登陆成功');
            setTimeout(function () {
              location.reload();
            }, 3000)
          } else {
            this.$message.error(body.reason);
          }
        })
      }
    }
  }
  module.exports = userComponent;
})
