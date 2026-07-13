<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Whatsapp;

class WhatsappController extends Controller
{
  public function index()
{
    // Mengambil data API key. Jika tidak ada, buat record baru yang kosong.
    $api = Whatsapp::firstOrCreate(
        ['key' => 'fonnte_api_key'],
        ['value' => '', 'group' => 'whatsapp']
    );

    return view('components.fonnte', compact('api'));
}

    public function update(Request $request)
    {
        // Sesuaikan dengan name="fonnte_token" di Blade
        $request->validate([
            'fonnte_token' => 'required'
        ]);

        try {
            Whatsapp::updateOrCreate(
                ['key' => 'fonnte_api_key'],
                [
                    'value' => $request->fonnte_token,
                    'group' => 'whatsapp'
                ]
            );

            // WAJIB RETURN JSON UNTUK AJAX
            return response()->json([
                'success' => true, 
                'message' => 'API Key berhasil disimpan!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Gagal: ' . $e->getMessage()
            ], 500);
        }
    }
}