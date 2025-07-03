<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Models\User;
use App\Enums\RolesEnum;
use App\Events\AdvertiserRegistered;
use Spatie\Permission\Models\Role;
use Mockery;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_login_creates_user_and_assigns_roles()
    {
        // Arrange
        $email = 'nigah@techknowworld.com';
        $password = 'sec00r3tH3e3B#tob3com3Saf3rHas01';

        $mockedUserResponse = [
            'email' => $email,
            'companyKey' => 'company_123',
            'roles' => 'AdNetworkCompanyAdmin',
            'isAdPublisher' => true,
            'isAdvertiser' => true,
        ];

        // Mock the static SecureApi::login call
        \Mockery::mock('alias:App\Services\SecureApi')
            ->shouldReceive('login')
            ->once()
            ->andReturn([
                'user' => json_encode($mockedUserResponse),
            ]);

        // Ensure roles exist
        Role::create(['name' => RolesEnum::PUBLISHER->value, 'guard_name' => 'api']);
        Role::create(['name' => RolesEnum::ADVERTISER->value, 'guard_name' => 'api']);

        Event::fake();

        // Act
        $response = $this->postJson('/api/login', [
            'email' => $email,
            'password' => $password,
        ]);

        // Assert
        $response->assertStatus(200);

        $user = User::where('email', $email)->first();

        $this->assertNotNull($user);
        $this->assertEquals('company_123', $user->secure_api_id);
        $this->assertTrue($user->hasRole(RolesEnum::PUBLISHER->value));
        $this->assertTrue($user->hasRole(RolesEnum::ADVERTISER->value));

        Event::assertDispatched(AdvertiserRegistered::class);
    }
}
