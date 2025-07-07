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
    protected $baseLangPath; 
    protected $fileName; 

    /**
     * Create a new job instance.
     *
     * @param array $batch
     * @param string $sourceLang
     * @param string $targetLang
     * @param string|null $baseLangPath 
     * @param string|null $fileName 
     */
    public function __construct($batch, $sourceLang, $targetLang, $baseLangPath = null, $fileName = null)
    {
        $this->batch = $batch;
        $this->sourceLang = $sourceLang;
        $this->targetLang = $targetLang;
        $this->baseLangPath = $baseLangPath;
        $this->fileName = $fileName ?? 'messages.php';
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
            $translatedBatch = $translationService->translateBatch($this->batch, $this->sourceLang, $this->targetLang);

            if ($this->baseLangPath) {
                $this->appendToFileCustomPath($this->baseLangPath, $translatedBatch);
            } else {
                $this->appendToFile($translatedBatch);
            }
        } catch (Exception $e) {
            Log::error('Translation failed for batch: ' . $e->getMessage());
        }
    }

    /**
     * Append to default language path
     */
    private function appendToFile($translatedBatch)
    {
        $filePath = base_path('lang/' . $this->targetLang . DIRECTORY_SEPARATOR . $this->fileName);
        $this->writeToFile($filePath, $translatedBatch);
    }

    /**
     * Append to custom base path + targetLang folder
     */
    private function appendToFileCustomPath(string $basePath, array $translatedBatch)
    {
        $filePath = rtrim($basePath, DIRECTORY_SEPARATOR) 
            . DIRECTORY_SEPARATOR . $this->targetLang 
            . DIRECTORY_SEPARATOR . $this->fileName;

        $this->writeToFile($filePath, $translatedBatch);
    }

    /**
     * Common method to write translations to file
     */
    private function writeToFile(string $filePath, array $translatedBatch)
    {
        $dirPath = dirname($filePath);

        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0755, true);
        }

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
