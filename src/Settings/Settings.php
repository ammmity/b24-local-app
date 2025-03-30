<?php

namespace App\Settings;

class Settings implements SettingsInterface
{
    public const ENVIRONMENT_PRODUCTION = 'production';
    public const ENVIRONMENT_DEVELOP = 'dev';

    public function __construct(
        private readonly array $settings
    )
    {}

    /**
     * @param string $key
     * @return mixed
     */
    public function get(string $key = '') : mixed
    {
        if (empty($key)) {
            return $this->settings;
        }
        return key_exists($key, $this->settings) ? $this->settings[$key] : null;
    }

    public function isProduction(): bool
    {
        return $this->settings['environment'] === self::ENVIRONMENT_PRODUCTION;
    }
}
