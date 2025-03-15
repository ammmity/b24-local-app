<?php
namespace App\Settings;
interface SettingsInterface
{
    /**
     * @param string $key
     * @return mixed
     */
    public function get(string $key): mixed;

    public function isProduction(): bool;
}
