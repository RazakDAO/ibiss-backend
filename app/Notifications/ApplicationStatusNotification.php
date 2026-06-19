<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    const LABELS = [
        'reviewing' => 'En cours d\'examen',
        'interview' => 'Entretien',
        'selected'  => 'Sélectionné(e)',
        'rejected'  => 'Non retenu(e)',
    ];

    const MESSAGES_SMS = [
        'interview' => 'Félicitations ! Vous êtes convoqué(e) en entretien pour "{titre}". Connectez-vous sur Ibissé pour les détails.',
        'selected'  => 'Félicitations ! Votre candidature pour "{titre}" a été retenue.',
        'rejected'  => 'Votre candidature pour "{titre}" n\'a pas été retenue. Courage, de nouvelles offres vous attendent sur Ibissé.',
        'reviewing' => 'Votre candidature pour "{titre}" est en cours d\'examen.',
    ];

    public function __construct(private Application $candidature, private string $nouveauStatut) {}

    public function via(object $notifiable): array
    {
        $canaux = ['mail'];

        $prefSms = $notifiable->candidate?->alert_preferences['sms_status'] ?? true;
        if ($prefSms && $notifiable->phone) {
            $canaux[] = 'sms_custom';
        }

        return $canaux;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $offre  = $this->candidature->offre;
        $label  = self::LABELS[$this->nouveauStatut] ?? $this->nouveauStatut;

        $mail = (new MailMessage)
            ->subject("Votre candidature pour {$offre->title} — {$label}")
            ->greeting("Bonjour {$notifiable->name},");

        match ($this->nouveauStatut) {
            'interview' => $mail->line("**Félicitations !** Vous êtes convoqué(e) en entretien pour le poste de **{$offre->title}**.")
                               ->line("Le recruteur vous contactera directement pour les modalités."),
            'selected'  => $mail->line("**Félicitations !** Votre candidature pour **{$offre->title}** a été retenue."),
            'rejected'  => $mail->line("Après examen de votre candidature pour **{$offre->title}**, le recruteur n'a pas donné suite.")
                               ->line("Ne vous découragez pas, de nouvelles offres correspondent à votre profil sur Ibissé."),
            default     => $mail->line("Le statut de votre candidature pour **{$offre->title}** est maintenant : **{$label}**."),
        };

        return $mail
            ->action('Voir mes candidatures', config('app.frontend_url') . '/candidatures')
            ->salutation("L'équipe Ibissé");
    }

    public function toSms(object $notifiable): string
    {
        $titre   = $this->candidature->offre->title;
        $modele  = self::MESSAGES_SMS[$this->nouveauStatut] ?? 'Votre candidature pour "{titre}" a été mise à jour.';
        return str_replace('{titre}', $titre, $modele);
    }
}
