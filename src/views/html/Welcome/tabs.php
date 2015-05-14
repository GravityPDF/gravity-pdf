<h2 class="nav-tab-wrapper">
	<a class="nav-tab <?php echo $vars['selected'] == 'gfpdf-getting-started' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'gfpdf-getting-started' ), 'index.php' ) ) ); ?>">
		<?php _e( 'Getting Started', 'pdfextended' ); ?>
	</a>
	<a class="nav-tab <?php echo $vars['selected'] == 'gfpdf-update' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'gfpdf-update' ), 'index.php' ) ) ); ?>">
		<?php _e( "What's New", 'pdfextended' ); ?>
	</a>
</h2>