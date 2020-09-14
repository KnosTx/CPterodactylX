<?php

namespace Pterodactyl\Services\Eggs\Sharing;

use Ramsey\Uuid\Uuid;
use Pterodactyl\Models\Egg;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Contracts\Repository\EggRepositoryInterface;
use Pterodactyl\Contracts\Repository\NestRepositoryInterface;
use Pterodactyl\Exceptions\Service\Egg\BadEggFormatException;
use Pterodactyl\Exceptions\Service\InvalidFileUploadException;
use Pterodactyl\Contracts\Repository\EggVariableRepositoryInterface;
use Symfony\Component\Yaml\Exception\ParseException as YamlParseException;

class EggImporterService
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * @var \Pterodactyl\Contracts\Repository\EggVariableRepositoryInterface
     */
    protected $eggVariableRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\NestRepositoryInterface
     */
    protected $nestRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\EggRepositoryInterface
     */
    protected $repository;

    /**
     * EggImporterService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface $connection
     * @param \Pterodactyl\Contracts\Repository\EggRepositoryInterface $repository
     * @param \Pterodactyl\Contracts\Repository\EggVariableRepositoryInterface $eggVariableRepository
     * @param \Pterodactyl\Contracts\Repository\NestRepositoryInterface $nestRepository
     */
    public function __construct(
        ConnectionInterface $connection,
        EggRepositoryInterface $repository,
        EggVariableRepositoryInterface $eggVariableRepository,
        NestRepositoryInterface $nestRepository
    ) {
        $this->connection = $connection;
        $this->eggVariableRepository = $eggVariableRepository;
        $this->repository = $repository;
        $this->nestRepository = $nestRepository;
    }

    /**
     * Take an uploaded YAML file and parse it into a new egg.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param int $nest
     * @return \Pterodactyl\Models\Egg
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Service\Egg\BadEggFormatException
     * @throws \Pterodactyl\Exceptions\Service\InvalidFileUploadException
     */
    public function handle(UploadedFile $file, int $nest): Egg
    {
        if ($file->getError() !== UPLOAD_ERR_OK || ! $file->isFile()) {
            throw new InvalidFileUploadException(
                sprintf(
                    'The selected file ["%s"] was not in a valid format to import. (is_file: %s is_valid: %s err_code: %s err: %s)',
                    $file->getFilename(),
                    $file->isFile() ? 'true' : 'false',
                    $file->isValid() ? 'true' : 'false',
                    $file->getError(),
                    $file->getErrorMessage()
                )
            );
        }

        $fileContent = $file->openFile()->fread($file->getSize());

        $parsed = null;
        try {
            $parsed = Yaml::parse($fileContent, Yaml::PARSE_OBJECT_FOR_MAP);
        } catch (YamlParseException $exception) {
            $parsed = json_decode($fileContent);
            if (json_last_error() !== 0) {
                throw new BadEggFormatException(trans('exceptions.nest.importer.parse_error', [
                    'error' => $exception->getMessage(),
                ]), $exception);
            }
        }

        if (object_get($parsed, 'meta.version') === 'PTDL_v1') {
            $parsed['meta']['version'] = 'PTDL_v2';
            $parsed['config']['files'] = Yaml::dump(json_decode($parsed['config']['files'], true), 8, 2);
            $parsed['config']['startup'] = Yaml::dump(json_decode($parsed['config']['startup'], true), 8, 2);
            $parsed['config']['logs'] = Yaml::dump(json_decode($parsed['config']['logs'], true), 8, 2);
        }

        if (object_get($parsed, 'meta.version') !== 'PTDL_v2') {
            throw new InvalidFileUploadException(trans('exceptions.nest.importer.invalid_egg'));
        }

        $nest = $this->nestRepository->getWithEggs($nest);
        $this->connection->beginTransaction();

        $egg = $this->repository->create([
            'uuid' => Uuid::uuid4()->toString(),
            'nest_id' => $nest->id,
            'author' => object_get($parsed, 'author'),
            'name' => object_get($parsed, 'name'),
            'description' => object_get($parsed, 'description'),
            'features' => object_get($parsed, 'features'),
            // Maintain backwards compatability for eggs that are still using the old single image
            // string format. New eggs can provide an array of Docker images that can be used.
            'docker_images' => object_get($parsed, 'images') ?? [object_get($parsed, 'image')],
            'update_url' => object_get($parsed, 'meta.update_url'),
            'config_files' => object_get($parsed, 'config.files'),
            'config_startup' => object_get($parsed, 'config.startup'),
            'config_logs' => object_get($parsed, 'config.logs'),
            'config_stop' => object_get($parsed, 'config.stop'),
            'startup' => object_get($parsed, 'startup'),
            'script_install' => object_get($parsed, 'scripts.installation.script'),
            'script_entry' => object_get($parsed, 'scripts.installation.entrypoint'),
            'script_container' => object_get($parsed, 'scripts.installation.container'),
            'copy_script_from' => null,
        ], true, true);

        collect($parsed->variables)->each(function ($variable) use ($egg) {
            $this->eggVariableRepository->create(array_merge((array) $variable, [
                'egg_id' => $egg->id,
            ]));
        });

        $this->connection->commit();

        return $egg;
    }
}
