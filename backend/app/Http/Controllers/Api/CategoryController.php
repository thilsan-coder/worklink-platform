<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::where('is_active', true)
            ->with(['skills' => function ($query) {
                $query->where('is_active', true);
            }])
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $category = Category::where('is_active', true)
            ->with(['skills' => function ($query) {
                $query->where('is_active', true);
            }])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $category,
        ]);
    }
}
