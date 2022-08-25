<?php

namespace HexMakina\kadro\Auth;

class Permission extends \HexMakina\TightORM\TightModel
{
    public const TABLE_NAME = 'kadro_permission';
    public const TABLE_ALIAS = 'permission';

    public const GROUP_ADMIN = 'group_admin';
    public const GROUP_STAFF = 'group_social';
    public const GROUP_MEDIC = 'group_medical';

    public function __toString()
    {
        return $this->get('name');
    }
}
