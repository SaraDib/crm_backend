<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemSettingController extends Controller
{
    /**
     * Get all system settings grouped.
     */
    public function index(Request $request)
    {
        if ($request->user()->user_type !== 'system') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $settings = DB::table('system_settings')
            ->orderBy('group')
            ->get();

        return response()->json($settings);
    }

    /**
     * Update or create multiple settings.
     */
    public function updateBulk(Request $request)
    {
        if ($request->user()->user_type !== 'system') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable',
        ]);

        foreach ($validated['settings'] as $setting) {
            DB::table('system_settings')
                ->where('key', $setting['key'])
                ->update([
                    'value' => $setting['value'],
                    'updated_at' => now(),
                ]);
        }

        return response()->json(['message' => 'Paramètres système mis à jour avec succès.']);
    }
}
