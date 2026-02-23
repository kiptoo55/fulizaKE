<section class="live-feed">
    <div class="feed-header">
        <h2>Live Activity</h2>
        <span class="live-badge">● Live</span>
    </div>
    <div class="feed-items">
        <?php foreach ($liveFeeds as $feed): ?>
        <div class="feed-item">
            <div class="user-avatar"><?php echo htmlspecialchars($feed['initial']); ?></div>
            <div class="feed-details">
                <span class="phone"><?php echo htmlspecialchars($feed['phone']); ?></span>
                <span class="action">boosted <strong>Ksh <?php echo htmlspecialchars($feed['amount']); ?></strong></span>
                <div class="feed-time" style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($feed['time']); ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
