<section class="pricing-section">
    <h2>Select Your Fuliza Limit</h2>
    <div class="pricing-grid">
        <?php foreach ($pricingPlans as $plan): ?>
        <div class="pricing-card <?php echo strtolower(str_replace(' ', '-', $plan['badge'])); ?>">
            <?php if ($plan['badge']): ?>
            <div class="card-badge"><?php echo $plan['badge']; ?></div>
            <?php endif; ?>
            <div class="limit-amount">Ksh <?php echo $plan['limit']; ?></div>
            <div class="fee-amount">Fee: <span>Ksh <?php echo $plan['fee']; ?></span></div>
            <button class="select-btn" data-limit="<?php echo $plan['limit']; ?>" data-fee="<?php echo $plan['fee']; ?>">
                Select Plan
            </button>
        </div>
        <?php endforeach; ?>
    </div>
</section>
