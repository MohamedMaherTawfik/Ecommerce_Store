<?php

namespace App\Providers;

use App\Http\Middleware\CheckIfInstalled;
use App\Models\Addresses;
use App\Models\BlogPost;
use App\Models\EmailTemplate;
use App\Models\Invoice;
use App\Models\Orders;
use App\Models\Payment;
use App\Models\Products;
use App\Models\Refund;
use App\Models\ReturnRequest;
use App\Models\Reviews;
use App\Models\Shipment;
use App\Models\ShippingMethod;
use App\Models\SiteSetting;
use App\Models\TaxRule;
use App\Models\Ticket;
use App\Models\User;
use App\Observers\UserObserver;
use App\Policies\AddressPolicy;
use App\Policies\BlogPostPolicy;
use App\Policies\EmailTemplatePolicy;
use App\Policies\InvoicePolicy;
use App\Policies\OrderPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\ProductPolicy;
use App\Policies\RefundPolicy;
use App\Policies\ReturnRequestPolicy;
use App\Policies\ReviewPolicy;
use App\Policies\ShipmentPolicy;
use App\Policies\ShippingMethodPolicy;
use App\Policies\SiteSettingPolicy;
use App\Policies\TaxRulePolicy;
use App\Policies\TicketPolicy;
use App\Policies\UserPolicy;
use App\Repository\Register\UserRepository;
use App\Repository\Register\UserRepositoryImpl;
use App\Services\Installer\EnvironmentSetupService;
use App\Services\Installer\InstallationStateService;
use App\Services\SettingsService;
use App\Support\TaggedCache;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepository::class, UserRepositoryImpl::class);
        $this->app->singleton(InstallationStateService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('admin-login', function (Request $request) {
            $identity = Str::lower(trim((string) $request->input('email')));

            return Limit::perMinute(5)->by(hash('sha256', $identity.'|'.$request->ip()));
        });

        RateLimiter::for('password-reset-request', function (Request $request) {
            $identity = Str::lower(trim((string) $request->input('email')));

            return [
                Limit::perMinute(5)->by(hash('sha256', $identity.'|'.$request->ip())),
                Limit::perHour(10)->by(hash('sha256', $identity)),
            ];
        });

        RateLimiter::for('password-reset-verify', function (Request $request) {
            $identity = Str::lower(trim((string) $request->input('email')));

            return [
                Limit::perMinute(5)->by(hash('sha256', $identity.'|'.$request->ip())),
                Limit::perHour(10)->by(hash('sha256', $identity)),
            ];
        });

        if ($this->app->runningInConsole()) {
            $this->app->make(EnvironmentSetupService::class)->ensureBootstrapState();
            $this->app->make(InstallationStateService::class)->logState('app_boot');
        }
        TaggedCache::logDiagnostics();

        User::observe(UserObserver::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Products::class, ProductPolicy::class);
        Gate::policy(Orders::class, OrderPolicy::class);
        Gate::policy(Addresses::class, AddressPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(ReturnRequest::class, ReturnRequestPolicy::class);
        Gate::policy(Refund::class, RefundPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(Shipment::class, ShipmentPolicy::class);
        Gate::policy(Reviews::class, ReviewPolicy::class);
        Gate::policy(SiteSetting::class, SiteSettingPolicy::class);
        Gate::policy(TaxRule::class, TaxRulePolicy::class);
        Gate::policy(ShippingMethod::class, ShippingMethodPolicy::class);
        Gate::policy(Ticket::class, TicketPolicy::class);
        Gate::policy(BlogPost::class, BlogPostPolicy::class);
        Gate::policy(EmailTemplate::class, EmailTemplatePolicy::class);

        if (! CheckIfInstalled::isInstalled()) {
            return;
        }

        try {
            SettingsService::overrideConfig();
        } catch (\Throwable $e) {
            // Fail silently if settings table is unavailable.
        }
    }
}
