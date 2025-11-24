<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AddressController extends Controller
{
    /**
     * Store a new address for a customer
     */
    public function store(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'label' => 'required|string|in:home,office,other',
            'recipient_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'street_address' => 'required|string',
            'city' => 'required|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'is_default' => 'boolean',
        ]);

        $validated['customer_id'] = $customer->id;

        $address = Address::create($validated);

        return response()->json([
            'message' => 'Address created successfully',
            'data' => new AddressResource($address),
        ], 201);
    }

    /**
     * Update an existing address
     */
    public function update(Request $request, Address $address): JsonResponse
    {
        $validated = $request->validate([
            'label' => 'sometimes|string|in:home,office,other',
            'recipient_name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'street_address' => 'sometimes|string',
            'city' => 'sometimes|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'sometimes|string|max:20',
            'country' => 'sometimes|string|max:100',
            'is_default' => 'boolean',
        ]);

        $address->update($validated);

        return response()->json([
            'message' => 'Address updated successfully',
            'data' => new AddressResource($address),
        ]);
    }

    /**
     * Delete an address
     */
    public function destroy(Address $address): JsonResponse
    {
        // Prevent deleting the default address if it's the only one
        if ($address->is_default) {
            $addressCount = Address::where('customer_id', $address->customer_id)->count();

            if ($addressCount > 1) {
                // Set another address as default before deleting
                Address::where('customer_id', $address->customer_id)
                    ->where('id', '!=', $address->id)
                    ->first()
                    ->update(['is_default' => true]);
            }
        }

        $address->delete();

        return response()->json([
            'message' => 'Address deleted successfully',
        ]);
    }

    /**
     * Set an address as default
     */
    public function setDefault(Address $address): JsonResponse
    {
        // Remove default from all other addresses
        Address::where('customer_id', $address->customer_id)
            ->where('id', '!=', $address->id)
            ->update(['is_default' => false]);

        // Set this address as default
        $address->update(['is_default' => true]);

        return response()->json([
            'message' => 'Default address updated successfully',
            'data' => new AddressResource($address),
        ]);
    }
}
