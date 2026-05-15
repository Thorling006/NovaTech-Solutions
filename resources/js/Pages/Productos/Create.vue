<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    categorias: Array,
});

const form = useForm({
    codigo: '',
    nombre: '',
    descripcion: '',
    categoria_id: '',
    precio: '',
    stock_minimo: '',
    imagen: null,
});

const submit = () => {
    form.post(route('productos.store'));
};
</script>

<template>
    <Head title="Nuevo Producto" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nuevo Producto</h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <form @submit.prevent="submit" class="space-y-6" enctype="multipart/form-data">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="codigo" value="Código" />
                                    <TextInput id="codigo" type="text" class="mt-1 block w-full" v-model="form.codigo" required autofocus />
                                    <InputError class="mt-2" :message="form.errors.codigo" />
                                </div>
                                <div>
                                    <InputLabel for="nombre" value="Nombre del Producto" />
                                    <TextInput id="nombre" type="text" class="mt-1 block w-full" v-model="form.nombre" required />
                                    <InputError class="mt-2" :message="form.errors.nombre" />
                                </div>
                            </div>

                            <div>
                                <InputLabel for="descripcion" value="Descripción (Opcional)" />
                                <textarea id="descripcion" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" v-model="form.descripcion" rows="3"></textarea>
                                <InputError class="mt-2" :message="form.errors.descripcion" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <InputLabel for="categoria_id" value="Categoría" />
                                    <select id="categoria_id" v-model="form.categoria_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" required>
                                        <option value="" disabled>Seleccionar...</option>
                                        <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.nombre }}</option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.categoria_id" />
                                </div>
                                <div>
                                    <InputLabel for="precio" value="Precio ($)" />
                                    <TextInput id="precio" type="number" step="0.01" min="0" class="mt-1 block w-full" v-model="form.precio" required />
                                    <InputError class="mt-2" :message="form.errors.precio" />
                                </div>
                                <div>
                                    <InputLabel for="stock_minimo" value="Stock Mínimo" />
                                    <TextInput id="stock_minimo" type="number" min="0" class="mt-1 block w-full" v-model="form.stock_minimo" required />
                                    <InputError class="mt-2" :message="form.errors.stock_minimo" />
                                </div>
                            </div>

                            <div>
                                <InputLabel for="imagen" value="Imagen del Producto" />
                                <input type="file" id="imagen" @input="form.imagen = $event.target.files[0]" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" accept="image/*" />
                                <InputError class="mt-2" :message="form.errors.imagen" />
                            </div>

                            <div class="flex items-center gap-4 pt-4 border-t">
                                <PrimaryButton :disabled="form.processing">Guardar Producto</PrimaryButton>
                                <Link :href="route('productos.index')" class="text-gray-600 hover:underline">Cancelar</Link>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
