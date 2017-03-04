define(function (require, exports, module) {

  var userInterface = [{
    title: '创建/更新用户',
    url: '/user',
    method: 'POST',
    comments: [
      '如果是新用户则response.result.isnew="1" ',
      '用户进入后，先读取本地存储的token，如果token存在则使用 /user/{token} 获取用户信息',
      'token不存在或者无效，则再[post] /user 的方式生成新用户',
      '如果仅仅是session过期，则上一步的方式不会重复生成用户(openid唯一)，会根据code重新构建token并返回旧的用户数据',
      '同时该接口还有更新用混成和头像的功能'
    ],
    body: {
      code: 'user_code',
      name: '微信用户的昵称',
      avatar: 'http://avatar(用户头像地址).png',
    }
  }, {
    title: '获取用户',
    url: '/user/8af033b658d7a66666370620b7c3b80b',
    method: 'GET',
    comments: [
      '/user 后跟的是token',
      '如果token对应的用户不存在，则会返回 {error: 0, result: null}',
      '为了省事可以以上面的post接口代替此接口,只是效率会差一些，多一次与微信服务器的通信（code->openid）'
    ]
  }, {
    title: '删除用户',
    url: '/user/22',
    admin: true,
    method: 'DELETE',
    comments: [
      '/user 后跟的是 uid',
      '该接口仅用于辅助开发和测试，切勿用于生产环境'
    ]
  }];

  module.exports = {
    title: 'user',
    interfaces: userInterface
  }
})
