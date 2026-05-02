<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title">Информация о системе</h3>
    </div>
    <div class="panel-body" style="padding: 10px 15px;">
        
        <table class="table table-striped table-condensed" style="margin-bottom: 0;">
            <tr>
                <th style="width: 120px;">Разработчик:</th>
                <td><?php echo htmlspecialchars($developer['name']); ?></td>
            </tr>
            <tr>
                <th>Организация:</th>
                <td><?php echo htmlspecialchars($developer['company']); ?></td>
            </tr>
            <tr>
                <th>Email:</th>
                <td><a href="mailto:<?php echo htmlspecialchars($developer['email']); ?>"><?php echo htmlspecialchars($developer['email']); ?></a></td>
            </tr>
            <tr>
                <th>Веб-сайты:</th>
                <td>
                    <a href="<?php echo htmlspecialchars($developer['website_1']); ?>" target="_blank"><?php echo htmlspecialchars($developer['website_1']); ?></a>
                    <?php if (!empty($developer['website_2'])): ?>
                        <br><a href="<?php echo htmlspecialchars($developer['website_2']); ?>" target="_blank"><?php echo htmlspecialchars($developer['website_2']); ?></a>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        
        <div style="margin: 15px 0;">
            <button id="checkUpdatesBtn" class="btn btn-primary">
                <i class="glyphicon glyphicon-refresh"></i> Проверить обновления
            </button>
            <span id="checkUpdatesStatus" style="margin-left: 10px;"></span>
        </div>

        <h4 style="margin: 15px 0 10px 0;">
            Установленные модули
            <label style="margin-left: 20px; font-weight: normal; font-size: 14px;">
                <input type="checkbox" id="hideKohana"> Скрыть модули фреймворка
            </label>
        </h4>

        <table class="table table-bordered table-condensed" style="margin-bottom: 0;" id="modulesTable">
            <thead>
                <tr>
                    <th style="width: 40px;">№</th>
                    <th>Модуль</th>
                    <th>Текущая версия</th>
                    <th>Актуальная версия (GitHub)</th>
                    <th>Путь</th>
                </tr>
            </thead>
            <tbody>
                <?php $counter = 1; ?>
                <?php foreach ($modules_list as $module): ?>
                <tr data-module="<?= htmlspecialchars($module['name']) ?>" data-current-version="<?= htmlspecialchars($module['version']) ?>">
                    <td class="text-center"><?= $counter++ ?></td>
                    <td>
                        <strong><?= htmlspecialchars($module['name_display']) ?></strong>
                        <br><small class="text-muted"><?= htmlspecialchars($module['name']) ?></small>
                    </td>
                    <td>
                        <?php if ($module['version_defined']): ?>
                            <span class="label label-primary"><?= htmlspecialchars($module['version']) ?></span>
                        <?php else: ?>
                            <span class="label label-default"><?= htmlspecialchars($module['version']) ?></span>
                            <?php if ($module['version'] !== 'Kohana'): ?>
                                <br><small class="text-muted">(нет константы)</small>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td class="update-cell" data-module="<?= htmlspecialchars($module['name']) ?>">
                        <?php 
                        $status = $module['update_status'];
                        $latest_version = $status['latest_version'];
                        $has_update = $status['has_update'];
                        $error = $status['error'];
                        
                        if ($error): ?>
                            <span class="label label-warning"><?= htmlspecialchars($status['message']) ?></span>
                        <?php elseif ($latest_version !== null): ?>
                            <?php if ($has_update): ?>
                                <span class="label label-danger">
                                    <?= htmlspecialchars($latest_version) ?> (есть обновление!)
                                </span>
                            <?php else: ?>
                                <span class="label label-success">
                                    <?= htmlspecialchars($latest_version) ?> (актуально)
                                </span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="label label-default">Неизвестно</span>
                        <?php endif; ?>
                    </td>
                    <td><small><?= htmlspecialchars(str_replace(DOCROOT, '', $module['path'])) ?></small></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr id="modulesCountRow">
                    <td colspan="5" class="text-center"><strong>Всего модулей: <?php echo count($modules_list); ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<style>
    .glyphicon-spin {
        -webkit-animation: spin 2s infinite linear;
        animation: spin 2s infinite linear;
    }
    @-webkit-keyframes spin {
        0% { -webkit-transform: rotate(0deg); }
        100% { -webkit-transform: rotate(359deg); }
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(359deg); }
    }
</style>

<script>
    (function() {
        const checkbox = document.getElementById('hideKohana');
        const table = document.getElementById('modulesTable');
        const tbody = table.querySelector('tbody');
        const rows = tbody ? Array.from(tbody.querySelectorAll('tr')) : [];
        const countCell = document.querySelector('#modulesCountRow td');
        const totalModules = <?php echo count($modules_list); ?>;
        const checkUpdatesBtn = document.getElementById('checkUpdatesBtn');
        const checkUpdatesStatus = document.getElementById('checkUpdatesStatus');

        function updateVisibleCount() {
            if (!countCell) return;
            const visibleRows = rows.filter(row => {
                return row.style.display !== 'none';
            });
            const visibleCount = visibleRows.length;
            if (checkbox.checked) {
                countCell.innerHTML = `<strong>Всего модулей: ${visibleCount} (из ${totalModules} скрыто ${totalModules - visibleCount})</strong>`;
            } else {
                countCell.innerHTML = `<strong>Всего модулей: ${totalModules}</strong>`;
            }
        }

        function filterRows() {
            const hide = checkbox.checked;
            rows.forEach(row => {
                const version = row.getAttribute('data-current-version');
                if (hide && version === 'Kohana') {
                    row.style.display = 'none';
                } else {
                    row.style.display = '';
                }
            });
            updateVisibleCount();
        }

        if (checkbox) {
            checkbox.addEventListener('change', filterRows);
            filterRows();
        }

        // Обработчик кнопки "Проверить обновления"
        if (checkUpdatesBtn) {
            checkUpdatesBtn.addEventListener('click', function() {
                const btn = this;
                const originalText = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="glyphicon glyphicon-refresh glyphicon-spin"></i> Проверка...';
                checkUpdatesStatus.innerHTML = '<span class="text-info">Идет проверка обновлений...</span>';
                
                // AJAX-запрос к серверу
                fetch('<?= $check_updates_url ?>', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Ошибка сети');
                    }
                    return response.json();
                })
                .then(data => {
                    // Обновляем ячейки с версиями
                    rows.forEach(row => {
                        const moduleName = row.getAttribute('data-module');
                        const currentVersion = row.getAttribute('data-current-version');
                        const updateCell = row.querySelector('.update-cell');
                        
                        if (updateCell && data[moduleName]) {
                            const info = data[moduleName];
                            
                            if (info.error) {
                                updateCell.innerHTML = `<span class="label label-warning">${info.message}</span>`;
                            } else if (info.latest_version) {
                                const hasUpdate = info.has_update;
                                let labelClass = hasUpdate ? 'label-danger' : 'label-success';
                                let extraText = hasUpdate ? ' (есть обновление!)' : ' (актуально)';
                                updateCell.innerHTML = `<span class="label ${labelClass}">${info.latest_version}${extraText}</span>`;
                            } else {
                                updateCell.innerHTML = '<span class="label label-default">Неизвестно</span>';
                            }
                        }
                    });
                    
                    checkUpdatesStatus.innerHTML = '<span class="text-success">Проверка завершена</span>';
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    
                    // Автоматически скрываем сообщение через 3 секунды
                    setTimeout(() => {
                        checkUpdatesStatus.innerHTML = '';
                    }, 3000);
                })
                .catch(error => {
                    console.error('Ошибка при проверке обновлений:', error);
                    checkUpdatesStatus.innerHTML = '<span class="text-danger">Ошибка при проверке обновлений</span>';
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
            });
        }
    })();
</script>