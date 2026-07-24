<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NavLink;
use App\Models\SiteSetting;
use App\Models\Footer;

class LayoutSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Navbar Links ─────────────────────────
        $navbarLinks = [
            ['key' => 'home',     'route' => '/{lang}',          'sort_order' => 1],
            ['key' => 'products', 'route' => '/{lang}/products', 'sort_order' => 2],
            ['key' => 'wishlist', 'route' => '/{lang}/wishlist', 'sort_order' => 3],
            ['key' => 'about',    'route' => '/{lang}/about',    'sort_order' => 4],
            ['key' => 'contact',  'route' => '/{lang}/contact',  'sort_order' => 5],
        ];

        foreach ($navbarLinks as $link) {
            NavLink::updateOrCreate(
                ['key' => $link['key'], 'location' => 'navbar'],
                array_merge($link, ['location' => 'navbar'])
            );
        }

        // ─── Footer Quick Links ───────────────────
        $quickLinks = [
            ['key' => 'home',     'route' => '/{lang}',          'sort_order' => 1],
            ['key' => 'products', 'route' => '/{lang}/products', 'sort_order' => 2],
        ];

        foreach ($quickLinks as $link) {
            NavLink::updateOrCreate(
                ['key' => $link['key'], 'location' => 'footer_quick'],
                array_merge($link, ['location' => 'footer_quick'])
            );
        }

        // ─── Footer Support Links ─────────────────
        $supportLinks = [
            ['key' => 'about',     'route' => '/{lang}/about',   'sort_order' => 1],
            ['key' => 'contact',   'route' => '/{lang}/contact', 'sort_order' => 2],
            ['key' => 'copyright', 'route' => '#',               'sort_order' => 3],
            ['key' => 'terms',     'route' => '#',               'sort_order' => 4],
            ['key' => 'privacy',   'route' => '#',               'sort_order' => 5],
            ['key' => 'help',      'route' => '#',               'sort_order' => 6],
        ];

        foreach ($supportLinks as $link) {
            NavLink::updateOrCreate(
                ['key' => $link['key'], 'location' => 'footer_support'],
                array_merge($link, ['location' => 'footer_support'])
            );
        }

        // ─── Site Settings ────────────────────────
        $settings = [
            'navbar_image'   => '/images/ai_logo.webp',
            'footer_image'   => '/images/ai_logo.webp',
            'register_image' => '/images/ai_logo.webp',
            'copyright'      => '© 2025 Emmy Store — All rights reserved',
            'google_play'    => '#',
            'app_store'      => '#',
        ];

        foreach ($settings as $key => $value) {
            SiteSetting::setValue($key, $value);
        }

        // ─── Footer Social Links (uses existing footers table) ───
        Footer::updateOrCreate(['id' => 1], [
            'facebook'  => '#',
            'twitter'   => '#',
            'instagram' => '#',
            'linkedin'  => null,
            'youtube'   => null,
            'whatsapp'  => null,
            'phone'     => null,
            'email'     => null,
            'copyright' => '© 2025 Ataa Foundation — All rights reserved',
            'logo'      => '/images/ai_logo.webp',
        ]);
    }
}
