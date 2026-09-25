<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServerSettings;

class ServerSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'settings' => 'Main_agent',
                'value' => null,
                'type' => 'one_select',
            ],
            [
                'settings' => 'Docker',
                'value' => '1',
                'type' => 'toggle',
            ],
            [
                'settings' => 'Terminal_shell',
                'value' => '/bin/bash',
                'type' => 'input',
            ],
        ];

        foreach ($settings as $setting) {
            ServerSettings::updateOrCreate(
                [
                    'settings' => $setting['settings'],
                ],
                [
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'update_by' => null,
                ]
            );
        }
    }
}