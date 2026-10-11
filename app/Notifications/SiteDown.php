<?php

namespace App\Notifications;

use App\Models\Audit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SiteDown extends Notification implements ShouldQueue
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
        $reason = $this->audit->error_message
            ?? "La página respondió con el código HTTP {$this->audit->http_status}.";

        return (new MailMessage)
            ->error()
            ->subject("🔴 {$site->name} está caído")
            ->greeting('Tu sitio no responde')
            ->line("WebPulse no pudo cargar {$site->url}.")
            ->line("Motivo: {$reason}")
            ->action('Ver la auditoría', route('audits.show', $this->audit))
            ->line('Te avisaremos cuando vuelva a funcionar.');
    }
}
