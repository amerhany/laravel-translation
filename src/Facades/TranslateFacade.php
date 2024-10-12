<?php

namespace Amir\TranslationService\Facades;

use Illuminate\Support\Facades\Facade;

class TranslateFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'translation-service';
    }
}
