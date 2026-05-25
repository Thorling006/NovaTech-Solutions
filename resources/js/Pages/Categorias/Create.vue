<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';

const form = useForm({
    nombre: '',
    estado: true,
});

const submit = () => {
    form.post(route('categorias.store'));
};
</script>

<template>
    <Head title="Nueva Categoría" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-white leading-tight">Nueva Categoría</h2>
        </template>

        <div class="py-8">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="card p-8">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <InputLabel for="nombre" value="Nombre de la Categoría" />
                            <TextInput id="nombre" type="text" class="mt-1 block w-full" v-model="form.nombre" required autofocus />
                            <InputError class="mt-2" :message="form.errors.nombre" />
                        </div>

                        <div class="flex items-center">
                            <label class="flex items-center">
                                <Checkbox name="estado" v-model:checked="form.estado" />
                                <span class="ms-2 text-sm text-zinc-400">Categoría Activa</span>
                            </label>
                        </div>

                        <div class="flex items-center gap-4 pt-4 border-t border-zinc-800/50">
                            <PrimaryButton :disabled="form.processing">Guardar</PrimaryButton>
                            <Link :href="route('categorias.index')" class="text-zinc-500 hover:text-white text-sm transition-colors">Cancelar</Link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
