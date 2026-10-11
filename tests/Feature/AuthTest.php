<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_visitor_can_register_and_is_signed_in(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Nereo',
            'email' => 'nereo@example.com',
            'password' => 'una-clave-segura',
            'password_confirmation' => 'una-clave-segura',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs(User::sole());
        $this->assertSame('nereo@example.com', User::sole()->email);
    }

    public function test_registration_rejects_a_taken_email_and_mismatched_passwords(): void
    {
        User::factory()->create(['email' => 'nereo@example.com']);

        $this->post(route('register'), [
            'name' => 'Nereo',
            'email' => 'nereo@example.com',
            'password' => 'una-clave-segura',
            'password_confirmation' => 'otra-clave',
        ])->assertSessionHasErrors([
            'email' => 'Ya existe una cuenta con ese correo.',
            'password' => 'Las contraseñas no coinciden.',
        ]);

        $this->assertGuest();
    }

    public function test_a_user_can_sign_in_and_out(): void
    {
        $user = User::factory()->create();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_a_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'incorrecta'])
            ->assertSessionHasErrors(['email' => 'El correo o la contraseña no son correctos.']);

        $this->assertGuest();
    }

    public function test_sign_in_attempts_are_rate_limited(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $attempt) {
            $this->post(route('login'), ['email' => $user->email, 'password' => 'incorrecta']);
        }

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])->assertTooManyRequests();
        $this->assertGuest();
    }

    public function test_guests_are_sent_to_sign_in_from_the_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }
}
