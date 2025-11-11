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
        return view('requester.view_request_bbm', compact('title'));
    }

    public function requester_create(Request $request)
    {
        // POST : Logic untuk menampilkan halaman form create request BBM
        // Schema terlibat : permohonans
        // return : Data Permohonan berupa, Id user (pemohon), tipe BBM, jumlah tagihan, catatan pemohon

        
    }


    /**
     * =========================================
     * end Controller bagian Requester
     * =========================================
     */
    
}
