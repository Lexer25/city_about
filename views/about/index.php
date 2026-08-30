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
			<tr>
                <th>Пользователь:</th>
                <td><?php echo htmlspecialchars($user_info['city_name']); ?></td>
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
                    <td><small><?php echo htmlspecialchars(str_replace(DOCROOT, '', $module['path'])) ?></small></td>
					<td>
                        <?php echo htmlspecialchars($module['name_display']) ?>
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
    var checkbox = document.getElementById('hideKohana');
    var table = document.getElementById('modulesTable');
    var tbody = table.querySelector('tbody');
    var rows = tbody ? Array.from(tbody.querySelectorAll('tr')) : [];
    var countCell = document.querySelector('#modulesCountRow td');
    var totalModules = <?php echo count($modules_list); ?>;

    // ============ ФИЛЬТРЫ ============
    function updateVisibleCount() {
        if (!countCell) return;
        var visibleRows = rows.filter(function(row) {
            return row.style.display !== 'none';
        });
        var visibleCount = visibleRows.length;
        if (checkbox.checked) {
            countCell.innerHTML = '<strong>Всего модулей: ' + visibleCount + ' (из ' + totalModules + ' скрыто ' + (totalModules - visibleCount) + ')</strong>';
        } else {
            countCell.innerHTML = '<strong>Всего модулей: ' + totalModules + '</strong>';
        }
    }

    function filterRows() {
        var hide = checkbox.checked;
        rows.forEach(function(row) {
            var version = row.getAttribute('data-version');
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
        setTimeout(filterRows, 100);
    }

    // ============ СОХРАНЕНИЕ CSV ============
    function saveTableAsCSV() {
        var table = document.getElementById('modulesTable');
        var allRows = table.querySelectorAll('tbody tr');
        var csv = [];
        
        // --- ПОЛУЧАЕМ ОБЪЕКТ ---
        var objectElement = document.getElementById('objectNameData');
        var objectName = objectElement ? objectElement.value : '<?php echo isset($user_info["city_name"]) ? addslashes(htmlspecialchars($user_info["city_name"])) : "Не указан"; ?>';
        
        // --- ТЕКУЩАЯ ДАТА ---
        var currentDate = new Date();
        var dateStr = currentDate.getFullYear() + '-' + 
                      String(currentDate.getMonth() + 1).padStart(2, '0') + '-' + 
                      String(currentDate.getDate()).padStart(2, '0') + ' ' +
                      String(currentDate.getHours()).padStart(2, '0') + ':' +
                      String(currentDate.getMinutes()).padStart(2, '0');
        
        // --- ПЕРВАЯ СТРОКА: объект и дата ---
        csv.push('Объект;Дата экспорта');
        csv.push('"' + objectName + '";"' + dateStr + '"');
        csv.push(''); // Пустая строка-разделитель
        
        // --- ЗАГОЛОВКИ ТАБЛИЦЫ ---
        var headers = [];
        table.querySelectorAll('thead th').forEach(function(th) {
            headers.push(th.textContent.trim());
        });
        csv.push(headers.join(';'));
        
        // --- ДАННЫЕ ТАБЛИЦЫ ---
        allRows.forEach(function(row) {
            if (row.style.display === 'none') return;
            var rowData = [];
            row.querySelectorAll('td').forEach(function(td) {
                var text = td.textContent.trim();
                text = text.replace(/\s+/g, ' ').trim();
                text = text.replace(/"/g, '""');
                rowData.push('"' + text + '"');
            });
            csv.push(rowData.join(';'));
        });
        
        // --- ФОРМИРУЕМ ИМЯ ФАЙЛА С ОБЪЕКТОМ ---
        // Очищаем имя объекта от спецсимволов для безопасного имени файла
        var cleanObjectName = objectName.replace(/[^a-zA-Zа-яА-Я0-9\-_\s]/g, '').trim();
        if (cleanObjectName === '') {
            cleanObjectName = 'unknown';
        }
        
        var dateForFilename = currentDate.getFullYear() + '-' + 
                              String(currentDate.getMonth() + 1).padStart(2, '0') + '-' + 
                              String(currentDate.getDate()).padStart(2, '0');
        
        var filename = 'modules_list_' + cleanObjectName + '_' + dateForFilename + '.csv';
        
        // Создаем и скачиваем файл
        var blob = new Blob(['\uFEFF' + csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(link.href);
    }

    // ============ КНОПКА СОХРАНЕНИЯ ============
    var panelBody = document.querySelector('.panel-body');
    if (panelBody) {
        var saveDiv = document.createElement('div');
        saveDiv.style.cssText = 'margin: 15px 0;';
        
        var button = document.createElement('button');
        button.className = 'btn btn-success';
        button.innerHTML = '<i class="glyphicon glyphicon-download-alt"></i> Сохранить CSV';
        button.onclick = saveTableAsCSV;
        
        saveDiv.appendChild(button);
        
        var h4 = panelBody.querySelector('h4');
        var infoDiv = panelBody.querySelector('.alert');
        if (infoDiv) {
            panelBody.insertBefore(saveDiv, infoDiv.nextSibling);
        } else {
            panelBody.insertBefore(saveDiv, h4);
        }
    }

})();
</script>