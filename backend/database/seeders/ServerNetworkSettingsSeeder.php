<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServerNetworkSettings;

class ServerNetworkSettingsSeeder extends Seeder
{
    public function run(): void
    {
        ServerNetworkSettings::updateOrCreate(
            ['id' => 1],
            [
                'dns' => [
                    'enabled' => false,
                    'type' =>'toggle',
                    'servers' => [],
                ],

                'proxy' => [
                    'enabled' => false,
                    'scheme' => 'http',
                    'host' => '',
                    'port' => 8080,
                    'username' => '',
                    'password' => '',
                    'no_proxy' => [
                        'localhost',
                        '127.0.0.1',
                    ],
                ],

                'docker_proxy' => [
                    'enabled' => false,
                    'http_proxy' => '',
                    'https_proxy' => '',
                    'no_proxy' => [
                        'localhost',
                        '127.0.0.1',
                    ],
                ],
            ]
        );
    }
}