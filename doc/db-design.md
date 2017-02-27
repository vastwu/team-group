### Group
团

fields | comment
---|---
id              | 自增id
title           | 标题
limit_amount    | 开团金额
limit_users     | 开团人数
finishtime      | 结束时间
summary         | 简介
images<thumb, origin>          | 图片地址
contact         | 联系方式
commodities<name, price>     | 商品
fields<label, fieldname>  | 需要买家填写的字段名



### Participant
参团者

fields | comment
---|---
id          | 自增id
uid         | 用户微信id
name        | 买家姓名
mobile      | 买家手机号
address     | 买家地址




# Question
* 是否需要单独开发用户体系？之前如何使用的微信用户体系？
* 是否需要单独开发图片上传接口？
* 目前接口的成功/失败返回值规则是什么？希望能够尽量实现统一
