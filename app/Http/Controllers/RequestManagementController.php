<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RequestManagementController extends Controller
{
    /**
     *
     * =========================================
     *
     * Controller bagian Requester
     *
     * =========================================
     *
     * action : - Melakukan Request
     *          - Melihat status request
     *          - Melakukan Laporan dari proses request
     *
     * */

    public function requester_index()
    {
        // GET : Logic untuk menampilkan halaman dashboard requester
        $title = "Dashboard Requester";
        $activeRequests = 1;
        $pendingCount = 2;
        $approvedCount = 3;
        $rejectedCount = 4;
        $totalRequests = 10;
        return view('requester.view_request_bbm', compact('title', 'activeRequests','pendingCount','approvedCount','rejectedCount','totalRequests'));
    }

    public function requester_create(Request $request)
    {
        // POST : Logic untuk menampilkan halaman form create request BBM
        // Schema terlibat : permohonans
        // return : Data Permohonan berupa, Id user (pemohon), tipe BBM, jumlah tagihan, catatan pemohon

        dd($request->all());

    }


    /**
     * =========================================
     * end Controller bagian Requester
     * =========================================
     */

}
