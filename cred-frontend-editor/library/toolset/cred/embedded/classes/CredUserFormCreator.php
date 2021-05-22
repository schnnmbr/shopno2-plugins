<?php

define( "PAD", "\t" );
define( "NL", "\r\n" );

if ( ! class_exists( "CredUserFormCreator", false ) ) {

	/**
	 * Description of CredUserFormCreator
	 *
	 * usage: CredUserFormCreator::cred_create_form('mycredform_name_post', 'new', 'subscriber');
	 *        CredUserFormCreator::cred_create_form('mycredform_name_page', 'edit', 'subscriber');
	 * to include:
	 * if (defined( 'CRED_CLASSES_PATH' )) {
	 * require_once CRED_CLASSES_PATH."/CredUserFormCreator.php";
	 * CredUserFormCreator::cred_create_form('test', 'new', 'subscriber');
	 * }
	 *
	 * @author Franko
	 */
	class CredUserFormCreator {

		/**
		 *
		 * stdClass Object
		 * (
		 * [form] => Array
		 * (
		 * [hide_comments] => 0
		 * [has_media_button] => 0
		 * [has_toolset_buttons] => 0
		 * [has_media_manager] => 0
		 * [action_message] =>
		 * [type] => new
		 * [action] => form
		 * [redirect_delay] => 0
		 * )
		 *
		 * [post] => Array
		 * (
		 * [post_type] => post
		 * [post_status] => publish
		 * )
		 *
		 * )
		 *
		 *
		 * stdClass Object
		 * (
		 * [notifications] => Array
		 * (
		 * )
		 *
		 * [enable] => 1
		 * )
		 *
		 * @param type $name
		 * @param type $mode [new|edit]
		 * @param type $post_type
		 */
		public static $_created = array();

		/**
		 * @param $name
		 * @param $mode
		 * @param array $user_type
		 * @param bool $autogenerated_username
		 * @param bool $autogenerated_password
		 * @param bool $autogenerated_nickname
		 * @param string $post_type
		 *
		 * @return mixed
		 */
		public static function cred_create_form( $name, $mode, $user_type = array( 'subscriber' ), $autogenerated_username = true, $autogenerated_password = true, $autogenerated_nickname = true, $post_type = 'user' ) {
			$name = sanitize_text_field( $name );
			if ( empty( self::$_created ) && ! in_array( $name, self::$_created ) ) {
				self::$_created[] = $name;

				$form = get_page_by_title( wp_specialchars_decode( $name ), OBJECT, CRED_USER_FORMS_CUSTOM_POST_NAME );
				if ( ! empty( $form ) ) {
					//TODO: give message? Toolset Form already exists
					return;
				}

				$model = CRED_Loader::get( 'MODEL/UserForms' );
				$fields_model = CRED_Loader::get( 'MODEL/UserFields' );
				$autogenerated_array = array(
					'username' => $autogenerated_username,
					'nickname' => $autogenerated_nickname,
					'password' => $autogenerated_password,
				);
				$fields_all = $fields_model->getFields( $autogenerated_array );

				if ( isset( $fields_all['extra_fields']['_featured_image'] ) ) {
					unset( $fields_all['extra_fields']['_featured_image'] );
				}

				$form_id = 1;
				$form_name = $name;
				$includeWPML = false;
				$includeRecaptcha = false;
				$counter = 0;

				if ( ! $includeRecaptcha ) {
					unset( $fields_all['extra_fields']['recaptcha'] );
				}


				$form = new stdClass;
				$form->ID = '';
				$form->post_title = $name;
				$form->post_content = '';
				global $current_user;
				$form->post_author = $current_user->ID;
				$form->post_status = 'private';
				$form->comment_status = 'closed';
				$form->ping_status = 'closed';
				$form->post_type = CRED_USER_FORMS_CUSTOM_POST_NAME;
				$form->post_name = CRED_USER_FORMS_CUSTOM_POST_NAME;

				$fields = array();
				$fields['form_settings'] = new stdClass;
				$fields['form_settings']->form_type = $mode;
				$fields['form_settings']->form_action = 'form';
				$fields['form_settings']->form_action_page = '';
				$fields['form_settings']->redirect_delay = 0;
				$fields['form_settings']->message = '';
				$fields['form_settings']->hide_comments = 1;
				$fields['form_settings']->include_captcha_scaffold = 0;
				$fields['form_settings']->include_wpml_scaffold = 0;
				$fields['form_settings']->has_media_button = 0;
				$fields['form_settings']->has_toolset_buttons = 0;
				$fields['form_settings']->has_media_manager = 0;
				$fields['form_settings']->cred_theme_css = 'minimal';

				$fields['form_settings']->post_type = $post_type;
				$fields['form_settings']->post_status = 'publish';

				$js_user_type = json_encode( $user_type );
				$fields['form_settings']->user_role = $js_user_type;

				$fields['form_settings']->autogenerate_username_scaffold = $autogenerated_username;
				$fields['form_settings']->autogenerate_password_scaffold = $autogenerated_password;

				$fields['wizard'] = -1;

				$fields['extra'] = new stdClass;
				$fields['extra']->css = '';
				$fields['extra']->js = '';
				$fields['extra']->scaffold = '';

				$fields['extra']->messages = $model->getDefaultMessages();

				$res = $model->saveForm( $form, $fields );

				return $res;
			}
		}

	}

}