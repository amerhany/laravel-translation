<?php

namespace Amir\TranslationService\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

class InstallTranslationServiceCommand extends Command
{
    protected $signature = 'translation-service:install';
    protected $description = 'Install the Translation Service package and ensure job tables are set up.';

    public function handle()
    {
        $this->info('Starting installation of Translation Service package...');

        // Check if jobs and job_batches tables exist
        if (!Schema::hasTable('jobs') || !Schema::hasTable('job_batches')) {
            $this->warn('The jobs or job_batches tables do not exist.');

            if ($this->confirm('Do you want to create the jobs and job_batches tables and run the migration?', true)) {
                // Run the migration commands for the tables
                Artisan::call('queue:table');
                Artisan::call('queue:batches-table');
                Artisan::call('migrate');

                $this->info('The jobs and job_batches tables have been created and migrated successfully.');
            } else {
                $this->warn('The jobs and job_batches tables are required for the package to work.');
            }
        } else {
            $this->info('The jobs and job_batches tables already exist.');
        }

        $this->info('Translation Service package installed successfully.');
    }
}
