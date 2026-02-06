<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use App\Models\ProjectInvite;
use App\Models\Notification;

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
        View::composer('*', function ($view) {
            $inviteCount = 0;
            $notiCount = 0;

            if (Auth::check()) {

                // ของเดิม: pending invites
                $inviteCount = ProjectInvite::query()
                    ->where('email', Auth::user()->email)
                    ->where('status', 'pending')
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                    })
                    ->count();

                // ✅ ข้อ 5: unread notifications
                $notiCount = Notification::where('user_id', Auth::id())
                    ->whereNull('read_at')
                    ->count();
            }

            $view->with('navPendingInvitesCount', $inviteCount);
            $view->with('navUnreadNotiCount', $notiCount);
        });
    }
}
