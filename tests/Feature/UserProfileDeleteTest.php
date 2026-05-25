<?php

namespace Tests\Feature;

use App\Models\Rating;
use App\Models\User;
use App\Repositories\Interfaces\BookingRepositoryInterface;
use App\Repositories\Interfaces\LocationRepositoryInterface;
use App\Repositories\Interfaces\RatingRepositoryInterface;
use App\Repositories\Interfaces\TourRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

final class UserProfileDeleteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_delete_user_account_successful(): void
    {
        Storage::fake('public');

        $user = new User([
            'id' => 1,
            'password' => Hash::make('password123'),
            'avatar' => 'avatars/avatar.jpg',
        ]);
        $user->id = 1;

        Storage::disk('public')->put('avatars/avatar.jpg', 'avatar content');
        Storage::disk('public')->put('ratings/10/rating_image.jpg', 'rating image content');

        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldReceive('find')
            ->once()
            ->with(1)
            ->andReturn($user);
        $userRepository->shouldReceive('delete')
            ->once()
            ->with(1)
            ->andReturn(true);

        $bookingRepository = Mockery::mock(BookingRepositoryInterface::class);
        $bookingRepository->shouldReceive('hasActiveBookings')
            ->once()
            ->with(1)
            ->andReturn(false);

        $rating = new Rating([
            'id' => 10,
            'location_id' => 100,
            'tour_id' => 200,
        ]);
        $rating->id = 10;
        $rating->location_id = 100;
        $rating->tour_id = 200;

        $ratingRepository = Mockery::mock(RatingRepositoryInterface::class);
        $ratingRepository->shouldReceive('getWhere')
            ->once()
            ->with(['user_id' => 1])
            ->andReturn(new Collection([$rating]));

        $locationRepository = Mockery::mock(LocationRepositoryInterface::class);
        $locationRepository->shouldReceive('updateStats')
            ->once()
            ->with(100)
            ->andReturn(true);

        $tourRepository = Mockery::mock(TourRepositoryInterface::class);
        $tourRepository->shouldReceive('updateStats')
            ->once()
            ->with(200)
            ->andReturn(true);

        $this->app->instance(UserRepositoryInterface::class, $userRepository);
        $this->app->instance(BookingRepositoryInterface::class, $bookingRepository);
        $this->app->instance(RatingRepositoryInterface::class, $ratingRepository);
        $this->app->instance(LocationRepositoryInterface::class, $locationRepository);
        $this->app->instance(TourRepositoryInterface::class, $tourRepository);

        $this->actingAs($user);

        $response = $this->deleteJson('/api/v1/user/account', [
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 200)
            ->assertJsonPath('message', 'Your account has been deleted successfully.');

        Storage::disk('public')->assertMissing('avatars/avatar.jpg');
        Storage::disk('public')->assertMissing('ratings/10/rating_image.jpg');
    }

    public function test_delete_user_account_wrong_password(): void
    {
        $user = new User([
            'id' => 1,
            'password' => Hash::make('password123'),
        ]);
        $user->id = 1;

        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldReceive('find')
            ->once()
            ->with(1)
            ->andReturn($user);

        $this->app->instance(UserRepositoryInterface::class, $userRepository);

        $this->actingAs($user);

        $response = $this->deleteJson('/api/v1/user/account', [
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('code', 400)
            ->assertJsonPath('message', 'The confirmation password is incorrect.');
    }

    public function test_delete_user_account_has_active_bookings(): void
    {
        $user = new User([
            'id' => 1,
            'password' => Hash::make('password123'),
        ]);
        $user->id = 1;

        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldReceive('find')
            ->once()
            ->with(1)
            ->andReturn($user);

        $bookingRepository = Mockery::mock(BookingRepositoryInterface::class);
        $bookingRepository->shouldReceive('hasActiveBookings')
            ->once()
            ->with(1)
            ->andReturn(true);

        $this->app->instance(UserRepositoryInterface::class, $userRepository);
        $this->app->instance(BookingRepositoryInterface::class, $bookingRepository);

        $this->actingAs($user);

        $response = $this->deleteJson('/api/v1/user/account', [
            'password' => 'password123',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('code', 400)
            ->assertJsonPath('message', 'You have active bookings. Please cancel or complete them before deleting your account.');
    }

    public function test_delete_user_account_validation_error(): void
    {
        $response = $this->deleteJson('/api/v1/user/account', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }
}
