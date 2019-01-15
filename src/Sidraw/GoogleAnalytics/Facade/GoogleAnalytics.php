<?php

namespace Sidraw\GoogleAnalytics\Facade;

use Illuminate\Support\Facades\Facade as BaseFacade;

class GoogleAnalytics extends BaseFacade
{
    protected static function getFacadeAccessor() {
        return 'googleAnalytics';
    }
}