<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OpenWaService;

class WhatsAppDaemonCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:daemon {action=status : Action: status, start, restart, stop, logs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage the WhatsApp Baileys microservice daemon process (start, status, restart, stop, logs)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = (string) ($this->argument('action') ?: 'status');
        $this->info("Executing WhatsApp Daemon Action: {$action}...");

        $res = OpenWaService::runDaemonCommand($action);

        $this->line($res['output'] ?? '');

        return ($res['success'] ?? false) ? Command::SUCCESS : Command::FAILURE;
    }
}
