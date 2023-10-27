<?php

namespace DavidPuzder\LaravelIdentityTrace\Enums;

enum DeviceTypeEnum: int
{
    case DESKTOP = 1;
    case TABLET = 2;
    case MOBILE = 3;
    case BOT = 4;
}
