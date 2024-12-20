<?php
namespace App\Repositories\Interfaces;

interface BaseRepositoryInterface {
    /**
     * Get all records with optional relationships.
     *
     * @param array $with
     * @return \Illuminate\Support\Collection
     */
    public function all(array $with = []): \Illuminate\Support\Collection;

    /**
     * Find a record by its ID with optional relationships.
     *
     * @param int $id
     * @param array $with
     * @return ?\Illuminate\Database\Eloquent\Model
     */
    public function find(int $id, array $with = []): ?\Illuminate\Database\Eloquent\Model;

    /**
     * Create a new record with the given attributes.
     *
     * @param array $attributes
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function create(array $attributes): \Illuminate\Database\Eloquent\Model;

    /**
     * Update a record by its ID with the given attributes.
     *
     * @param int $id
     * @param array $attributes
     * @return bool
     */
    public function update(int $id, array $attributes): bool;

    /**
     * Delete a record by its ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;
}
