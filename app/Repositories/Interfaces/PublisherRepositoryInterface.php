<?php

namespace App\Repositories\Interfaces;

use App\Models\PublisherAsset;

interface PublisherRepositoryInterface extends BaseRepositoryInterface
{
    public function togglePublisherAssetStatus(int $id): ?PublisherAsset;
}
