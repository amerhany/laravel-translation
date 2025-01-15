# Laravel Translation Service

[![Latest Version on Packagist](https://img.shields.io/packagist/v/amir/translation-service.svg?style=flat-square)](https://packagist.org/packages/amir/translation-service)
[![Total Downloads](https://img.shields.io/packagist/dt/amir/translation-service.svg?style=flat-square)](https://packagist.org/packages/amir/translation-service)
[![License](https://img.shields.io/packagist/l/amir/translation-service.svg?style=flat-square)](https://packagist.org/packages/amir/translation-service)

A Laravel package for translating text between supported languages using Google Translate. This package allows you to translate language files in bulk and supports queued jobs for efficient processing. It also provides functionality to track the progress of translation jobs, making it easy to display progress on the front end.

---

## Table of Contents

- [Installation](#installation)
- [Usage](#usage)
  - [Translate Language Files](#translate-language-files)
  - [Run the Queue Worker](#run-the-queue-worker)
  - [Track Translation Progress](#track-translation-progress)
  - [Translate Single Text](#translate-single-text)
  - [Supported Languages](#supported-languages)
  - [Validate Language Codes](#validate-language-codes)
- [Contributing](#contributing)
- [License](#license)
- [Credits](#credits)
---

## Installation

You can install the package via Composer:

```bash
composer require amir/translation-service
```
Publish Configuration:

```bash
php artisan vendor:publish --provider="Amir\TranslationService\Providers\TranslateServiceProvider"
```

Set Up Database Tables:
```bash
php artisan queue:table
php artisan queue:batches-table
php artisan migrate
```
Run the Install Command:
```bash
php artisan translation-service:install
```
---
## Usage
After successfully installing the package, follow these steps to begin using it effectively

### Translate Language Files
To translate a language file, use the `translateFile` method provided by the `TranslationService`. This method dispatches translation jobs in batches and returns a batch ID for tracking progress.

```php
use Amir\TranslationService\Services\TranslationService;

$filePath = base_path('resources/lang/en/messages.php');
$sourceLang = 'en';
$targetLang = 'es';

$response = TranslationService::translateFile($filePath, $sourceLang, $targetLang);

// Output the response
dd($response);
```
The **Response** will include the batch ID, which you can use to track the progress of the translation jobs:
```php
[
    'code' => '200',
    'message' => 'Translation jobs dispatched',
    'batch_id' => 'batch-id-123', // Use this to track progress
    'start_time' => 1698765432, // Timestamp of when the batch started
]
```
### Run the Queue Worker
To process the translation jobs, you need to run the Laravel queue worker. Use the following command:
```bash
php artisan queue:work
```

### Track Translation Progress
You can track the progress of the translation jobs using the batch ID returned by the translateFile method. Laravel provides a Batch facade to query the status of a batch.

Here’s an example of how to track progress in your controller:
```php
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;

public function getBatchProgress($batchId)
{
    $batch = Bus::findBatch($batchId);

    if (!$batch) {
        return response()->json([
            'code' => '404',
            'message' => 'Batch not found',
        ], 404);
    }

    return response()->json([
        'code' => '200',
        'message' => 'Batch progress retrieved',
        'progress' => $batch->progress(),
        'total_jobs' => $batch->totalJobs,
        'pending_jobs' => $batch->pendingJobs,
        'failed_jobs' => $batch->failedJobs,
        'finished_at' => $batch->finishedAt,
    ]);
}
```
### Display Progress in HTML/JavaScript
To display the progress on the front end, you can poll the backend periodically using the batch ID. Here’s an example using HTML and JavaScript:

**HTML**
```html
<div id="progress-container">
    <p>Translation Progress: <span id="progress">0</span>%</p>
    <p>Pending Jobs: <span id="pending-jobs">0</span></p>
    <p>Failed Jobs: <span id="failed-jobs">0</span></p>
    <p id="status">Status: Processing...</p>
</div>
```
**JavaScript**

```javascript
async function trackProgress(batchId) {
    const response = await fetch(`/api/batch-progress/${batchId}`);
    const data = await response.json();

    if (data.code === '200') {
        // Update progress
        document.getElementById('progress').textContent = data.progress;
        document.getElementById('pending-jobs').textContent = data.pending_jobs;
        document.getElementById('failed-jobs').textContent = data.failed_jobs;

        if (data.progress === 100) {
            document.getElementById('status').textContent = 'Status: Completed!';
        } else {
            // Poll again after a delay
            setTimeout(() => trackProgress(batchId), 2000);
        }
    } else {
        console.error('Error tracking progress:', data.message);
    }
}

// Start tracking progress
const batchId = 'batch-id-123'; // Replace with your batch ID
trackProgress(batchId);
```
### Customizing the Front End
The example above provides a basic implementation for tracking and displaying translation progress. If you want to create your own front-end implementation, you can use the `getBatchProgress` API endpoint to fetch the progress data and display it in any way you prefer (e.g., using a progress bar, charts, or custom UI components).

Here’s the API response structure for reference:
```json
{
    "code": "200",
    "message": "Batch progress retrieved",
    "progress": 50,
    "total_jobs": 100,
    "pending_jobs": 50,
    "failed_jobs": 0,
    "finished_at": null
}
```
You can use this data to build a more advanced or visually appealing progress tracker.


### Translate Single Text
To translate a single text string, use the `translateText` method:
```php
use Amir\TranslationService\Services\TranslationService;

$text = "Hello, world!";
$sourceLang = 'en';
$targetLang = 'es';

$translatedText = TranslationService::translateText($text, $sourceLang, $targetLang);

echo $translatedText; // Output: "¡Hola, mundo!"
```
### Supported Languages
The package supports a wide range of languages. You can retrieve the list of supported languages using the `getSupportedLanguages` method:
```php
use Amir\TranslationService\Services\TranslationService;

$languages = TranslationService::getSupportedLanguages();
dd($languages);
```
### Validate Language Codes
Before translating text, you can validate whether a language code is supported using the `isLanguageSupported` method:

```php
use Amir\TranslationService\Services\TranslationService;

$language = 'es';

if (TranslationService::isLanguageSupported($language)) {
    echo "The language '$language' is supported!";
} else {
    echo "The language '$language' is NOT supported.";
}
```

## Contributing

Contributions are welcome! If you'd like to contribute, please follow these steps:

- Fork the repository.
- Create a new branch for your feature or bugfix.
- Submit a pull request.

Please ensure your code follows the PSR-12 coding standard and includes tests where applicable.

## License
This package is open-source software licensed under the MIT License.

## Credits
[Amir Hany](https://github.com/amerhany)

