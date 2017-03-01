define(function () {
  return {
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
