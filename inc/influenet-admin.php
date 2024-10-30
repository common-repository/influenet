<?php

if ('influenet-admin.php' == basename($_SERVER['SCRIPT_FILENAME']))
	die ( __('Please do not access this file directly. Thanks!', INFLUENET_PLUGIN_NAME_ ) );

if ( !is_admin() ) {
	die();
}
if ( !current_user_can( 'manage_options' ) ) :
	wp_die( __('You do not have sufficient permissions to access this page.', INFLUENET_PLUGIN_NAME_ ) );
endif;
if ( isset( $_POST['influenet-submit'] ) ) :
    if ( ! wp_verify_nonce( sanitize_text_field( $_POST['influenet-nonce'] ), 'influenet-nonce' ) ) die( 'Invalid Nonce.' );
	if ( function_exists( 'current_user_can' ) && current_user_can( 'edit_plugins' ) ) :
		update_option( 'influenet_api_key', sanitize_text_field( $_POST['influenet_api_key'] ) );
		update_option( 'influenet_author_id', sanitize_text_field( $_POST['influenet_author_id'] ) );
		echo '<div class="updated fade"><p>Options updated and saved.</p></div>';
else :
	wp_die( '<p>' . __('You do not have sufficient permissions.', INFLUENET_PLUGIN_NAME_) . '</p>' );
endif;
endif;

$influenet_author_id = get_option( 'influenet_author_id' );
?>
<div id="influenet-options" class="wrap">
<div id="influenet-options-icon" class="icon32"><br /></div>
<h2><?php _e('Influenet Options', INFLUENET_PLUGIN_NAME_); ?></h2>
<form class="influenet-form" name="influenet-options" method="post" action="">
<h3>General</h3>
<table class="form-table">
<tr valign="top">
	<th scope="row"><label for="influenet-api-key"><?php _e('API KEY', INFLUENET_PLUGIN_NAME_); ?></label></th>
	<td>
		<input type="text" class="code regular-text" name="influenet_api_key" id="influenet_api_key" value="<?php echo get_option( 'influenet_api_key' ); ?>" style="width: 80%;"/>
		<span class="description"><a href="https://influenet.com/projects" target="_blank"><?php _e('Get API KEY', INFLUENET_PLUGIN_NAME_); ?></a></span>
	</td>
</tr>
<tr valign="top">
	<th scope="row"><label for="influenet-api-key"><?php _e('Author default', INFLUENET_PLUGIN_NAME_); ?></label></th>
	<td>
	<select name="influenet_author_id" id="influenet_author_id"><?php
		$users = get_users( );
        
        if ( $users ) foreach ( $users as $id => $user ) {
        
            if($influenet_author_id == $user->ID)
            	echo "<option value=".$user->ID." selected>".$user->data->display_name."</option>\n";
        	else
        		echo "<option value=".$user->ID.">".$user->data->display_name."</option>\n";
            
        }
    ?>
    </select>
    </td>
</tr>

</table>
<?php wp_nonce_field( 'influenet-nonce', 'influenet-nonce', false ) ?> 
<p class="submit"><input id="influenet-submit" type="submit" name="influenet-submit" class="button-primary influenet-button" value="<?php _e('Save Changes', INFLUENET_PLUGIN_NAME_); ?>" /></p>
</form>
</div>
<div class="clear"></div>
