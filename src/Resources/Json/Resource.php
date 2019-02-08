<?php

namespace Marcelgwerder\ApiHandler\Resources\Json;

use Illuminate\Http\Resources\Json\Resource as IlluminateResource;

class Resource extends IlluminateResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return $this->resource;
    }
}
