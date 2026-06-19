<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewApplicationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private Application $candidature) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $offre    = $this->candidature->offre;
        $candidat = $this->candidature->candidate->user;

        return (new MailMessage)
            ->subject("Nouvelle candidature — {$offre->title}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("{$candidat->name} vient de postuler à votre offre **{$offre->title}**.")
            ->line("Ville : {$offre->city} | Type : {$offre->type}")
            ->action('Voir la candidature', config('app.frontend_url') . '/recruteur/candidatures')
            ->line("Connectez-vous sur Ibissé pour consulter le profil et le CV du candidat.")
            ->salutation("L'équipe Ibissé");
    }

    public function toSms(object $notifiable): string
    {
        $offre = $this->candidature->offre;
        return "Ibissé : Nouvelle candidature pour \"{$offre->title}\". Connectez-vous pour la consulter.";
    }
}
