<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
namespace Pterodactyl\Http\Controllers\API;

use Illuminate\Http\Request;

use Dingo\Api\Exception\ResourceException;

use Pterodactyl\Models;
use Pterodactyl\Transformers\UserTransformer;
use Pterodactyl\Repositories\UserRepository;

use Pterodactyl\Exceptions\DisplayValidationException;
use Pterodactyl\Exceptions\DisplayException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * @Resource("Users")
 */
class UserController extends BaseController
{

    /**
     * List All Users
     *
     * Lists all users currently on the system.
     *
     * @Get("/users/{?page}")
     * @Versions({"v1"})
     * @Parameters({
     *      @Parameter("page", type="integer", description="The page of results to view.", default=1)
     * })
     * @Response(200)
     */
    public function getUsers(Request $request)
    {
        $users = Models\User::paginate(50);
        return $this->response->paginator($users, new UserTransformer);
    }

    /**
     * List Specific User
     *
     * Lists specific fields about a user or all fields pertaining to that user.
     *
     * @Get("/users/{id}/{fields}")
     * @Versions({"v1"})
     * @Parameters({
     *      @Parameter("id", type="integer", required=true, description="The ID of the user to get information on."),
     *      @Parameter("fields", type="string", required=false, description="A comma delimidated list of fields to include.")
     * })
     * @Response(200)
     */
    public function getUser(Request $request, $id)
    {
        $query = Models\User::where('id', $id);

        if (!is_null($request->input('fields'))) {
            foreach(explode(',', $request->input('fields')) as $field) {
                if (!empty($field)) {
                    $query->addSelect($field);
                }
            }
        }

        try {
            if (!$query->first()) {
                throw new NotFoundHttpException('No user by that ID was found.');
            }
            return $query->first();
        } catch (NotFoundHttpException $ex) {
            throw $ex;
        } catch (\Exception $ex) {
            throw new BadRequestHttpException('There was an issue with the fields passed in the request.');
        }

    }

    /**
     * Create a New User
     *
     * @Post("/users")
     * @Versions({"v1"})
     * @Transaction({
     *      @Request({
     *          "email": "foo@example.com",
     *          "password": "foopassword",
     *          "admin": false
     *       }, headers={"Authorization": "Bearer <jwt-token>"}),
     *       @Response(201),
     *       @Response(422, body={
     *          "message": "A validation error occured.",
     *          "errors": {
     *              "email": {"The email field is required."},
     *              "password": {"The password field is required."},
     *              "admin": {"The admin field is required."}
     *          },
     *          "status_code": 422
     *       })
     * })
     */
    public function postUser(Request $request)
    {
        try {
            $user = new UserRepository;
            $create = $user->create($request->input('email'), $request->input('password'), $request->input('admin'));
            return $this->response->created(route('api.users.view', [
                'id' => $create
            ]));
        } catch (DisplayValidationException $ex) {
            throw new ResourceException('A validation error occured.', json_decode($ex->getMessage(), true));
        } catch (DisplayException $ex) {
            throw new ResourceException($ex->getMessage());
        } catch (\Exception $ex) {
            throw new ServiceUnavailableHttpException('Unable to create a user on the system due to an error.');
        }
    }

    /**
     * Update an Existing User
     *
     * The data sent in the request will be used to update the existing user on the system.
     *
     * @Patch("/users/{id}")
     * @Versions({"v1"})
     * @Transaction({
     *      @Request({
     *          "email": "new@email.com"
     *      }, headers={"Authorization": "Bearer <jwt-token>"}),
     *      @Response(200, body={"email": "new@email.com"}),
     *      @Response(422)
     * })
     * @Parameters({
     *         @Parameter("id", type="integer", required=true, description="The ID of the user to modify.")
     * })
     */
    public function patchUser(Request $request, $id)
    {
        try {
            $user = new UserRepository;
            $user->update($id, $request->all());
            return Models\User::findOrFail($id);
        } catch (DisplayValidationException $ex) {
            throw new ResourceException('A validation error occured.', json_decode($ex->getMessage(), true));
        } catch (DisplayException $ex) {
            throw new ResourceException($ex->getMessage());
        } catch (\Exception $ex) {
            throw new ServiceUnavailableHttpException('Unable to update a user on the system due to an error.');
        }
    }

    /**
     * Delete a User
     *
     * @Delete("/users/{id}")
     * @Versions({"v1"})
     * @Transaction({
     *      @Request(headers={"Authorization": "Bearer <jwt-token>"}),
     *      @Response(204),
     *      @Response(422)
     * })
     * @Parameters({
     *      @Parameter("id", type="integer", required=true, description="The ID of the user to delete.")
     * })
     */
    public function deleteUser(Request $request, $id)
    {
        try {
            $user = new UserRepository;
            $user->delete($id);
            return $this->response->noContent();
        } catch (DisplayException $ex) {
            throw new ResourceException($ex->getMessage());
        } catch (\Exception $ex) {
            throw new ServiceUnavailableHttpException('Unable to delete this user due to an error.');
        }
    }

}
