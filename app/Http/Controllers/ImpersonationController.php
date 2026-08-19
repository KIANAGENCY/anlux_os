<?php



declare(strict_types=1);



namespace App\Http\Controllers;



use App\Models\User;

use App\Services\ImpersonationService;

use App\Services\UserPresenceService;

use App\Support\ExactoAuthContext;

use Illuminate\Http\JsonResponse;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Schema;

use Throwable;



final class ImpersonationController extends Controller

{

    public function __construct(

        private readonly ImpersonationService $impersonation,

        private readonly UserPresenceService $presence,

    ) {}



    public function cuentas(): JsonResponse

    {

        try {

            $user = $this->requireUser();



            return response()->json([

                'success' => true,

                'data' => $this->impersonation->listAccountsForSwitchSelect($user),

                'presence' => [

                    'tracking' => $this->presence->trackingEnabled(),

                    'online_minutes' => $this->presence->onlineMinutes(),

                ],

            ]);

        } catch (Throwable $e) {

            report($e);



            return response()->json([

                'success' => false,

                'message' => $this->cuentasErrorMessage($e),

            ], 500);

        }

    }



    private function cuentasErrorMessage(Throwable $e): string

    {

        if (! Schema::hasColumn('login', 'last_seen_at')) {

            return 'Falta la columna last_seen_at en la tabla login. En phpMyAdmin ejecuta: ALTER TABLE login ADD COLUMN last_seen_at DATETIME NULL;';

        }



        return 'No se pudieron cargar las cuentas. Revisa storage/logs/laravel.log en el servidor.';

    }



    /** @deprecated Usar cuentas() */

    public function tecnicosActivos(): JsonResponse

    {

        return $this->cuentas();

    }



    public function solicitar(Request $request): JsonResponse

    {

        try {

            $user = $this->requireUser();

            $targetId = (int) $request->input('target_id', 0);

            $result = $this->impersonation->createRequest($user, $targetId);



            return response()->json($result, $result['success'] ? 200 : 422);

        } catch (Throwable $e) {

            report($e);



            return response()->json(['success' => false, 'message' => 'Error al solicitar acceso.'], 500);

        }

    }



    public function estado(string $token): JsonResponse

    {

        $user = $this->requireUser();

        $result = $this->impersonation->requestStatus($user, $token);



        return response()->json($result, $result['success'] ? 200 : 422);

    }



    public function aplicar(Request $request): JsonResponse

    {

        $user = $this->requireUser();

        $token = (string) $request->input('token', '');

        $result = $this->impersonation->applyApprovedSession($user, $token);



        return response()->json($result, $result['success'] ? 200 : 422);

    }



    public function cancelar(Request $request): JsonResponse

    {

        $user = $this->requireUser();

        $token = (string) $request->input('token', '');

        $result = $this->impersonation->cancelRequestByToken($user, $token);



        return response()->json($result, $result['success'] ? 200 : 422);

    }



    public function pendientes(): JsonResponse

    {

        try {

            $user = $this->requireUser();



            return response()->json([

                'success' => true,

                'data' => $this->impersonation->pendingForTarget($user),

            ]);

        } catch (Throwable $e) {

            report($e);



            return response()->json(['success' => true, 'data' => []]);

        }

    }



    public function responder(Request $request): JsonResponse

    {

        $user = $this->requireUser();

        $requestId = (int) $request->input('request_id', 0);

        $approve = filter_var($request->input('approve'), FILTER_VALIDATE_BOOL);

        $result = $this->impersonation->respond($user, $requestId, $approve);



        return response()->json($result, $result['success'] ? 200 : 422);

    }



    public function salir(): JsonResponse

    {

        $this->requireUser();

        $result = $this->impersonation->endImpersonation();



        return response()->json($result, $result['success'] ? 200 : 422);

    }



    private function requireUser(): User

    {

        $user = ExactoAuthContext::currentUser();

        abort_unless($user !== null, 403);



        return $user;

    }

}

