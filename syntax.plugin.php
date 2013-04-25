<?php

/**
 * Syntax Highlighter
 * Version: 1.0
 * Author: Benjamin Hutchins <http://www.xvolter.com>
 * Copyright: Copyright (C) 2008, Benjamin Hutchins
 * License: MIT License
 */

class Syntax extends Plugin {

	


	/**
	 * Add update beacon support
	 **/
	public function action_update_check()
	{
	 	Update::add( 'Syntax Highlighter', 'CD64FF02-B078-11DD-9AA2-3F8056D89593', $this->info->version );
	}


	/**
	 * When Plugin is activated insert default options
	 */
	public function action_plugin_activation( $file )
	{
		if(Plugins::id_from_file($file) == Plugins::id_from_file(__FILE__)) {
			Options::set( 'syntax__default_lang', 'javascript' );
		}
	}


	/**
	 * Show configure option
	 */
	public function filter_plugin_config( $actions, $plugin_id ) 
	{
		if ( $plugin_id == $this->plugin_id() ) {
			$actions[]= _t('Configure');
		}
		return $actions;
	}


	/**
	 * Handle special plugin requests
	 */
	public function action_plugin_ui( $plugin_id, $action ) 
	{
		if ( $plugin_id == $this->plugin_id() ) {
			switch ( $action ) {
				case _t('Configure'):
					$ui= new FormUI( strtolower( get_class( $this ) ) );
					$ui->append( 'text', 'default_lang', 'syntax__default_lang', _t('Default language:') );
					$ui->append( 'submit', 'save', _t('Save') );
					$ui->out();
				break;
			}
		}
	}


	/**
	 * On request modify <code> tags.
	 * We do this here because otherwise you'd get a mess
	 * of HTML in your database.
	 */
	public function filter_post_content( $output, $post )
	{
		if ( Controller::get_action() == 'admin' || $output == "" )
			return $output;

		$output = preg_replace_callback(
			"/<code([^>]*)>(.*)<\/code>/siU",
			array($this, "syntax_highlight"),
			$output);

		return $output;
	}


	/**
	 * Include stylesheet
	 */
	public function action_template_header()
	{
		echo '<link rel="stylesheet" type="text/css" href="' . $this->get_url() . '/syntax.css">';
	}


	/**
	 * Process a <code> tag
	 */
	private function syntax_highlight( $match )
	{
		$code = $this->trim( $match[2] );

		// process the match as somewhat XML
		$element = new SimpleXMLElement( "<code{$match[1]}></code>" );
		$attributes = $element->attributes();

		// Make sure Syntax isn't turned off
		if ( isset($attributes['syntax']) && mb_strtolower($attributes['syntax']) == 'off' )
			return $match[0];

		// Get language
		$lang = isset( $attributes['lang'] ) ? (string)$attributes['lang'] : false;
		$lineNumbersEnabled = isset( $attributes['linenumbers'] ) ? $attributes['linenumbers'] == "true" : false;

		if ( ! $lang ) $lang = Options::get( 'syntax__default_lang' );
		$lang = preg_replace('#[^a-zA-Z0-9\-_]#', '', $lang);

		// Turn off error reporting
		$er = error_reporting();
		error_reporting(0);

		// We need to include this here...
		// YOU CAN REMOVE THIS IF YOU HAVE GeSHi in your user/classes
		if ( !class_exists('GeSHi') )
			require_once ( dirname(__FILE__) . '/geshi.php' );

		// Start GeSHi
		$geshi = new GeSHi($code, $lang);

		// we enable classes and use a stylehseet,
		// saves output time
		$geshi->enable_classes( true );
		$geshi->enable_keyword_links( false );
		if ($lineNumbersEnabled) {
			$geshi->enable_line_numbers( GESHI_NORMAL_LINE_NUMBERS);

			$highlightLines = isset( $attributes['highlightlines'] ) ? $attributes['highlightlines'] : false;
			if ($highlightLines) {
				$geshi->highlight_lines_extra(explode(",", $highlightLines));
			}
		}

		// remove PRE default style
		$geshi->set_overall_style('', false);

		$parsed = $geshi->parse_code();
		error_reporting($er);

		// create output
  	$output = $parsed;

		return "<div class=\"syntax_highlight\">$output</div>";
	}


	/**
	 * Trim source code
	 */
	private function trim( $code )
	{
		$code = preg_replace("/^\s*\n/siU", "", $code);
		$code = rtrim($code);

		return $code;
	}


	/**
	 * Creates sidebar of lines
	 */
	private function line_numbers( $code, &$output, $count = null, $start = 0 )
	{
		if ( $count === null ) $count= count( explode("\n", $code) );

		for ($i = 0; $i < $count; $i++)
			$output .=  ( $i + ( $start ? 1 : 0) ) . "\n";
	}
}

?>
