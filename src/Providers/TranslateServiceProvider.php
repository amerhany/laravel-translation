<?php

namespace Amir\TranslationService\Providers;

use Amir\TranslationService\Console\Commands\InstallTranslationServiceCommand;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;
use Amir\TranslationService\Services\TranslationService;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TranslateServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register the main class to use with the facade
        $this->app->singleton('translation-service', function () {
            return new TranslationService;
        });
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallTranslationServiceCommand::class,
            ]);

            // Get the console input and output interfaces
            $this->app['events']->listen('command.start', function (InputInterface $input, OutputInterface $output) {
                $io = new SymfonyStyle($input, $output);
                $this->checkAndSetupJobTables($io);
            });
        }
    }

    protected function checkAndSetupJobTables(SymfonyStyle $io)
    {
        try {
            $jobsExists = Schema::hasTable('jobs');
            $jobBatchesExists = Schema::hasTable('job_batches');

            if (!$jobsExists || !$jobBatchesExists) {
                $this->alertUserAndMigrate($io);
            } else {
                $io->success('The jobs and job_batches tables already exist.');
            }
        } catch (\Exception $e) {
            $io->error('Database error: ' . $e->getMessage());
        }
    }

    protected function alertUserAndMigrate(SymfonyStyle $io)
    {
        $io->warning('The jobs or job_batches tables do not exist.');
        $io->text('You should install and migrate the Job and Batch tables.');

        // Ask the user to run the migrations
        if ($io->confirm('Do you want to create the jobs and job_batches tables and run the migration?', true)) {
            Artisan::call('queue:table');        // Create jobs table migration
            Artisan::call('queue:batches-table'); // Create job_batches table migration
            Artisan::call('migrate');            // Run all pending migrations

            // Check again if the tables now exist
            if (Schema::hasTable('jobs') && Schema::hasTable('job_batches')) {
                $io->success('The jobs and job_batches tables have been created and migrated successfully.');
                $this->askToRunQueue($io); // Ask user to run the queue
            } else {
                $io->error('Warning: The jobs and job_batches tables were not created successfully.');
            }
        } else {
            $io->error('Warning: The jobs and job_batches tables are required for the package to work properly.');
        }
    }

    protected function askToRunQueue(SymfonyStyle $io)
    {
        $io->text('You should run the queue worker for the package to function correctly.');
        if ($io->confirm('Do you want to start the queue worker now?', true)) {
            Artisan::call('queue:work'); // Start the queue worker
            $io->success('The queue worker has been started.');
        } else {
            $io->warning('Please remember to start the queue worker later.');
        }
    }
}
