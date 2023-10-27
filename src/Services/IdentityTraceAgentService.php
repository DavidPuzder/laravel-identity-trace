<?php

namespace DavidPuzder\LaravelIdentityTrace\Services;

use DavidPuzder\LaravelIdentityTrace\Enums\DeviceTypeEnum;
use Jenssegers\Agent\Agent;

class IdentityTraceAgentService {

    /**
     * @param Agent $agent
     * @return array
     */
    public function getAgentDataForDevice(Agent $agent): array
    {
        $devicePlatform = $this->getDevicePlatform($agent) ?? null;
        $devicePlatformVersion = $this->getDevicePlatformVersion($agent) ?? null;
        $browser = $this->getDeviceBrowser($agent) ?? null;
        $browserVersion = $this->getDeviceBrowserVersion($agent) ?? null;
        $deviceType = $this->getDeviceTypeFromAgent($agent) ?? null;
        $language = $this->getDeviceLanguage($agent) ?? null;
        $ip = $this->getDeviceIp() ?? null;

        return [
            'platform' => $devicePlatform,
            'platform_version' => $devicePlatformVersion,
            'browser' => $browser,
            'browser_version' => $browserVersion,
            'device_type' => $deviceType,
            'language' => $language,
            'ip' => $ip,
        ];
    }

    /**
     * @param Agent $agent
     * @return bool|string
     */
    public function getDevicePlatform(Agent $agent): bool|string
    {
        return $agent->platform();
    }

    /**
     * @param Agent $agent
     * @return float|bool|string
     */
    public function getDevicePlatformVersion(Agent $agent): float|bool|string
    {
        return $agent->version($this->getDevicePlatform($agent));
    }

    /**
     * @param Agent $agent
     * @return bool|string
     */
    public function getDeviceBrowser(Agent $agent): bool|string
    {
        return $agent->browser();
    }

    /**
     * @param Agent $agent
     * @return float|bool|string
     */
    public function getDeviceBrowserVersion(Agent $agent): float|bool|string
    {
        return $agent->version($this->getDeviceBrowser($agent));
    }

    /**
     * @param Agent $agent
     * @return int
     */
    public function getDeviceTypeFromAgent(Agent $agent): int
    {
        return $this->getDeviceTypeEnumFromAgent($agent)?->value ?? DeviceTypeEnum::BOT->value;
    }

    /**
     * @param Agent $agent
     * @return string|null
     */
    public function getDeviceLanguage(Agent $agent): ?string
    {
        return count($agent->languages()) ? $agent->languages()[0] : null;
    }

    /**
     * @return string|null
     */
    public function getDeviceIp(): ?string
    {
        return request()->ip();
    }

    /**
     * @param Agent $agent
     * @return DeviceTypeEnum
     */
    public function getDeviceTypeEnumFromAgent(Agent $agent): DeviceTypeEnum
    {
        return match (true) {
            $agent->isDesktop() => DeviceTypeEnum::DESKTOP,
            $agent->isTablet() => DeviceTypeEnum::TABLET,
            $agent->isMobile() => DeviceTypeEnum::MOBILE,
            default => DeviceTypeEnum::BOT,
        };
    }
}
