<template>
    <main class="about-page" :dir="lang === 'ar' ? 'rtl' : 'ltr'">
        <section class="about-hero">
            <div class="about-shell about-hero__grid">
                <div class="about-hero__copy">
                    <span class="about-eyebrow">{{ copy.eyebrow }}</span>
                    <h1>{{ copy.title }}</h1>
                    <p>{{ copy.subtitle }}</p>

                    <div class="about-hero__actions">
                        <RouterLink :to="`/${lang}/products`" class="about-btn about-btn--primary">
                            {{ copy.primaryCta }}
                        </RouterLink>
                        <RouterLink :to="`/${lang}/contact`" class="about-btn about-btn--ghost">
                            {{ copy.secondaryCta }}
                        </RouterLink>
                    </div>
                </div>

                <div class="about-hero__card">
                    <div class="about-hero__badge">{{ copy.badge }}</div>
                    <div class="about-hero__stats">
                        <article v-for="stat in stats" :key="stat.label" class="about-stat">
                            <strong>{{ stat.value }}</strong>
                            <span>{{ stat.label }}</span>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <section class="about-section">
            <div class="about-shell about-story">
                <div class="about-story__intro">
                    <span class="about-eyebrow">{{ copy.storyEyebrow }}</span>
                    <h2>{{ copy.storyTitle }}</h2>
                </div>

                <div class="about-story__content">
                    <p>{{ copy.storyBody }}</p>
                    <div class="about-story__highlights">
                        <div v-for="item in highlights" :key="item.title" class="about-highlight">
                            <div class="about-highlight__icon">
                                <i :class="item.icon"></i>
                            </div>
                            <div>
                                <h3>{{ item.title }}</h3>
                                <p>{{ item.text }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="about-section about-section--muted">
            <div class="about-shell">
                <div class="about-section__head">
                    <span class="about-eyebrow">{{ copy.featuresEyebrow }}</span>
                    <h2>{{ copy.featuresTitle }}</h2>
                    <p>{{ copy.featuresSubtitle }}</p>
                </div>

                <div class="about-feature-grid">
                    <article v-for="feature in features" :key="feature.title" class="about-feature-card">
                        <div class="about-feature-card__icon">
                            <i :class="feature.icon"></i>
                        </div>
                        <h3>{{ feature.title }}</h3>
                        <p>{{ feature.text }}</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="about-section">
            <div class="about-shell">
                <div class="about-section__head">
                    <span class="about-eyebrow">{{ copy.valuesEyebrow }}</span>
                    <h2>{{ copy.valuesTitle }}</h2>
                </div>

                <div class="about-values">
                    <article v-for="value in values" :key="value.title" class="about-value-card">
                        <h3>{{ value.title }}</h3>
                        <p>{{ value.text }}</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="about-cta">
            <div class="about-shell about-cta__inner">
                <div>
                    <span class="about-eyebrow about-eyebrow--light">{{ copy.ctaEyebrow }}</span>
                    <h2>{{ copy.ctaTitle }}</h2>
                    <p>{{ copy.ctaText }}</p>
                </div>

                <RouterLink :to="`/${lang}/products`" class="about-btn about-btn--light">
                    {{ copy.ctaButton }}
                </RouterLink>
            </div>
        </section>
    </main>
</template>

<script setup>
import { computed } from "vue";
import { RouterLink, useRoute } from "vue-router";
import { useSeoMeta } from "@/composables/useSeoMeta";

useSeoMeta({
    title: "About Us",
    description: "Learn more about EliteShop. We build a sharper, faster, and more trusted shopping experience.",
});

const route = useRoute();
const lang = computed(() => route.params.lang || localStorage.getItem("language") || "en");

const copy = computed(() => {
    if (lang.value === "ar") {
        return {
            eyebrow: "من نحن",
            title: "نصنع تجربة تسوق أكثر وضوحًا وسرعة وثقة.",
            subtitle:
                "نبني متجرًا رقميًا يربط بين المنتجات المختارة بعناية وتجربة استخدام سلسة، من أول تصفح وحتى إتمام الطلب والمتابعة بعد الشراء.",
            primaryCta: "تصفح المنتجات",
            secondaryCta: "تواصل معنا",
            badge: "تجربة تسوق حديثة",
            storyEyebrow: "قصتنا",
            storyTitle: "شركة تركز على الجودة، التفاصيل، والثقة طويلة المدى.",
            storyBody:
                "فكرتنا بسيطة: تقديم منتجات موثوقة ضمن واجهة سريعة وواضحة تساعد العميل على الوصول لما يريد بدون تعقيد. لذلك نهتم بتناسق العرض، وضوح التقييمات، وسلاسة إدارة السلة والطلبات على كل الصفحات.",
            featuresEyebrow: "لماذا نحن",
            featuresTitle: "ما الذي يميز تجربتنا",
            featuresSubtitle:
                "كل جزء في المنصة مصمم ليجعل رحلة الشراء أسهل، أسرع، وأكثر احترافية على الهاتف وسطح المكتب.",
            valuesEyebrow: "قيمنا",
            valuesTitle: "كيف نعمل كل يوم",
            ctaEyebrow: "ابدأ الآن",
            ctaTitle: "اكتشف المنتجات المناسبة لك في دقائق.",
            ctaText: "استعرض الكتالوج، أضف للسلة بسهولة، وتتبع تقييمات حقيقية قبل اتخاذ قرار الشراء.",
            ctaButton: "ابدأ التسوق",
        };
    }

    return {
        eyebrow: "About Us",
        title: "We build a sharper, faster, and more trusted shopping experience.",
        subtitle:
            "Our storefront is designed to connect carefully selected products with a smooth customer journey from discovery to checkout and beyond.",
        primaryCta: "Browse Products",
        secondaryCta: "Contact Us",
        badge: "Modern Commerce Experience",
        storyEyebrow: "Our Story",
        storyTitle: "A company built around quality, detail, and long-term trust.",
        storyBody:
            "Our idea is simple: present reliable products inside a polished interface that helps customers find what they need without friction. That is why we focus on clear product presentation, trustworthy reviews, and a smoother cart experience across every page.",
        featuresEyebrow: "Why Choose Us",
        featuresTitle: "What makes our experience different",
        featuresSubtitle:
            "Every part of the platform is tuned to make online shopping easier, faster, and more polished on mobile and desktop.",
        valuesEyebrow: "Our Values",
        valuesTitle: "How we work every day",
        ctaEyebrow: "Start Here",
        ctaTitle: "Find the right products in just a few minutes.",
        ctaText: "Explore the catalog, add items to your cart with confidence, and rely on real product reviews before you buy.",
        ctaButton: "Start Shopping",
    };
});

const stats = computed(() =>
    lang.value === "ar"
        ? [
              { value: "10K+", label: "عميل سعيد" },
              { value: "500+", label: "منتج متاح" },
              { value: "4.8/5", label: "متوسط رضا العملاء" },
          ]
        : [
              { value: "10K+", label: "Happy Customers" },
              { value: "500+", label: "Products Available" },
              { value: "4.8/5", label: "Average Satisfaction" },
          ],
);

const highlights = computed(() =>
    lang.value === "ar"
        ? [
              {
                  icon: "bi bi-lightning-charge",
                  title: "أداء سريع",
                  text: "تنقل مرن وواجهة واضحة تساعد العميل على الوصول للمنتج بسرعة.",
              },
              {
                  icon: "bi bi-shield-check",
                  title: "ثقة وشفافية",
                  text: "عرض تقييمات حقيقية ومعلومات منظمة لدعم قرار الشراء بثقة.",
              },
          ]
        : [
              {
                  icon: "bi bi-lightning-charge",
                  title: "Fast Experience",
                  text: "Smooth navigation and clear product discovery that reduces friction.",
              },
              {
                  icon: "bi bi-shield-check",
                  title: "Trust and Clarity",
                  text: "Real ratings and organized information that support better buying decisions.",
              },
          ],
);

const features = computed(() =>
    lang.value === "ar"
        ? [
              {
                  icon: "bi bi-cart-check",
                  title: "سلة تفاعلية",
                  text: "تحديثات فورية لعدد المنتجات والتنبيهات بعد الإضافة والحذف.",
              },
              {
                  icon: "bi bi-star-half",
                  title: "تقييمات دقيقة",
                  text: "إظهار متوسط التقييمات الحقيقي وعدد المراجعات على كل البطاقات.",
              },
              {
                  icon: "bi bi-phone",
                  title: "تصميم متجاوب",
                  text: "تجربة مريحة ومتوازنة على الهاتف والأجهزة اللوحية والشاشات الكبيرة.",
              },
          ]
        : [
              {
                  icon: "bi bi-cart-check",
                  title: "Reactive Cart",
                  text: "Instant cart count updates and clear feedback after add or remove actions.",
              },
              {
                  icon: "bi bi-star-half",
                  title: "Accurate Reviews",
                  text: "Real average ratings and review counts across product cards and listings.",
              },
              {
                  icon: "bi bi-phone",
                  title: "Responsive Design",
                  text: "A balanced experience across phones, tablets, and large screens.",
              },
          ],
);

const values = computed(() =>
    lang.value === "ar"
        ? [
              {
                  title: "الوضوح أولًا",
                  text: "نحافظ على تجربة بسيطة ومباشرة في المحتوى، التصفح، والشراء.",
              },
              {
                  title: "الجودة في التفاصيل",
                  text: "نهتم بالتقييمات، الرسائل التفاعلية، وسلوك الواجهة بنفس قدر اهتمامنا بالمحتوى نفسه.",
              },
              {
                  title: "التطوير المستمر",
                  text: "نراجع الرحلة كاملة باستمرار ونحسنها بناءً على الاستخدام الفعلي.",
              },
          ]
        : [
              {
                  title: "Clarity First",
                  text: "We keep discovery, product details, and checkout direct and easy to understand.",
              },
              {
                  title: "Quality in the Details",
                  text: "We care about ratings, feedback, and UI behavior as much as we care about the products themselves.",
              },
              {
                  title: "Continuous Improvement",
                  text: "We refine the full shopping journey based on real usage and practical feedback.",
              },
          ],
);
</script>

<style scoped>
.about-page {
    background:
        radial-gradient(circle at top left, rgba(196, 64, 58, 0.12), transparent 32%),
        radial-gradient(circle at top right, rgba(15, 23, 42, 0.08), transparent 34%),
        var(--sf-bg);
    color: var(--sf-text);
}

.about-shell {
    width: min(1200px, calc(100% - 2rem));
    margin-inline: auto;
}

.about-hero {
    padding: 4.5rem 0 3rem;
}

.about-hero__grid {
    display: grid;
    grid-template-columns: minmax(0, 1.15fr) minmax(280px, 0.85fr);
    gap: 1.5rem;
    align-items: stretch;
}

.about-hero__copy,
.about-hero__card,
.about-feature-card,
.about-value-card,
.about-highlight {
    border: 1px solid var(--sf-border);
    background: rgba(255, 255, 255, 0.78);
    box-shadow: var(--sf-shadow-lg);
    backdrop-filter: blur(14px);
}

[data-theme="dark"] .about-hero__copy,
[data-theme="dark"] .about-hero__card,
[data-theme="dark"] .about-feature-card,
[data-theme="dark"] .about-value-card,
[data-theme="dark"] .about-highlight {
    background: rgba(15, 23, 42, 0.72);
}

.about-hero__copy {
    padding: clamp(1.6rem, 4vw, 3rem);
    border-radius: 2rem;
}

.about-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    color: #c4403a;
    font-size: 0.8rem;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    margin-bottom: 1rem;
}

.about-eyebrow--light {
    color: rgba(255, 255, 255, 0.86);
}

.about-hero h1,
.about-section h2,
.about-cta h2 {
    margin: 0;
    line-height: 1.08;
    font-size: clamp(2rem, 4vw, 3.9rem);
    letter-spacing: -0.04em;
}

.about-section h2,
.about-cta h2 {
    font-size: clamp(1.7rem, 3vw, 2.8rem);
}

.about-hero p,
.about-section__head p,
.about-story__content > p,
.about-value-card p,
.about-feature-card p,
.about-highlight p,
.about-cta p {
    color: var(--sf-muted);
    line-height: 1.8;
    margin: 0;
}

.about-hero__copy p {
    margin-top: 1.15rem;
    max-width: 60ch;
}

.about-hero__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.85rem;
    margin-top: 1.6rem;
}

.about-btn {
    min-height: 48px;
    padding: 0.78rem 1.2rem;
    border-radius: 999px;
    font-weight: 700;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.25s ease, box-shadow 0.25s ease, background 0.25s ease;
}

.about-btn:hover {
    transform: translateY(-2px);
}

.about-btn--primary {
    background: #c4403a;
    color: #fff;
    box-shadow: 0 18px 34px rgba(196, 64, 58, 0.24);
}

.about-btn--ghost {
    border: 1px solid var(--sf-border);
    color: var(--sf-text);
    background: var(--sf-surface);
}

.about-btn--light {
    background: #fff;
    color: #101828;
}

.about-hero__card {
    border-radius: 2rem;
    padding: 1.4rem;
    display: grid;
    gap: 1.2rem;
    background:
        linear-gradient(180deg, rgba(255, 255, 255, 0.92), rgba(255, 255, 255, 0.84)),
        linear-gradient(135deg, rgba(196, 64, 58, 0.06), rgba(16, 24, 40, 0.06));
}

[data-theme="dark"] .about-hero__card {
    background:
        linear-gradient(180deg, rgba(15, 23, 42, 0.82), rgba(15, 23, 42, 0.72)),
        linear-gradient(135deg, rgba(196, 64, 58, 0.08), rgba(255, 255, 255, 0.04));
}

.about-hero__badge {
    width: fit-content;
    padding: 0.45rem 0.8rem;
    border-radius: 999px;
    background: rgba(196, 64, 58, 0.1);
    color: #c4403a;
    font-size: 0.82rem;
    font-weight: 700;
}

.about-hero__stats {
    display: grid;
    gap: 1rem;
}

.about-stat {
    padding: 1rem 1.1rem;
    border-radius: 1.2rem;
    background: rgba(255, 255, 255, 0.7);
    border: 1px solid rgba(196, 64, 58, 0.08);
}

[data-theme="dark"] .about-stat {
    background: rgba(255, 255, 255, 0.04);
}

.about-stat strong {
    display: block;
    font-size: 1.5rem;
    line-height: 1;
}

.about-stat span {
    margin-top: 0.4rem;
    display: block;
    color: var(--sf-muted);
    font-size: 0.92rem;
}

.about-section {
    padding: 1.5rem 0 4rem;
}

.about-section--muted {
    background: rgba(15, 23, 42, 0.03);
}

[data-theme="dark"] .about-section--muted {
    background: rgba(255, 255, 255, 0.02);
}

.about-story {
    display: grid;
    grid-template-columns: 0.82fr 1.18fr;
    gap: 1.5rem;
    align-items: start;
}

.about-story__content {
    display: grid;
    gap: 1.1rem;
}

.about-story__highlights {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
}

.about-highlight {
    border-radius: 1.5rem;
    padding: 1.15rem;
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 0.85rem;
    align-items: start;
}

.about-highlight__icon,
.about-feature-card__icon {
    width: 52px;
    height: 52px;
    border-radius: 1rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(196, 64, 58, 0.1);
    color: #c4403a;
    font-size: 1.3rem;
}

.about-highlight h3,
.about-feature-card h3,
.about-value-card h3 {
    margin: 0 0 0.45rem;
    font-size: 1.05rem;
}

.about-section__head {
    max-width: 700px;
    margin-bottom: 1.4rem;
}

.about-feature-grid,
.about-values {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
}

.about-feature-card,
.about-value-card {
    border-radius: 1.6rem;
    padding: 1.4rem;
}

.about-feature-card__icon {
    margin-bottom: 1rem;
}

.about-cta {
    padding: 0 0 4.5rem;
}

.about-cta__inner {
    padding: clamp(1.5rem, 4vw, 2.6rem);
    border-radius: 2rem;
    background: linear-gradient(135deg, #101828 0%, #c4403a 100%);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.2rem;
    box-shadow: 0 24px 48px rgba(16, 24, 40, 0.24);
}

.about-cta p {
    color: rgba(255, 255, 255, 0.82);
    margin-top: 0.7rem;
    max-width: 56ch;
}

@media (max-width: 991.98px) {
    .about-hero__grid,
    .about-story,
    .about-feature-grid,
    .about-values,
    .about-story__highlights {
        grid-template-columns: 1fr;
    }

    .about-cta__inner {
        align-items: flex-start;
        flex-direction: column;
    }
}

@media (max-width: 575.98px) {
    .about-hero {
        padding-top: 3rem;
    }

    .about-shell {
        width: min(100% - 1.25rem, 1200px);
    }

    .about-hero__copy,
    .about-hero__card,
    .about-feature-card,
    .about-value-card,
    .about-highlight,
    .about-cta__inner {
        border-radius: 1.4rem;
    }
}
</style>
