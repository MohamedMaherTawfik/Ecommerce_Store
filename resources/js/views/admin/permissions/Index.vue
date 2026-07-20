<template><AdminLayout><div class="admin-page"><section class="admin-page-header"><div><div class="admin-page-kicker"><i class="bi bi-shield-lock"></i> Security</div><h2 class="admin-page-title">Role permissions</h2></div></section><section class="admin-panel"><div class="admin-panel__body">
<div class="d-flex gap-2 mb-4"><input v-model="role" class="form-control admin-control" placeholder="Role name, e.g. manager"><button class="btn-admin" @click="loadRole">Load role</button><button class="btn-admin btn-admin--primary" @click="save">Save permissions</button></div>
<div v-for="(items,module) in permissions" :key="module" class="mb-4"><h3 class="text-capitalize">{{module}}</h3><label v-for="item in items" :key="item.id" class="d-block py-1"><input v-model="selected" type="checkbox" :value="item.name"> {{item.label}} <small>({{item.name}})</small></label></div>
</div></section></div></AdminLayout></template>
<script setup>
import {onMounted,ref} from "vue"; import AdminLayout from "@/views/admin/layout/AdminLayout.vue"; import service from "@/services/admin/permissionService";
const permissions=ref({}),roles=ref({}),role=ref("manager"),selected=ref([]); const load=async()=>{const data=(await service.list()).data;permissions.value=data.permissions;roles.value=data.roles}; const loadRole=()=>selected.value=roles.value[role.value]||[]; const save=async()=>{await service.update(role.value,selected.value);await load()}; onMounted(load);
</script>
