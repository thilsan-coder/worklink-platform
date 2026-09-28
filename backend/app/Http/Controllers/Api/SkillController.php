<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SkillController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Skill::where('is_active', true)->with('category');

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $skills = $query->get();

        return response()->json([
            'success' => true,
            'data' => $skills,
        ]);
    }
}
