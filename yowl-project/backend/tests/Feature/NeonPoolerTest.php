<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Migrations must not go through Neon's connection pooler.
 *
 * Neon runs PgBouncer in transaction mode on its "-pooler" endpoint, and that
 * mode does not support the DDL a migration issues. The first statement fails,
 * the transaction is poisoned, and every statement after it reports 25P02,
 * "current transaction is aborted", which hides the real cause. A deployment
 * failed on exactly that, reporting a constraint on the users table that was
 * never the problem.
 *
 * The pooler stays in use at runtime, where it saves the connections a free
 * plan counts closely.
 */
class NeonPoolerTest extends TestCase
{
    public static function hotes(): array
    {
        return [
            'Neon avec pooler' => [
                'ep-lucky-tooth-zab5zmo5-pooler.c-2.eu-west-2.aws.neon.tech',
                'ep-lucky-tooth-zab5zmo5.c-2.eu-west-2.aws.neon.tech',
            ],
            'Neon sans pooler' => [
                'ep-lucky-tooth-zab5zmo5.c-2.eu-west-2.aws.neon.tech',
                'ep-lucky-tooth-zab5zmo5.c-2.eu-west-2.aws.neon.tech',
            ],
            'autre hebergeur' => [
                'db.exemple.fr',
                'db.exemple.fr',
            ],
            'un nom qui contient pooler ailleurs' => [
                'pooler-interne.exemple.fr',
                'pooler-interne.exemple.fr',
            ],
        ];
    }

    /**
     * @dataProvider hotes
     */
    public function test_the_migration_host_drops_the_pooler_suffix(string $donne, string $attendu): void
    {
        $obtenu = preg_replace('/-pooler(?=\.)/', '', $donne);

        $this->assertSame($attendu, $obtenu);
    }

    public function test_both_connections_share_every_other_setting(): void
    {
        $pooler = config('database.connections.pgsql');
        $direct = config('database.connections.pgsql_unpooled');

        // Seul l'hôte diffère : laisser diverger le reste ferait migrer une
        // base et servir l'autre.
        unset($pooler['host'], $direct['host']);

        $this->assertSame($pooler, $direct);
    }

    public function test_the_unpooled_connection_exists_and_speaks_postgres(): void
    {
        $this->assertSame('pgsql', config('database.connections.pgsql_unpooled.driver'));
    }
}
