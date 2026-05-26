<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { ref, computed } from 'vue';

const props = defineProps({ 
    movimiento: Object,
    productos: Array 
});

const form = useForm({ 
    producto_id: props.movimiento.producto_id, 
    tipo: props.movimiento.tipo, 
    cantidad: props.movimiento.cantidad 
});

const selectedProduct = computed(() => props.productos.find(p => p.id === form.producto_id) || null);

const submit = () => { 
    form.put(route('movimientos.update', props.movimiento.id)); 
};
</script>

<template>
    <Head title="Editar Movimiento" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-white leading-tight">Editar Movimiento {{ movimiento.id }}</h2>
                <Link :href="route('movimientos.index')" class="text-sm link-action">← Volver</Link>
            </div>
        </template>
        
        <div class="py-8">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="card p-8">
                    <div v-if="$page.props.flash && $page.props.flash.error" class="mb-4 flash-error">
                        {{ $page.props.flash.error }}
                    </div>
                    
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <InputLabel for="producto_id" value="Producto" />
                            <select id="producto_id" v-model="form.producto_id" class="select-dark mt-1 block w-full" required>
                                <option value="" disabled>Seleccionar producto...</option>
                                <option v-for="prod in productos" :key="prod.id" :value="prod.id">
                                    {{ prod.codigo }} - {{ prod.nombre }} (Stock: {{ prod.stock_actual }})
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.producto_id" />
                        </div>

                        <Transition enter-active-class="transition ease-out duration-300" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
                            <div v-if="selectedProduct" class="bg-zinc-800/40 border border-zinc-800 p-4 rounded-xl flex items-center gap-4">
                                <img v-if="selectedProduct.imagen" :src="`/storage/${selectedProduct.imagen}`" class="w-14 h-14 object-cover rounded-xl border border-zinc-700" />
                                <div>
                                    <p class="font-medium text-zinc-200">{{ selectedProduct.nombre }}</p>
                                    <p class="text-sm text-zinc-500">
                                        Stock actual: <span class="font-medium text-white">{{ selectedProduct.stock_actual }}</span> 
                                        · Mínimo: {{ selectedProduct.stock_minimo }}
                                    </p>
                                </div>
                            </div>
                        </Transition>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <InputLabel for="tipo" value="Tipo de Movimiento" />
                                <select id="tipo" v-model="form.tipo" class="select-dark mt-1 block w-full" required>
                                    <option value="entrada">Entrada (Añadir)</option>
                                    <option value="salida">Salida (Restar)</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.tipo" />
                            </div>
                            <div>
                                <InputLabel for="cantidad" value="Cantidad" />
                                <TextInput id="cantidad" type="number" min="1" class="mt-1 block w-full" v-model="form.cantidad" required />
                                <InputError class="mt-2" :message="form.errors.cantidad" />
                            </div>
                        </div>

                        <div class="flex items-center gap-4 pt-4 border-t border-zinc-800/50">
                            <PrimaryButton :disabled="form.processing">Actualizar Movimiento</PrimaryButton>
                            <Link :href="route('movimientos.index')" class="text-zinc-500 hover:text-white text-sm transition-colors">Cancelar</Link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
