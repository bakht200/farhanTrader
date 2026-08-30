<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login', absolute: false));
    }

    public function test_guest_logout_still_redirects_to_login(): void
    {
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_guests_are_redirected_to_the_login_screen(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_expired_session_json_requests_return_401(): void
    {
        $this->getJson('/sync/ping')->assertUnauthorized();
    }

    public function test_login_page_is_not_cached(): void
    {
        $cacheControl = (string) $this->get('/login')->assertOk()->headers->get('Cache-Control');

        $this->assertStringContainsString('no-store', $cacheControl);
    }

    public function test_csrf_token_endpoint_returns_a_token(): void
    {
        $this->getJson('/csrf-token')
            ->assertOk()
            ->assertJsonStructure(['token']);
    }

    public function test_login_expired_query_shows_a_friendly_message(): void
    {
        $this->get('/login?expired=1')
            ->assertOk()
            ->assertSee('Your session expired. Please sign in again.');
    }

    public function test_http_419_redirects_to_login_instead_of_expired_page(): void
    {
        $request = \Illuminate\Http\Request::create('/login', 'POST');
        $request->setLaravelSession($this->app['session']->driver());

        $response = $this->app->make(\Illuminate\Contracts\Debug\ExceptionHandler::class)
            ->render($request, new \Symfony\Component\HttpKernel\Exception\HttpException(419, 'CSRF token mismatch.'));

        $this->assertTrue($response->isRedirect(route('login', ['expired' => 1])));
        $this->assertStringNotContainsString('PAGE EXPIRED', $response->getContent());
    }
}
