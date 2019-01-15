Laravel GoogleAnalytics
===============

Installation
------------

You should publish config by this command

``` bash
php artisan vendor:publish --provider="Sidraw\GoogleAnalytics\GoogleAnalyticsServiceProvider"
```

the required config will be published in `config/google-analytics.php`

and you should set app name, view id and put the service account key to storage path

```php
<?php

return [
    'name'   => env('GOOGLE_ANALYTICS_APP_NAME'),
    'key'    => storage_path('app/google/analytics_secret.json'),
    'viewId' => env('GOOGLE_ANALYTICS_VIEW_ID')
];
```

How to use
------------

```php
use Sidraw\GoogleAnalytics\Facade\GoogleAnalytics;

...
...
...

public function getReport()
{
    $startDate = strtotime('-7 day');
    $endDate   = strtotime('yesterday');
    
    $alias = 'pv';
    $metrics    = ['pageviews' => $alias];
    $dimensions = ['pagePath'];
    
    $whatYouNeedToFilter = [
        'welcome.php',
        'search.html'
    ];
    
    $filters    = [
        'dimension' => [
            'pagePath' => $whatYouNeedToFilter
        ]
    ];
    
    $regex = '/(\S+)\./';

    $report = GoogleAnalytics::getReport($startDate, $endDate, $metrics, $dimensions, $filters, $regex);

    return $report;
}
```

result

```php
array (size=2)
  'welcome' => 
    array (size=1)
      'pv' => int 7015
  'search' => 
      array (size=1)
        'pv' => int 9301

```
