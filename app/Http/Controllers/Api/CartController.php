<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddToCartRequest;
use App\Http\Requests\Cart\MergeCartRequest;
use App\Http\Requests\Cart\UpdateCartRequest;
use App\Models\CartItem;
use App\Models\TourSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Class CartController
 * Handles shopping cart operations for tour bookings.
 */
final class CartController extends Controller
{
    /**
     * Retrieve all cart items for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $items = CartItem::where('user_id', $userId)
            ->with(['tour', 'tourSchedule'])
            ->get();

        return $this->success($items, 'Cart items retrieved successfully.');
    }

    /**
     * Add an item to the user's cart.
     */
    public function store(AddToCartRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $validated = $request->validated();

        $tourSchedule = TourSchedule::find($validated['tour_schedule_id']);
        if (! $tourSchedule) {
            return $this->not_found('Tour schedule not found.');
        }

        $requestedSeats = $validated['quantity_adult'] + ($validated['quantity_child'] ?? 0);
        $availableSeats = $tourSchedule->max_people - $tourSchedule->booked_people;

        // Check if item already exists in user's cart
        $existingCartItem = CartItem::where('user_id', $userId)
            ->where('tour_schedule_id', $validated['tour_schedule_id'])
            ->first();

        if ($existingCartItem) {
            $totalRequestedSeats = $requestedSeats + $existingCartItem->quantity_adult + $existingCartItem->quantity_child;

            if ($availableSeats < $totalRequestedSeats) {
                return $this->validation_error([
                    'quantity_adult' => ['The combined passenger count exceeds remaining available seats ('.$availableSeats.').'],
                ], 'Capacity exceeded');
            }

            $existingCartItem->update([
                'quantity_adult' => $existingCartItem->quantity_adult + $validated['quantity_adult'],
                'quantity_child' => $existingCartItem->quantity_child + ($validated['quantity_child'] ?? 0),
                'quantity_infant' => $existingCartItem->quantity_infant + ($validated['quantity_infant'] ?? 0),
            ]);

            return $this->success($existingCartItem->load(['tour', 'tourSchedule']), 'Item quantity updated in cart.');
        }

        if ($availableSeats < $requestedSeats) {
            return $this->validation_error([
                'quantity_adult' => ['The passenger count exceeds remaining available seats ('.$availableSeats.').'],
            ], 'Capacity exceeded');
        }

        $cartItem = CartItem::create([
            'user_id' => $userId,
            'tour_id' => $validated['tour_id'],
            'tour_schedule_id' => $validated['tour_schedule_id'],
            'quantity_adult' => $validated['quantity_adult'],
            'quantity_child' => $validated['quantity_child'] ?? 0,
            'quantity_infant' => $validated['quantity_infant'] ?? 0,
        ]);

        return $this->created($cartItem->load(['tour', 'tourSchedule']), 'Item added to cart successfully.');
    }

    /**
     * Update an existing cart item's quantities.
     */
    public function update(UpdateCartRequest $request, int $id): JsonResponse
    {
        $userId = $request->user()->id;
        $validated = $request->validated();

        $cartItem = CartItem::where('user_id', $userId)->find($id);
        if (! $cartItem) {
            return $this->not_found('Cart item not found.');
        }

        $tourSchedule = TourSchedule::find($cartItem->tour_schedule_id);
        if (! $tourSchedule) {
            return $this->not_found('Tour schedule not found.');
        }

        $requestedSeats = $validated['quantity_adult'] + ($validated['quantity_child'] ?? 0);
        $availableSeats = $tourSchedule->max_people - $tourSchedule->booked_people;

        if ($availableSeats < $requestedSeats) {
            return $this->validation_error([
                'quantity_adult' => ['The passenger count exceeds remaining available seats ('.$availableSeats.').'],
            ], 'Capacity exceeded');
        }

        $cartItem->update([
            'quantity_adult' => $validated['quantity_adult'],
            'quantity_child' => $validated['quantity_child'] ?? 0,
            'quantity_infant' => $validated['quantity_infant'] ?? 0,
        ]);

        return $this->success($cartItem->load(['tour', 'tourSchedule']), 'Cart item updated successfully.');
    }

    /**
     * Delete a cart item.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $userId = $request->user()->id;

        $cartItem = CartItem::where('user_id', $userId)->find($id);
        if (! $cartItem) {
            return $this->not_found('Cart item not found.');
        }

        $cartItem->delete();

        return $this->success(null, 'Item removed from cart.');
    }

    /**
     * Clear all cart items.
     */
    public function clear(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        CartItem::where('user_id', $userId)->delete();

        return $this->success(null, 'Cart cleared successfully.');
    }

    /**
     * Merge local guest cart items into the user's cart.
     */
    public function merge(MergeCartRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $validated = $request->validated();
        $items = $validated['items'];

        DB::transaction(function () use ($userId, $items) {
            foreach ($items as $item) {
                $tourSchedule = TourSchedule::find($item['tour_schedule_id']);
                if (! $tourSchedule) {
                    continue;
                }

                $availableSeats = $tourSchedule->max_people - $tourSchedule->booked_people;

                $existingCartItem = CartItem::where('user_id', $userId)
                    ->where('tour_schedule_id', $item['tour_schedule_id'])
                    ->first();

                if ($existingCartItem) {
                    $newAdult = $existingCartItem->quantity_adult + $item['quantity_adult'];
                    $newChild = $existingCartItem->quantity_child + ($item['quantity_child'] ?? 0);
                    $newInfant = $existingCartItem->quantity_infant + ($item['quantity_infant'] ?? 0);

                    $totalRequested = $newAdult + $newChild;
                    if ($totalRequested > $availableSeats) {
                        if ($availableSeats >= 1) {
                            $newAdult = min($newAdult, $availableSeats);
                            $newChild = max(0, $availableSeats - $newAdult);
                        } else {
                            $newAdult = 1;
                            $newChild = 0;
                        }
                    }

                    $existingCartItem->update([
                        'quantity_adult' => $newAdult,
                        'quantity_child' => $newChild,
                        'quantity_infant' => $newInfant,
                    ]);
                } else {
                    $requestedSeats = $item['quantity_adult'] + ($item['quantity_child'] ?? 0);
                    $newAdult = $item['quantity_adult'];
                    $newChild = $item['quantity_child'] ?? 0;
                    $newInfant = $item['quantity_infant'] ?? 0;

                    if ($requestedSeats > $availableSeats) {
                        if ($availableSeats >= 1) {
                            $newAdult = min($newAdult, $availableSeats);
                            $newChild = max(0, $availableSeats - $newAdult);
                        } else {
                            $newAdult = 1;
                            $newChild = 0;
                        }
                    }

                    CartItem::create([
                        'user_id' => $userId,
                        'tour_id' => $item['tour_id'],
                        'tour_schedule_id' => $item['tour_schedule_id'],
                        'quantity_adult' => $newAdult,
                        'quantity_child' => $newChild,
                        'quantity_infant' => $newInfant,
                    ]);
                }
            }
        });

        $mergedItems = CartItem::where('user_id', $userId)
            ->with(['tour', 'tourSchedule'])
            ->get();

        return $this->success($mergedItems, 'Cart merged successfully.');
    }
}
