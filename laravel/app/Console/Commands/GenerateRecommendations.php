<?php

namespace App\Console\Commands;

use App\Services\AIEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Background async job — dispatched on login so ML processing
 * does not block real-time operations (SDD AI sequence Fig 3.13).
 */
class GenerateRecommendations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(private int $memberId) {}

    public function handle(AIEngine $ai): void
    {
        $ai->generateRecommendation($this->memberId);
    }
}
