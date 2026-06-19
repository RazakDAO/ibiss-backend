<?php

namespace App\Jobs;

use App\Models\Candidate;
use App\Models\Offre;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendJobAlerts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(private Offre $offre) {}

    public function handle(): void
    {
        $secteur = $this->offre->sector;
        $ville   = $this->offre->city;
        $envoyes = 0;

        Candidate::with('user')
            ->where('visibility', '!=', 'private')
            ->whereNotNull('alert_preferences')
            ->chunk(50, function ($candidats) use ($secteur, $ville, &$envoyes) {
                foreach ($candidats as $candidat) {
                    $prefs = $candidat->alert_preferences ?? [];

                    $secteurs = $prefs['sectors'] ?? [];
                    $villes   = $prefs['cities'] ?? [];

                    $secteurMatch = empty($secteurs) || in_array($secteur, $secteurs);
                    $villeMatch   = empty($villes) || in_array($ville, $villes);

                    if (!$secteurMatch || !$villeMatch) {
                        continue;
                    }

                    // Email
                    if ($candidat->user->email) {
                        Mail::raw(
                            "Nouvelle offre sur Ibissé : {$this->offre->title} à {$this->offre->city}.\n\n" .
                            "Type : {$this->offre->type} | Niveau : {$this->offre->level}\n\n" .
                            "Consultez l'offre sur " . config('app.frontend_url') . "/offres/{$this->offre->slug}",
                            fn ($m) => $m->to($candidat->user->email)
                                        ->subject("Nouvelle offre : {$this->offre->title}")
                        );
                    }

                    // SMS si activé
                    if (($prefs['sms_alerts'] ?? false) && $candidat->user->phone) {
                        SendSmsNotification::dispatch(
                            $candidat->user->phone,
                            "Ibissé : Nouvelle offre \"{$this->offre->title}\" à {$this->offre->city}. Postulez sur ibisse.bf"
                        )->onQueue('notifications');
                    }

                    $envoyes++;
                }
            });

        Log::info('Alertes offre envoyées', [
            'offre_id' => $this->offre->id,
            'envoyes'  => $envoyes,
        ]);
    }
}
