<?php

namespace App\Repositories\Interfaces;

interface ZoneRepositoryInterface extends BaseRepositoryInterface {
    /**
     * Get all active assets.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getActiveZones(): \Illuminate\Support\Collection;
}
