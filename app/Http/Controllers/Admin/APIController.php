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
namespace Pterodactyl\Http\Controllers\Admin;

use Alert;
use Log;

use Pterodactyl\Models;
use Pterodactyl\Repositories\APIRepository;
use Pterodactyl\Http\Controllers\Controller;

use Pterodactyl\Exceptions\DisplayValidationException;
use Pterodactyl\Exceptions\DisplayException;

use Illuminate\Http\Request;

class APIController extends Controller
{

    public function __construct()
    {
        //
    }

    public function getIndex(Request $request)
    {
        $keys = Models\APIKey::all();
        foreach($keys as &$key) {
            $key->permissions = Models\APIPermission::where('key_id', $key->id)->get();
        }

        return view('admin.api.index', [
            'keys' => $keys
        ]);
    }

    public function getNew(Request $request)
    {
        return view('admin.api.new');
    }

    public function postNew(Request $request)
    {
        try {
            $api = new APIRepository;
            $secret = $api->new($request->except(['_token']));
            // Alert::info('An API Keypair has successfully been generated. The API secret for this public key is shown below and will not be shown again.<br /><br />Secret: <code>' . $secret . '</code>')->flash();
            Alert::info("<script type='text/javascript'>swal({
                type: 'info',
                title: 'Secret Key',
                html: true,
                text: 'The secret for this keypair is shown below and will not be shown again.<hr /><code style=\'text-align:center;\'>" . $secret . "</code>'
            });</script>")->flash();
            return redirect()->route('admin.api');
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.api.new')->withErrors(json_decode($ex->getMessage()))->withInput();
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unhandled exception occured while attempting to add this API key.')->flash();
        }
        return redirect()->route('admin.api.new')->withInput();
    }

    public function deleteRevokeKey(Request $request, $key)
    {
        try {
            $api = new APIRepository;
            $api->revoke($key);
            return response('', 204);
        } catch (\Exception $ex) {
            return response()->json([
                'error' => 'An error occured while attempting to remove this key.'
            ], 503);
        }
    }

}
