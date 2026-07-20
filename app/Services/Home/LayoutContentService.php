<?php

namespace App\Services\Home;

use App\Models\Footer;
use App\Models\NavLink;
use App\Models\SiteSetting;
use App\Support\TaggedCache;
use Illuminate\Support\Facades\Schema;

class LayoutContentService
{
    public function get(): array
    {
        return TaggedCache::tags(['layout', 'navlinks', 'settings'])->remember(
            'home_layout_data_v2',
            3600,
            function (): array {
                $footer = Schema::hasTable('footers') ? Footer::first() : null;
                $settings = Schema::hasTable('site_settings')
                    ? SiteSetting::getMany([
                        'navbar_image',
                        'footer_image',
                        'register_image',
                        'copyright',
                        'google_play',
                        'app_store',
                        'tab_icon',
                    ])
                    : [];
                $navbarLinks = Schema::hasTable('nav_links')
                    ? NavLink::active()->location('navbar')->ordered()->get(['key', 'route', 'icon'])->toArray()
                    : [];
                $quickLinks = Schema::hasTable('nav_links')
                    ? NavLink::active()->location('footer_quick')->ordered()->get(['key', 'route'])->toArray()
                    : [];
                $supportLinks = Schema::hasTable('nav_links')
                    ? NavLink::active()->location('footer_support')->ordered()->get(['key', 'route'])->toArray()
                    : [];

                return [
                    'register_image' => $this->assetUrl($settings['register_image'] ?? null, '/images/ai_logo.webp'),
                    'tab_icon' => $this->assetUrl($settings['tab_icon'] ?? null),
                    'navbar' => [
                        'logo' => $this->assetUrl($settings['navbar_image'] ?? null, '/images/logo.webp'),
                        'links' => $navbarLinks,
                        'cta' => [
                            'cart' => '/{lang}/cart',
                            'auth' => '/{lang}/auth',
                            'profile' => '/{lang}/profile',
                            'wishlist' => '/{lang}/wishlist',
                        ],
                    ],
                    'footer' => [
                        'logo' => $this->assetUrl($settings['footer_image'] ?? null, '/images/ai_logo.webp'),
                        'quickLinks' => $quickLinks,
                        'supportLinks' => $supportLinks,
                        'socials' => collect([
                            ['name' => 'facebook', 'icon' => 'bi-facebook', 'href' => $footer?->facebook],
                            ['name' => 'x', 'icon' => 'bi-twitter-x', 'href' => $footer?->twitter],
                            ['name' => 'instagram', 'icon' => 'bi-instagram', 'href' => $footer?->instagram],
                            ['name' => 'linkedin', 'icon' => 'bi-linkedin', 'href' => $footer?->linkedin],
                            ['name' => 'youtube', 'icon' => 'bi-youtube', 'href' => $footer?->youtube],
                            ['name' => 'tiktok', 'icon' => 'bi-tiktok', 'href' => $footer?->tiktok],
                            ['name' => 'whatsapp', 'icon' => 'bi-whatsapp', 'href' => $footer?->whatsapp],
                        ])->filter(fn (array $social) => ! empty($social['href']))->values()->all(),
                        'copyright' => $settings['copyright'] ?? $footer?->copyright ?? '',
                        'contact' => [
                            'phone' => $footer?->phone,
                            'email' => $footer?->email,
                            'address' => $footer?->address,
                        ],
                        'stores' => [
                            [
                                'name' => 'google',
                                'href' => $settings['google_play'] ?? '#',
                                'image' => 'https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg',
                                'alt' => 'Get it on Google Play',
                            ],
                            [
                                'name' => 'apple',
                                'href' => $settings['app_store'] ?? '#',
                                'image' => 'https://developer.apple.com/assets/elements/badges/download-on-the-app-store.svg',
                                'alt' => 'Download on the App Store',
                            ],
                        ],
                    ],
                ];
            }
        );
    }

    private function assetUrl(mixed $value, ?string $fallback = null): ?string
    {
        $url = is_string($value) && $value !== '' ? $value : $fallback;

        if ($url && (str_starts_with($url, 'storage/') || str_starts_with($url, '/storage/'))) {
            return asset(ltrim($url, '/'));
        }

        if (in_array($url, ['/images/logo1.png', '/images/logo.png'], true)) {
            return '/images/logo.webp';
        }

        if (in_array($url, ['/images/ai_logo.png', '/images/Ai_logo.png'], true)) {
            return '/images/ai_logo.webp';
        }

        return $url;
    }
}
