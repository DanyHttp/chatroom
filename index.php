<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پیام‌رسان</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="chat-container">
        <!-- هدر -->
        <div class="chat-header">
            <h1>💬 پیام‌رسان</h1>
            <div id="room-info" style="display: none;">
                <span id="current-room-code"></span>
                <button id="switch-room-btn" class="btn btn-small">تغییر اتاق</button>
            </div>
        </div>

        <!-- فرم انتخاب/ساخت اتاق -->
        <div id="room-selection-form" class="username-form">
            <div class="username-box">
                <h2>انتخاب یا ساخت اتاق</h2>
                
                <!-- تب‌ها -->
                <div class="room-tabs">
                    <button id="create-tab" class="room-tab active">ساخت اتاق</button>
                    <button id="join-tab" class="room-tab">پیوستن به اتاق</button>
                </div>

                <!-- فرم ساخت اتاق -->
                <div id="create-room-section" class="room-section">
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="auto-generate-code">
                            تولید کد خودکار
                        </label>
                    </div>
                    <div class="form-group" id="manual-code-group">
                        <input type="text" id="create-room-code" placeholder="کد ورود (6 رقم)" maxlength="6" pattern="[0-9]{6}" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <input type="text" id="creator-name" placeholder="نام شما..." maxlength="100" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="room-expiry">مدت زمان انقضای اتاق:</label>
                        <select id="room-expiry" class="form-select">
                            <option value="0">بدون انقضا</option>
                            <option value="1">1 ساعت</option>
                            <option value="24">24 ساعت (1 روز)</option>
                            <option value="168" selected>7 روز</option>
                            <option value="720">30 روز</option>
                            <option value="2160">90 روز</option>
                        </select>
                    </div>
                    <button id="create-room-btn" class="btn btn-primary">ساخت اتاق</button>
                </div>

                <!-- فرم پیوستن به اتاق -->
                <div id="join-room-section" class="room-section" style="display: none;">
                    <div class="form-group">
                        <input type="text" id="join-room-code" placeholder="کد ورود اتاق (6 رقم)" maxlength="6" pattern="[0-9]{6}" autocomplete="off">
                    </div>
                    <button id="join-room-btn" class="btn btn-primary">پیوستن به اتاق</button>
                </div>
            </div>
        </div>

        <!-- فرم ورود نام کاربری -->
        <div id="username-form" class="username-form" style="display: none;">
            <div class="username-box">
                <h2>لطفاً نام خود را وارد کنید</h2>
                <input type="text" id="username-input" placeholder="نام شما..." maxlength="100" autocomplete="off">
                <button id="username-submit" class="btn btn-primary">ورود به چت</button>
            </div>
        </div>

        <!-- بخش چت (مخفی تا زمانی که نام وارد شود) -->
        <div id="chat-section" class="chat-section" style="display: none;">
            <!-- نمایش پیام‌ها -->
            <div id="messages-container" class="messages-container">
                <div class="messages-loading">در حال بارگذاری پیام‌ها...</div>
            </div>

            <!-- فرم ارسال پیام -->
            <div class="message-form-container">
                <form id="message-form" class="message-form">
                    <div class="sender-info">
                        <span id="current-username-display"></span>
                    </div>
                    <div class="input-group">
                        <textarea 
                            id="message-input" 
                            placeholder="پیام خود را بنویسید..." 
                            rows="2" 
                            maxlength="5000"
                        ></textarea>
                        <button type="submit" id="send-button" class="btn btn-send">
                            <span>ارسال</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/js/chat.js"></script>
</body>
</html>
