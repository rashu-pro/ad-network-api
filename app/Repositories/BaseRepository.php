<?php
namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use App\Repositories\Interfaces\BaseRepositoryInterface;

abstract class BaseRepository implements BaseRepositoryInterface {
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param Model $model
     */
    public function __construct(Model $model) {
        $this->model = $model;
    }

    /**
     * Get all records with optional relationships.
     *
     * @param array $with
     * @return \Illuminate\Support\Collection
     */
    public function all(array $with = []): \Illuminate\Support\Collection {
        return $this->model->with($with)->get();
    }

    /**
     * Find a record by its ID with optional relationships.
     *
     * @param int $id
     * @param array $with
     * @return ?Model
     */
    public function find(int $id, array $with = []): ?Model {
        return $this->model->with($with)->find($id);
    }

    /**
     * Create a new record with the given attributes.
     *
     * @param array $attributes
     * @return Model
     */
    public function create(array $attributes): Model {
        return $this->model->create($attributes);
    }

    /**
     * Update a record by its ID with the given attributes.
     *
     * @param int $id
     * @param array $attributes
     * @return bool
     */
    public function update(int $id, array $attributes): bool {
        $record = $this->find($id);
        return $record ? $record->update($attributes) : false;
    }

    /**
     * Delete a record by its ID.
     *
     * @param int $id
     * @return bool True if the record was successfully deleted, false otherwise.
     */
    public function delete(int $id): bool {
        $record = $this->find($id);
        return $record ? $record->delete() : false;
    }
}
