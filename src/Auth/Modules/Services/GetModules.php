<?php

namespace AMovil\Auth\Modules\Services;

use AMovil\Auth\Modules\Domain\ModuleRepository;
use AMovil\Shared\Application\Response;

class GetModules
{
    private $repo;

    public function __construct(ModuleRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke()
    {
        $modules_id = $this->repo->get()
        ->groupBy('id_tracing')
        ->map(function($row){ return $row[0]->id_tracing; })
        ->toArray();
        $data = $this->repo->getAsTreeByIds(array_keys($modules_id));
        return new Response([], $data);
    }
}
