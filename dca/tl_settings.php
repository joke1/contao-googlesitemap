<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * TYPOlight webCMS
 * Copyright (C) 2005-2009 Leo Feyer
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at http://www.gnu.org/licenses/.
 *
 * PHP version 5
 * @copyright  Andreas Schempp 2009
 * @author     Andreas Schempp <andreas@schempp.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 */
 

/**
 * Palettes
 */
$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] = preg_replace('@([,|;]{1}pNewLine)([,|;]{1})@', '$1,updatePageLastmod,noqueryPageLastmod,pingGoogle$2', $GLOBALS['TL_DCA']['tl_settings']['palettes']['default']);


/**
 * Fields
 */
$GLOBALS['TL_DCA']['tl_settings']['fields']['updatePageLastmod'] = array
(
	'label'			=> $GLOBALS['TL_LANG']['tl_settings']['updatePageLastmod'],
	'inputType'		=> 'checkbox',
	'eval'			=> array('tl_class'=>'w50'),
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['noqueryPageLastmod'] = array
(
	'label'			=> $GLOBALS['TL_LANG']['tl_settings']['noqueryPageLastmod'],
	'inputType'		=> 'checkbox',
	'eval'			=> array('tl_class'=>'w50'),
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['pingGoogle'] = array
(
	'label'			=> $GLOBALS['TL_LANG']['tl_settings']['pingGoogle'],
	'inputType'		=> 'checkbox',
	'eval'			=> array('tl_class'=>'clr'),
);

