<template>
    <AdminLayout>
        <Transition appear name="page-fade">
            <div class="admin-page" v-if="message">
                <section class="admin-page-header">
                    <div class="admin-page-copy">
                        <div class="admin-page-kicker">
                            <RouterLink to="/admin/contact-messages" class="text-decoration-none">
                                <i class="bi bi-arrow-left me-1"></i> Back to Messages
                            </RouterLink>
                        </div>
                        <h2 class="admin-page-title">Message Details</h2>
                    </div>
                </section>

                <div class="row">
                    <div class="col-md-8">
                        <section class="admin-panel mb-4">
                            <div class="admin-panel__header">
                                <h3 class="admin-panel__title">{{ message.subject }}</h3>
                                <span v-if="message.replied_at" class="badge bg-success">Replied</span>
                                <span v-else class="badge bg-danger">Not Replied</span>
                            </div>
                            <div class="admin-panel__body p-4">
                                <div class="mb-3">
                                    <strong>From:</strong> {{ message.name }} ({{ message.email }})
                                </div>
                                <div class="mb-3">
                                    <strong>Date:</strong> {{ new Date(message.created_at).toLocaleString() }}
                                </div>
                                <hr>
                                <p style="white-space: pre-wrap;">{{ message.message }}</p>
                            </div>
                        </section>

                        <section class="admin-panel mb-4" v-if="message.replies && message.replies.length > 0">
                            <div class="admin-panel__header">
                                <h3 class="admin-panel__title">Reply History</h3>
                            </div>
                            <div class="admin-panel__body p-4">
                                <div v-for="reply in message.replies" :key="reply.id" class="card mb-3 border-start border-4 border-primary">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <strong>{{ reply.admin ? reply.admin.name : 'Admin' }}</strong>
                                            <small class="text-muted">{{ new Date(reply.created_at).toLocaleString() }}</small>
                                        </div>
                                        <p style="white-space: pre-wrap;" class="mb-0">{{ reply.message }}</p>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <div v-if="canReplyToMessages" class="col-md-4">
                        <section class="admin-panel">
                            <div class="admin-panel__header">
                                <h3 class="admin-panel__title">Send Reply</h3>
                            </div>
                            <div class="admin-panel__body p-3">
                                <form @submit.prevent="submitReply">
                                    <div class="mb-3">
                                        <label class="form-label">Message</label>
                                        <textarea v-model="replyForm.message" class="form-control" rows="6" required></textarea>
                                    </div>
                                    <button type="submit" class="btn-admin btn-admin--primary w-100" :disabled="replying">
                                        <span v-if="replying" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                        Send Reply
                                    </button>
                                </form>
                                <div v-if="successMessage" class="alert alert-success mt-3">{{ successMessage }}</div>
                                <div v-if="errorMessage" class="alert alert-danger mt-3">{{ errorMessage }}</div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
            
            <div v-else-if="loading" class="admin-page">
                <div class="admin-skeleton-panel p-4">
                    <div class="admin-skeleton-line admin-skeleton-line--lg mb-3"></div>
                    <div class="admin-skeleton-line mb-3"></div>
                    <div class="admin-skeleton-line admin-skeleton-line--md mb-3"></div>
                </div>
            </div>
        </Transition>
    </AdminLayout>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import { hasAdminPermission } from "@/config/adminAccess";
import { getUserData } from "@/services/auth/authSession";
import AdminLayout from "@/views/admin/layout/AdminLayout.vue";
import api from "@/services/AdminApiClient";

const route = useRoute();
const canReplyToMessages = hasAdminPermission(getUserData() || {}, "contact_messages.reply");
const message = ref(null);
const loading = ref(true);
const replying = ref(false);

const replyForm = ref({
    message: ''
});

const successMessage = ref('');
const errorMessage = ref('');

const fetchMessage = async () => {
    loading.value = true;
    try {
        const response = await api.get(`/admin/contact-messages/${route.params.id}`);
        message.value = response.data.data;
    } catch (error) {
        console.error("Failed to fetch message details:", error);
    } finally {
        loading.value = false;
    }
};

const submitReply = async () => {
    replying.value = true;
    successMessage.value = '';
    errorMessage.value = '';

    try {
        await api.post(`/admin/contact-messages/${route.params.id}/reply`, replyForm.value);
        successMessage.value = 'Reply sent successfully.';
        replyForm.value.message = '';
        fetchMessage(); // Refresh to show new reply and update status
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Failed to send reply.';
    } finally {
        replying.value = false;
    }
};

onMounted(() => {
    fetchMessage();
});
</script>
