<template>
    <AdminLayout>
        <div class="admin-page">
            <section class="admin-page-header">
                <div>
                    <div class="admin-page-kicker">
                        <i class="bi bi-journal-text"></i>
                        Content
                    </div>
                    <h2 class="admin-page-title">Blog</h2>
                </div>
                <button v-if="canManage" class="btn-admin" type="button" @click="newPost">New post</button>
            </section>

            <section class="admin-panel mb-4">
                <div class="admin-panel__body">
                    <form v-if="editing" class="admin-form-grid" @submit.prevent="savePost">
                        <div class="admin-field">
                            <label class="admin-label">Title</label>
                            <input v-model="form.title" class="form-control admin-control" required />
                        </div>
                        <div class="admin-field">
                            <label class="admin-label">Category</label>
                            <select v-model="form.blog_category_id" class="form-select admin-control">
                                <option value="">No category</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                        </div>
                        <div class="admin-field">
                            <label class="admin-label">Status</label>
                            <select v-model="form.status" class="form-select admin-control">
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                            </select>
                        </div>
                        <div class="admin-field">
                            <label class="admin-label">Publish date</label>
                            <input v-model="form.published_at" type="datetime-local" class="form-control admin-control" />
                        </div>
                        <div class="admin-field admin-field--full">
                            <label class="admin-label">Excerpt</label>
                            <textarea v-model="form.excerpt" class="form-control admin-control" rows="3"></textarea>
                        </div>
                        <div class="admin-field admin-field--full">
                            <label class="admin-label">HTML content</label>
                            <textarea v-model="form.content" class="form-control admin-control" rows="12" required></textarea>
                        </div>
                        <div class="admin-field">
                            <label class="admin-label">Featured image</label>
                            <input type="file" accept="image/jpeg,image/png,image/webp" class="form-control admin-control" @change="featuredImage = $event.target.files?.[0] || null" />
                        </div>
                        <div class="admin-field">
                            <label class="admin-label">Tags</label>
                            <div>
                                <label v-for="tag in tags" :key="tag.id" class="me-3">
                                    <input v-model="form.tag_ids" type="checkbox" :value="tag.id" />
                                    {{ tag.name }}
                                </label>
                            </div>
                        </div>

                        <SeoFields
                            v-model:slug="form.slug"
                            v-model:meta-title="form.meta_title"
                            v-model:meta-description="form.meta_description"
                            v-model:meta-keywords="form.meta_keywords"
                            v-model:og-title="form.og_title"
                            v-model:og-description="form.og_description"
                            v-model:og-image="form.og_image"
                            v-model:canonical-url="form.canonical_url"
                            :fallback-title="form.title"
                            :fallback-description="form.excerpt"
                        />

                        <div class="admin-field">
                            <label class="admin-label">Twitter Title</label>
                            <input v-model="form.twitter_title" class="form-control admin-control" maxlength="255" />
                        </div>
                        <div class="admin-field">
                            <label class="admin-label">Twitter Image</label>
                            <input type="file" accept="image/jpeg,image/png,image/webp" class="form-control admin-control" @change="form.twitter_image = $event.target.files?.[0] || null" />
                        </div>
                        <div class="admin-field admin-field--full">
                            <label class="admin-label">Twitter Description</label>
                            <textarea v-model="form.twitter_description" class="form-control admin-control" rows="3" maxlength="500"></textarea>
                        </div>

                        <div class="admin-field admin-field--full">
                            <div class="admin-actions">
                                <button class="btn-admin" type="submit">Save post</button>
                                <button class="btn-admin btn-admin--soft" type="button" @click="editing = false">Cancel</button>
                            </div>
                        </div>
                    </form>

                    <div v-else class="admin-table-wrap">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Published</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="post in posts" :key="post.id">
                                    <td>{{ post.title }}</td>
                                    <td>{{ post.category?.name || "-" }}</td>
                                    <td>{{ post.status }}</td>
                                    <td>{{ post.published_at || "-" }}</td>
                                    <td>
                                        <button v-if="canManage" class="btn-admin btn-admin--sm" type="button" @click="editPost(post)">Edit</button>
                                        <button v-if="canManage" class="btn-admin btn-admin--danger btn-admin--sm" type="button" @click="removePost(post.id)">Delete</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section v-if="canManage" class="admin-grid">
                <div class="admin-grid__item--6 admin-panel">
                    <div class="admin-panel__body">
                        <h3>Categories</h3>
                        <form class="d-flex gap-2" @submit.prevent="addCategory">
                            <input v-model="categoryName" class="form-control" required />
                            <button class="btn-admin">Add</button>
                        </form>
                        <p v-for="category in categories" :key="category.id" class="mt-2">
                            {{ category.name }}
                            <button class="btn btn-sm text-danger" @click="deleteCategory(category.id)">Delete</button>
                        </p>
                    </div>
                </div>
                <div class="admin-grid__item--6 admin-panel">
                    <div class="admin-panel__body">
                        <h3>Tags</h3>
                        <form class="d-flex gap-2" @submit.prevent="addTag">
                            <input v-model="tagName" class="form-control" required />
                            <button class="btn-admin">Add</button>
                        </form>
                        <span v-for="tag in tags" :key="tag.id" class="badge text-bg-light me-2 mt-2">
                            {{ tag.name }}
                            <button class="btn btn-sm p-0 text-danger" @click="deleteTag(tag.id)">x</button>
                        </span>
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from "vue";
import SeoFields from "@/components/admin/SeoFields.vue";
import { hasAdminPermission } from "@/config/adminAccess";
import { getUserData } from "@/services/auth/authSession";
import service from "@/services/admin/blogService";
import AdminLayout from "@/views/admin/layout/AdminLayout.vue";

const canManage = hasAdminPermission(getUserData() || {}, "blog.manage");
const posts = ref([]);
const categories = ref([]);
const tags = ref([]);
const editing = ref(false);
const editingId = ref(null);
const featuredImage = ref(null);
const categoryName = ref("");
const tagName = ref("");

const blank = () => ({
    title: "",
    slug: "",
    excerpt: "",
    content: "",
    blog_category_id: "",
    tag_ids: [],
    status: "draft",
    published_at: "",
    meta_title: "",
    meta_description: "",
    meta_keywords: "",
    canonical_url: "",
    og_title: "",
    og_description: "",
    og_image: null,
    twitter_title: "",
    twitter_description: "",
    twitter_image: null,
});
const form = reactive(blank());

const load = async () => {
    posts.value = (await service.posts()).data?.data || [];
    categories.value = (await service.categories()).data || [];
    tags.value = (await service.tags()).data || [];
};

const newPost = () => {
    Object.assign(form, blank());
    editingId.value = null;
    featuredImage.value = null;
    editing.value = true;
};

const editPost = async (post) => {
    const record = (await service.showPost(post.id)).data || post;
    Object.assign(form, blank(), record, {
        blog_category_id: record.blog_category_id || "",
        tag_ids: (record.tags || []).map((tag) => tag.id),
        published_at: record.published_at?.slice(0, 16) || "",
        og_image: record.og_image || null,
        twitter_image: record.twitter_image || null,
    });
    editingId.value = record.id;
    featuredImage.value = null;
    editing.value = true;
};

const savePost = async () => {
    const payload = new FormData();

    Object.entries(form).forEach(([key, value]) => {
        if (key === "tag_ids") {
            value.forEach((id) => payload.append("tag_ids[]", id));
        } else if (value instanceof File) {
            payload.append(key, value);
        } else if (value !== null && value !== "") {
            payload.append(key, value);
        }
    });

    if (featuredImage.value) payload.append("featured_image", featuredImage.value);

    await service.savePost(editingId.value, payload);
    editing.value = false;
    await load();
};

const removePost = async (id) => {
    if (window.confirm("Delete post?")) {
        await service.deletePost(id);
        await load();
    }
};

const addCategory = async () => {
    await service.saveCategory(null, { name: categoryName.value });
    categoryName.value = "";
    await load();
};
const deleteCategory = async (id) => {
    await service.deleteCategory(id);
    await load();
};
const addTag = async () => {
    await service.saveTag(null, { name: tagName.value });
    tagName.value = "";
    await load();
};
const deleteTag = async (id) => {
    await service.deleteTag(id);
    await load();
};

onMounted(load);
</script>
