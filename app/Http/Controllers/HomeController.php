<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('home', [
            'demoBusiness' => Business::where('slug', DatabaseSeeder::DEMO_BUSINESS_SLUG)->first(),
        ]);
    }
}
