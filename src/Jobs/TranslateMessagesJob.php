<?php

namespace Amir\TranslationService\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Amir\TranslationService\Services\TranslationService;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Bus\Batchable;

class TranslateMessagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    protected $batch;
    protected $sourceLang;
    protected $targetLang;

    /**
     * Create a new job instance.
     *
     * @param array $batch
     * @param string $targetLang
     */
    public function __construct($batch, $sourceLang, $targetLang)
    {
        $this->batch = $batch;
        $this->sourceLang = $sourceLang;
        $this->targetLang = $targetLang;
    }

    /**
     * Execute the job.
     *
     * @param TranslationService $translationService
     * @return void
     */
    public function handle(TranslationService $translationService)
    {
        Log::info('Starting translation for batch: ' . json_encode($this->batch));

        try {
            // Translate the batch using the service method
            $translatedBatch = $translationService->translateBatch($this->batch, $this->sourceLang, $this->targetLang);
            // Append the translated batch to the file
            $this->appendToFile($translatedBatch);
        } catch (Exception $e) {
            Log::error('Translation failed for batch: ' . $e->getMessage());
        }
    }

    /**
     * Appends the translated batch to the target language file.
     *
     * @param array $translatedBatch
     */
    private function appendToFile($translatedBatch)
    {
        $filePath = base_path('resources/lang/' . $this->targetLang . '/messages.php');

        $currentContent = [];
        if (file_exists($filePath)) {
            try {
                $currentContent = include($filePath);
                if (!is_array($currentContent)) {
                    throw new Exception("Invalid content in the language file.");
                }
            } catch (Exception $e) {
                Log::error("Error loading existing content: " . $e->getMessage());
                return;
            }
        }

        $updatedContent = array_merge($currentContent, $translatedBatch);

        if (!is_array($updatedContent)) {
            Log::error("Updated content is not an array.");
            return;
        }

        $str = "<?php return " . var_export($updatedContent, true) . ";";
        if (file_put_contents($filePath, $str) === false) {
            Log::error("Failed to write to file: " . $filePath);
        }
    }
}
