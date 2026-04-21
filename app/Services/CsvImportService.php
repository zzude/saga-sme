<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\JournalHeader;
use App\Models\JournalLine;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CsvImportService
{
    // ─────────────────────────────────────────────
    // COA IMPORT
    // ─────────────────────────────────────────────

    /**
     * Parse + validate COA rows — returns preview data, no DB write
     * CSV columns: code, name, type, parent_code, description, is_active, opening_balance
     */
    public function previewCoa(array $rows, int $companyId): array
    {
        $preview  = [];
        $errors   = [];
        $warnings = [];

        $validTypes = ['asset', 'liability', 'equity', 'revenue', 'expense'];

        foreach ($rows as $i => $row) {
            $line    = $i + 2;
            $code    = trim($row['code'] ?? '');
            $name    = trim($row['name'] ?? '');
            $type    = strtolower(trim($row['type'] ?? ''));
            $parentCode = trim($row['parent_code'] ?? '');
            $isActive   = strtolower(trim($row['is_active'] ?? 'true'));
            $openingBal = trim($row['opening_balance'] ?? '');

            $rowErrors = [];

            if (!$code) $rowErrors[] = 'code required';
            if (!$name) $rowErrors[] = 'name required';
            if (!in_array($type, $validTypes)) $rowErrors[] = "invalid type '{$type}'";

            // Duplicate check
            if ($code && Account::where('company_id', $companyId)->where('code', $code)->exists()) {
                $rowErrors[] = "code '{$code}' already exists";
            }

            // Parent check
            $parentId = null;
            $level    = 1;
            if ($parentCode) {
                $parent = Account::where('company_id', $companyId)->where('code', $parentCode)->first();
                if (!$parent) {
                    $rowErrors[] = "parent_code '{$parentCode}' not found";
                } else {
                    $parentId = $parent->id;
                    $parentLevel = is_object($parent->level) ? $parent->level->value : (int) $parent->level;
                    $level = $parentLevel + 1;
                    if ($level > 3) $rowErrors[] = "level would exceed 3 (parent is level {$parentLevel})";
                }
            }

            // Opening balance
            $obAmount = null;
            if ($openingBal !== '' && $openingBal !== null) {
                if (!is_numeric($openingBal)) {
                    $rowErrors[] = "opening_balance must be numeric";
                } else {
                    $obAmount = (float) $openingBal;
                }
            }

            if (!empty($rowErrors)) {
                foreach ($rowErrors as $err) {
                    $errors[] = "Row {$line}: {$err}";
                }
                $preview[] = [
                    'row'    => $line,
                    'code'   => $code,
                    'name'   => $name,
                    'type'   => $type,
                    'level'  => $level,
                    'parent' => $parentCode,
                    'ob'     => $obAmount,
                    'active' => $isActive !== 'false',
                    'status' => 'error',
                    'errors' => $rowErrors,
                ];
            } else {
                $preview[] = [
                    'row'       => $line,
                    'code'      => $code,
                    'name'      => $name,
                    'type'      => $type,
                    'level'     => $level,
                    'parent_id' => $parentId,
                    'parent'    => $parentCode,
                    'ob'        => $obAmount,
                    'active'    => $isActive !== 'false',
                    'status'    => 'ok',
                    'errors'    => [],
                ];
            }
        }

        $okCount  = count(array_filter($preview, fn($r) => $r['status'] === 'ok'));
        $errCount = count(array_filter($preview, fn($r) => $r['status'] === 'error'));

        return [
            'preview'   => $preview,
            'ok_count'  => $okCount,
            'err_count' => $errCount,
            'errors'    => $errors,
            'can_import'=> $errCount === 0,
        ];
    }

    /**
     * Commit COA import — only call after previewCoa() confirms can_import = true
     */
    public function commitCoa(array $previewRows, int $companyId, int $periodId): array
    {
        $imported = 0;
        $obEntries = [];

        DB::transaction(function () use ($previewRows, $companyId, &$imported, &$obEntries) {
            foreach ($previewRows as $row) {
                if ($row['status'] !== 'ok') continue;

                $account = Account::create([
                    'company_id'  => $companyId,
                    'code'        => $row['code'],
                    'name'        => $row['name'],
                    'type'        => $row['type'],
                    'level'       => $row['level'],
                    'parent_id'   => $row['parent_id'] ?? null,
                    'description' => '',
                    'is_active'   => $row['active'],
                ]);

                if ($row['ob'] !== null && $row['ob'] != 0) {
                    $obEntries[] = [
                        'account'  => $account,
                        'amount'   => $row['ob'],
                        'type'     => $row['type'],
                    ];
                }

                $imported++;
            }
        });

        // Generate opening balance journals
        $obJournals = 0;
        if (!empty($obEntries)) {
            $obJournals = $this->generateOpeningBalanceJournals($obEntries, $companyId, $periodId);
        }

        return [
            'imported'   => $imported,
            'ob_journals'=> $obJournals,
        ];
    }

    private function generateOpeningBalanceJournals(array $obEntries, int $companyId, int $periodId): int
    {
        $ref = 'OB-CSV-' . now()->format('YmdHis');

        $header = JournalHeader::create([
            'company_id'   => $companyId,
            'period_id'    => $periodId,
            'reference_no' => $ref,
            'date'         => now()->format('Y-m-d'),
            'status'       => 'posted',
            'source_type'  => 'opening_balance',
            'summary_text' => 'Opening Balance — CSV Import',
            'created_by'   => Auth::id(),
            'posted_by'    => Auth::id(),
            'posted_at'    => now(),
        ]);

        foreach ($obEntries as $entry) {
            $normalDebit = in_array($entry['type'], ['asset', 'expense']);
            JournalLine::create([
                'journal_header_id' => $header->id,
                'account_id'        => $entry['account']->id,
                'debit'             => $normalDebit ? $entry['amount'] : 0,
                'credit'            => $normalDebit ? 0 : $entry['amount'],
                'description'       => 'Opening balance — ' . $entry['account']->code,
            ]);
        }

        return 1;
    }

    // ─────────────────────────────────────────────
    // JOURNAL IMPORT
    // ─────────────────────────────────────────────

    /**
     * Parse + validate journal rows — preview only, no DB write
     * CSV columns: reference_no, date, summary_text, account_code, debit, credit, description
     */
    public function previewJournals(array $rows, int $companyId): array
    {
        $entries  = [];
        $errors   = [];
        $grouped  = [];

        // Group by reference_no
        foreach ($rows as $i => $row) {
            $ref = trim($row['reference_no'] ?? '');
            if (!$ref) {
                $errors[] = "Row " . ($i + 2) . ": reference_no required";
                continue;
            }
            $grouped[$ref][] = ['line' => $i + 2, 'data' => $row];
        }

        foreach ($grouped as $ref => $lines) {
            $entryErrors = [];
            $entryRows   = [];
            $totalDebit  = 0;
            $totalCredit = 0;

            // Duplicate check
            if (JournalHeader::where('reference_no', $ref)->where('company_id', $companyId)->exists()) {
                $entryErrors[] = "reference '{$ref}' already exists in GL";
            }

            $firstRow = $lines[0]['data'];
            $date     = trim($firstRow['date'] ?? '');
            $summary  = trim($firstRow['summary_text'] ?? $ref);

            // Date validation — strict YYYY-MM-DD
            if (!$date) {
                $entryErrors[] = 'date required';
            } elseif (!\DateTime::createFromFormat('Y-m-d', $date)) {
                $entryErrors[] = "date '{$date}' must be YYYY-MM-DD format";
            }

            foreach ($lines as $lineData) {
                $row    = $lineData['data'];
                $rowNum = $lineData['line'];

                $accCode = trim($row['account_code'] ?? '');
                $debit   = trim($row['debit'] ?? '');
                $credit  = trim($row['credit'] ?? '');

                $lineErrors = [];

                // XOR validation — only one side
                $hasDebit  = $debit !== '' && (float)$debit > 0;
                $hasCredit = $credit !== '' && (float)$credit > 0;

                if ($hasDebit && $hasCredit) {
                    $lineErrors[] = "Row {$rowNum}: cannot have both debit and credit";
                } elseif (!$hasDebit && !$hasCredit) {
                    $lineErrors[] = "Row {$rowNum}: debit or credit required";
                }

                // Account validation
                $account = null;
                if ($accCode) {
                    $account = Account::where('company_id', $companyId)
                        ->where('code', $accCode)
                        ->first();
                    if (!$account) {
                        $lineErrors[] = "Row {$rowNum}: account_code '{$accCode}' not found";
                    }
                } else {
                    $lineErrors[] = "Row {$rowNum}: account_code required";
                }

                $entryErrors = array_merge($entryErrors, $lineErrors);

                $dr = (float)($debit ?: 0);
                $cr = (float)($credit ?: 0);
                $totalDebit  += $dr;
                $totalCredit += $cr;

                $entryRows[] = [
                    'line'        => $rowNum,
                    'account_code'=> $accCode,
                    'account_id'  => $account?->id,
                    'account_name'=> $account?->name,
                    'debit'       => $dr,
                    'credit'      => $cr,
                    'description' => trim($row['description'] ?? ''),
                ];
            }

            // Balance check
            if (abs($totalDebit - $totalCredit) > 0.01) {
                $entryErrors[] = "Not balanced: DR " . number_format($totalDebit, 2) . " ≠ CR " . number_format($totalCredit, 2);
            }

            $entries[] = [
                'reference_no' => $ref,
                'date'         => $date,
                'summary_text' => $summary,
                'lines'        => $entryRows,
                'total_debit'  => $totalDebit,
                'total_credit' => $totalCredit,
                'status'       => empty($entryErrors) ? 'ok' : 'error',
                'errors'       => $entryErrors,
            ];

            foreach ($entryErrors as $err) {
                $errors[] = "{$ref}: {$err}";
            }
        }

        $okCount  = count(array_filter($entries, fn($e) => $e['status'] === 'ok'));
        $errCount = count(array_filter($entries, fn($e) => $e['status'] === 'error'));

        return [
            'entries'    => $entries,
            'ok_count'   => $okCount,
            'err_count'  => $errCount,
            'errors'     => $errors,
            'can_import' => $errCount === 0,
        ];
    }

    /**
     * Commit journal import — only call after previewJournals() confirms can_import = true
     */
    public function commitJournals(array $entries, int $companyId, int $periodId): array
    {
        $imported = 0;

        DB::transaction(function () use ($entries, $companyId, $periodId, &$imported) {
            foreach ($entries as $entry) {
                if ($entry['status'] !== 'ok') continue;

                $header = JournalHeader::create([
                    'company_id'   => $companyId,
                    'period_id'    => $periodId,
                    'reference_no' => $entry['reference_no'],
                    'date'         => $entry['date'],
                    'status'       => 'posted',
                    'source_type'  => 'manual',
                    'summary_text' => $entry['summary_text'],
                    'created_by'   => Auth::id(),
                    'posted_by'    => Auth::id(),
                    'posted_at'    => now(),
                ]);

                foreach ($entry['lines'] as $line) {
                    JournalLine::create([
                        'journal_header_id' => $header->id,
                        'account_id'        => $line['account_id'],
                        'debit'             => $line['debit'],
                        'credit'            => $line['credit'],
                        'description'       => $line['description'],
                    ]);
                }

                $imported++;
            }
        });

        return ['imported' => $imported];
    }

    // ─────────────────────────────────────────────
    // ERROR CSV EXPORT
    // ─────────────────────────────────────────────

    public function generateErrorCsv(array $errors): string
    {
        $csv = "row_or_reference,error_message\n";
        foreach ($errors as $error) {
            $csv .= '"' . str_replace('"', '""', $error) . '"' . "\n";
        }
        return $csv;
    }

    // ─────────────────────────────────────────────
    // CSV PARSER
    // ─────────────────────────────────────────────

    public function parseCsv(string $path): array
    {
        $rows = [];
        if (($handle = fopen($path, 'r')) === false) return [];

        $headers = null;
        while (($line = fgetcsv($handle)) !== false) {
            if (!$headers) {
                $headers = array_map('trim', $line);
                continue;
            }
            if (count($line) === count($headers)) {
                $rows[] = array_combine($headers, $line);
            }
        }
        fclose($handle);
        return $rows;
    }
}