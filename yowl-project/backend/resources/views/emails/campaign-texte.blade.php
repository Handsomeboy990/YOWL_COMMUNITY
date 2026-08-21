{{-- Version texte d'une campagne.

     Le corps est ecrit en HTML depuis la console d'administration : on le
     ramene a du texte plutot que de demander a l'equipe de rediger deux fois.
     Imparfait, mais un message sans version texte se fait classer en
     indesirable par la plupart des filtres. --}}
{{ $subjectLine }}

{!! trim(preg_replace("/\n{3,}/", "\n\n", html_entity_decode(strip_tags(preg_replace('/<\/(p|div|h[1-6]|li|tr|br)\s*\/?>/i', "\n", $bodyHtml))))) !!}

--
{{ $siteName }}
Pour ne plus recevoir ces messages : {{ $unsubscribeUrl }}
