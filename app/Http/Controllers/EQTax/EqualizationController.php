<?php

namespace App\Http\Controllers\EQTax;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EqualizationController extends Controller
{
    public function index()
    {
        $pageName = "Ekualisasi Pajak";
        $sql = "
    WITH gl_agg AS (
        SELECT
            TRIM(LEADING '0' FROM TRIM(no_faktur_pajak)) AS no_faktur_pajak,
            SUM(dpp) AS dpp_gl,
            SUM(ppn) AS ppn_gl
        FROM eqtax_gl
        GROUP BY TRIM(LEADING '0' FROM TRIM(no_faktur_pajak))
    ),
    spt_norm AS (
        SELECT
            TRIM(LEADING '0' FROM TRIM(no_faktur_pajak)) AS no_faktur_pajak,
            dpp AS dpp_spt,
            ppn AS ppn_spt
        FROM eqtax_coretax_spt
    )
    SELECT
        COALESCE(spt.no_faktur_pajak, gl.no_faktur_pajak) AS no_faktur_pajak,
        spt.dpp_spt,
        gl.dpp_gl,
        spt.ppn_spt,
        gl.ppn_gl,
        COALESCE(spt.ppn_spt, 0) - COALESCE(gl.ppn_gl, 0) AS selisih_ppn
    FROM spt_norm AS spt
    FULL OUTER JOIN gl_agg AS gl
        ON spt.no_faktur_pajak = gl.no_faktur_pajak
";

        $data = DB::select($sql);
        return view('eqtax.equalization.index', compact("pageName", "data"));
    }

    public function equalization() {}
}
