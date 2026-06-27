<?php
/**
 * QueueInterface 队列接口
 */
namespace BoxPHP\Queue\Queue;

interface QueueInterface
{
    public function push(string $queue, mixed $data, int $delay = 0): bool;
    public function pop(string $queue, int $timeout = 0): ?array;
    public function peek(string $queue, int $start = 0, int $count = 10): array;
    public function size(string $queue): int;
    public function delete(string $queue): bool;
    public function ack(string $queue, string $id): bool;
    public function reject(string $queue, string $id, bool $requeue = false): bool;
}
