<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class ObligationGenerated
{
    use Dispatchable;

    public function __construct(
        public int $month,
        public int $year,
        public int $count,
    ) {}
}
