<template>
    <AuthLayout>
        <LoadingComponent :isLoading="isLoading">
            <div class="text-center">
                <span>{{ $t('Loading') }}</span>
            </div>
        </LoadingComponent>
        <div v-if="!isLoading" class="my-auto">
            <div class="px-4 py-5">
                <div class="min-h-[calc(100vh-565px)] text-center flex flex-col items-center justify-center">
                    <div class="w-full bg-white rounded-lg shadow-lg md:mt-0 sm:max-w-md xl:p-0 dark:bg-gray-700 dark:border-gray-700">
                        <div class="p-6 space-y-4 md:space-y-6 sm:p-8">
                            <h1 class="mb-8 text-3xl text-center">{{ $t('Register') }}</h1>

                            <div class="mt-4 px-4">
                                <form @submit.prevent="registerSubmit" method="post" action="" class="">
                                    <div class="relative mb-3">
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                                            <i class="fa-regular fa-user text-success-emphasis"></i>
                                        </div>
                                        <input type="text"
                                               name="name"
                                               v-model="registerForm.name"
                                               class="input-group"
                                               :placeholder="$t('Enter name')"
                                               required
                                        >
                                    </div>

                                    <div class="relative mb-3">
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                                            <i class="fa-regular fa-envelope text-success-emphasis"></i>
                                        </div>
                                        <input type="email"
                                               name="email"
                                               v-model="registerForm.email"
                                               class="input-group"
                                               :placeholder="$t('Enter email or phone')"
                                               required
                                        >
                                    </div>

                                    <div class="relative mb-3">
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                                            <i class="fa-regular fa-lock text-success-emphasis"></i>
                                        </div>
                                        <input :type="typeInputPassword"
                                               name="password"
                                               v-model="registerForm.password"
                                               class="input-group pr-[40px]"
                                               :placeholder="$t('Enter password')"
                                               required
                                        >
                                        <button type="button" @click.prevent="togglePassword" class="absolute inset-y-0 right-0 flex items-center pr-3.5">
                                            <i v-if="typeInputPassword === 'password'" class="fa-regular fa-eye"></i>
                                            <i v-if="typeInputPassword === 'text'" class="fa-sharp fa-regular fa-eye-slash"></i>
                                        </button>
                                    </div>

                                    <div class="relative mb-3">
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                                            <i class="fa-regular fa-lock text-success-emphasis"></i>
                                        </div>
                                        <input :type="typeInputPassword"
                                               name="password_confirmation"
                                               v-model="registerForm.password_confirmation"
                                               class="input-group pr-[40px]"
                                               :placeholder="$t('Confirm the Password')"
                                               required
                                        >
                                        <button type="button" @click.prevent="togglePassword" class="absolute inset-y-0 right-0 flex items-center pr-3.5">
                                            <i v-if="typeInputPassword === 'password'" class="fa-regular fa-eye"></i>
                                            <i v-if="typeInputPassword === 'text'" class="fa-sharp fa-regular fa-eye-slash"></i>
                                        </button>
                                    </div>

                                    <!-- Campo de código de referência (apenas se houver código) -->
                                    <div v-if="registerForm.reference_code" class="relative mb-3">
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                                            <i class="fa-solid fa-gift text-success-emphasis"></i>
                                        </div>
                                        <input type="text" 
                                               name="reference_code" 
                                               v-model="registerForm.reference_code" 
                                               class="input-group" 
                                               :placeholder="$t('Referral Code')" 
                                               readonly>
                                        <div class="text-xs text-green-600 mt-1 ml-3">
                                            <i class="fa-solid fa-check mr-1"></i>
                                            Código de referência aplicado
                                        </div>
                                    </div>

                                    <hr class="mb-3 mt-2 dark:border-gray-600">

                                    <div class="mt-5 w-full">
                                        <button type="submit" class="ui-button-blue rounded w-full mb-3">
                                            {{ $t('Register') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>


<script>

import {useToast} from "vue-toastification";
import {useAuthStore} from "@/Stores/Auth.js";
import HttpApi from "@/Services/HttpApi.js";
import AuthLayout from "@/Layouts/AuthLayout.vue";
import {useRoute, useRouter} from "vue-router";
import {onMounted, reactive} from "vue";
import {useSpinStoreData} from "@/Stores/SpinStoreData.js";
import LoadingComponent from "@/Components/UI/LoadingComponent.vue";

export default {
    props: [],
    components: {LoadingComponent, AuthLayout },
    data() {
        return {
            isLoading: false,
            typeInputPassword: 'password',
            registerForm: {
                name: '',
                email: '',
                password: '',
                password_confirmation: '',
                reference_code: '',
                spin_data: null
            },
        }
    },
    setup() {
        const router = useRouter();
        const routeParams = reactive({
            code: null,
        });

        onMounted(() => {
            const params = new URLSearchParams(window.location.search);
            if (params.has('code')) {
                routeParams.code = params.get('code');
            }
        });

        return {
            routeParams,
            router
        };
    },
    computed: {
        isAuthenticated() {
            const authStore = useAuthStore();
            return authStore.isAuth;
        },
    },
    beforeUnmount() {

    },
    mounted() {
        const router = useRouter();
        if(this.isAuthenticated) {
            router.push({ name: 'home' });
        }

        // Capturar código de referência tanto de parâmetro de rota quanto de query string
        const currentRoute = this.router.currentRoute.value;
        const urlParams = new URLSearchParams(window.location.search);

        // 1) Tentar parâmetro de rota
        if (currentRoute.params && currentRoute.params.code) {
            try {
                const str = atob(currentRoute.params.code);
                const obj = JSON.parse(str);
                this.registerForm.spin_token = currentRoute.params.code;
            } catch (e) {
                this.registerForm.reference_code = currentRoute.params.code;
            }
        }

        // 2) Tentar query string (?code= ou ?ref=)
        const queryCode = urlParams.get('code') || urlParams.get('ref');
        if (!this.registerForm.reference_code && queryCode) {
            this.registerForm.reference_code = queryCode;
        }
    },
    methods: {
        redirectSocialTo: function() {
            return '/auth/redirect/google'
        },
        togglePassword: function() {
            if(this.typeInputPassword === 'password') {
                this.typeInputPassword = 'text';
            }else{
                this.typeInputPassword = 'password';
            }
        },
        registerSubmit: async function(event) {
            const _this = this;
            const _toast = useToast();
            _this.isLoading = true;

            const authStore = useAuthStore();
            await HttpApi.post('auth/register', _this.registerForm)
                .then(response => {
                    if(response.data.access_token !== undefined) {
                        authStore.setToken(response.data.access_token);
                        authStore.setUser(response.data.user);
                        authStore.setIsAuth(true);

                        _this.registerForm = {
                            name: '',
                            email: '',
                            password: '',
                            password_confirmation: '',
                            reference_code: '',
                            spin_data: null
                        };

                        _this.router.push({ name: 'profileDeposit' });
                        _toast.success(_this.$t('Your account has been created successfully'));
                    }

                    _this.isLoading = false;
                })
                .catch(error => {
                    const _this = this;
                    Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {
                        _toast.error(`${value}`);
                    });
                    _this.isLoading = false;
                });
        },
    },
    created() {

    },
    watch: {

    },
};
</script>

<style scoped>

</style>
