<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Deal;
use App\Models\Feature;
use App\Models\Testimonial;
use App\Models\TrustItem;
use Illuminate\Database\Seeder;

class HomeContentSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Trust Items ──────────────────────────
        $trustItems = [
            ['icon' => 'bi-truck',                  'label' => 'Free Shipping',   'sub' => 'On orders over $50',  'sort_order' => 1],
            ['icon' => 'bi-arrow-counterclockwise',  'label' => 'Easy Returns',    'sub' => '30-day hassle-free',  'sort_order' => 2],
            ['icon' => 'bi-shield-check',            'label' => 'Secure Payment',  'sub' => '256-bit encryption',  'sort_order' => 3],
            ['icon' => 'bi-gem',                     'label' => 'Premium Quality', 'sub' => 'Curated collections', 'sort_order' => 4],
        ];

        foreach ($trustItems as $item) {
            TrustItem::updateOrCreate(['label' => $item['label']], $item);
        }

        // ─── Features ─────────────────────────────
        $features = [
            ['icon' => 'bi-truck',                  'label' => 'Free Shipping',   'text' => 'Free shipping on every order over $50. No hidden fees, ever.',             'sort_order' => 1],
            ['icon' => 'bi-arrow-counterclockwise',  'label' => 'Easy Returns',    'text' => 'Changed your mind? Return within 30 days, no questions asked.',            'sort_order' => 2],
            ['icon' => 'bi-shield-lock',             'label' => 'Secure Payment',  'text' => 'Your financial data is fully encrypted and never stored.',                 'sort_order' => 3],
            ['icon' => 'bi-gem',                     'label' => 'Premium Quality', 'text' => 'Every item curated by our team for quality and longevity.',                'sort_order' => 4],
        ];

        foreach ($features as $item) {
            Feature::updateOrCreate(['label' => $item['label']], $item);
        }

        // ─── Testimonials ─────────────────────────
        $testimonials = [
            [
                'name' => 'Sarah M.',
                'role' => 'Verified Buyer',
                'text' => 'The quality is absolutely unreal. I ordered a jacket and it arrived next day, beautifully packaged. 10/10 would recommend.',
                'rating' => 5,
                'sort_order' => 1,
            ],
            [
                'name' => 'James K.',
                'role' => 'Regular Customer',
                'text' => 'Finally an online store that gets both style and customer service right. My go-to for everything wardrobe-related.',
                'rating' => 5,
                'sort_order' => 2,
            ],
            [
                'name' => 'Amira L.',
                'role' => 'Fashion Blogger',
                'text' => 'I\'ve featured this store three times on my blog. The curation is impeccable and new drops keep me coming back every week.',
                'rating' => 5,
                'sort_order' => 3,
            ],
        ];

        foreach ($testimonials as $item) {
            Testimonial::updateOrCreate(['name' => $item['name']], $item);
        }

        // ─── Deals ────────────────────────────────
        $deals = [
            ['name' => 'Linen Blend Shirt', 'category' => 'Men',         'icon' => '👔', 'discount' => 35, 'sale_price' => 49,  'original_price' => 75,  'sold_percent' => 68, 'sold_label' => '68% claimed', 'sort_order' => 1],
            ['name' => 'Leather Crossbody',  'category' => 'Accessories', 'icon' => '👜', 'discount' => 28, 'sale_price' => 89,  'original_price' => 124, 'sold_percent' => 45, 'sold_label' => '45% claimed', 'sort_order' => 2],
            ['name' => 'Slim Chino Pant',    'category' => 'Men',         'icon' => '👖', 'discount' => 40, 'sale_price' => 39,  'original_price' => 65,  'sold_percent' => 82, 'sold_label' => '82% claimed', 'sort_order' => 3],
            ['name' => 'Silk Wrap Dress',    'category' => 'Women',       'icon' => '👗', 'discount' => 22, 'sale_price' => 119, 'original_price' => 153, 'sold_percent' => 31, 'sold_label' => '31% claimed', 'sort_order' => 4],
        ];

        foreach ($deals as $item) {
            Deal::updateOrCreate(['name' => $item['name']], $item);
        }

        // ─── Banners ──────────────────────────────
        Banner::updateOrCreate(
            ['type' => 'hero'],
            [
                'eyebrow' => 'Featured Drop',
                'title' => 'Refined pieces crafted for those who lead, not follow.',
                'sort_order' => 1,
            ]
        );

        Banner::updateOrCreate(
            ['type' => 'promo'],
            [
                'eyebrow' => 'Limited Time',
                'title' => 'Upgrade Your Style Game',
                'subtitle' => 'Up to 40% off on premium collections. Today only.',
                'cta_text' => 'Claim Offer',
                'cta_link' => '/{lang}/products',
                'sort_order' => 1,
            ]
        );

        Banner::updateOrCreate(
            ['type' => 'newsletter'],
            [
                'eyebrow' => 'Stay in the loop',
                'title' => 'Get Exclusive Drops & Offers First',
                'subtitle' => 'Join 12,000+ style-forward subscribers. No spam, ever.',
                'cta_text' => 'Subscribe',
                'sort_order' => 1,
            ]
        );
    }
}
