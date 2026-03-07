<?php

namespace App\Listeners;

use App\Events\TransactionCreated;
use App\Models\InvoiceItem;

class UpdateInvoiceStatus
{
    public function handle(TransactionCreated $event): void
    {
        $transaction = $event->transaction;

        if ($transaction->type !== 'income') {
            return;
        }

        // Find invoice items linked to obligations that were just paid by this transaction
        $paidObligationIds = $transaction->items()
            ->whereNotNull('fee_type_id')
            ->get()
            ->flatMap(function ($item) {
                return \App\Models\StudentObligation::where('transaction_item_id', $item->id)
                    ->where('is_paid', true)
                    ->pluck('id');
            });

        if ($paidObligationIds->isEmpty()) {
            return;
        }

        // Find all invoices containing these obligations and recalculate
        $invoiceIds = InvoiceItem::whereIn('student_obligation_id', $paidObligationIds)
            ->pluck('invoice_id')
            ->unique();

        foreach ($invoiceIds as $invoiceId) {
            $invoice = \App\Models\Invoice::find($invoiceId);
            if ($invoice && $invoice->status !== 'cancelled') {
                // Recalculate based on obligation payment state
                $total = $invoice->items()->count();
                $paid = $invoice->items()
                    ->whereHas('studentObligation', fn ($q) => $q->where('is_paid', true))
                    ->count();

                $paidAmount = $invoice->items()
                    ->whereHas('studentObligation', fn ($q) => $q->where('is_paid', true))
                    ->sum('amount');

                $invoice->paid_amount = $paidAmount;

                if ($paid === 0) {
                    $invoice->status = 'unpaid';
                } elseif ($paid < $total) {
                    $invoice->status = 'partially_paid';
                } else {
                    $invoice->status = 'paid';
                }

                $invoice->save();
            }
        }
    }
}
