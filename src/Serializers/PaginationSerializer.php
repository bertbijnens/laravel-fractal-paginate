<?php
/**
 * Created by PhpStorm.
 * User: bertbijnens
 * Date: 11/07/2019
 * Time: 12:02
 */

namespace BertBijnens\LaravelFractalPaginate\Serializers;

use League\Fractal\Serializer\ArraySerializer;

class PaginationSerializer extends ArraySerializer
{
	public static $context = [];

	public function collection($resourceKey, array $data) {

	    if($resourceKey !== 'root') {
            return $data;
        }

		return [
			'data' => $data,
			'links' => optional(self::$context)->getPaginationData($data) ?: [],
		];
	}

	public function null() {
		return null;
	}

	public static function setContext($context) {
		self::$context = $context ?: [];
	}
}
