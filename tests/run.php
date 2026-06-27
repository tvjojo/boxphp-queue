<?php
/**
 * Queue 包测试 - 修正版
 */
require_once __DIR__ . '/../vendor/autoload.php';

use BoxPHP\Queue\Queue\QueueInterface;

echo "=== BoxPHP Queue Package Tests ===\n\n";
$passed = 0;
$failed = 0;

// Test 1: QueueInterface
echo "1. Queue Interface\n";
try {
    $queue = new class implements QueueInterface {
        private array $queues = [];
        private array $processing = [];
        
        public function push(string $queue, mixed $data, int $delay = 0): bool {
            if (!isset($this->queues[$queue])) $this->queues[$queue] = [];
            $this->queues[$queue][] = [
                'id' => uniqid('job_', true),
                'data' => $data,
                'created_at' => time(),
                'delay' => $delay,
            ];
            return true;
        }
        
        public function pop(string $queue, int $timeout = 0): ?array {
            if (empty($this->queues[$queue])) return null;
            $job = array_shift($this->queues[$queue]);
            $this->processing[$queue][$job['id']] = $job;
            return $job;
        }
        
        public function peek(string $queue, int $start = 0, int $count = 10): array {
            return array_slice($this->queues[$queue] ?? [], $start, $count);
        }
        
        public function size(string $queue): int {
            return count($this->queues[$queue] ?? []);
        }
        
        public function delete(string $queue): bool {
            unset($this->queues[$queue]);
            return true;
        }
        
        public function ack(string $queue, string $id): bool {
            if (isset($this->processing[$queue][$id])) {
                unset($this->processing[$queue][$id]);
                return true;
            }
            return false;
        }
        
        public function reject(string $queue, string $id, bool $requeue = false): bool {
            if (isset($this->processing[$queue][$id])) {
                $job = $this->processing[$queue][$id];
                unset($this->processing[$queue][$id]);
                if ($requeue) {
                    array_unshift($this->queues[$queue], $job);
                }
                return true;
            }
            return false;
        }
    };
    
    // Push
    $result = $queue->push('emails', ['to' => 'user@example.com', 'subject' => 'Hello']);
    assert($result === true);
    assert($queue->size('emails') === 1);
    
    // Push more
    $queue->push('emails', ['to' => 'admin@example.com', 'subject' => 'Admin']);
    assert($queue->size('emails') === 2);
    
    // Peek
    $peeked = $queue->peek('emails', 0, 1);
    assert(count($peeked) === 1);
    assert($queue->size('emails') === 2); // Peek doesn't remove
    
    // Pop
    $job = $queue->pop('emails');
    assert($job !== null);
    assert($job['data']['to'] === 'user@example.com');
    assert($queue->size('emails') === 1);
    
    // Ack
    $result = $queue->ack('emails', $job['id']);
    assert($result === true);
    
    // Pop and reject with requeue
    $job2 = $queue->pop('emails');
    assert($job2 !== null);
    $result = $queue->reject('emails', $job2['id'], true);
    assert($result === true);
    assert($queue->size('emails') === 1); // Requeued
    
    // Pop empty queue
    $job3 = $queue->pop('empty_queue');
    assert($job3 === null);
    
    // Delete
    $result = $queue->delete('emails');
    assert($result === true);
    assert($queue->size('emails') === 0);
    
    echo "   ✓ Queue interface tests passed\n";
    $passed++;
} catch (\Throwable $e) {
    echo "   ✗ Failed: " . $e->getMessage() . "\n";
    $failed++;
}

echo "\n=== Results: {$passed} passed, {$failed} failed ===\n";
exit($failed > 0 ? 1 : 0);
