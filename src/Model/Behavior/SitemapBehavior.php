<?php
/**
 * Behavior for loading Sitemap records
 */
namespace Sitemap\Model\Behavior;

use Cake\Datasource\ResultSetInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Routing\Router;

/**
 * \Sitemap\Model\Behavior\SitemapBehavior
 */
class SitemapBehavior extends Behavior {
	/**
	 * Default configuration.
	 *
	 * @var array
	 */
	// @codingStandardsIgnoreStart
	protected $_defaultConfig = [
	// @codingStandardsIgnoreEnd
		'cacheConfigKey' => 'default',
		'lastmod' => 'modified',
		'changefreq' => 'daily',
		'priority' => '0.9',
		'conditions' => [],
		'order' => [],
		'fields' => [],
		'finders' => [],
		'urlParamField' => null,
		'urlForEntity' => [],
		'implementedMethods' => [
			'getUrl' => 'returnUrlForEntity',
		],
		'implementedFinders' => [
			'forSitemap' => 'findSitemapRecords',
		],
	];

	/**
	 * Constructor
	 *
	 * Merges config with the default and store in the config property
	 *
	 * @param \Cake\ORM\Table $table The table this behavior is attached to.
	 * @param array $config The config for this behavior.
	 */
	public function __construct(Table $table, array $config = []) {
		parent::__construct($table, $config);
	}

	/**
	 * Constructor hook method.
	 *
	 * Implement this method to avoid having to overwrite
	 * the constructor and call parent.
	 *
	 * @param array $config The configuration settings provided to this behavior.
	 * @return void
	 */
	public function initialize(array $config) {
		parent::initialize($config);
		
		if ($this->getConfig('urlParamField') === null) {
			$this->setConfig('urlParamField', $this->getTable()->getPrimaryKey());
		}
	}

	/**
	 * Return the URL for the primary view action for an Entity.
	 *
	 * @param \Cake\ORM\Entity $entity Entity object passed in to return the url for.
	 * @return string Returns the URL string.
	 */
	public function returnUrlForEntity(Entity $entity) {
		$url = array_merge([
			'plugin' => null,
			'prefix' => null,
			'controller' => $this->getTable()->getAlias(),
			'action' => 'view',
			$entity->{$this->getConfig('urlParamField')},
		], $this->getConfig('urlForEntity'));

		return Router::url($url, true);
	}

	/**
	 * Find the Sitemap Records for a Table.
	 *
	 * @param \Cake\ORM\Query $query The Query being modified.
	 * @param array $options The array of options for the find.
	 * @return \Cake\ORM\Query Returns the modified Query object.
	 */
	public function findSitemapRecords(Query $query, array $options) {
		$query
			->where($this->getConfig('conditions'))
			->cache("sitemap_{$query->getRepository()->getAlias()}", $this->getConfig('cacheConfigKey'))
			->order($this->getConfig('order'))
			->formatResults(function ($results) {
				return $this->mapResults($results);
			});

		if (!empty($this->getConfig('fields'))) {
			$query->select($this->getConfig('fields'));
		}

		if (!empty($this->getConfig('finders'))) {
			foreach ($this->getConfig('finders') as $finder) {
				$query->find($finder);
			}
		}

		return $query;
	}

	/**
	 * Format Results method to take the ResultSetInterface and map it to add
	 * calculated fields for the Sitemap.
	 *
	 * @param \Cake\Datasource\ResultSetInterface|Cake\Collection\Iterator\ReplaceIterator $results The results of a Query
	 * operation.
	 * @return \Cake\Collection\CollectionInterface Returns the modified collection
	 * of Results.
	 */
	public function mapResults($results) {
		return $results->map(function ($entity) {
			return $this->mapEntity($entity);
		});
	}

	/**
	 * Modify an entity with new `_` fields for the Sitemap display.
	 *
	 * @param \Cake\ORM\Entity $entity The entity being modified.
	 * @return \Cake\ORM\Entity Returns the modified entity.
	 */
	public function mapEntity(Entity $entity) {
		$entity['_loc'] = $this->getTable()->getUrl($entity);
		$entity['_lastmod'] = $entity->{$this->getConfig('lastmod')};
		$entity['_changefreq'] = $this->getConfig('changefreq');
		$entity['_priority'] = $this->getConfig('priority');

		return $entity;
	}
}
