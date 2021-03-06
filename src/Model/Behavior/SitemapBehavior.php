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
	}

	/**
	 * Return the URL for the primary view action for an Entity.
	 *
	 * @param \Cake\ORM\Entity $entity Entity object passed in to return the url for.
	 * @return string Returns the URL string.
	 */
	public function returnUrlForEntity(Entity $entity) {
		return Router::url(
			[
				'plugin' => null,
				'prefix' => null,
				'controller' => $this->_table->alias(),
				'action' => 'view',
				$entity->{$this->_table->primaryKey()},
			],
			true
		);
	}

	/**
	 * Find the Sitemap Records for a Table.
	 *
	 * @param \Cake\ORM\Query $query The Query being modified.
	 * @param array $options The array of options for the find.
	 * @return \Cake\ORM\Query Returns the modified Query object.
	 */
	public function findSitemapRecords(Query $query, array $options) {
		$query = $query
			->where($this->_config['conditions'])
			->cache("sitemap_{$query->repository()->alias()}", $this->_config['cacheConfigKey'])
			->order($this->_config['order'])
			->formatResults(function ($results) {
				return $this->mapResults($results);
			});

		if (!empty($this->_config['fields'])) {
			$query = $query->select($this->_config['fields']);
		}

		return $query;
	}

	/**
	 * Format Results method to take the ResultSetInterface and map it to add
	 * calculated fields for the Sitemap.
	 *
	 * @param \Cake\Datasource\ResultSetInterface $results The results of a Query
	 * operation.
	 * @return \Cake\Collection\CollectionInterface Returns the modified collection
	 * of Results.
	 */
	public function mapResults(ResultSetInterface $results) {
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
		$entity['_loc'] = $this->_table->getUrl($entity);
		$entity['_lastmod'] = $entity->{$this->_config['lastmod']};
		$entity['_changefreq'] = $this->_config['changefreq'];
		$entity['_priority'] = $this->_config['priority'];

		return $entity;
	}
}
