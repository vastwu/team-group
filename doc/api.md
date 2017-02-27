# 基于restful规则

API_ROOT: '/'

## /api/group

### 创建拼团
`[POST] /api/group`

#### request-body
```js
{
  // 拼团标题
  title: "拼团标题",            
  // 拼团简介
  summary: "拼团简介拼团简介拼团简介",
  // 开团金额
  limit_amount: 10000,          
  // 开团人数
  limit_users: 30,              
  // 结束时间戳
  finishtime: 1488205354492,    
  // 图片
  images: [
    "http://xxxx.png",
    "http://yyyy.png"
  ],
  // 商品
  commodities: [{
    name: "商品A",
    price: 398
  }, {
    name: "商品B",
    price: 398
  }],
  // 自定义填写字段名
  // 如果无需买家填写，该字段传非数组即可(null/undefined)
  custom_fields: [
    "姓名",
    "手机号",
    "地址",
    "学号",
    "自定义xxx"
  }]
}
```

#### response-body
```js
{
    error: 0,
    msg: null,
    result: {}
}
```

### 获取拼团详情
`[GET] /api/group/group_id`

#### response-body
```js
{
  // 拼团标题
  title: "拼团标题",            
  // 拼团简介
  summary: "拼团简介拼团简介拼团简介",
  // 开团金额
  limit_amount: 10000,          
  // 开团人数
  limit_users: 30,              
  // 结束时间戳
  finishtime: 1488205354492,    
  // 图片
  images: [
    "http://xxxx.png",
    "http://yyyy.png"
  ],
  // 商品
  commodities: [{
    name: "商品A",
    price: 398,
    total: 23
  }, {
    name: "商品B",
    price: 398
    total: 43
  }],
  // 自定义填写字段名
  // 如果无需买家填写，该字段传非数组即可(null/undefined)
  custom_fields: [
    "姓名",
    "手机号",
    "地址",
    "学号",
    "自定义xxx"
  }],
  // 参与者
  participants: [{
    // 参与者头像
    pic: "http://xxx.png",
    // 参与者昵称
    nickname: "ooxx"
  }],
  total_price: 1002
}
```


### 提前结束拼团

### 拼团搜索

### 参加拼团


