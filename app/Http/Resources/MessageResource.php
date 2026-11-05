<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Odgovor sa samo prevedenom porukom: {"message": "..."}.
 *
 * @property string $resource
 */
class MessageResource extends JsonResource
{
    /**
     * @return array{message: string}
     */
    public function toArray(Request $request): array
    {
        return ['message' => $this->resource];
    }
}
