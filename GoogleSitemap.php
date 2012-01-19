<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2010 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  Andreas Schempp 2009-2010
 * @author     Andreas Schempp <andreas@schempp.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 * @version    $Id$
 */
 

class GoogleSitemap extends Backend
{

	/**
	 * Generate Google XML sitemaps
	 *
	 * Based on Automator::generateSitemap() TYPOlight 2.8.3
	 */
	public function generateSitemap($intId=0)
	{
		$blnPing = false;
		$time = time();
		$this->removeOldFeeds();
		
		// Only root pages should have sitemap names
		$this->Database->execute("UPDATE tl_page SET createSitemap='', sitemapName='' WHERE type!='root'");

		// Get a particular root page
		if ($intId > 0)
		{
			do
			{
				$objRoot = $this->Database->prepare("SELECT * FROM tl_page WHERE id=?")
										  ->limit(1)
										  ->execute($intId);

				if ($objRoot->numRows < 1)
				{
					break;
				}

				$intId = $objRoot->pid;
			}
			while ($objRoot->type != 'root' && $intId > 0);

			// Make sure the page is published
			if (!$objRoot->published || (strlen($objRoot->start) && $objRoot->start > $time) || (strlen($objRoot->stop) && $objRoot->stop < $time))
			{
				return;
			}

			// Check sitemap name
			if (!$objRoot->createSitemap || !$objRoot->sitemapName)
			{
				return;
			}

			$objRoot->reset();
		}

		// Get all published root pages
		else
		{
			$objRoot = $this->Database->execute("SELECT id, dns, sitemapName FROM tl_page WHERE type='root' AND createSitemap=1 AND sitemapName!='' AND (start='' OR start<$time) AND (stop='' OR stop>$time) AND published=1");
		}

		// Return if there are no pages
		if ($objRoot->numRows < 1)
		{
			return;
		}

		// Create XML file
		while($objRoot->next())
		{
			$objFile = new File($objRoot->sitemapName . '.xml');

			$objFile->write('');
			$objFile->append('<?xml version="1.0" encoding="UTF-8"?>');
			
			if (strlen($objRoot->sitemapHeader))
			{
				$objFile->append($objRoot->sitemapHeader);
			}
			
			$objFile->append('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">');

			$strDomain = '';

			// Support for extension "DomainLink" by Tristan Lins
			if (in_array('DomainLink', $this->Config->getActiveModules()))
			{
				$this->import('DomainLink');
				$strDomain = $this->DomainLink->absolutizeUrl('', $objRoot);
			}
			
			// Overwrite domain
			else if (strlen($objRoot->dns))
			{
				$strDomain = ($this->Environment->ssl ? 'https://' : 'http://') . $objRoot->dns . TL_PATH . '/';
			}
			
			if (version_compare(VERSION . '.' . BUILD, '2.7.2', '<'))
			{
				$arrPages = $this->getSearchablePages($objRoot->id, $strDomain);
			}
			else
			{
				$arrPages = $this->findSearchablePages($objRoot->id, $strDomain);
			}
			
			$arrFiles = $this->getDownloadFiles($objRoot->id, $strDomain);
			$arrPages = array_merge($arrPages, array_keys($arrFiles));
			$arrPageOptions = array_merge($arrFiles, $this->getPageOptions($objRoot->id, $strDomain));
						
			// HOOK: take additional pages
			if (array_key_exists('getSearchablePages', $GLOBALS['TL_HOOKS']) && is_array($GLOBALS['TL_HOOKS']['getSearchablePages']))
			{
				foreach ($GLOBALS['TL_HOOKS']['getSearchablePages'] as $callback)
				{
					$this->import($callback[0]);
					$arrPages = $this->$callback[0]->$callback[1]($arrPages, $objRoot->id);
				}
			}

			// Add pages
			foreach ($arrPages as $strUrl)
			{
				// Options for this page, if available
				$arrOptions = $arrPageOptions[$strUrl];
				
				$strUrl = rawurlencode($strUrl);
				$strUrl = str_replace(array('%2F', '%3F', '%3D', '%26', '%3A//'), array('/', '?', '=', '&', '://'), $strUrl);
				$strUrl = ampersand($strUrl, true);
				
				if (substr($strUrl, -2) == '//')
						$strUrl = substr($strUrl, 0, -1);

				if ($arrOptions['sitemap_ignore'])
					continue;

				if (!is_array($arrOptions) || (!strlen($arrOptions['sitemap_lastmod']) && !strlen($arrOptions['sitemap_changefreq']) && !strlen($arrOptions['sitemap_priority'])))
				{
					$objFile->append('  <url><loc>' . $strUrl . '</loc></url>');
				}
				else
				{
					$objFile->append("  <url>\n    <loc>" . $strUrl . "</loc>");
					
					if (strlen($arrOptions['sitemap_lastmod']))
					{
						$objFile->append("    <lastmod>" . date('c', $arrOptions['sitemap_lastmod']) . "</lastmod>");
					}
					
					if (strlen($arrOptions['sitemap_changefreq']))
					{
						$objFile->append("    <changefreq>" . $arrOptions['sitemap_changefreq'] . "</changefreq>");
					}
					
					if (strlen($arrOptions['sitemap_priority']))
					{
						$objFile->append("    <priority>" . $arrOptions['sitemap_priority'] . "</priority>");
					}
					
					$objFile->append("  </url>");
				}
			}

			$objFile->append('</urlset>');
			$objFile->close();

			// Add log entry
			$this->log('Generated sitemap "' . $objRoot->sitemapName . '.xml"', 'GoogleSitemap generateSitemap()', TL_CRON);
			
			if ($GLOBALS['TL_CONFIG']['pingGoogle'] && $GLOBALS['TL_CONFIG']['lastGooglePing'] < (time() - 3600))
			{
				// Fallback domain
				if (!strlen($strDomain))
				{
					$strDomain = $this->Environment->base;
				}
				
				$objRequest = new Request();
				$objRequest->send('http://www.google.com/webmasters/tools/ping?sitemap=' . urlencode($strDomain . $objRoot->sitemapName . '.xml'));
				
				$blnPing = true;
			}
		}
		
		if ($blnPing)
		{
			$this->import('Config');
			$this->Config->update('$GLOBALS[\'TL_CONFIG\'][\'lastGooglePing\']', time());
		}
	}
	
	
	/**
	 * Returns an array of all regular pages including their sitemap settings
	 *
	 * Based on Backend::getSearchablePages() from TYPOlight 2.6.5
	 */
	protected function getPageOptions($pid=0, $domain='', $blnHook=true)
	{
		$time = time();

		// Get published pages
		$objPages = $this->Database->prepare("SELECT * FROM tl_page WHERE pid=? AND (start='' OR start<?) AND (stop='' OR stop>?) AND published=1 ORDER BY sorting")
								   ->execute($pid, $time, $time);

		if ($objPages->numRows < 1)
		{
			return array();
		}

		// Fallback domain
		if (!strlen($domain))
		{
			$domain = $this->Environment->base;
		}

		$arrPages = array();

		// Recursively walk through all subpages
		while($objPages->next())
		{
			// Set domain
			if ($objPages->type == 'root')
			{
				if (strlen($objPages->dns))
				{
					$domain = ($this->Environment->ssl ? 'https://' : 'http://') . $objPages->dns . TL_PATH . '/';
				}
				else
				{
					$domain = $this->Environment->base;
				}
			}

			// Add regular pages
			elseif ($objPages->type == 'regular')
			{
				// Searchable and not protected
				if (!$objPages->noSearch && (!$objPages->protected || $GLOBALS['TL_CONFIG']['indexProtected']))
				{
					// Published
					if ($objPages->published && (!$objPages->start || $objPages->start < $time) && (!$objPages->stop || $objPages->stop > $time))
					{
						// Start page marker
						if ($objPages->initialPage)
						{
							$arrPages[$domain] = $objPages->row();
						}
						else
						{
							$arrPages[$domain . $this->generateFrontendUrl($objPages->row())] = $objPages->row();
						}

						// Get articles with teaser
						$objArticle = $this->Database->prepare("SELECT * FROM tl_article WHERE pid=? AND (start='' OR start<?) AND (stop='' OR stop>?) AND published=1 AND showTeaser=1 ORDER BY sorting")
													 ->execute($objPages->id, $time, $time);

						while ($objArticle->next())
						{
							$arrPages[$domain . $this->generateFrontendUrl($objPages->row(), '/articles/' . ((strlen($objArticle->alias) && !$GLOBALS['TL_CONFIG']['disableAlias']) ? $objArticle->alias : $objArticle->id))] = $objPages->row();
						}
					}
				}
			}

			// Get subpages
			if ((!$objPages->protected || $GLOBALS['TL_CONFIG']['indexProtected']))
			{
				$arrPages = array_merge($arrPages, $this->getPageOptions($objPages->id, $domain, false));
			}
		}
		
		// HOOK: get additional page options
		if ($blnHook && isset($GLOBALS['TL_HOOKS']['getSitemapOptions']) && is_array($GLOBALS['TL_HOOKS']['getSitemapOptions']))
		{
			foreach ($GLOBALS['TL_HOOKS']['getSitemapOptions'] as $callback)
			{
				$this->import($callback[0]);
				$arrPages = $this->$callback[0]->$callback[1]($arrPages, $domain);
			}
		}

		return $arrPages;
	}
	
	
	/**
	 * Article has been saved, ask to update page time stamp
	 */
	public function updateArticle()
	{
		// Multiple edit is not supported
		if ($this->Input->get('act') == 'editAll')
			$this->redirect($this->Environment->script.'?do=article');
			
		$objArticle = $this->Database->prepare("SELECT * FROM tl_article WHERE id=?")->limit(1)->execute($this->Input->get('id'));
		$objPage = $this->Database->prepare("SELECT * FROM tl_page WHERE id=?")->limit(1)->execute($objArticle->pid);
		
		// Update page
		if ($this->Input->post('FORM_SUBMIT') == 'tl_article_update' || $GLOBALS['TL_CONFIG']['noqueryPageLastmod'])
		{
			if (isset($_POST['applyPageUpdate']) || $GLOBALS['TL_CONFIG']['noqueryPageLastmod'])
			{
				$this->Database->prepare("UPDATE tl_page SET sitemap_lastmod=? WHERE id=?")->execute(time(), $objPage->id);
				$this->generateSitemap($objPage->id);
			}
			
			switch( $this->Input->get('button'))
			{
				case 'saveNclose':
					$this->redirect($this->Environment->script.'?do=article&table=tl_content&id=' . $this->Input->get('id'));
					break;
					
				case 'saveNcreate':
					$this->redirect($this->Environment->script.'?do=article&table=tl_article&act=create&mode=1&pid=' . $this->Input->get('id'));
					break;
					
				case 'saveNback':
					$this->redirect($this->Environment->script.'?do=article');
					break;
					
				case 'save':
				default:
					$this->redirect($this->Environment->script.'?do=article&table=tl_article&act=edit&id=' . $this->Input->get('id'));
			}
		}
		
		return '
<div id="tl_buttons"></div>

<h2 class="sub_headline">' . $GLOBALS['TL_LANG']['MSC']['pageUpdateArticle'][0] . '</h2>

<form action="' . $this->Environment->request . '" method="post">
<input type="hidden" name="FORM_SUBMIT" value="tl_article_update" />

<div class="tl_formbody_edit">
<div class="tl_tbox">' . sprintf($GLOBALS['TL_LANG']['MSC']['pageUpdateArticle'][1], $objArticle->title, $objPage->title) . '</div>
</div>

<div class="tl_formbody_submit">

<div class="tl_submit_container">
<input type="submit" name="cancelPageUpdate" id="cancelPageUpdate" class="tl_submit" alt="don\'t update page" accesskey="c" value="' . $GLOBALS['TL_LANG']['MSC']['cancelPageUpdate'] . '" />
<input type="submit" name="applyPageUpdate" id="applyPageUpdate" class="tl_submit" alt="update page" accesskey="s" value="' . $GLOBALS['TL_LANG']['MSC']['applyPageUpdate'] . '" />
</div>

</div>
</form>';
	}
	
	
	/**
	 * Content element has been saved, ask to update page time stamp
	 */
	public function updateContent()
	{
		// Multiple edit is not supported
		if ($this->Input->get('act') == 'editAll')
			$this->redirect($this->Environment->script.'?do=article&table=tl_content&id=' . $this->Input->get('id'));
			
		$objElement = $this->Database->prepare("SELECT * FROM tl_content WHERE id=?")->limit(1)->execute($this->Input->get('id'));
		$objArticle = $this->Database->prepare("SELECT * FROM tl_article WHERE id=?")->limit(1)->execute($objElement->pid);
		$objPage = $this->Database->prepare("SELECT * FROM tl_page WHERE id=?")->limit(1)->execute($objArticle->pid);
		
		// Update page
		if ($this->Input->post('FORM_SUBMIT') == 'tl_content_update' || $GLOBALS['TL_CONFIG']['noqueryPageLastmod'])
		{
			if (isset($_POST['applyPageUpdate']) || $GLOBALS['TL_CONFIG']['noqueryPageLastmod'])
			{
				$this->Database->prepare("UPDATE tl_page SET sitemap_lastmod=? WHERE id=?")->execute(time(), $objPage->id);
				$this->generateSitemap($objPage->id);
			}
			
			switch( $this->Input->get('button') )
			{
				case 'saveNclose':
					$this->redirect($this->Environment->script.'?do=article&table=tl_content&id=' . $objArticle->id);
					break;
					
				case 'saveNcreate':
					$this->redirect($this->Environment->script.'?do=article&table=tl_content&act=create&mode=1&pid=' . $this->Input->get('id') . '&id=' . $objArticle->id);
					break;
					
				case 'saveNback':
					$this->redirect($this->Environment->script.'?do=article');
					break;
					
				case 'save':
				default:
					$this->redirect($this->Environment->script.'?do=article&table=tl_content&act=edit&id=' . $this->Input->get('id'));
			}
		}
		
		return '
<div id="tl_buttons"></div>

<h2 class="sub_headline">' . $GLOBALS['TL_LANG']['MSC']['pageUpdateContent'][0] . '</h2>

<form action="' . $this->Environment->request . '" method="post">
<input type="hidden" name="FORM_SUBMIT" value="tl_content_update" />

<div class="tl_formbody_edit">
<div class="tl_tbox">' . sprintf($GLOBALS['TL_LANG']['MSC']['pageUpdateContent'][1], $objArticle->title, $objPage->title) . '</div>
</div>

<div class="tl_formbody_submit">

<div class="tl_submit_container">
<input type="submit" name="cancelPageUpdate" id="cancelPageUpdate" class="tl_submit" alt="don\'t update page" accesskey="c" value="' . $GLOBALS['TL_LANG']['MSC']['cancelPageUpdate'] . '" />
<input type="submit" name="applyPageUpdate" id="applyPageUpdate" class="tl_submit" alt="update page" accesskey="s" value="' . $GLOBALS['TL_LANG']['MSC']['applyPageUpdate'] . '" />
</div>

</div>
</form>';
	}
	
	
	/**
	 * Hook function for generateFrontendUrl()
	 */
	public function checkInitialPage($arrPage, $strParams, $strUrl)
	{
		if (!isset($arrPage['initialPage']))
		{
			$objPage = $this->Database->prepare("SELECT * FROM tl_page WHERE id=?")->execute($arrPage['id']);
			if ($objPage->numRows)
			{
				$arrPage['initialPage'] = $objPage->initialPage;
			}
		}
		
		if ($arrPage['initialPage'] && !strlen($strParams))
		{
			return strlen(TL_PATH) ? TL_PATH . '/' : '';
		}
		
		return $strUrl;
	}
	
	
	/**
	 * Hook function for replaceInsertTags()
	 */
	public function replacePageTags($strTag)
	{
		$arrTag = trimsplit('::', $strTag);
		
		switch( $arrTag[0] )
		{
			case 'last_page_update':
			
				global $objPage;
				
				// No date/time set
				if (!strlen($objPage->sitemap_lastmod) || $objPage->sitemap_lastmod == 0)
					return '';
				
				$strFormat = $GLOBALS['TL_CONFIG']['datimFormat'];
				
				if (strlen($arrTag[1]))
				{
					$strFormat = $arrTag[1];
				}
				
				return date($strFormat, $objPage->sitemap_lastmod);
				
				break;
		}
		
		return false;
	}
	
	
	public function getDownloadFiles($rootId, $strDomain)
	{
		$arrChildRecords = $this->getChildRecords($rootId, 'tl_page', true);
		
		if (!is_array($arrChildRecords) || !count($arrChildRecords))
			return array();
			
		// Fallback domain
		if (!strlen($strDomain))
		{
			$strDomain = $this->Environment->base;
		}
			
		$arrFiles = array();
		
		$objContents = $this->Database->execute("SELECT c.* FROM tl_content c LEFT OUTER JOIN tl_article a ON c.pid=a.id LEFT OUTER JOIN tl_page p ON a.pid=p.id WHERE (((c.type='download' OR c.type='downloads' OR c.type='pdfdownload') AND c.addToSitemap='1') OR c.type='downloadarchiv') AND p.id IN (" . implode(',', $arrChildRecords) . ")");
		
		while( $objContents->next() )
		{
			if ($objContents->type == 'download')
			{
				if (file_exists(TL_ROOT . '/' . $objContents->singleSRC) && !is_dir(TL_ROOT . '/' . $objContents->singleSRC))
				{
					$arrFiles[$strDomain . $objContents->singleSRC] = array('sitemap_lastmod' => filemtime(TL_ROOT . '/' . $objContents->singleSRC));
				}
			}
			elseif ($objContents->type == 'downloads')
			{
				$arrSRCs = deserialize($objContents->multiSRC);
				
				if (is_array($arrSRCs) && count($arrSRCs))
				{
					foreach ( $arrSRCs as $file )
					{
						if (file_exists(TL_ROOT . '/' . $file) && !is_dir(TL_ROOT . '/' . $file))
						{
							$arrFiles[$strDomain . $file] = array('sitemap_lastmod' => filemtime(TL_ROOT . '/' . $file));
						}
					}
				}
			}
			elseif ($objContents->type == 'downloadarchiv')
			{
				$arrArchiveIds = deserialize($objContents->downloadarchiv);
				
				if (is_array($arrArchiveIds) && count($arrArchiveIds))
				{
					$objFiles = $this->Database->execute("SELECT * FROM tl_downloadarchivitems WHERE addToSitemap='1' AND published='1' AND pid IN (" . implode(',', $arrArchiveIds) . ")");
					
					while( $objFiles->next() )
					{
						if (file_exists(TL_ROOT . '/' . $objFiles->singleSRC) && !is_dir(TL_ROOT . '/' . $objFiles->singleSRC))
						{
							$arrFiles[$strDomain . $objFiles->singleSRC] = array('sitemap_lastmod' => filemtime(TL_ROOT . '/' . $objFiles->singleSRC));
						}
					}
				}
			}
			elseif ($objContents->type == 'pdfdownload')
			{
				if (file_exists(TL_ROOT . '/' . $objContents->pdfFile) && !is_dir(TL_ROOT . '/' . $objContents->pdfFile))
				{
					$arrFiles[$strDomain . $objContents->pdfFile] = array('sitemap_lastmod' => filemtime(TL_ROOT . '/' . $objContents->singleSRC));
				}
			}
		}
		
		return $arrFiles;
	}
}

