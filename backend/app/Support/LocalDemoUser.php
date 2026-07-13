<?php

namespace App\Support;

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Collection;

class LocalDemoUser extends GenericUser
{
    public function loadMissing($relations = null): static
    {
        return $this;
    }

    public function esAdmin(): bool
    {
        return strtoupper((string) ($this->attributes['usuario_grupo'] ?? '')) === 'ADMIN';
    }

    public function esJefeArea(): bool
    {
        return $this->areasSupervisadas()->isNotEmpty();
    }

    public function areasSupervisadas(): Collection
    {
        return collect($this->attributes['areas_supervisadas'] ?? [])
            ->filter()
            ->map(fn ($areaId) => (int) $areaId)
            ->unique()
            ->values();
    }

    public function jefaturasActivas(): Collection
    {
        return collect($this->attributes['jefaturas'] ?? []);
    }
}