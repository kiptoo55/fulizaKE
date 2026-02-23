        </main>
        <footer>
            <div class="trust-badges">
                <div class="badge">
                    <span class="badge-icon">🔒</span>
                    <span>Secure Payment</span>
                </div>
                <div class="badge">
                    <span class="badge-icon">⚡</span>
                    <span>Instant Activation</span>
                </div>
                <div class="badge">
                    <span class="badge-icon">✓</span>
                    <span>Verified Service</span>
                </div>
                <div class="badge">
                    <span class="badge-icon">🛡️</span>
                    <span>256-bit Encryption</span>
                </div>
            </div>
            <p class="copyright">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </footer>
    </div>

    <!-- Payment Modal -->
    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            
            <div class="modal-header">
                <h2>Complete Your Payment</h2>
                <p>Secure M-Pesa Transaction</p>
            </div>
            
            <div class="modal-body">
                <div id="selectedPlan" class="selected-plan">
                    <!-- Plan details will be inserted here -->
                </div>
                
                <form id="paymentForm">
                    <div class="form-group">
                        <label for="idNumber">ID Number</label>
                        <input type="text" id="idNumber" name="idNumber" placeholder="e.g., 12345678" maxlength="8">
                        <small>Your National ID number (for record keeping)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">M-Pesa Phone Number</label>
                        <input type="tel" id="phone" name="phone" placeholder="e.g., 0712345678" required maxlength="10">
                        <small>Enter the phone number registered with M-Pesa</small>
                    </div>
                    
                    <input type="hidden" id="amount" name="amount">
                    <input type="hidden" id="limit" name="limit">
                    <input type="hidden" id="fee" name="fee">
                    
                    <button type="submit" class="pay-btn">
                        <span class="btn-text">Pay via M-Pesa</span>
                        <span class="loading-spinner"></span>
                    </button>
                </form>
                
                <div id="paymentStatus" style="display: none;">
                    <div class="status-message">
                        <span class="status-icon">⏳</span>
                        <h3 class="status-title pending">Processing Payment...</h3>
                        <p class="status-description">Please check your phone and enter your M-Pesa PIN</p>
                    </div>
                    <div class="progress-bar">
                        <div class="progress"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="/fuliza+/fuliza/assets/js/script.js"></script>
    
    <!-- Toast Container -->
    <div id="toastContainer" class="toast-container"></div>
</body>
</html>
