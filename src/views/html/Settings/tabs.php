<h2 class="nav-tab-wrapper">
	<?php foreach($vars['tabs'] as $tab): ?>
		<a class="nav-tab <?php echo ($vars['selected'] == $tab['id']) ? 'nav-tab-active' : ''; ?>" href="<?php echo $vars['data']->settings_url . '&amp;tab=' . $tab['id']; ?>"><?php echo $tab['name']; ?></a>
	<?php endforeach; ?>
</h2>