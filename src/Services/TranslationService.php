<?php

namespace Amir\TranslationService\Services;

use Amir\TranslationService\Jobs\TranslateMessagesJob;
use Amir\TranslationService\Helpers\TranslationHelper;
use Illuminate\Support\Facades\Bus;

class TranslationService
{
    /**
     * Translates a given language file by batching the data and dispatching jobs.
     *
     * @param string $filePath - The path to the language file to be translated.
     * @param string $targetLang - The target language code.
     * @return \Illuminate\Http\JsonResponse
     */
    public static function translateFile($filePath, $sourceLang, $targetLang)
    {
        if (!self::isLanguageSupported($sourceLang)) {
            throw new \Exception('The source language ' . $sourceLang . ' is not supported.');
        }

        if (!self::isLanguageSupported($targetLang)) {
            throw new \Exception('The target language ' . $targetLang . ' is not supported.');
        }

        // Load the existing language file
        if (file_exists($filePath)) {
            $fullData = include($filePath);

            // Batch the data, assuming batches of 100 items
            $batches = array_chunk($fullData, 200, true); // true to preserve keys

            // Prepare an array to hold the translation jobs
            $jobs = [];

            foreach ($batches as $batch) {
                // Add each batch job to the jobs array
                $jobs[] = new TranslateMessagesJob($batch, $sourceLang, $targetLang);
            }

            // Dispatch all jobs as a single batch
            $batch = Bus::batch($jobs)->dispatch();

            return [
                'code' => '200',
                'message' => 'Translation jobs dispatched',
                'batch_id' => $batch->id,
                'start_time' => now()->timestamp, // Save start time as a timestamp
            ];
        } else {
            return response()->json([
                'code' => '404',
                'message' => 'File not found'
            ]);
        }
    }

    public static function translateText($text, $sourceLang, $targetLang)
    {
        if (!self::isLanguageSupported($sourceLang)) {
            throw new \Exception('The source language ' . $sourceLang . ' is not supported.');
        }

        if (!self::isLanguageSupported($targetLang)) {
            throw new \Exception('The target language ' . $targetLang . ' is not supported.');
        }
        // Use the helper to call the Google Translate API
        return TranslationHelper::translateAPI($text, $sourceLang, $targetLang);
    }

    /**
     * Dispatches a translation job for each batch.
     *
     * @param array $batch - A chunk of the language file data.
     * @param string $targetLang - The target language code.
     */
    private static function dispatchTranslationJob($batch, $sourceLang, $targetLang)
    {
        // Dispatch the job for this batch
        TranslateMessagesJob::dispatch($batch, $sourceLang, $targetLang);
    }

    /**
     * Handles translating a batch of strings using the Google Translate API.
     *
     * @param array $batch - The array of strings to be translated.
     * @param string $lang - The target language code.
     * @return array
     */
    public static function translateBatch($batch, $sourceLang, $targetLang)
    {
        // Preprocessing the batch of array (from array to string + filter)
        $processedBatch = self::preprocessingBatch($batch);

        // Translate the filtered string
        $translatedValue = self::translateValue($processedBatch, $sourceLang, $targetLang);


        // Split the translated string back into an array
        return self::splitStringToArray($batch, $translatedValue);
    }

    /**
     * Translates the string using the Google Translate API.
     *
     * @param string $value
     * @param string $lang
     * @return string
     */
    private static function translateValue($value, $sourceLang, $targetLang)
    {
        return TranslationHelper::translateAPI($value, $sourceLang, $targetLang);
    }

    /**
     * Preprocess the batch to concatenate and clean the strings.
     *
     * @param array $batch
     * @return string
     */
    private static function preprocessingBatch($batch)
    {
        $concatenatedString = implode('| ', array_keys($batch));
        $filteredData = str_replace('_', ' ', TranslationHelper::removeInvalidCharacters($concatenatedString));

        return $filteredData;
    }

    /**
     * Split the translated string back into an array.
     *
     * @param array $batch
     * @param string $translatedString
     * @return array
     */
    private static function splitStringToArray($batch, $translatedString)
    {
        $splitValues = explode('| ', $translatedString);
        $errors = [];
        $i = 0;

        foreach ($batch as $key => $value) {
            if (isset($splitValues[$i])) {
                $batch[$key] = $splitValues[$i];
            } else {
                $batch[$key] = $value;
                array_push($errors, 'Key not found: ' . $key);
            }
            $i++;
        }

        return $batch;
    }

    private static function getSupportedLanguagesArray()
    {
        return [
            'af',
            'sq',
            'am',
            'ar',
            'hy',
            'as',
            'ay',
            'az',
            'bm',
            'eu',
            'be',
            'bn',
            'bho',
            'bs',
            'bg',
            'ca',
            'ceb',
            'zh-CN',
            'zh-TW',
            'co',
            'hr',
            'cs',
            'da',
            'dv',
            'doi',
            'nl',
            'en',
            'eo',
            'et',
            'ee',
            'fil',
            'fi',
            'fr',
            'fy',
            'gl',
            'ka',
            'de',
            'el',
            'gn',
            'gu',
            'ht',
            'ha',
            'haw',
            'he',
            'hi',
            'hmn',
            'hu',
            'is',
            'ig',
            'ilo',
            'id',
            'ga',
            'it',
            'ja',
            'jv',
            'kn',
            'kk',
            'km',
            'rw',
            'gom',
            'ko',
            'kri',
            'ku',
            'ckb',
            'ky',
            'lo',
            'la',
            'lv',
            'ln',
            'lt',
            'lg',
            'lb',
            'mk',
            'mai',
            'mg',
            'ms',
            'ml',
            'mt',
            'mi',
            'mr',
            'mni-Mtei',
            'lus',
            'mn',
            'my',
            'ne',
            'no',
            'ny',
            'or',
            'om',
            'ps',
            'fa',
            'pl',
            'pt',
            'pa',
            'qu',
            'ro',
            'ru',
            'sm',
            'sa',
            'gd',
            'nso',
            'sr',
            'st',
            'sn',
            'sd',
            'si',
            'sk',
            'sl',
            'so',
            'es',
            'su',
            'sw',
            'sv',
            'tl',
            'tg',
            'ta',
            'tt',
            'te',
            'th',
            'ti',
            'ts',
            'tr',
            'tk',
            'ak',
            'uk',
            'ur',
            'ug',
            'uz',
            'vi',
            'cy',
            'xh',
            'yi',
            'yo',
            'zu'
        ];
    }

    /**
     * Check if the language is supported.
     *
     * @param string $lang
     * @return bool
     */
    public static function isLanguageSupported($lang)
    {
        return in_array($lang, self::getSupportedLanguagesArray());
    }

    public static function getSupportedLanguages()
    {
        return self::getSupportedLanguagesArray();
    }
}
