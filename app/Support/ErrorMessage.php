<?php

namespace App\Support;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ErrorMessage
{
    /**
     * Build a human readable message from a throwable.
     *
     * ValidationException hardcodes its message to "The given data was invalid."
     * so the actionable per-field messages have to be pulled from errors().
     */
    public static function from(Throwable $e): string
    {
        if ($e instanceof ValidationException) {
            $messages = collect($e->errors())
                ->flatten()
                ->filter()
                ->unique()
                ->values();

            if ($messages->isNotEmpty()) {
                return $messages->implode('; ');
            }
        }

        if ($e instanceof ModelNotFoundException) {
            return 'Data yang dimaksud tidak ditemukan.';
        }

        if ($e instanceof AuthenticationException) {
            return 'Sesi Anda telah berakhir. Silakan masuk kembali.';
        }

        if ($e instanceof AuthorizationException) {
            return trim($e->getMessage()) !== '' ? $e->getMessage() : 'Anda tidak memiliki izin untuk tindakan ini.';
        }

        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();

            return $status === 403
                ? 'Anda tidak memiliki izin untuk mengakses data ini.'
                : ($e->getMessage() !== '' ? $e->getMessage() : 'Terjadi kesalahan pada server.');
        }

        $message = trim($e->getMessage());

        return $message !== '' ? $message : 'Terjadi kesalahan yang tidak diketahui.';
    }
}
