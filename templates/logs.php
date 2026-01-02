<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap">
    <h1 class="wp-heading-inline">Aktivitäts-Logs</h1>
    <button id="clear-logs-btn" class="page-title-action">Logs leeren</button>
    <hr class="wp-header-end">

    <?php if (empty($logs)): ?>
        <p>Keine Einträge vorhanden.</p>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped" style="margin-top:20px;">
            <thead>
                <tr>
                    <th style="width:160px;">Datum</th>
                    <th style="width:200px;">Seite</th>
                    <th style="width:120px;">Aktion</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= esc_html($log->created_at) ?></td>
                        <td><strong><?= esc_html($log->site_name ?: 'System') ?></strong></td>
                        <td><span class="wpmm-badge"><?= esc_html($log->action) ?></span></td>
                        <td><small><?= esc_html($log->details) ?></small></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
