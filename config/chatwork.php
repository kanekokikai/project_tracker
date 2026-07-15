<?php

return [
    'enabled' => env('CHATWORK_ENABLED', false),

    'api_token' => env('CHATWORK_API_TOKEN', ''),

    'room_id' => env('CHATWORK_ROOM_ID', ''),

    'project_base_url' => rtrim((string) env('CHATWORK_PROJECT_BASE_URL', env('APP_URL', '')), '/'),

    'member_mapping' => [
        '堀内' => '1406764',
        '金子' => '1419661',
        '只川' => '1407283',
        '星' => '2215971',
        '安井' => '1406770',
        '池上' => '2783025',
        '安藤' => '7458535',
        '権守' => '1423234',
        '稲葉' => '1423247',
        '久保田奈々' => '1406770',
        '久保田' => '4650346',
    ],
];
