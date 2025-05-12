<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Cuộc gọi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            /* Professional Light Theme */
            --primary-bg: #FFFFFF;
            --secondary-bg: #F7F9FC;
            --card-bg: #FFFFFF;
            --primary-text: #2D3748;
            --secondary-text: #718096;
            --icon-color-default: var(--primary-text); /* Changed for better contrast */
            --icon-color-active: var(--accent-color); /* Icon color for active state button */

            --control-button-bg: #EDF2F7;
            --control-button-hover-bg: #E2E8F0;
            --control-button-active-bg: #CBD5E0;
            --control-button-text-color: var(--primary-text);

            --accent-color: #3182CE; /* Professional blue */
            --accent-text-color-on-accent-bg: #FFFFFF; /* Text on accent background (e.g. old active state) */
            --accent-text-color-on-light-bg: var(--accent-color); /* Accent text on light background */

            --hangup-bg: #E53E3E;
            --hangup-hover-bg: #C53030;
            --hangup-text-color: #FFFFFF;

            --avatar-bg: #E2E8F0;
            --avatar-icon-color: #718096;

            --base-font-size: 16px;
            --border-radius: 12px;
            --control-button-size: 70px; /* Slightly larger for a bit more presence */
            --active-button-border-width: 2px;

            /* Softer shadows for a cleaner look */
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 3px 5px -1px rgba(0, 0, 0, 0.06), 0 2px 3px -1px rgba(0, 0, 0, 0.05);
            --shadow-lg-accent: 0 4px 10px -2px rgba(49, 130, 206, 0.25); /* Accent shadow for active buttons */
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
            background-color: var(--secondary-bg);
            color: var(--primary-text);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-around;
            height: 100vh;
            padding: 20px;
            text-align: center;
            overflow: hidden;
        }

        .call-info {
            margin-top: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            padding: 25px 20px; /* More vertical padding */
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            max-width: 370px; /* Slightly adjusted max-width */
        }

        .avatar-placeholder {
            width: 100px; /* Maintained size */
            height: 100px;
            border-radius: 50%;
            background-color: var(--avatar-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 22px; /* Adjusted spacing */
            box-shadow: var(--shadow-sm);
        }

        .avatar-placeholder .fas {
            font-size: 52px; /* Slightly larger icon */
            color: var(--avatar-icon-color);
        }

        #popup-callee-number-display {
            font-size: 28px; /* More prominent */
            font-weight: 600;
            color: var(--primary-text);
            margin-bottom: 8px; /* Increased margin */
            word-break: break-all;
            line-height: 1.35;
        }

        #popup-call-status,
        #popup-call-timer {
            font-size: 16px;
            color: var(--secondary-text);
            min-height: 22px;
            margin-bottom: 6px; /* Adjusted spacing */
            font-weight: 400;
        }

        .call-controls {
            width: 100%;
            max-width: 370px; /* Match call-info card */
            margin-top: 15px;
            padding: 25px 20px;
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
        }

        .control-buttons-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px; /* Tighter grid for neatness */
            margin-bottom: 22px; /* Adjusted spacing */
        }

        .control-button {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background-color: var(--control-button-bg);
            border: var(--active-button-border-width) solid transparent; /* Prepare for active border */
            border-radius: 50%;
            width: var(--control-button-size);
            height: var(--control-button-size);
            color: var(--control-button-text-color);
            cursor: pointer;
            transition: background-color 0.15s ease, transform 0.1s ease, box-shadow 0.15s ease, border-color 0.15s ease;
            box-shadow: var(--shadow-sm);
        }

        .control-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            box-shadow: none;
            background-color: #E9EDF1; /* Slightly different disabled bg */
            border-color: transparent;
        }

        .control-button:not(:disabled):hover {
            background-color: var(--control-button-hover-bg);
            /* box-shadow: var(--shadow-md); */ /* Optional: Hover shadow, might be too much */
        }

        .control-button:not(:disabled):active {
             background-color: var(--control-button-active-bg);
             transform: scale(0.95);
             box-shadow: inset 0 1px 1px rgba(0,0,0,0.05);
        }

        .control-button .fas {
            font-size: 26px; /* Good balance */
            margin-bottom: 5px;
            color: var(--icon-color-default);
            transition: color 0.15s ease;
        }

        .control-button .button-label {
            font-size: 11px; /* Smaller for a cleaner look */
            color: var(--secondary-text);
            font-weight: 500;
            transition: color 0.15s ease;
        }

        /* Active state for Mute/Hold: White bg, accent border, accent icon/text */
        .control-button.active-state {
            background-color: var(--primary-bg); /* White background */
            border-color: var(--accent-color);
            box-shadow: var(--shadow-lg-accent); /* Accent shadow */
        }
        .control-button.active-state .fas {
            color: var(--icon-color-active);
        }
        .control-button.active-state .button-label {
            color: var(--accent-text-color-on-light-bg);
            font-weight: 600; /* Bolder label when active */
        }

        .hangup-button-container {
            display: flex;
            justify-content: center;
        }

        #popup-hangup-button {
            background-color: var(--hangup-bg);
            color: var(--hangup-text-color);
            width: var(--control-button-size);
            height: var(--control-button-size);
            border-radius: 50%;
            border: var(--active-button-border-width) solid transparent; /* Consistent border setup */
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.15s ease, transform 0.1s ease, box-shadow 0.15s ease;
            box-shadow: var(--shadow-sm);
        }
        #popup-hangup-button .fas {
            font-size: 28px; /* Prominent hangup icon */
            transform: rotate(135deg);
            color: var(--hangup-text-color);
        }

        #popup-hangup-button:disabled {
             opacity: 0.6;
             cursor: not-allowed;
             box-shadow: none;
             background-color: #FCA5A5; /* Lighter red for disabled hangup */
             border-color: transparent;
        }
        #popup-hangup-button:not(:disabled):hover {
            background-color: var(--hangup-hover-bg);
            box-shadow: 0 2px 4px rgba(229, 62, 62, 0.3); /* Hangup hover shadow */
        }
        #popup-hangup-button:not(:disabled):active {
            background-color: var(--hangup-hover-bg);
            transform: scale(0.95);
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="call-info">
        <div class="avatar-placeholder">
            <i class="fas fa-user"></i>
        </div>
        <div id="popup-callee-number-display">-</div>
        <div id="popup-call-status">Đang khởi tạo...</div>
        <div id="popup-call-timer" style="display: none;">00:00:00</div>
    </div>

    <div class="call-controls">
        <div class="control-buttons-grid">
            <button id="popup-mute-button" class="control-button" disabled>
                <i class="fas fa-microphone-slash"></i>
                <span class="button-label">Tắt tiếng</span>
            </button>

            {{-- Example for other buttons - can be added later --}}
            <button class="control-button" disabled> {{-- Placeholder for Keypad --}}
                <i class="fas fa-th"></i>
                <span class="button-label">Bàn phím</span>
            </button>

            <button id="popup-hold-button" class="control-button" disabled>
                <i class="fas fa-pause"></i>
                <span class="button-label">Giữ máy</span>
            </button>
        </div>

        <div class="hangup-button-container">
            <button id="popup-hangup-button" disabled>
                <i class="fas fa-phone-alt"></i>
            </button>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="text/javascript" src="https://sipgetway.voip24h.vn/public/js/voip24hlibrary.min.js"></script>
    <script type="text/javascript" src="https://sipgetway.voip24h.vn/public/js/voip24hgateway.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/adapterjs/0.15.5/adapter.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const SIP_SERVER_IP = '222.255.115.80:5999';
            const SIP_EXTENSION = '604';
            const SIP_PASSWORD = 'demo@voip24h.vn';

            let initialPhoneNumberToCall = null;
            let callStartTime;
            let callTimerInterval;
            let currentCalleeForDisplay = '-';
            let isCallActive = false;
            let ellipsisInterval = null;
            let baseStatusText = '';
            let openerOrigin = null;
            let orderIdForEvent = null;
            let lastRegistrationFailure = false;

            const popupStatusEl = document.getElementById('popup-call-status');
            // const popupCalleeInfoContainer = document.getElementById('popup-callee-info-container'); // Removed container, directly use display
            const popupCalleeNumberDisplay = document.getElementById('popup-callee-number-display');
            const popupTimer = document.getElementById('popup-call-timer');
            const popupHangupButton = document.getElementById('popup-hangup-button');

            const popupMuteButton = document.getElementById('popup-mute-button');
            const muteButtonLabel = popupMuteButton ? popupMuteButton.querySelector('span.button-label') : null;
            const muteButtonIcon = popupMuteButton ? popupMuteButton.querySelector('i') : null;

            const popupHoldButton = document.getElementById('popup-hold-button');
            const holdButtonLabel = popupHoldButton ? popupHoldButton.querySelector('span.button-label') : null;
            const holdButtonIcon = popupHoldButton ? popupHoldButton.querySelector('i') : null;

            function startEllipsisAnimation(baseText) {
                stopEllipsisAnimation(); // Ensure any existing is stopped first
                baseStatusText = baseText;
                let dotCount = 0;
                if (popupStatusEl) popupStatusEl.textContent = baseStatusText; // Initial text (no dots yet)

                ellipsisInterval = setInterval(() => {
                    // Defensive check: If the underlying text has changed from what we started animating,
                    // or if isCallActive is false and we are animating a call-related status, stop.
                    if (popupStatusEl && !popupStatusEl.textContent.startsWith(baseStatusText)) {
                        stopEllipsisAnimation(); // Stop this instance of ellipsis
                        return;
                    }
                    // If we are animating a call-specific active status (like "Đang gọi") but the call is no longer active, stop.
                    if ((baseStatusText === 'Đang gọi' || baseStatusText === 'Đang kết nối') && !isCallActive) {
                        stopEllipsisAnimation();
                        return;
                    }

                    dotCount = (dotCount + 1) % 4;
                    if (popupStatusEl) popupStatusEl.textContent = baseStatusText + '.'.repeat(dotCount);
                }, 500);
            }

            function stopEllipsisAnimation() {
                if (ellipsisInterval) {
                    clearInterval(ellipsisInterval);
                    ellipsisInterval = null;
                }
                if (baseStatusText && popupStatusEl && popupStatusEl.textContent.startsWith(baseStatusText)) {
                    popupStatusEl.textContent = baseStatusText;
                }
                baseStatusText = '';
            }

            function refreshRegistrationStatusDisplay() {
                if (isCallActive) return;
                if (popupStatusEl && popupStatusEl.textContent === 'Đang đăng ký SIP...') return;

                if (typeof isRegistered === 'function') {
                    if (isRegistered()) {
                        updatePopupUI('Đã đăng ký SIP', '-', false);
                        lastRegistrationFailure = false;
                    } else {
                        updatePopupUI(lastRegistrationFailure ? 'Đăng ký SIP thất bại' : 'Chưa đăng ký SIP', '-', false);
                    }
                } else {
                    updatePopupUI('Trạng thái SIP không rõ', '-', false);
                }
            }

            function getQueryParam(param) {
                const urlParams = new URLSearchParams(window.location.search);
                return urlParams.get(param);
            }

            function notifyOpener(eventData) {
                if (window.opener && !window.opener.closed && openerOrigin) {
                    const messagePayload = { ...eventData, orderId: orderIdForEvent };
                    if (['callAccepted', 'callMuteToggled', 'callHoldToggled', 'voipCallEnded'].includes(eventData.event)) {
                        messagePayload.callState = window.callControlAPI.getCallState();
                    }
                    window.opener.postMessage(messagePayload, openerOrigin);
                } else if (!openerOrigin) {
                    console.warn('Opener origin not set. Cannot send message to opener.');
                }
            }

            function updateCallTimer() {
                if (!callStartTime || !popupTimer) return;
                const elapsed = Math.floor((Date.now() - callStartTime) / 1000);
                const hours = String(Math.floor(elapsed / 3600)).padStart(2, '0');
                const minutes = String(Math.floor((elapsed % 3600) / 60)).padStart(2, '0');
                const seconds = String(elapsed % 60).padStart(2, '0');
                if (popupStatusEl.style.display === 'none') { // If status is hidden, show timer there
                    popupTimer.textContent = `${hours}:${minutes}:${seconds}`;
                    popupTimer.style.display = 'block';
                } else { // Else, update the status element itself if it's showing time (like on older iOS)
                     popupStatusEl.textContent = `${hours}:${minutes}:${seconds}`;
                }
            }

            function stopCallTimer() {
                if (callTimerInterval) clearInterval(callTimerInterval);
                callTimerInterval = null;
                if (popupTimer) popupTimer.style.display = 'none';
                 // Restore status text if it was showing timer
                if (popupStatusEl && popupStatusEl.textContent.includes(':')) {
                    popupStatusEl.textContent = baseStatusText || 'Đã kết thúc';
                }
            }

            function startCallTimer() {
                callStartTime = Date.now();
                if (popupStatusEl) popupStatusEl.style.display = 'block'; // Ensure status/timer element is visible
                if (popupTimer) popupTimer.style.display = 'none'; // Hide dedicated timer if status shows time

                updateCallTimer(); // Initial display
                if (callTimerInterval) clearInterval(callTimerInterval);
                callTimerInterval = setInterval(updateCallTimer, 1000);
            }

            function updateMuteButtonState() {
                if (typeof isMute === 'function' && muteButtonIcon && muteButtonLabel && popupMuteButton) {
                    try {
                        const muted = isMute();
                        muteButtonLabel.textContent = muted ? 'Bật tiếng' : 'Tắt tiếng';
                        muteButtonIcon.className = muted ? 'fas fa-microphone-slash' : 'fas fa-microphone';
                        popupMuteButton.classList.toggle('active-state', muted);
                    } catch (e) {
                        console.warn('Could not get mute state:', e);
                        if(muteButtonLabel) muteButtonLabel.textContent = 'Tắt tiếng';
                        if(muteButtonIcon) muteButtonIcon.className = 'fas fa-microphone-slash';
                        if(popupMuteButton) popupMuteButton.classList.remove('active-state');
                    }
                } else if (muteButtonLabel && muteButtonIcon && popupMuteButton) {
                    if(muteButtonLabel) muteButtonLabel.textContent = 'Tắt tiếng';
                    if(muteButtonIcon) muteButtonIcon.className = 'fas fa-microphone-slash';
                    if(popupMuteButton) popupMuteButton.classList.remove('active-state');
                }
            }

            function updateHoldButtonState() {
                 if (typeof isOnHold === 'function' && holdButtonIcon && holdButtonLabel && popupHoldButton) {
                    try {
                        const onHold = isOnHold();
                        holdButtonLabel.textContent = onHold ? 'Tiếp tục' : 'Giữ máy';
                        holdButtonIcon.className = onHold ? 'fas fa-play' : 'fas fa-pause';
                        popupHoldButton.classList.toggle('active-state', onHold);
                    } catch (e) {
                        console.warn('Could not get hold state (isOnHold function might be missing or erroring):', e);
                        if(holdButtonLabel) holdButtonLabel.textContent = 'Giữ máy';
                        if(holdButtonIcon) holdButtonIcon.className = 'fas fa-pause';
                        if(popupHoldButton) popupHoldButton.classList.remove('active-state');
                    }
                } else if (holdButtonLabel && holdButtonIcon && popupHoldButton) {
                    if(holdButtonLabel) holdButtonLabel.textContent = 'Giữ máy';
                    if(holdButtonIcon) holdButtonIcon.className = 'fas fa-pause';
                    if(popupHoldButton) popupHoldButton.classList.remove('active-state');
                }
            }

            function updatePopupUI(statusText, calleeNumForDisplay, activeCall) {
                isCallActive = activeCall;
                currentCalleeForDisplay = calleeNumForDisplay || '-';

                const statesForEllipsis = ['Chuẩn bị gọi', 'Đang đăng ký SIP', 'Đang gọi', 'Đang kết nối'];
                const isTimerString = (txt) => typeof txt === 'string' && /^\d{2}:\d{2}(:\d{2})?$/.test(txt);

                stopEllipsisAnimation();

                if (popupStatusEl) {
                    popupStatusEl.style.display = 'block';

                    if (activeCall && (statusText === "Đã kết nối" || isTimerString(statusText))) {
                        if (popupTimer) popupTimer.style.display = 'none';
                        popupStatusEl.textContent = statusText;
                        if (statusText === "Đã kết nối" && statesForEllipsis.includes(statusText)) {
                             startEllipsisAnimation(statusText);
                        }
                    } else {
                        stopCallTimer();
                        popupStatusEl.textContent = statusText;
                        if (statesForEllipsis.some(s => statusText.startsWith(s))) {
                            startEllipsisAnimation(statusText);
                        }
                    }
                }

                if (popupCalleeNumberDisplay) {
                     popupCalleeNumberDisplay.textContent = currentCalleeForDisplay;
                }

                if (popupTimer && !(activeCall && statusText === "Đã kết nối")) {
                    popupTimer.style.display = 'none';
                }

                if (popupHangupButton) popupHangupButton.disabled = !activeCall;
                if (popupMuteButton) {
                    popupMuteButton.disabled = !activeCall;
                    updateMuteButtonState();
                }
                if (popupHoldButton) {
                    popupHoldButton.disabled = !activeCall;
                    updateHoldButtonState();
                }
                 if (!activeCall && !isTimerString(statusText)) stopCallTimer();

                notifyOpener({
                    event: 'callStatusUpdate',
                    statusText: statusText,
                    calleeDisplay: currentCalleeForDisplay,
                    callState: window.callControlAPI ? window.callControlAPI.getCallState() : { isActive: activeCall, isMuted: false, isOnHold: false }
                });
            }

            function handleSipEvent(event, data) {
                console.info("SIP Event (Call Window):", event, data || '');
                let calleeNumberForEvent = initialPhoneNumberToCall || ((data && data.phonenumber) ? data.phonenumber : currentCalleeForDisplay);

                if (event === 'accepted' || event === 'progress' || event === 'calling') {
                     if (isCallActive) updateHoldButtonState();
                }

                switch (event) {
                    case 'register':
                    case 'registered':
                        console.log('SIP Registered Successfully! Data:', data);
                        lastRegistrationFailure = false;
                        if (initialPhoneNumberToCall) {
                            console.log(`SIP Registered. Proceeding to call: ${initialPhoneNumberToCall}`);
                            currentCalleeForDisplay = initialPhoneNumberToCall;
                            updatePopupUI('Đang gọi', currentCalleeForDisplay, true);
                            if (typeof call === 'function') {
                                call(initialPhoneNumberToCall);
                            } else {
                                console.error('call function is not defined!');
                                updatePopupUI('Lỗi gọi', initialPhoneNumberToCall, false);
                            }
                            initialPhoneNumberToCall = null;
                        } else {
                            refreshRegistrationStatusDisplay();
                        }
                        break;
                    case 'registration_failed':
                    case 'registrationFailed':
                        console.error('SIP Registration Failed!', data);
                        lastRegistrationFailure = true;
                        refreshRegistrationStatusDisplay();
                        Swal.fire('Đăng ký SIP Lỗi', 'Không thể đăng ký. Kiểm tra cấu hình và kết nối.', 'error');
                        break;
                    case 'progress':
                    case 'calling':
                        console.log('Call in progress. Data:', data);
                        updatePopupUI('Đang gọi', calleeNumberForEvent, true);
                        break;
                    case 'incomingcall':
                        console.log('Incoming call in dedicated window. Data:', data);
                        const incomingNumber = (data && data.phonenumber) ? data.phonenumber : 'không rõ';
                        currentCalleeForDisplay = incomingNumber;
                        updatePopupUI('Cuộc gọi đến', incomingNumber, true);
                        Swal.fire({
                            title: 'Cuộc gọi đến!',
                            text: `Từ: ${incomingNumber}`,
                            icon: 'info',
                            showDenyButton: true,
                            confirmButtonText: 'Nghe máy',
                            denyButtonText: `Từ chối`,
                            allowOutsideClick: false,
                        }).then((result) => {
                            if (result.isConfirmed) {
                                if (typeof answerCall === 'function') answerCall();
                                updatePopupUI('Đang kết nối', incomingNumber, true);
                            } else {
                                if (typeof rejectCall === 'function') rejectCall();
                                updatePopupUI('Đã từ chối', incomingNumber, false);
                            }
                        });
                        break;
                    case 'accepted':
                        console.log('Call accepted. Data:', data);
                        updatePopupUI('Đã kết nối', calleeNumberForEvent, true);
                        startCallTimer();
                        notifyOpener({ event: 'callAccepted' });
                        break;
                    case 'terminated':
                    case 'hangup':
                    case 'employer_hangup':
                    case 'customer_hangup':
                        console.log('Call ended. Data:', data);
                        const previousCallActiveState = isCallActive;
                        updatePopupUI('Đã kết thúc', '-', false);
                        if (previousCallActiveState) {
                            notifyOpener({ event: 'voipCallEnded' });
                        }
                        setTimeout(() => {
                            if (!isCallActive) {
                                refreshRegistrationStatusDisplay();
                            }
                        }, 2000);
                        break;
                    case 'error':
                        console.error('SIP Library Error:', data);
                        updatePopupUI('Lỗi SIP', '-', false);
                        Swal.fire('Lỗi SIP', `Lỗi từ thư viện: ${data ? JSON.stringify(data) : 'Không rõ'}`, 'error');
                        break;
                    case 'init':
                        console.log('VoIP Library initialized in call window. Data:', data);
                        updatePopupUI('Đang đăng ký SIP', initialPhoneNumberToCall || '-', false);
                        lastRegistrationFailure = false;
                        try {
                            if (typeof registerSip === 'function') {
                                console.log('Attempting SIP registration:', SIP_SERVER_IP, SIP_EXTENSION);
                                registerSip(SIP_SERVER_IP, SIP_EXTENSION, SIP_PASSWORD);
                            } else {
                                console.error('registerSip function is not defined!');
                                updatePopupUI('Lỗi Khởi Tạo', '-', false);
                                Swal.fire('Lỗi', 'Không tìm thấy hàm đăng ký SIP.', 'error');
                            }
                        } catch (e) {
                            console.error('Error calling registerSip:', e);
                            updatePopupUI('Lỗi Đăng Ký SIP', '-', false);
                            Swal.fire('Lỗi Đăng Ký SIP', 'Lỗi khi cố gắng đăng ký SIP.', 'error');
                        }
                        break;
                    default:
                        console.log('Unhandled SIP Event (Call Window):', event, data);
                }
            }

            function initializeVoip() {
                if (typeof initGateWay === 'function') {
                    console.log('Initializing VoIP Gateway in call window...');
                    initGateWay(handleSipEvent);
                } else {
                    console.error('initGateWay function is not defined!');
                    updatePopupUI('Lỗi Thư Viện', '-', false);
                    Swal.fire('Lỗi Thư Viện', 'Không tìm thấy hàm initGateWay.', 'error');
                }
            }

            if (popupHangupButton) {
                popupHangupButton.addEventListener('click', () => {
                    // Check if a call is active and the hangUp function exists
                    if (isCallActive && typeof hangUp === 'function') {
                        hangUp(); // Execute the hangup action

                        // --- INSTANT FEEDBACK ---
                        // Update the UI immediately to reflect the call has ended.
                        // This will change status text, disable buttons, stop timer etc.
                        updatePopupUI('Đã kết thúc', '-', false);

                    } else if (typeof hangUp !== 'function') {
                        console.error('hangUp function is not defined');
                    }
                    // If !isCallActive, the button should ideally be disabled,
                    // so no action is typically needed if clicked in that state.
                });
            }
            if (popupMuteButton) {
                popupMuteButton.addEventListener('click', () => {
                    if (typeof toggleMute === 'function') {
                        toggleMute();
                        updateMuteButtonState();
                        notifyOpener({ event: 'callMuteToggled' });
                    }
                    else console.error('toggleMute undefined');
                });
            }
            if (popupHoldButton) {
                popupHoldButton.addEventListener('click', () => {
                    if (typeof toggleHold === 'function') {
                        toggleHold();
                        updateHoldButtonState();
                        notifyOpener({ event: 'callHoldToggled' });
                    } else {
                        console.error('toggleHold function is not defined or not supported by the library.');
                    }
                });
            }

            initialPhoneNumberToCall = getQueryParam('phone_number');
            openerOrigin = getQueryParam('openerOrigin');
            orderIdForEvent = getQueryParam('order_id');

            if(initialPhoneNumberToCall) {
                 currentCalleeForDisplay = initialPhoneNumberToCall;
                 updatePopupUI('Chuẩn bị gọi', currentCalleeForDisplay, false);
            } else {
                 updatePopupUI('Đang khởi tạo', '-', false);
            }
            initializeVoip();

            window.addEventListener('beforeunload', (event) => {
                if (isCallActive) {
                }
            });

            window.callControlAPI = {
                hangUpCall: () => {
                    if (typeof hangUp === 'function') {
                        hangUp();
                        return true;
                    }
                    console.error('hangUp function not available in call window');
                    return false;
                },
                toggleMuteCall: () => {
                    if (popupMuteButton) {
                        popupMuteButton.click();
                        return true;
                    }
                    console.error('toggleMute function or button not available');
                    return false;
                },
                toggleHoldCall: () => {
                    if (popupHoldButton) {
                        popupHoldButton.click();
                        return true;
                    }
                    console.error('toggleHold function or button not available');
                    return false;
                },
                getCallState: () => {
                    return {
                        isActive: isCallActive,
                        isMuted: (typeof isMute === 'function') ? isMute() : false,
                        isOnHold: (typeof isOnHold === 'function') ? isOnHold() : false
                    };
                }
            };
        });
    </script>
</body>
</html>
