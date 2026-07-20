import { onUnmounted, watchEffect } from "vue";

const SITE_NAME = import.meta.env.VITE_APP_NAME || "EliteShop";
const DEFAULT_DESCRIPTION =
    "Discover quality products, trusted reviews, secure checkout, and fast delivery.";
const SUPPORTED_LOCALES = ["en", "ar"];

const resolveValue = (value, fallback = null) =>
    (typeof value === "function" ? value() : value) ?? fallback;

const stripHtml = (value = "") =>
    String(value)
        .replace(/<[^>]*>/g, " ")
        .replace(/\s+/g, " ")
        .trim();

const truncate = (value, length = 160) => {
    const text = stripHtml(value);
    return text.length > length ? `${text.slice(0, length - 1).trim()}…` : text;
};

const absoluteUrl = (value) => {
    if (!value) return null;
    try {
        return new URL(value, window.location.origin).toString();
    } catch {
        return null;
    }
};

const canonicalUrl = (value) => {
    const url = new URL(value || window.location.href, window.location.origin);
    url.hash = "";
    return url.toString();
};

const upsertMeta = (attribute, key, content) => {
    const selector = `meta[${attribute}="${key}"]`;
    let element = document.head.querySelector(selector);

    if (!content) {
        element?.remove();
        return;
    }

    if (!element) {
        element = document.createElement("meta");
        element.setAttribute(attribute, key);
        document.head.appendChild(element);
    }

    element.setAttribute("content", String(content));
};

const upsertLink = (rel, href, attributes = {}) => {
    const extraSelector = Object.entries(attributes)
        .map(([key, value]) => `[${key}="${String(value)}"]`)
        .join("");
    let element = document.head.querySelector(`link[rel="${rel}"]${extraSelector}`);

    if (!href) {
        element?.remove();
        return;
    }

    if (!element) {
        element = document.createElement("link");
        element.setAttribute("rel", rel);
        Object.entries(attributes).forEach(([key, value]) =>
            element.setAttribute(key, value),
        );
        document.head.appendChild(element);
    }

    element.setAttribute("href", href);
};

const setStructuredData = (schema) => {
    let element = document.head.querySelector('script[data-seo-schema="page"]');

    if (!schema) {
        element?.remove();
        return;
    }

    if (!element) {
        element = document.createElement("script");
        element.type = "application/ld+json";
        element.dataset.seoSchema = "page";
        document.head.appendChild(element);
    }

    element.textContent = JSON.stringify(schema);
};

const localizedAlternates = (url) => {
    const parsed = new URL(url);
    const segments = parsed.pathname.split("/").filter(Boolean);
    const currentLocale = SUPPORTED_LOCALES.includes(segments[0])
        ? segments[0]
        : "en";

    return Object.fromEntries(
        SUPPORTED_LOCALES.map((locale) => {
            const localizedSegments = [...segments];
            if (SUPPORTED_LOCALES.includes(localizedSegments[0])) {
                localizedSegments[0] = locale;
            } else {
                localizedSegments.unshift(locale);
            }
            const localized = new URL(parsed.origin);
            localized.pathname = `/${localizedSegments.join("/")}`;
            return [locale, localized.toString()];
        }),
    );
};

export function useSeoMeta(options = {}) {
    watchEffect(() => {
        const rawTitle = truncate(resolveValue(options.title, SITE_NAME), 70);
        const title = options.appendSiteName === false || rawTitle.includes(SITE_NAME)
            ? rawTitle
            : `${rawTitle} | ${SITE_NAME}`;
        const description = truncate(
            resolveValue(options.description, DEFAULT_DESCRIPTION),
            160,
        );
        const url = canonicalUrl(resolveValue(options.canonical, resolveValue(options.url)));
        const image = absoluteUrl(resolveValue(options.image));
        const type = resolveValue(options.type, "website");
        const robots = resolveValue(
            options.robots,
            "index,follow,max-image-preview:large",
        );
        const locale = resolveValue(
            options.locale,
            document.documentElement.lang || "en",
        );
        const ogTitle = truncate(resolveValue(options.ogTitle, rawTitle), 70);
        const ogDescription = truncate(
            resolveValue(options.ogDescription, description),
            200,
        );
        const twitterTitle = truncate(
            resolveValue(options.twitterTitle, ogTitle),
            70,
        );
        const twitterDescription = truncate(
            resolveValue(options.twitterDescription, ogDescription),
            200,
        );
        const twitterImage =
            absoluteUrl(resolveValue(options.twitterImage)) || image;

        document.title = title;
        document.documentElement.lang = locale;
        document.documentElement.dir = locale === "ar" ? "rtl" : "ltr";

        upsertMeta("name", "description", description);
        upsertMeta("name", "keywords", resolveValue(options.keywords));
        upsertMeta("name", "robots", robots);
        upsertMeta("property", "og:site_name", SITE_NAME);
        upsertMeta("property", "og:title", ogTitle);
        upsertMeta("property", "og:description", ogDescription);
        upsertMeta("property", "og:url", url);
        upsertMeta("property", "og:type", type);
        upsertMeta("property", "og:locale", locale === "ar" ? "ar_AR" : "en_US");
        upsertMeta("property", "og:image", image);
        upsertMeta("property", "og:image:alt", image ? ogTitle : null);
        upsertMeta("name", "twitter:card", twitterImage ? "summary_large_image" : "summary");
        upsertMeta("name", "twitter:title", twitterTitle);
        upsertMeta("name", "twitter:description", twitterDescription);
        upsertMeta("name", "twitter:image", twitterImage);
        upsertMeta("name", "twitter:image:alt", twitterImage ? twitterTitle : null);

        upsertLink("canonical", url);

        const alternates = resolveValue(options.alternates, localizedAlternates(url));
        document.head
            .querySelectorAll('link[rel="alternate"][hreflang]')
            .forEach((element) => element.remove());

        Object.entries(alternates || {}).forEach(([language, href]) => {
            const element = document.createElement("link");
            element.rel = "alternate";
            element.hreflang = language;
            element.href = absoluteUrl(href);
            element.dataset.seoLocale = language;
            document.head.appendChild(element);
        });

        const defaultHref = alternates?.en || Object.values(alternates || {})[0];
        if (defaultHref) {
            const element = document.createElement("link");
            element.rel = "alternate";
            element.hreflang = "x-default";
            element.href = absoluteUrl(defaultHref);
            element.dataset.seoLocale = "x-default";
            document.head.appendChild(element);
        }

        upsertLink("prev", absoluteUrl(resolveValue(options.prev)));
        upsertLink("next", absoluteUrl(resolveValue(options.next)));
        setStructuredData(resolveValue(options.schema));
    });

    onUnmounted(() => {
        document.head.querySelector('script[data-seo-schema="page"]')?.remove();
        document.head.querySelector('link[rel="prev"]')?.remove();
        document.head.querySelector('link[rel="next"]')?.remove();
    });
}
