<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    productos: Array,
});

const form = useForm({
    producto_id: '',
    tipo: 'entrada',
    cantidad: 1,
});

const selectedProduct = computed(() => {
    return props.productos.find(p => p.id === form.producto_id) || null;
});

const submit = () => {
    form.post(route('movimientos.store'));
};
</script>

<template>
    <Head title="Registrar Movimiento" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Registrar Movimiento</h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div v-if="$page.props.flash && $page.props.flash.error" class="mb-4 text-red-600 bg-red-100 p-3 rounded">
                            {{ $page.props.flash.error }}
                        </div>
                        
                        <form @submit.prevent="submit" class="space-y-6">
                            
                            <div>
                                <InputLabel for="producto_id" value="Producto" />
                                <select id="producto_id" v-model="form.producto_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" required>
                                    <option value="" disabled>Seleccionar producto...</option>
                                    <option v-for="prod in productos" :key="prod.id" :value="prod.id">
                                        {{ prod.codigo }} - {{ prod.nombre }} (Stock actual: {{ prod.stock_actual }})
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.producto_id" />
                            </div>

                            <div v-if="selectedProduct" class="bg-blue-50 p-4 rounded-md flex items-center gap-4">
                                <img v-if="selectedProduct.imagen" :src="`/storage/${selectedProduct.imagen}`" class="w-16 h-16 object-cover rounded" />
                                <div>
                                    <p class="font-bold">{{ selectedProduct.nombre }}</p>
                                    <p class="text-sm text-gray-600">Stock actual: <span class="font-bold text-gray-900">{{ selectedProduct.stock_actual }}</span> | Stock mínimo: {{ selectedProduct.stock_minimo }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="tipo" value="Tipo de Movimiento" />
                                    <select id="tipo" v-model="form.tipo" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" required>
                                        <option value="entrada">Entrada (Añadir al stock)</option>
                                        <option value="salida">Salida (Restar al stock)</option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.tipo" />
                                </div>
                                <div>
                                    <InputLabel for="cantidad" value="Cantidad" />
                                    <TextInput id="cantidad" type="number" min="1" class="mt-1 block w-full" v-model="form.cantidad" required />
                                    <InputError class="mt-2" :message="form.errors.cantidad" />
                                </div>
                            </div>

                            <div class="flex items-center gap-4 pt-4 border-t">
                                <PrimaryButton :disabled="form.processing">Registrar Movimiento</PrimaryButton>
                                <Link :href="route('movimientos.index')" class="text-gray-600 hover:underline">Cancelar</Link>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
