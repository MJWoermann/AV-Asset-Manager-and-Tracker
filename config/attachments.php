<?php

return [

    'disk' => env('ATTACHMENTS_DISK', 'local'),

    'max_kilobytes' => 25600,

    'allowed_mimes' => [
        'jpg', 'jpeg', 'png', 'webp', 'pdf', 'docx', 'xlsx', 'csv',
    ],

    'image_mimes' => [
        'jpg', 'jpeg', 'png', 'webp',
    ],

    'image_max_edge' => 1920,

    'thumbnail_max_edge' => 400,

    'jpeg_quality' => 82,

    'webp_quality' => 80,

];
