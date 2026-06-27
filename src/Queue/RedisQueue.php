<?php
/**
 * RedisQueue Redis 队列
 */
namespace BoxPHP\Queue\Queue;

use BoxPHP\Redis\Redis\RedisInterface;

class RedisQueue implements QueueInterface
{
    protected RedisInterface $redis;
    protected string $prefix;

    public function __construct(RedisInterface $redis, string $prefix = 'queue:')
    {
        $this->redis = $redis;
        $this->prefix = $prefix;
    }

    public function push(string $queue, mixed $data, int $delay = 0): bool
    {
        $payload = json_encode([
            'id' => uniqid('job_', true),
            'data' => $data,
            'created_at' => time(),
            'delay' => $delay,
        ]);

        if ($delay > 0) {
            $delayKey = $this->prefix . $queue . ':delayed';
            $this->redis->zAdd($delayKey, time() + $delay, $payload);
            return true;
        }

        $this->redis->rPush($this->prefix . $queue, $payload);
        return true;
    }

    public function pop(string $queue, int $timeout = 0): ?array
    {
        $queueKey = $this->prefix . $queue;
        $processingKey = $queueKey . ':processing';

        // 先处理延迟队列
        $this->processDelayed($queue);

        // 阻塞等待
        if ($timeout > 0) {
            // Redis BLPOP 不可用（Redis 3.0），改用轮询
            $start = microtime(true);
            while (microtime(true) - $start < $timeout) {
                $item = $this->redis->lPop($queueKey);
                if ($item !== false) {
                    $job = json_decode($item, true);
                    if ($job) {
                        $job['queue'] = $queue;
                        return $job;
                    }
                }
                usleep(10000); // 10ms
            }
            return null;
        }

        $item = $this->redis->lPop($queueKey);
        if ($item === false) {
            return null;
        }

        $job = json_decode($item, true);
        if ($job) {
            $job['queue'] = $queue;
        }
        return $job;
    }

    public function peek(string $queue, int $start = 0, int $count = 10): array
    {
        $items = $this->redis->lRange($this->prefix . $queue, $start, $start + $count - 1);
        $jobs = [];
        foreach ($items as $item) {
            $job = json_decode($item, true);
            if ($job) {
                $jobs[] = $job;
            }
        }
        return $jobs;
    }

    public function size(string $queue): int
    {
        return $this->redis->lLen($this->prefix . $queue);
    }

    public function delete(string $queue): bool
    {
        return $this->redis->del($this->prefix . $queue) > 0;
    }

    public function ack(string $queue, string $id): bool
    {
        return true; // 简单队列不需要 ACK
    }

    public function reject(string $queue, string $id, bool $requeue = false): bool
    {
        return true;
    }

    protected function processDelayed(string $queue): void
    {
        $delayKey = $this->prefix . $queue . ':delayed';
        $queueKey = $this->prefix . $queue;
        $now = time();

        $items = $this->redis->lRange($delayKey, 0, -1);
        foreach ($items as $item) {
            $job = json_decode($item, true);
            if ($job && ($job['created_at'] + ($job['delay'] ?? 0)) <= $now) {
                $this->redis->lPop($delayKey);
                $this->redis->rPush($queueKey, $item);
            }
        }
    }
}
