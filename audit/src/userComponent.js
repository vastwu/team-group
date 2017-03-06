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
          console.log(response);
        })
      }
    }
  }
  module.exports = userComponent;
})
