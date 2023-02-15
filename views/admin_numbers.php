<div class="wrap admin_tool_basic_page" id="<?php echo esc_attr($action); ?>_page">
	<h1 class="wp-heading-inline"><?php echo esc_html($title); ?></h1>

    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
    <div id="ajax_message" class="notice notice-success"><p>All updated.</p></div>
    <?php else: ?>
    <div id="ajax_message"></div>
    <?php endif; ?>
    
    <?php $table->display(); ?>
</div> 

