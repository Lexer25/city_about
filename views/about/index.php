<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title">Информация о программе</h3>
    </div>
    <div class="panel-body" style="padding: 10px 15px;">
        
        <table class="table table-striped table-condensed" style="margin-bottom: 0;">
            <tr>
                <th style="width: 120px;">Название:</th>
                <td><?php echo htmlspecialchars($developer['name']); ?></td>
            </tr>
            <tr>
                <th>Компания:</th>
                <td><?php echo htmlspecialchars($developer['company']); ?></td>
            </tr>
            <tr>
                <th>Email:</th>
                <td><a href="mailto:<?php echo htmlspecialchars($developer['email']); ?>"><?php echo htmlspecialchars($developer['email']); ?></a></td>
            </tr>
            <tr>
                <th>Веб-сайт:</th>
                <td>
                    <a href="<?php echo htmlspecialchars($developer['website_1']); ?>" target="_blank"><?php echo htmlspecialchars($developer['website_1']); ?></a>
                    <?php if (!empty($developer['website_2'])): ?>
                        <br><a href="<?php echo htmlspecialchars($developer['website_2']); ?>" target="_blank"><?php echo htmlspecialchars($developer['website_2']); ?></a>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

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
                    <th>Версия</th>
                    <th>Обновление</th>
                    <th>Путь</th>
                </tr>
            </thead>
            <tbody>
                <?php $counter = 1; ?>
                <?php foreach ($modules_list as $module): ?>
                <tr data-version="<?= htmlspecialchars($module['version']) ?>">
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
                    <td>
                        <?php $status = $module['update_status']; ?>
                        <?php if ($status['error']): ?>
                            <span class="label label-warning">⚠️ <?= $status['message'] ?></span>
                        <?php elseif ($status['has_update']): ?>
                            <span class="label label-danger">🆙 <?= $status['message'] ?></span>
                        <?php elseif ($status['latest_version'] !== null): ?>
                            <span class="label label-success">✅ Актуально</span>
                        <?php else: ?>
                            <span class="label label-default">⚙️ Не настроен</span>
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

<script>
    (function() {
        const checkbox = document.getElementById('hideKohana');
        const table = document.getElementById('modulesTable');
        const tbody = table.querySelector('tbody');
        const rows = tbody ? Array.from(tbody.querySelectorAll('tr')) : [];
        const countCell = document.querySelector('#modulesCountRow td');
        const totalModules = <?php echo count($modules_list); ?>;

        function updateVisibleCount() {
            if (!countCell) return;
            const visibleRows = rows.filter(row => {
                // проверяем, видима ли строка (не имеет style.display = 'none')
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
                // проверяем, является ли модуль Kohana (по атрибуту data-version)
                const version = row.getAttribute('data-version');
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
            // принудительно применить фильтр при загрузке (если checkbox по какой-то причине включён)
            filterRows();
        }
    })();
</script>