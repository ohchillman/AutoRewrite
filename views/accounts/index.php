<?php if (!empty($account['proxy_ip'])): ?>
    <span class="badge <?php echo ($account['proxy_status'] == 'working') ? 'bg-success' : 'bg-danger'; ?>">
        <?php echo htmlspecialchars($account['proxy_ip'] . ':' . $account['proxy_port']); ?>
    </span>
<?php else: ?>
    <span class="text-muted">-</span>
<?php endif; ?>
