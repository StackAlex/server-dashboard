<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ServerSettings;
use App\Models\ServerAgent;

class SettingsController extends Controller
{
    /**
     * Получить пользовательские настройки
     */
    public function userSettings()
    {
        $user = Auth::user();

        return response()->json([
            'settings' => $user->settings,
        ]);
    }

    /**
     * Получить настройки Dashboard
     */
    public function dashboardSettings()
    {
        $settings = ServerSettings::all();

        foreach ($settings as $setting) {
            $setting->options = $this->getAllOptions($setting);
        }

        return response()->json([
            'settingsServer' => $settings,
        ]);
    }

    /**
     * Получить options для настройки
     */
    private function getAllOptions(ServerSettings $setting): array
    {
        $options = $setting->options ?? [];

        if (
            !is_array($options) ||
            !isset($options['source'])
        ) {
            return [];
        }

        return match ($options['source']) {
            'server_agents' => $this->getAgentsOptions($options),

            default => [],
        };
    }

    /**
     * Получить активных агентов
     */
    private function getAgentsOptions(array $options): array
    {
        $valueField = $options['value'] ?? 'id';
        $labelField = $options['label'] ?? 'name';

        return ServerAgent::where('enabled', true)
            ->get([
                $valueField,
                $labelField,
            ])
            ->map(fn ($agent) => [
                'value' => $agent->{$valueField},
                'label' => $agent->{$labelField},
            ])
            ->values()
            ->toArray();
    }
}