<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicWorkerResource;
use App\Models\WorkerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = WorkerProfile::with(['user', 'skills.category', 'portfolioItems'])
            ->where('verification_status', 'verified');

        if ($request->has('availability_status')) {
            $query->where('availability_status', $request->availability_status);
        }

        if ($request->has('category_id')) {
            $query->whereHas('skills', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        if ($request->has('skill_id')) {
            $query->whereHas('skills', function ($q) use ($request) {
                $q->where('skills.id', $request->skill_id);
            });
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%");
                })->orWhere('bio', 'like', "%{$search}%");
            });
        }

        $workers = $query->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => PublicWorkerResource::collection($workers)->response()->getData(true),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $worker = WorkerProfile::with(['user', 'skills.category', 'portfolioItems'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new PublicWorkerResource($worker),
        ]);
    }
}
