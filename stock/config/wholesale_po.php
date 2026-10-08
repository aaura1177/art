<?php

return [
    /*
    | Client reminder recipients (not suppliers).
    | First reminder: 7 days after upload; then every 3 days until delivery date.
    */
    'reminder_recipients' => [
        [
            'name'  => 'Harsh Saini',
            'email' => env('WHOLESALE_REMINDER_HARSH_EMAIL', 'admin@artisanfurniture.net'),
        ],
        [
            'name'  => 'Anu Jain',
            'email' => env('WHOLESALE_REMINDER_ANU_EMAIL', 'info@globalvisioncompany.com'),
        ],
    ],

    'first_reminder_days' => 7,
    'repeat_reminder_days' => 3,

    'excel_max_kb' => 5120,
    'excel_mimes' => ['xlsx', 'xls', 'csv'],

    'upload_disk_path' => 'wholesale_uploads',
];
