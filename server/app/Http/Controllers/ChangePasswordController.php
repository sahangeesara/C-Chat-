<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;


class ChangePasswordController extends Controller
{
    public function process(ChangePasswordRequest $request)
    {
        return $this->getPasswordResetTableRow($request)->count()>0 ? $this->changePassword($request) : $this->tokenNotFoundResponse();
    }

    private function getPasswordResetTableRow($request): \Illuminate\Database\Query\Builder
    {
        return DB::table('password_reset_tokens')->where(['email'=>$request->email,'token'=>$request->resetToken]);
    }

    private function tokenNotFoundResponse(): \Illuminate\Http\JsonResponse
    {
        return response()->json(['error' =>'Token or Email is incorrect'],ResponseAlias:: HTTP_UNPROCESSABLE_ENTITY);
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
            ResponseAlias::HTTP_CREATED
        );
    }
}
