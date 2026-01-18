<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Display a listing of company roles.
     */
    public function index()
    {
        $roles = Role::where('type', 'company')->get();
        return response()->json($roles);
    }
}
