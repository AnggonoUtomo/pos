<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Mail\Markdown;
use Tests\TestCase;

class NotificationMarkdownTest extends TestCase
{
    public function test_auth_notification_html_and_text_keep_action_links_without_delivery(): void
    {
        $user = new User(['name' => 'QA', 'email' => 'qa@example.test']);
        $user->id = 1;

        foreach ([new ResetPassword('synthetic-test-value'), new VerifyEmail] as $notification) {
            $message = $notification->toMail($user);
            $html = (string) $message->render();
            $text = (string) app(Markdown::class)->renderText($message->markdown, $message->data());

            $this->assertStringContainsString($message->actionText, $html);
            $this->assertStringContainsString($message->actionText, $text);
            $this->assertStringContainsString(htmlspecialchars($message->actionUrl, ENT_QUOTES, 'UTF-8'), $html);
            $this->assertStringContainsString($message->actionUrl, $text);
            $this->assertStringNotContainsString('<script', $html);
        }
    }
}
