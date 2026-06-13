<script setup>
import { ref, onMounted, nextTick } from 'vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import axios from 'axios';
import { marked } from 'marked';

const isOpen = ref(false);
const isTyping = ref(false);
const inputMessage = ref('');
const messages = ref([
    { role: 'model', text: 'Hello! I am Khadija, your AI assistant. How can I help you today?' }
]);

const chatContainer = ref(null);

const toggleChat = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        scrollToBottom();
    }
};

const scrollToBottom = () => {
    nextTick(() => {
        if (chatContainer.value) {
            chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
        }
    });
};

const sendMessage = async () => {
    if (!inputMessage.value.trim() || isTyping.value) return;

    const userText = inputMessage.value.trim();
    messages.value.push({ role: 'user', text: userText });
    inputMessage.value = '';
    isTyping.value = true;
    scrollToBottom();

    try {
        // Format for backend ChatbotController format
        const formattedMessages = messages.value.map(msg => ({
            role: msg.role === 'model' ? 'model' : 'user',
            parts: [{ text: msg.text }]
        }));

        const response = await axios.post('/chat', { messages: formattedMessages });

        if (response.data && response.data.reply) {
            messages.value.push({ role: 'model', text: response.data.reply });
        } else {
            messages.value.push({ role: 'model', text: "I'm sorry, I encountered an error and couldn't process your request." });
        }
    } catch (error) {
        console.error("Chat error:", error);
        messages.value.push({ role: 'model', text: "I'm having trouble connecting right now. Please try again later." });
    } finally {
        isTyping.value = false;
        scrollToBottom();
    }
};

const formatMessage = (text) => {
    return marked(text, { breaks: true });
};
</script>

<template>
    <div class="fixed bottom-6 right-6 z-[9999] flex flex-col items-end">
        <!-- Chat Window -->
        <div v-if="isOpen" 
             class="bg-white rounded-2xl shadow-2xl w-80 sm:w-96 mb-4 overflow-hidden flex flex-col border border-gray-100 transition-all duration-300"
             style="height: 500px; max-height: calc(100vh - 120px);">
             
            <!-- Header -->
            <div class="bg-[#1C0D82] text-white p-4 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
                        <i class="pi pi-sparkles text-white text-sm"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm leading-tight">Khadija AI Assistant</h3>
                        <p class="text-xs text-blue-200">Online</p>
                    </div>
                </div>
                <button @click="toggleChat" class="text-white hover:bg-white/20 w-8 h-8 flex items-center justify-center rounded-full transition-colors">
                    <i class="pi pi-times"></i>
                </button>
            </div>

            <!-- Messages List -->
            <div ref="chatContainer" class="flex-1 p-4 overflow-y-auto bg-gray-50 flex flex-col gap-4">
                <div v-for="(msg, index) in messages" :key="index"
                     class="flex" :class="msg.role === 'user' ? 'justify-end' : 'justify-start'">
                    
                    <div class="max-w-[85%] p-3 rounded-2xl text-sm leading-relaxed"
                         :class="msg.role === 'user' ? 'bg-[#1C0D82] text-white rounded-tr-none' : 'bg-white border border-gray-100 text-gray-800 rounded-tl-none shadow-sm'">
                        <div v-if="msg.role === 'user'">{{ msg.text }}</div>
                        <div v-else class="prose prose-sm prose-p:my-1 prose-ul:my-1 prose-li:my-0 prose-a:text-[#1C0D82] max-w-none" v-html="formatMessage(msg.text)"></div>
                    </div>
                </div>

                <!-- Typing Indicator -->
                <div v-if="isTyping" class="flex justify-start">
                    <div class="bg-white border border-gray-100 rounded-2xl rounded-tl-none shadow-sm p-4 flex gap-1">
                        <div class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce"></div>
                        <div class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                        <div class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
                    </div>
                </div>
            </div>

            <!-- Input Area -->
            <div class="p-3 bg-white border-t border-gray-100">
                <form @submit.prevent="sendMessage" class="flex gap-2">
                    <InputText v-model="inputMessage" placeholder="Ask a question..." class="flex-1 !rounded-full !bg-gray-50 border-none" :disabled="isTyping" />
                    <Button type="submit" icon="pi pi-send" class="!rounded-full !w-10 !h-10 !p-0 flex items-center justify-center !bg-[#1C0D82] !border-none" :disabled="!inputMessage.trim() || isTyping" />
                </form>
            </div>
        </div>

        <!-- Floating Button -->
        <Button v-if="!isOpen" @click="toggleChat" 
                class="!w-14 !h-14 !rounded-full shadow-lg !bg-[#1C0D82] !border-[#1C0D82] hover:!bg-[#150a61] flex items-center justify-center p-button-icon-only transition-transform hover:scale-105">
            <i class="pi pi-comments text-2xl text-white"></i>
        </Button>
    </div>
</template>

<style scoped>
/* Scrollbar styling for the chat container */
::-webkit-scrollbar {
    width: 6px;
}
::-webkit-scrollbar-track {
    background: transparent; 
}
::-webkit-scrollbar-thumb {
    background: #cbd5e1; 
    border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
    background: #94a3b8; 
}
</style>
