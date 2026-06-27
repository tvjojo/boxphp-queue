<?php
/**
 * BoxPHP Framework
 *
 * Copyright 2026 BoxPHP
 * By tvjojo, asterhuang, 黄波涛; 5viv.com
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

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
