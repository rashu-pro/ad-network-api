<?php

namespace App\Repositories\Eloquent;

use App\Models\Zone;
use App\Repositories\BaseRepository;
use App\Repositories\Interfaces\ZoneRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ZoneRepository extends BaseRepository implements ZoneRepositoryInterface {
    public function __construct(Zone $model) {
        parent::__construct($model);
    }
    public function getActiveZones(): Collection
    {
        return $this->model->whereNull('deleted_at')->get();
    }
}
