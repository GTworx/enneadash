// app.js - EnneaDash Voice Frontend Engine

// Enneagram Type Descriptions and Archetype Names
const enneagramTypes = {
    1: {
        name: "The Reformer",
        title: "The Rational, Idealistic Type",
        core_orientation: "Doing the right thing, maintaining integrity and high standards.",
        description: "Reformers are rational, idealistic, principled, orderly, and self-controlled. Striving for perfection, they can be critical of themselves and others, always looking for improvement.",
        key_traits: "Ethical, organized, structured, conscientious, self-correcting.",
        key_drivers: "Being objective, accurate, improving oneself, and living with integrity.",
        biggest_fear: "Being corrupt, flawed, evil, or physically/morally defective.",
        core_values: "Integrity, excellence, responsibility, truthfulness, order.",
        decision_making_style: "Structured and rational, relying on rules, ethical guidelines, and analytical correctness.",
        stress_reactions: "Becomes moody, critical, and resentful under pressure (moves to Type 4).",
        security_triggers: "Becomes more spontaneous, relaxed, and creative when safe (moves to Type 7).",
        core_fear: "Being bad, corrupt, or wrong.",
        core_desire: "To be good, to have integrity, and to be balanced.",
        core_weakness: "Anger (resentment that is constantly repressed into self-control).",
        soul_message: "You are good as you are.",
        growth_arrow_desc: "In integration (growth), the Reformer moves towards Type 7, embracing spontaneity, joy, and lightheartedness.",
        stress_arrow_desc: "In disintegration (stress), the Reformer moves towards Type 4, experiencing feelings of alienation, self-pity, and moodiness.",
        growth_action: "Practice self-compassion and learn to accept imperfections as valuable parts of human growth.",
        relationship_action: "Avoid holding partners to impossible standards; express appreciation for their efforts.",
        career_action: "Delegate tasks confidently and avoid micromanaging project details.",
        stress_action: "Take deep breaths, allow yourself to play, and schedule guilt-free downtime weekly.",
        daily_habit: "Consciously pause once a day to notice something that is perfectly fine just as it is."
    },
    2: {
        name: "The Helper",
        title: "The Caring, Interpersonal Type",
        core_orientation: "Expressing warmth, offering help, and cultivating affection.",
        description: "Helpers are demonstrative, generous, people-pleasing, and possessive. They sincerely want to feel loved, useful, and appreciated, occasionally neglecting their own boundaries.",
        key_traits: "Empathetic, nurturing, warm, supportive, altruistic.",
        key_drivers: "Connecting with others, feeling needed, expressing affection, and defending the vulnerable.",
        biggest_fear: "Being unwanted, unworthy of love, or completely discarded.",
        core_values: "Unconditional love, generosity, relationships, service, compassion.",
        decision_making_style: "Relationship-centric, prioritizing emotional impacts and needs of classmates or colleagues.",
        stress_reactions: "Becomes aggressive, demanding, and overly critical under pressure (moves to Type 8).",
        security_triggers: "Becomes self-reflective, creative, and introspective when secure (moves to Type 4).",
        core_fear: "Being unloved or unwanted for who they are.",
        core_desire: "To feel loved and appreciated.",
        core_weakness: "Pride (denying their own needs while over-emphasizing their helpfulness to others).",
        soul_message: "You are wanted and worthy of love.",
        growth_arrow_desc: "In growth, the Helper integrates towards Type 4, developing healthy self-care, creative expression, and authentic feelings.",
        stress_arrow_desc: "In stress, the Helper disintegrates towards Type 8, becoming controlling, confrontational, and demanding.",
        growth_action: "Set clear boundaries and practice saying 'no' when you are emotionally exhausted.",
        relationship_action: "Express your personal needs directly instead of expecting others to read your mind.",
        career_action: "Focus on your assigned job scope instead of taking on others' workloads out of obligation.",
        stress_action: "Step back, enjoy moments of isolation, and recharge through introspective creative activities.",
        daily_habit: "Write down three personal needs you have today and meet at least one of them."
    },
    3: {
        name: "The Achiever",
        title: "The Success-Oriented, Pragmatic Type",
        core_orientation: "Striving for success, outstanding achievements, and efficiency.",
        description: "Achievers are adaptable, ambitious, driven, and highly image-conscious. They value productivity, competency, and achieving goals that bring validation.",
        key_traits: "Goal-oriented, self-assured, efficient, energetic, charismatic.",
        key_drivers: "Being admired, distinguishing themselves, earning prestige, and avoiding failure.",
        biggest_fear: "Being worthless, incompetent, ineffective, or a failure.",
        core_values: "Success, productivity, distinction, competence, professional excellence.",
        decision_making_style: "Pragmatic, logical, and fast-paced, focusing entirely on execution and results.",
        stress_reactions: "Becomes disengaged, passive-aggressive, or sluggish under stress (moves to Type 9).",
        security_triggers: "Becomes cooperative, loyal, and community-minded when safe (moves to Type 6).",
        core_fear: "Being worthless or having no inherent value.",
        core_desire: "To feel valuable, successful, and respected.",
        core_weakness: "Deceit (crafting a successful image rather than showing their authentic self).",
        soul_message: "You are valued for who you are, not what you achieve.",
        growth_arrow_desc: "In growth, the Achiever integrates towards Type 6, becoming more cooperative, loyal, and committed to group well-being.",
        stress_arrow_desc: "In stress, the Achiever disintegrates towards Type 9, shutting down and becoming lethargic or directionless.",
        growth_action: "Value relationships and teamwork over individual metrics or social status.",
        relationship_action: "Share your failures and fears with trusted loved ones to cultivate authenticity.",
        career_action: "Balance hard work with strategic pauses; allow collaborators to take the lead occasionally.",
        stress_action: "Recognize when you are running on empty; disconnect from devices and sleep.",
        daily_habit: "Spend ten minutes reflecting on your day without measuring your productivity."
    },
    4: {
        name: "The Individualist",
        title: "The Sensitive, Withdrawn Type",
        core_orientation: "Expressing authentic identity, depth, and appreciating aesthetics.",
        description: "Individualists are expressive, dramatic, self-absorbed, and temperamental. They value authenticity and unique creative expression, seeking meaning in all aspects of life.",
        key_traits: "Intuitive, authentic, sensitive, expressive, introspective.",
        key_drivers: "Creating beauty, understanding deep emotions, staying true to oneself, and honoring feelings.",
        biggest_fear: "Having no unique identity or personal significance.",
        core_values: "Authenticity, aesthetic beauty, emotional depth, true individuality, self-expression.",
        decision_making_style: "Intuitive and emotional, strongly guided by how choices align with internal values.",
        stress_reactions: "Becomes clingy, dependent, and overly people-pleasing under pressure (moves to Type 2).",
        security_triggers: "Becomes objective, organized, and active when safe (moves to Type 1).",
        core_fear: "Having no identity or significance.",
        core_desire: "To cultivate a unique identity and find significance.",
        core_weakness: "Envy (feeling that everyone else possesses qualities they lack).",
        soul_message: "You are seen and appreciated for your unique beauty.",
        growth_arrow_desc: "In growth, the Individualist integrates towards Type 1, translating feelings into objective action, discipline, and order.",
        stress_arrow_desc: "In stress, the Individualist disintegrates towards Type 2, seeking validation and becoming overly dependent on others.",
        growth_action: "Build healthy routines and structures to ground your complex emotional world.",
        relationship_action: "Avoid getting caught in cycles of pull-and-push dynamics; appreciate stable, quiet affection.",
        career_action: "Commit to completing projects even when your creative inspiration temporarily fades.",
        stress_action: "Channel intense emotions into structured journaling, exercising, or volunteering.",
        daily_habit: "Focus on active tasks and execute one objective chore first thing each morning."
    },
    5: {
        name: "The Investigator",
        title: "The Intense, Cerebral Type",
        core_orientation: "Acquiring knowledge, understanding mechanisms, and protecting energy.",
        description: "Investigators are perceptive, innovative, secretive, and detached. They specialize in deep analysis, requiring quiet independence and mental clarity to recharge.",
        key_traits: "Analytical, insightful, independent, private, conceptual.",
        key_drivers: "Obtaining mastery, processing facts, maintaining autonomy, and escaping emotional noise.",
        biggest_fear: "Being overwhelmed, helpless, incapable, or ignorant.",
        core_values: "Mastery, rationality, independence, deep knowledge, clarity.",
        decision_making_style: "Highly objective, data-driven, and systematic, minimizing emotional interference.",
        stress_reactions: "Becomes hyperactive, distracted, and scattered under stress (moves to Type 7).",
        security_triggers: "Becomes self-assured, assertive, and physically active when safe (moves to Type 8).",
        core_fear: "Being useless, helpless, or incapable.",
        core_desire: "To be capable, competent, and fully knowledgeable.",
        core_weakness: "Avarice (hoarding info, time, and emotional energy to avoid dependency).",
        soul_message: "Your presence is capable and welcome in this world.",
        growth_arrow_desc: "In growth, the Investigator integrates towards Type 8, stepping into leadership and assertive, confident physical action.",
        stress_arrow_desc: "In stress, the Investigator disintegrates towards Type 7, escaping into theory, distraction, or frantic mental rabbit holes.",
        growth_action: "Share your thoughts early and step out of isolation to collaborate in physical groups.",
        relationship_action: "Practice sharing your emotional states directly rather than withdrawing into protective silence.",
        career_action: "Trust your competence and launch projects before you feel 100% prepared.",
        stress_action: "Engage your body through physical exercise to pull energy down from your head.",
        daily_habit: "Have a brief, casual conversation with someone about something unrelated to work."
    },
    6: {
        name: "The Loyalist",
        title: "The Committed, Security-Oriented Type",
        core_orientation: "Ensuring safety, maintaining trust, and building secure alliances.",
        description: "Loyalists are engaging, responsible, anxious, and suspicious. They seek stable guidance, support systems, and consistency to alleviate underlying anxiety.",
        key_traits: "Reliable, committed, alert, trustworthy, collaborative.",
        key_drivers: "Belonging to a trusted group, anticipating hazards, obtaining safety, and defending policies.",
        biggest_fear: "Being without support, guidance, or security; being abandoned.",
        core_values: "Trustworthiness, security, community loyalty, preparation, responsibility.",
        decision_making_style: "Collaborative and risk-averse, consulting trust systems and planning contingencies.",
        stress_reactions: "Becomes competitive, image-conscious, and workaholic under stress (moves to Type 3).",
        security_triggers: "Becomes relaxed, optimistic, and experimental when safe (moves to Type 9).",
        core_fear: "Being unsupported, guide-less, or abandoned.",
        core_desire: "To have security and support.",
        core_weakness: "Fear (continually planning for the worst possibilities to preempt anxiety).",
        soul_message: "You are safe, supported, and guided.",
        growth_arrow_desc: "In growth, the Loyalist integrates towards Type 9, finding inner calm, trusting life, and letting go of constant scanning.",
        stress_arrow_desc: "In stress, the Loyalist disintegrates towards Type 3, acting driven, defensive, and projecting a false, competent mask.",
        growth_action: "Develop confidence in your own authority and trust your primary instincts.",
        relationship_action: "Avoid testing your partner's loyalty; express your vulnerabilities openly instead.",
        career_action: "Acknowledge progress and success instead of focusing only on what could go wrong.",
        stress_action: "Limit news intake and practice mindfulness techniques to quiet catastrophic loops.",
        daily_habit: "Identify one situation today where you can trust the natural flow of outcomes."
    },
    7: {
        name: "The Enthusiast",
        title: "The Busy, Fun-Loving Type",
        core_orientation: "Seeking excitement, options, versatility, and avoiding discomfort.",
        description: "Enthusiasts are spontaneous, versatile, distractible, and quick-thinking. They seek positive experiences, constantly planning future options to outrun inner pain.",
        key_traits: "Optimistic, playful, quick-witted, adventurous, versatile.",
        key_drivers: "Staying stimulated, keeping options open, experiencing pleasure, and avoiding boredom/sorrow.",
        biggest_fear: "Being deprived, pain-bound, trapped in negativity, or limited.",
        core_values: "Freedom, joy, optimism, abundance, lifelong learning.",
        decision_making_style: "Fast and expansive, prioritizing possibilities, novel ideas, and positive opportunities.",
        stress_reactions: "Becomes critical, perfectionistic, and demanding under stress (moves to Type 1).",
        security_triggers: "Becomes focused, quiet, and deeply analytical when safe (moves to Type 5).",
        core_fear: "Being deprived, trapped, or stuck in pain.",
        core_desire: "To be free, happy, and fully satisfied.",
        core_weakness: "Gluttony (insatiable craving for future plans and fresh, exciting stimulations).",
        soul_message: "You will be completely provided for.",
        growth_arrow_desc: "In growth, the Enthusiast integrates towards Type 5, developing focus, deep analytical capacity, and calm patience.",
        stress_arrow_desc: "In stress, the Enthusiast disintegrates towards Type 1, becoming dogmatic, irritable, and structural.",
        growth_action: "Practice staying in the present moment, even when experiencing mild discomfort or boredom.",
        relationship_action: "Commit to deep, serious conversations and showing up during difficult emotional seasons.",
        career_action: "See projects through to completion before launching into the next attractive idea.",
        stress_action: "Slow down your speech, schedule moments of silence, and restrict multitasking.",
        daily_habit: "Stay with a simple, quiet task for twenty consecutive minutes without checking your phone."
    },
    8: {
        name: "The Challenger",
        title: "The Powerful, Dominating Type",
        core_orientation: "Expressing strength, asserting control, and protecting resources.",
        description: "Challengers are self-confident, strong, assertive, and protective. They stand up for beliefs, resist manipulation, and guard their personal vulnerabilities.",
        key_traits: "Direct, protective, decisive, powerful, truth-seeking.",
        key_drivers: "Being self-reliant, protecting their inner circle, dominating spaces, and staying strong.",
        biggest_fear: "Being controlled, harmed, weak, or dependent on others.",
        core_values: "Strength, justice, honesty, control, self-reliance.",
        decision_making_style: "Decisive and action-oriented, preferring intuitive, swift execution that demonstrates leadership.",
        stress_reactions: "Becomes quiet, withdrawn, and hyper-observant under pressure (moves to Type 5).",
        security_triggers: "Becomes open-hearted, caring, and protective of others when safe (moves to Type 2).",
        core_fear: "Being controlled, harmed, or vulnerable.",
        core_desire: "To protect themselves and determine their own path.",
        core_weakness: "Lust (intensity of force, desire to dominate and possess life experiences).",
        soul_message: "You will not be harmed; it is safe to open your heart.",
        growth_arrow_desc: "In growth, the Challenger integrates towards Type 2, displaying gentle care, empathy, and open-hearted vulnerability.",
        stress_arrow_desc: "In stress, the Challenger disintegrates towards Type 5, withdrawing, hoarding energy, and analyzing threat vectors.",
        growth_action: "Practice letting down your defenses and trusting others with your personal vulnerabilities.",
        relationship_action: "Soften your style of communication and listen actively without planning a counterargument.",
        career_action: "Encourage others to lead and build consensus rather than directing by sheer force of will.",
        stress_action: "Recognize when anger is masking fatigue, and check in with your quiet feelings.",
        daily_habit: "Consciously cede control over a small daily choice (such as choosing a restaurant) to someone else."
    },
    9: {
        name: "The Peacemaker",
        title: "The Easygoing, Self-Effacing Type",
        core_orientation: "Maintaining inner calm, resolving conflicts, and adapting to others.",
        description: "Peacemakers are receptive, reassuring, agreeable, and complacent. They avoid conflict to maintain peace, occasionally minimizing their own views.",
        key_traits: "Easygoing, harmonious, accommodating, patient, diplomatic.",
        key_drivers: "Maintaining peace, avoiding tension, holding stability, and uniting groups.",
        biggest_fear: "Fragmentation, separation, conflict, being overlooked, or cut off.",
        core_values: "Harmony, peace of mind, stability, inclusivity, patience.",
        decision_making_style: "Deliberate and consensus-driven, striving to make sure all perspectives feel valued.",
        stress_reactions: "Becomes anxious, reactive, and hyper-vigilant under pressure (moves to Type 6).",
        security_triggers: "Becomes highly focused, efficient, and self-developing when safe (moves to Type 3).",
        core_fear: "Loss of connection, conflict, and separation.",
        core_desire: "To have inner stability and peace of mind.",
        core_weakness: "Sloth (unwillingness to show presence and assert personal desires).",
        soul_message: "Your presence matters in this world.",
        growth_arrow_desc: "In growth, the Peacemaker integrates towards Type 3, asserting presence, prioritizing personal goals, and taking decisive action.",
        stress_arrow_desc: "In stress, the Peacemaker disintegrates towards Type 6, becoming anxious, worried, and overly defensive.",
        growth_action: "State your preferences clearly and recognize that your opinion adds immense value to the group.",
        relationship_action: "Address underlying disagreements directly before minor issues accumulate into resentment.",
        career_action: "Set personal deadlines and hold yourself accountable to completing core priorities.",
        stress_action: "Engage in physical movement or stretching to break out of mental or physical inertia.",
        daily_habit: "Declare one firm choice or preference today without apologizing for it."
    }
};

const wingArchetypes = {
    "1w9": "The Idealist", "1w2": "The Activist",
    "2w1": "The Companion", "2w3": "The Host/Hostess",
    "3w2": "The Star", "3w4": "The Professional",
    "4w3": "The Aristocrat", "4w5": "The Bohemian",
    "5w4": "The Iconoclast", "5w6": "The Troubleshooter",
    "6w5": "The Defender", "6w7": "The Buddy",
    "7w6": "The Pathfinder", "7w8": "The Realist",
    "8w7": "The Independent", "8w9": "The Bear",
    "9w8": "The Referee", "9w1": "The Dreamer"
};

// Global App State
const state = {
    user: null,
    loggedIn: false,
    activeSession: null,
    questions: [],
    answers: {}, // Stores { question_id: { score: 0-5, text_input: '', voice_input: '' } }
    currentIndex: 0,
    recognition: null,
    isListening: false,
    reportsHistory: [],
    myFeedbacks: []
};

// Toast utility
function showToast(message, type = 'error') {
    const existing = document.querySelector('.toast');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.className = `toast ${type === 'success' ? 'success' : ''}`;
    toast.innerText = message;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 4000);
}

// Password Policy Validation: Exactly 6 characters, containing at least one uppercase, one number, and one special char
function validatePassword(password) {
    if (password.length !== 6) {
        return "Password must be exactly 6 characters long.";
    }
    if (!/[A-Z]/.test(password)) {
        return "Password must contain at least one capital letter (A-Z).";
    }
    if (!/[0-9]/.test(password)) {
        return "Password must contain at least one numeric digit (0-9).";
    }
    if (!/[^A-Za-z0-9]/.test(password)) {
        return "Password must contain at least one special character.";
    }
    return null;
}

// App Initialization
document.addEventListener('DOMContentLoaded', async () => {
    initTheme();
    initSpeechRecognition();
    await checkAuth();
});

// Theme Initialization & Toggle
function initTheme() {
    const themeToggle = document.getElementById('theme-toggle');
    if (!themeToggle) return;

    themeToggle.addEventListener('click', () => {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        showToast(`Switched to ${newTheme === 'dark' ? 'Dark' : 'Light'} Mode`, 'success');
    });
}

// Speech Recognition Init
function initSpeechRecognition() {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (SpeechRecognition) {
        state.recognition = new SpeechRecognition();
        state.recognition.continuous = true;
        state.recognition.interimResults = false;
        state.recognition.lang = 'en-US';

        state.recognition.onresult = (event) => {
            const transcriptDisplay = document.getElementById('voice-transcript-display');
            if (transcriptDisplay) {
                const transcript = event.results[event.results.length - 1][0].transcript.trim();
                const currentQuestion = state.questions[state.currentIndex];
                if (currentQuestion) {
                    if (!state.answers[currentQuestion.id]) {
                        state.answers[currentQuestion.id] = { score: 0, text_input: '', voice_input: '' };
                    }
                    const currentVoiceVal = state.answers[currentQuestion.id].voice_input ? state.answers[currentQuestion.id].voice_input.trim() : '';
                    const newVal = currentVoiceVal ? `${currentVoiceVal} ${transcript}` : transcript;
                    state.answers[currentQuestion.id].voice_input = newVal;
                    transcriptDisplay.innerText = newVal;
                }
            }
        };

        state.recognition.onerror = (event) => {
            console.error('Speech recognition error:', event.error);
            stopListening();
            showToast(`Voice input error: ${event.error}. You can still type your response.`);
        };

        state.recognition.onend = () => {
            if (state.isListening) {
                state.recognition.start(); // Auto-restart if we didn't explicitly stop
            }
        };
    }
}

function startListening() {
    if (!state.recognition) {
        showToast('Speech recognition is not supported in this browser. Please use Chrome or Safari.');
        return;
    }
    state.isListening = true;
    state.recognition.start();
    
    const micBtn = document.getElementById('mic-btn');
    const micStatus = document.getElementById('mic-status');
    if (micBtn) micBtn.classList.add('listening');
    if (micStatus) {
        micStatus.classList.add('listening');
        micStatus.innerText = 'Listening... Speak now. Click mic again to stop.';
    }
}

function stopListening() {
    if (!state.recognition) return;
    state.isListening = false;
    state.recognition.stop();
    
    const micBtn = document.getElementById('mic-btn');
    const micStatus = document.getElementById('mic-status');
    if (micBtn) micBtn.classList.remove('listening');
    if (micStatus) {
        micStatus.classList.remove('listening');
        micStatus.innerText = 'Microphone off. Click to answer using voice.';
    }
}

function toggleListening() {
    if (state.isListening) {
        stopListening();
    } else {
        startListening();
    }
}

// Navigation & Auth Checks
async function checkAuth() {
    try {
        const res = await fetch('/api/auth/me');
        const data = await res.json();
        
        if (data.logged_in) {
            state.loggedIn = true;
            state.user = data.user;
            state.activeSession = data.active_session;
            renderHeader();
            
            if (data.user && data.user.force_password_change) {
                showToast('Password change required before accessing assessment.', 'info');
                renderChangePassword();
                return;
            }
            
            if (state.activeSession && state.activeSession.status === 'in_progress') {
                await startQuizFlow(state.activeSession.id);
            } else {
                renderDashboard();
            }
        } else {
            state.loggedIn = false;
            state.user = null;
            state.activeSession = null;
            renderHeader();
            renderWelcome();
        }
    } catch (err) {
        showToast('Connection error. Please try refreshing.');
    }
}

function renderHeader() {
    const nav = document.getElementById('nav-actions');
    if (!nav) return;

    if (state.loggedIn && state.user) {
        nav.innerHTML = `
            <div class="nav-user-info">
                <span class="welcome-user" style="margin-right: 8px;">Welcome, <strong>${state.user.name}</strong></span>
                <button class="btn btn-secondary btn-sm" id="logout-btn">Logout</button>
            </div>
        `;
        document.getElementById('logout-btn').addEventListener('click', handleLogout);
    } else {
        nav.innerHTML = `
            <button class="btn btn-primary btn-sm" onclick="renderLogin()">Login / Register</button>
        `;
    }
}

async function handleLogout() {
    try {
        await fetch('/api/auth/logout', { method: 'POST' });
        state.loggedIn = false;
        state.user = null;
        state.activeSession = null;
        showToast('Logged out successfully.', 'success');
        checkAuth();
    } catch (err) {
        showToast('Error logging out.');
    }
}

// ROUTED RENDERS
const mount = document.getElementById('screen-mount');

function renderWelcome() {
    mount.innerHTML = `
        <div class="screen-card glass landing-hero">
            <div class="hero-logo">
                <img src="${window.LOGO_URL || 'logo.jpg'}" alt="Logo" class="logo-img-hero">
            </div>
            <h1 class="screen-title">Voice-Enabled Enneagram Assessment</h1>
            <p class="screen-subtitle">
                Unlock deep insights into your personality type. Answer questions naturally using your voice to capture raw, authentic emotional context, and visualize your complete cognitive structure.
            </p>
            <div class="hero-actions">
                <button class="btn btn-primary" onclick="renderRegister()">Create Account</button>
                <button class="btn btn-secondary" onclick="renderLogin()">Sign In</button>
            </div>
        </div>
    `;
}

function renderLogin() {
    mount.innerHTML = `
        <div class="screen-card glass" style="max-width: 480px;">
            <h1 class="screen-title" style="text-align: center;">Sign In</h1>
            <p class="screen-subtitle" style="text-align: center;">Welcome back to EnneaDash Voice</p>
            
            <form id="login-form">
                <div class="form-group">
                    <label for="login-email">Email Address</label>
                    <input type="email" id="login-email" class="form-control" placeholder="your@email.com" required>
                </div>
                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <label for="login-password" style="margin-bottom: 0;">Password</label>
                        <a href="#" class="auth-forgot-link" onclick="renderForgotPassword(event)">Forgot Password?</a>
                    </div>
                    <div class="password-input-group">
                        <input type="password" id="login-password" class="form-control" placeholder="••••••••" required style="padding-right: 42px;">
                        <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('login-password', this)" aria-label="Toggle Password Visibility">
                            <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Sign In</button>
            </form>
            <div class="auth-switch-prompt">
                Don't have an account? <span class="auth-switch-link" onclick="renderRegister()">Register here</span>
            </div>
        </div>
    `;
    
    document.getElementById('login-form').addEventListener('submit', handleLoginSubmit);
}

async function handleLoginSubmit(e) {
    e.preventDefault();
    const email = document.getElementById('login-email').value;
    const password = document.getElementById('login-password').value;
    
    const formData = new FormData();
    formData.append('email', email);
    formData.append('password', password);
    
    try {
        const res = await fetch('/api/auth/login', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            if (data.force_change) {
                showToast('Temporary password detected. Please set your new password.', 'info');
                state.loggedIn = true;
                renderChangePassword();
            } else {
                showToast('Welcome back!', 'success');
                checkAuth();
            }
        } else {
            showToast(data.error || 'Invalid credentials.');
        }
    } catch (err) {
        showToast('Login connection failed.');
    }
}

function renderForgotPassword(e) {
    if (e) e.preventDefault();
    mount.innerHTML = `
        <div class="screen-card glass" style="max-width: 480px;">
            <h1 class="screen-title" style="text-align: center;">Reset Password</h1>
            <p class="screen-subtitle" style="text-align: center;">Enter your registered email to receive password reset instructions.</p>
            
            <form id="forgot-password-form">
                <div class="form-group">
                    <label for="forgot-email">Registered Email Address</label>
                    <input type="email" id="forgot-email" class="form-control" placeholder="your@email.com" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                    Send Reset Instructions
                </button>
            </form>
            
            <div class="auth-switch-prompt">
                Remembered your password? <span class="auth-switch-link" onclick="renderLogin()">Sign In here</span>
            </div>
        </div>
    `;
    
    document.getElementById('forgot-password-form').addEventListener('submit', handleForgotPasswordSubmit);
}

async function handleForgotPasswordSubmit(e) {
    e.preventDefault();
    const email = document.getElementById('forgot-email').value;
    showToast('Processing password reset request...', 'info');
    
    const formData = new FormData();
    formData.append('email', email);
    
    try {
        const res = await fetch('/api/auth/forgot-password', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => { renderLogin(); }, 2000);
        } else {
            showToast(data.error || 'Failed to process password reset.');
        }
    } catch (err) {
        showToast('Connection failed. Please try again.');
    }
}

function renderChangePassword() {
    mount.innerHTML = `
        <div class="screen-card glass" style="max-width: 520px;">
            <div style="text-align: center; margin-bottom: 24px;">
                <div style="width: 56px; height: 56px; border-radius: 18px; background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(139, 92, 246, 0.2)); border: 1px solid var(--surface-glass-border); color: var(--accent-indigo); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px; box-shadow: var(--shadow-glow);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                </div>
                <h1 class="screen-title" style="font-size: 1.8rem; margin-bottom: 6px;">Change Your Password</h1>
                <p class="screen-subtitle" style="font-size: 0.92rem; margin-bottom: 0;">For security, you must update your password before accessing the assessment.</p>
            </div>

            <form id="change-password-form">
                <div class="form-group">
                    <label for="change-current-password">Current Password (Default: ed@123)</label>
                    <div class="password-input-group">
                        <input type="password" id="change-current-password" class="form-control" placeholder="••••••••" required style="padding-right: 42px;">
                        <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('change-current-password', this)" aria-label="Toggle Password Visibility">
                            <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="change-new-password">New Password</label>
                    <div class="password-input-group">
                        <input type="password" id="change-new-password" class="form-control" placeholder="Exactly 6 chars (e.g. aB1!cd)" required maxlength="6" style="padding-right: 42px;" oninput="validatePasswordStrengthUI()">
                        <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('change-new-password', this)" aria-label="Toggle Password Visibility">
                            <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>
                </div>

                <!-- Password Strength Meter & Policy Checklist -->
                <div class="strength-box">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <span style="color: var(--text-secondary); font-weight: 500;">Password Strength</span>
                        <span id="strength-text" style="font-weight: 700; color: #ef4444;">Weak</span>
                    </div>
                    <div class="strength-track">
                        <div id="strength-bar" class="strength-fill"></div>
                    </div>
                    <div class="rule-grid">
                        <div id="rule-length" class="rule-item">✕ Exactly 6 characters</div>
                        <div id="rule-upper" class="rule-item">✕ 1 Uppercase letter (A-Z)</div>
                        <div id="rule-number" class="rule-item">✕ 1 Numeric digit (0-9)</div>
                        <div id="rule-special" class="rule-item">✕ 1 Special character</div>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 16px;">
                    <label for="change-confirm-password">Confirm New Password</label>
                    <div class="password-input-group">
                        <input type="password" id="change-confirm-password" class="form-control" placeholder="Repeat new password" required maxlength="6" style="padding-right: 42px;" oninput="validatePasswordMatchUI()">
                        <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('change-confirm-password', this)" aria-label="Toggle Password Visibility">
                            <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>
                    <div id="match-error" style="color: #ef4444; font-size: 0.8rem; margin-top: 6px; display: none;">Passwords do not match</div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                    Save Password & Continue
                </button>
            </form>
        </div>
    `;
    
    document.getElementById('change-password-form').addEventListener('submit', handleChangePasswordSubmit);
}

async function handleChangePasswordSubmit(e) {
    e.preventDefault();
    const currentPassword = document.getElementById('change-current-password').value;
    const newPassword = document.getElementById('change-new-password').value;
    const confirmPassword = document.getElementById('change-confirm-password').value;
    
    if (newPassword !== confirmPassword) {
        showToast('New password and confirm password do not match.');
        return;
    }

    if (newPassword === 'ed@123') {
        showToast('You cannot reuse the default temporary password ed@123.');
        return;
    }

    const errorMsg = validatePassword(newPassword);
    if (errorMsg) {
        showToast(errorMsg);
        return;
    }
    
    showToast('Updating your password...', 'info');
    
    const formData = new FormData();
    formData.append('current_password', currentPassword);
    formData.append('new_password', newPassword);
    formData.append('confirm_password', confirmPassword);
    
    try {
        const res = await fetch('/api/auth/change-password', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            showToast('Password updated successfully!', 'success');
            if (state.user) {
                state.user.force_password_change = false;
            }
            checkAuth();
        } else {
            showToast(data.error || 'Failed to update password.');
        }
    } catch (err) {
        showToast('Connection error. Please try again.');
    }
}

function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    btn.innerHTML = isPassword
        ? `<svg class="eye-off-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"></path><path d="M6.61 6.61A13.52 13.52 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"></path><line x1="2" x2="22" y1="2" y2="22"></line></svg>`
        : `<svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
}

function validatePasswordStrengthUI() {
    const passInput = document.getElementById('change-new-password');
    if (!passInput) return;
    const val = passInput.value;
    
    const hasLen = val.length === 6;
    const hasUpper = /[A-Z]/.test(val);
    const hasNum = /[0-9]/.test(val);
    const hasSpec = /[^A-Za-z0-9]/.test(val);

    updateRuleIndicator('rule-length', hasLen, 'Exactly 6 characters');
    updateRuleIndicator('rule-upper', hasUpper, '1 Uppercase letter (A-Z)');
    updateRuleIndicator('rule-number', hasNum, '1 Numeric digit (0-9)');
    updateRuleIndicator('rule-special', hasSpec, '1 Special character');

    let count = (hasLen ? 1 : 0) + (hasUpper ? 1 : 0) + (hasNum ? 1 : 0) + (hasSpec ? 1 : 0);
    const bar = document.getElementById('strength-bar');
    const text = document.getElementById('strength-text');
    
    if (bar && text) {
        if (count === 0) {
            bar.style.width = '0%';
            bar.style.background = '#ef4444';
            text.innerText = 'Weak';
            text.style.color = '#ef4444';
        } else if (count <= 2) {
            bar.style.width = '40%';
            bar.style.background = '#f59e0b';
            text.innerText = 'Fair';
            text.style.color = '#f59e0b';
        } else if (count === 3) {
            bar.style.width = '75%';
            bar.style.background = '#6366f1';
            text.innerText = 'Good';
            text.style.color = '#6366f1';
        } else {
            bar.style.width = '100%';
            bar.style.background = '#10b981';
            text.innerText = 'Strong';
            text.style.color = '#10b981';
        }
    }

    validatePasswordMatchUI();
}

function updateRuleIndicator(elementId, isValid, labelText) {
    const el = document.getElementById(elementId);
    if (!el) return;
    if (isValid) {
        el.classList.add('valid');
        el.innerText = '✓ ' + labelText;
    } else {
        el.classList.remove('valid');
        el.innerText = '✕ ' + labelText;
    }
}

function validatePasswordMatchUI() {
    const newPass = document.getElementById('change-new-password') ? document.getElementById('change-new-password').value : '';
    const confirmPass = document.getElementById('change-confirm-password') ? document.getElementById('change-confirm-password').value : '';
    const errEl = document.getElementById('match-error');
    if (!errEl) return;
    if (confirmPass.length > 0 && newPass !== confirmPass) {
        errEl.style.display = 'block';
    } else {
        errEl.style.display = 'none';
    }
}

function renderRegister() {
    if (!state.registerData) {
        state.registerData = {
            name: '',
            gender: '',
            email: '',
            password: '',
            age: ''
        };
        state.registerStep = 1;
    }

    const reg = state.registerData;
    const step = state.registerStep;

    // Build the step indicators
    let indicatorHtml = `
        <div class="wizard-steps-container">
            <div class="wizard-step ${step >= 1 ? 'completed' : ''} ${step === 1 ? 'active' : ''}">
                <div class="step-circle">
                    ${step > 1 ? '<span class="step-tick">✓</span>' : '<span class="step-icon">👤</span>'}
                </div>
                <span class="step-label">Name</span>
            </div>
            <div class="step-line ${step > 1 ? 'completed' : ''}"></div>
            <div class="wizard-step ${step >= 2 ? 'completed' : ''} ${step === 2 ? 'active' : ''}">
                <div class="step-circle">
                    ${step > 2 ? '<span class="step-tick">✓</span>' : '<span class="step-icon">👥</span>'}
                </div>
                <span class="step-label">Gender</span>
            </div>
            <div class="step-line ${step > 2 ? 'completed' : ''}"></div>
            <div class="wizard-step ${step >= 3 ? 'completed' : ''} ${step === 3 ? 'active' : ''}">
                <div class="step-circle">
                    ${step > 3 ? '<span class="step-tick">✓</span>' : '<span class="step-icon">✉</span>'}
                </div>
                <span class="step-label">Email</span>
            </div>
            <div class="step-line ${step > 3 ? 'completed' : ''}"></div>
            <div class="wizard-step ${step >= 4 ? 'completed' : ''} ${step === 4 ? 'active' : ''}">
                <div class="step-circle">
                    ${step > 4 ? '<span class="step-tick">✓</span>' : '<span class="step-icon">📅</span>'}
                </div>
                <span class="step-label">Age</span>
            </div>
        </div>
    `;

    // Build the step content
    let stepContentHtml = '';

    if (step === 1) {
        stepContentHtml = `
            <div class="step-card-header">
                <div class="step-header-badge">👤</div>
                <h2 class="step-card-title">What is your name?</h2>
                <p class="step-card-subtitle">Enter your full name to personalize your experience</p>
            </div>
            <div class="form-group">
                <label for="reg-name">Full Name</label>
                <input type="text" id="reg-name" class="form-control" placeholder="Garima Agrawal" value="${reg.name}" required>
            </div>
        `;
    } else if (step === 2) {
        stepContentHtml = `
            <div class="step-card-header">
                <div class="step-header-badge">👥</div>
                <h2 class="step-card-title">How do you identify?</h2>
                <p class="step-card-subtitle">This helps us personalize your experience</p>
            </div>
            <div class="gender-cards-grid">
                <button class="gender-card ${reg.gender === 'Male' ? 'selected' : ''}" data-gender="Male">
                    <span class="gender-emoji">👦</span>
                    <span class="gender-label">Male</span>
                </button>
                <button class="gender-card ${reg.gender === 'Female' ? 'selected' : ''}" data-gender="Female">
                    <span class="gender-emoji">👧</span>
                    <span class="gender-label">Female</span>
                </button>
                <button class="gender-card ${reg.gender === 'Non-Binary' ? 'selected' : ''}" data-gender="Non-Binary">
                    <span class="gender-emoji">🧑</span>
                    <span class="gender-label">Non-Binary</span>
                </button>
                <button class="gender-card ${reg.gender === 'Prefer not to say' ? 'selected' : ''}" data-gender="Prefer not to say">
                    <span class="gender-emoji">🤐</span>
                    <span class="gender-label">Prefer not to say</span>
                </button>
            </div>
        `;
    } else if (step === 3) {
        stepContentHtml = `
            <div class="step-card-header">
                <div class="step-header-badge">✉</div>
                <h2 class="step-card-title">Account Details</h2>
                <p class="step-card-subtitle">Enter your email and choose a secure password</p>
            </div>
            <div class="form-group" style="margin-bottom: 20px;">
                <label for="reg-email">Email Address</label>
                <input type="email" id="reg-email" class="form-control" placeholder="you@domain.com" value="${reg.email}" required>
            </div>
            <div class="form-group">
                <label for="reg-password">Password</label>
                <input type="password" id="reg-password" class="form-control" placeholder="Exactly 6 chars (eg: aB1!cd)" value="${reg.password}" required maxlength="6">
            </div>
        `;
    } else if (step === 4) {
        stepContentHtml = `
            <div class="step-card-header">
                <div class="step-header-badge">📅</div>
                <h2 class="step-card-title">Select your age group</h2>
                <p class="step-card-subtitle">Choose your age range to categorize personality types</p>
            </div>
            <div class="age-cards-grid">
                <button class="age-card ${reg.age === '18-25' ? 'selected' : ''}" data-age="18-25">
                    <span class="age-label">18-25</span>
                </button>
                <button class="age-card ${reg.age === '26-35' ? 'selected' : ''}" data-age="26-35">
                    <span class="age-label">26-35</span>
                </button>
                <button class="age-card ${reg.age === '36-45' ? 'selected' : ''}" data-age="36-45">
                    <span class="age-label">36-45</span>
                </button>
                <button class="age-card ${reg.age === '46-55' ? 'selected' : ''}" data-age="46-55">
                    <span class="age-label">46-55</span>
                </button>
                <button class="age-card ${reg.age === '56-65' ? 'selected' : ''}" data-age="56-65">
                    <span class="age-label">56-65</span>
                </button>
            </div>
        `;
    }

    mount.innerHTML = `
        <div class="screen-card glass" style="max-width: 600px; padding: 30px;">
            ${indicatorHtml}
            
            <div class="wizard-step-content" style="margin-top: 30px; margin-bottom: 30px;">
                ${stepContentHtml}
            </div>
            
            <div class="wizard-actions-bar" style="display: flex; justify-content: space-between; align-items: center; border-open-color: var(--surface-glass-border); border-top: 1px solid var(--surface-glass-border); padding-top: 20px;">
                <button class="btn btn-secondary" id="reg-back-btn" ${step === 1 ? 'style="opacity: 0.5; pointer-events: none;"' : ''}>← Back</button>
                <button class="btn btn-primary" id="reg-next-btn">${step === 4 ? 'Register & Start' : 'Continue →'}</button>
            </div>
            
            <div class="auth-switch-prompt" style="margin-top: 20px;">
                Already registered? <span class="auth-switch-link" onclick="renderLogin()">Sign In here</span>
            </div>
            <div style="font-size: 0.8rem; color: var(--text-muted); text-align: center; margin-top: 10px; line-height: 1.4;">
                Hint: The password should be at least six characters long. To make it stronger, use upper and lower case letters, numbers, and symbols like ! @ " ? $ % ^ &)
            </div>
        </div>
    `;

    // Attach Event Listeners
    if (step === 1) {
        document.getElementById('reg-name').addEventListener('input', (e) => {
            state.registerData.name = e.target.value;
        });
    }

    if (step === 2) {
        const genderCards = document.querySelectorAll('.gender-card');
        genderCards.forEach(card => {
            card.addEventListener('click', () => {
                genderCards.forEach(c => c.classList.remove('selected'));
                card.classList.add('selected');
                state.registerData.gender = card.getAttribute('data-gender');
            });
        });
    }

    if (step === 3) {
        document.getElementById('reg-email').addEventListener('input', (e) => {
            state.registerData.email = e.target.value;
        });
        document.getElementById('reg-password').addEventListener('input', (e) => {
            state.registerData.password = e.target.value;
        });
    }

    if (step === 4) {
        const ageCards = document.querySelectorAll('.age-card');
        ageCards.forEach(card => {
            card.addEventListener('click', () => {
                ageCards.forEach(c => c.classList.remove('selected'));
                card.classList.add('selected');
                state.registerData.age = card.getAttribute('data-age');
            });
        });
    }

    document.getElementById('reg-back-btn').addEventListener('click', () => {
        if (state.registerStep > 1) {
            state.registerStep--;
            renderRegister();
        }
    });

    document.getElementById('reg-next-btn').addEventListener('click', handleRegisterNext);
}

async function handleRegisterNext() {
    const step = state.registerStep;
    const reg = state.registerData;

    if (step === 1) {
        if (!reg.name || reg.name.trim() === '') {
            showToast('Please enter your full name.');
            return;
        }
        state.registerStep = 2;
        renderRegister();
    } else if (step === 2) {
        if (!reg.gender) {
            showToast('Please select how you identify.');
            return;
        }
        state.registerStep = 3;
        renderRegister();
    } else if (step === 3) {
        const emailInput = document.getElementById('reg-email');
        
        if (!reg.email || !emailInput.checkValidity()) {
            showToast('Please enter a valid email address.');
            return;
        }
        const errorMsg = validatePassword(reg.password);
        if (errorMsg) {
            showToast(errorMsg);
            return;
        }
        state.registerStep = 4;
        renderRegister();
    } else if (step === 4) {
        if (!reg.age) {
            showToast('Please select your age group.');
            return;
        }
        
        // Final Submission to API
        const formData = new FormData();
        formData.append('email', reg.email);
        formData.append('password', reg.password);
        formData.append('name', reg.name);
        formData.append('age_group', reg.age);
        formData.append('gender', reg.gender);
        
        try {
            const res = await fetch('/api/auth/register', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            
            if (data.success) {
                showToast('Account registered successfully!', 'success');
                // Clear registration state
                state.registerData = null;
                state.registerStep = 1;
                checkAuth();
            } else {
                showToast(data.error || 'Registration failed.');
            }
        } catch (err) {
            showToast('Registration server failed.');
        }
    }
}

// LOGGED-IN MAIN DASHBOARD
async function renderDashboard() {
    mount.innerHTML = `
        <div class="loader-spinner">
            <div class="spinner"></div>
            <p>Loading dashboard...</p>
        </div>
    `;
    
    try {
        const [resReports, _] = await Promise.all([
            fetch('/api/exam/reports'),
            loadUserFeedbacks()
        ]);
        const data = await resReports.json();
        state.reportsHistory = data.reports || [];
        
        const hasActiveSession = state.activeSession && state.activeSession.status === 'in_progress';
        const hasCompletedReport = state.reportsHistory && state.reportsHistory.length > 0;

        // Slice up to 3 most recent completed assessments (sorted newest first)
        const recentReports = state.reportsHistory.slice(0, 3);
        let recentReportsHtml = '';
        if (recentReports.length > 0) {
            recentReportsHtml = recentReports.map(rep => {
                const repW1 = rep.wing_1;
                const repW2 = rep.wing_2;
                const repRaw = rep.raw_scores || {};
                const repScoreW1 = repRaw[repW1] || 0;
                const repScoreW2 = repRaw[repW2] || 0;
                const repActiveWing = (repScoreW1 > 0 || repScoreW2 > 0) ? (repScoreW1 >= repScoreW2 ? repW1 : repW2) : repW1;
                const repHasWing = repActiveWing !== null && repActiveWing !== undefined && repActiveWing > 0;
                const repTypeWingStr = `Type ${rep.enneagram_type}${repHasWing ? ' w' + repActiveWing : ''}`;
                
                const repDate = new Date(rep.created_at || Date.now()).toLocaleDateString(undefined, {
                    month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit'
                });

                return `
                    <div class="glass" style="padding: 16px 20px; border-radius: var(--radius-md); border: 1px solid var(--surface-glass-border); margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; background: rgba(255, 255, 255, 0.02);">
                        <div>
                            <div style="font-size: 1.1rem; font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
                                <span class="badge-type-pill" style="background: rgba(99, 102, 241, 0.15); color: var(--accent-cyan); border: 1px solid rgba(99, 102, 241, 0.3); padding: 4px 14px; border-radius: 20px; font-size: 0.95rem;">${repTypeWingStr}</span>
                            </div>
                            <div style="font-size: 0.84rem; color: var(--text-muted); margin-top: 6px;">
                                Completed on ${repDate}
                            </div>
                        </div>
                        <div>
                            <button class="btn btn-secondary btn-sm" onclick="showReportDetails(${rep.id})" style="padding: 8px 16px; font-size: 0.85rem; font-weight: 600; white-space: nowrap;">
                                View Report
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            recentReportsHtml = '<p style="color: var(--text-muted); text-align: center; padding: 20px; margin: 0; font-size: 0.9rem;">No previous assessments found.</p>';
        }

        const feedbackListHtml = renderFeedbackListHtml(state.myFeedbacks);

        mount.innerHTML = `
            <div class="screen-card glass">
                <h1 class="screen-title">Enneagram Dashboard</h1>
                <p class="screen-subtitle">Unlock your inner wiring using voice-based expression analysis.</p>
                
                <!-- Voice Enneagram Assessment Card -->
                <div style="background: rgba(99, 102, 241, 0.05); border: 1px solid rgba(99, 102, 241, 0.2); padding: 30px; border-radius: var(--radius-lg); text-align: center; margin-bottom: 28px;">
                    <h2 style="font-size: 1.5rem; margin-bottom: 12px;">${hasActiveSession ? 'Assessment In Progress' : 'Voice Enneagram Assessment'}</h2>
                    <p style="color: var(--text-secondary); max-width: 500px; margin: 0 auto 20px auto; font-size: 0.95rem;">
                        ${hasActiveSession ? 'You have an active assessment in progress. Continue from where you left off with your answers preserved.' : 'This assessment takes about 10-15 minutes. Speak freely about why you agree or disagree with each statement to generate deep analytical results.'}
                    </p>
                    <div style="display: flex; gap: 12px; justify-content: center; align-items: center; flex-wrap: wrap;">
                        <button class="btn btn-accent" onclick="${hasActiveSession ? `startQuizFlow(${state.activeSession.id})` : 'renderConsentScreen()'}">
                            ${hasActiveSession ? 'Resume Assessment' : 'Start Assessment'}
                        </button>
                    </div>
                </div>

                <!-- Recent Assessment History (Last 3 Attempts) -->
                <div class="glass" style="padding: 24px; border-radius: var(--radius-lg); border: 1px solid var(--surface-glass-border); text-align: left; margin-bottom: 28px;">
                    <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--text-primary); margin-top: 0; margin-bottom: 18px; border-bottom: 1px solid var(--surface-glass-border); padding-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--accent-cyan)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="9"/></svg>
                        Recent Assessment Attempts
                    </h3>
                    <div>
                        ${recentReportsHtml}
                    </div>
                </div>

                <!-- Feedback Section & My Feedback List -->
                <div class="glass" style="padding: 24px; border-radius: var(--radius-lg); border: 1px solid var(--surface-glass-border); text-align: left;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 18px; border-bottom: 1px solid var(--surface-glass-border); padding-bottom: 14px;">
                        <div>
                            <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--text-primary); margin: 0; display: flex; align-items: center; gap: 8px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--accent-indigo)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                Feedback & Support
                            </h3>
                            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 4px 0 0 0;">Have a suggestion or facing an issue? Share your feedback with us.</p>
                        </div>
                        <button class="btn btn-primary btn-sm" onclick="openFeedbackModal()" style="display: inline-flex; align-items: center; gap: 6px; padding: 10px 18px; font-size: 0.88rem; font-weight: 600;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Send Feedback
                        </button>
                    </div>

                    <h4 style="font-size: 1.05rem; font-weight: 700; color: var(--text-primary); margin-top: 0; margin-bottom: 14px;">My Feedback</h4>
                    <div id="my-feedback-list">
                        ${feedbackListHtml}
                    </div>
                </div>
            </div>
        `;
    } catch (err) {
        showToast('Failed to load dashboard data.');
    }
}

// GDPR CONSENT SCREEN
function renderConsentScreen() {
    mount.innerHTML = `
        <div class="screen-card glass" style="max-width: 650px;">
            <h1 class="screen-title">GDPR Data & Voice Consent</h1>
            <p class="screen-subtitle">We value your privacy and comply strictly with general data protection regulations.</p>
            
            <div class="consent-content">
                <h3>1. Collection of Voice and Text Data</h3>
                <p>By opting to use the voice features, this application transcribes your speech to text using the Web Speech API directly in your browser. No voice audio recordings are uploaded or stored on our servers; only the transcribed text is processed and saved in our secure database.</p>
                <br>
                <h3>2. How We Use Your Data</h3>
                <p>The transcribed reasons and rating selections are used exclusively to calculate your Enneagram personality report and display historical reports on your personal dashboard. Your data will never be sold, shared, or used for advertising.</p>
                <br>
                <h3>3. Right to Erasure</h3>
                <p>You maintain full control of your data. You can request deletion of your account and all associated test results at any time by contacting support.</p>
            </div>
            
            <form id="consent-form">
                <div style="margin-bottom: 30px;">
                    <label class="consent-checkbox-label">
                        <input type="checkbox" id="consent-check" required checked>
                        <span>I consent to the collection and processing of my rating choices and transcribed voice reasoning text to calculate my personality profile.</span>
                    </label>
                </div>
                
                <div style="display: flex; gap: 16px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="renderDashboard()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Agree & Proceed</button>
                </div>
            </form>
        </div>
    `;
    
    document.getElementById('consent-form').addEventListener('submit', handleConsentSubmit);
}

async function handleConsentSubmit(e) {
    e.preventDefault();
    const consentCheck = document.getElementById('consent-check').checked;
    if (!consentCheck) return;
    
    const formData = new FormData();
    formData.append('consent', '1');
    
    try {
        const res = await fetch('/api/exam/consent', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            await startQuizFlow(data.session_id);
        } else {
            showToast(data.error || 'Failed to register consent.');
        }
    } catch (err) {
        showToast('Error registering GDPR consent.');
    }
}

async function handleBackToDashboard() {
    stopListening();
    
    // Auto-save current question response if any input was provided
    if (state.questions && state.questions[state.currentIndex]) {
        const q = state.questions[state.currentIndex];
        const ans = state.answers[q.id] || {};
        const hasText = ans.text_input && ans.text_input.trim() !== '';
        const hasVoice = ans.voice_input && ans.voice_input.trim() !== '';
        const hasScore = ans.score && ans.score > 0;
        
        if ((hasText || hasVoice || hasScore) && state.activeSession && state.activeSession.id) {
            const combinedReason = [ans.text_input, ans.voice_input].filter(t => t && t.trim() !== '').join(' ');
            const formData = new FormData();
            formData.append('session_id', state.activeSession.id);
            formData.append('question_id', q.id);
            formData.append('score', ans.score || 0);
            formData.append('text_input', ans.text_input || '');
            formData.append('voice_input', ans.voice_input || '');
            formData.append('reason', combinedReason || '');
            formData.append('input_mode', hasVoice && !hasText ? 'voice' : 'text');
            
            try {
                await fetch('/api/exam/answer', {
                    method: 'POST',
                    body: formData
                });
            } catch (err) {
                console.error('Auto-saving answer on back to dashboard failed:', err);
            }
        }
    }
    
    renderDashboard();
}

// QUIZ ENGINE FLOW
async function startQuizFlow(sessionId) {
    state.activeSession = { id: sessionId, status: 'in_progress' };
    
    mount.innerHTML = `
        <div class="loader-spinner">
            <div class="spinner"></div>
            <p>Fetching assessment questions...</p>
        </div>
    `;
    
    try {
        // Fetch active mode configuration
        try {
            const configRes = await fetch('/api/exam/config');
            const configData = await configRes.json();
            if (configData.error) {
                showToast(configData.error);
                renderDashboard();
                return;
            }
            state.activeMode = configData.active_mode || 'hybrid';
        } catch (cfgErr) {
            state.activeMode = 'hybrid';
        }

        // Fetch questions
        const qRes = await fetch('/api/exam/questions');
        const qData = await qRes.json();
        if (qData.error) {
            if (qData.session_inactive || (typeof qData.error === 'string' && qData.error.includes('Invalid or inactive session'))) {
                state.activeSession = null;
                await showReportDetails(qData.report_id);
                return;
            }
            showToast(qData.error);
            renderDashboard();
            return;
        }
        state.questions = qData.questions || [];
        
        // Merge saved answers from server with in-memory state.answers, preserving all responses
        const serverAnswers = qData.saved_answers || {};
        state.answers = { ...serverAnswers, ...(state.answers || {}) };
        
        if (state.questions.length === 0) {
            showToast('No questions found. Database might need seeding.');
            renderDashboard();
            return;
        }

        // If currentIndex is not set or out of range, set to first unanswered question
        if (typeof state.currentIndex !== 'number' || state.currentIndex < 0 || state.currentIndex >= state.questions.length) {
            let firstUnanswered = 0;
            for (let i = 0; i < state.questions.length; i++) {
                const qId = state.questions[i].id;
                const ans = state.answers[qId];
                if (!ans || (!ans.score && !ans.text_input && !ans.voice_input)) {
                    firstUnanswered = i;
                    break;
                }
            }
            state.currentIndex = firstUnanswered;
        }
        
        renderQuestionScreen();
    } catch (err) {
        showToast('Failed to start assessment questionnaire.');
        renderDashboard();
    }
}

function renderQuestionScreen() {
    stopListening();
    
    if (state.currentIndex >= state.questions.length) {
        submitQuiz();
        return;
    }
    
    const q = state.questions[state.currentIndex];
    const progressVal = Math.round((state.currentIndex / state.questions.length) * 100);
    const existingAnswer = state.answers[q.id] || { score: 0, text_input: '', voice_input: '' };
    
    mount.innerHTML = `
        <div class="screen-card glass">
            <div class="quiz-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 12px; flex-wrap: wrap;">
                <button id="assessment-back-dashboard-btn" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; font-size: 0.85rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    Back to Dashboard
                </button>
                <div style="display: flex; align-items: center; gap: 16px;">
                    <span style="color: var(--text-muted); font-weight: 600; font-size: 0.85rem;">ENNEADASH ASSESSMENT</span>
                    <span style="color: var(--accent-cyan); font-weight: 700; font-size: 0.9rem;">Question ${state.currentIndex + 1} of ${state.questions.length}</span>
                </div>
            </div>
            
            <div class="progress-bar-container">
                <div class="progress-fill" style="width: ${progressVal}%;"></div>
            </div>
            
            <div class="question-card">
                <p class="question-text">"${q.prompt_text}"</p>
                
                <div class="likert-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <span style="font-size: 0.85rem; font-weight: 600; color: var(--text-secondary);">Select your level of agreement:</span>
                    <button id="clean-likert-btn" class="btn btn-secondary btn-sm" style="padding: 6px 12px; font-size: 0.8rem; border-color: rgba(239, 68, 68, 0.4); color: #ef4444; border-radius: var(--radius-md);">Clean</button>
                </div>
                
                <div class="likert-scale">
                    <button class="likert-btn ${existingAnswer.score === 1 ? 'selected' : ''}" data-val="1">
                        <span class="likert-value">1</span>
                        <span class="likert-label">Strongly Disagree</span>
                    </button>
                    <button class="likert-btn ${existingAnswer.score === 2 ? 'selected' : ''}" data-val="2">
                        <span class="likert-value">2</span>
                        <span class="likert-label">Disagree</span>
                    </button>
                    <button class="likert-btn ${existingAnswer.score === 3 ? 'selected' : ''}" data-val="3">
                        <span class="likert-value">3</span>
                        <span class="likert-label">Neutral</span>
                    </button>
                    <button class="likert-btn ${existingAnswer.score === 4 ? 'selected' : ''}" data-val="4">
                        <span class="likert-value">4</span>
                        <span class="likert-label">Agree</span>
                    </button>
                    <button class="likert-btn ${existingAnswer.score === 5 ? 'selected' : ''}" data-val="5">
                        <span class="likert-value">5</span>
                        <span class="likert-label">Strongly Agree</span>
                    </button>
                </div>
                
                <div class="multi-modal-inputs" style="display: flex; flex-direction: column; gap: 24px; border-top: 1px solid var(--surface-glass-border); padding-top: 30px; margin-top: 30px; text-align: left;">
                    
                    <!-- Text Input Section -->
                    <div class="text-input-section" style="${(state.activeMode === 'voice' || state.activeMode === 'scale') ? 'display: none;' : ''}">
                        <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <div>
                                <h3 style="font-size: 1.05rem; font-weight: 600; color: var(--text-secondary); margin: 0;">Text Reasoning Option</h3>
                                <span style="font-size: 0.75rem; color: var(--text-muted);">Type your explanation or thoughts here</span>
                            </div>
                            <button id="clean-text-btn" class="btn btn-secondary btn-sm" style="padding: 6px 12px; font-size: 0.8rem; border-color: rgba(239, 68, 68, 0.4); color: #ef4444; border-radius: var(--radius-md);">Clean</button>
                        </div>
                        <textarea id="reasoning-text" class="reasoning-textarea" placeholder="Type your answer here..." style="min-height: 80px;">${existingAnswer.text_input || ''}</textarea>
                    </div>

                    <!-- Voice Note Section -->
                    <div class="voice-note-section" style="${(state.activeMode === 'typing' || state.activeMode === 'scale') ? 'display: none;' : ''}">
                        <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <div>
                                <h3 style="font-size: 1.05rem; font-weight: 600; color: var(--text-secondary); margin: 0;">Voice Note Option</h3>
                                <span style="font-size: 0.75rem; color: var(--text-muted);">Speak your thoughts using speech-to-text</span>
                            </div>
                            <button id="clean-voice-btn" class="btn btn-secondary btn-sm" style="padding: 6px 12px; font-size: 0.8rem; border-color: rgba(239, 68, 68, 0.4); color: #ef4444; border-radius: var(--radius-md);">Clean</button>
                        </div>
                        
                        <!-- Real-time transcribed text display -->
                        <div id="voice-transcript-display" style="background: rgba(0, 0, 0, 0.2); border: 1px solid var(--surface-glass-border); border-radius: var(--radius-md); padding: 14px; min-height: 60px; color: var(--text-primary); font-size: 0.95rem; line-height: 1.5; margin-bottom: 12px; overflow-wrap: break-word;">
                            ${existingAnswer.voice_input ? existingAnswer.voice_input : '<span style="color: var(--text-muted); font-style: italic;">Speak to see transcript here...</span>'}
                        </div>
                        
                        <div class="mic-controls">
                            <button id="mic-btn" class="mic-btn">🎙️</button>
                            <span id="mic-status" class="listening-status">Microphone off. Click to speak.</span>
                        </div>
                    </div>

                </div>
                
                <div class="quiz-footer" style="display: flex; justify-content: space-between; align-items: center; margin-top: 30px;">
                    <button id="prev-btn" class="btn btn-secondary" ${state.currentIndex === 0 ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''}>
                        ← Previous
                    </button>
                    <button id="next-btn" class="btn btn-primary">
                        ${state.currentIndex === state.questions.length - 1 ? 'Submit & Calculate Type' : 'Next Question →'}
                    </button>
                </div>
            </div>
        </div>
    `;
    
    // Attach event handlers
    const backDashBtn = document.getElementById('assessment-back-dashboard-btn');
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');
    const micBtn = document.getElementById('mic-btn');
    const textArea = document.getElementById('reasoning-text');
    const cleanLikertBtn = document.getElementById('clean-likert-btn');
    const cleanTextBtn = document.getElementById('clean-text-btn');
    const cleanVoiceBtn = document.getElementById('clean-voice-btn');
    
    if (backDashBtn) {
        backDashBtn.addEventListener('click', handleBackToDashboard);
    }
    
    if (prevBtn && state.currentIndex > 0) {
        prevBtn.addEventListener('click', () => { state.currentIndex--; renderQuestionScreen(); });
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', validateAndNextQuestion);
    }
    if (micBtn) {
        micBtn.addEventListener('click', toggleListening);
    }
    if (textArea) {
        textArea.addEventListener('input', (e) => {
            if (!state.answers[q.id]) {
                state.answers[q.id] = { score: 0, text_input: '', voice_input: '' };
            }
            state.answers[q.id].text_input = e.target.value;
        });
    }

    if (cleanLikertBtn) {
        cleanLikertBtn.addEventListener('click', () => {
            if (state.answers[q.id]) {
                state.answers[q.id].score = 0;
            }
            document.querySelectorAll('.likert-btn').forEach(btn => btn.classList.remove('selected'));
            showToast('Likert scale rating cleared.', 'info');
        });
    }

    if (cleanTextBtn) {
        cleanTextBtn.addEventListener('click', () => {
            if (state.answers[q.id]) {
                state.answers[q.id].text_input = '';
            }
            const txtArea = document.getElementById('reasoning-text');
            if (txtArea) txtArea.value = '';
            showToast('Text reasoning input cleared.', 'info');
        });
    }

    if (cleanVoiceBtn) {
        cleanVoiceBtn.addEventListener('click', () => {
            if (state.answers[q.id]) {
                state.answers[q.id].voice_input = '';
            }
            const voiceDisp = document.getElementById('voice-transcript-display');
            if (voiceDisp) voiceDisp.innerHTML = '<span style="color: var(--text-muted); font-style: italic;">Speak to see transcript here...</span>';
            stopListening();
            showToast('Voice note transcript cleared.', 'info');
        });
    }
    
    document.querySelectorAll('.likert-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const val = parseInt(e.currentTarget.getAttribute('data-val'));
            document.querySelectorAll('.likert-btn').forEach(b => b.classList.remove('selected'));
            e.currentTarget.classList.add('selected');
            
            if (!state.answers[q.id]) {
                state.answers[q.id] = { score: 0, text_input: '', voice_input: '' };
            }
            state.answers[q.id].score = val;
        });
    });
}

async function validateAndNextQuestion() {
    const q = state.questions[state.currentIndex];
    const ans = state.answers[q.id] || {};
    
    const hasText = ans.text_input && ans.text_input.trim() !== '';
    const hasVoice = ans.voice_input && ans.voice_input.trim() !== '';
    const hasScore = ans.score && ans.score > 0;
    
    if (!hasText && !hasVoice && !hasScore) {
        let hintMsg = 'Please select a scale rating or type an explanation to proceed.';
        if (state.activeMode === 'scale') {
            hintMsg = 'Please select a 1-5 scale rating to proceed.';
        } else if (state.activeMode === 'voice') {
            hintMsg = 'Please select a scale rating or record a voice note to proceed.';
        } else if (state.activeMode === 'hybrid') {
            hintMsg = 'Please select a scale rating, type an explanation, record a voice note, or combine them to proceed.';
        }
        showToast(hintMsg);
        return;
    }
    
    stopListening();
    
    // Combine reasoning for legacy compatibility
    const combinedReason = [ans.text_input, ans.voice_input].filter(t => t && t.trim() !== '').join(' ');
    
    // Save answer to server
    const formData = new FormData();
    formData.append('session_id', state.activeSession.id);
    formData.append('question_id', q.id);
    formData.append('score', ans.score || 0);
    formData.append('text_input', ans.text_input || '');
    formData.append('voice_input', ans.voice_input || '');
    formData.append('reason', combinedReason || '');
    formData.append('input_mode', hasVoice && !hasText ? 'voice' : 'text');
    
    try {
        const res = await fetch('/api/exam/answer', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            state.currentIndex++;
            renderQuestionScreen();
        } else if (data.session_inactive || (typeof data.error === 'string' && data.error.includes('Invalid or inactive session'))) {
            state.activeSession = null;
            await showReportDetails(data.report_id);
        } else {
            showToast(data.error || 'Failed to save answer.');
        }
    } catch (err) {
        showToast('Network error saving answer.');
    }
}

// SUBMIT ASSESSMENT AND CALCULATE RESULTS
async function submitQuiz() {
    mount.innerHTML = `
        <div class="loader-spinner">
            <div class="spinner"></div>
            <p>Compiling responses and generating report...</p>
        </div>
    `;
    
    const formData = new FormData();
    formData.append('session_id', state.activeSession.id);
    
    try {
        const res = await fetch('/api/exam/submit', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            state.activeSession = null;
            showToast('Assessment submitted successfully!', 'success');
            await showReportDetails(data.report_id);
        } else if (data.session_inactive || (typeof data.error === 'string' && data.error.includes('Invalid or inactive session'))) {
            state.activeSession = null;
            await showReportDetails(data.report_id);
        } else {
            showToast(data.error || 'Failed to calculate Enneagram scores.');
            renderDashboard();
        }
    } catch (err) {
        showToast('Error sending quiz submission.');
        renderDashboard();
    }
}

// VIEW ANALYSIS REPORT DETAILS FOR SPECIFIC ASSESSMENT
async function showReportDetails(reportId) {
    mount.innerHTML = `
        <div class="loader-spinner">
            <div class="spinner"></div>
            <p>Retrieving report analysis...</p>
        </div>
    `;
    
    try {
        const res = await fetch('/api/exam/reports');
        const data = await res.json();
        state.reportsHistory = data.reports || [];
        
        let report = null;
        if (reportId) {
            report = state.reportsHistory.find(r => r.id == reportId);
        }
        if (!report && state.reportsHistory.length > 0) {
            report = state.reportsHistory[0];
        }
        if (!report) {
            showToast('No personality report available.', 'info');
            renderDashboard();
            return;
        }
        
        const domType = report.enneagram_type;
        const typeInfo = enneagramTypes[domType] || {
            name: `Type ${domType}`,
            title: 'Enneagram Personality Archetype',
            core_orientation: 'Personality Orientation',
            description: 'Enneagram archetype assessment result.',
            key_traits: 'Conscious and behavioral traits.',
            growth_arrow_desc: 'Growth integration path.',
            growth_action: 'Personal growth recommendation.',
            decision_making_style: 'Cognitive decision-making approach.',
            core_values: 'Guiding values and priorities.'
        };

        const w1 = report.wing_1;
        const w2 = report.wing_2;
        const rawScores = report.raw_scores || {};
        
        const scoreW1 = rawScores[w1] || 0;
        const scoreW2 = rawScores[w2] || 0;
        const activeWing = (scoreW1 > 0 || scoreW2 > 0) ? (scoreW1 >= scoreW2 ? w1 : w2) : w1;
        const hasWing = activeWing !== null && activeWing !== undefined && activeWing > 0;
        const wingKey = hasWing ? `${domType}w${activeWing}` : '';
        const archetype = hasWing ? (wingArchetypes[wingKey] || "Enneagram Archetype") : '';
        const typeAndWingStr = `Type ${domType}${hasWing ? ' w' + activeWing : ''}`;

        const repDate = new Date(report.created_at || Date.now()).toLocaleDateString(undefined, {
            month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit'
        });

        const userEmail = (state.user && state.user.email_id) ? state.user.email_id : '';

        mount.innerHTML = `
            <div class="screen-card glass" style="max-width: 800px; margin: 0 auto;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
                    <div>
                        <h1 class="screen-title" style="margin: 0; font-size: 1.6rem;">Personality Report</h1>
                        <p style="color: var(--text-muted); font-size: 0.88rem; margin-top: 4px;">Completed on ${repDate}</p>
                    </div>
                    <button class="btn btn-secondary" onclick="renderDashboard()">Back to Dashboard</button>
                </div>
                
                <!-- Primary Result Display Card -->
                <div class="primary-result-card glass" style="text-align: center; padding: 36px 24px; margin-bottom: 24px; border: 1px solid var(--surface-glass-border); border-radius: var(--radius-lg); background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(168, 85, 247, 0.1));">
                    <div class="result-badge" style="width: 84px; height: 84px; font-size: 2.6rem; line-height: 84px; margin: 0 auto 16px auto; background: linear-gradient(135deg, #6366f1, #a855f7); color: white; border-radius: 50%; font-weight: 800; box-shadow: 0 8px 24px rgba(99, 102, 241, 0.35);">${domType}</div>
                    <h2 style="font-size: 2.4rem; font-weight: 800; color: var(--text-primary); margin: 0 0 8px 0;">${typeAndWingStr}</h2>
                    <p style="color: var(--accent-cyan); font-size: 1.1rem; font-weight: 600; margin: 0 0 10px 0;">${typeInfo.name} ${hasWing ? '• ' + archetype : ''}</p>
                    <div style="font-size: 0.85rem; color: var(--text-muted);">
                        Completion Date &amp; Time: <strong>${repDate}</strong>
                    </div>
                </div>

                <!-- 1. Personality Summary -->
                <div class="glass" style="padding: 22px 24px; border-radius: var(--radius-lg); border: 1px solid var(--surface-glass-border); text-align: left; margin-bottom: 20px;">
                    <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-primary); margin-top: 0; margin-bottom: 10px; border-bottom: 1px solid var(--surface-glass-border); padding-bottom: 8px;">
                        Personality Summary
                    </h3>
                    <p style="color: var(--text-secondary); font-size: 0.95rem; line-height: 1.7; margin: 0 0 10px 0;">${typeInfo.description}</p>
                    <p style="color: var(--text-muted); font-size: 0.88rem; line-height: 1.6; margin: 0;"><strong>Core Orientation:</strong> ${typeInfo.core_orientation}</p>
                </div>

                <!-- 2. Dominant Wing Archetype & Description -->
                <div class="glass" style="padding: 22px 24px; border-radius: var(--radius-lg); border: 1px solid var(--surface-glass-border); text-align: left; margin-bottom: 20px;">
                    <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-primary); margin-top: 0; margin-bottom: 10px; border-bottom: 1px solid var(--surface-glass-border); padding-bottom: 8px;">
                        Dominant Wing Archetype &amp; Description
                    </h3>
                    <p style="color: var(--accent-cyan); font-size: 0.95rem; font-weight: 600; margin: 0 0 6px 0;">${hasWing ? 'Wing ' + activeWing + ' - ' + archetype : 'No Active Dominant Wing'}</p>
                    <p style="color: var(--text-secondary); font-size: 0.92rem; line-height: 1.6; margin: 0;">
                        ${hasWing ? `Your primary Type ${domType} energy is modified by Type ${activeWing} attributes, expressing as "${archetype}". This wing shapes your behavioral nuances and problem-solving style.` : 'Your scores indicate a balanced expression without a singular dominating wing.'}
                    </p>
                </div>

                <!-- 3. Core Strengths -->
                <div class="glass" style="padding: 22px 24px; border-radius: var(--radius-lg); border: 1px solid var(--surface-glass-border); text-align: left; margin-bottom: 20px;">
                    <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-primary); margin-top: 0; margin-bottom: 10px; border-bottom: 1px solid var(--surface-glass-border); padding-bottom: 8px;">
                        Core Strengths
                    </h3>
                    <p style="color: var(--text-secondary); font-size: 0.92rem; line-height: 1.6; margin: 0 0 8px 0;"><strong>Key Traits:</strong> ${typeInfo.key_traits}</p>
                    <p style="color: var(--text-secondary); font-size: 0.92rem; line-height: 1.6; margin: 0;"><strong>Core Values:</strong> ${typeInfo.core_values}</p>
                </div>

                <!-- 4. Growth Directions -->
                <div class="glass" style="padding: 22px 24px; border-radius: var(--radius-lg); border: 1px solid var(--surface-glass-border); text-align: left; margin-bottom: 20px;">
                    <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-primary); margin-top: 0; margin-bottom: 10px; border-bottom: 1px solid var(--surface-glass-border); padding-bottom: 8px;">
                        Growth Directions
                    </h3>
                    <p style="color: var(--text-secondary); font-size: 0.92rem; line-height: 1.6; margin: 0 0 8px 0;"><strong>Integration Path:</strong> ${typeInfo.growth_arrow_desc}</p>
                    <p style="color: var(--text-secondary); font-size: 0.92rem; line-height: 1.6; margin: 0;"><strong>Growth Practice:</strong> ${typeInfo.growth_action}</p>
                </div>

                <!-- 5. Communication Style -->
                <div class="glass" style="padding: 22px 24px; border-radius: var(--radius-lg); border: 1px solid var(--surface-glass-border); text-align: left; margin-bottom: 24px;">
                    <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-primary); margin-top: 0; margin-bottom: 10px; border-bottom: 1px solid var(--surface-glass-border); padding-bottom: 8px;">
                        Communication Style
                    </h3>
                    <p style="color: var(--text-secondary); font-size: 0.92rem; line-height: 1.6; margin: 0;"><strong>Decision &amp; Expression Style:</strong> ${typeInfo.decision_making_style}</p>
                </div>

                <!-- Email My Report Section -->
                <div class="glass" style="padding: 22px 24px; border-radius: var(--radius-lg); border: 1px solid var(--surface-glass-border); text-align: left; margin-bottom: 24px; background: rgba(99, 102, 241, 0.05);">
                    <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-primary); margin-top: 0; margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--accent-cyan)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        Email My Report
                    </h3>
                    <p style="color: var(--text-muted); font-size: 0.88rem; margin: 0 0 14px 0;">Send a copy of this specific personality report to your email inbox:</p>
                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <input type="email" id="main-report-email-input" class="form-control" placeholder="Enter email address" value="${userEmail}" style="flex: 1; min-width: 200px; padding: 10px 14px; font-size: 0.9rem;">
                        <button id="main-report-send-email-btn" class="btn btn-accent" style="padding: 10px 20px; white-space: nowrap; font-size: 0.9rem; font-weight: 600;">
                            <span>Email My Report</span>
                        </button>
                    </div>
                    <div id="main-email-status-message" style="margin-top: 10px; font-size: 0.85rem; display: none; text-align: left;"></div>
                </div>
            </div>
        `;

        // Event listener for Email My Report button
        const mainSendBtn = document.getElementById('main-report-send-email-btn');
        const mainEmailInput = document.getElementById('main-report-email-input');
        const mainStatus = document.getElementById('main-email-status-message');

        if (mainSendBtn && mainEmailInput) {
            mainSendBtn.addEventListener('click', async () => {
                const email = mainEmailInput.value.trim();
                if (!email) {
                    showToast('Please enter a valid email address.');
                    return;
                }
                
                mainSendBtn.disabled = true;
                mainSendBtn.innerHTML = `<span>Sending...</span>`;
                if (mainStatus) {
                    mainStatus.style.display = 'block';
                    mainStatus.style.color = 'var(--text-muted)';
                    mainStatus.innerText = 'Dispatching email report...';
                }

                try {
                    const formData = new FormData();
                    formData.append('report_id', report.id);
                    formData.append('email', email);
                    
                    const emailRes = await fetch('/api/exam/send_report_email', {
                        method: 'POST',
                        body: formData
                    });
                    const emailData = await emailRes.json();

                    if (emailData.success) {
                        showToast(emailData.message || 'Report emailed successfully!', 'success');
                        if (mainStatus) {
                            mainStatus.style.color = '#10b981';
                            mainStatus.innerText = '✓ Report successfully sent to ' + email;
                        }
                    } else {
                        showToast(emailData.error || 'Failed to send email report.');
                        if (mainStatus) {
                            mainStatus.style.color = '#ef4444';
                            mainStatus.innerText = '✕ ' + (emailData.error || 'Failed to send email.');
                        }
                    }
                } catch (err) {
                    showToast('Error sending report email.');
                    if (mainStatus) {
                        mainStatus.style.color = '#ef4444';
                        mainStatus.innerText = '✕ Error sending report email.';
                    }
                } finally {
                    mainSendBtn.disabled = false;
                    mainSendBtn.innerHTML = `<span>Email My Report</span>`;
                }
            });
        }
    } catch (err) {
        showToast('Failed to load report details.');
        renderDashboard();
    }
}
window.showReportDetails = showReportDetails;
window.renderConsentScreen = renderConsentScreen;
window.renderDashboard = renderDashboard;
window.renderLogin = renderLogin;
window.renderRegister = renderRegister;
window.renderForgotPassword = renderForgotPassword;
window.renderChangePassword = renderChangePassword;
window.togglePasswordVisibility = togglePasswordVisibility;
window.validatePasswordStrengthUI = validatePasswordStrengthUI;
window.validatePasswordMatchUI = validatePasswordMatchUI;

// ==========================================================================
// FEEDBACK SYSTEM ENGINE
// ==========================================================================
let currentFeedbackFile = null;

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

async function loadUserFeedbacks() {
    try {
        const res = await fetch('/api/feedback/list');
        const data = await res.json();
        if (data.success) {
            state.myFeedbacks = data.feedbacks || [];
        }
    } catch (err) {
        console.error('Failed to load user feedbacks:', err);
    }
}

function renderFeedbackListHtml(feedbacks) {
    if (!feedbacks || feedbacks.length === 0) {
        return `
            <div style="text-align: center; padding: 24px 16px; background: var(--input-bg); border-radius: var(--radius-md); border: 1px dashed var(--surface-glass-border);">
                <p style="color: var(--text-muted); margin: 0; font-size: 0.9rem;">
                    No feedback submitted yet. Have questions, suggestions, or encountered an issue? Click <strong>"Send Feedback"</strong> above!
                </p>
            </div>
        `;
    }

    return feedbacks.map(item => {
        const dateStr = new Date(item.created_at || Date.now()).toLocaleDateString(undefined, {
            month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit'
        });
        const status = (item.status || 'Submitted').toLowerCase();
        let statusClass = 'submitted';
        if (status.includes('review')) statusClass = 'in-review';
        if (status.includes('resolve')) statusClass = 'resolved';

        let attachmentHtml = '';
        if (item.attachment_path) {
            const attName = item.attachment_name || 'Attachment';
            attachmentHtml = `
                <a href="/${item.attachment_path}" target="_blank" class="feedback-attachment-link" title="Download attachment">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                    ${escapeHtml(attName)}
                </a>
            `;
        }

        return `
            <div class="feedback-card">
                <div class="feedback-card-header">
                    <h4 class="feedback-title-text">${escapeHtml(item.title)}</h4>
                    <span class="feedback-status-badge ${statusClass}">${escapeHtml(item.status || 'Submitted')}</span>
                </div>
                <p class="feedback-description-text">${escapeHtml(item.description)}</p>
                <div class="feedback-card-footer">
                    <span>Submitted on ${dateStr}</span>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        ${attachmentHtml}
                        <button type="button" class="btn btn-secondary btn-sm" onclick="openViewFeedbackModal(${item.id})" style="padding: 4px 12px; font-size: 0.78rem; font-weight: 600;">
                            View Details
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function openFeedbackModal() {
    currentFeedbackFile = null;
    
    let modalBackdrop = document.getElementById('feedback-modal-backdrop');
    if (!modalBackdrop) {
        modalBackdrop = document.createElement('div');
        modalBackdrop.id = 'feedback-modal-backdrop';
        modalBackdrop.className = 'modal-backdrop';
        modalBackdrop.setAttribute('role', 'dialog');
        modalBackdrop.setAttribute('aria-modal', 'true');
        modalBackdrop.setAttribute('aria-labelledby', 'feedback-modal-title');
        document.body.appendChild(modalBackdrop);
    }

    modalBackdrop.innerHTML = `
        <div class="modal-card">
            <div class="modal-header">
                <h2 id="feedback-modal-title" class="modal-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--accent-cyan)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    Send Feedback
                </h2>
                <button type="button" class="modal-close-btn" onclick="closeFeedbackModal()" aria-label="Close modal">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
            
            <form id="feedback-form" style="display: flex; flex-direction: column; flex: 1; overflow: hidden;">
                <div class="modal-body">
                    <!-- Feedback Title -->
                    <div class="form-group" style="margin-bottom: 20px;">
                        <div class="field-header-row">
                            <label for="fb-title-input" class="field-label">Feedback Title <span style="color:#ef4444;">*</span></label>
                            <span id="fb-title-counter" class="char-counter">0 / 100</span>
                        </div>
                        <input type="text" id="fb-title-input" class="form-control" placeholder="Summarize your issue or suggestion" required maxlength="100">
                    </div>

                    <!-- Description -->
                    <div class="form-group" style="margin-bottom: 20px;">
                        <div class="field-header-row">
                            <label for="fb-desc-input" class="field-label">Description <span style="color:#ef4444;">*</span></label>
                            <span id="fb-desc-counter" class="char-counter">0 / 2000</span>
                        </div>
                        <textarea id="fb-desc-input" class="form-control reasoning-textarea" placeholder="Please describe the issue or feedback in detail..." required maxlength="2000" rows="5" style="min-height: 110px;"></textarea>
                    </div>

                    <!-- File Upload (Optional) -->
                    <div class="form-group" style="margin-bottom: 10px;">
                        <div class="field-header-row">
                            <label class="field-label">Attachment (Optional)</label>
                            <span style="font-size: 0.78rem; color: var(--text-muted);">Max size: 10 MB</span>
                        </div>
                        
                        <!-- Drag & Drop Zone -->
                        <div id="fb-dropzone" class="drag-drop-zone" tabindex="0" role="button" aria-label="Upload attachment">
                            <div class="upload-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
                            </div>
                            <div class="dropzone-prompt">Drag & drop your file here, or <span style="color: var(--accent-indigo); text-decoration: underline;">Browse</span></div>
                            <div class="dropzone-subtext">Supported formats: .jpg, .jpeg, .png, .pdf, .doc</div>
                            <input type="file" id="fb-file-input" accept=".jpg,.jpeg,.png,.pdf,.doc" style="display: none;">
                        </div>

                        <!-- Selected File Preview Container -->
                        <div id="fb-file-preview" style="display: none;"></div>
                    </div>

                    <!-- Upload Progress Indicator -->
                    <div id="fb-progress-container" class="upload-progress-container">
                        <div class="upload-progress-header">
                            <span id="fb-progress-status">Uploading feedback...</span>
                            <span id="fb-progress-percent">0%</span>
                        </div>
                        <div class="upload-progress-track">
                            <div id="fb-progress-fill" class="upload-progress-fill"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeFeedbackModal()">Cancel</button>
                    <button type="submit" id="fb-submit-btn" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
                        <span>Submit Feedback</span>
                    </button>
                </div>
            </form>
        </div>
    `;

    // Make backdrop active with smooth transition
    setTimeout(() => { modalBackdrop.classList.add('active'); }, 10);

    // Focus title input
    const titleInput = document.getElementById('fb-title-input');
    if (titleInput) titleInput.focus();

    // Wire character counter for Title
    titleInput.addEventListener('input', () => {
        const len = titleInput.value.length;
        const counter = document.getElementById('fb-title-counter');
        if (counter) {
            counter.innerText = `${len} / 100`;
            counter.classList.toggle('limit-exceeded', len >= 100);
        }
    });

    // Wire character counter for Description
    const descInput = document.getElementById('fb-desc-input');
    descInput.addEventListener('input', () => {
        const len = descInput.value.length;
        const counter = document.getElementById('fb-desc-counter');
        if (counter) {
            counter.innerText = `${len} / 2000`;
            counter.classList.toggle('limit-exceeded', len >= 2000);
        }
    });

    // Wire Drag and Drop + File Input
    const dropzone = document.getElementById('fb-dropzone');
    const fileInput = document.getElementById('fb-file-input');

    dropzone.addEventListener('click', () => fileInput.click());
    dropzone.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            fileInput.click();
        }
    });

    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.add('drag-over');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('drag-over');
        }, false);
    });

    dropzone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files && files.length > 0) {
            handleFileSelection(files[0]);
        }
    });

    fileInput.addEventListener('change', (e) => {
        if (fileInput.files && fileInput.files.length > 0) {
            handleFileSelection(fileInput.files[0]);
        }
    });

    // Wire Form Submit
    document.getElementById('feedback-form').addEventListener('submit', handleFeedbackSubmit);

    // Close on ESC key or backdrop click
    const handleKeydown = (e) => {
        if (e.key === 'Escape') {
            closeFeedbackModal();
            document.removeEventListener('keydown', handleKeydown);
        }
    };
    document.addEventListener('keydown', handleKeydown);

    modalBackdrop.addEventListener('click', (e) => {
        if (e.target === modalBackdrop) {
            closeFeedbackModal();
        }
    });
}

function closeFeedbackModal() {
    const modalBackdrop = document.getElementById('feedback-modal-backdrop');
    if (modalBackdrop) {
        modalBackdrop.classList.remove('active');
        setTimeout(() => {
            if (modalBackdrop.parentNode) modalBackdrop.parentNode.removeChild(modalBackdrop);
        }, 300);
    }
    currentFeedbackFile = null;
}

function handleFileSelection(file) {
    if (!file) return;

    // Validate file extension
    const allowedExts = ['jpg', 'jpeg', 'png', 'pdf', 'doc'];
    const fileName = file.name || '';
    const ext = fileName.split('.').pop().toLowerCase();

    if (!allowedExts.includes(ext)) {
        showToast('Invalid file format. Only .jpg, .jpeg, .png, .pdf, and .doc files are supported.', 'error');
        return;
    }

    // Validate file size: Max 10 MB (10 * 1024 * 1024 bytes)
    const maxSize = 10 * 1024 * 1024;
    if (file.size > maxSize) {
        showToast('File size exceeds the 10 MB limit. Please select a smaller file.', 'error');
        return;
    }

    currentFeedbackFile = file;
    renderFilePreviewBanner(file, ext);
}

function renderFilePreviewBanner(file, ext) {
    const previewBox = document.getElementById('fb-file-preview');
    if (!previewBox) return;

    const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
    const sizeKb = Math.round(file.size / 1024);
    const displaySize = file.size >= 1024 * 1024 ? `${sizeMb} MB` : `${sizeKb} KB`;

    previewBox.style.display = 'block';
    previewBox.innerHTML = `
        <div class="file-preview-banner">
            <div class="file-info-group">
                <span class="file-icon-badge">${escapeHtml(ext)}</span>
                <div>
                    <div class="file-name-text">${escapeHtml(file.name)}</div>
                    <div class="file-size-text">${displaySize}</div>
                </div>
            </div>
            <button type="button" class="file-remove-btn" onclick="removeFeedbackFile()" title="Remove file" aria-label="Remove attachment">
                ✕
            </button>
        </div>
    `;
}

function removeFeedbackFile() {
    currentFeedbackFile = null;
    const fileInput = document.getElementById('fb-file-input');
    if (fileInput) fileInput.value = '';
    const previewBox = document.getElementById('fb-file-preview');
    if (previewBox) {
        previewBox.style.display = 'none';
        previewBox.innerHTML = '';
    }
}

async function handleFeedbackSubmit(e) {
    e.preventDefault();

    const titleInput = document.getElementById('fb-title-input');
    const descInput = document.getElementById('fb-desc-input');
    const submitBtn = document.getElementById('fb-submit-btn');
    const progressContainer = document.getElementById('fb-progress-container');
    const progressFill = document.getElementById('fb-progress-fill');
    const progressPercent = document.getElementById('fb-progress-percent');
    const progressStatus = document.getElementById('fb-progress-status');

    const title = titleInput.value.trim();
    const description = descInput.value.trim();

    // Client-side validation
    if (!title) {
        showToast('Please enter a feedback title.', 'error');
        titleInput.focus();
        return;
    }

    if (title.length > 100) {
        showToast('Feedback title must not exceed 100 characters.', 'error');
        titleInput.focus();
        return;
    }

    if (!description) {
        showToast('Please enter a description.', 'error');
        descInput.focus();
        return;
    }

    if (description.length > 2000) {
        showToast('Description must not exceed 2000 characters.', 'error');
        descInput.focus();
        return;
    }

    // Disable submit button during upload
    submitBtn.disabled = true;
    submitBtn.innerHTML = `<span>Submitting...</span>`;
    if (progressContainer) progressContainer.style.display = 'block';

    const formData = new FormData();
    formData.append('title', title);
    formData.append('description', description);
    if (currentFeedbackFile) {
        formData.append('attachment', currentFeedbackFile);
    }

    // Use XMLHttpRequest for live upload progress indicator
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/feedback/submit', true);

    xhr.upload.onprogress = (event) => {
        if (event.lengthComputable) {
            const percent = Math.round((event.loaded / event.total) * 100);
            if (progressFill) progressFill.style.width = `${percent}%`;
            if (progressPercent) progressPercent.innerText = `${percent}%`;
            if (progressStatus) progressStatus.innerText = percent < 100 ? 'Uploading attachment...' : 'Processing submission...';
        }
    };

    xhr.onload = async () => {
        let response = {};
        try {
            response = JSON.parse(xhr.responseText);
        } catch (err) {
            response = { error: 'Invalid server response.' };
        }

        if (xhr.status >= 200 && xhr.status < 300 && response.success) {
            if (progressFill) progressFill.style.width = '100%';
            if (progressPercent) progressPercent.innerText = '100%';
            showToast(response.message || 'Feedback submitted successfully!', 'success');

            closeFeedbackModal();

            // Refresh feedbacks list & dashboard
            await loadUserFeedbacks();
            const feedbackListContainer = document.getElementById('my-feedback-list');
            if (feedbackListContainer) {
                feedbackListContainer.innerHTML = renderFeedbackListHtml(state.myFeedbacks);
            } else {
                renderDashboard();
            }
        } else {
            showToast(response.error || 'Failed to submit feedback.', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = `<span>Submit Feedback</span>`;
            if (progressContainer) progressContainer.style.display = 'none';
        }
    };

    xhr.onerror = () => {
        showToast('Network error while submitting feedback.', 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = `<span>Submit Feedback</span>`;
        if (progressContainer) progressContainer.style.display = 'none';
    };

    xhr.send(formData);
}

async function openViewFeedbackModal(feedbackId) {
    let item = (state.myFeedbacks || []).find(f => f.id == feedbackId);
    
    if (!item) {
        try {
            const res = await fetch(`/api/feedback/view?id=${feedbackId}`);
            const data = await res.json();
            if (data.success) {
                item = data.feedback;
            } else {
                showToast(data.error || 'Failed to load feedback details.', 'error');
                return;
            }
        } catch (err) {
            showToast('Error loading feedback details.', 'error');
            return;
        }
    }

    if (!item) return;

    let viewBackdrop = document.getElementById('view-feedback-modal-backdrop');
    if (!viewBackdrop) {
        viewBackdrop = document.createElement('div');
        viewBackdrop.id = 'view-feedback-modal-backdrop';
        viewBackdrop.className = 'modal-backdrop';
        viewBackdrop.setAttribute('role', 'dialog');
        viewBackdrop.setAttribute('aria-modal', 'true');
        viewBackdrop.setAttribute('aria-labelledby', 'view-feedback-modal-title');
        document.body.appendChild(viewBackdrop);
    }

    const createdDate = new Date(item.created_at || Date.now()).toLocaleString(undefined, {
        month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit'
    });
    const updatedDate = item.updated_at ? new Date(item.updated_at).toLocaleString(undefined, {
        month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit'
    }) : createdDate;

    const status = (item.status || 'Submitted').toLowerCase();
    let statusClass = 'submitted';
    if (status.includes('review')) statusClass = 'in-review';
    if (status.includes('resolve')) statusClass = 'resolved';

    let attachmentSectionHtml = '<p style="color: var(--text-muted); font-size: 0.88rem; margin: 0;">No file attached</p>';
    if (item.attachment_path) {
        const attName = item.attachment_name || 'Attachment';
        attachmentSectionHtml = `
            <a href="/${item.attachment_path}" target="_blank" class="feedback-attachment-link" style="padding: 8px 14px; font-size: 0.9rem;" title="Download attachment">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                ${escapeHtml(attName)}
            </a>
        `;
    }

    viewBackdrop.innerHTML = `
        <div class="modal-card">
            <div class="modal-header">
                <h2 id="view-feedback-modal-title" class="modal-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--accent-indigo)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    Feedback Details
                </h2>
                <button type="button" class="modal-close-btn" onclick="closeViewFeedbackModal()" aria-label="Close modal">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
            <div class="modal-body" style="padding: 24px 28px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 16px; border-bottom: 1px solid var(--surface-glass-border); padding-bottom: 14px;">
                    <div>
                        <span style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); font-weight: 700;">Feedback ID: #${item.id}</span>
                        <h3 style="font-size: 1.3rem; font-weight: 700; color: var(--text-primary); margin: 4px 0 0 0;">${escapeHtml(item.title)}</h3>
                    </div>
                    <span class="feedback-status-badge ${statusClass}">${escapeHtml(item.status || 'Submitted')}</span>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="font-size: 0.82rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Description</label>
                    <div style="background: var(--input-bg); border: 1px solid var(--surface-glass-border); border-radius: var(--radius-md); padding: 16px; font-size: 0.92rem; color: var(--text-primary); line-height: 1.6; white-space: pre-wrap; word-break: break-word;">${escapeHtml(item.description)}</div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                    <div>
                        <label style="font-size: 0.82rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 4px;">Submitted On</label>
                        <span style="font-size: 0.88rem; color: var(--text-secondary); font-weight: 500;">${createdDate}</span>
                    </div>
                    <div>
                        <label style="font-size: 0.82rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 4px;">Last Updated</label>
                        <span style="font-size: 0.88rem; color: var(--text-secondary); font-weight: 500;">${updatedDate}</span>
                    </div>
                </div>

                <div>
                    <label style="font-size: 0.82rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Attachment Details</label>
                    ${attachmentSectionHtml}
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeViewFeedbackModal()">Close</button>
            </div>
        </div>
    `;

    setTimeout(() => { viewBackdrop.classList.add('active'); }, 10);

    const handleKeydown = (e) => {
        if (e.key === 'Escape') {
            closeViewFeedbackModal();
            document.removeEventListener('keydown', handleKeydown);
        }
    };
    document.addEventListener('keydown', handleKeydown);

    viewBackdrop.addEventListener('click', (e) => {
        if (e.target === viewBackdrop) {
            closeViewFeedbackModal();
        }
    });
}

function closeViewFeedbackModal() {
    const backdrop = document.getElementById('view-feedback-modal-backdrop');
    if (backdrop) {
        backdrop.classList.remove('active');
        setTimeout(() => {
            if (backdrop.parentNode) backdrop.parentNode.removeChild(backdrop);
        }, 300);
    }
}

window.openFeedbackModal = openFeedbackModal;
window.closeFeedbackModal = closeFeedbackModal;
window.openViewFeedbackModal = openViewFeedbackModal;
window.closeViewFeedbackModal = closeViewFeedbackModal;
window.removeFeedbackFile = removeFeedbackFile;


