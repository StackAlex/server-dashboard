<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ServerSettings;

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
        getAllOptions();

        $settings = ServerSettings::all();

        return response()->json([
            'settingsServer' => $settings,
        ]);
    }
    
    private function getAllOptions(){
        getAgentsOptions();
    }
    private function getAgentsOptions(){
        $agents = ServerAgent::where('enabled', true)
            ->get([
                'id',
                'name',
            ]);
        
        $settings = ServerSettings::where('settings', 'Main_agent')->first();
        if (!$settings) {
            return;
        }
        $settings->options = $agents;
        $settings->save();
    }
}