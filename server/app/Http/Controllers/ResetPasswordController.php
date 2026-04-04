<?php

namespace App\Http\Controllers;


use App\Mail\ResetPasswordMail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResetPasswordController extends Controller
{
    public function sendEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        if (!$this->validateEmail($request->email)) {
            return $this->failedResponse();
        }

        return $this->send($request->email)
            ? $this->successResponse()
            : response()->json([
                'error' => 'Unable to send reset email. Please try again later.',
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function send($email)
    {
        try {
            $token = $this->createToken($email);
            Mail::to($email)->send(new ResetPasswordMail($token));
            return true;
        } catch (\Throwable $e) {
            Log::error('Reset password mail failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }


    public function createToken($email)
    {
        $oldToken = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if ($oldToken) {
            return $oldToken->token;
        }

        $token = Str::random(60);
        $this->saveToken($token, $email);

        return $token;
    }

    public function saveToken($token, $email)
    {
        DB::table('password_reset_tokens')->insert([
            'email' => $email,
            'token' => $token,
            'created_at' => Carbon::now()
        ]);
    }

    public function validateEmail($email): bool
    {
        return User::where('email', $email)->exists();
    }

    private function failedResponse()
    {
        return response()->json([
            'error' => 'Email not found',
        ], ResponseAlias::HTTP_NOT_FOUND);
    }

    private function successResponse()
    {
        return response()->json([
            'data' => 'Password reset link sent',
        ], ResponseAlias::HTTP_OK);
    }
}

