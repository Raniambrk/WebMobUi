<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use App\Models\PollVote;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiPollController extends Controller
{
    /**
     * Display a listing of the authenticated user's polls.
     */
    public function index(Request $request)
    {
        $polls = $request->user()->polls()->orderBy('created_at', 'desc')->get();

        return $polls;
    }

    /**
     * Display the specified poll by its secret token.
     * Accessible sans authentification, mais le contenu varie selon les droits.
     */
    public function show(Request $request, string $token)
    {
        // On charge le sondage avec ses options et le nombre de votes par option
        $poll = Poll::with(['options' => function ($query) {
            $query->withCount('votes');
        }])->where('secret_token', $token)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        // Ajout : vérifier si l'utilisateur connecté est le propriétaire
        $user    = $request->user();
        $isOwner = $user && $user->id === $poll->user_id;

        // Un sondage en brouillon n'est visible que par son créateur
        if ($poll->is_draft && !$isOwner) {
            return response()->json(['message' => 'Sondage non disponible.'], 403);
        }

        // Les résultats sont visibles si publics ou si on est le propriétaire
        $showResults = $poll->results_public || $isOwner;

        // Ajout : récupérer les options pour lesquelles l'utilisateur a déjà voté
        $userVoteOptionIds = [];
        if ($user) {
            $userVoteOptionIds = PollVote::where('poll_id', $poll->id)
                ->where('user_id', $user->id)
                ->pluck('poll_option_id')
                ->toArray();
        }

        // On construit la réponse avec les infos supplémentaires pour le frontend
        $pollData                          = $poll->toArray();
        $pollData['is_owner']              = $isOwner;
        $pollData['show_results']          = $showResults;
        $pollData['user_vote_option_ids']  = $userVoteOptionIds;
        $pollData['is_expired']            = $poll->ends_at && now()->isAfter($poll->ends_at);

        // Si les résultats ne sont pas publics, on masque les compteurs de votes
        if (!$showResults) {
            foreach ($pollData['options'] as &$opt) {
                $opt['votes_count'] = null;
            }
        }

        return response()->json($pollData);
    }

    /**
     * Remove the specified poll.
     */
    public function remove(Request $request, int $id)
    {
        $poll = Poll::where('id', $id)->where('user_id', $request->user()->id)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        $poll->delete();

        return response()->json(['message' => 'success'], 200);
    }

    /**
     * Ajout : créer un nouveau sondage avec ses options et paramètres.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title'                  => 'nullable|string|max:255',
            'question'               => 'required|string|max:500',
            'options'                => 'required|array|min:2',
            'options.*.label'        => 'required|string|max:255',
            'is_draft'               => 'boolean',
            'allow_multiple_choices' => 'boolean',
            'allow_vote_change'      => 'boolean',
            'results_public'         => 'boolean',
            'duration'               => 'nullable|integer|min:1',
            'start_now'              => 'boolean',
        ]);

        $poll                         = new Poll();
        $poll->user_id                = $request->user()->id;
        $poll->title                  = $data['title'] ?? null;
        $poll->question               = $data['question'];
        $poll->secret_token           = Str::random(32); // token unique pour partager le sondage
        $poll->is_draft               = $data['is_draft'] ?? true;
        $poll->allow_multiple_choices = $data['allow_multiple_choices'] ?? false;
        $poll->allow_vote_change      = $data['allow_vote_change'] ?? false;
        $poll->results_public         = $data['results_public'] ?? false;
        $poll->duration               = $data['duration'] ?? null;

        // Si on lance directement (pas brouillon), on enregistre la date de début et de fin
        if (!$poll->is_draft && ($data['start_now'] ?? false)) {
            $poll->started_at = now();
            if ($poll->duration) {
                $poll->ends_at = now()->addSeconds($poll->duration);
            }
        }

        $poll->save();

        // On crée chaque option associée au sondage
        foreach ($data['options'] as $opt) {
            $poll->options()->create(['label' => $opt['label']]);
        }

        return response()->json($poll->load('options'), 201);
    }

    /**
     * Ajout : modifier un sondage existant (propriétaire uniquement).
     */
    public function update(Request $request, int $id)
    {
        $poll = Poll::where('id', $id)->where('user_id', $request->user()->id)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        $data = $request->validate([
            'title'                  => 'nullable|string|max:255',
            'question'               => 'sometimes|required|string|max:500',
            'options'                => 'sometimes|array|min:2',
            'options.*.id'           => 'nullable|integer',
            'options.*.label'        => 'required|string|max:255',
            'is_draft'               => 'boolean',
            'allow_multiple_choices' => 'boolean',
            'allow_vote_change'      => 'boolean',
            'results_public'         => 'boolean',
            'duration'               => 'nullable|integer|min:1',
            'start_now'              => 'boolean',
        ]);

        // On met à jour uniquement les champs envoyés
        if (array_key_exists('title', $data))           $poll->title = $data['title'];
        if (isset($data['question']))                    $poll->question = $data['question'];
        if (isset($data['allow_multiple_choices']))      $poll->allow_multiple_choices = $data['allow_multiple_choices'];
        if (isset($data['allow_vote_change']))           $poll->allow_vote_change = $data['allow_vote_change'];
        if (isset($data['results_public']))              $poll->results_public = $data['results_public'];
        if (array_key_exists('duration', $data))        $poll->duration = $data['duration'];

        // Gestion du lancement : si on passe de brouillon à actif
        if (isset($data['is_draft'])) {
            $wasDraft       = $poll->is_draft;
            $poll->is_draft = $data['is_draft'];

            if ($wasDraft && !$poll->is_draft && !$poll->started_at) {
                $poll->started_at = now();
                if ($poll->duration) {
                    $poll->ends_at = now()->addSeconds($poll->duration);
                }
            }
        }

        $poll->save();

        // Mise à jour des options : on garde celles qui existent, on crée les nouvelles, on supprime les retirées
        if (isset($data['options'])) {
            $keepIds = [];
            foreach ($data['options'] as $opt) {
                if (!empty($opt['id'])) {
                    $option = $poll->options()->find($opt['id']);
                    if ($option) {
                        $option->update(['label' => $opt['label']]);
                        $keepIds[] = $option->id;
                    }
                } else {
                    $new       = $poll->options()->create(['label' => $opt['label']]);
                    $keepIds[] = $new->id;
                }
            }
            $poll->options()->whereNotIn('id', $keepIds)->delete();
        }

        return response()->json($poll->load('options'));
    }

    /**
     * Ajout : enregistrer le vote d'un utilisateur authentifié sur un sondage.
     */
    public function vote(Request $request, string $token)
    {
        $poll = Poll::with('options')->where('secret_token', $token)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        // On ne peut pas voter sur un brouillon ou un sondage terminé
        if ($poll->is_draft) {
            return response()->json(['message' => 'Sondage pas encore actif.'], 403);
        }

        if ($poll->ends_at && now()->isAfter($poll->ends_at)) {
            return response()->json(['message' => 'Sondage terminé.'], 403);
        }

        $data = $request->validate([
            'option_ids'   => 'required|array|min:1',
            'option_ids.*' => 'integer',
        ]);

        $optionIds = $data['option_ids'];
        $validIds  = $poll->options->pluck('id')->toArray();

        // Vérification que les options envoyées appartiennent bien à ce sondage
        foreach ($optionIds as $oid) {
            if (!in_array($oid, $validIds)) {
                return response()->json(['message' => 'Option invalide.'], 422);
            }
        }

        // Unicité du vote côté API : un seul choix si le sondage ne permet pas le multi
        if (!$poll->allow_multiple_choices && count($optionIds) > 1) {
            return response()->json(['message' => 'Un seul choix autorisé.'], 422);
        }

        $userId        = $request->user()->id;
        $existingVotes = PollVote::where('poll_id', $poll->id)->where('user_id', $userId)->get();

        if ($existingVotes->isNotEmpty()) {
            // Si le vote n'est pas modifiable, on bloque
            if (!$poll->allow_vote_change) {
                return response()->json(['message' => 'Vous avez déjà voté.'], 403);
            }
            // Sinon on supprime l'ancien vote pour le remplacer
            PollVote::where('poll_id', $poll->id)->where('user_id', $userId)->delete();
        }

        // On enregistre chaque option choisie
        foreach ($optionIds as $oid) {
            PollVote::create([
                'poll_id'        => $poll->id,
                'user_id'        => $userId,
                'poll_option_id' => $oid,
            ]);
        }

        return response()->json(['message' => 'Vote enregistré.']);
    }

    /**
     * Ajout : retourner les résultats en direct d'un sondage (utilisé pour le polling frontend).
     */
    public function results(Request $request, string $token)
    {
        $poll = Poll::with(['options' => function ($q) {
            $q->withCount('votes');
        }])->where('secret_token', $token)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        $user    = $request->user();
        $isOwner = $user && $user->id === $poll->user_id;

        // Seuls le propriétaire ou les sondages publics peuvent voir les résultats
        if (!$poll->results_public && !$isOwner) {
            return response()->json(['message' => 'Résultats non publics.'], 403);
        }

        $totalVotes = $poll->options->sum('votes_count');

        // On calcule le pourcentage de chaque option
        $options = $poll->options->map(function ($opt) use ($totalVotes) {
            return [
                'id'          => $opt->id,
                'label'       => $opt->label,
                'votes_count' => $opt->votes_count,
                'percentage'  => $totalVotes > 0 ? round(($opt->votes_count / $totalVotes) * 100, 1) : 0,
            ];
        });

        return response()->json([
            'total_votes' => $totalVotes,
            'options'     => $options,
            'is_expired'  => $poll->ends_at && now()->isAfter($poll->ends_at),
        ]);
    }
}