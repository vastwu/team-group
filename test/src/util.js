define(function () {
  return {
    getAdminToken: function () {
      return '2cc4d8f81bfdbdda3193cd57d7ce34fc';
    },
    random: function (min, max, integer) {
      if (arguments.length === 2) {
        integer = true;
      }
      var value = Math.random() * (max - min)  + min;
      if (integer) {
        value = parseInt(value);
      }
      return value;
    }
  }
})
