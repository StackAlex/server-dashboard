<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServerSettings;
use App\Models\ServerAgent;

class ServerSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'settings' => 'main_agent',
                'value' => [],
                'options' => [],
                'type' => 'one_select',
            ],

            [
                'settings' => 'docker',
                'value' => ['true'],
                'options' => [],
                'type' => 'toggle',
            ],

            [
                'settings' => 'terminal_shell',
                'value' => ['/bin/bash'],
                'options' => [],
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
                    'options' => $setting['options'],
                    'update_by' => null,
                ]
            );
        }
    }
}