<?php
/**
 * Emoji ッ Plugin
 * This plugin auto-detects smiley shortcuts and replace them with emojis
 * EMOJI Source www.emoji-cheat-sheet.com
 * Author: Igor Gaffling
 */

class PluginEmoji {
        /* ADD WHAT YOU LIKE - https://gist.github.com/hkan/264423ab0ee720efb55e05a0f5f90887 */
  static $version = '1.0.0';
  static function load( $PicoWiki ) {
    $PicoWiki->event('view_after', NULL, function($html) use ($PicoWiki) {
      // doc meta data can be used to skip emoji plugin.
      if (isset($PicoWiki->meta['no-emoji']) && $PicoWiki->meta['no-emoji']) return $html;

      $search_replace = array(
        '(y)'        => '👍',
        '(n)'        => '👎',
        ':+1:'       => '👍',
        ':-1:'       => '👎',
        ':wink:'     => '👋',
        ':tada:'     => '🎉',
        ':cat:'      => '😺',
        ':sparkles:' => '✨',
        ':camel:'    => '🐫',
        ':rocket:'   => '🚀',
        ':metal:'    => '🤘',
        ':star:'     => '⭐',
	':tent:'     => '⛺',
	':joy:'      => '🤣',
	':x:'        => '❎',
	':v:'        => '✅',
	':check_mark:' => '&#x2714;',
	':cross_mark:' => '&#x274c;',
        '<3'         => '❤', /* ❤️ 💗 */
        ';-)'        => '😉',
        ':-)'        => '🙂',
        ':-|'        => '😐',
        ':-('        => '🙁',
        ':-D'        => '😀',
        ':-P'        => '😛',
        ':-p'        => '😜',
        ':-*'        => '😘',
        ':-o'        => '😮',
        ':-O'        => '😲',
        ':-0'        => '😲',
        '^_^'        => '😁',
        '>_<'        => '😆',
        '3:-)'       => '😈',
        '}:-)'       => '😈',
        '>:-)'       => '😈',
        ":')"        => '😂',
        ":'-)"       => '😂',
        ":'("        => '😢',
        ":'-("       => '😢',
        '0:-)'       => '😇',
        'O:-)'       => '😇',
      );
      return str_replace(array_keys($search_replace), $search_replace, $html);
    });
  }
}
