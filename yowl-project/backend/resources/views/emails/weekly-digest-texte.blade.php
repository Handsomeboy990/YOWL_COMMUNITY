Bonjour {{ $user->username }},

@if($reviews->isNotEmpty())
Voici ce qui a fait parler sur YOWL cette semaine :

@foreach($reviews as $review)
- {{ \Illuminate\Support\Str::limit(trim(strip_tags($review->content)), 110) }}
  {{ rtrim(config('app.frontend_url'), '/') }}/reviews/{{ $review->id }}

@endforeach
@else
La semaine a ete calme sur les sujets que tu suis. Passe voir le fil, il s'y
publie de nouveaux avis tous les jours.

{{ rtrim(config('app.frontend_url'), '/') }}/feed
@endif

--
Pour ne plus recevoir ce resume : {{ $unsubscribeUrl }}
