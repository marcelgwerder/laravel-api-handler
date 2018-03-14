<?php

namespace Marcelgwerder\ApiHandler\Resources\Json;

use Illuminate\Http\Resources\Json\ResourceCollection as IlluminateResourceCollection;

class ResourceCollection extends IlluminateResourceCollection
{

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'data' => $this->collection
        ];
    }
}
