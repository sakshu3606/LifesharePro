// Chatbot Functionality - Save as: js/chatbot.js

class LifeShareChatbot {
    constructor() {
        this.isOpen = false;
        this.messages = [];
        this.lastContext = null; // Track conversation context
        this.init();
    }

    init() {
        this.createChatbotHTML();
        this.attachEventListeners();
        this.showWelcomeMessage();
    }

    createChatbotHTML() {
        const chatbotHTML = `
            <div class="chatbot-container">
                <button class="chatbot-button" id="chatbotToggle">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/>
                        <circle cx="8" cy="10" r="1.5"/>
                        <circle cx="12" cy="10" r="1.5"/>
                        <circle cx="16" cy="10" r="1.5"/>
                    </svg>
                    <span class="chatbot-badge">!</span>
                </button>
                
                <div class="chatbot-window" id="chatbotWindow">
                    <div class="chatbot-header">
                        <div class="chatbot-header-content">
                            <div class="chatbot-avatar">❤️</div>
                            <div class="chatbot-title">
                                <h3>LifeShare Assistant</h3>
                                <div class="chatbot-status">Online • Ready to help</div>
                            </div>
                        </div>
                        <button class="chatbot-close" id="chatbotClose">×</button>
                    </div>
                    
                    <div class="chatbot-messages" id="chatbotMessages"></div>
                    
                    <div class="chatbot-input">
                        <input 
                            type="text" 
                            id="chatbotInput" 
                            placeholder="Type your message..."
                            autocomplete="off"
                        />
                        <button class="chatbot-send" id="chatbotSend">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', chatbotHTML);
    }

    attachEventListeners() {
        const toggle = document.getElementById('chatbotToggle');
        const close = document.getElementById('chatbotClose');
        const send = document.getElementById('chatbotSend');
        const input = document.getElementById('chatbotInput');

        toggle.addEventListener('click', () => this.toggleChat());
        close.addEventListener('click', () => this.toggleChat());
        send.addEventListener('click', () => this.sendMessage());
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.sendMessage();
        });
    }

    toggleChat() {
        this.isOpen = !this.isOpen;
        const window = document.getElementById('chatbotWindow');
        const button = document.getElementById('chatbotToggle');
        const badge = button.querySelector('.chatbot-badge');

        window.classList.toggle('active');
        button.classList.toggle('active');

        if (this.isOpen) {
            badge.style.display = 'none';
            document.getElementById('chatbotInput').focus();
        }
    }

    showWelcomeMessage() {
        setTimeout(() => {
            this.addBotMessage(
                "👋 Welcome to LifeShare! I'm here to help you with blood and organ donation. How can I assist you today?",
                [
                    "Register as blood donor",
                    "Pledge organs",
                    "Find donors",
                    "Learn about donation"
                ]
            );
        }, 1000);
    }

    sendMessage() {
        const input = document.getElementById('chatbotInput');
        const message = input.value.trim();

        if (!message) return;

        this.addUserMessage(message);
        input.value = '';

        // Show typing indicator
        this.showTyping();

        // Process message and respond
        setTimeout(() => {
            this.hideTyping();
            this.processMessage(message.toLowerCase());
        }, 1000 + Math.random() * 1000);
    }

    addUserMessage(text) {
        const messagesContainer = document.getElementById('chatbotMessages');
        const time = this.getCurrentTime();

        const messageHTML = `
            <div class="message user">
                <div class="message-avatar">👤</div>
                <div>
                    <div class="message-content">${this.escapeHtml(text)}</div>
                    <div class="message-time">${time}</div>
                </div>
            </div>
        `;

        messagesContainer.insertAdjacentHTML('beforeend', messageHTML);
        this.scrollToBottom();
    }

    addBotMessage(text, quickReplies = []) {
        const messagesContainer = document.getElementById('chatbotMessages');
        const time = this.getCurrentTime();

        let quickRepliesHTML = '';
        if (quickReplies.length > 0) {
            quickRepliesHTML = '<div class="quick-replies">';
            quickReplies.forEach(reply => {
                quickRepliesHTML += `<button class="quick-reply-btn" onclick="chatbot.handleQuickReply('${reply}')">${reply}</button>`;
            });
            quickRepliesHTML += '</div>';
        }

        const messageHTML = `
            <div class="message bot">
                <div class="message-avatar">❤️</div>
                <div>
                    <div class="message-content">${text}</div>
                    <div class="message-time">${time}</div>
                    ${quickRepliesHTML}
                </div>
            </div>
        `;

        messagesContainer.insertAdjacentHTML('beforeend', messageHTML);
        this.scrollToBottom();
    }

    handleQuickReply(reply) {
        this.addUserMessage(reply);
        this.showTyping();
        
        setTimeout(() => {
            this.hideTyping();
            this.processMessage(reply.toLowerCase());
        }, 1000);
    }

    showTyping() {
        const messagesContainer = document.getElementById('chatbotMessages');
        const typingHTML = `
            <div class="message bot typing-message">
                <div class="message-avatar">❤️</div>
                <div class="typing-indicator">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            </div>
        `;
        messagesContainer.insertAdjacentHTML('beforeend', typingHTML);
        this.scrollToBottom();
    }

    hideTyping() {
        const typing = document.querySelector('.typing-message');
        if (typing) typing.remove();
    }

    processMessage(message) {
        let response = '';
        let quickReplies = [];

        // Blood Donor Registration
        if (message.includes('blood donor') || message.includes('donate blood') || message.includes('register as blood donor')) {
            this.lastContext = 'blood';
            response = "🩸 That's wonderful! To register as a blood donor, you need to:<br><br>1. Be between 18-65 years old<br>2. Weigh at least 50 kg<br>3. Be in good health<br><br>Would you like me to direct you to the registration form?";
            quickReplies = ["Yes, take me to registration", "Tell me more about requirements"];
        }
        // Organ Pledge
        else if (message.includes('pledge organs') || message.includes('organ') || message.includes('pledge')) {
            this.lastContext = 'organ';
            response = "🫀 Thank you for considering organ donation! One organ donor can save up to 8 lives. You can pledge to donate:<br><br>• Heart<br>• Liver<br>• Kidneys<br>• Lungs<br>• Pancreas<br>• Corneas<br><br>Would you like to make a pledge?";
            quickReplies = ["Yes, I want to pledge", "What happens after pledging?"];
        }
        // Find Donors
        else if (message.includes('find') || message.includes('search')) {
            response = "🔍 You can search for donors in our database by:<br><br>• Blood group<br>• Location (city/state)<br>• Donation type<br><br>All donor information is privacy-protected. Would you like to search now?";
            quickReplies = ["Search blood donors", "Search organ donors"];
        }
        // Search Blood Donors
        else if (message.includes('search blood donors')) {
            response = "I'll redirect you to the blood donor search page where you can filter by blood group and location.";
            setTimeout(() => {
                window.location.href = '/Projects/lifeshare/search-donors.php';
            }, 1500);
        }
        // Search Organ Donors
        else if (message.includes('search organ donors')) {
            response = "I'll redirect you to the organ donor search page where you can find available donors.";
            setTimeout(() => {
                window.location.href = '/Projects/lifeshare/search-donors.php';
            }, 1500);
        }
        // Eligibility Requirements
        else if (message.includes('eligib') || message.includes('requirement') || message.includes('tell me more')) {
            response = "✅ <strong>Blood Donation Requirements:</strong><br>• Age: 18-65 years<br>• Weight: Minimum 50 kg<br>• Good health<br>• No recent illness<br><br>You can donate every 3 months (12 weeks).";
            quickReplies = ["Register now", "What about organ donation?"];
        }
        // What about organ donation
        else if (message.includes('what about organ')) {
            response = "🫀 <strong>Organ Donation Eligibility:</strong><br>• Any age (with consent)<br>• No specific health restrictions<br>• Medical evaluation at time of death<br><br>You can pledge your organs anytime to give the gift of life!";
            quickReplies = ["I want to pledge", "Learn more"];
        }
        // What happens after pledging
        else if (message.includes('what happens after')) {
            response = "After pledging organs:<br><br>1. You'll receive a pledge certificate<br>2. Your details are stored securely<br>3. Your family will be notified of your wishes<br>4. Medical team coordinates donation when the time comes<br><br>Your decision can save up to 8 lives! 💝";
            quickReplies = ["I want to pledge", "Tell me more"];
        }
        // Safety concerns
        else if (message.includes('safe') || message.includes('risk')) {
            response = "🛡️ Donation is very safe! All equipment is sterile and single-use. Donors are screened for health conditions. The process is supervised by trained medical professionals.";
            quickReplies = ["That's reassuring!", "How long does it take?"];
        }
        // Time duration
        else if (message.includes('time') || message.includes('long') || message.includes('how long')) {
            response = "⏱️ Blood donation typically takes:<br>• Registration: 10 minutes<br>• Health screening: 10 minutes<br>• Donation: 10-15 minutes<br>• Rest & refreshments: 10 minutes<br><br>Total: About 45 minutes";
            quickReplies = ["Ready to donate!", "What should I bring?"];
        }
        // What to bring
        else if (message.includes('what should i bring') || message.includes('bring')) {
            response = "📋 Please bring:<br><br>• Valid photo ID (Aadhaar/Passport/License)<br>• Good health and positive attitude!<br><br>Make sure you've eaten well and are hydrated before donation.";
            quickReplies = ["Register now", "Any other tips?"];
        }
        // Tips
        else if (message.includes('tips') || message.includes('advice')) {
            response = "💡 <strong>Donation Tips:</strong><br>• Eat iron-rich foods before donation<br>• Drink plenty of water<br>• Get good sleep the night before<br>• Avoid fatty foods<br>• Relax and stay calm<br><br>You're doing something amazing! 🌟";
            quickReplies = ["I'm ready!", "Tell me more"];
        }
        // Contact & Support
        else if (message.includes('contact') || message.includes('help') || message.includes('support')) {
            response = "📞 You can reach us:<br><br>📧 Email: info@lifeshare.org<br>📱 Phone: +1 (555) 123-4567<br>🏥 Address: 123 Healthcare Avenue<br>⏰ Available: 24/7";
            quickReplies = ["Thank you!", "I have another question"];
        }
        // Learn about donation
        else if (message.includes('learn') || message.includes('learn about donation')) {
            response = "📚 <strong>About LifeShare:</strong><br><br>We connect donors with recipients and save lives through:<br>• Blood donation<br>• Organ pledges<br>• Emergency donor search<br><br>Every donation matters! What would you like to know more about?";
            quickReplies = ["Blood donation", "Organ donation", "Success stories"];
        }
        // Success stories
        else if (message.includes('success') || message.includes('stories')) {
            response = "❤️ We've helped save over 10,000 lives through blood and organ donations! Every donor is a hero. Your contribution can be someone's second chance at life. Join our community of lifesavers today!";
            quickReplies = ["I want to help!", "Tell me how"];
        }
        // Thank you
        else if (message.includes('thank') || message.includes('thanks')) {
            response = "You're very welcome! 😊 Remember, every donation saves lives. Is there anything else you'd like to know?";
            quickReplies = ["Register as donor", "Find donors", "Learn more"];
        }
        // Register now
        else if (message.includes('register now') || message.includes('ready to donate') || message.includes("i'm ready")) {
            if (this.lastContext === 'organ') {
                response = "Excellent! I'll redirect you to the organ pledge form.";
                setTimeout(() => {
                    window.location.href = '/Projects/lifeshare/organ-donation.php';
                }, 1500);
            } else {
                response = "Great! I'll redirect you to the blood donation registration form.";
                setTimeout(() => {
                    window.location.href = '/Projects/lifeshare/blood-donation.php';
                }, 1500);
            }
        }
        // Yes responses - context aware
        else if (message.includes('yes, take me to registration')) {
            response = "Great! I'll redirect you to the blood donation registration form.";
            setTimeout(() => {
                window.location.href = '/Projects/lifeshare/blood-donation.php';
            }, 1500);
        }
        else if (message.includes('yes, i want to pledge') || message.includes('i want to pledge')) {
            response = "Excellent! I'll redirect you to the organ pledge form.";
            setTimeout(() => {
                window.location.href = '/Projects/lifeshare/organ-donation.php';
            }, 1500);
        }
        else if (message.includes('yes') || message.includes('i want to help')) {
            if (this.lastContext === 'organ') {
                response = "Wonderful! Let me direct you to the organ pledge form.";
                setTimeout(() => {
                    window.location.href = '/Projects/lifeshare/organ-donation.php';
                }, 1500);
            } else if (this.lastContext === 'blood') {
                response = "Wonderful! Let me direct you to the blood donation registration.";
                setTimeout(() => {
                    window.location.href = '/Projects/lifeshare/blood-donation.php';
                }, 1500);
            } else {
                response = "Perfect! How else can I help you today?";
                quickReplies = ["Find donors", "Learn about requirements", "Contact support"];
            }
        }
        // No responses
        else if (message.includes('no')) {
            response = "No problem! Feel free to ask me anything else about LifeShare.";
            quickReplies = ["Tell me about blood donation", "Tell me about organ donation", "Find donors"];
        }
        // Default response
        else {
            response = "I can help you with:<br><br>🩸 Blood donation registration<br>🫀 Organ pledge information<br>🔍 Finding donors<br>📋 Eligibility requirements<br>💬 General questions<br><br>What would you like to know?";
            quickReplies = ["Register as blood donor", "Pledge organs", "Find donors", "Requirements"];
        }

        this.addBotMessage(response, quickReplies);
    }

    scrollToBottom() {
        const messagesContainer = document.getElementById('chatbotMessages');
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    getCurrentTime() {
        const now = new Date();
        return now.toLocaleTimeString('en-US', { 
            hour: 'numeric', 
            minute: '2-digit',
            hour12: true 
        });
    }

    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
}

// Initialize chatbot when DOM is loaded
let chatbot;
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        chatbot = new LifeShareChatbot();
    });
} else {
    chatbot = new LifeShareChatbot();
}