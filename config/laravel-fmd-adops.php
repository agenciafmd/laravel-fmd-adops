<?php

declare(strict_types=1);

return [
    'webhook' => env('FMD_ADOPS_WEBHOOK', ''),
    'secret' => env('FMD_ADOPS_SECRET', ''),
    'error_email' => env('FMD_ADOPS_ERROR_EMAIL', ''),
];
