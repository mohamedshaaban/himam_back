<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->resetLinksPointAtTheApp();
    }

    /**
     * The reset link in the email has to open the reader's app, not the API.
     *
     * By default Laravel builds this URL against APP_URL, which here is the
     * backend — a reader clicking it would land on a JSON endpoint. The app and
     * the API live on different hosts in this project, so the frontend origin
     * is configured separately.
     */
    private function resetLinksPointAtTheApp(): void
    {
        ResetPassword::createUrlUsing(function ($user, string $token) {
            // FRONTEND_URL may carry a comma-separated list for CORS; the first
            // entry is the canonical one to send people to.
            $origin = trim(explode(',', (string) env('FRONTEND_URL', ''))[0]);

            if ($origin === '') {
                $origin = rtrim((string) config('app.url'), '/');
            }

            $query = http_build_query([
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ]);

            return rtrim($origin, '/').'/reset-password?'.$query;
        });
    }
}
