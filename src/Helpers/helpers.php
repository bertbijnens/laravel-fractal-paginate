<?php

if(!function_exists('paginate')) {
	function paginate($query, $transformer) {
		return fractal()->paginate($query, $transformer);
	}
}