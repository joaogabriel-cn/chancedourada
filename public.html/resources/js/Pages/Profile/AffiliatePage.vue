<template>
    <BaseLayout>
        <div class="w-full min-h-[calc(100vh-80px)] bg-[#0a0d0b] rounded-lg p-[10px] px-[16px] text-white">
            <div class="bg-[#171717] rounded-[16px] p-[10px] pt-[20px] min-h-[calc(100vh-80px)]">
                <div class="max-w-4xl mx-auto !h-[100%]">
                    <!-- Loading state -->
                    <div v-if="isLoading" class="flex flex-col items-center justify-center text-center py-20">
                        <svg aria-hidden="true" class="w-8 h-8 text-gray-200 animate-spin dark:text-gray-600 fill-green-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C0 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                            <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/>
                        </svg>
                        <span class="mt-3 text-white block">Carregando...</span>
                    </div>

                    <!-- Conteúdo principal -->
                    <div v-else class="space-y-6">
                        <!-- Header -->
                        <div class="text-center">
                            <h1 class="text-3xl font-bold text-white mb-2">Programa de Afiliados</h1>
                            <p class="text-gray-400">Compartilhe seu link e ganhe comissões por cada indicação!</p>
                        </div>

                        <!-- Informações do usuário -->
                        <div class="bg-gradient-to-r from-[#1a1a1a] to-[#2a2a2a] rounded-xl p-6 border border-[rgba(255,255,255,.05)]">
                            <div class="flex items-center justify-center gap-4">
                                <div class="w-16 h-16 rounded-full bg-gradient-to-br from-yellow-500 to-orange-600 flex items-center justify-center text-2xl">
                                    <i class="fa-solid fa-user"></i>
                                </div>
                                <div class="text-center">
                                    <h2 class="text-xl font-bold text-white">{{ userData.name || 'Usuário' }}</h2>
                                    <div class="flex items-center justify-center gap-2 mt-1">
                                        <span class="bg-blue-500/20 text-blue-400 px-3 py-1 rounded-full text-sm border border-blue-500/30">
                                            <i class="fa-solid fa-gem mr-1"></i>
                                            Afiliado
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Link de referência -->
                        <div class="bg-[#1a1a1a] rounded-xl p-6 border border-[rgba(255,255,255,.05)]">
                            <h2 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                                <i class="fa-solid fa-link text-blue-400"></i>
                                Seu Link de Referência
                            </h2>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">Seu Código</label>
                                    <div class="flex gap-2">
                                        <input 
                                            type="text" 
                                            readonly 
                                            :value="referencecode || 'Clique para gerar seu código'"
                                            class="flex-1 p-3 bg-gray-800 rounded-lg border border-gray-600 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition text-sm text-white" 
                                            id="referenceCode"
                                        >
                                        <button 
                                            v-if="!referencecode" 
                                            @click.prevent="generateCode" 
                                            :disabled="isLoadingGenerate"
                                            class="bg-blue-500 text-white px-4 py-3 rounded-lg hover:bg-blue-600 transition disabled:opacity-50 text-sm font-medium"
                                        >
                                            {{ isLoadingGenerate ? 'Gerando...' : 'Gerar' }}
                                        </button>
                                        <button 
                                            v-else 
                                            @click.prevent="copyCode"
                                            class="bg-green-500 text-white px-4 py-3 rounded-lg hover:bg-green-600 transition text-sm font-medium"
                                        >
                                            Copiar
                                        </button>
                                    </div>
                                </div>
                                <div v-if="referencelink">
                                    <label class="block text-sm font-medium text-gray-300 mb-2">Link Completo</label>
                                    <div class="flex gap-2">
                                        <input 
                                            type="text" 
                                            readonly 
                                            :value="referencelink"
                                            class="flex-1 p-3 bg-gray-800 rounded-lg border border-gray-600 text-sm text-gray-300" 
                                        >
                                        <button 
                                            @click.prevent="copyLink"
                                            class="bg-purple-500 text-white px-4 py-3 rounded-lg hover:bg-purple-600 transition text-sm font-medium"
                                        >
                                            <i class="fa-solid fa-share-nodes mr-1"></i>
                                            Compartilhar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Como funciona -->
                        <div class="bg-[#1a1a1a] rounded-xl p-6 border border-[rgba(255,255,255,.05)]">
                            <h2 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                                <i class="fa-solid fa-info-circle text-green-400"></i>
                                Como Funciona
                            </h2>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="text-center p-4">
                                    <div class="w-12 h-12 rounded-full bg-blue-500/20 flex items-center justify-center mx-auto mb-3">
                                        <i class="fa-solid fa-share text-blue-400 text-xl"></i>
                                    </div>
                                    <h3 class="font-semibold text-white mb-2">1. Compartilhe</h3>
                                    <p class="text-sm text-gray-400">Use seu link de referência para convidar amigos</p>
                                </div>
                                <div class="text-center p-4">
                                    <div class="w-12 h-12 rounded-full bg-green-500/20 flex items-center justify-center mx-auto mb-3">
                                        <i class="fa-solid fa-user-plus text-green-400 text-xl"></i>
                                    </div>
                                    <h3 class="font-semibold text-white mb-2">2. Eles se Cadastram</h3>
                                    <p class="text-sm text-gray-400">Seus convidados se registram usando seu link</p>
                                </div>
                                <div class="text-center p-4">
                                    <div class="w-12 h-12 rounded-full bg-yellow-500/20 flex items-center justify-center mx-auto mb-3">
                                        <i class="fa-solid fa-dollar-sign text-yellow-400 text-xl"></i>
                                    </div>
                                    <h3 class="font-semibold text-white mb-2">3. Você Ganha</h3>
                                    <p class="text-sm text-gray-400">Receba comissões por cada indicação ativa</p>
                                </div>
                            </div>
                        </div>

                        <!-- Informações de comissão -->
                        <div class="bg-[#1a1a1a] rounded-xl p-6 border border-[rgba(255,255,255,.05)]">
                            <h2 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                                <i class="fa-solid fa-percentage text-yellow-400"></i>
                                Suas Comissões
                            </h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-[#262626] rounded-lg p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-yellow-500/20 flex items-center justify-center">
                                            <i class="fa-solid fa-percentage text-yellow-400"></i>
                                        </div>
                                        <div>
                                            <div class="font-semibold text-white">RevShare</div>
                                            <div class="text-sm text-gray-400">Comissão sobre ganhos dos indicados</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-[#262626] rounded-lg p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-purple-500/20 flex items-center justify-center">
                                            <i class="fa-solid fa-handshake text-purple-400"></i>
                                        </div>
                                        <div>
                                            <div class="font-semibold text-white">CPA</div>
                                            <div class="text-sm text-gray-400">Comissão por ativação</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </BaseLayout>
</template>

<script>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import HttpApi from "@/Services/HttpApi.js";
import {useToast} from "vue-toastification";
import {useAuthStore} from "@/Stores/Auth.js";

export default {
    components: { BaseLayout },
    data() {
        return {
            isLoading: true,
            isLoadingGenerate: false,
            referencecode: '',
            referencelink: ''
        }
    },
    computed: {
        userData() {
            const authStore = useAuthStore();
            return authStore.user;
        }
    },
    methods: {
        copyCode: function() {
            const _toast = useToast();
            var inputElement = document.getElementById("referenceCode");
            inputElement.select();
            inputElement.setSelectionRange(0, 99999);
            document.execCommand("copy");
            _toast.success('Código copiado com sucesso');
        },
        copyLink: function() {
            const _toast = useToast();
            navigator.clipboard.writeText(this.referencelink).then(() => {
                _toast.success('Link copiado com sucesso');
            });
        },
        getCode: function() {
            const _this = this;
            _this.isLoading = true;

            HttpApi.get('profile/affiliates/')
                .then(response => {
                    if(response.data.code !== '' && response.data.code !== undefined && response.data.code !== null) {
                        _this.referencecode = response.data.code;
                    }
                    _this.referencelink = response.data.url;
                    _this.isLoading = false;
                })
                .catch(error => {
                    console.error('Erro ao carregar código:', error);
                    _this.isLoading = false;
                });
        },
        generateCode: function() {
            const _this = this;
            const _toast = useToast();
            _this.isLoadingGenerate = true;

            HttpApi.get('profile/affiliates/generate')
                .then(response => {
                    if(response.data.status) {
                        _this.getCode();
                        _toast.success('Seu código foi gerado com sucesso');
                    }
                    _this.isLoadingGenerate = false;
                })
                .catch(error => {
                    _toast.error('Erro ao gerar código');
                    _this.isLoadingGenerate = false;
                });
        }
    },
    created() {
        if (!this.userData || !this.userData.id) {
            console.error('Usuário não está logado ou dados não disponíveis');
            this.isLoading = false;
            return;
        }
        
        // Carrega apenas o código/link que já funciona
        this.getCode();
    }
};
</script>

<style scoped>
/* Estilos personalizados para melhor responsividade */
@media (max-width: 768px) {
    .grid {
        grid-template-columns: 1fr;
    }
}
</style>