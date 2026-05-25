<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({ roles: Array });
const form = useForm({ name: '', email: '', password: '', role_id: '' });
const submit = () => { form.post(route('users.store')); };
</script>

<template>
    <Head title="Nuevo Usuario" />
    <AuthenticatedLayout>
        <template #header><h2 class="font-semibold text-xl text-white leading-tight">Nuevo Usuario</h2></template>
        <div class="py-8">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="card p-8">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <InputLabel for="name" value="Nombre" />
                            <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required autofocus />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>
                        <div>
                            <InputLabel for="email" value="Correo Electrónico" />
                            <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>
                        <div>
                            <InputLabel for="password" value="Contraseña" />
                            <TextInput id="password" type="password" class="mt-1 block w-full" v-model="form.password" required />
                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>
                        <div>
                            <InputLabel for="role_id" value="Rol" />
                            <select id="role_id" v-model="form.role_id" class="select-dark mt-1 block w-full" required>
                                <option value="" disabled>Selecciona un rol</option>
                                <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.nombre }}</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.role_id" />
                        </div>
                        <div class="flex items-center gap-4 pt-4 border-t border-zinc-800/50">
                            <PrimaryButton :disabled="form.processing">Guardar</PrimaryButton>
                            <Link :href="route('users.index')" class="text-zinc-500 hover:text-white text-sm transition-colors">Cancelar</Link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
