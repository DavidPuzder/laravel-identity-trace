<?php

namespace DavidPuzder\LaravelIdentityTrace\Services;

use DavidPuzder\LaravelIdentityTrace\Events\IdentityTraceDeviceCreatedEvent;
use DavidPuzder\LaravelIdentityTrace\Interfaces\IdentityTraceDeviceInterface;
use DavidPuzder\LaravelIdentityTrace\Models\IdentityTraceDevice;
use Jenssegers\Agent\Agent;

class IdentityTraceDeviceService {

    /**
     * @param IdentityTraceDeviceInterface $traceableInterface
     * @param array $deviceAgentData
     * @return IdentityTraceDevice
     */
    public function findOrCreateTraceableDeviceByAgent(IdentityTraceDeviceInterface $traceableInterface, array $deviceAgentData): IdentityTraceDevice
    {
        $identityTraceDevice = $this->findTraceableDeviceByAgent($traceableInterface, $deviceAgentData);

        if (is_null($identityTraceDevice)) {
            $identityTraceDevice = $this->createTraceableDeviceByAgent($traceableInterface, $deviceAgentData);
        }

        return $identityTraceDevice;
    }

    /**
     * @param IdentityTraceDeviceInterface $traceableInterface
     * @param Agent $agent
     * @return IdentityTraceDevice|null
     */
    public function findTraceableDeviceByAgent(IdentityTraceDeviceInterface $traceableInterface, array $deviceAgentData): ?IdentityTraceDevice
    {
        if (! $traceableInterface->hasDevices()) {
            return null;
        }

        return $traceableInterface
            ->devices()
            ->with('login')
            ->get()
            ->filter(fn (IdentityTraceDevice $identityTraceDevice) => $this->deviceMatch($identityTraceDevice, $deviceAgentData))
            ->first();
    }

    /**
     * @param IdentityTraceDeviceInterface $traceable
     * @param array $deviceAgentData
     * @return IdentityTraceDevice
     */
    public function createTraceableDeviceByAgent(IdentityTraceDeviceInterface $traceable, array $deviceAgentData): IdentityTraceDevice
    {
        $model = IdentityTraceDevice::class;
        $identityTraceDevice = new $model;

        $identityTraceDevice->platform = $deviceAgentData['platform'] ?? null;
        $identityTraceDevice->platform_version = $deviceAgentData['platform_version'] ?? null;
        $identityTraceDevice->browser = $deviceAgentData['browser'] ?? null;
        $identityTraceDevice->browser_version = $deviceAgentData['browser_version'] ?? null;
        $identityTraceDevice->device_type = $deviceAgentData['device_type'] ?? null;
        $identityTraceDevice->language = $deviceAgentData['language'] ?? null;

        $identityTraceDevice->traceable()->associate($traceable);

        $identityTraceDevice->save();

        event(new IdentityTraceDeviceCreatedEvent($identityTraceDevice));

        return $identityTraceDevice;
    }

    /**
     * @param IdentityTraceDevice $identityTraceDevice
     * @param array $deviceAgentData
     * @param array|null $attributes
     * @return bool
     */
    public function deviceMatch(IdentityTraceDevice $identityTraceDevice, array $deviceAgentData, array $attributes = null): bool
    {
        $attributes = is_null($attributes) ? $this->getDeviceMatchingAttributesConfig() : $attributes;
        $matches = 0;

        if (in_array('platform', $attributes)) {
            $matches += $identityTraceDevice->platform === $deviceAgentData['platform'];
        }

        if (in_array('platform_version', $attributes)) {
            $agentPlatformVersion = $deviceAgentData['platform_version'];
            $agentPlatformVersion = empty($agentPlatformVersion) ? '0' : $agentPlatformVersion;
            $matches += $identityTraceDevice->platform_version === $agentPlatformVersion;
        }

        if (in_array('browser', $attributes)) {
            $matches += $identityTraceDevice->browser === $deviceAgentData['browser'];
        }

        if (in_array('browser_version', $attributes)) {
            $matches += $identityTraceDevice->browser_version === $deviceAgentData['browser_version'];
        }

        if (in_array('language', $attributes)) {
            $matches += $identityTraceDevice->language === $deviceAgentData['language'];
        }

        return $matches === count($attributes);
    }

    /**
     * @return string[]
     */
    public function getDeviceMatchingAttributesConfig(): array
    {
        return [
            'platform',
            'platform_version',
            'browser',
        ];
    }
}
