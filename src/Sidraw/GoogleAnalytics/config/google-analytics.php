<?php

return [
    'name'   => env('GOOGLE_ANALYTICS_APP_NAME'),
    'key'    => storage_path('app/google/analytics_secret.json'),
    'viewId' => env('GOOGLE_ANALYTICS_VIEW_ID')
];