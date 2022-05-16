<?php
/*
PicoWiki Checkboxes
This plugin converts '[ ]' and '[x]' to checkboxes

*/
Class PluginCheckboxes
{
	static $version = '1.0.0';

	static function run( $PicoWiki ){
		$PicoWiki->event('view_after', NULL, function($PicoWiki) {
            $PicoWiki->html = str_replace('[ ]', '<input type="checkbox" disabled>', $PicoWiki->html);
            $PicoWiki->html = str_replace('[x]', '<input type="checkbox" checked disabled>', $PicoWiki->html);
	        return $PicoWiki;
		});
	}
}
