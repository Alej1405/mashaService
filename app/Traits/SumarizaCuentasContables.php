<?php

namespace App\Traits;

use App\Models\JournalEntryLine;

trait SumarizaCuentasContables
{
    // Single JOIN query instead of N+1 (1 query per account_plan).
    protected function sumCuentas(int $empresaId, array $codes, string $type, string $field, $desde, $hasta): float
    {
        $query = JournalEntryLine::query()
            ->join('account_plans', 'journal_entry_lines.account_plan_id', '=', 'account_plans.id')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('account_plans.empresa_id', $empresaId)
            ->where('account_plans.type', $type)
            ->where('account_plans.accepts_movements', true)
            ->where('journal_entries.empresa_id', $empresaId)
            ->where('journal_entries.status', 'confirmado')
            ->where('journal_entries.esta_cuadrado', true)
            ->whereBetween('journal_entries.fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->where(function ($q) use ($codes) {
                foreach ($codes as $c) {
                    $q->orWhere('account_plans.code', 'like', $c . '%');
                }
            });

        return (float) $query->sum("journal_entry_lines.{$field}");
    }
}
