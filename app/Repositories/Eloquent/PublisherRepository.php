<?php

namespace App\Repositories\Eloquent;

use App\Models\PublisherAsset;
use App\Repositories\BaseRepository;
use App\Repositories\Interfaces\PublisherRepositoryInterface;

class PublisherRepository extends BaseRepository implements PublisherRepositoryInterface
{
    public function __construct(PublisherAsset $model) {
        parent::__construct($model);
    }

    public function togglePublisherAssetStatus(int $id): ?PublisherAsset
    {
        $publisherAsset = $this->model->find($id);

        if ($publisherAsset) {
            $publisherAsset->status = !$publisherAsset->status;
            $publisherAsset->save();
        }

        return $publisherAsset;
    }

}
