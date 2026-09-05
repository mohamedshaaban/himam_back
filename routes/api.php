<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Api;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Himam API
|--------------------------------------------------------------------------
|
| Three tiers: public routes the landing and login screens use, authenticated
| reader routes behind Sanctum, and the admin dashboard behind the `admin`
| middleware. Every response is language-negotiated by SetLocale, which runs
| ahead of these routes (see bootstrap/app.php).
|
*/

// ─── Public ──────────────────────────────────────────────────────────────────

Route::post('auth/register', [Api\AuthController::class, 'register']);
Route::post('auth/login', [Api\AuthController::class, 'login']);

// Static content: about, privacy, support and the FAQ. Public, because a
// privacy policy behind a login is no use to someone deciding whether to join.
Route::get('pages/{slug}', [Api\ContentController::class, 'page']);
Route::get('faqs', [Api\ContentController::class, 'faqs']);
Route::get('contact', [Api\ContentController::class, 'contact']);

Route::get('locales', [Api\ReferenceController::class, 'locales']);
Route::get('levels', [Api\ReferenceController::class, 'levels']);
Route::get('slides/{screen}', [Api\ReferenceController::class, 'slides']);

// Reachable without signing in so the landing page can preview the catalogue.
Route::get('books', [Api\BookController::class, 'index']);
Route::get('books/{book}', [Api\BookController::class, 'show']);
Route::get('badges', [Api\BadgeController::class, 'index']);
Route::get('honor-board', Api\HonorBoardController::class);

// Behind the QR code on every certificate.
Route::get('certificates/verify/{code}', [Api\CertificateController::class, 'verify']);

// ─── Authenticated reader ────────────────────────────────────────────────────

Route::middleware('auth:sanctum')->group(function () {
    Route::get('auth/me', [Api\AuthController::class, 'me']);
    Route::post('auth/logout', [Api\AuthController::class, 'logout']);

    Route::put('profile', [Api\ProfileController::class, 'update']);
    Route::put('profile/password', [Api\ProfileController::class, 'updatePassword']);

    Route::get('dashboard', Api\DashboardController::class);
    Route::get('progress', Api\ProgressController::class);

    Route::get('sections/{section}', [Api\SectionController::class, 'show'])->name('sections.show');
    Route::get('sections/{section}/quiz', [Api\QuizController::class, 'show']);
    Route::post('sections/{section}/quiz', [Api\QuizController::class, 'submit']);

    Route::get('certificates', [Api\CertificateController::class, 'index']);
    Route::get('certificates/available', [Api\CertificateController::class, 'available']);
    Route::get('certificates/{certificate}', [Api\CertificateController::class, 'show']);

    Route::get('announcements', [Api\AnnouncementController::class, 'index']);
    Route::post('announcements/read-all', [Api\AnnouncementController::class, 'markAllRead']);
    Route::get('announcements/{announcement}', [Api\AnnouncementController::class, 'show']);

    Route::get('notification-preferences', [Api\AnnouncementController::class, 'preferences']);
    Route::put('notification-preferences', [Api\AnnouncementController::class, 'updatePreferences']);
});

// ─── Admin dashboard ─────────────────────────────────────────────────────────

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('stats', Admin\StatsController::class);

    // Languages are data, not config: adding one here makes it immediately
    // available to every translatable field without a deploy or a migration.
    Route::apiResource('locales', Admin\LocaleController::class)->except('show');

    Route::get('media', [Admin\MediaController::class, 'index']);
    Route::post('media', [Admin\MediaController::class, 'store']);
    Route::delete('media', [Admin\MediaController::class, 'destroy']);

    Route::apiResource('levels', Admin\LevelController::class);

    Route::apiResource('books', Admin\BookController::class);
    Route::get('books/{book}/sections', [Admin\SectionController::class, 'index']);
    Route::post('books/{book}/sections', [Admin\SectionController::class, 'store']);

    Route::get('sections/{section}', [Admin\SectionController::class, 'show']);
    Route::put('sections/{section}', [Admin\SectionController::class, 'update']);
    Route::delete('sections/{section}', [Admin\SectionController::class, 'destroy']);

    Route::get('sections/{section}/questions', [Admin\QuestionController::class, 'index']);
    Route::post('sections/{section}/questions', [Admin\QuestionController::class, 'store']);
    Route::put('questions/{question}', [Admin\QuestionController::class, 'update']);
    Route::delete('questions/{question}', [Admin\QuestionController::class, 'destroy']);

    Route::apiResource('badges', Admin\BadgeController::class)->except('show');
    Route::post('badges/{badge}/award', [Admin\BadgeController::class, 'award']);
    Route::post('badges/{badge}/revoke', [Admin\BadgeController::class, 'revoke']);

    Route::apiResource('announcements', Admin\AnnouncementController::class);
    Route::post('announcements/{announcement}/publish', [Admin\AnnouncementController::class, 'publish']);

    Route::apiResource('slides', Admin\SlideController::class)->except('show');

    Route::apiResource('users', Admin\UserController::class)->except('store');
    Route::post('users/{user}/recalculate', [Admin\UserController::class, 'recalculate']);

    Route::get('certificates', [Admin\CertificateController::class, 'index']);
    Route::post('certificates', [Admin\CertificateController::class, 'store']);
    Route::delete('certificates/{certificate}', [Admin\CertificateController::class, 'destroy']);
});
