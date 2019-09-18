# Laravel fractal paginate

This package provides macro functions to easily implement pagination.
[Spatie's Laravel fractal wrapper](https://github.com/spatie/laravel-fractal/) is required.


# Install

This packages implements Laravel auto-discovery. Installing this package using composer:

    composer require bertbijnens/laravel-fractal-paginate

# Usage

Use the "paginate" macro on fractal:

    return fractal()->paginate($query, $transformer);

The paginate macro acts the same as the collection method. However instead of a collection it expects a queryable object.

Based on the available request variables it will apply pagination with support for:
- page (starts at 1)
- offset
- limit (currently capped at 100)
- since (filter objects updated since this timestamp)
- until (filter objects updated until this timestamp)

The response will look something like:

    {
		data: [
			{},
			{},
			{},
			....
		],
		links: {
			next: 'https://.....?page=2&limit=5'
		}
	}

The response always returns the next url, when there is no more data available this will be null. 

## Optional pagination

You can set the pagination response as optional

    return fractal()->paginate($query, $transformer, false);

When this third parameters (paginationRequired, default true) is set to false and there are no pagination parameters in the request, the reponse will go through the default serializer as set in the fractal config.
