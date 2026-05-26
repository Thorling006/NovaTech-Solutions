<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;
const photoInput = ref(null);
const photoPreviewName = ref('');

const form = useForm({
    _method: 'patch',
    name: user.name,
    email: user.email,
    foto: null,
});

const triggerPhotoSelect = () => {
    photoInput.value.click();
};

const onPhotoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.foto = file;
        photoPreviewName.value = file.name;
    }
};

const submitProfile = () => {
    form.post(route('profile.update'), {
        onSuccess: () => {
            photoPreviewName.value = '';
        },
        onError: () => {
            if (form.errors.foto) {
                form.foto = null;
                photoPreviewName.value = '';
            }
        }
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-white">
                Profile Information
            </h2>

            <p class="mt-1 text-sm text-zinc-400">
                Update your account's profile information and email address.
            </p>
        </header>

        <form
            @submit.prevent="submitProfile"
            class="mt-6 space-y-6"
            enctype="multipart/form-data"
        >
            <!-- Profile Photo Section -->
            <div class="space-y-2 mb-6">
                <InputLabel for="foto" value="Foto de Perfil" />
                <div class="flex items-center gap-4">
                    <div class="relative w-20 h-20 rounded-full overflow-hidden border border-zinc-700 bg-zinc-800 flex items-center justify-center">
                        <img v-if="user.foto_url" :src="user.foto_url" alt="Foto de perfil" class="w-full h-full object-cover" />
                        <span v-else class="text-2xl font-bold text-zinc-500 uppercase">{{ user.name.charAt(0) }}</span>
                    </div>

                    <div class="flex flex-col gap-2">
                        <input
                            id="foto"
                            type="file"
                            ref="photoInput"
                            class="hidden"
                            @change="onPhotoChange"
                            accept="image/*"
                        />
                        <button
                            type="button"
                            @click="triggerPhotoSelect"
                            class="px-4 py-2 bg-zinc-800 hover:bg-zinc-700 border border-zinc-700 rounded-md text-sm font-medium text-white transition duration-150"
                        >
                            Seleccionar Foto
                        </button>
                        <span v-if="photoPreviewName" class="text-xs text-zinc-400">
                            {{ photoPreviewName }}
                        </span>
                        <InputError class="mt-1" :message="form.errors.foto" />
                    </div>
                </div>
            </div>
            <div>
                <InputLabel for="name" value="Name" />
                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-zinc-400">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="text-zinc-300 underline hover:text-white transition-colors duration-200"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-emerald-400"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out duration-300"
                    enter-from-class="opacity-0 -translate-x-2"
                    leave-active-class="transition ease-in-out duration-300"
                    leave-to-class="opacity-0 -translate-x-2"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-emerald-400"
                    >
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
