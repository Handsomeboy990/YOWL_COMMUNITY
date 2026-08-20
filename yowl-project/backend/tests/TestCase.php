<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Base de tous les tests, volontairement vide.
 *
 * Une version précédente créait les rôles « client » et « admin » dans son
 * setUp, pour tous les tests de la suite. Aucun test ne voyait donc jamais une
 * base sans rôles, qui est pourtant l'état exact d'une base de production
 * fraîchement migrée. Un déploiement a échoué sur ce cas que la suite
 * déclarait couvert.
 *
 * Un test qui a besoin d'un rôle le crée lui-même. C'est trois lignes de plus
 * et ça garde les préconditions visibles là où elles comptent.
 */
abstract class TestCase extends BaseTestCase
{
}
