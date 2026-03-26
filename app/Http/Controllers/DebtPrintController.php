<?php

namespace App\Http\Controllers;

use App\Models\Debt;
use App\Models\Empresa;
use Illuminate\Http\Request;

class DebtPrintController extends Controller
{
    public function paymentHistory(Request $request, string $empresaSlug, int $debtId)
    {
        $empresa = Empresa::where('slug', $empresaSlug)->firstOrFail();

        // Verificar que el usuario pertenece a esta empresa
        if (auth()->user()->empresa_id !== $empresa->id && !auth()->user()->hasRole('super_admin')) {
            abort(403);
        }

        $debt = Debt::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->with(['payments.bankAccount', 'payments.cashRegister', 'bankAccount', 'amortizationLines'])
            ->findOrFail($debtId);

        return view('debt.payment-history', compact('debt', 'empresa'));
    }
}
