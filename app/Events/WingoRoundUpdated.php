<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow; 
use Illuminate\Queue\SerializesModels;

final class WingoRoundUpdated implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public string $gameType,
        public array $payload
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("wingo.{$this->gameType}");
    }

    public function broadcastAs(): string
    {
        return 'WingoRoundUpdated';
    }
}
