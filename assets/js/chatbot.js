(function(){
    console.log('🤖 Eofori.js loaded');

    const elements = {};
    let conversationHistory = [];
    let isTyping = false;

    function init() {
        elements.openBtn = document.getElementById('open-chat');
        elements.closeBtn = document.getElementById('close-chat');
        elements.modal = document.getElementById('chat-modal');
        elements.body = document.getElementById('chat-body');
        elements.input = document.getElementById('chat-input');
        elements.send = document.getElementById('chat-send');
        elements.suggestions = document.getElementById('chat-suggestions');

        if (!elements.openBtn || !elements.modal) return;

        elements.basePath = window.BASE_PATH || '';

        // FORCE MODAL HIDDEN
        elements.modal.style.display = 'none';
        elements.modal.classList.remove('chat-open');

        setupEventListeners();
        console.log('✅ Chatbot initialized - HIDDEN');
    }

    function setupEventListeners() {
        if (elements.openBtn) {
            elements.openBtn.addEventListener('click', openChat);
        }
        if (elements.closeBtn) {
            elements.closeBtn.addEventListener('click', closeChat);
        }
        if (elements.send) {
            elements.send.addEventListener('click', (e) => {
                e.preventDefault();
                sendMessage();
            });
        }
        if (elements.input) {
            elements.input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                } else if (e.key === 'Escape') {
                    closeChat();
                }
            });
            elements.input.addEventListener('input', (e) => {
                const hasText = e.target.value.trim().length > 0;
                if (elements.send) {
                    elements.send.classList.toggle('active', hasText);
                }
            });
        }
    }

    function openChat() {
        if (!elements.modal) return;
        
        elements.modal.style.display = 'block';
        elements.modal.classList.add('chat-open');

        if (conversationHistory.length === 0) {
            setTimeout(() => {
                appendMessage('🚀 Welcome to Eofori! How can I help you today?', 'bot');
            }, 300);
        }

        setTimeout(() => {
            if (elements.input) elements.input.focus();
        }, 350);
    }

    function closeChat() {
        if (!elements.modal) return;
        
        elements.modal.classList.remove('chat-open');
        setTimeout(() => {
            elements.modal.style.display = 'none';
        }, 300);
    }

    async function sendMessage(suggestionText = null) {
        const text = suggestionText || elements.input?.value?.trim();
        if (!text || isTyping) return;

        appendMessage(text, 'user');
        if (elements.input) {
            elements.input.value = '';
            elements.send?.classList.remove('active');
        }

        isTyping = true;
        try {
            const response = await fetch(elements.basePath + '/api/chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    message: text,
                    user_role: window.USER_ROLE || 'guest',
                    timestamp: Date.now()
                })
            });

            if (response.ok) {
                const data = await response.json();
                appendMessage(data.reply || 'Sorry, I couldn\'t process that request.', 'bot');
            } else {
                appendMessage('❌ Sorry, I\'m having trouble connecting. Please try again.', 'bot');
            }
        } catch (error) {
            appendMessage('❌ Sorry, I\'m having trouble connecting. Please try again.', 'bot');
        }
        isTyping = false;
    }

    function appendMessage(text, from) {
        if (!elements.body) return;

        const messageDiv = document.createElement('div');
        messageDiv.className = `message-wrapper ${from}`;

        const row = document.createElement('div');
        row.className = `message-row ${from}-row`;

        if (from === 'bot') {
            const avatar = document.createElement('div');
            avatar.className = 'message-avatar';
            const img = document.createElement('img');
            img.src = `${elements.basePath}/assets/img/robot.svg`;
            img.alt = 'Assistant';
            avatar.appendChild(img);
            row.appendChild(avatar);
        }

        const bubble = document.createElement('div');
        bubble.className = `message-bubble ${from}-bubble`;
        bubble.innerHTML = text.replace(/\n/g, '<br>');
        row.appendChild(bubble);

        const timestamp = document.createElement('div');
        timestamp.className = 'message-timestamp';
        timestamp.textContent = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        row.appendChild(timestamp);

        messageDiv.appendChild(row);
        elements.body.appendChild(messageDiv);

        conversationHistory.push({ text, from, timestamp: Date.now() });

        setTimeout(() => messageDiv.classList.add('visible'), 50);
        setTimeout(() => {
            elements.body.scrollTop = elements.body.scrollHeight;
        }, 100);

        return messageDiv;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.Eofori = { openChat, closeChat };
})();