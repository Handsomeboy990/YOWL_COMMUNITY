<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Roles that the application itself depends on.
     *
     * Registration assigns "client" and the administration routes require
     * "admin": deleting either would lock the platform, so they are listed
     * here and refused rather than left to a mistake.
     */
    private const PROTECTED_ROLES = ['admin', 'client'];

    public function index()
    {
        // Le comptage passe par la table pivot plutot que par withCount sur la
        // relation users : spatie resout alors le modele depuis le garde
        // courant, qui vaut sanctum sur cette API et n'a pas de fournisseur
        // declare, ce qui faisait echouer la requete.
        $counts = DB::table(config('permission.table_names.model_has_roles'))
            ->select('role_id', DB::raw('COUNT(*) AS total'))
            ->groupBy('role_id')
            ->pluck('total', 'role_id');

        $roles = Role::with('permissions:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'users_count' => (int) ($counts[$role->id] ?? 0),
                'protected' => in_array($role->name, self::PROTECTED_ROLES, true),
                'permissions' => $role->permissions->pluck('name'),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'roles' => $roles,
                'permissions' => Permission::orderBy('name')->pluck('name'),
            ],
            'message' => 'Roles retrieved successfully.',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:60', 'regex:/^[a-z][a-z0-9_-]*$/', 'unique:roles,name'],
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);
        $role->syncPermissions($validated['permissions'] ?? []);

        AuditLog::record('role.created', $role, ['permissions' => $validated['permissions'] ?? []], $request);

        return response()->json([
            'success' => true,
            'data' => $role->load('permissions:id,name'),
            'message' => 'Rôle créé',
        ], 201);
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'permissions' => 'present|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $before = $role->permissions->pluck('name')->all();
        $role->syncPermissions($validated['permissions']);

        AuditLog::record('role.permissions_updated', $role, [
            'from' => $before,
            'to' => $validated['permissions'],
        ], $request);

        return response()->json([
            'success' => true,
            'data' => $role->load('permissions:id,name'),
            'message' => 'Droits mis à jour',
        ]);
    }

    public function destroy(Request $request, Role $role)
    {
        if (in_array($role->name, self::PROTECTED_ROLES, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Ce rôle est nécessaire au fonctionnement de la plateforme.',
            ], 422);
        }

        $assigned = DB::table(config('permission.table_names.model_has_roles'))
            ->where('role_id', $role->id)
            ->exists();
        if ($assigned) {
            return response()->json([
                'success' => false,
                'message' => 'Ce rôle est encore attribué à des membres.',
            ], 422);
        }

        $name = $role->name;
        $role->delete();
        AuditLog::record('role.deleted', null, ['name' => $name], $request);

        return response()->json(['success' => true, 'message' => 'Rôle supprimé']);
    }

    /**
     * Create a permission so it can be attached to a role.
     */
    public function storePermission(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80', 'regex:/^[a-z][a-z0-9_.\-]*$/', 'unique:permissions,name'],
        ]);

        $permission = Permission::create(['name' => $validated['name'], 'guard_name' => 'web']);
        AuditLog::record('permission.created', $permission, [], $request);

        return response()->json([
            'success' => true,
            'data' => $permission,
            'message' => 'Droit créé',
        ], 201);
    }

    /**
     * Assign the roles of a member, all at once.
     */
    public function syncUserRoles(Request $request, \App\Models\User $user)
    {
        $validated = $request->validate([
            'roles' => 'present|array',
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ]);

        // Un administrateur qui se retire son propre role perdrait l'acces a
        // la console, sans personne pour le lui rendre.
        if ($user->id === $request->user()->id && ! in_array('admin', $validated['roles'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas retirer votre propre rôle administrateur.',
            ], 403);
        }

        $before = $user->getRoleNames()->all();
        $user->syncRoles($validated['roles']);

        AuditLog::record('user.roles_updated', $user, [
            'from' => $before,
            'to' => $validated['roles'],
        ], $request);

        return response()->json([
            'success' => true,
            'data' => ['roles' => $user->getRoleNames()],
            'message' => 'Rôles mis à jour',
        ]);
    }
}
