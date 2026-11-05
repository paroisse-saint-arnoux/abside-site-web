<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Setting;

class HomeController extends Controller
{
    public function __invoke()
    {
        return view('home', [
            'home' => Setting::get('home', []),
            'featured' => Event::featuredForHome(),
        ]);
    }
}
