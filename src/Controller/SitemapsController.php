<?php
/**
 * Basic Sitemaps Controller to display a standard Sitemap in HTML and XML.
 */
namespace Sitemap\Controller;

use Cake\Core\Configure;
use Sitemap\Controller\AppController;
use Sitemap\Lib\Iterators\PagesIterator;

/**
 * \Sitemap\Controller\SitemapsController
 */
class SitemapsController extends AppController {
	/**
	 * Index page for the sitemap.
	 *
	 * @return void
	 */
	public function index() {
		$tablesToList = [];
		$data = [];

		if (Configure::check('Sitemap.tables')) {
			$tablesToList = Configure::read('Sitemap.tables');
		}

		$pagesToList = $this->listPages();
		if ($pagesToList) {
			$data['Pages'] = $pagesToList;
		}

		foreach ($tablesToList as $table) {
			$tableInstance = $this->loadModel($table);
			$data[$table] = $tableInstance->find('forSitemap');
		}

		$this->set('data', $data);
		$this->set('_serialize', false);
	}

	public function listPages()
	{
		if (!Configure::read('Sitemap.pages.enable', true)) {
			return null;
		}

		$pagesToList = [];
		$pages = new PagesIterator(APP . 'Template' . DS . 'Pages' . DS, []);
		$config = array_merge([
				'_changefreq' => 'daily',
				'_priority' => '0.9',
			], Configure::read('Sitemap.pages.config'));

		foreach($pages as $page) {
			$listPage = (object) $config;
			$listPage->_loc = $page['url'];
			$listPage->_lastmod = $page['modified'];
			$pagesToList[] = $listPage;
		}

		return $pagesToList;
	}
}
