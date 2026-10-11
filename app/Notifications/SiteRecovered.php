<?php

namespace App\Notifications;

use App\Models\Audit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SiteRecovered extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Audit $audit) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $site = $this->audit->site;

        return (new MailMessage)
            ->success()
            ->subject("🟢 {$site->name} volvió a funcionar")
            ->greeting('Tu sitio está de vuelta')
            ->line("{$site->url} responde otra vez (HTTP {$this->audit->http_status}).")
            ->action('Ver la auditoría', route('audits.show', $this->audit));
    }
}
