<div class="wrap admin_tool_basic_page" id="<?php echo esc_attr($action); ?>_page">
	<h1 class="wp-heading-inline"><?php echo esc_html($title); ?></h1>

        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
        <div id="ajax_message" class="notice notice-success"><p>All updated.</p></div>
        <?php else: ?>
        <div id="ajax_message"></div>
        <?php endif; ?>
        
        <p>Choose your settings below. Then create an access key on the <a href="edit.php?post_type=numbersq_key">NumbersQ Key page</a>. Once you have copied the key, you can then use it on <a href="https://www.numbersq.com/number/add" target="_blank">NumbersQ when you add a number.</a></p>

        <form action="admin-ajax.php" method="post" class="ajax_form">
            <?php wp_nonce_field( $action ); ?>
                <input name="action" type="hidden" value="<?php echo esc_attr($action); ?>" />

                <table class="form-table">
                    <tbody>
                        <tr class="form-field">
                            <th scope="row"><label>Timezone</label></th>
                            <td>
                                <select name="timezone_string">
                                    <option value="use_wordpress" <?php echo ($timezone_string == 'use_wordpress') ? 'selected' : ''; ?>>Use WordPress Setting</option>
                                    <?php 
                                        // Remove the default empty value selection value from the list.
                                        // Have the drop down either select the '' value if "use_wordpress"
                                        // is passed in or use the $timezone_string that was previously selected.
                                        echo preg_replace('/<option selected="selected" value="">.*<\/option>/', '', wp_timezone_choice(($timezone_string == 'use_wordpress') ? '' : $timezone_string)); 
                                    ?>
                                </select>
                                <p class="description"></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
			                
                <p class="submit">
                        <input type="submit" class="button button-primary" value="Submit">
			<span class="spinner"></span>
                </p>
        </form> 
</div> 
