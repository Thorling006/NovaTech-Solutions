<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    producto: Object,
    categorias: Array,
});

const form = useForm({
    _method: 'PUT',
    codigo: props.producto.codigo,
    nombre: props.producto.nombre,
    descripcion: props.producto.descripcion || '',
    categoria_id: props.producto.categoria_id,
    precio: props.producto.precio,
    stock_minimo: props.producto.stock_minimo,
    estado: props.producto.estado,
    imagen: null,
});

const submit = () => {
    // Usamos POST con _method: PUT porque estamos enviando archivos
    form.post(route('productos.update', props.producto.id));
};
</script>

<template>
    <Head title="Editar Producto" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar Producto</h2>
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

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="stock_minimo" value="Stock Mínimo" />
                                    <TextInput id="stock_minimo" type="number" min="0" class="mt-1 block w-full" v-model="form.stock_minimo" required />
                                    <InputError class="mt-2" :message="form.errors.stock_minimo" />
                                </div>
                                <div>
                                    <InputLabel for="estado" value="Estado" />
                                    <select id="estado" v-model="form.estado" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" required>
                                        <option value="disponible">Disponible</option>
                                        <option value="stock_bajo">Stock Bajo</option>
                                        <option value="agotado">Agotado</option>
                                        <option value="inactivo">Inactivo</option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.estado" />
                                </div>
                            </div>

                            <div>
                                <InputLabel value="Imagen Actual" v-if="producto.imagen" />
                                <img v-if="producto.imagen" :src="`/storage/${producto.imagen}`" class="mt-2 w-32 h-32 object-cover rounded shadow" />
                                
                                <InputLabel for="imagen" value="Nueva Imagen (Opcional)" class="mt-4" />
                                <input type="file" id="imagen" @input="form.imagen = $event.target.files[0]" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" accept="image/*" />
                                <InputError class="mt-2" :message="form.errors.imagen" />
                            </div>

                            <div class="flex items-center gap-4 pt-4 border-t">
                                <PrimaryButton :disabled="form.processing">Actualizar Producto</PrimaryButton>
                                <Link :href="route('productos.index')" class="text-gray-600 hover:underline">Cancelar</Link>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
