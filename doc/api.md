# 基于restful规则

API_ROOT: '/'

## /api/group

### post 创建一个拼团
`[POST] /api/group`

#### request-body
fields | required | comment
---|---|---
title           | 标题
limit_amount    | 开团金额
limit_users     | 开团人数
finishtime      | 结束时间
summary         | 简介
images<thumb, origin>          | 图片地址
contact         | 联系方式

#### response-body
```js
{
    error: 0,
    msg: null,
    result: {}
}
```

### delete

### update

### get

### get