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
    const tbody = table ? table.querySelector('tbody') : null;
    const rows = tbody ? Array.from(tbody.querySelectorAll('tr')) : [];
    const countCell = document.querySelector('#modulesCountRow td');
    const totalModules = <?php echo count($modules_list); ?>;
    const checkUpdatesBtn = document.getElementById('checkUpdatesBtn');
    const checkUpdatesStatus = document.getElementById('checkUpdatesStatus');

    function updateVisibleCount() {
        if (!countCell) return;
        const visibleRows = rows.filter(row => row.style.display !== 'none');
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

    // Функция добавления кнопок обновления
    function addUpdateButtons() {
        rows.forEach(row => {
            const moduleName = row.getAttribute('data-module');
            const updateCell = row.querySelector('.update-cell');
            
            // Пропускаем, если уже есть кнопка
            if (updateCell && !updateCell.querySelector('.update-module-btn')) {
                // Находим span с актуальной версией и проверяем, есть ли обновление
                const versionSpan = updateCell.querySelector('span');
                if (versionSpan && versionSpan.classList.contains('label-danger')) {
                    // Извлекаем версию из текста (формат: "1.0.5 (есть обновление!)")
                    const spanText = versionSpan.innerText;
                    const latestVersion = spanText.split(' ')[0];
                    
                    const updateBtn = document.createElement('button');
                    updateBtn.className = 'btn btn-xs btn-success update-module-btn';
                    updateBtn.setAttribute('data-module', moduleName);
                    updateBtn.setAttribute('data-version', latestVersion);
                    updateBtn.innerHTML = '<i class="glyphicon glyphicon-download-alt"></i> Обновить';
                    updateBtn.style.marginLeft = '10px';
                    
                    updateBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        confirmUpdate(moduleName, latestVersion, updateBtn);
                    });
                    
                    updateCell.appendChild(updateBtn);
                }
            }
        });
    }

    function confirmUpdate(moduleName, newVersion, btn) {
        if (confirm(`Обновить модуль "${moduleName}" с версии ${btn.closest('tr').getAttribute('data-current-version')} до ${newVersion}?\n\nРекомендуется сделать резервную копию перед обновлением.`)) {
            performUpdate(moduleName, btn);
        }
    }

    function performUpdate(moduleName, btn) {
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="glyphicon glyphicon-refresh glyphicon-spin"></i> Установка...';
        
        // Создаем индикатор прогресса
        const updateCell = btn.closest('.update-cell');
        const progressDiv = document.createElement('div');
        progressDiv.className = 'progress progress-striped active';
        progressDiv.style.marginTop = '5px';
        progressDiv.style.marginBottom = '0';
        progressDiv.style.width = '100%';
        progressDiv.innerHTML = `
            <div class="progress-bar progress-bar-info" role="progressbar" style="width: 0%">0%</div>
        `;
        updateCell.appendChild(progressDiv);
        
        fetch(`/about/install_update/${moduleName}`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Обновляем индикатор до 100%
                const progressBar = progressDiv.querySelector('.progress-bar');
                if (progressBar) {
                    progressBar.style.width = '100%';
                    progressBar.innerHTML = '100%';
                    progressBar.className = 'progress-bar progress-bar-success';
                }
                
                showNotification('success', `Модуль "${moduleName}" успешно обновлен до версии ${data.message.match(/\d+\.\d+\.\d+/)?.[0] || 'новой'}!`);
                
                // Перезагружаем страницу через 2 секунды
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                showNotification('danger', `Ошибка: ${data.error}`);
                btn.innerHTML = originalText;
                btn.disabled = false;
                progressDiv.remove();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('danger', 'Произошла ошибка при обновлении: ' + error.message);
            btn.innerHTML = originalText;
            btn.disabled = false;
            progressDiv.remove();
        });
    }

    function showNotification(type, message) {
        // Удаляем старые уведомления
        const oldAlerts = document.querySelectorAll('.update-notification');
        oldAlerts.forEach(alert => alert.remove());
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade in update-notification`;
        alertDiv.style.position = 'fixed';
        alertDiv.style.top = '20px';
        alertDiv.style.right = '20px';
        alertDiv.style.zIndex = '9999';
        alertDiv.style.minWidth = '300px';
        alertDiv.innerHTML = `
            <button type="button" class="close" onclick="this.parentElement.remove()">&times;</button>
            <strong>${type === 'success' ? '✅ Успех!' : '❌ Ошибка!'}</strong> ${message}
        `;
        document.body.appendChild(alertDiv);
        
        setTimeout(() => {
            if (alertDiv.parentElement) alertDiv.remove();
        }, 5000);
    }

    // Обработчик кнопки "Проверить обновления"
    if (checkUpdatesBtn) {
        checkUpdatesBtn.addEventListener('click', function() {
            const btn = this;
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="glyphicon glyphicon-refresh glyphicon-spin"></i> Проверка...';
            checkUpdatesStatus.innerHTML = '<span class="text-info">Идет проверка обновлений...</span>';
            
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
                    const updateCell = row.querySelector('.update-cell');
                    
                    if (updateCell && data[moduleName]) {
                        const info = data[moduleName];
                        
                        let newHtml = '';
                        if (info.error) {
                            newHtml = `<span class="label label-warning">${info.message}</span>`;
                        } else if (info.latest_version) {
                            const hasUpdate = info.has_update;
                            let labelClass = hasUpdate ? 'label-danger' : 'label-success';
                            let extraText = hasUpdate ? ' (есть обновление!)' : ' (актуально)';
                            newHtml = `<span class="label ${labelClass}">${info.latest_version}${extraText}</span>`;
                        } else {
                            newHtml = '<span class="label label-default">Неизвестно</span>';
                        }
                        updateCell.innerHTML = newHtml;
                    }
                });
                
                checkUpdatesStatus.innerHTML = '<span class="text-success">✅ Проверка завершена</span>';
                btn.innerHTML = originalText;
                btn.disabled = false;
                
                // ДОБАВЛЯЕМ КНОПКИ ПОСЛЕ ОБНОВЛЕНИЯ ТАБЛИЦЫ
                setTimeout(addUpdateButtons, 100);
                
                setTimeout(() => {
                    checkUpdatesStatus.innerHTML = '';
                }, 3000);
            })
            .catch(error => {
                console.error('Ошибка:', error);
                checkUpdatesStatus.innerHTML = '<span class="text-danger">❌ Ошибка при проверке обновлений</span>';
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        });
    }
    
    // Инициализация: добавляем кнопки после загрузки страницы
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(addUpdateButtons, 200);
        });
    } else {
        setTimeout(addUpdateButtons, 200);
    }
})();
</script>