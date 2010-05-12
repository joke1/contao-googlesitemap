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
 * @version    $Id$ 
 */


/**
 * Disable core sitemap updater and add our own
 */
unset($GLOBALS['TL_DCA']['tl_page']['config']['onsubmit_callback'][array_search(array('tl_page', 'updateSitemap'), $GLOBALS['TL_DCA']['tl_page']['config']['onsubmit_callback'])]);
$GLOBALS['TL_DCA']['tl_page']['config']['onsubmit_callback'][] = array('tl_page_googlesitemap', 'updateSitemap');


/**
 * Palettes
 */
$GLOBALS['TL_DCA']['tl_page']['palettes']['regular'] = str_replace(array(',noSearch;', 'type;'), array(',noSearch;{sitemap_legend:hide},sitemap_lastmod,sitemap_ignore,sitemap_changefreq,sitemap_priority;', 'type,initialPage;'), $GLOBALS['TL_DCA']['tl_page']['palettes']['regular']);
$GLOBALS['TL_DCA']['tl_page']['subpalettes']['createSitemap'] .= ',sitemapHeader';


/**
 * Fields
 */
$GLOBALS['TL_DCA']['tl_page']['fields']['initialPage'] = array
(
	'label'					=> &$GLOBALS['TL_LANG']['tl_page']['initialPage'],
	'exclude'				=> true,
	'inputType'				=> 'checkbox',
	'eval'					=> array('tl_class'=>'w50'),
);

$GLOBALS['TL_DCA']['tl_page']['fields']['sitemap_lastmod'] = array
(
	'label'					=> &$GLOBALS['TL_LANG']['tl_page']['sitemap_lastmod'],
	'exclude'				=> true,
	'inputType'				=> 'text',
	'default'				=> time(),
	'eval'					=> array('maxlength'=>10, 'rgxp'=>'date', 'datepicker'=>$this->getDatePickerString(), 'tl_class'=>'w50 wizard')
);

$GLOBALS['TL_DCA']['tl_page']['fields']['sitemap_ignore'] = array
(
	'label'					=> &$GLOBALS['TL_LANG']['tl_page']['sitemap_ignore'],
	'exclude'				=> true,
	'inputType'				=> 'checkbox',
	'eval'					=> array('tl_class'=>'w50 m12')
);

$GLOBALS['TL_DCA']['tl_page']['fields']['sitemap_changefreq'] = array
(
	'label'					=> &$GLOBALS['TL_LANG']['tl_page']['sitemap_changefreq'],
	'exclude'				=> true,
	'inputType'				=> 'select',
	'options'				=> array('always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'),
	'reference'				=> &$GLOBALS['TL_LANG']['MSC']['sitemap_changefreq'],
	'eval'					=> array('includeBlankOption'=>true, 'tl_class'=>'w50'),
);

$GLOBALS['TL_DCA']['tl_page']['fields']['sitemap_priority'] = array
(
	'label'					=> &$GLOBALS['TL_LANG']['tl_page']['sitemap_priority'],
	'exclude'				=> true,
	'inputType'				=> 'select',
	'options'				=> array('0.0', '0.1', '0.2', '0.3', '0.4', '0.5', '0.6', '0.7', '0.8', '0.9', '1.0'),
	'eval'					=> array('includeBlankOption'=>true, 'tl_class'=>'w50'),
);

$GLOBALS['TL_DCA']['tl_page']['fields']['sitemapHeader'] = array
(
	'label'					=> &$GLOBALS['TL_LANG']['tl_page']['sitemapHeader'],
	'exclude'				=> true,
	'inputType'				=> 'textarea',
	'eval'					=> array('style'=>'height: 60px', 'preserveTags'=>true),
);


class tl_page_googlesitemap extends Backend
{
	/**
	 * Recursively add pages to a sitemap
	 * @param object
	 */
	public function updateSitemap(DataContainer $dc)
	{
		$this->import('GoogleSitemap');
		$this->GoogleSitemap->generateSitemap($dc->id);
	}
}

