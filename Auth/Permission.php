<?php

namespace HexMakina\kadro\Auth;

class Permission extends \HexMakina\TightORM\TightModel
{
    const TABLE_NAME = 'kadro_permission';
    const TABLE_ALIAS = 'permission';

    const GROUP_ADMIN = 'group_admin';
    const GROUP_STAFF = 'group_social';
    const GROUP_MEDIC = 'group_medical';

    public function __toString()
    {
        return $this->get('name');
    }
}
