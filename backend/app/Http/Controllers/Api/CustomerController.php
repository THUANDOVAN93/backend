<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Customer::with(['user', 'addresses']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('phone', 'like', "%{$search}%");
        }

        $customers = $query->withCount('orders')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => CustomerResource::collection($customers),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
            ],
        ]);
    }

    public function show(Customer $customer): JsonResponse
    {
        $customer->load(['user', 'addresses', 'orders']);

        return response()->json([
            'data' => new CustomerResource($customer),
        ]);
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'notes' => 'nullable|string',
        ]);

        $customer->update($validated);

        return response()->json([
            'message' => 'Customer updated successfully',
            'data' => new CustomerResource($customer->load(['user', 'addresses'])),
        ]);
    }
}
