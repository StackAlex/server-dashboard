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
                'settings' => 'Main_agent',
                'value' => [],
                'options' => [
                    'source' => 'server_agents',
                    'value' => 'id',
                    'label' => 'name',
                ],
                'type' => 'one_select',
            ],

            [
                'settings' => 'Docker',
                'value' => ['true'],
                'options' => [],
                'type' => 'toggle',
            ],

            [
                'settings' => 'Terminal_Shell',
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