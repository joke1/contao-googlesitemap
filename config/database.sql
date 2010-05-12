-- **********************************************************
-- *                                                        *
-- * IMPORTANT NOTE                                         *
-- *                                                        *
-- * Do not import this file manually but use the TYPOlight *
-- * install tool to create and maintain database tables!   *
-- *                                                        *
-- **********************************************************

-- 
-- Table `tl_page`
-- 

CREATE TABLE `tl_page` (
  `initialPage` char(1) NOT NULL default '',
  `sitemap_lastmod` varchar(11) NOT NULL default '',
  `sitemap_changefreq` varchar(10) NOT NULL default '',
  `sitemap_priority` varchar(3) NOT NULL default '',
  `sitemap_ignore` char(1) NOT NULL default '',
  `sitemapHeader` text NULL,
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

-- 
-- Table `tl_content`
-- 

CREATE TABLE `tl_content` (
  `addToSitemap` char(1) NOT NULL default '',
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

-- 
-- Table `tl_downloadarchivitems`
-- 

CREATE TABLE `tl_downloadarchivitems` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `addToSitemap` char(1) NOT NULL default '',
  PRIMARY KEY  (`id`),
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

