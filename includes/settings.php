<?php
/**
 * Settings
 *
 * @author nasimnet
 * @package ncp-api-get-listing
 * @since  1.0
 */

defined( 'ABSPATH' ) || exit;

$plugin_options = array(
	'nasim_plugin_options' => array(
		'type'    => 'tab',
		'title'   => 'title plugin',
		'options' => array(

			'plugin_help' => array(
				'type'  => 'html',
				'value' => '',
				'label' => 'راهنما',
				'html'  => '',
			),

		),
	),
);

