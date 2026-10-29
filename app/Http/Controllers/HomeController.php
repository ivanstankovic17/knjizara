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
            ['name' => 'Katalog u bazi', 'chapter' => 5, 'done' => false],
            ['name' => 'REST API kataloga', 'chapter' => 7, 'done' => false],
            ['name' => 'Prijava i porudžbine', 'chapter' => 11, 'done' => false],
        ];

        return view('home', ['features' => $features]);
    }
}
