# boxphp/queue

BoxPHP 队列包 - 基于 Redis 的队列实现

## 安装

```bash
composer require boxphp/queue
```

## 前置要求

- boxphp/redis

## 使用

### 基础使用

```php
use BoxPHP\Queue\Queue\RedisQueue;
use BoxPHP\Redis\Redis\RedisConnection;

$redis = new RedisConnection(['host' => '127.0.0.1', 'port' => 6379]);
$redis->connect();

$queue = new RedisQueue($redis, 'myapp');

// 推送任务
$queue->push(['job' => 'SendEmail', 'to' => 'user@example.com']);
$queue->push(['job' => 'ProcessImage', 'id' => 123]);

// 弹出任务
$job = $queue->pop();
if ($job) {
    // 处理任务
    processJob($job);
}

// 查看队列大小
echo $queue->size(); // 2
```

### 任务优先级

```php
// 高优先级任务（推送到队列头部）
$queue->pushHigh(['job' => 'UrgentTask']);

// 普通任务
$queue->push(['job' => 'NormalTask']);

// 低优先级任务（推送到队列尾部）
$queue->pushLow(['job' => 'BackgroundTask']);
```

### 延迟任务

```php
// 30秒后执行
$queue->delay(30, ['job' => 'DelayedTask']);

// 5分钟后执行
$queue->delay(300, ['job' => 'LaterTask']);
```

### 任务预览

```php
// 查看但不移除任务
$jobs = $queue->peek(0, 10); // 查看前10个任务
```

### 队列工作者

```php
use BoxPHP\Queue\Queue\RedisQueue;

$queue = new RedisQueue($redis, 'myapp');

while (true) {
    $job = $queue->pop();
    
    if ($job === null) {
        sleep(1); // 队列为空，等待1秒
        continue;
    }
    
    try {
        // 处理任务
        processJob($job);
        
        // 处理成功，确认
        $queue->ack();
    } catch (\Exception $e) {
        // 处理失败，重新入队
        $queue->nack($job);
    }
}
```

## 依赖

- PHP >= 8.1
- boxphp/core ^1.0
- boxphp/redis ^1.0
