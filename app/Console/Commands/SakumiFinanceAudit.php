<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SakumiFinanceAudit extends Command
{
    protected $signature = 'sakumi:finance-audit';
    protected $description = 'Audit financial integrity of SAKUMI';

    public function handle()
    {
        $this->info('');
        $this->info('SAKUMI FINANCE AUDIT');
        $this->info('----------------------');

        // 1️⃣ invoice tanpa receipt
        $invoiceWithoutReceipt = DB::table('invoices')
            ->leftJoin('receipts', 'receipts.invoice_id', '=', 'invoices.id')
            ->whereNull('receipts.id')
            ->count();

        $this->line("Invoices without receipts : $invoiceWithoutReceipt");


        // 2️⃣ receipt tanpa invoice
        $receiptWithoutInvoice = DB::table('receipts')
            ->leftJoin('invoices', 'receipts.invoice_id', '=', 'invoices.id')
            ->whereNull('invoices.id')
            ->whereNotNull('receipts.invoice_id')
            ->count();

        $this->line("Receipts without invoice  : $receiptWithoutInvoice");


        // 3️⃣ receipt tanpa transaction
        $receiptWithoutTransaction = DB::table('receipts')
            ->leftJoin('transactions', 'receipts.transaction_id', '=', 'transactions.id')
            ->whereNull('transactions.id')
            ->whereNotNull('receipts.transaction_id')
            ->count();

        $this->line("Receipts without transaction : $receiptWithoutTransaction");


        // 4️⃣ receipt dengan settlement hilang
        $receiptMissingSettlement = DB::table('receipts')
            ->leftJoin('settlements', 'receipts.settlement_id', '=', 'settlements.id')
            ->whereNotNull('receipts.settlement_id')
            ->whereNull('settlements.id')
            ->count();

        $this->line("Receipts missing settlement : $receiptMissingSettlement");


        // 5️⃣ settlement tanpa receipt
        $settlementWithoutReceipt = DB::table('settlements')
            ->leftJoin('receipts', 'receipts.settlement_id', '=', 'settlements.id')
            ->whereNull('receipts.id')
            ->count();

        $this->line("Settlement without receipt  : $settlementWithoutReceipt");


        // 6️⃣ duplicate verification code
        $duplicateCodes = DB::table('receipts')
            ->select('verification_code')
            ->groupBy('verification_code')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        $this->line("Duplicate verification code : $duplicateCodes");


        $this->info('');

        if (
            $invoiceWithoutReceipt == 0 &&
            $receiptWithoutInvoice == 0 &&
            $receiptWithoutTransaction == 0 &&
            $receiptMissingSettlement == 0 &&
            $settlementWithoutReceipt == 0 &&
            $duplicateCodes == 0
        ) {
            $this->info('STATUS: OK');
        } else {
            $this->warn('STATUS: ISSUES FOUND');
        }

        return 0;
    }
}
