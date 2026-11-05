<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MagicLinkController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/qui-sommes-nous', [PageController::class, 'about'])->name('about');
Route::get('/soutenir', [PageController::class, 'support'])->name('support');

Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'send'])->middleware('throttle:5,10')->name('contact.send');
Route::post('/newsletter', [NewsletterController::class, 'store'])->middleware('throttle:10,10')->name('newsletter');

Route::get('/mentions-legales', [PageController::class, 'legal'])->name('legal');
Route::get('/confidentialite', [PageController::class, 'privacy'])->name('privacy');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

Route::middleware('signed')->group(function () {
    Route::get('/connexion/{token}', [MagicLinkController::class, 'show'])->name('magic-link.show');
    Route::post('/connexion/{token}', [MagicLinkController::class, 'login'])->middleware('throttle:10,1')->name('magic-link.login');
});
