<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Models\MailingContact;
use App\Models\MailingGroup;
use Illuminate\Console\Command;

class GroupMailingContactsCommand extends Command
{
    protected $signature = 'mailing:reagrupar
                            {--empresa=  : ID de empresa específica (omitir = todas)}
                            {--force     : Reagrupa TODOS los contactos, no solo los sin grupo}';

    protected $description = 'Agrupa automáticamente contactos de mailing en grupos de 1.500. Seguro en producción.';

    public function handle(): int
    {
        $empresaId = $this->option('empresa');
        $force     = $this->option('force');

        $query = Empresa::query();
        if ($empresaId) {
            $query->where('id', $empresaId);
        }

        $empresas = $query->get();

        if ($empresas->isEmpty()) {
            $this->warn('No se encontraron empresas.');
            return Command::SUCCESS;
        }

        foreach ($empresas as $empresa) {
            $this->procesarEmpresa($empresa, (bool) $force);
        }

        $this->newLine();
        $this->info('Agrupación completada.');

        return Command::SUCCESS;
    }

    private function procesarEmpresa(Empresa $empresa, bool $force): void
    {
        $query = MailingContact::where('empresa_id', $empresa->id);
        if (! $force) {
            $query->whereNull('mailing_group_id');
        }

        $total = $query->count();

        if ($total === 0) {
            $this->line("  <fg=gray>[{$empresa->name}]</> Sin contactos pendientes.");
            return;
        }

        $this->line("  <fg=cyan>[{$empresa->name}]</> {$total} contacto(s) a procesar...");

        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%');
        $bar->start();

        $groupState = [];

        // chunkById usa WHERE id > ? en lugar de OFFSET, así no salta registros
        // cuando los actualizamos dentro del mismo chunk
        $query->chunkById(500, function ($contacts) use ($empresa, &$groupState, $bar) {
            $byGroup = [];

            foreach ($contacts as $contact) {
                $groupId             = MailingGroup::assignGroupBatch($empresa->id, $groupState);
                $byGroup[$groupId][] = $contact->id;
            }

            // Un UPDATE por grupo, no por contacto
            foreach ($byGroup as $groupId => $ids) {
                MailingContact::withoutGlobalScopes()->whereIn('id', $ids)->update(['mailing_group_id' => $groupId]);
            }

            $bar->advance(count($contacts));
        });

        $bar->finish();
        $this->newLine();

        $grupos = MailingGroup::where('empresa_id', $empresa->id)->count();
        $this->line("  <fg=green>✓</> Distribuidos en {$grupos} grupo(s) de máx. " . MailingGroup::CAPACITY . " contactos.");
    }
}
