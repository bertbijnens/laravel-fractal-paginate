<?php

namespace BertBijnens\LaravelFractalPaginate\Managers;

use BertBijnens\LaravelFractalPaginate\Models\PaginationContext;
use Illuminate\Http\Request;

class PaginationContextManager
{
	public static function getContextFrom(Request $request, $paginationRequired = true) {
		return PaginationContext::make($request->all(), $paginationRequired);
	}

	public static function getContextFromRequest($paginationRequired = true) {
		return self::getContextFrom(request(), $paginationRequired);
	}
}