<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({ producto: Object, categorias: Array });

const form = useForm({
    _method: 'PUT', codigo: props.producto.codigo, nombre: props.producto.nombre,
    descripcion: props.producto.descripcion || '', categoria_id: props.producto.categoria_id,
    precio: props.producto.precio, stock_minimo: props.producto.stock_minimo,
    estado: props.producto.estado, imagen: null,
});

const submit = () => { form.post(route('productos.update', props.producto.id)); };
</script>

<template>
    <Head title="Editar Producto" />
    <AuthenticatedLayout>
        <template #header><h2 class="font-semibold text-xl text-white leading-tight">Editar Producto</h2></template>
        <div class="py-8">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="card p-8">
                    <form @submit.prevent="submit" class="space-y-6" enctype="multipart/form-data">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
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
                            <textarea id="descripcion" class="textarea-dark mt-1 block w-full" v-model="form.descripcion" rows="3"></textarea>
                            <InputError class="mt-2" :message="form.errors.descripcion" />
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <InputLabel for="categoria_id" value="Categoría" />
                                <select id="categoria_id" v-model="form.categoria_id" class="select-dark mt-1 block w-full" required>
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
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <InputLabel for="stock_minimo" value="Stock Mínimo" />
                                <TextInput id="stock_minimo" type="number" min="0" class="mt-1 block w-full" v-model="form.stock_minimo" required />
                                <InputError class="mt-2" :message="form.errors.stock_minimo" />
                            </div>
                            <div>
                                <InputLabel for="estado" value="Estado" />
                                <select id="estado" v-model="form.estado" class="select-dark mt-1 block w-full" required>
                                    <option value="disponible">Disponible</option>
                                    <option value="stock_bajo">Stock Bajo</option>
                                    <option value="agotado">Agotado</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.estado" />
                            </div>
                        </div>
                        <div>
                            <div v-if="producto.imagen" class="mb-3">
                                <InputLabel value="Imagen Actual" />
                                <img :src="`/storage/${producto.imagen}`" class="mt-2 w-24 h-24 object-cover rounded-xl border border-zinc-800" />
                            </div>
                            <InputLabel for="imagen" value="Nueva Imagen (Opcional)" />
                            <input type="file" id="imagen" @input="form.imagen = $event.target.files[0]" class="mt-1 block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-zinc-800 file:text-zinc-300 hover:file:bg-zinc-700 file:transition-colors file:cursor-pointer" accept="image/*" />
                            <InputError class="mt-2" :message="form.errors.imagen" />
                        </div>
                        <div class="flex items-center gap-4 pt-4 border-t border-zinc-800/50">
                            <PrimaryButton :disabled="form.processing">Actualizar Producto</PrimaryButton>
                            <Link :href="route('productos.index')" class="text-zinc-500 hover:text-white text-sm transition-colors">Cancelar</Link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
