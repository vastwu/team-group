define(function (require, exports, module) {

  var userInterface = [{
    title: '创建用户',
    url: '/user',
    method: 'POST',
    comments: [
      '如果是新用户则 isnew="1" '
    ],
    body: {
      code: '021HhoR80AwGoK1UJDO80FwYQ80HhoRN',
      name: 'wx_user',
      avatar: 'http://avatar.png',
    }
  }];

  module.exports = {
    title: 'user',
    interfaces: userInterface
  }
})
