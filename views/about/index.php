<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title">Информация о программе</h3>
    </div>
    <div class="panel-body">
        
        <table class="table table-striped">
            <tr>
                <th>Название:</th>
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
                <td><a href="<?php echo htmlspecialchars($developer['website_1']); ?>" target="_blank"><?php echo htmlspecialchars($developer['website_1']).'<br>'.htmlspecialchars($developer['website_2']); ?></a></td>
            </tr>
        </table>

        <h4>Текущая версия системы</h4>
        <p class="lead">Версия: <strong><?php echo htmlspecialchars($current_version); ?></strong></p>

       

        <h4>История версий</h4>
        <?php if (empty($version_history)): ?>
            <p>История версий пока не доступна.</p>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($version_history as $version_info): ?>
                    <div class="list-group-item">
                        <h5 class="list-group-item-heading">
                            Версия <?php echo htmlspecialchars($version_info['version']); ?>
                            <small class="text-muted"> (от <?php echo htmlspecialchars($version_info['date']); ?>)</small>
                        </h5>
                        <div class="list-group-item-text">
                            <pre><?php echo htmlspecialchars($version_info['changes']); ?></pre>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
		 <!-- Список модулей -->
        <h4>Установленные модули</h4>
 <h4>Установленные модули</h4>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>Модуль</th>
            <th>Версия</th>
            <th>Путь</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($modules_list as $module): ?>
        <tr>
            <td>
                <strong><?= htmlspecialchars($module['name_display']) ?></strong>
                <br>
                <small class="text-muted"><?= htmlspecialchars($module['name']) ?></small>
            </td>
            <td>
                <?php if ($module['version_defined']): ?>
                    <span class="label label-primary"><?= htmlspecialchars($module['version']) ?></span>
                <?php else: ?>
                    <span class="label label-default"><?= htmlspecialchars($module['version']) ?></span>
                    <br>
                    <?php if ($module['version'] !== 'Kohana'): ?>
                        <small class="text-muted">(добавьте константу <?= strtoupper($module['name']) ?>_VERSION в init.php)</small>
                    <?php endif; ?>
                <?php endif; ?>
            </td>
            <td><small><?= htmlspecialchars(str_replace(DOCROOT, '', $module['path'])) ?></small></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" class="text-center"><strong>Всего модулей: <?php echo count($modules_list); ?></strong></td>
        </tr>
    </tfoot>
</table>
    </div>
</div>

<!-- Добавим немного CSS для улучшения внешнего вида -->
<style>
.modules-table pre {
    margin: 0;
    padding: 5px;
    background: #f5f5f5;
    border: none;
}
.modules-table .label {
    font-size: 90%;
    padding: 3px 8px;
}
</style>