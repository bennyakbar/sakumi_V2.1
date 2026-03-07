<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SakumiLedgerCheck extends Command
{
    protected $signature = 'sakumi:ledger-check';

    protected $description = 'Verify financial ledger integrity in SAKUMI';

    public function handle()
    {
        $this->info('');
        $this->info('SAKUMI LEDGER CHECK');
        $this->info('---------------------');

        // total invoices
        $invoiceCount = DB::table('invoices')->count();

        // total receipts
        $receiptCount = DB::table('receipts')->count();

        // total transactions
        $transactionCount = DB::table('transactions')->count();

        // total settlements
        $settlementCount = DB::table('settlements')->count();

        $this->line("Invoices      : $invoiceCount");
        $this->line("Receipts      : $receiptCount");
        $this->line("Transactions  : $transactionCount");
        $this->line("Settlements   : $settlementCount");

        $this->info('');

        // basic integrity checks
        $receiptWithoutInvoice = DB::table('receipts')
            ->whereNotNull('invoice_id')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('invoices')
                  ->whereColumn('invoices.id', 'receipts.invoice_id');
            })
            ->count();

        $receiptWithoutTransaction = DB::table('receipts')
            ->whereNotNull('transaction_id')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('transactions')
                  ->whereColumn('transactions.id', 'receipts.transaction_id');
            })
            ->count();

        $receiptWithoutSettlement = DB::table('receipts')
            ->whereNotNull('settlement_id')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('settlements')
                  ->whereColumn('settlements.id', 'receipts.settlement_id');
            })
            ->count();

        $this->line("Receipts without invoice     : $receiptWithoutInvoice");
        $this->line("Receipts without transaction : $receiptWithoutTransaction");
        $this->line("Receipts without settlement  : $receiptWithoutSettlement");

        $this->info('');

        if (
            $receiptWithoutInvoice == 0 &&
            $receiptWithoutTransaction == 0 &&
            $receiptWithoutSettlement == 0
        ) {
            $this->info("LEDGER STATUS: OK");
        } else {
            $this->warn("LEDGER STATUS: PROBLEMS DETECTED");
        }

        return 0;
    }
}
