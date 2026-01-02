/**
 * منطق JavaScript برای سیستم چت با قابلیت اتاق‌ها
 */

let currentUsername = '';
let currentRoomId = null;
let currentRoomCode = '';
let lastMessageId = 0;
let pollingInterval = null;
let isPolling = false;

// عناصر DOM - فرم اتاق
const roomSelectionForm = document.getElementById('room-selection-form');
const createTab = document.getElementById('create-tab');
const joinTab = document.getElementById('join-tab');
const createRoomSection = document.getElementById('create-room-section');
const joinRoomSection = document.getElementById('join-room-section');
const autoGenerateCode = document.getElementById('auto-generate-code');
const manualCodeGroup = document.getElementById('manual-code-group');
const createRoomCode = document.getElementById('create-room-code');
const creatorName = document.getElementById('creator-name');
const roomExpiry = document.getElementById('room-expiry');
const createRoomBtn = document.getElementById('create-room-btn');
const joinRoomCode = document.getElementById('join-room-code');
const joinRoomBtn = document.getElementById('join-room-btn');

// عناصر DOM - فرم نام کاربری و چت
const usernameForm = document.getElementById('username-form');
const usernameInput = document.getElementById('username-input');
const usernameSubmit = document.getElementById('username-submit');
const chatSection = document.getElementById('chat-section');
const messagesContainer = document.getElementById('messages-container');
const messageForm = document.getElementById('message-form');
const messageInput = document.getElementById('message-input');
const sendButton = document.getElementById('send-button');
const currentUsernameDisplay = document.getElementById('current-username-display');
const roomInfo = document.getElementById('room-info');
const currentRoomCodeDisplay = document.getElementById('current-room-code');
const switchRoomBtn = document.getElementById('switch-room-btn');

// بررسی وجود room_code در URL
function getRoomCodeFromURL() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('room') || '';
}

// ذخیره room_code در localStorage
function saveRoomToStorage(roomCode, roomId) {
    localStorage.setItem('room_code', roomCode);
    localStorage.setItem('room_id', roomId.toString());
}

// خواندن room_code از localStorage
function getRoomFromStorage() {
    return {
        code: localStorage.getItem('room_code') || '',
        id: parseInt(localStorage.getItem('room_id') || '0')
    };
}

// پاک کردن room از localStorage
function clearRoomFromStorage() {
    localStorage.removeItem('room_code');
    localStorage.removeItem('room_id');
}

// مدیریت تب‌های فرم اتاق
createTab.addEventListener('click', () => {
    createTab.classList.add('active');
    joinTab.classList.remove('active');
    createRoomSection.style.display = 'block';
    joinRoomSection.style.display = 'none';
});

joinTab.addEventListener('click', () => {
    joinTab.classList.add('active');
    createTab.classList.remove('active');
    createRoomSection.style.display = 'none';
    joinRoomSection.style.display = 'block';
});

// مدیریت checkbox تولید کد خودکار
autoGenerateCode.addEventListener('change', function() {
    if (this.checked) {
        manualCodeGroup.style.display = 'none';
        createRoomCode.value = '';
    } else {
        manualCodeGroup.style.display = 'block';
    }
});

// محدود کردن ورودی کد به اعداد
[createRoomCode, joinRoomCode].forEach(input => {
    input.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
});


// رویداد ساخت اتاق
createRoomBtn.addEventListener('click', handleCreateRoom);
creatorName.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        handleCreateRoom();
    }
});
createRoomCode.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        handleCreateRoom();
    }
});

// رویداد پیوستن به اتاق
joinRoomBtn.addEventListener('click', handleJoinRoom);
joinRoomCode.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        handleJoinRoom();
    }
});

// رویداد تغییر اتاق
switchRoomBtn.addEventListener('click', () => {
    if (confirm('آیا می‌خواهید از این اتاق خارج شوید؟')) {
        stopPolling();
        clearRoomFromStorage();
        window.location.href = 'index.php';
    }
});

// رویداد ورود نام کاربری
usernameSubmit.addEventListener('click', handleUsernameSubmit);
usernameInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        handleUsernameSubmit();
    }
});

// رویداد ارسال پیام
messageForm.addEventListener('submit', handleSendMessage);
messageInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        handleSendMessage(e);
    }
});

/**
 * مدیریت ساخت اتاق
 */
async function handleCreateRoom() {
    const roomCode = createRoomCode.value.trim();
    const creator = creatorName.value.trim();
    const autoGen = autoGenerateCode.checked;
    const expiryHours = parseInt(roomExpiry.value) || 0;
    
    if (!creator) {
        alert('لطفاً نام خود را وارد کنید');
        creatorName.focus();
        return;
    }
    
    if (creator.length > 100) {
        alert('نام نباید بیشتر از 100 کاراکتر باشد');
        return;
    }
    
    if (!autoGen && !roomCode) {
        alert('لطفاً کد ورود را وارد کنید یا گزینه تولید کد خودکار را انتخاب کنید');
        createRoomCode.focus();
        return;
    }
    
    if (!autoGen && !/^\d{6}$/.test(roomCode)) {
        alert('کد ورود باید دقیقاً 6 رقم عددی باشد');
        createRoomCode.focus();
        return;
    }
    
    createRoomBtn.disabled = true;
    createRoomBtn.textContent = 'در حال ساخت...';
    
    try {
        const response = await fetch('api/create_room.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                room_code: autoGen ? '' : roomCode,
                creator_name: creator,
                auto_generate: autoGen,
                expiry_hours: expiryHours
            })
        });
        
        const result = await response.json();
        
        if (result.success && result.data) {
            // ذخیره نام سازنده در localStorage
            localStorage.setItem('pending_username', creator);
            // هدایت به صفحه با room_code در URL
            window.location.href = `index.php?room=${result.data.room_code}`;
        } else {
            alert('خطا در ساخت اتاق: ' + (result.message || 'خطای نامشخص'));
            createRoomBtn.disabled = false;
            createRoomBtn.textContent = 'ساخت اتاق';
        }
    } catch (error) {
        console.error('Error creating room:', error);
        alert('خطا در اتصال به سرور');
        createRoomBtn.disabled = false;
        createRoomBtn.textContent = 'ساخت اتاق';
    }
}

/**
 * مدیریت پیوستن به اتاق
 */
async function handleJoinRoom() {
    const roomCode = joinRoomCode.value.trim();
    
    if (!roomCode) {
        alert('لطفاً کد ورود را وارد کنید');
        joinRoomCode.focus();
        return;
    }
    
    if (!/^\d{6}$/.test(roomCode)) {
        alert('کد ورود باید دقیقاً 6 رقم عددی باشد');
        joinRoomCode.focus();
        return;
    }
    
    joinRoomBtn.disabled = true;
    joinRoomBtn.textContent = 'در حال بررسی...';
    
    try {
        const response = await fetch('api/join_room.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                room_code: roomCode
            })
        });
        
        const result = await response.json();
        
        if (result.success && result.data) {
            // هدایت به صفحه با room_code در URL
            window.location.href = `index.php?room=${result.data.room_code}`;
        } else {
            alert('خطا: ' + (result.message || 'اتاق یافت نشد'));
            joinRoomBtn.disabled = false;
            joinRoomBtn.textContent = 'پیوستن به اتاق';
        }
    } catch (error) {
        console.error('Error joining room:', error);
        alert('خطا در اتصال به سرور');
        joinRoomBtn.disabled = false;
        joinRoomBtn.textContent = 'پیوستن به اتاق';
    }
}

/**
 * بررسی و بارگذاری اطلاعات اتاق از URL
 */
async function checkRoomFromURL() {
    const roomCodeFromURL = getRoomCodeFromURL();
    
    if (!roomCodeFromURL) {
        // اگر room_code در URL نیست، فرم انتخاب اتاق را نمایش بده
        roomSelectionForm.style.display = 'block';
        return false;
    }
    
    // بررسی وجود اتاق
    try {
        const response = await fetch(`api/get_room_info.php?room_code=${roomCodeFromURL}`);
        const result = await response.json();
        
        if (result.success && result.data) {
            currentRoomId = result.data.id;
            currentRoomCode = result.data.room_code;
            saveRoomToStorage(currentRoomCode, currentRoomId);
            
            // نمایش اطلاعات اتاق در هدر
            currentRoomCodeDisplay.textContent = `اتاق: ${currentRoomCode}`;
            roomInfo.style.display = 'block';
            
            // بررسی وجود نام کاربری در localStorage (از ساخت اتاق)
            const pendingUsername = localStorage.getItem('pending_username');
            if (pendingUsername) {
                // اگر نام وجود داشت، مستقیماً به چت برو
                localStorage.removeItem('pending_username'); // پاک کردن از localStorage
                currentUsername = pendingUsername;
                currentUsernameDisplay.textContent = `ارسال به عنوان: ${currentUsername}`;
                
                // مخفی کردن فرم‌ها و نمایش بخش چت
                roomSelectionForm.style.display = 'none';
                usernameForm.style.display = 'none';
                chatSection.style.display = 'flex';
                
                // بارگذاری پیام‌های موجود
                loadMessages();
                
                // شروع polling
                startPolling();
                
                // فوکوس روی فیلد پیام
                messageInput.focus();
                
                return true;
            } else {
                // اگر نام وجود نداشت، فرم نام کاربری را نمایش بده
                roomSelectionForm.style.display = 'none';
                usernameForm.style.display = 'block';
                return true;
            }
        } else {
            alert('اتاقی با این کد ورود یافت نشد');
            roomSelectionForm.style.display = 'block';
            return false;
        }
    } catch (error) {
        console.error('Error checking room:', error);
        alert('خطا در بررسی اتاق');
        roomSelectionForm.style.display = 'block';
        return false;
    }
}

/**
 * مدیریت ورود نام کاربری
 */
function handleUsernameSubmit() {
    const username = usernameInput.value.trim();
    
    if (!username) {
        alert('لطفاً نام خود را وارد کنید');
        usernameInput.focus();
        return;
    }
    
    if (username.length > 100) {
        alert('نام نباید بیشتر از 100 کاراکتر باشد');
        return;
    }
    
    if (!currentRoomId) {
        alert('لطفاً ابتدا به یک اتاق بپیوندید');
        return;
    }
    
    currentUsername = username;
    currentUsernameDisplay.textContent = `ارسال به عنوان: ${currentUsername}`;
    
    // مخفی کردن فرم ورود و نمایش بخش چت
    usernameForm.style.display = 'none';
    chatSection.style.display = 'flex';
    
    // بارگذاری پیام‌های موجود
    loadMessages();
    
    // شروع polling
    startPolling();
    
    // فوکوس روی فیلد پیام
    messageInput.focus();
}

/**
 * بارگذاری پیام‌ها
 */
async function loadMessages() {
    if (!currentRoomId || currentRoomId <= 0) {
        console.error('currentRoomId is not set:', currentRoomId);
        messagesContainer.innerHTML = '<div class="empty-messages">اتاق مشخص نشده است. لطفاً صفحه را رفرش کنید.</div>';
        return;
    }
    
    try {
        messagesContainer.innerHTML = '<div class="messages-loading">در حال بارگذاری پیام‌ها...</div>';
        
        const url = `api/get_messages.php?room_id=${currentRoomId}`;
        console.log('Fetching messages from:', url);
        
        const response = await fetch(url);
        
        // بررسی وضعیت HTTP response
        if (!response.ok) {
            let errorText = '';
            try {
                errorText = await response.text();
                const errorJson = JSON.parse(errorText);
                errorText = errorJson.message || errorText;
            } catch (e) {
                // اگر JSON نبود، همان متن را استفاده کن
            }
            console.error('HTTP Error:', response.status, errorText);
            messagesContainer.innerHTML = '<div class="empty-messages">خطا در دریافت پیام‌ها: ' + errorText + '</div>';
            return;
        }
        
        const result = await response.json();
        console.log('Messages response:', result);
        
        if (!result) {
            messagesContainer.innerHTML = '<div class="empty-messages">پاسخ نامعتبر از سرور</div>';
            return;
        }
        
        if (result.success) {
            messagesContainer.innerHTML = '';
            lastMessageId = 0;
            
            if (!result.data || result.data.length === 0) {
                messagesContainer.innerHTML = '<div class="empty-messages">هنوز پیامی ارسال نشده است</div>';
            } else {
                result.data.forEach(message => {
                    addMessageToUI(message);
                    lastMessageId = Math.max(lastMessageId, message.id);
                });
                scrollToBottom();
            }
        } else {
            const errorMsg = result.message || 'خطای نامشخص';
            console.error('API Error:', errorMsg, result);
            messagesContainer.innerHTML = '<div class="empty-messages">خطا: ' + errorMsg + '</div>';
        }
    } catch (error) {
        console.error('Error loading messages:', error);
        messagesContainer.innerHTML = '<div class="empty-messages">خطا در اتصال به سرور: ' + (error.message || 'خطای نامشخص') + '</div>';
    }
}

/**
 * دریافت پیام‌های جدید
 */
async function fetchNewMessages() {
    if (isPolling || !currentRoomId) return;
    
    isPolling = true;
    
    try {
        const response = await fetch(`api/get_messages.php?room_id=${currentRoomId}&last_id=${lastMessageId}`);
        
        if (!response.ok) {
            console.error('HTTP Error in polling:', response.status);
            return;
        }
        
        const result = await response.json();
        
        if (result && result.success && result.data && result.data.length > 0) {
            result.data.forEach(message => {
                addMessageToUI(message);
                lastMessageId = Math.max(lastMessageId, message.id);
            });
            scrollToBottom();
        }
    } catch (error) {
        console.error('Error fetching new messages:', error);
    } finally {
        isPolling = false;
    }
}

/**
 * شروع polling برای دریافت پیام‌های جدید
 */
function startPolling() {
    stopPolling();
    // دریافت پیام‌های جدید هر 2.5 ثانیه
    pollingInterval = setInterval(fetchNewMessages, 2500);
}

/**
 * توقف polling
 */
function stopPolling() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
}

/**
 * افزودن پیام به رابط کاربری
 */
function addMessageToUI(message) {
    const messageDiv = document.createElement('div');
    messageDiv.className = 'message';
    
    const isSent = message.sender_name === currentUsername;
    messageDiv.classList.add(isSent ? 'sent' : 'received');
    
    const messageContent = document.createElement('div');
    messageContent.className = 'message-content';
    
    const messageHeader = document.createElement('div');
    messageHeader.className = 'message-header';
    messageHeader.textContent = message.sender_name;
    
    const messageText = document.createElement('div');
    messageText.className = 'message-text';
    messageText.textContent = message.message;
    
    const messageTime = document.createElement('span');
    messageTime.className = 'message-time';
    messageTime.textContent = formatTime(message.created_at);
    
    messageContent.appendChild(messageHeader);
    messageContent.appendChild(messageText);
    messageContent.appendChild(messageTime);
    messageDiv.appendChild(messageContent);
    
    messagesContainer.appendChild(messageDiv);
}

/**
 * فرمت کردن زمان
 */
function formatTime(dateString) {
    if (!dateString) {
        return '';
    }
    
    let date;
    
    // Parse کردن تاریخ از فرمت Y-m-d H:i:s
    if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(dateString)) {
        // تبدیل از 'Y-m-d H:i:s' به ISO format (بدون Z برای استفاده از local time)
        const isoString = dateString.replace(' ', 'T');
        date = new Date(isoString);
    } else {
        // اگر فرمت دیگری بود، مستقیماً parse کن
        date = new Date(dateString);
    }
    
    // بررسی معتبر بودن تاریخ
    if (isNaN(date.getTime())) {
        console.error('Invalid date:', dateString);
        return '';
    }
    
    const now = new Date();
    
    // محاسبه تفاوت به میلی‌ثانیه
    const diff = now.getTime() - date.getTime();
    
    // اگر تفاوت منفی است (تاریخ آینده)، تاریخ را نمایش بده
    if (diff < 0) {
        return formatPersianDateTime(date);
    }
    
    // اگر کمتر از یک دقیقه پیش بود
    if (diff < 60000) {
        return 'همین الان';
    }
    
    // اگر امروز بود
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const messageDate = new Date(date.getFullYear(), date.getMonth(), date.getDate());
    
    if (messageDate.getTime() === today.getTime()) {
        // امروز - فقط ساعت و دقیقه
        return formatPersianTime(date);
    }
    
    // اگر دیروز بود
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    if (messageDate.getTime() === yesterday.getTime()) {
        return 'دیروز ' + formatPersianTime(date);
    }
    
    // در غیر این صورت تاریخ کامل
    return formatPersianDateTime(date);
}

/**
 * فرمت کردن زمان به فارسی (فقط ساعت و دقیقه)
 */
function formatPersianTime(date) {
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${hours}:${minutes}`;
}

/**
 * فرمت کردن تاریخ و زمان به فارسی
 */
function formatPersianDateTime(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${year}/${month}/${day} ${hours}:${minutes}`;
}

/**
 * اسکرول به پایین
 */
function scrollToBottom() {
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

/**
 * مدیریت ارسال پیام
 */
async function handleSendMessage(e) {
    e.preventDefault();
    
    const message = messageInput.value.trim();
    
    if (!message) {
        return;
    }
    
    if (!currentUsername) {
        alert('لطفاً ابتدا نام خود را وارد کنید');
        return;
    }
    
    if (!currentRoomId) {
        alert('اتاق مشخص نشده است');
        return;
    }
    
    // غیرفعال کردن دکمه ارسال
    sendButton.disabled = true;
    sendButton.textContent = 'در حال ارسال...';
    
    try {
        const response = await fetch('api/send_message.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                room_id: currentRoomId,
                sender_name: currentUsername,
                message: message
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // پاک کردن فیلد ورودی
            messageInput.value = '';
            messageInput.style.height = 'auto';
            
            // افزودن پیام به UI
            if (result.data) {
                addMessageToUI(result.data);
                lastMessageId = Math.max(lastMessageId, result.data.id);
                scrollToBottom();
            }
        } else {
            alert('خطا در ارسال پیام: ' + (result.message || 'خطای نامشخص'));
        }
    } catch (error) {
        console.error('Error sending message:', error);
        alert('خطا در اتصال به سرور');
    } finally {
        // فعال کردن دکمه ارسال
        sendButton.disabled = false;
        sendButton.innerHTML = '<span>ارسال</span>';
        messageInput.focus();
    }
}

// تنظیم ارتفاع خودکار برای textarea
messageInput.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = (this.scrollHeight) + 'px';
});

// بررسی اتاق هنگام بارگذاری صفحه
window.addEventListener('load', async () => {
    await checkRoomFromURL();
});
