<?php

namespace BertBijnens\LaravelFractalPaginate;

use App\Models\Album;
use BertBijnens\LaravelFractalPaginate\Managers\PaginationContextManager;
use BertBijnens\LaravelFractalPaginate\Serializers\PaginationSerializer;
use Illuminate\Support\ServiceProvider;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Spatie\Fractal\Fractal;
use Spatie\Fractalistic\ArraySerializer;

class LaravelFractalPaginateServiceProvider extends ServiceProvider
{
    public function boot(\Illuminate\Routing\Router $router)
    {
        if($this->app->runningInConsole()) {

        }
    }

    public function register()
    {
        Fractal::macro('paginate', function($collection, $transformer, $paginationRequired = true) {

        	$context = PaginationContextManager::getContextFromRequest($paginationRequired);

			if(!is_array($collection) && get_class($collection) !== 'Illuminate\Database\Eloquent\Collection') {
				$collection = $context->apply($collection)->get();
			}

			PaginationSerializer::setContext($context);


			if($context->paginationAvailable || $paginationRequired) {
				return fractal($this)->collection($collection, $transformer, 'root')->serializeWith(PaginationSerializer::class);
			}

			return fractal($this)->collection($collection, $transformer);
		});

		Fractal::macro('prepend', function($item) {

			if($item) {
				$this->data->prepend($item);
			}

			return $this;
		});

		Fractal::macro('append', function($item) {

			if($item) {
				$this->data->append($item);
			}

			return $this;
		});
    }
}
