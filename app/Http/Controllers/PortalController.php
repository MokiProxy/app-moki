<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Slider;
use Illuminate\Support\Facades\Auth;

class PortalController extends Controller
{
    /**
     * Konstruktor untuk memastikan keamanan akses portal.
     */
    public function __construct()
    {
        // Update: Menambahkan except('index') agar bisa diakses tanpa login (untuk test)
        // Jika sudah selesai testing, hapus ->except('index') agar kembali aman
        $this->middleware('auth')->except('index');
    }

    /**
     * Menampilkan Halaman Utama Portal.
     */
    public function index()
    {
        // 1. Data Menu Aplikasi
        $menus = [
            [
                'title' => 'IT Admin',
                'sub'   => 'IT Admin Panel',
                'icon'  => 'mdi-cog',
                'color' => '#556EE6',
                'permission' => 'it-admin.access',
                'link'  => route('it-admin.index')
            ],
            [
                'title' => 'AMS',
                'sub'   => 'Asset Management',
                'icon'  => 'mdi-database-settings',
                'color' => '#4e73df',
                'permission' => 'ams.menu',
                'link'  => route('transaction.index')
            ],
            [
                'title' => 'HELPDESK',
                'sub'   => 'IT Support Ticket',
                'icon'  => 'mdi-face-agent',
                'color' => '#1cc88a',
                'permission' => 'helpdesk.menu',
                'link'  => route('helpdesk.index')
            ],
            [
                'title' => 'DATA PEGAWAI',
                'sub'   => 'Data Pegawai SBS',
                'icon'  => 'mdi-account',
                'color' => '#FF8F00',
                'permission' => 'data-pegawai.menu',
                'link'  => '#'
                ],
            [
                'title' => 'FORM IT',
                'sub'   => 'Digital Request',
                'icon'  => 'mdi-file-document-edit',
                'color' => '#36b9cc',
                'permission' => 'form-it.menu',
                'link'  => '#'
            ],
            [
                'title' => 'SOP IT',
                'sub'   => 'Standard Procedure',
                'icon'  => 'mdi-book-open-variant',
                'color' => '#f6c23e',
                'permission' => 'sop-it.menu',
                'link'  => '#'
            ],
            [
                'title' => 'DOKTER',
                'sub'   => 'Dokumen Kontroler',
                'icon'  => 'mdi-file',
                'color' => '#f6c23e',
                'permission' => 'sop-it.menu',
                'link'  => '#'
                ],
                [
                    'title' => 'MORE',
                    'sub'   => 'Other Apps',
                    'icon'  => 'mdi-apps',
                    'color' => '#858796',
                    'permission' => 'sop-it.menu',
                'link'  => '#'
            ],
        ];

        // 2. Data Slider (Dinamis dengan Fallback ke Dummy)
        $sliders = $this->getSliders();

        // 3. Render View dari folder resources/views/portal/portal.blade.php
        return view('portal', compact('menus', 'sliders')); // Tanpa awalan portal.
    }

    /**
     * Mengambil data slider dari database atau data dummy.
     */
    private function getSliders()
    {
        try {
            // Cek apakah tabel slider ada dan memiliki data
            if (class_exists('App\Models\Slider')) {
                $data = Slider::where('is_active', true)->get();
                if ($data->isNotEmpty()) {
                    return $data;
                }
            }

            // Data Dummy jika database kosong/belum ada
            return collect([
                (object)[
                    'image' => 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?auto=format&fit=crop&w=1200&q=80',
                    'title' => 'Digital Transformation',
                    'desc'  => 'Membangun ekosistem kerja yang lebih efisien dan terintegrasi.'
                ],
                (object)[
                    'image' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=1200&q=80',
                    'title' => 'AMS Monitoring',
                    'desc'  => 'Kelola aset perusahaan Anda dengan sistem monitoring yang akurat.'
                ]
            ]);
        } catch (\Exception $e) {
            // Jika terjadi error (misal table belum dimigrasi), kirim koleksi kosong agar view tidak crash
            return collect([]);
        }
    }
}
