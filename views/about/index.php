﻿<div class="panel panel-primary">
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

        <div class="alert alert-info" style="margin: 15px 0;">
            <i class="glyphicon glyphicon-info-sign"></i>
            Информация о модулях получена из локальных файлов системы.
        </div>

        <h4 style="margin: 15px 0 10px 0;">
            Установленные модули
            <label style="margin-left: 20px; font-weight: normal; font-size: 14px;">
                <input type="checkbox" id="hideKohana" checked> Скрыть модули фреймворка
            </label>
        </h4>

        <table class="table table-bordered table-condensed" style="margin-bottom: 0;" id="modulesTable">
            <thead>
                <tr>
                    <th style="width: 40px;">№</th>
                    <th>Модуль</th>
                    <th>Версия</th>
                    <th>Источник версии</th>
                    <th>Статус</th>
                    <th>Путь</th>
					<th>Титул страницы</th>
                 </tr>
            </thead>
            <tbody>
                <?php $counter = 1; ?>
                <?php foreach ($modules_list as $module): ?>
                <tr data-module="<?= htmlspecialchars($module['name']) ?>" data-version="<?= htmlspecialchars($module['version']) ?>">
                    <td class="text-center"><?= $counter++ ?></td>
                    <td>
                        <?= htmlspecialchars($module['name']) ?>
                    </td>
                    <td>
                        <?php if ($module['version_defined']): ?>
                            <span class="label label-primary"><?= htmlspecialchars($module['version']) ?></span>
                        <?php elseif ($module['version'] === 'Kohana'): ?>
                            <span class="label label-default">Kohana Core</span>
                        <?php else: ?>
                            <span class="label label-warning"><?= htmlspecialchars($module['version']) ?></span>
                        <?php endif; ?>
                    </td>
					<td>
                        <small><?= htmlspecialchars($module['version_source']) ?></small>
                    </td>
                    <td>
                        <?php if ($module['is_active']): ?>
                            <span class="label label-success">Активен</span>
                        <?php else: ?>
                            <span class="label label-danger">Неактивен</span>
                        <?php endif; ?>
                    </td>
                    <td><small><?echo htmlspecialchars(str_replace(DOCROOT, '', $module['path'])) ?></small></td>
					<td>
                        <?echo htmlspecialchars($module['name_display']) ?>
                     </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr id="modulesCountRow">
                    <td colspan="6" class="text-center"><strong>Всего модулей: <?php echo count($modules_list); ?></strong></td>
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

    // ============ ФИЛЬТРЫ ============
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
            const version = row.getAttribute('data-version');
            // Скрываем модули ядра Kohana
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
        // По умолчанию скрываем Kohana модули
        setTimeout(filterRows, 100);
    }

    // ============ СОХРАНЕНИЕ CSV ============
    function saveTableAsCSV() {
        const table = document.getElementById('modulesTable');
        const allRows = table.querySelectorAll('tbody tr');
        let csv = [];
        
        // Заголовки
        const headers = [];
        table.querySelectorAll('thead th').forEach(th => {
            headers.push(th.textContent.trim());
        });
        csv.push(headers.join(';'));
        
        // Данные
        allRows.forEach(row => {
            if (row.style.display === 'none') return;
            const rowData = [];
            row.querySelectorAll('td').forEach(td => {
                let text = td.textContent.trim();
                text = text.replace(/\s+/g, ' ').trim();
                text = text.replace(/"/g, '""');
                rowData.push(`"${text}"`);
            });
            csv.push(rowData.join(';'));
        });
        
        const blob = new Blob(['\uFEFF' + csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `modules_list_${new Date().toISOString().slice(0,10)}.csv`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(link.href);
    }

    // ============ КНОПКА СОХРАНЕНИЯ ============
    const panelBody = document.querySelector('.panel-body');
    if (panelBody) {
        const saveDiv = document.createElement('div');
        saveDiv.style.cssText = 'margin: 15px 0;';
        
        const button = document.createElement('button');
        button.className = 'btn btn-success';
        button.innerHTML = '<i class="glyphicon glyphicon-download-alt"></i> Сохранить CSV';
        button.onclick = saveTableAsCSV;
        
        saveDiv.appendChild(button);
        
        const h4 = panelBody.querySelector('h4');
        const infoDiv = panelBody.querySelector('.alert');
        if (infoDiv) {
            panelBody.insertBefore(saveDiv, infoDiv.nextSibling);
        } else {
            panelBody.insertBefore(saveDiv, h4);
        }
    }

})();
</script>