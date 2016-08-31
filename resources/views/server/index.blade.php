{{-- Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com> --}}

{{-- Permission is hereby granted, free of charge, to any person obtaining a copy --}}
{{-- of this software and associated documentation files (the "Software"), to deal --}}
{{-- in the Software without restriction, including without limitation the rights --}}
{{-- to use, copy, modify, merge, publish, distribute, sublicense, and/or sell --}}
{{-- copies of the Software, and to permit persons to whom the Software is --}}
{{-- furnished to do so, subject to the following conditions: --}}

{{-- The above copyright notice and this permission notice shall be included in all --}}
{{-- copies or substantial portions of the Software. --}}

{{-- THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR --}}
{{-- IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, --}}
{{-- FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE --}}
{{-- AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER --}}
{{-- LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, --}}
{{-- OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE --}}
{{-- SOFTWARE. --}}
@extends('layouts.master')

@section('title')
    Viewing Server: {{ $server->name }}
@endsection

@section('scripts')
    @parent
    {!! Theme::css('css/metricsgraphics.css') !!}
    {!! Theme::js('js/d3.min.js') !!}
    {!! Theme::js('js/metricsgraphics.min.js') !!}
    {!! Theme::js('js/async.min.js') !!}
@endsection

@section('content')
<div class="col-md-12">
    <ul class="nav nav-tabs tabs_with_panel" id="config_tabs">
        <li id="triggerConsoleView" class="active"><a href="#console" data-toggle="tab">{{ trans('server.index.control') }}</a></li>
        @can('view-allocation', $server)<li><a href="#allocation" data-toggle="tab">{{ trans('server.index.allocation') }}</a></li>@endcan
    </ul>
    <div class="tab-content">
        <div class="tab-pane active" id="console">
            <div class="panel panel-default">
                <div class="panel-heading"></div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-12">
                            <textarea id="live_console" class="form-control console" readonly="readonly">Loading Previous Content...</textarea>
                        </div>
                        <div class="col-md-6">
                            <hr />
                            @can('send-command', $server)
                                <form action="#" method="post" id="console_command" style="display:none;">
                                    <fieldset>
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="command" id="ccmd" placeholder="{{ trans('server.index.command') }}" />
                                            <span class="input-group-btn">
                                                <button id="sending_command" class="btn btn-primary btn-sm">&rarr;</button>
                                            </span>
                                        </div>
                                    </fieldset>
                                </form>
                                <div class="alert alert-danger" id="sc_resp" style="display:none;margin-top: 15px;"></div>
                            @endcan
                        </div>
                        <div class="col-md-6" style="text-align:center;">
                            <hr />
                            @can('power-start', $server)<button class="btn btn-success btn-sm disabled" data-attr="power" data-action="start">Start</button>@endcan
                            @can('power-restart', $server)<button class="btn btn-primary btn-sm disabled" data-attr="power" data-action="restart">Restart</button>@endcan
                            @can('power-stop', $server)<button class="btn btn-danger btn-sm disabled" data-attr="power" data-action="stop">Stop</button>@endcan
                            @can('power-kill', $server)<button class="btn btn-danger btn-sm disabled" data-attr="power" data-action="kill"><i class="fa fa-ban" data-toggle="tooltip" data-placement="top" title="Kill Running Process"></i></button>@endcan
                            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#pauseConsole" id="pause_console"><small><i class="fa fa-pause fa-fw"></i></small></button>
                            <div id="pw_resp" style="display:none;margin-top: 15px;"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12" id="stats_players">
                            <h3>Active Players</h3><hr />
                            <div id="players_notice" class="alert alert-info">
                                <i class="fa fa-spinner fa-spin"></i> Waiting for response from server...
                            </div>
                            <span id="toggle_players" style="display:none;">
                                <p class="text-muted">No players are online.</p>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @can('view-allocation', $server)
            <div class="tab-pane" id="allocation">
                <div class="panel panel-default">
                    <div class="panel-heading"></div>
                    <div class="panel-body">
                        <div class="alert alert-info">Below is a listing of all avaliable IPs and Ports for your service. To change the default connection address for your server, simply click on the one you would like to make default below.</div>
                        <ul class="nav nav-pills nav-stacked" id="conn_options">
                            @foreach ($allocations as $allocation)
                                <li role="presentation" @if($allocation->id === $server->allocation) class="active" @endif>
                                    <a href="#/set-connnection/{{ $allocation->ip }}:{{ $allocation->port }}" data-action="set-connection" data-connection="{{ $allocation->ip }}:{{ $allocation->port }}">{{ $allocation->ip_alias }}
                                        <span class="badge">{{ $allocation->port }}</span>
                                        @if($allocation->ip !== $allocation->ip_alias)<small><span class="pull-right">Alias for {{ $allocation->ip }}</span></small>@endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endcan
    </div>
    <div class="panel panel-default" style="margin-top:-22px;">
        <div class="panel-heading"></div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-12">
                    <h4 class="text-center">Memory Usage (MB)</h4>
                    <div class="col-md-12" id="chart_memory" style="height:250px;"></div>
                </div>
            </div>
            <div class="row" style="margin-top:15px;">
                <div class="col-md-12">
                    <h4 class="text-center">CPU Usage (% Total) <small><a href="#" data-action="show-all-cores">toggle cores</a></small></h4>
                    <div class="col-md-12" id="chart_cpu" style="height:250px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="pauseConsole" tabindex="-1" role="dialog" aria-labelledby="PauseConsole" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="PauseConsole">{{ trans('server.index.scrollstop') }}</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <textarea id="paused_console" class="form-control console" readonly="readonly"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('strings.close') }}</button>
            </div>
        </div>
    </div>
</div>
@if($server->a_serviceFile === 'minecraft')
    <script src="{{ route('server.js', [$server->uuidShort, 'minecraft/eula.js']) }}"></script>
@endif
<script>
$(window).load(function () {
    $('[data-toggle="tooltip"]').tooltip();

    var showOnlyTotal = true;
    $('[data-action="show-all-cores"]').click(function (event) {
        event.preventDefault();
        showOnlyTotal = !showOnlyTotal;
        $('#chart_cpu').empty();
    });

    // -----------------+
    // Charting Methods |
    // -----------------+
    var memoryGraphSettings = {
        data: [{
            'date': new Date(),
            'memory': -1
        }],
        full_width: true,
        full_height: true,
        right: 40,
        target: document.getElementById('chart_memory'),
        x_accessor: 'date',
        y_accessor: 'memory',
        animate_on_load: false,
        y_rug: true,
        area: false,
    };

    var cpuGraphData = [
        [{
            'date': new Date(),
            'cpu': -1
        }]
    ];
    var cpuGraphSettings = {
        data: cpuGraphData,
        legend: ['Total', 'C0', 'C1', 'C2', 'C3', 'C4', 'C5', 'C6', 'C7'],
        colors: [
            '#113F8C',
            '#00A1CB',
            '#01A4A4',
            '#61AE24',
            '#D0D102',
            '#D70060',
            '#E54028',
            '#F18D05',
            '#616161',
            '#32742C',
        ],
        right: 40,
        full_width: true,
        full_height: true,
        target: document.getElementById('chart_cpu'),
        x_accessor: 'date',
        y_accessor: 'cpu',
        aggregate_rollover: true,
        missing_is_hidden: true,
        animate_on_load: false,
        area: false,
    };

    MG.data_graphic(memoryGraphSettings);
    MG.data_graphic(cpuGraphSettings);

    // Socket Recieves New Server Stats
    var activeChartArrays = [];
    socket.on('proc', function (proc) {

        var curDate = new Date();
        if (typeof memoryGraphSettings.data[0][20] !== 'undefined' || memoryGraphSettings.data[0][0].memory === -1) {
            memoryGraphSettings.data[0].shift();
        }

        if (typeof cpuGraphData[0][20] !== 'undefined' || cpuGraphData[0][0].cpu === -1) {
            cpuGraphData[0].shift();
        }

        memoryGraphSettings.data[0].push({
            'date': curDate,
            'memory': parseInt(proc.data.memory.total / (1024 * 1024))
        });

        cpuGraphData[0].push({
            'date': curDate,
            'cpu': ({{ $server->cpu }} > 0) ? parseFloat(((proc.data.cpu.total / {{ $server->cpu }}) * 100).toFixed(3).toString()) : proc.data.cpu.total
        });

        async.waterfall([
            function (callback) {
                // Remove blank values from listing
                var activeCores = [];
                async.forEachOf(proc.data.cpu.cores, function(inner, i, eachCallback) {
                    if (proc.data.cpu.cores[i] > 0) {
                        activeCores.push(proc.data.cpu.cores[i]);
                    }
                    return eachCallback();
                }, function () {
                    return callback(null, activeCores);
                });
            },
            function (active, callback) {
                var modifedActiveCores = { '0': 0 };
                async.forEachOf(active, function (inner, i, eachCallback) {
                    if (i > 7) {
                        modifedActiveCores['0'] = modifedActiveCores['0'] + active[i];
                    } else {
                        if (activeChartArrays.indexOf(i) < 0) activeChartArrays.push(i);
                        modifedActiveCores[i] = active[i];
                    }
                    return eachCallback();
                }, function () {
                    return callback(null, modifedActiveCores);
                });
            },
            function (modified, callback) {
                async.forEachOf(activeChartArrays, function (inner, i, eachCallback) {
                    if (typeof cpuGraphData[(i + 1)] === 'undefined') {
                        cpuGraphData[(i + 1)] = [{
                            'date': curDate,
                            'cpu': ({{ $server->cpu }} > 0) ? parseFloat((((modified[i] || 0)/ {{ $server->cpu }}) * 100).toFixed(3).toString()) : modified[i] || null
                        }];
                    } else {
                        if (typeof cpuGraphData[(i + 1)][20] !== 'undefined') cpuGraphData[(i + 1)].shift();
                        cpuGraphData[(i + 1)].push({
                            'date': curDate,
                            'cpu': ({{ $server->cpu }} > 0) ? parseFloat((((modified[i] || 0)/ {{ $server->cpu }}) * 100).toFixed(3).toString()) : modified[i] || null
                        });
                    }
                    return eachCallback();
                }, function () {
                    return callback();
                });
            },
            function (callback) {
                cpuGraphSettings.data = (showOnlyTotal === true) ? cpuGraphData[0] : cpuGraphData;
                return callback();
            },
        ], function () {
            MG.data_graphic(memoryGraphSettings);
            MG.data_graphic(cpuGraphSettings);
        });
    });

    // Socket Recieves New Query
    socket.on('query', function (data){
        if($('#players_notice').is(':visible')){
            $('#players_notice').hide();
            $('#toggle_players').show();
        }
        if(typeof data['data'] !== 'undefined' && typeof data['data'].players !== 'undefined' && data['data'].players.length !== 0){
            $('#toggle_players').html('');
            $.each(data['data'].players, function(id, d) {
                $('#toggle_players').append('<code>' + d.name + '</code>,');
            });
        }else{
            $('#toggle_players').html('<p class=\'text-muted\'>No players are currently online.</p>');
        }
    });

    // New Console Data Recieved
    socket.on('console', function (data) {
        $('#live_console').val($('#live_console').val() + data.line);
        $('#live_console').scrollTop($('#live_console')[0].scrollHeight);
    });

    // Update Listings on Initial Status
    socket.on('initial_status', function (data) {
        if (data.status !== 0) {
            $.ajax({
                type: 'GET',
                headers: {
                    'X-Access-Token': '{{ $server->daemonSecret }}',
                    'X-Access-Server': '{{ $server->uuid }}'
                },
                url: '{{ $node->scheme }}://{{ $node->fqdn }}:{{ $node->daemonListen }}/server/log',
                timeout: 10000
            }).done(function(data) {
                $('#live_console').val(data);
                $('#live_console').scrollTop($('#live_console')[0].scrollHeight);
            }).fail(function(jqXHR, textStatus, errorThrown) {
                alert('Unable to load initial server log, try reloading the page.');
            });
        } else {
            $('#live_console').val('Server is currently off.');
        }
        updateServerPowerControls(data.status);
        updatePlayerListVisibility(data.status);
    });

    // Update Listings on Status
    socket.on('status', function (data) {
        updateServerPowerControls(data.status);
        updatePlayerListVisibility(data.status);
    });

    // Scroll to the top of the Console when switching to that tab.
    $('#triggerConsoleView').click(function () {
        $('#live_console').scrollTop($('#live_console')[0].scrollHeight);
    });
    if($('triggerConsoleView').is(':visible')) {
        $('#live_console').scrollTop($('#live_console')[0].scrollHeight);
    }
    $('a[data-toggle=\'tab\']').on('shown.bs.tab', function (e) {
        $('#live_console').scrollTop($('#live_console')[0].scrollHeight);
    });

    // Load Paused Console with Live Console Data
    $('#pause_console').click(function(){
        $('#paused_console').val($('#live_console').val());
    });

    function updatePlayerListVisibility(data) {
        // Server is On or Starting
        if(data !== 0) {
            $('#stats_players').show();
        } else {
            $('#stats_players').hide();
        }
    }

    @can('set-allocation', $server)
        // Send Request
        $('[data-action="set-connection"]').click(function (event) {
            event.preventDefault();
            var element = $(this);
            if (element.hasClass('active')) {
                return;
            }

            $.ajax({
                method: 'POST',
                url: '/server/{{ $server->uuidShort }}/ajax/set-connection',
                data: {
                    connection: element.data('connection')
                },
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            }).done(function (data) {
                swal({
                    type: 'success',
                    title: '',
                    text: data
                });
                $('#conn_options').find('li.active').removeClass('active');
                element.parent().addClass('active');
            }).fail(function (jqXHR) {
                console.error(jqXHR);
                var respError;
                if (typeof jqXHR.responseJSON.error === 'undefined' || jqXHR.responseJSON.error === '') {
                    respError = 'An error occured while attempting to perform this action.';
                } else {
                    respError = jqXHR.responseJSON.error;
                }
                swal({
                    type: 'error',
                    title: 'Whoops!',
                    text: respError
                });
            });
        });
    @endcan

    @can('send-command', $server)
        // Send Command to Server
        $('#console_command').submit(function (event) {

            event.preventDefault();
            var ccmd = $('#ccmd').val();
            if (ccmd == '') {
                return;
            }

            $('#sending_command').html('<i class=\'fa fa-refresh fa-spin\'></i>').addClass('disabled');
            $.ajax({
                type: 'POST',
                headers: {
                    'X-Access-Token': '{{ $server->daemonSecret }}',
                    'X-Access-Server': '{{ $server->uuid }}'
                },
                contentType: 'application/json; charset=utf-8',
                url: '{{ $node->scheme }}://{{ $node->fqdn }}:{{ $node->daemonListen }}/server/command',
                timeout: 10000,
                data: JSON.stringify({ command: ccmd })
            }).fail(function (jqXHR) {
                console.error(jqXHR);
                var error = 'An error occured while trying to process this request.';
                if (typeof jqXHR.responseJSON !== 'undefined' && typeof jqXHR.responseJSON.error !== 'undefined') {
                    error = jqXHR.responseJSON.error;
                }
                swal({
                    type: 'error',
                    title: 'Whoops!',
                    text: error
                });
            }).done(function () {
                $('#ccmd').val('');
            }).always(function () {
                $('#sending_command').html('&rarr;').removeClass('disabled');
            });
        });
    @endcan
    var can_run = true;
    function updateServerPowerControls (data) {

        // Reset Console Data
        if (data === 2) {
            $('#live_console').val($('#live_console').val() + '\n --+ Server Detected as Booting + --\n');
            $('#live_console').scrollTop($('#live_console')[0].scrollHeight);
        }

        // Server is On or Starting
        if(data == 1 || data == 2) {
            $("#console_command").slideDown();
            $('[data-attr="power"][data-action="start"]').addClass('disabled');
            $('[data-attr="power"][data-action="stop"], [data-attr="power"][data-action="restart"]').removeClass('disabled');
        } else {
            $("#console_command").slideUp();
            $('[data-attr="power"][data-action="start"]').removeClass('disabled');
            $('[data-attr="power"][data-action="stop"], [data-attr="power"][data-action="restart"]').addClass('disabled');
        }

        if(data !== 0) {
            $('[data-attr="power"][data-action="kill"]').removeClass('disabled');
        } else {
            $('[data-attr="power"][data-action="kill"]').addClass('disabled');
        }

    }

    $('[data-attr="power"]').click(function (event) {
        event.preventDefault();
        var action = $(this).data('action');
        var killConfirm = false;
        if (action === 'kill') {
            swal({
                type: 'warning',
                title: '',
                text: 'This operation will not save your server data gracefully. You should only use this if your server is failing to respond to normal stop commands.',
                showCancelButton: true,
                allowOutsideClick: true,
                closeOnConfirm: true,
                confirmButtonText: 'Kill Server',
                confirmButtonColor: '#d9534f'
            }, function () {
                setTimeout(function() {
                    powerToggleServer('kill');
                }, 100);
            });
        } else {
            powerToggleServer(action);
        }

    });

    function powerToggleServer(action) {
        $.ajax({
            type: 'PUT',
            headers: {
                'X-Access-Token': '{{ $server->daemonSecret }}',
                'X-Access-Server': '{{ $server->uuid }}'
            },
            contentType: 'application/json; charset=utf-8',
            data: JSON.stringify({
                action: action
            }),
            url: '{{ $node->scheme }}://{{ $node->fqdn }}:{{ $node->daemonListen }}/server/power',
            timeout: 10000
        }).fail(function(jqXHR) {
            var error = 'An error occured while trying to process this request.';
            if (typeof jqXHR.responseJSON !== 'undefined' && typeof jqXHR.responseJSON.error !== 'undefined') {
                error = jqXHR.responseJSON.error;
            }
            swal({
                type: 'error',
                title: 'Whoops!',
                text: error
            });
        });
    }
});

$(document).ready(function () {
    $('.server-index').addClass('active');
});
</script>
@endsection
