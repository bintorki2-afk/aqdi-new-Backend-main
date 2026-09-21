<?php

namespace App\Modules\Employees\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Role;

class PermissionController extends Controller
{
    use Responser;

    /**
     * Display a listing of permissions (no sort, no filter)
     */
    public function index(Request $request)
    {
        try {
            $permissions = Permission::with(['roles'])
                ->paginate((int) $request->get('per_page', 20));

            return $this->apiResponse(
                [
                    'items' => $permissions->items(),
                    'pagination' => $this->paginate($permissions),
                ],
                trans('api.success')
            );
        } catch (\Exception $e) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Store permissions for a section (English section key) with actions[]
     * Example permission name: section.action
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'section' => 'required|string|max:255', // English key
                'actions' => 'required|array|min:1',
                'actions.*' => 'in:view,create,edit,delete,retrieve',
                'role_id' => 'nullable|exists:roles,id',
            ]);

            $section = $this->normalizeSectionKey($validated['section']);
            $actions = array_values(array_unique($validated['actions']));

            $created = [];
            $existing = [];

            DB::transaction(function () use ($section, $actions, $validated, &$created, &$existing) {
                foreach ($actions as $action) {
                    $name = "{$section}.{$action}";

                    $permission = Permission::firstOrCreate(
                        ['name' => $name],
                        [
                            'section' => $section,
                            'action' => $action,
                        ]
                    );

                    if ($permission->wasRecentlyCreated) {
                        $created[] = $permission;
                    } else {
                        $existing[] = $permission;
                    }
                }

                // Optional: attach to role
                if (!empty($validated['role_id'])) {
                    /** @var Role $role */
                    $role = Role::find($validated['role_id']);

                    $all = collect($created)->merge($existing)->unique('id');
                    $role->permissions()->syncWithoutDetaching($all->pluck('id')->all());
                }
            });

            return $this->apiResponse(
                [
                    'section_key' => $section,
                    'section_label' => config("permissions.sections.$section", $section),
                    'created_count' => count($created),
                    'existing_count' => count($existing),
                    'created' => $created,
                    'existing' => $existing,
                ],
                trans('api.created_successfully'),
                201
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Exception $e) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Display the specified permission
     */
    public function show($id)
    {
        try {
            $permission = Permission::with(['roles'])->find($id);

            if (!$permission) {
                return $this->errorMessage(trans('api.not_found'), 404);
            }

            return $this->apiResponse(
                [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'section_key' => $permission->section,
                    'section_label' => config('permissions.sections.' . $permission->section, $permission->section),
                    'action' => $permission->action ?? $this->actionFromName($permission->name),
                    'action_label' => config('permissions.actions.' . ($permission->action ?? $this->actionFromName($permission->name))),
                    'roles' => $permission->roles,
                ],
                trans('api.success')
            );
        } catch (\Exception $e) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Update permission (keeps decision: section=english key, name=section.action)
     * Allowed updates:
     * - section (english key)
     * - action
     * - optional role attach via role_id
     */
    public function update(Request $request, $id)
    {
        try {
            $permission = Permission::find($id);

            if (!$permission) {
                return $this->errorMessage(trans('api.not_found'), 404);
            }

            $validated = $request->validate([
                'section' => 'sometimes|required|string|max:255',
                'action' => 'sometimes|required|in:view,create,edit,delete,retrieve',
                'role_id' => 'nullable|exists:roles,id',
            ]);

            $currentSection = $permission->section;
            $currentAction = $permission->action ?? $this->actionFromName($permission->name);

            $newSection = array_key_exists('section', $validated)
                ? $this->normalizeSectionKey($validated['section'])
                : $currentSection;

            $newAction = array_key_exists('action', $validated)
                ? $validated['action']
                : $currentAction;

            $newName = "{$newSection}.{$newAction}";

            // ensure unique name (exclude current)
            $exists = Permission::where('name', $newName)
                ->where('id', '!=', $permission->id)
                ->exists();

            if ($exists) {
                return $this->errorMessage(trans('api.already_exists') ?? 'Already exists', 422);
            }

            $permission->update([
                'section' => $newSection,
                'action' => $newAction,
                'name' => $newName,
            ]);

            // Optional: attach to role
            if (!empty($validated['role_id'])) {
                /** @var Role $role */
                $role = Role::find($validated['role_id']);
                $role->permissions()->syncWithoutDetaching([$permission->id]);
            }

            return $this->apiResponse(
                $permission->fresh()->load('roles'),
                trans('api.updated_successfully')
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Exception $e) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Remove the specified permission
     */
    public function destroy($id)
    {
        try {
            $permission = Permission::find($id);

            if (!$permission) {
                return $this->errorMessage(trans('api.not_found'), 404);
            }

            $permission->delete();

            return $this->apiResponse([], trans('api.deleted_successfully'));
        } catch (\Exception $e) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get permissions grouped by section (no filter/sort)
     * Output matches UI cards: section_key + section_label + actions list
     */
    public function bySection()
    {
        try {
            $permissions = Permission::query()
                ->with(['roles'])
                ->get()
                ->groupBy('section');

            $grouped = $permissions->map(function ($items, $section) {
                return [
                    'section_key' => $section,
                    'section_label' => config("permissions.sections.$section", $section),
                    'permissions' => $items->map(function ($permission) {
                        $action = $permission->action ?? $this->actionFromName($permission->name);

                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'action' => $action,
                            'action_label' => config("permissions.actions.$action", $action),
                            'roles' => $permission->roles,
                        ];
                    })->values(),
                ];
            })->values();

            return $this->apiResponse($grouped, trans('api.success'));
        } catch (\Exception $e) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Normalize section key to safe english-like key
     */
    private function normalizeSectionKey(string $section): string
    {
        $section = trim($section);
        $section = strtolower($section);
        $section = preg_replace('/\s+/', '_', $section);
        $section = preg_replace('/[^a-z0-9_]/', '', $section);
        return $section !== '' ? $section : 'section';
    }

    /**
     * If action column not present, infer action from "section.action"
     */
    private function actionFromName(string $name): ?string
    {
        $parts = explode('.', $name);
        return $parts[1] ?? null;
    }
}
