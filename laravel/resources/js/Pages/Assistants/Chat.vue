<script>
import Layout from '@/Layouts/main.vue'
import PageHeader from '@/Components/page-header.vue'
import { useFetchPetition } from '@/Composables/useFetchPetition.js'
import { useAlert } from '@/Composables/useSweetAlert.js'

const { fetchPetition } = useFetchPetition()
const { showAlert } = useAlert()

export default {
    name: 'AssistantsChat',
    components: { Layout, PageHeader },

    data() {
        return {
            messages: [],
            question: '',
            loading: false,
            conversationUuid: null,
        }
    },

    mounted() {
        this.messages.push({
            role: 'assistant',
            content: '¡Hola! Soy el asistente virtual de las Unidades Tecnológicas de Santander. Puedo ayudarte con reglamentos, procesos académicos, calendario y más. ¿En qué puedo ayudarte hoy?',
            sources: null,
            createdAt: new Date().toISOString(),
            showSources: false,
        })
    },

    methods: {
        async sendMessage() {
            const q = this.question.trim()
            if (!q || this.loading) return

            this.messages.push({
                role: 'user',
                content: q,
                sources: null,
                createdAt: new Date().toISOString(),
                showSources: false,
            })

            this.question = ''
            this.loading = true
            this.$nextTick(() => this.scrollToBottom())

            const { ok, data } = await fetchPetition('/chat', {
                method: 'POST',
                body: {
                    question: q,
                    conversation_uuid: this.conversationUuid,
                },
            })

            this.loading = false

            if (ok && data) {
                if (data.conversation_uuid) {
                    this.conversationUuid = data.conversation_uuid
                }
                this.messages.push({
                    ...data.message,
                    showSources: false,
                })
            } else {
                showAlert('error', 'Error de conexión', 'No se pudo obtener respuesta del asistente. Intenta de nuevo.', 3000)
            }

            this.$nextTick(() => this.scrollToBottom())
        },

        handleKeydown(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault()
                this.sendMessage()
            }
        },

        toggleSources(index) {
            this.messages[index].showSources = !this.messages[index].showSources
        },

        scrollToBottom() {
            const el = this.$refs.messagesContainer
            if (el) el.scrollTop = el.scrollHeight
        },

        formatTime(dateStr) {
            return new Date(dateStr).toLocaleTimeString('es-ES', {
                hour: '2-digit',
                minute: '2-digit',
            })
        },

        clearChat() {
            this.conversationUuid = null
            this.messages = [{
                role: 'assistant',
                content: '¡Hola de nuevo! ¿En qué puedo ayudarte?',
                sources: null,
                createdAt: new Date().toISOString(),
                showSources: false,
            }]
        },
    },
}
</script>

<template>
    <Layout>
        <PageHeader title="Asistente Virtual" pageTitle="UTS" />

        <div class="row justify-content-center">
            <div class="col-xxl-8 col-xl-10">

                <!-- Header de la interfaz -->
                <div class="chat-shell">
                    <div class="chat-shell__header">
                        <div class="chat-shell__avatar">
                            <i class="ri-robot-2-line"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-0 fw-semibold">Asistente UTS</h6>
                            <p class="mb-0 chat-shell__model">
                                <span class="chat-shell__dot"></span>
                                Gemma 3N · RAG activo
                            </p>
                        </div>
                        <button
                            class="btn btn-ghost-secondary btn-sm"
                            title="Nueva conversación"
                            @click="clearChat"
                        >
                            <i class="ri-refresh-line me-1"></i>Nueva
                        </button>
                    </div>

                    <!-- Área de mensajes -->
                    <div class="chat-shell__body" ref="messagesContainer">
                        <div
                            v-for="(msg, index) in messages"
                            :key="index"
                            :class="['chat-msg', msg.role === 'user' ? 'chat-msg--user' : 'chat-msg--assistant']"
                        >
                            <!-- Burbuja asistente -->
                            <template v-if="msg.role === 'assistant'">
                                <div class="chat-msg__icon">
                                    <i class="ri-robot-2-line"></i>
                                </div>
                                <div class="chat-msg__content">
                                    <div class="chat-msg__bubble chat-msg__bubble--assistant">
                                        {{ msg.content }}
                                    </div>

                                    <!-- Fuentes -->
                                    <div v-if="msg.sources && msg.sources.length" class="chat-msg__sources">
                                        <button
                                            class="chat-msg__sources-toggle"
                                            @click="toggleSources(index)"
                                        >
                                            <i class="ri-file-text-line me-1"></i>
                                            {{ msg.sources.length }} fuente{{ msg.sources.length > 1 ? 's' : '' }}
                                            <i :class="msg.showSources ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'" class="ms-1"></i>
                                        </button>
                                        <div v-if="msg.showSources" class="chat-msg__sources-list">
                                            <div
                                                v-for="(src, si) in msg.sources"
                                                :key="si"
                                                class="chat-msg__source-item"
                                            >
                                                <i class="ri-file-pdf-line me-1 text-danger"></i>
                                                <span class="fw-medium">{{ src.title }}</span>
                                                <p class="mb-0 chat-msg__excerpt">{{ src.excerpt }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <span class="chat-msg__time">{{ formatTime(msg.createdAt) }}</span>
                                </div>
                            </template>

                            <!-- Burbuja usuario -->
                            <template v-else>
                                <div class="chat-msg__content chat-msg__content--user">
                                    <div class="chat-msg__bubble chat-msg__bubble--user">
                                        {{ msg.content }}
                                    </div>
                                    <span class="chat-msg__time text-end d-block">{{ formatTime(msg.createdAt) }}</span>
                                </div>
                            </template>
                        </div>

                        <!-- Indicador de escritura -->
                        <div v-if="loading" class="chat-msg chat-msg--assistant">
                            <div class="chat-msg__icon">
                                <i class="ri-robot-2-line"></i>
                            </div>
                            <div class="chat-msg__content">
                                <div class="chat-msg__bubble chat-msg__bubble--assistant chat-msg__bubble--typing">
                                    <span></span><span></span><span></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Input -->
                    <div class="chat-shell__footer">
                        <div class="chat-shell__input-wrap">
                            <textarea
                                v-model="question"
                                class="chat-shell__input"
                                placeholder="Escribe tu pregunta... (Enter para enviar, Shift+Enter para nueva línea)"
                                rows="1"
                                :disabled="loading"
                                @keydown="handleKeydown"
                            ></textarea>
                            <button
                                class="chat-shell__send"
                                :disabled="loading || !question.trim()"
                                @click="sendMessage"
                                title="Enviar"
                            >
                                <i v-if="!loading" class="ri-send-plane-fill"></i>
                                <div v-else class="chat-shell__spinner"></div>
                            </button>
                        </div>
                        <p class="chat-shell__hint">
                            <i class="ri-shield-check-line me-1"></i>
                            Las respuestas se basan únicamente en documentos oficiales de la UTS.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </Layout>
</template>

<style scoped>
/* ── Shell del chat ─────────────────────────────────────── */
.chat-shell {
    border-radius: 1rem;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(16, 44, 38, 0.12);
    border: 1px solid rgba(16, 44, 38, 0.10);
    background: var(--vz-card-bg);
    display: flex;
    flex-direction: column;
    height: calc(100vh - 220px);
    min-height: 520px;
}

/* ── Header ──────────────────────────────────────────────── */
.chat-shell__header {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    padding: 1rem 1.25rem;
    background: #102C26;
    color: #F7E7CE;
    flex-shrink: 0;
}

.chat-shell__avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: rgba(247, 231, 206, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    color: #F7E7CE;
    flex-shrink: 0;
}

.chat-shell__model {
    font-size: 0.75rem;
    color: rgba(247, 231, 206, 0.70);
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

.chat-shell__dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #51B566;
    display: inline-block;
    animation: pulse 2s ease-in-out infinite;
}

/* ── Body ────────────────────────────────────────────────── */
.chat-shell__body {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    scroll-behavior: smooth;
}

/* ── Mensajes ────────────────────────────────────────────── */
.chat-msg {
    display: flex;
    gap: 0.75rem;
    align-items: flex-start;
    max-width: 85%;
}

.chat-msg--user {
    align-self: flex-end;
    flex-direction: row-reverse;
}

.chat-msg--assistant {
    align-self: flex-start;
}

.chat-msg__icon {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: rgba(16, 44, 38, 0.10);
    color: #102C26;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
    margin-top: 2px;
}

.chat-msg__content {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.chat-msg__content--user {
    align-items: flex-end;
}

.chat-msg__bubble {
    padding: 0.75rem 1rem;
    border-radius: 1rem;
    line-height: 1.55;
    font-size: 0.9rem;
    white-space: pre-wrap;
    word-break: break-word;
}

.chat-msg__bubble--assistant {
    background: var(--vz-secondary-bg);
    border-top-left-radius: 0.25rem;
    color: var(--vz-body-color);
    border: 1px solid var(--vz-border-color);
}

.chat-msg__bubble--user {
    background: #102C26;
    color: #F7E7CE;
    border-top-right-radius: 0.25rem;
}

.chat-msg__time {
    font-size: 0.7rem;
    color: var(--vz-secondary-color);
    padding: 0 0.25rem;
}

/* ── Typing indicator ────────────────────────────────────── */
.chat-msg__bubble--typing {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 0.85rem 1.1rem;
    min-width: 56px;
}

.chat-msg__bubble--typing span {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #102C26;
    opacity: 0.4;
    animation: typing-bounce 1.2s ease-in-out infinite;
}

.chat-msg__bubble--typing span:nth-child(2) { animation-delay: 0.2s; }
.chat-msg__bubble--typing span:nth-child(3) { animation-delay: 0.4s; }

/* ── Fuentes ─────────────────────────────────────────────── */
.chat-msg__sources {
    margin-top: 0.25rem;
}

.chat-msg__sources-toggle {
    background: none;
    border: 1px solid rgba(16, 44, 38, 0.25);
    border-radius: 0.5rem;
    padding: 0.3rem 0.75rem;
    font-size: 0.78rem;
    color: #102C26;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    transition: background 0.15s;
}

.chat-msg__sources-toggle:hover {
    background: rgba(16, 44, 38, 0.07);
}

.chat-msg__sources-list {
    margin-top: 0.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.chat-msg__source-item {
    background: rgba(81, 181, 102, 0.08);
    border: 1px solid rgba(81, 181, 102, 0.25);
    border-radius: 0.625rem;
    padding: 0.6rem 0.875rem;
    font-size: 0.8rem;
}

.chat-msg__excerpt {
    color: var(--vz-secondary-color);
    font-size: 0.75rem;
    margin-top: 0.25rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* ── Footer / Input ──────────────────────────────────────── */
.chat-shell__footer {
    padding: 1rem 1.25rem 0.875rem;
    border-top: 1px solid var(--vz-border-color);
    flex-shrink: 0;
    background: var(--vz-card-bg);
}

.chat-shell__input-wrap {
    display: flex;
    gap: 0.625rem;
    align-items: flex-end;
}

.chat-shell__input {
    flex: 1;
    border: 1.5px solid var(--vz-border-color);
    border-radius: 0.75rem;
    padding: 0.65rem 1rem;
    font-size: 0.9rem;
    resize: none;
    background: var(--vz-secondary-bg);
    color: var(--vz-body-color);
    transition: border-color 0.2s;
    line-height: 1.5;
    min-height: 44px;
    max-height: 120px;
    overflow-y: auto;
}

.chat-shell__input:focus {
    outline: none;
    border-color: #102C26;
    box-shadow: 0 0 0 3px rgba(16, 44, 38, 0.12);
}

.chat-shell__input::placeholder {
    color: var(--vz-secondary-color);
    font-size: 0.85rem;
}

.chat-shell__send {
    width: 44px;
    height: 44px;
    border-radius: 0.75rem;
    border: none;
    background: #102C26;
    color: #F7E7CE;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
    transition: background 0.2s, transform 0.15s;
}

.chat-shell__send:hover:not(:disabled) {
    background: #1a3f38;
    transform: translateY(-1px);
}

.chat-shell__send:disabled {
    opacity: 0.45;
    cursor: not-allowed;
    transform: none;
}

.chat-shell__spinner {
    width: 18px;
    height: 18px;
    border: 2px solid rgba(247, 231, 206, 0.3);
    border-top-color: #F7E7CE;
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
}

.chat-shell__hint {
    font-size: 0.72rem;
    color: var(--vz-secondary-color);
    margin: 0.5rem 0 0;
    text-align: center;
}

/* ── Animaciones ─────────────────────────────────────────── */
@keyframes typing-bounce {
    0%, 60%, 100% { transform: translateY(0); opacity: 0.4; }
    30% { transform: translateY(-6px); opacity: 1; }
}

@keyframes pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(81, 181, 102, 0.4); }
    50% { box-shadow: 0 0 0 5px rgba(81, 181, 102, 0); }
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
