<?php

namespace Pterodactyl\Http\Controllers\API;

use Illuminate\Http\Request;

use Pterodactyl\Models;
use Pterodactyl\Transformers\NodeTransformer;
use Pterodactyl\Repositories\NodeRepository;

use Pterodactyl\Exceptions\DisplayValidationException;
use Pterodactyl\Exceptions\DisplayException;
use Dingo\Api\Exception\ResourceException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * @Resource("Servers")
 */
class NodeController extends BaseController
{

    public function __construct()
    {
        //
    }

    /**
     * List All Nodes
     *
     * Lists all nodes currently on the system.
     *
     * @Get("/nodes/{?page}")
     * @Versions({"v1"})
     * @Parameters({
     *      @Parameter("page", type="integer", description="The page of results to view.", default=1)
     * })
     * @Response(200)
     */
    public function getNodes(Request $request)
    {
        $nodes = Models\Node::paginate(50);
        return $this->response->paginator($nodes, new NodeTransformer);
    }

    /**
     * Create a New Node
     *
     * @Post("/nodes")
     * @Versions({"v1"})
     * @Transaction({
     *      @Request({
     *      	'name' => 'My API Node',
     *      	'location' => 1,
     *      	'public' => 1,
     *      	'fqdn' => 'daemon.wuzzle.woo',
     *      	'scheme' => 'https',
     *      	'memory' => 10240,
     *      	'memory_overallocate' => 100,
     *      	'disk' => 204800,
     *      	'disk_overallocate' => -1,
     *      	'daemonBase' => '/srv/daemon-data',
     *      	'daemonSFTP' => 2022,
     *      	'daemonListen' => 8080
     *      }, headers={"Authorization": "Bearer <jwt-token>"}),
     *       @Response(201),
     *       @Response(422, body={
     *          "message": "A validation error occured.",
     *          "errors": {},
     *          "status_code": 422
     *       }),
     *       @Response(503, body={
     *       	"message": "There was an error while attempting to add this node to the system.",
     *       	"status_code": 503
     *       })
     * })
     */
    public function postNode(Request $request)
    {
        try {
            $node = new NodeRepository;
            $new = $node->create($request->all());
            return $this->response->created(route('api.nodes.view', [
                'id' => $new
            ]));
        } catch (DisplayValidationException $ex) {
            throw new ResourceException('A validation error occured.', json_decode($ex->getMessage(), true));
        } catch (DisplayException $ex) {
            throw new ResourceException($ex->getMessage());
        } catch (\Exception $e) {
            throw new BadRequestHttpException('There was an error while attempting to add this node to the system.');
        }
    }

    /**
     * List Specific Node
     *
     * Lists specific fields about a server or all fields pertaining to that node.
     *
     * @Get("/nodes/{id}/{?fields}")
     * @Versions({"v1"})
     * @Parameters({
     *      @Parameter("id", type="integer", required=true, description="The ID of the node to get information on."),
     *      @Parameter("fields", type="string", required=false, description="A comma delimidated list of fields to include.")
     * })
     * @Response(200)
     */
    public function getNode(Request $request, $id, $fields = null)
    {
        $query = Models\Node::where('id', $id);

        if (!is_null($request->input('fields'))) {
            foreach(explode(',', $request->input('fields')) as $field) {
                if (!empty($field)) {
                    $query->addSelect($field);
                }
            }
        }

        try {
            if (!$query->first()) {
                throw new NotFoundHttpException('No node by that ID was found.');
            }
            return $query->first();
        } catch (NotFoundHttpException $ex) {
            throw $ex;
        } catch (\Exception $ex) {
            throw new BadRequestHttpException('There was an issue with the fields passed in the request.');
        }
    }

    /**
     * List Node Allocations
     *
     * Returns a listing of all node allocations.
     *
     * @Get("/nodes/{id}/allocations")
     * @Versions({"v1"})
     * @Parameters({
     *      @Parameter("id", type="integer", required=true, description="The ID of the node to get allocations for."),
     * })
     * @Response(200)
     */
    public function getNodeAllocations(Request $request, $id)
    {
        $allocations = Models\Allocation::select('ip', 'port', 'assigned_to')->where('node', $id)->orderBy('ip', 'asc')->orderBy('port', 'asc')->get();
        if ($allocations->count() < 1) {
            throw new NotFoundHttpException('No allocations where found for the requested node.');
        }
        return $allocations;
    }

    /**
     * Delete Node
     *
     * @Delete("/nodes/{id}")
     * @Versions({"v1"})
     * @Parameters({
     *      @Parameter("id", type="integer", required=true, description="The ID of the node."),
     * })
     * @Response(204)
     */
    public function deleteNode(Request $request, $id)
    {
        try {
            $node = new NodeRepository;
            $node->delete($id);
            return $this->response->noContent();
        } catch (DisplayException $ex) {
            throw new ResourceException($ex->getMessage());
        } catch(\Exception $e) {
            throw new ServiceUnavailableHttpException('An error occured while attempting to delete this node.');
        }
    }

}
