<?php
/*
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

return [
    'daemon_connection_failed' => 'There was an exception while attempting to communicate with the daemon resulting in a HTTP/:code response code. This exception has been logged.',
    'node' => [
        'servers_attached' => 'A node must have no servers linked to it in order to be deleted.',
        'daemon_off_config_updated' => 'The daemon configuration <strong>has been updated</strong>, however there was an error encountered while attempting to automatically update the configuration file on the Daemon. You will need to manually update the configuration file (core.json) for the daemon to apply these changes. The daemon responded with a HTTP/:code response code and the error has been logged.',
    ],
    'allocations' => [
        'too_many_ports' => 'Adding more than 1000 ports at a single time is not supported. Please use a smaller range.',
        'invalid_mapping' => 'The mapping provided for :port was invalid and could not be processed.',
        'cidr_out_of_range' => 'CIDR notation only allows masks between /25 and /32.',
    ],
    'service' => [
        'delete_has_servers' => 'A service with active servers attached to it cannot be deleted from the Panel.',
        'options' => [
            'delete_has_servers' => 'A service option with active servers attached to it cannot be deleted from the Panel.',
            'invalid_copy_id' => 'The service option selected for copying a script from either does not exist, or is copying a script itself.',
            'must_be_child' => 'The "Copy Settings From" directive for this option must be a child option for the selected service.',
        ],
        'variables' => [
            'env_not_unique' => 'The environment variable :name must be unique to this service option.',
            'reserved_name' => 'The environment variable :name is protected and cannot be assigned to a variable.',
        ],
    ],
    'packs' => [
        'delete_has_servers' => 'Cannot delete a pack that is attached to active servers.',
        'update_has_servers' => 'Cannot modify the associated option ID when servers are currently attached to a pack.',
        'invalid_upload' => 'The file provided does not appear to be valid.',
        'invalid_mime' => 'The file provided does not meet the required type :type',
        'unreadable' => 'The archive provided could not be opened by the server.',
        'zip_extraction' => 'An exception was encountered while attempting to extract the archive provided onto the server.',
        'invalid_archive_exception' => 'The pack archive provided appears to be missing a required archive.tar.gz or import.json file in the base directory.',
    ],
    'subusers' => [
        'editing_self' => 'Editing your own subuser account is not permitted.',
        'user_is_owner' => 'You cannot add the server owner as a subuser for this server.',
        'subuser_exists' => 'A user with that email address is already assigned as a subuser for this server.',
    ],
    'databases' => [
        'delete_has_databases' => 'Cannot delete a database host server that has active databases linked to it.',
    ],
    'tasks' => [
        'chain_interval_too_long' => 'The maximum interval time for a chained task is 15 minutes.',
    ],
    'locations' => [
        'has_nodes' => 'Cannot delete a location that has active nodes attached to it.',
    ],
];
