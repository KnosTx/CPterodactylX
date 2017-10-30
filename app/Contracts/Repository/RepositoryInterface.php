<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Contracts\Repository;

interface RepositoryInterface
{
    /**
     * Return an identifier or Model object to be used by the repository.
     *
     * @return string|\Closure|object
     */
    public function model();

    /**
     * Return the model being used for this repository instance.
     *
     * @return mixed
     */
    public function getModel();

    /**
     * Returns an instance of a query builder.
     *
     * @return mixed
     */
    public function getBuilder();

    /**
     * Returns the colummns to be selected or returned by the query.
     *
     * @return mixed
     */
    public function getColumns();

    /**
     * An array of columns to filter the response by.
     *
     * @param array $columns
     * @return $this
     */
    public function withColumns($columns = ['*']);

    /**
     * Disable returning a fresh model when data is inserted or updated.
     *
     * @return $this
     */
    public function withoutFresh();

    /**
     * Create a new model instance and persist it to the database.
     *
     * @param array $fields
     * @param bool  $validate
     * @param bool  $force
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function create(array $fields, $validate = true, $force = false);

    /**
     * Delete a given record from the database.
     *
     * @param int $id
     * @return int
     */
    public function delete($id);

    /**
     * Delete records matching the given attributes.
     *
     * @param array $attributes
     * @return int
     */
    public function deleteWhere(array $attributes);

    /**
     * Find a model that has the specific ID passed.
     *
     * @param int $id
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function find($id);

    /**
     * Find a model matching an array of where clauses.
     *
     * @param array $fields
     * @return mixed
     */
    public function findWhere(array $fields);

    /**
     * Find and return the first matching instance for the given fields.
     *
     * @param array $fields
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function findFirstWhere(array $fields);

    /**
     * Return a count of records matching the passed arguments.
     *
     * @param array $fields
     * @return int
     */
    public function findCountWhere(array $fields);

    /**
     * Update a given ID with the passed array of fields.
     *
     * @param int   $id
     * @param array $fields
     * @param bool  $validate
     * @param bool  $force
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update($id, array $fields, $validate = true, $force = false);

    /**
     * Perform a mass update where matching records are updated using whereIn.
     * This does not perform any model data validation.
     *
     * @param string $column
     * @param array  $values
     * @param array  $fields
     * @return int
     */
    public function updateWhereIn($column, array $values, array $fields);

    /**
     * Update a record if it exists in the database, otherwise create it.
     *
     * @param array $where
     * @param array $fields
     * @param bool  $validate
     * @param bool  $force
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function updateOrCreate(array $where, array $fields, $validate = true, $force = false);

    /**
     * Update multiple records matching the passed clauses.
     *
     * @param array $where
     * @param array $fields
     * @return mixed
     */
    public function massUpdate(array $where, array $fields);

    /**
     * Return all records from the model.
     *
     * @return mixed
     */
    public function all();

    /**
     * Insert a single or multiple records into the database at once skipping
     * validation and mass assignment checking.
     *
     * @param array $data
     * @return bool
     */
    public function insert(array $data);

    /**
     * Insert multiple records into the database and ignore duplicates.
     *
     * @param array $values
     * @return bool
     */
    public function insertIgnore(array $values);
}
