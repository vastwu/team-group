define(function (require, exports, module) {

  var uploadInterface = [{
    title: '上传图片',
    url: '/upload',
    method: 'POST',
    comments: [
    ],
    send: function (domain) {

    }
  }];

  module.exports = {
    title: 'upload',
    interfaces: uploadInterface
  }
})
