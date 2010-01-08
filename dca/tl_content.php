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
$GLOBALS['TL_DCA']['tl_content']['palettes']['download'] = str_replace('linkTitle', 'linkTitle,addToSitemap', $GLOBALS['TL_DCA']['tl_content']['palettes']['download']);
$GLOBALS['TL_DCA']['tl_content']['palettes']['downloads'] = str_replace('sortBy', 'sortBy,addToSitemap', $GLOBALS['TL_DCA']['tl_content']['palettes']['downloads']);


/**
 * Fields
 */
$GLOBALS['TL_DCA']['tl_content']['fields']['addToSitemap'] = array
(
	'label'			=> &$GLOBALS['TL_LANG']['tl_content']['addToSitemap'],
	'exclude'		=> true,
	'inputType'		=> 'checkbox',
	'eval'			=> array('tl_class'=>'w50 m12'),
);


/**
 * Hooks
 */
if ($GLOBALS['TL_CONFIG']['updatePageLastmod'])
{
	$GLOBALS['TL_DCA']['tl_content']['config']['onsubmit_callback'][] = array('tl_content_googlesitemap', 'onsubmitContent');
	$GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = array('tl_content_googlesitemap', 'onloadContent');
}


class tl_content_googlesitemap extends Backend
{
	
	/**
	 * Set the session data if a content element is saved.
	 * 
	 * @access public
	 * @param mixed $dc
	 * @return void
	 */
	function onsubmitContent($dc)
	{
		// Page has been reloaded due to input change (DCA submitOnChange)
		if ($this->Input->post('SUBMIT_TYPE') == 'auto' || $this->Input->get('mode') == '2')
			return;
		
		if (isset($_POST['saveNclose']))
		{
			$strButton = 'saveNclose';
		}
		elseif (isset($_POST['saveNcreate']))
		{
			$strButton = 'saveNcreate';
		}
		elseif (isset($_POST['saveNback']))
		{
			$strButton = 'saveNback';
		}
		else
		{
			$strButton = 'close';
		}
		
		$strUrl = $this->addToUrl('key=updateContent&button=' . $strButton);
		
		$_SESSION['GOOGLESITEMAP'] = $strUrl;
	}
	
	
	/**
	 * Check if session data is set a redirect to update key.
	 * 
	 * @access public
	 * @return void
	 */
	function onloadContent()
	{
		if (strlen($_SESSION['GOOGLESITEMAP']))
		{
			$strUrl = $_SESSION['GOOGLESITEMAP'];
			unset($_SESSION['GOOGLESITEMAP']);
			
			$this->redirect($strUrl);
		}
	}
}

