<?php

namespace USMB\UserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class USMBUserBundle extends Bundle
{

    public function getParent()
    {
        return 'FOSUserBundle';
    }
}
