<?php
// Temporary config file - replace with real one later
define('SITE_NAME', 'FulizaBoost');
define('SITE_TAGLINE', 'Instant Limit Increase • No Paperwork • Same Day Access');

// Live feed data
$liveFeeds = [
    ['initial' => 'J', 'phone' => '0721****77', 'amount' => '15,000', 'time' => 'just now'],
    ['initial' => 'M', 'phone' => '0723****12', 'amount' => '9,500', 'time' => '2 mins ago'],
    ['initial' => 'D', 'phone' => '0710****90', 'amount' => '4,200', 'time' => '1 min ago'],
    ['initial' => 'S', 'phone' => '0722****33', 'amount' => '5,600', 'time' => '3 mins ago'],
    ['initial' => 'M', 'phone' => '0728****01', 'amount' => '21,300', 'time' => 'just now'],
];

// Pricing data
$pricingPlans = [
    ['limit' => '5,000', 'fee' => '149', 'badge' => ''],
    ['limit' => '7,500', 'fee' => '249', 'badge' => 'Most Popular'],
    ['limit' => '10,000', 'fee' => '349', 'badge' => ''],
    ['limit' => '12,500', 'fee' => '449', 'badge' => ''],
    ['limit' => '21,000', 'fee' => '549', 'badge' => ''],
    ['limit' => '30,000', 'fee' => '649', 'badge' => 'Best Value'],
    ['limit' => '35,000', 'fee' => '749', 'badge' => ''],
    ['limit' => '40,000', 'fee' => '800', 'badge' => ''],
    ['limit' => '45,000', 'fee' => '1030', 'badge' => ''],
    ['limit' => '50,000', 'fee' => '1161', 'badge' => 'Premium'],
    ['limit' => '55,000', 'fee' => '1350', 'badge' => ''],
    ['limit' => '60,000', 'fee' => '2750', 'badge' => ''],
    ['limit' => '65,000', 'fee' => '4049', 'badge' => ''],
    ['limit' => '70,000', 'fee' => '5049', 'badge' => 'Max Limit'],
];

// Simple database class (temporary)
class Database {
    public function getConnection() {
        return null;
    }
}
?>