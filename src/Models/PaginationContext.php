<?php

namespace BertBijnens\LaravelFractalPaginate\Models;

use Exception;

class PaginationContext
{
	public $paginationRequired = true;

	public $paginationAvailable = false;

	public $page;

	public $since;

	public $until;

	public $offset;

	public $limit = null;

	public $max_limit = 1000;

	public $query = null;

	protected $data = [];


	protected $fillable = [
		'page',
		'since',
		'until',
		'offset',
		'limit'
	];


	public function __construct($context = array(), $paginationRequired = true) {

		$this->paginationRequired = $paginationRequired;

		if(is_object($context)) {
			$context = (array)$context;
		}

		if(is_array($context)) {
			foreach($this->fillable as $fillable) {
				if(isset($context[$fillable])) {
					$this->$fillable = $context[$fillable];
				}
			}
		}

		if($this->paginationRequired) {
			$this->page = $this->page ?: 1;

			$this->limit = $this->limit ?: 20;
		}

		if($this->page || $this->limit || $this->since || $this->offset) {
			$this->paginationAvailable = true;
		}
	}

	public static function make($context, $paginationRequired = true) {
		return new self($context, $paginationRequired);
	}


	public function getPageType() {
		if($this->page === null && $this->offset !== null) {
			return 'offset';
		}

		return 'page';
	}

	public function isPageType($type) {
		return $this->getPageType() == $type;
	}

	public function hasTimestamps() {
		return $this->since || $this->until;
	}

	public function apply($query) {

		if($this->hasTimestamps()) {
			$this->applyTimestampFilters($query);
		}

		$this->applyPageFilters($query);

		$this->query = $query;

		return $query;
	}

	public function hasNextPage() {
		return count($this->data) != 0 && count($this->data) == $this->limit;
	}

	public function getNextPage() {
		$context = [];

		if($this->limit) {
			$context['limit'] = $this->limit;
		}

		//TOOD check if there is a next page? else give current page / correct offset? unless items in current page is equeal to limit?
		if(!$this->hasNextPage()) {
			return null;
		}

		if($this->isPageType('page')) {
			if(!$this->page || $this->page == 0) {
				$this->page = 1;
			}

			$context['page'] = $this->page + 1;
		}
		else {
			$context['offset'] = ($this->offset ?: 0) + $this->limit; //TODO instead of limit, items in the current response
		}

		if($this->since) {
			$s = $this->since;

			//TODO figure out if this is should work:
			/*foreach($data as $d) {
				if(optional($d)['updated_at'] > $s) {
					$s = $d['updated_at'];
				}
			}*/

			//Temp workarround
			$s = $_SERVER['REQUEST_TIME'];

			$context['since'] = $s;
		}


		if($this->until) {
			$context['until'] = $this->until;
		}

		return '?' . http_build_query(array_merge(
				$_GET,
				$context
			));
	}

	public function getPrevious() {
		$context = [];

		if($this->limit) {
			$context['limit'] = $this->limit;
		}

		//TOOD check if there is a previous page? else give current page / correct offset? unless items in current page is equeal to limit?
		if($this->isPageType('page')) {
			if(!$this->page || $this->page == 0) {
				$this->page = 1;
			}

			$context['page'] = $this->page - 1;

			if($context['page'] <= 0) {
				return null;
			}
		}
		else {
			$context['offset'] = ($this->offset ?: 0) + $this->limit; //TODO instead of limit, items in the current response
		}

		if($this->since) {
			$s = $this->since;

			//TODO figure out if this is should work:
			/*foreach($data as $d) {
				if(optional($d)['updated_at'] > $s) {
					$s = $d['updated_at'];
				}
			}*/

			//Temp workarround
			$s = $_SERVER['REQUEST_TIME'];

			$context['since'] = $s;
		}


		if($this->until) {
			$context['until'] = $this->until;
		}

		return '?' . http_build_query(array_merge(
				$_GET,
				$context
			));
	}

	private function applyPageFilters($query) {
		if((!$this->limit || $this->limit <= 0) && $this->paginationRequired) {
			throw new Exception('Illigal page limit: ' . $this->limit . '. Page limit should be at least 1');
		}

		if((!$this->offset || $this->offset < 0) && $this->paginationRequired) {
			$this->offset = 0;
		}

		if($this->limit || $this->paginationRequired) {
			$query->limit(min($this->limit, $this->max_limit));
		}

		if($this->offset) {
			$query->offset($this->offset);
		}
		else if($this->page) {
			$query->offset(max(0, $this->limit * ($this->page - 1)));
		}
	}

	private function applyTimestampFilters($query) {
		if($this->since) {
			$query->where('updated_at', '>=', date('Y-m-d H:i:s', $this->since));
		}

		if($this->until) {
			$query->where('updated_at', '<', date('Y-m-d H:i:s', $this->until));
		}
	}

	public function getPaginationData($data) {
		$uri = $_SERVER['REQUEST_URI'] ?? request()->path();
		$this->data = $data;

		if(strpos($uri, '?') !== false) {
			$uri = substr($uri,0, strpos($uri, '?'));
		}

		$next_page = $this->getNextPage();
		$previous_page = $this->getPrevious();

		$protocol = env('APP_ENV') == 'local' && !isset($_SERVER["HTTPS"]) ? 'http' : 'https';
		$host = $_SERVER['HTTP_HOST'] ?? env('APP_URL');

		return [
			'next' => $next_page ? $protocol . '://' . $host . $uri . $next_page : null,
			'previous' => $previous_page ? $protocol . '://' . $host . $uri . $previous_page : null
		];
	}

	public function getMetaData($data) {
		$meta = [
			'total' => 0,
			'pages' => 0,
			'page' => 0
		];

		if($this->query) {
			$query = with(clone $this->query);
			if($query) {
				$query->limit = null;
				$meta['total'] = $query->offset(0)->count();
			}
		}

		if($this->paginationRequired || $this->limit) {
			$meta['pages'] = ceil($meta['total'] / $this->limit);
		}

		if($this->paginationAvailable) {
			$meta['page'] = $this->page;
		}

		return $meta;
	}
}
