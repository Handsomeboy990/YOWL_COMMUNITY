<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    /**
     * The settings, with the metadata the console needs to render them.
     */
    public function index()
    {
        $values = Settings::all();

        $fields = [];
        foreach (Settings::REGISTRY as $key => $definition) {
            $fields[] = [
                'key' => $key,
                'label' => $definition['label'],
                'help' => $definition['help'] ?? null,
                'group' => $definition['group'],
                'type' => $definition['type'],
                'value' => array_key_exists($key, $values) ? $values[$key] : $definition['default'],
                'default' => $definition['default'],
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $fields,
            'message' => 'Settings retrieved successfully.',
        ]);
    }

    /**
     * The identity a visitor needs before signing in.
     *
     * Cached: it is read on every first paint, and it changes when an
     * administrator saves the console, which already clears the settings.
     */
    public function site()
    {
        return response()->json([
            'success' => true,
            'data' => Settings::publicValues(),
            'message' => 'Site settings retrieved successfully.',
        ]);
    }

    /**
     * Store an image for a setting and hand back its path.
     *
     * The value written into the setting is the stored path, so the logo
     * follows the media disk like every other upload and survives a container
     * restart once the disk is an object store.
     */
    public function uploadImage(Request $request)
    {
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,jpg,png,webp,svg|max:2048',
            'key' => ['required', 'string'],
        ]);

        $definition = Settings::REGISTRY[$validated['key']] ?? null;
        if (! $definition || $definition['type'] !== 'image') {
            return response()->json([
                'success' => false,
                'message' => 'Ce réglage ne reçoit pas d\'image.',
            ], 422);
        }

        $chemin = \App\Support\Media::store($request->file('image'), 'site');

        AuditLog::record('setting.image', null, ['key' => $validated['key']], $request);

        return response()->json([
            'success' => true,
            'data' => ['path' => $chemin],
            'message' => 'Image enregistrée.',
        ]);
    }

    /**
     * Update one or several settings.
     *
     * Only declared keys are accepted, each validated with the rule attached
     * to it in the registry, so the console cannot write a value the rest of
     * the application is not prepared to read.
     */
    public function update(Request $request)
    {
        $submitted = $request->input('settings', []);
        if (! is_array($submitted) || ! $submitted) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun réglage à enregistrer.',
            ], 422);
        }

        $unknown = array_diff(array_keys($submitted), array_keys(Settings::REGISTRY));
        if ($unknown) {
            return response()->json([
                'success' => false,
                'message' => 'Réglage inconnu : '.implode(', ', $unknown),
            ], 422);
        }

        // Les cles portent un point, que le validateur lit comme un chemin
        // vers un tableau imbrique. Il est echappe le temps de la validation,
        // sans quoi aucune regle ne s'applique et tout passe.
        $rules = [];
        foreach (array_keys($submitted) as $key) {
            $rules[str_replace('.', '\.', $key)] = Settings::REGISTRY[$key]['rules'];
        }

        $validator = Validator::make($submitted, $rules);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors(),
                'message' => 'Réglages invalides.',
            ], 422);
        }

        $before = Settings::all();

        foreach (array_keys($submitted) as $key) {
            Settings::set($key, $submitted[$key]);
        }

        $changes = [];
        foreach (array_keys($submitted) as $key) {
            if (($before[$key] ?? null) !== Settings::get($key)) {
                $changes[$key] = ['from' => $before[$key] ?? null, 'to' => Settings::get($key)];
            }
        }

        if ($changes) {
            AuditLog::record('settings.updated', null, $changes, $request);
        }

        return response()->json([
            'success' => true,
            'data' => Settings::all(),
            'message' => 'Réglages enregistrés',
        ]);
    }

    /**
     * The audit trail, newest first.
     */
    public function auditLog(Request $request)
    {
        $logs = AuditLog::with('user:id,username')
            ->when($request->input('action'), fn ($query, $action) => $query->where('action', $action))
            ->orderByDesc('created_at')
            ->paginate(30);

        return response()->json([
            'success' => true,
            'data' => $logs,
            'message' => 'Audit log retrieved successfully.',
        ]);
    }
}
