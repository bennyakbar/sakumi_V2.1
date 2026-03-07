<?php

namespace App\Services;

use App\Models\BankReconciliationLine;
use App\Models\BankReconciliationLog;
use App\Models\BankReconciliationSession;
use App\Models\Transaction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class BankReconciliationService
{
    public function createSession(array $data, int $userId): BankReconciliationSession
    {
        $session = BankReconciliationSession::query()->create([
            ...$data,
            'status' => 'draft',
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $this->log($session, 'create_session', [
            'bank_account_name' => $session->bank_account_name,
            'period' => sprintf('%04d-%02d', $session->period_year, $session->period_month),
        ], $userId);

        return $session;
    }

    public function importCsv(BankReconciliationSession $session, UploadedFile $file, int $userId): int
    {
        $this->ensureSessionEditable($session);

        $handle = fopen($file->getRealPath(), 'rb');
        if ($handle === false) {
            return 0;
        }

        $header = fgetcsv($handle) ?: [];
        $count = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($header)) {
                continue;
            }

            $item = array_combine($header, $row);
            if (!$item) {
                continue;
            }

            $amount = (float) ($item['amount'] ?? 0);
            $type = strtolower((string) ($item['type'] ?? 'debit'));
            if (!in_array($type, ['debit', 'credit'], true)) {
                $type = $amount < 0 ? 'credit' : 'debit';
            }
            $amount = abs($amount);

            if ($amount <= 0 || empty($item['date'])) {
                continue;
            }

            BankReconciliationLine::create([
                'bank_reconciliation_session_id' => $session->id,
                'line_date' => $item['date'],
                'description' => $item['description'] ?? null,
                'reference' => $item['reference'] ?? null,
                'amount' => $amount,
                'type' => $type,
                'match_status' => 'unmatched',
            ]);
            $count++;
        }

        fclose($handle);

        if ($count > 0 && $session->status === 'draft') {
            $session->update([
                'status' => 'in_review',
                'updated_by' => $userId,
            ]);
        }

        $this->log($session, 'import_csv', ['rows' => $count], $userId);

        return $count;
    }

    public function matchLine(BankReconciliationLine $line, int $transactionId, int $userId): void
    {
        $this->ensureSessionEditable($line->session);

        $transaction = Transaction::query()->findOrFail($transactionId);
        if ($transaction->status !== 'completed') {
            throw new \RuntimeException('Only completed transaction can be matched.');
        }
        if ($transaction->unit_id !== $line->session->unit_id) {
            throw new \RuntimeException('Transaction unit mismatch.');
        }

        $line->update([
            'matched_transaction_id' => $transaction->id,
            'match_status' => 'matched',
            'matched_by' => $userId,
            'matched_at' => now(),
        ]);

        $this->log($line->session, 'match_line', [
            'line_id' => $line->id,
            'transaction_id' => $transaction->id,
        ], $userId);
    }

    public function unmatchLine(BankReconciliationLine $line, int $userId): void
    {
        $this->ensureSessionEditable($line->session);

        $line->update([
            'matched_transaction_id' => null,
            'match_status' => 'unmatched',
            'matched_by' => null,
            'matched_at' => null,
        ]);

        $this->log($line->session, 'unmatch_line', ['line_id' => $line->id], $userId);
    }

    public function closeSession(BankReconciliationSession $session, int $userId): BankReconciliationSession
    {
        $this->ensureSessionEditable($session);

        $unmatchedCount = $session->lines()->where('match_status', 'unmatched')->count();
        if ($unmatchedCount > 0) {
            throw new \RuntimeException('Cannot close session while unmatched lines still exist.');
        }

        return DB::transaction(function () use ($session, $userId) {
            $session->update([
                'status' => 'closed',
                'closed_at' => now(),
                'updated_by' => $userId,
            ]);

            $this->log($session, 'close_session', [
                'unmatched_count' => $session->lines()->where('match_status', 'unmatched')->count(),
            ], $userId);

            return $session->fresh();
        });
    }

    private function ensureSessionEditable(BankReconciliationSession $session): void
    {
        if ($session->status === 'closed') {
            throw new \RuntimeException('Closed reconciliation session is locked.');
        }
    }

    private function log(BankReconciliationSession $session, string $action, array $payload, int $actorId): void
    {
        BankReconciliationLog::create([
            'bank_reconciliation_session_id' => $session->id,
            'action' => $action,
            'payload' => $payload,
            'actor_id' => $actorId,
        ]);
    }
}
