# build with laravel

线上地址：`http://pintuan.yuanwei100.com`

# 更新说明

## 2017-03-23
### 新增与变更
* `[GET] /group/{拼团id}/participant/{订单id}` 接口新增参团者姓名和头像

## 2017-03-22
### 新增与变更
* 商品价格移除了整型限制，数据库字段调整为`DECIMAL(14,2)`
* 可以使用 `[GET] /group/{拼团id}` 接口中的 `total_users` 展示拼团信息中的总人数

### bug 修复
* [x] 取消拼团时的状态判定bug
* [x] `[GET] group/68/participant` 接口中错误的将 uid 当做 id, 另外为了安全起见，移除了该接口中用户敏感信息（`token`, `session_key`, `openid`），仅保留`name`, `uid`和`avatar`

## 2017-03-21
### 新增与变更
* 批量查询某个团下的订单可以使用`/group/{团id}/participant`, 支持分页
* 按id查询单个订单可以使用`/group/{团id}/participant/{订单id}`
* participant类接口的commodities字段，均包含了商品名称和单价
* participant类接口(包括单条或批量)新增`total_price`字段，表示该订单的合计金额
* 提前结束拼团可使用`[PUT] /group/41`，body里带上`finishtime=1`，（接口文档名称：更新拼团状态(提前结束拼团)）

### bug 修复
1. [x] 解决拼团总金额和订单的数额对不上的问题
1. [x] 拼团结束时，如果达到人数或金额要求，则`status=2`, 表示为截团
1. [x] 拼团结束时，如果未达到人数或金额要求，则`status=1`, 表示为已过期
1. [x] 手动结束拼团与达到结束时间逻辑一致, 即将finishtime更新为当前时间

