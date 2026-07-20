<template>
    <section class="seo-editor">
        <div class="seo-editor__heading">
            <div>
                <h3>Search & Social SEO</h3>
                <p>Control how this page appears in search results and social shares.</p>
            </div>
            <span>{{ descriptionLength }}/160</span>
        </div>

        <div class="seo-editor__grid">
            <label>
                URL Slug
                <input v-model="slug" class="form-control admin-control" maxlength="255" placeholder="premium-cotton-shirt" />
            </label>

            <label>
                Canonical URL
                <input v-model="canonicalUrl" class="form-control admin-control" type="url" maxlength="2048" placeholder="https://example.com/en/products/premium-cotton-shirt" />
            </label>

            <label class="seo-editor__full">
                SEO Title
                <input v-model="metaTitle" class="form-control admin-control" maxlength="70" placeholder="Search result title" />
                <small>{{ titleLength }}/60 recommended characters</small>
            </label>

            <label class="seo-editor__full">
                SEO Description
                <textarea v-model="metaDescription" class="form-control admin-control" rows="3" maxlength="500" placeholder="A concise, persuasive summary for search engines."></textarea>
            </label>

            <label class="seo-editor__full">
                SEO Keywords
                <input v-model="metaKeywords" class="form-control admin-control" maxlength="1000" placeholder="keyword one, keyword two" />
            </label>

            <label>
                Open Graph Title
                <input v-model="ogTitle" class="form-control admin-control" maxlength="255" placeholder="Social sharing title" />
            </label>

            <label>
                Open Graph Image
                <input class="form-control admin-control" type="file" accept="image/jpeg,image/png,image/webp" @change="selectOgImage" />
            </label>

            <label class="seo-editor__full">
                Open Graph Description
                <textarea v-model="ogDescription" class="form-control admin-control" rows="3" maxlength="500" placeholder="Social sharing description"></textarea>
            </label>
        </div>

        <div class="seo-preview">
            <p class="seo-preview__url">{{ previewUrl }}</p>
            <h4>{{ metaTitle || fallbackTitle || "SEO title preview" }}</h4>
            <p>{{ metaDescription || fallbackDescription || "SEO description preview will appear here." }}</p>
        </div>
    </section>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    fallbackTitle: { type: String, default: "" },
    fallbackDescription: { type: String, default: "" },
});

const slug = defineModel("slug", { default: "" });
const metaTitle = defineModel("metaTitle", { default: "" });
const metaDescription = defineModel("metaDescription", { default: "" });
const metaKeywords = defineModel("metaKeywords", { default: "" });
const ogTitle = defineModel("ogTitle", { default: "" });
const ogDescription = defineModel("ogDescription", { default: "" });
const ogImage = defineModel("ogImage", { default: null });
const canonicalUrl = defineModel("canonicalUrl", { default: "" });

const titleLength = computed(() => (metaTitle.value || props.fallbackTitle).length);
const descriptionLength = computed(() =>
    (metaDescription.value || props.fallbackDescription).length,
);
const previewUrl = computed(() =>
    canonicalUrl.value ||
    `${window.location.origin}/en/products/${slug.value || "page-slug"}`,
);

const selectOgImage = (event) => {
    ogImage.value = event.target.files?.[0] || null;
};
</script>

<style scoped>
.seo-editor {
    grid-column: 1 / -1;
    padding: 1.25rem;
    border: 1px solid #dbe4f0;
    border-radius: 1rem;
    background: #f8fafc;
}

.seo-editor__heading {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
}

.seo-editor__heading h3 {
    margin: 0;
    font-size: 1.05rem;
}

.seo-editor__heading p,
.seo-editor__heading span,
.seo-editor small {
    margin: 0.25rem 0 0;
    color: #64748b;
    font-size: 0.78rem;
}

.seo-editor__grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
}

.seo-editor label {
    display: grid;
    gap: 0.4rem;
    color: #334155;
    font-size: 0.85rem;
    font-weight: 700;
}

.seo-editor__full {
    grid-column: 1 / -1;
}

.seo-preview {
    margin-top: 1rem;
    padding: 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.8rem;
    background: #fff;
}

.seo-preview__url {
    color: #188038 !important;
    font-size: 0.78rem;
}

.seo-preview h4 {
    margin: 0.25rem 0;
    color: #1a0dab;
    font-size: 1.15rem;
    font-weight: 500;
}

.seo-preview p {
    margin: 0;
    color: #4d5156;
    font-size: 0.85rem;
}

@media (max-width: 767.98px) {
    .seo-editor__grid {
        grid-template-columns: 1fr;
    }

    .seo-editor__full {
        grid-column: auto;
    }
}
</style>
