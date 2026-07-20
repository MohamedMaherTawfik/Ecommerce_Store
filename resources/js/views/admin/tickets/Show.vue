<template><AdminLayout><div class="admin-page" v-if="ticket">
<section class="admin-page-header"><div><div class="admin-page-kicker">{{ticket.ticket_number}}</div><h2 class="admin-page-title">{{ticket.subject}}</h2><p>{{ticket.user?.name}} - {{ticket.user?.email}}</p></div></section>
<section class="admin-panel"><div class="admin-panel__body">
<div v-if="canManageTickets" class="d-flex gap-2 mb-4"><select v-model="ticket.status" class="form-select admin-control" @change="update"><option v-for="s in statuses" :key="s">{{s}}</option></select><select v-model="ticket.priority" class="form-select admin-control" @change="update"><option v-for="p in priorities" :key="p">{{p}}</option></select></div>
<article v-for="m in ticket.messages" :key="m.id" class="p-3 mb-3 rounded border" :class="m.is_admin?'bg-light':''"><strong>{{m.user?.name}} <small>{{m.is_admin?'Admin':'Customer'}}</small></strong><p class="mb-0 mt-2">{{m.message}}</p></article>
<form v-if="canReplyToTickets" class="d-flex gap-2" @submit.prevent="reply"><textarea v-model="message" class="form-control admin-control" required></textarea><button class="btn-admin" :disabled="ticket.status==='closed'">Reply</button></form>
</div></section></div></AdminLayout></template>
<script setup>
import {onMounted,ref} from "vue"; import {useRoute} from "vue-router"; import {hasAdminPermission} from "@/config/adminAccess"; import {getUserData} from "@/services/auth/authSession"; import AdminLayout from "@/views/admin/layout/AdminLayout.vue"; import service from "@/services/admin/ticketService";
const admin=getUserData()||{},canManageTickets=hasAdminPermission(admin,"tickets.manage"),canReplyToTickets=hasAdminPermission(admin,"tickets.reply");
const route=useRoute(),ticket=ref(null),message=ref(""); const statuses=["open","pending","admin_reply","customer_reply","closed"],priorities=["low","normal","high","urgent"];
const load=async()=>ticket.value=(await service.show(route.params.id)).data; const update=()=>service.update(ticket.value.id,{status:ticket.value.status,priority:ticket.value.priority}); const reply=async()=>{await service.reply(ticket.value.id,message.value);message.value="";await load()}; onMounted(load);
</script>
