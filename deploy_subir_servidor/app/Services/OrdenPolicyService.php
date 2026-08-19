<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDO;
use RuntimeException;

final class OrdenPolicyService
{
    private function debugLog(string $runId, string $hypothesisId, string $location, string $message, array $data = []): void {}

    public function __construct(
        private readonly ExactoVaultService $vault
    ) {}

    private function looksSealedName(?string $value): bool
    {
        $v = trim((string) $value);

        return $v !== '' && str_starts_with($v, 'v1:');
    }

    public function sharedOrdersEnabled(): bool
    {
        return (bool) config('exacto.auth_shared_orders', false);
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
                $pdo->query("SELECT REGEXP_REPLACE('a  b', '[[:space:]]+', ' ') AS _exacto_regexp_probe");
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
        $sessionName = $this->readableTecnicoNombre(session('nombre_tecnico'));
        if ($sessionName !== '') {
            return $this->normalizeTecnicoNombre($sessionName);
        }

        $storedName = $user?->getRawOriginal('nombre_tecnico');
        if (trim((string) $storedName) === '') {
            $storedName = $user?->nombre_tecnico;
        }

        return $this->normalizeTecnicoNombre($this->readableTecnicoNombre($storedName));
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
        if ($nombreNorm === '') {
            return false;
        }

        $pdo = DB::connection()->getPdo();
        try {
            $eCab = $this->nameCompareExpr($pdo, 'c.tecnico_recibido');
            $eTRec = $this->nameCompareExpr($pdo, 't.tecnico_recibido');
            $eTEnt = $this->nameCompareExpr($pdo, 't.entregado_por_tecnico');
            $eLog = $this->nameCompareExpr($pdo, 'l.nombre_tecnico');

            $sql = "SELECT 1 FROM orden_servicio_c c WHERE c.id_orden_c = ?
          AND (
            {$eCab} = ?
            OR EXISTS (
              SELECT 1 FROM orden_servicio_t t WHERE t.id_orden_c = c.id_orden_c
              AND (
                {$eTRec} = ?
                OR {$eTEnt} = ?
              )
            )";

            $params = [$idOrdenC, $nombreNorm, $nombreNorm, $nombreNorm];

            if (Schema::hasTable('orden_servicio_tecnico_log')) {
                $sql .= "
            OR EXISTS (
              SELECT 1 FROM orden_servicio_tecnico_log l WHERE l.id_orden_c = c.id_orden_c
              AND {$eLog} = ?
            )";
                $params[] = $nombreNorm;
            }

            if (Schema::hasTable('orden_servicio_audit_log')) {
                $eAud = $this->nameCompareExpr($pdo, 'a.usuario');
                $sql .= "
            OR EXISTS (
              SELECT 1 FROM orden_servicio_audit_log a WHERE a.id_orden_c = c.id_orden_c
              AND {$eAud} = ?
            )";
                $params[] = $nombreNorm;
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

        $nombreNorm = $this->actorTecnicoNombre($user);
        if ($nombreNorm === '') {
            $this->debugLog('run1', 'H5', 'app/Services/OrdenPolicyService.php:listRestrictionSql:empty', 'Empty tecnico name; forcing empty restriction', []);

            return ['sql' => ' AND 1 = 0 ', 'params' => []];
        }

        $pdo = DB::connection()->getPdo();
        $eCab = $this->nameCompareExpr($pdo, 'c.tecnico_recibido');
        $eTRec = $this->nameCompareExpr($pdo, 't.tecnico_recibido');
        $eTEnt = $this->nameCompareExpr($pdo, 't.entregado_por_tecnico');
        $eLog = $this->nameCompareExpr($pdo, 'l.nombre_tecnico');

        $sql = " AND (
        {$eCab} = ?
        OR EXISTS (
          SELECT 1 FROM orden_servicio_t t WHERE t.id_orden_c = c.id_orden_c
          AND (
            {$eTRec} = ?
            OR {$eTEnt} = ?
          )
        )";

        $params = [$nombreNorm, $nombreNorm, $nombreNorm];

        if (Schema::hasTable('orden_servicio_tecnico_log')) {
            $sql .= "
        OR EXISTS (
          SELECT 1 FROM orden_servicio_tecnico_log l WHERE l.id_orden_c = c.id_orden_c
          AND {$eLog} = ?
        )";
            $params[] = $nombreNorm;
        }

        if (Schema::hasTable('orden_servicio_audit_log')) {
            $eAud = $this->nameCompareExpr($pdo, 'a.usuario');
            $sql .= "
        OR EXISTS (
          SELECT 1 FROM orden_servicio_audit_log a WHERE a.id_orden_c = c.id_orden_c
          AND {$eAud} = ?
        )";
            $params[] = $nombreNorm;
        }

        $sql .= '
    )';

        return ['sql' => $sql, 'params' => $params];
    }
}
