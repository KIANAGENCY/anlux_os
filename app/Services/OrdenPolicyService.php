<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Support\AnluxAuthContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDO;
use RuntimeException;

final class OrdenPolicyService
{
    public function __construct(
        private readonly AnluxVaultService $vault
    ) {}

    private function looksSealedName(?string $value): bool
    {
        $v = trim((string) $value);

        return $v !== '' && str_starts_with($v, 'v1:');
    }

    public function sharedOrdersEnabled(): bool
    {
        return (bool) config('anlux.auth_shared_orders', false);
    }

    public function normalizeTecnicoNombre(?string $s): string
    {
        $s = trim((string) $s);
        if ($s === '') {
            return '';
        }
        $s = preg_replace('/\s+/u', ' ', $s);

        return mb_strtolower($s, 'UTF-8');
    }

    public function userIsAdmin(?User $user): bool
    {
        if (! $user) {
            return false;
        }
        $perfil = mb_strtolower(trim((string) ($user->perfil ?? '')), 'UTF-8');

        return $perfil === 'administrador' || $perfil === 'admin';
    }

    /** Expresión SQL para comparar nombres normalizados (alias.columna). */
    public function nameCompareExpr(PDO $pdo, string $qualifiedColumn): string
    {
        if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*\.[A-Za-z_][A-Za-z0-9_]*$/', $qualifiedColumn)) {
            return 'LOWER(TRIM(COALESCE('.$qualifiedColumn.", '')))";
        }

        static $regexpReplaceOk = [];
        $pdoId = spl_object_id($pdo);
        if (! array_key_exists($pdoId, $regexpReplaceOk)) {
            try {
                $pdo->query("SELECT REGEXP_REPLACE('a  b', '[[:space:]]+', ' ') AS _anlux_regexp_probe");
                $regexpReplaceOk[$pdoId] = true;
            } catch (\Throwable) {
                $regexpReplaceOk[$pdoId] = false;
            }
        }
        $useRegexp = $regexpReplaceOk[$pdoId];
        $inner = 'TRIM(COALESCE('.$qualifiedColumn.", ''))";
        if ($useRegexp) {
            return 'LOWER(REGEXP_REPLACE('.$inner.", '[[:space:]]+', ' '))";
        }

        return 'LOWER('.$inner.')';
    }

    public function ensureAuditTable(): void
    {
        if (! Schema::hasTable('orden_servicio_audit_log')) {
            throw new RuntimeException('Falta la tabla orden_servicio_audit_log. Ejecuta las migraciones pendientes.');
        }
    }

    private function readableTecnicoNombre(?string $value): string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return '';
        }

        if ($this->looksSealedName($raw)) {
            $revealed = trim($this->vault->revealString($raw, false));
            if ($revealed !== '' && ! $this->looksSealedName($revealed)) {
                return $revealed;
            }
        }

        return $raw;
    }

    private function actorTecnicoNombre(?User $user): string
    {
        $plain = trim(AnluxAuthContext::nombreTecnicoSesionActual($user));
        if ($plain === '' && $user !== null) {
            $plain = trim(AnluxAuthContext::nombreTecnicoParaRegistro($user));
        }
        if ($plain !== '') {
            return $this->normalizeTecnicoNombre($plain);
        }

        $storedName = $user?->getRawOriginal('nombre_tecnico');
        if (trim((string) $storedName) === '') {
            $storedName = $user?->nombre_tecnico;
        }

        return $this->normalizeTecnicoNombre($this->readableTecnicoNombre($storedName));
    }

    /**
     * Valores para comparar nombre de técnico en SQL (texto legible y cifrado v1:).
     *
     * @return array{norm: list<string>, exact: list<string>}
     */
    private function tecnicoMatchTokens(?User $user): array
    {
        $norm = [];
        $exact = [];

        $candidates = [];
        $sesion = trim(AnluxAuthContext::nombreTecnicoSesionActual($user));
        if ($sesion !== '') {
            $candidates[] = $sesion;
        }
        if ($user !== null) {
            $registro = trim(AnluxAuthContext::nombreTecnicoParaRegistro($user));
            if ($registro !== '') {
                $candidates[] = $registro;
            }
            $stored = $this->readableTecnicoNombre($user->getAttributes()['nombre_tecnico'] ?? $user->nombre_tecnico);
            if ($stored !== '') {
                $candidates[] = $stored;
            }
        }

        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);
            if ($candidate === '') {
                continue;
            }
            $normalized = $this->normalizeTecnicoNombre($candidate);
            if ($normalized !== '') {
                $norm[$normalized] = $normalized;
            }
            if (str_starts_with($candidate, 'v1:')) {
                $exact[$candidate] = $candidate;
            } else {
                $sealed = trim($this->vault->tecnicoNombreSeal($candidate));
                if ($sealed !== '' && str_starts_with($sealed, 'v1:')) {
                    $exact[$sealed] = $sealed;
                }
            }
        }

        return [
            'norm' => array_values($norm),
            'exact' => array_values($exact),
        ];
    }

    /** @param array{norm: list<string>, exact: list<string>} $tokens */
    private function sqlColumnMatchesTokens(PDO $pdo, string $qualifiedColumn, array $tokens): string
    {
        $parts = [];
        foreach ($tokens['norm'] as $value) {
            $parts[] = $this->nameCompareExpr($pdo, $qualifiedColumn).' = ?';
        }
        foreach ($tokens['exact'] as $value) {
            $parts[] = $qualifiedColumn.' = ?';
        }

        if ($parts === []) {
            return '0 = 1';
        }

        return '('.implode(' OR ', $parts).')';
    }

    /** @param array{norm: list<string>, exact: list<string>} $tokens @return list<string> */
    private function sqlMatchParams(array $tokens): array
    {
        return array_merge($tokens['norm'], $tokens['exact']);
    }

    /** @param array{norm: list<string>, exact: list<string>} $tokens */
    private function appendVisibilityMatch(string &$sql, array &$params, PDO $pdo, array $tokens): void
    {
        $cabMatch = $this->sqlColumnMatchesTokens($pdo, 'c.tecnico_recibido', $tokens);
        $tRecMatch = $this->sqlColumnMatchesTokens($pdo, 't.tecnico_recibido', $tokens);
        $tEntMatch = $this->sqlColumnMatchesTokens($pdo, 't.entregado_por_tecnico', $tokens);
        $logMatch = $this->sqlColumnMatchesTokens($pdo, 'l.nombre_tecnico', $tokens);
        $audMatch = $this->sqlColumnMatchesTokens($pdo, 'a.usuario', $tokens);

        $sql .= ' AND (';
        $sql .= $cabMatch;
        $params = array_merge($params, $this->sqlMatchParams($tokens));

        $sql .= ' OR EXISTS (
          SELECT 1 FROM orden_servicio_t t WHERE t.id_orden_c = c.id_orden_c
          AND ('.$tRecMatch.' OR '.$tEntMatch.')
        )';
        $params = array_merge($params, $this->sqlMatchParams($tokens), $this->sqlMatchParams($tokens));

        if (Schema::hasTable('orden_servicio_tecnico_log')) {
            $sql .= '
        OR EXISTS (
          SELECT 1 FROM orden_servicio_tecnico_log l WHERE l.id_orden_c = c.id_orden_c
          AND '.$logMatch.'
        )';
            $params = array_merge($params, $this->sqlMatchParams($tokens));
        }

        if (Schema::hasTable('orden_servicio_audit_log')) {
            $sql .= '
        OR EXISTS (
          SELECT 1 FROM orden_servicio_audit_log a WHERE a.id_orden_c = c.id_orden_c
          AND '.$audMatch.'
        )';
            $params = array_merge($params, $this->sqlMatchParams($tokens));
        }

        $sql .= '
    )';
    }

    public function userCanAccessOrder(?User $user, int $idOrdenC): bool
    {
        if ($idOrdenC <= 0 || ! $user) {
            return false;
        }
        if ($this->userIsAdmin($user)) {
            return true;
        }
        if ($this->sharedOrdersEnabled()) {
            return true;
        }

        $nombreNorm = $this->actorTecnicoNombre($user);
        $tokens = $this->tecnicoMatchTokens($user);
        if ($nombreNorm === '' || ($tokens['norm'] === [] && $tokens['exact'] === [])) {
            return false;
        }

        $pdo = DB::connection()->getPdo();
        try {
            $cabMatch = $this->sqlColumnMatchesTokens($pdo, 'c.tecnico_recibido', $tokens);
            $tRecMatch = $this->sqlColumnMatchesTokens($pdo, 't.tecnico_recibido', $tokens);
            $tEntMatch = $this->sqlColumnMatchesTokens($pdo, 't.entregado_por_tecnico', $tokens);
            $logMatch = $this->sqlColumnMatchesTokens($pdo, 'l.nombre_tecnico', $tokens);
            $audMatch = $this->sqlColumnMatchesTokens($pdo, 'a.usuario', $tokens);

            $sql = "SELECT 1 FROM orden_servicio_c c WHERE c.id_orden_c = ?
          AND (
            {$cabMatch}
            OR EXISTS (
              SELECT 1 FROM orden_servicio_t t WHERE t.id_orden_c = c.id_orden_c
              AND (
                {$tRecMatch}
                OR {$tEntMatch}
              )
            )";

            $params = array_merge(
                [$idOrdenC],
                $this->sqlMatchParams($tokens),
                $this->sqlMatchParams($tokens),
                $this->sqlMatchParams($tokens)
            );

            if (Schema::hasTable('orden_servicio_tecnico_log')) {
                $sql .= "
            OR EXISTS (
              SELECT 1 FROM orden_servicio_tecnico_log l WHERE l.id_orden_c = c.id_orden_c
              AND {$logMatch}
            )";
                $params = array_merge($params, $this->sqlMatchParams($tokens));
            }

            if (Schema::hasTable('orden_servicio_audit_log')) {
                $sql .= "
            OR EXISTS (
              SELECT 1 FROM orden_servicio_audit_log a WHERE a.id_orden_c = c.id_orden_c
              AND {$audMatch}
            )";
                $params = array_merge($params, $this->sqlMatchParams($tokens));
            }

            $sql .= '
          )
          LIMIT 1';

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            return (bool) $stmt->fetchColumn();
        } catch (\Throwable) {
            return false;
        }
    }

    /** @return array{sql: string, params: array<int, string>} */
    public function listRestrictionSql(?User $user): array
    {
        if (! $user || $this->userIsAdmin($user) || $this->sharedOrdersEnabled()) {
            return ['sql' => '', 'params' => []];
        }

        $tokens = $this->tecnicoMatchTokens($user);
        if ($tokens['norm'] === [] && $tokens['exact'] === []) {
            return ['sql' => ' AND 1 = 0 ', 'params' => []];
        }

        $pdo = DB::connection()->getPdo();
        $sql = '';
        $params = [];
        $this->appendVisibilityMatch($sql, $params, $pdo, $tokens);

        return ['sql' => $sql, 'params' => $params];
    }
}
