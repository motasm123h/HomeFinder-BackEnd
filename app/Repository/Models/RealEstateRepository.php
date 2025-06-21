<?php

namespace App\Repository\Models;

use App\Models\RealEstate;
use App\Models\RealEstate_images;
use App\Repository\Repo;
use App\Services\MediaService;


class RealEstateRepository extends Repo
{

    public function __construct(
        private MediaService $mediaService,

    ) {
        parent::__construct(RealEstate::class,);
    }

    public function create(array $data): RealEstate
    {
        return parent::create($data);
    }

    public function update(array $data, int $id): RealEstate
    {
        $realEstate = parent::findOrFail($id);
        $realEstate->update($data);
        return $realEstate;
    }
    public function createProperties(RealEstate $realEstate, array $properties): void
    {
        $realEstate->properties()->create($properties);
    }

    public function updateProperties(RealEstate $realEstate, array $properties): void
    {
        $realEstate->properties()->updateOrCreate(
            ['real_estate_id' => $realEstate->id],
            $properties
        );
    }

    public function getDetails(int $id)
    {

        $realEstate = RealEstate::findOrFail($id)->load([
            'properties',
            'location',
            'images',
            'user:id,name,email,status',
            'user.contact:id,phone_no,username,user_id'
        ]);

        $view = $realEstate->view()->first();
        if ($view) {
            $view->increment('counter');
        } else {
            $realEstate->view()->create(['counter' => 1]);
        }

        return $realEstate;
    }

    public function delete(int $id): bool
    {
        return parent::delete($id);
    }
}
