<?php

namespace Tests\Feature\Dokter;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\ActsAsSuperAdmin;
use Tests\TestCase;

class FileManagementSearchTest extends TestCase
{
    use ActsAsSuperAdmin, RefreshDatabase;

    private const FOLDER = 'DUTA WAHANA PERSADA';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('ftp_final');

        Permission::firstOrCreate([
            'name' => 'dokter.file-managements.validate',
            'guard_name' => 'web',
        ]);

        $this->setUpSuperAdmin();

        Storage::disk('ftp_final')->put(self::FOLDER.'/invoice-2512014.pdf', '%PDF-1.4 invoice');
        Storage::disk('ftp_final')->put(self::FOLDER.'/approval-724.pdf', '%PDF-1.4 approval');
        Storage::disk('ftp_final')->put(self::FOLDER.'/catatan.txt', 'catatan');
    }

    public function test_file_table_shows_search_input_and_all_files_without_filter(): void
    {
        $response = $this->get(route('dokter.file-managements.index', ['path' => self::FOLDER]));

        $response->assertOk()
            ->assertSee('Cari nama file')
            ->assertSee('invoice-2512014.pdf')
            ->assertSee('approval-724.pdf')
            ->assertSee('catatan.txt');
    }

    public function test_file_table_can_be_filtered_by_filename(): void
    {
        $response = $this->get(route('dokter.file-managements.index', [
            'path' => self::FOLDER,
            'search' => 'invoice',
        ]));

        $response->assertOk()
            ->assertSee('Cari nama file')
            ->assertSee('invoice-2512014.pdf')
            ->assertDontSee('approval-724.pdf')
            ->assertDontSee('catatan.txt')
            ->assertSee('1 dari 3 file');
    }

    public function test_filename_search_is_case_insensitive(): void
    {
        $response = $this->get(route('dokter.file-managements.index', [
            'path' => self::FOLDER,
            'search' => 'APPROVAL',
        ]));

        $response->assertOk()
            ->assertSee('approval-724.pdf')
            ->assertDontSee('invoice-2512014.pdf');
    }

    public function test_search_form_stays_visible_when_no_file_matches(): void
    {
        $response = $this->get(route('dokter.file-managements.index', [
            'path' => self::FOLDER,
            'search' => 'tidak-ada-file-ini',
        ]));

        $response->assertOk()
            ->assertSee('Cari nama file')
            ->assertSee('tidak ditemukan')
            ->assertDontSee('invoice-2512014.pdf')
            ->assertDontSee('approval-724.pdf');
    }

    public function test_root_page_does_not_show_file_search(): void
    {
        $response = $this->get(route('dokter.file-managements.index'));

        $response->assertOk()
            ->assertSee(self::FOLDER)
            ->assertDontSee('Cari nama file');
    }
}
