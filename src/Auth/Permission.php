<?php

namespace HexMakina\kadro\Auth;

class Permission extends \HexMakina\TightORM\TightModel
{
    /**
     * @var string
     */
    public const TABLE_NAME = 'kadro_permission';

    /**
     * @var string
     */
    public const TABLE_ALIAS = 'permission';

    /**
     * @var string
     */
    public const GROUP_ADMIN = 'group_admin';

    /**
     * @var string
     */
    public const GROUP_STAFF = 'group_social';

    /**
     * @var string
     */
    public const GROUP_MEDIC = 'group_medical';

    public function __toString()
    {
        return $this->get('name');
    }
}
