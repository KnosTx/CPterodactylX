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

namespace Pterodactyl\Repositories\Daemon;

use GuzzleHttp\Client;
use Illuminate\Foundation\Application;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Pterodactyl\Contracts\Repository\Daemon\BaseRepositoryInterface;

class BaseRepository implements BaseRepositoryInterface
{
    protected $app;
    protected $accessServer;
    protected $accessToken;
    protected $node;
    protected $config;
    protected $nodeRepository;

    public function __construct(
        Application $app,
        ConfigRepository $config,
        NodeRepositoryInterface $nodeRepository
    ) {
        $this->app = $app;
        $this->config = $config;
        $this->nodeRepository = $nodeRepository;
    }

    public function setNode($id)
    {
        // @todo accept a model
        $this->node = $this->nodeRepository->find($id);

        return $this;
    }

    public function getNode()
    {
        return $this->node;
    }

    public function setAccessServer($server = null)
    {
        $this->accessServer = $server;

        return $this;
    }

    public function getAccessServer()
    {
        return $this->accessServer;
    }

    public function setAccessToken($token = null)
    {
        $this->accessToken = $token;

        return $this;
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function getHttpClient($headers = [])
    {
        if (! is_null($this->accessServer)) {
            $headers['X-Access-Server'] = $this->getAccessServer();
        }

        if (! is_null($this->accessToken)) {
            $headers['X-Access-Token'] = $this->getAccessToken();
        } elseif (! is_null($this->node)) {
            $headers['X-Access-Token'] = $this->getNode()->daemonSecret;
        }

        return new Client([
            'base_uri' => sprintf('%s://%s:%s/', $this->getNode()->scheme, $this->getNode()->fqdn, $this->getNode()->daemonListen),
            'timeout' => $this->config->get('pterodactyl.guzzle.timeout'),
            'connect_timeout' => $this->config->get('pterodactyl.guzzle.connect_timeout'),
            'headers' => $headers,
        ]);
    }
}
