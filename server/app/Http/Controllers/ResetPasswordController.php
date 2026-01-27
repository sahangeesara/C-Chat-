<?php

namespace App\Http\Controllers;


use App\Mail\ResetPasswordMail;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Illuminate\Support\Facades\DB;

class ResetPasswordController extends Controller
{
    public function sendEmail(Request $request)
    {
        if (!$this->validateEmail($request->email)) {
            return $this->failedResponse();
        }

        $this->send($request->email);
        return $this->successResponse();
    }

    public function send($email)
    {
        try {
            $token = $this->createToken($email);
            Mail::to($email)->send(new ResetPasswordMail($token));
        } catch (\Throwable $e) {
            \Log::error($e->getMessage());
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
}

