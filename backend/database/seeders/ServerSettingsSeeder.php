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
                'meta'=> [],
                'type' => 'one_select',
            ],

            [
                'settings' => 'Docker',
                'value' => ['true'],
                'options' => [],
                'meta'=> [],
                'type' => 'toggle',
            ],

            [
                'settings' => 'Terminal_Shell',
                'value' => ['/bin/bash'],
                'options' => [],
                'meta'=> [],
                'type' => 'input',
            ],

            [
                'settings' => 'DNS',
                'value' => ['false'],
                'options' => [],
                'meta' => [
                    'transport' => 'server_agent',
                    'category' => 'network',
                ],
                'type' => 'toggle',
            ],

            [
                'settings' => 'DNS_servers',
                'value' => [],
                'options' => [],
                'meta' => [
                    'transport' => 'server_agent',
                    'category' => 'network',
                ],
                'type' => 'input',
            ],

            [
                'settings' => 'Proxy',
                'value' => ['false'],
                'options' => [],
                'meta' => [
                    'transport' => 'server_agent',
                    'category' => 'network',
                ],
                'type' => 'toggle',
            ],

            [
                'settings' => 'Proxy_url',
                'value' => [],
                'options' => [],
                'meta' => [
                    'transport' => 'server_agent',
                    'category' => 'network',
                ],
                'type' => 'input',
            ],

            [
                'settings' => 'Proxy_Username',
                'value' => [],
                'options' => [],
                'meta' => [
                    'transport' => 'server_agent',
                    'category' => 'network',
                ],
                'type' => 'input',
            ],

            [
                'settings' => 'Proxy_password__secret',
                'value' => [],
                'options' => [],
                'meta' => [
                    'transport' => 'server_agent',
                    'category' => 'network',
                ],
                'type' => 'input',
            ],

            [
                'settings' => 'Proxy_exclude',
                'value' => [],
                'options' => [],
                'meta' => [
                    'transport' => 'server_agent',
                    'category' => 'network',
                ],
                'type' => 'input',
            ],

            [
                'settings' => 'Docker_Proxy',
                'value' => ['false'],
                'options' => [],
                'meta' => [
                    'transport' => 'server_agent',
                    'category' => 'network',
                ],
                'type' => 'toggle',
            ],

            [
                'settings' => 'Docker_Proxy_URL',
                'value' => [],
                'options' => [],
                'meta' => [
                    'transport' => 'server_agent',
                    'category' => 'network',
                ],
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
                    'meta' => $setting['meta'],
                    'update_by' => null,
                ]
            );
        }
    }
}