<?php
/**
 * iPHP - i PHP Framework
 * Copyright (c) 2012 iiiphp.com. All rights reserved.
 *
 * @author coolmoo <iiiphp@qq.com>
 * @website http://www.iiiphp.com
 * @license http://www.iiiphp.com/license
 * @version 2.0.0
 */
defined('iPHP') OR exit('What are you doing?');
defined('iPHP_LIB') OR exit('iPHP vendor need define iPHP_LIB');
iPHP::import(iPHP_LIB . '/Parsedown.php');

function Markdown($content) {
	$Parsedown = new Parsedown();
	$content = str_replace(array(
		'#--' . iPHP_APP . '.Markdown--#',
		'#--' . iPHP_APP . '.PageBreak--#',
	), array('', '@--' . iPHP_APP . '.PageBreak--@'), $content);
	$content = $Parsedown->text($content);
	$content = str_replace('@--' . iPHP_APP . '.PageBreak--@', '#--' . iPHP_APP . '.PageBreak--#', $content);
	return $content;
}
