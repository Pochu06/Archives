@php
    $chatbotMessages = collect(session('chatbot.messages', []))
        ->filter(fn ($message) => is_array($message) && in_array($message['role'] ?? null, ['user', 'assistant'], true) && trim((string) ($message['content'] ?? '')) !== '')
        ->map(function (array $message) {
            return [
                'role' => $message['role'],
                'content' => trim((string) $message['content']),
                'references' => is_array($message['references'] ?? null) ? array_values($message['references']) : [],
                'status' => trim((string) ($message['status'] ?? 'ok')),
            ];
        })
        ->values()
        ->all();

    $detailUrlTemplate = session('user_id')
        ? route('research.show', ['id' => '__CHATBOT_ID__'])
        : route('research.public-show', ['id' => '__CHATBOT_ID__']);
@endphp

<style>
    .chatbot-scrollbar::-webkit-scrollbar {
        width: 8px;
    }

    .chatbot-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 9999px;
    }

    @keyframes chatbot-typing-bounce {
        0%, 80%, 100% {
            transform: translateY(0);
            opacity: 0.35;
        }

        40% {
            transform: translateY(-5px);
            opacity: 1;
        }
    }

    .chatbot-typing-dot {
        animation: chatbot-typing-bounce 1s infinite ease-in-out;
    }

    .chatbot-typing-dot:nth-child(2) {
        animation-delay: 0.15s;
    }

    .chatbot-typing-dot:nth-child(3) {
        animation-delay: 0.3s;
    }
</style>

<div
    id="archiveChatbotWidget"
    data-store-url="{{ route('chatbot.store') }}"
    data-reset-url="{{ route('chatbot.reset') }}"
    data-detail-url-template="{{ $detailUrlTemplate }}"
    data-auto-open="{{ request()->routeIs('chatbot.index') ? '1' : '0' }}"
>
    <button
        type="button"
        data-chatbot-launcher
        class="fixed bottom-5 right-5 z-[70] inline-flex items-center gap-3 rounded-full bg-orange-600 px-4 py-3 text-white shadow-[0_20px_50px_rgba(37,99,235,0.25)] transition hover:bg-orange-700 focus:outline-none focus:ring-4 focus:ring-orange-200"
        aria-label="Open AI assistant"
    >
        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-white/15 text-lg">
            <i class="fas fa-robot"></i>
        </span>
        <span class="hidden pr-1 text-left sm:block">
            <span class="block text-xs uppercase tracking-[0.2em] text-orange-100">ARCHIVES</span>
            <span class="block text-sm font-semibold">Ask ARCHI</span>
        </span>
    </button>

    <div data-chatbot-backdrop class="fixed inset-0 z-[79] hidden bg-slate-950/35 backdrop-blur-[1px]"></div>

    <section
        data-chatbot-panel
        class="fixed inset-x-3 bottom-3 z-[80] hidden h-[min(80vh,44rem)] overflow-hidden rounded-[2rem] border border-gray-200 bg-white shadow-2xl sm:inset-x-auto sm:bottom-24 sm:right-5 sm:w-[26rem]"
        role="dialog"
        aria-modal="true"
        aria-labelledby="chatbotModalTitle"
    >
        <div class="flex h-full flex-col">
            <div class="border-b border-gray-100 bg-gradient-to-r from-orange-600 to-blue-600 px-5 py-4 text-white">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-orange-100">ARCHI</p>
                        <h2 id="chatbotModalTitle" class="mt-1 text-lg font-bold">ARCHIVES AI Assistant</h2>
                        <p class="mt-1 text-sm text-orange-50">Ask about archived research without leaving the current page.</p>
                    </div>
                    <button type="button" data-chatbot-close class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20" aria-label="Close AI assistant">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2 text-xs">
                    <button type="button" data-chatbot-clear class="rounded-full bg-white/10 px-3 py-1.5 font-semibold text-white transition hover:bg-white/20">Clear chat</button>
                </div>
            </div>

            <div data-chatbot-thread class="chatbot-scrollbar flex-1 overflow-y-auto bg-gray-50 px-4 py-4 sm:px-5">
                @if(empty($chatbotMessages))
                <div data-chatbot-empty class="rounded-3xl border border-dashed border-gray-300 bg-white px-5 py-8 text-center shadow-sm">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-orange-100 text-xl text-orange-700">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h3 class="mt-4 text-lg font-bold text-gray-900">Start a conversation</h3>
                    <p class="mt-2 text-sm leading-6 text-gray-600">Ask for archived papers, topic directions, or a quick explanation of a research method.</p>
                </div>
                @endif

                <div data-chatbot-messages class="space-y-4">
                    @foreach($chatbotMessages as $message)
                    <article class="{{ $message['role'] === 'user' ? 'flex justify-end' : '' }}">
                        <div class="max-w-[92%] {{ $message['role'] === 'user' ? 'rounded-[1.7rem] rounded-br-md bg-orange-600 px-4 py-3 text-white' : 'rounded-[1.7rem] rounded-tl-md border border-blue-100 bg-white px-4 py-3 text-gray-800 shadow-sm' }}">
                            <div class="mb-2 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.18em] {{ $message['role'] === 'user' ? 'text-orange-100' : (($message['status'] ?? 'ok') === 'unavailable' ? 'text-red-500' : 'text-blue-600') }}">
                                <i class="fas {{ $message['role'] === 'user' ? 'fa-user' : 'fa-robot' }}"></i>
                                <span>{{ $message['role'] === 'user' ? 'You' : 'Assistant' }}</span>
                            </div>
                            <p class="whitespace-pre-line text-sm leading-7">{{ $message['content'] }}</p>

                            @if($message['role'] === 'assistant' && ! empty($message['references']))
                            <div class="mt-4 border-t border-gray-100 pt-4">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-400">Related Archive Records</p>
                                <div class="mt-3 space-y-3">
                                    @foreach($message['references'] as $reference)
                                    <a href="{{ session('user_id') ? route('research.show', $reference['id']) : route('research.public-show', $reference['id']) }}" class="block rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 transition hover:border-orange-200 hover:bg-orange-50">
                                        <p class="text-sm font-semibold leading-6 text-gray-900">{{ $reference['title'] }}</p>
                                        <p class="mt-1 text-xs text-gray-500">{{ $reference['publication_year'] ?: 'N/A' }} &middot; {{ $reference['category'] ?: 'Uncategorized' }} &middot; {{ ($reference['college_code'] ?? $reference['college'] ?? 'N/A') }}</p>
                                        <p class="mt-2 text-sm leading-6 text-gray-600">{{ $reference['abstract_excerpt'] }}</p>
                                    </a>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                    </article>
                    @endforeach
                </div>
            </div>

            <div class="border-t border-gray-100 bg-white px-4 py-4 sm:px-5">
                <form data-chatbot-form action="{{ route('chatbot.store') }}" method="POST" class="space-y-3">
                    @csrf
                    <label for="chatbotWidgetMessage" class="sr-only">Message</label>
                    <textarea id="chatbotWidgetMessage" data-chatbot-message name="message" rows="3" maxlength="2000" class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-gray-800 focus:border-orange-500 focus:outline-none" placeholder="Ask about a topic, an archived paper, or research guidance..."></textarea>
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs leading-5 text-gray-500"></p>
                        <button type="submit" data-chatbot-submit class="inline-flex items-center gap-2 rounded-2xl bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-60">
                            <i class="fas fa-paper-plane"></i>
                            <span>Send</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<script>
(() => {
    const root = document.getElementById('archiveChatbotWidget');

    if (!root || root.dataset.bound === '1') {
        return;
    }

    root.dataset.bound = '1';

    const launcher = root.querySelector('[data-chatbot-launcher]');
    const backdrop = root.querySelector('[data-chatbot-backdrop]');
    const panel = root.querySelector('[data-chatbot-panel]');
    const closeButtons = root.querySelectorAll('[data-chatbot-close]');
    const thread = root.querySelector('[data-chatbot-thread]');
    const messageList = root.querySelector('[data-chatbot-messages]');
    const form = root.querySelector('[data-chatbot-form]');
    const textarea = root.querySelector('[data-chatbot-message]');
    const submitButton = root.querySelector('[data-chatbot-submit]');
    const clearButton = root.querySelector('[data-chatbot-clear]');
    const storeUrl = root.dataset.storeUrl || '';
    const resetUrl = root.dataset.resetUrl || '';
    const detailUrlTemplate = root.dataset.detailUrlTemplate || '';
    const autoOpen = root.dataset.autoOpen === '1';
    const emptyStateMarkup = root.querySelector('[data-chatbot-empty]')?.outerHTML || '';

    if (!launcher || !backdrop || !panel || !thread || !messageList || !form || !textarea || !submitButton) {
        return;
    }

    const csrfToken = form.querySelector('input[name="_token"]')?.value || '';

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

    const scrollToBottom = () => {
        thread.scrollTop = thread.scrollHeight;
    };

    const removeEmptyState = () => {
        const emptyState = root.querySelector('[data-chatbot-empty]');

        if (emptyState) {
            emptyState.remove();
        }
    };

    const renderEmptyState = () => {
        if (!emptyStateMarkup || root.querySelector('[data-chatbot-empty]')) {
            return;
        }

        messageList.insertAdjacentHTML('beforebegin', emptyStateMarkup);
    };

    const buildDetailUrl = (referenceId) => detailUrlTemplate.replace('__CHATBOT_ID__', String(referenceId));

    const renderReferences = (references) => {
        if (!Array.isArray(references) || references.length === 0) {
            return '';
        }

        return `
            <div class="mt-4 border-t border-gray-100 pt-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-400">Related Archive Records</p>
                <div class="mt-3 space-y-3">
                    ${references.map((reference) => `
                        <a href="${escapeHtml(buildDetailUrl(reference.id))}" class="block rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 transition hover:border-orange-200 hover:bg-orange-50">
                            <p class="text-sm font-semibold leading-6 text-gray-900">${escapeHtml(reference.title)}</p>
                            <p class="mt-1 text-xs text-gray-500">${escapeHtml(reference.publication_year ?? 'N/A')} &middot; ${escapeHtml(reference.category ?? 'Uncategorized')} &middot; ${escapeHtml(reference.college_code ?? reference.college ?? 'N/A')}</p>
                            <p class="mt-2 text-sm leading-6 text-gray-600">${escapeHtml(reference.abstract_excerpt ?? '')}</p>
                        </a>
                    `).join('')}
                </div>
            </div>
        `;
    };

    const removeTypingIndicator = () => {
        const typingIndicator = root.querySelector('[data-chatbot-typing]');

        if (typingIndicator) {
            typingIndicator.remove();
        }
    };

    const appendTypingIndicator = () => {
        removeEmptyState();
        removeTypingIndicator();

        const wrapper = document.createElement('article');
        wrapper.setAttribute('data-chatbot-typing', 'true');

        const bubble = document.createElement('div');
        bubble.className = 'max-w-[92%] rounded-[1.7rem] rounded-tl-md border border-blue-100 bg-white px-4 py-3 text-gray-800 shadow-sm';

        const label = document.createElement('div');
        label.className = 'mb-2 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-blue-600';
        label.innerHTML = '<i class="fas fa-robot"></i><span>Assistant</span>';

        const content = document.createElement('div');
        content.className = 'flex items-center gap-3';
        content.innerHTML = `
            <span class="text-sm font-medium text-gray-500">Generating response</span>
            <span class="inline-flex items-center gap-1" aria-hidden="true">
                <span class="chatbot-typing-dot h-2.5 w-2.5 rounded-full bg-blue-400"></span>
                <span class="chatbot-typing-dot h-2.5 w-2.5 rounded-full bg-blue-400"></span>
                <span class="chatbot-typing-dot h-2.5 w-2.5 rounded-full bg-blue-400"></span>
            </span>
        `;

        const screenReaderText = document.createElement('span');
        screenReaderText.className = 'sr-only';
        screenReaderText.textContent = 'Assistant is typing';

        bubble.appendChild(label);
        bubble.appendChild(content);
        bubble.appendChild(screenReaderText);
        wrapper.appendChild(bubble);
        messageList.appendChild(wrapper);
        scrollToBottom();
    };

    const appendMessage = (message) => {
        removeEmptyState();
        removeTypingIndicator();

        const role = message.role === 'user' ? 'user' : 'assistant';
        const wrapper = document.createElement('article');
        wrapper.className = role === 'user' ? 'flex justify-end' : '';

        const bubble = document.createElement('div');
        bubble.className = role === 'user'
            ? 'max-w-[92%] rounded-[1.7rem] rounded-br-md bg-orange-600 px-4 py-3 text-white'
            : 'max-w-[92%] rounded-[1.7rem] rounded-tl-md border border-blue-100 bg-white px-4 py-3 text-gray-800 shadow-sm';

        const label = document.createElement('div');
        const isUnavailable = (message.status || 'ok') === 'unavailable';
        label.className = role === 'user'
            ? 'mb-2 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-orange-100'
            : `mb-2 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.18em] ${isUnavailable ? 'text-red-500' : 'text-blue-600'}`;
        label.innerHTML = `<i class="fas ${role === 'user' ? 'fa-user' : 'fa-robot'}"></i><span>${role === 'user' ? 'You' : 'Assistant'}</span>`;

        const content = document.createElement('p');
        content.className = 'whitespace-pre-line text-sm leading-7';
        content.textContent = message.content || '';

        bubble.appendChild(label);
        bubble.appendChild(content);

        if (role === 'assistant' && Array.isArray(message.references) && message.references.length > 0) {
            const referenceWrapper = document.createElement('div');
            referenceWrapper.innerHTML = renderReferences(message.references);
            bubble.appendChild(referenceWrapper.firstElementChild);
        }

        wrapper.appendChild(bubble);
        messageList.appendChild(wrapper);
        scrollToBottom();
    };

    const openPanel = () => {
        panel.classList.remove('hidden');
        backdrop.classList.remove('hidden');
        launcher.classList.add('hidden');
        document.body.classList.add('overflow-hidden');
        window.setTimeout(() => textarea.focus(), 30);
        scrollToBottom();
    };

    const closePanel = () => {
        panel.classList.add('hidden');
        backdrop.classList.add('hidden');
        launcher.classList.remove('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    const openTriggers = document.querySelectorAll('[data-chatbot-open]');

    openTriggers.forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            openPanel();
        });
    });

    launcher.addEventListener('click', openPanel);
    backdrop.addEventListener('click', closePanel);
    closeButtons.forEach((button) => button.addEventListener('click', closePanel));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !panel.classList.contains('hidden')) {
            closePanel();
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const message = textarea.value.trim();

        if (!message) {
            textarea.focus();
            return;
        }

        appendMessage({ role: 'user', content: message });
        textarea.value = '';
        submitButton.disabled = true;
        appendTypingIndicator();

        try {
            const response = await fetch(storeUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ message }),
            });

            const payload = await response.json();

            if (!response.ok) {
                const validationMessage = payload?.errors?.message?.[0] || payload?.message || 'Unable to send your message right now.';
                removeTypingIndicator();
                appendMessage({ role: 'assistant', content: validationMessage, status: 'error', references: [] });
                return;
            }

            appendMessage(payload.assistant || { role: 'assistant', content: 'No response received.', status: 'error', references: [] });
        } catch (error) {
            removeTypingIndicator();
            appendMessage({ role: 'assistant', content: 'The assistant could not be reached right now. Please try again.', status: 'error', references: [] });
        } finally {
            submitButton.disabled = false;
            textarea.focus();
        }
    });

    if (clearButton) {
        clearButton.addEventListener('click', async () => {
            try {
                await fetch(resetUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });
            } catch (error) {
                // Ignore reset errors and still clear the local modal state.
            }

            removeTypingIndicator();
            messageList.innerHTML = '';
            renderEmptyState();
            textarea.value = '';
            scrollToBottom();
        });
    }

    scrollToBottom();

    if (autoOpen) {
        openPanel();
    }
})();
</script>