<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChartOfAccountRequest;
use App\Models\ChartOfAccount;
use App\Models\Erkap\CostCenter;
use App\Models\Erkap\CostElement;
use App\Services\Erkap\CoaOptionService;
use App\Support\CoaCode;
use App\Support\ErrorMessage;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChartOfAccountController extends Controller
{
    /**
     * Jumlah baris per statement insert saat sinkronisasi.
     */
    private const INSERT_CHUNK = 500;

    public function __construct(private CoaOptionService $options) {}

    public function index(Request $request)
    {
        $pageName = 'Chart of Accounts';

        $query = ChartOfAccount::with(['costCenter', 'costElement'])
            ->search($request->query('search'))
            ->when($request->filled('type') && in_array($request->type, ['revenue', 'expense']), function ($query) use ($request) {
                $query->where('type', $request->type);
            })
            ->orderBy('code');

        $chartOfAccounts = $query->paginate(15)->withQueryString();

        $totalRevenue = ChartOfAccount::revenue()->count();
        $totalExpense = ChartOfAccount::expense()->count();
        $coverage = $this->options->coverage();

        return view('erkap.chart-of-account.index', compact('pageName', 'chartOfAccounts', 'totalRevenue', 'totalExpense', 'coverage'));
    }

    public function create()
    {
        $pageName = 'Buat Chart of Account';

        // Pusat Biaya dirender server-side karena jumlahnya terbatas (ratusan)
        // dan menjadi titik awal rantai. Elemen Biaya diambil dari API agar
        // opsi di form create sama persis dengan sumber dropdown lain.
        $costCenters = $this->options->costCenters()
            ->map(fn (array $option) => [
                'id' => $option['id'],
                'code' => $option['code'],
                'label' => $option['label'],
            ])
            ->values();

        return view('erkap.chart-of-account.create', compact('pageName', 'costCenters'));
    }

    public function store(StoreChartOfAccountRequest $request)
    {
        try {
            ChartOfAccount::create($request->validated());

            return redirect()->route('erkap.chart-of-accounts.index')
                ->with('success', 'Chart of Account baru berhasil disimpan.');
        } catch (Exception $err) {
            return redirect()->route('erkap.chart-of-accounts.create')
                ->withInput()
                ->with('error', ErrorMessage::from($err));
        }
    }

    public function show(ChartOfAccount $chartOfAccount)
    {
        $pageName = 'Detail Chart of Account';

        $chartOfAccount->load([
            'costCenter.businessUnit',
            'costCenter.location',
            'costCenter.managementArea',
            'costCenter.activity',
            'costElement.costElementCategory',
        ]);

        // Kode dipecah menjadi segmen a (1) + b (2) + c (5) + d (3) + e (4).
        $decoded = CoaCode::parse($chartOfAccount->code);

        return view('erkap.chart-of-account.show', compact('pageName', 'chartOfAccount', 'decoded'));
    }

    /**
     * Lengkapi himpunan Chart of Account dan tautkan Elemen Biaya ke COA default.
     *
     * COA adalah hasil komposisi (Pusat Biaya, Elemen Biaya), jadi sinkronisasi
     * berarti membuat baris yang belum ada untuk kombinasi tersebut — bukan
     * lagi menyamakan kode padding. Proses ini idempoten: dijalankan dua kali
     * tidak menghasilkan baris tambahan.
     */
    public function sync()
    {
        try {
            $report = DB::transaction(fn () => [
                'created' => $this->createMissingCombinations(),
                'linked' => $this->linkDefaultAccounts(),
            ]);

            $coverage = $this->options->coverage();

            $message = "Sinkronisasi selesai. {$report['created']} Chart of Account dibuat, "
                ."{$report['linked']} Elemen Biaya ditautkan ke COA default. "
                ."Kombinasi terisi {$coverage['filled']} dari {$coverage['expected']} "
                ."({$coverage['percent']}%) untuk {$coverage['costCenters']} Pusat Biaya × {$coverage['costElements']} Elemen Biaya.";

            if ($coverage['missing'] > 0) {
                $message .= " Masih ada {$coverage['missing']} kombinasi kosong.";
            }

            return redirect()->route('erkap.chart-of-accounts.index')
                ->with('success', $message);
        } catch (Exception $err) {
            return redirect()->route('erkap.chart-of-accounts.index')
                ->with('error', ErrorMessage::from($err));
        }
    }

    /**
     * Buat COA untuk setiap kombinasi (Pusat Biaya, Elemen Biaya) yang belum ada.
     */
    private function createMissingCombinations(): int
    {
        $existing = $this->existingCombinations();

        $costCenters = CostCenter::with(['businessUnit', 'location', 'managementArea', 'activity'])
            ->orderBy('code')
            ->get();

        $elements = CostElement::query()
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        // Tipe di-resolve sekali per elemen, bukan per kombinasi, supaya sync
        // 16.700 baris tidak memicu 16.700 query tambahan.
        $types = $this->elementTypes($elements);

        $created = 0;
        $rows = [];

        foreach ($costCenters as $costCenter) {
            $segments = $costCenter->segments();
            $prefixName = $costCenter->name;

            foreach ($elements as $element) {
                $key = $costCenter->id.'-'.$element->id;

                if ($existing->has($key)) {
                    continue;
                }

                $existing->put($key, true);

                $code = CoaCode::compose($segments + ['cost_element' => $element->code]);

                if (! $code) {
                    continue;
                }

                $rows[] = [
                    'code' => $code,
                    'cost_center_id' => $costCenter->id,
                    'cost_element_id' => $element->id,
                    'name' => Str::limit($element->name.' — '.$prefixName, 252, '...'),
                    'type' => $types[$element->id],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $created++;

                if (count($rows) >= self::INSERT_CHUNK) {
                    DB::table('chart_of_accounts')->insert($rows);
                    $rows = [];
                }
            }
        }

        if ($rows) {
            DB::table('chart_of_accounts')->insert($rows);
        }

        return $created;
    }

    /**
     * Pastikan setiap Elemen Biaya punya satu COA default (`chart_of_account_id`).
     */
    private function linkDefaultAccounts(): int
    {
        $linked = 0;

        CostElement::query()
            ->whereNull('chart_of_account_id')
            ->orderBy('id')
            ->chunkById(200, function ($elements) use (&$linked) {
                foreach ($elements as $element) {
                    $account = $element->chartOfAccounts()->orderBy('code')->first();

                    if (! $account) {
                        continue;
                    }

                    $element->update(['chart_of_account_id' => $account->id]);
                    $linked++;
                }
            });

        return $linked;
    }

    /**
     * Kombinasi yang sudah terisi, di-key "costCenterId-costElementId".
     *
     * @return Collection<string, bool>
     */
    private function existingCombinations(): Collection
    {
        return ChartOfAccount::query()
            ->whereNotNull('cost_center_id')
            ->whereNotNull('cost_element_id')
            ->select('cost_center_id', 'cost_element_id')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->cost_center_id.'-'.$row->cost_element_id => true]);
    }

    /**
     * Tipe COA per elemen, diambil dari COA tertua yang memakainya agar satu
     * elemen biaya konsisten di semua Pusat Biaya. Default `expense` mengikuti
     * konvensi tabel.
     *
     * @param  Collection<int, CostElement>  $elements
     * @return array<int, string>
     */
    private function elementTypes(Collection $elements): array
    {
        return $elements
            ->mapWithKeys(fn (CostElement $element) => [
                $element->id => $element->chartOfAccounts()->orderBy('code')->value('type') ?: 'expense',
            ])
            ->all();
    }
}
