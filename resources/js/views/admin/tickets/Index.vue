<template><AdminLayout><div class="admin-page">
<section class="admin-page-header"><div><div class="admin-page-kicker"><i class="bi bi-life-preserver"></i> Support</div><h2 class="admin-page-title">Tickets</h2></div></section>
<section class="admin-panel"><div class="admin-panel__body">
<div class="d-flex gap-2 mb-3"><input v-model="filters.search" class="form-control admin-control" placeholder="Search"><select v-model="filters.status" class="form-select admin-control"><option value="">All statuses</option><option v-for="s in statuses" :key="s">{{s}}</option></select><select v-model="filters.priority" class="form-select admin-control"><option value="">All priorities</option><option v-for="p in priorities" :key="p">{{p}}</option></select><button class="btn-admin" @click="load">Filter</button></div>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Ticket</th><th>Customer</th><th>Status</th><th>Priority</th><th>Updated</th></tr></thead><tbody>
<tr v-for="t in tickets" :key="t.id"><td><RouterLink :to="`/admin/tickets/${t.id}`">{{t.ticket_number}} - {{t.subject}}</RouterLink></td><td>{{t.user?.name}}</td><td>{{t.status}}</td><td>{{t.priority}}</td><td>{{date(t.last_reply_at)}}</td></tr>
</tbody></table></div></div></section></div></AdminLayout></template>
<script setup>
import {onMounted,reactive,ref} from "vue"; import {RouterLink} from "vue-router"; import AdminLayout from "@/views/admin/layout/AdminLayout.vue"; import service from "@/services/admin/ticketService";
const tickets=ref([]),filters=reactive({search:"",status:"",priority:""}); const statuses=["open","pending","admin_reply","customer_reply","closed"],priorities=["low","normal","high","urgent"];
const load=async()=>{tickets.value=(await service.list(filters)).data?.data||[]}; const date=v=>v?new Date(v).toLocaleString():"-"; onMounted(load);
</script>
