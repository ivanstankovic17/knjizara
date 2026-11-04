<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Početna stranica: šta knjižara već ume, a šta tek dolazi.
     */
    public function __invoke(): View
    {
        $features = [
            ['name' => 'API status', 'chapter' => 3, 'done' => true],
            ['name' => 'Middleware i jezik', 'chapter' => 4, 'done' => true],
            ['name' => 'Katalog u bazi', 'chapter' => 5, 'done' => true],
            ['name' => 'REST API kataloga', 'chapter' => 7, 'done' => true],
            ['name' => 'Validacija i format grešaka', 'chapter' => 8, 'done' => true],
            ['name' => 'JavaScript klijent: katalog i korpa', 'chapter' => 9, 'done' => true],
            ['name' => 'Prijava i porudžbine', 'chapter' => 11, 'done' => false],
        ];

        return view('home', ['features' => $features]);
    }
}
