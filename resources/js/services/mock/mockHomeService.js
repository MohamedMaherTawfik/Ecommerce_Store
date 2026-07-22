const delay = (ms = 450) => new Promise((resolve) => setTimeout(resolve, ms));

const clone = (value) => JSON.parse(JSON.stringify(value));

const heroSlides = [
    {
        id: 1,
        tag: "Bestseller",
        title: "Radiance Vitamin C Serum",
        description:
            "Achieve a brighter, more even complexion with our potent Vitamin C serum for a daily youthful glow.",
        ctaLabel: "Shop Now",
        ctaLink: "/{lang}/products?category=skincare",
        image:
            "https://images.unsplash.com/photo-1599305090598-fe179d501227?auto=format&fit=crop&w=1600&q=80",
        accent: "Must have",
        price: 45,
    },
    {
        id: 2,
        tag: "New Arrival",
        title: "Hydra Moisturizing Cream",
        description:
            "Lock in moisture all day with our lightweight, ultra-hydrating cream formulated for all skin types.",
        ctaLabel: "Buy Now",
        ctaLink: "/{lang}/products?category=skincare",
        image:
            "https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?auto=format&fit=crop&w=1600&q=80",
        accent: "Trending",
        price: 38,
    },
    {
        id: 3,
        tag: "Limited Edition",
        title: "Luxury Velvet Lipstick",
        description:
            "Experience rich, long-lasting color with a smooth velvet finish that feels as good as it looks.",
        ctaLabel: "Explore Product",
        ctaLink: "/{lang}/products?category=makeup",
        image:
            "https://images.unsplash.com/photo-1586495777744-4413f21062fa?auto=format&fit=crop&w=1600&q=80",
        accent: "Editor's Pick",
        price: 28,
    },
];

const trustItems = [
    {
        id: 1,
        icon: "bi-shield-check",
        title: "Secure checkout",
        text: "Encrypted payment flow with trusted gateways.",
    },
    {
        id: 2,
        icon: "bi-truck",
        title: "Fast delivery",
        text: "Same-day dispatch on selected premium drops.",
    },
    {
        id: 3,
        icon: "bi-arrow-repeat",
        title: "Easy returns",
        text: "Flexible return windows and concierge support.",
    },
    {
        id: 4,
        icon: "bi-award",
        title: "Curated quality",
        text: "Only pieces that meet our premium standard.",
    },
];

const categories = [
    {
        id: 1,
        slug: "outerwear",
        name: "Outerwear",
        productsCount: 28,
        image:
            "https://images.unsplash.com/photo-1541099649105-f69ad21f3246?auto=format&fit=crop&w=900&q=80",
    },
    {
        id: 2,
        slug: "footwear",
        name: "Footwear",
        productsCount: 34,
        image:
            "https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=900&q=80",
    },
    {
        id: 3,
        slug: "bags",
        name: "Bags",
        productsCount: 19,
        image:
            "https://images.unsplash.com/photo-1548036328-c9fa89d128fa?auto=format&fit=crop&w=900&q=80",
    },
    {
        id: 4,
        slug: "accessories",
        name: "Accessories",
        productsCount: 41,
        image:
            "https://images.unsplash.com/photo-1523170335258-f5ed11844a49?auto=format&fit=crop&w=900&q=80",
    },
    {
        id: 5,
        slug: "beauty",
        name: "Beauty",
        productsCount: 22,
        image:
            "https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?auto=format&fit=crop&w=900&q=80",
    },
    {
        id: 6,
        slug: "home",
        name: "Home",
        productsCount: 16,
        image:
            "https://images.unsplash.com/photo-1484154218962-a197022b5858?auto=format&fit=crop&w=900&q=80",
    },
];

const brands = [
    { id: 1, name: "Maison Noire", slug: "maison-noire", productsCount: 14 },
    { id: 2, name: "Atelier Ciel", slug: "atelier-ciel", productsCount: 20 },
    { id: 3, name: "Vanta", slug: "vanta", productsCount: 11 },
    { id: 4, name: "Nova Form", slug: "nova-form", productsCount: 18 },
    { id: 5, name: "Aurelia", slug: "aurelia", productsCount: 24 },
    { id: 6, name: "Lune Studio", slug: "lune-studio", productsCount: 9 },
];

const products = [
    {
        id: 101,
        slug: "silk-overshirt",
        name: "Silk Overshirt",
        price: 168,
        compareAtPrice: 210,
        rating: 4.9,
        reviewsCount: 128,
        image:
            "https://images.unsplash.com/photo-1523381210434-271e8be1f52b?auto=format&fit=crop&w=1200&q=80",
        hoverImage:
            "https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&w=1200&q=80",
        brand: "Maison Noire",
        category: "Shirts",
        tag: "Featured",
        badge: "New",
        stock: 34,
    },
    {
        id: 102,
        slug: "stamped-leather-tote",
        name: "Stamped Leather Tote",
        price: 246,
        compareAtPrice: 290,
        rating: 4.8,
        reviewsCount: 94,
        image:
            "https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=1200&q=80",
        hoverImage:
            "https://images.unsplash.com/photo-1594223274512-ad4803739b7c?auto=format&fit=crop&w=1200&q=80",
        brand: "Aurelia",
        category: "Bags",
        tag: "Classic",
        badge: "Limited",
        stock: 18,
    },
    {
        id: 103,
        slug: "architect-knit-sneaker",
        name: "Architect Knit Sneaker",
        price: 132,
        compareAtPrice: 158,
        rating: 4.7,
        reviewsCount: 201,
        image:
            "https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=1200&q=80",
        hoverImage:
            "https://images.unsplash.com/photo-1460353581641-37baddab0fa2?auto=format&fit=crop&w=1200&q=80",
        brand: "Vanta",
        category: "Footwear",
        tag: "Trending",
        badge: "Best seller",
        stock: 52,
    },
    {
        id: 104,
        slug: "cashmere-wrap-coat",
        name: "Cashmere Wrap Coat",
        price: 412,
        compareAtPrice: 480,
        rating: 5.0,
        reviewsCount: 76,
        image:
            "https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&w=1200&q=80",
        hoverImage:
            "https://images.unsplash.com/photo-1487412720507-e7ab37603c6f?auto=format&fit=crop&w=1200&q=80",
        brand: "Nova Form",
        category: "Outerwear",
        tag: "Luxury",
        badge: "Editors' pick",
        stock: 12,
    },
    {
        id: 105,
        slug: "polished-chain-bracelet",
        name: "Polished Chain Bracelet",
        price: 84,
        compareAtPrice: 96,
        rating: 4.6,
        reviewsCount: 56,
        image:
            "https://images.unsplash.com/photo-1617038220319-276d3cfab638?auto=format&fit=crop&w=1200&q=80",
        hoverImage:
            "https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?auto=format&fit=crop&w=1200&q=80",
        brand: "Aurelia",
        category: "Accessories",
        tag: "Minimal",
        badge: "New",
        stock: 48,
    },
    {
        id: 106,
        slug: "velvet-shoulder-bag",
        name: "Velvet Shoulder Bag",
        price: 214,
        compareAtPrice: 260,
        rating: 4.8,
        reviewsCount: 112,
        image:
            "https://images.unsplash.com/photo-1541099649105-f69ad21f3246?auto=format&fit=crop&w=1200&q=80",
        hoverImage:
            "https://images.unsplash.com/photo-1529139574466-a303027c1d8b?auto=format&fit=crop&w=1200&q=80",
        brand: "Lune Studio",
        category: "Bags",
        tag: "Statement",
        badge: "Trending",
        stock: 22,
    },
];

const flashSale = {
    title: "Private flash sale",
    description: "Handpicked pieces with limited stock and quietly dramatic discounts.",
    endsAt: new Date(Date.now() + 1000 * 60 * 60 * 18).toISOString(),
    items: [
        {
            id: 201,
            name: "Cloud trench",
            price: 142,
            originalPrice: 198,
            sold: 72,
            image:
                "https://images.unsplash.com/photo-1541364983171-a8ba01e95cfc?auto=format&fit=crop&w=1000&q=80",
        },
        {
            id: 202,
            name: "Studio mule",
            price: 96,
            originalPrice: 129,
            sold: 54,
            image:
                "https://images.unsplash.com/photo-1605733513597-a8f1c0e2bd46?auto=format&fit=crop&w=1000&q=80",
        },
        {
            id: 203,
            name: "Leather belt bag",
            price: 74,
            originalPrice: 112,
            sold: 81,
            image:
                "https://images.unsplash.com/photo-1614251055880-ee96e4803393?auto=format&fit=crop&w=1000&q=80",
        },
    ],
};

const promotionalBanner = {
    eyebrow: "Signature selection",
    title: "Build a wardrobe that feels edited, not crowded.",
    description:
        "A focused collection of versatile pieces designed to mix, match, and move season after season.",
    ctaLabel: "Shop the collection",
    ctaLink: "/{lang}/products?sort=featured",
    accentNote: "Luxury essentials. Zero clutter.",
};

const promiseItems = [
    {
        id: 1,
        title: "Concierge support",
        text: "Fast, human support when the details matter.",
        icon: "bi-person-heart",
    },
    {
        id: 2,
        title: "Premium materials",
        text: "Only fabrics and finishes that feel exceptional.",
        icon: "bi-gem",
    },
    {
        id: 3,
        title: "Carefully packed",
        text: "Every order is prepared to arrive feeling special.",
        icon: "bi-box-seam",
    },
    {
        id: 4,
        title: "Sustainable focus",
        text: "Fewer, better pieces with a longer life cycle.",
        icon: "bi-recycle",
    },
];

const testimonials = [
    {
        id: 1,
        name: "Maya Thompson",
        role: "Fashion director",
        quote:
            "The edit feels intentional, elevated, and refreshingly easy to shop.",
        rating: 5,
    },
    {
        id: 2,
        name: "Omar El-Sayed",
        role: "Creative consultant",
        quote:
            "Everything looks premium without trying too hard. That balance is hard to get right.",
        rating: 5,
    },
    {
        id: 3,
        name: "Noura Hadi",
        role: "Boutique owner",
        quote:
            "It has the clarity of a luxury storefront with the speed of a modern ecommerce build.",
        rating: 5,
    },
];

const instagram = [
    {
        id: 1,
        image:
            "https://images.unsplash.com/photo-1529139574466-a303027c1d8b?auto=format&fit=crop&w=1000&q=80",
        label: "@elite.store",
    },
    {
        id: 2,
        image:
            "https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&w=1000&q=80",
        label: "@elite.store",
    },
    {
        id: 3,
        image:
            "https://images.unsplash.com/photo-1487412720507-e7ab37603c6f?auto=format&fit=crop&w=1000&q=80",
        label: "@elite.store",
    },
    {
        id: 4,
        image:
            "https://images.unsplash.com/photo-1496747611176-843222e1e57c?auto=format&fit=crop&w=1000&q=80",
        label: "@elite.store",
    },
    {
        id: 5,
        image:
            "https://images.unsplash.com/photo-1617038220319-276d3cfab638?auto=format&fit=crop&w=1000&q=80",
        label: "@elite.store",
    },
    {
        id: 6,
        image:
            "https://images.unsplash.com/photo-1614251055880-ee96e4803393?auto=format&fit=crop&w=1000&q=80",
        label: "@elite.store",
    },
];

const reels = [
    {
        id: 1,
        title: "Summer Offers",
        thumbnail:
            "https://images.unsplash.com/photo-1529139574466-a303027c1d8b?auto=format&fit=crop&w=900&q=80",
        video_url:
            "https://www.youtube.com/watch?v=dQw4w9WgXcQ",
        duration: "00:30",
        views: 32541,
        likes: 250,
        liked: false,
        platform: "facebook",
        published_at: "2026-07-12T09:30:00.000Z",
    },
    {
        id: 2,
        title: "Behind the Drop",
        thumbnail:
            "https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&w=900&q=80",
        video_url:
            "https://www.youtube.com/watch?v=dQw4w9WgXcQ",
        duration: "00:24",
        views: 18420,
        likes: 182,
        liked: true,
        platform: "facebook",
        published_at: "2026-07-11T14:15:00.000Z",
    },
    {
        id: 3,
        title: "New Season Styling",
        thumbnail:
            "https://images.unsplash.com/photo-1487412720507-e7ab37603c6f?auto=format&fit=crop&w=900&q=80",
        video_url:
            "https://www.youtube.com/watch?v=dQw4w9WgXcQ",
        duration: "00:42",
        views: 46122,
        likes: 501,
        liked: false,
        platform: "facebook",
        published_at: "2026-07-10T17:45:00.000Z",
    },
    {
        id: 4,
        title: "Limited Offer Reveal",
        thumbnail:
            "https://images.unsplash.com/photo-1541099649105-f69ad21f3246?auto=format&fit=crop&w=900&q=80",
        video_url:
            "https://www.youtube.com/watch?v=dQw4w9WgXcQ",
        duration: "01:02",
        views: 28973,
        likes: 311,
        liked: false,
        platform: "facebook",
        published_at: "2026-07-09T11:05:00.000Z",
    },
    {
        id: 5,
        title: "Gift Edit",
        thumbnail:
            "https://images.unsplash.com/photo-1617038220319-276d3cfab638?auto=format&fit=crop&w=900&q=80",
        video_url:
            "https://www.youtube.com/watch?v=dQw4w9WgXcQ",
        duration: "00:18",
        views: 12654,
        likes: 142,
        liked: false,
        platform: "facebook",
        published_at: "2026-07-08T08:15:00.000Z",
    },
    {
        id: 6,
        title: "Style Tips",
        thumbnail:
            "https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=900&q=80",
        video_url:
            "https://www.youtube.com/watch?v=dQw4w9WgXcQ",
        duration: "00:36",
        views: 38791,
        likes: 410,
        liked: true,
        platform: "facebook",
        published_at: "2026-07-07T13:20:00.000Z",
    },
    {
        id: 7,
        title: "Weekend Favorites",
        thumbnail:
            "https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&w=900&q=80",
        video_url:
            "https://www.youtube.com/watch?v=dQw4w9WgXcQ",
        duration: "00:27",
        views: 20118,
        likes: 198,
        liked: false,
        platform: "facebook",
        published_at: "2026-07-06T15:50:00.000Z",
    },
];

const homePayload = {
    heroSlides,
    trustItems,
    categories,
    brands,
    flashSale,
    products: {
        featured: products.slice(0, 4),
        bestSellers: [products[3], products[0], products[5], products[1]],
        trending: [products[2], products[4], products[0], products[3]],
        spotlight: products.slice(0, 6),
    },
    promotionalBanner,
    promiseItems,
    testimonials,
    instagram,
    reels,
    newsletter: {
        eyebrow: "Stay in the loop",
        title: "Get exclusive drops and private sale access first.",
        description:
            "Join a refined list of customers who want thoughtful edits, not inbox noise.",
        placeholder: "your@email.com",
        ctaLabel: "Join the list",
    },
    stats: [
        { id: 1, label: "Premium brands", value: "50+" },
        { id: 2, label: "Average ship time", value: "24h" },
        { id: 3, label: "Customer rating", value: "4.9/5" },
    ],
};

export async function fetchHomeMock() {
    await delay();
    return {
        success: true,
        data: clone(homePayload),
    };
}

export function getHomeSnapshot() {
    return clone(homePayload);
}
