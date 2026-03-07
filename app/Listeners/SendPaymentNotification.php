<?php

namespace App\Listeners;

use App\Events\TransactionCreated;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPaymentNotification implements ShouldQueue
{
    public function __construct(
        private WhatsAppService $whatsAppService,
    ) {}

    public function handle(TransactionCreated $event): void
    {
        $transaction = $event->transaction;

        if ($transaction->type !== 'income' || !$transaction->student) {
            return;
        }

        $feeTypes = $transaction->items->map(fn ($item) => $item->feeType->name)->implode(', ');

        $this->whatsAppService->sendPaymentConfirmation(
            $transaction->student,
            $feeTypes,
            (float) $transaction->total_amount,
        );
    }
}
