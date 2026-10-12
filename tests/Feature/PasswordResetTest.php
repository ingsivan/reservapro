<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_login_page_links_to_password_recovery(): void
    {
        $this->get(route('login'))->assertOk()->assertSee(route('password.request'));
        $this->get(route('password.request'))->assertOk()->assertSee('Enviar enlace');
    }

    #[Test]
    public function a_reset_link_is_emailed_in_spanish(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $mail = $notification->toMail($user);

            return $mail->subject === 'Restablece tu contraseña de ReservaPro'
                && str_contains($mail->actionUrl, route('password.reset', $notification->token));
        });
    }

    #[Test]
    public function an_unknown_email_gets_the_same_answer(): void
    {
        Notification::fake();

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'nadie@example.com'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status', __(Password::RESET_LINK_SENT))
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    #[Test]
    public function the_password_can_be_changed_with_the_emailed_token(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()
            ->assertSee($user->email);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'nueva-clave-123',
            'password_confirmation' => 'nueva-clave-123',
        ])->assertRedirect(route('login'))->assertSessionHas('status');

        $this->assertTrue(Hash::check('nueva-clave-123', $user->fresh()->password));
        $this->post(route('login'), ['email' => $user->email, 'password' => 'nueva-clave-123'])
            ->assertRedirect($user->homeRoute());
    }

    #[Test]
    public function an_invalid_token_does_not_change_the_password(): void
    {
        $user = User::factory()->create();
        $originalHash = $user->password;

        $this->post(route('password.update'), [
            'token' => 'token-falso',
            'email' => $user->email,
            'password' => 'nueva-clave-123',
            'password_confirmation' => 'nueva-clave-123',
        ])->assertSessionHasErrors('email');

        $this->assertSame($originalHash, $user->fresh()->password);
    }
}
