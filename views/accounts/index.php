                                <td>
                                    <?php if (!empty($account['proxy_ip'])): ?>
                                        <span class="badge <?php if ($account['proxy_status'] == 'working'): ?>bg-success<?php else: ?>bg-danger<?php endif; ?>">
                                            <?php echo htmlspecialchars($account['proxy_ip'] . ':' . $account['proxy_port']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
