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
			<tr>
                <th>Версия модуля About</th>
                <td><?php echo ABOUT_VERSION; ?></td>
            </tr>
			
        </table>

        
 
 <h4>Установленные модули</h4>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>Модуль</th>
            <th>Версия</th>
            <th>Обновление</th>
            <th>Путь</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($modules_list as $module): ?>
        <tr>
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
                    <!-- можно добавить кнопку обновления -->
                    <!-- <a href="update?module=<?= $module['name'] ?>" class="btn btn-xs btn-primary">Обновить</a> -->
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
        <tr><td colspan="4" class="text-center"><strong>Всего модулей: <?php echo count($modules_list); ?></strong></td></tr>
    </tfoot>
</table>