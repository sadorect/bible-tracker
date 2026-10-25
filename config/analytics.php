<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Site Visit Retention
    |--------------------------------------------------------------------------
    |
    | How many days of site visit rows to keep. The site-visits:prune command
    | runs nightly from the scheduler and deletes anything older, so the
    | table cannot grow without bound.
    |
    */

    'site_visit_retention_days' => (int) env('SITE_VISIT_RETENTION_DAYS', 90),

];
