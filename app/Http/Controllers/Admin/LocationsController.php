<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
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

namespace Pterodactyl\Http\Controllers\Admin;

use Alert;
use Pterodactyl\Models;
use Illuminate\Http\Request;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\LocationRepository;
use Pterodactyl\Exceptions\DisplayValidationException;

class LocationsController extends Controller
{
    public function __construct()
    {
        //
    }

    public function getIndex(Request $request)
    {
        return view('admin.locations.index', [
            'locations' => Models\Location::withCount('nodes', 'servers')->paginate(20),
        ]);
    }

    public function deleteLocation(Request $request, $id)
    {
        $location = Models\Location::withCount('nodes')->findOrFail($id);

        if ($location->nodes_count > 0) {
            return response()->json([
                'error' => 'You cannot remove a location that is currently assigned to a node.',
            ], 422);
        }

        $location->delete();

        return response('', 204);
    }

    public function patchLocation(Request $request, $id)
    {
        try {
            $location = new LocationRepository;
            $location->edit($id, $request->only(['long', 'short']));

            return response('', 204);
        } catch (DisplayValidationException $ex) {
            return response()->json([
                'error' => 'There was a validation error while processing this request. Location descriptions must be between 1 and 255 characters, and the location code must be between 1 and 20 characters with no spaces or special characters.',
            ], 422);
        } catch (\Exception $ex) {
            // This gets caught and processed into JSON anyways.
            throw $ex;
        }
    }

    public function postLocation(Request $request)
    {
        try {
            $location = new LocationRepository;
            $location->create($request->only(['long', 'short']));
            Alert::success('New location successfully added.')->flash();

            return redirect()->route('admin.locations');
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.locations')->withErrors(json_decode($ex->getMessage()))->withInput();
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unhandled exception occured while attempting to add this location. Please try again.')->flash();
        }

        return redirect()->route('admin.locations')->withInput();
    }
}
