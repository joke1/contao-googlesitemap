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


if ($GLOBALS['TL_CONFIG']['updatePageLastmod'])
{
	$GLOBALS['TL_DCA']['tl_article']['config']['onsubmit_callback'][] = array('tl_article_googlesitemap', 'onsubmitArticle');
	$GLOBALS['TL_DCA']['tl_article']['config']['onload_callback'][] = array('tl_article_googlesitemap', 'onloadArticle');
}


class tl_article_googlesitemap extends Backend
{
	function onsubmitArticle($dc)
	{
		// Page has been reloaded due to input change (DCA submitOnChange)
		if ($this->Input->post('SUBMIT_TYPE') == 'auto' || $this->Input->get('s2e') == '1')
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
		
		$strUrl = $this->addToUrl('key=updateArticle&button=' . $strButton);
		
		$_SESSION['GOOGLESITEMAP'] = $strUrl;
	}
	
	
	/**
	 * Check if session data is set a redirect to update key.
	 * 
	 * @access public
	 * @return void
	 */
	function onloadArticle()
	{
		if (strlen($_SESSION['GOOGLESITEMAP']))
		{
			$strUrl = $_SESSION['GOOGLESITEMAP'];
			unset($_SESSION['GOOGLESITEMAP']);
			
			$this->redirect($strUrl);
		}
	}
}

