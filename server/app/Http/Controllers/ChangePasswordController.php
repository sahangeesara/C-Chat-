<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;


class ChangePasswordController extends Controller
{
    public function process(ChangePasswordRequest $request)
    {
        $tokenRow = $this->getPasswordResetTableRow($request);

        if (!$tokenRow) {
            return $this->tokenNotFoundResponse();
        }

        if ($this->isExpired($tokenRow->created_at)) {
            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->delete();

            return response()->json([
                'error' => 'Reset token expired. Please request a new link.',
            ], ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->changePassword($request);
    }

    private function getPasswordResetTableRow($request): ?object
    {
        return DB::table('password_reset_tokens')
            ->where(['email' => $request->email, 'token' => $request->resetToken])
            ->first();
    }

    private function isExpired($createdAt): bool
    {
        if (!$createdAt) {
            return true;
        }

        $expireMinutes = (int) config('auth.passwords.users.expire', 60);

        return Carbon::parse($createdAt)->addMinutes($expireMinutes)->isPast();
    }

    private function tokenNotFoundResponse(): \Illuminate\Http\JsonResponse
    {
        return response()->json(['error' => 'Token or Email is incorrect'], ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
    }

    private function changePassword($request)
    {
        $user = User::whereEmail($request->email)->first();

        if (!$user) {
            return response()->json([
                'error' => 'User not found',
            ], ResponseAlias::HTTP_NOT_FOUND);
        }

        $user->update([
            'password' => $request->password
        ]);

        DB::table('password_reset_tokens')
            ->where([
                'email' => $request->email,
                'token' => $request->resetToken
            ])->delete();

        return response()->json(
            ['data' => 'Password successfully changed'],
            ResponseAlias::HTTP_OK
        );
    }
}
